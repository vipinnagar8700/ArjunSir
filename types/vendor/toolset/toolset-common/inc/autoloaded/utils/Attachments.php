<?php

namespace OTGS\Toolset\Common\Utils;

use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Attachment-related utilities.
 *
 * @since Types 3.3
 */
class Attachments {

	const CACHE_KEY_PREFIX = 'toolset_attachment_id_';
	const CACHE_GROUP = 'toolset_attachment_ids';

	/**
	 * This is non-zero in case there's a third-party caching mechanism in place.
	 * Zero value used in wp_cache_set() could mean "infinity" in that case.
	 */
	const CACHE_EXPIRATION_SECONDS = 10;

	/** @var TypesGuidIdGateway */
	private $types_guid_id_gateway;

	/** @var string */
	private $base_upload_directory;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * Attachments constructor.
	 *
	 * @param TypesGuidIdGateway $types_guid_id_proxy
	 * @param WpmlService $wpml_service
	 */
	public function __construct( TypesGuidIdGateway $types_guid_id_proxy, WpmlService $wpml_service = null ) {
		$this->types_guid_id_gateway = $types_guid_id_proxy;
		$this->wpml_service = $wpml_service ?: WpmlService::get_instance();
	}

	/**
	 * Return an ID of an attachment by searching the database with the file URL.
	 * Using the Types Post GUID ID table, if that does not deliver data, it's fetching the image
	 * by using it's post name. Supporting post names with number: e.g. my-image-3
	 * Once the image is fetched it's applied to the Types table for having a faster fetch next call.
	 *
	 * @param string $url URL of the file.
	 *
	 * @return int|null Attachment ID if it exists.
	 * @since 2.2.9
	 * @since Types 3.3 extracted to a separate class.
	 * @since Types 3.3.6 Improved query to use post_name instead of GUID. The GUID will still be checked on the fetched
	 *        result.
	 */
	public function get_attachment_id_by_url( $url ) {
		// First, see if we've already done this during this request.
		$cache_key = $this->get_cache_key( $url );
		$cached_value_found = null;
		$cached_id = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $cached_value_found );
		if ( $cached_value_found ) {
			return $cached_id;
		}

		// Split the $url into two parts with the upload directory as the separator.
		$parsed_url = explode( wp_parse_url( $this->get_base_upload_directory(), PHP_URL_PATH ), $url );

		// Return nothing if there aren't any $url parts
		$attachment_path = toolset_getarr( $parsed_url, 1 );
		if ( ! isset( $attachment_path ) || empty( $attachment_path ) ) {
			return null;
		}

		// Get the host of the $url, ignoring www.
		$file_host = str_ireplace( 'www.', '', wp_parse_url( $url, PHP_URL_HOST ) );

		// Return nothing if the current host and $url host do not match.
		if ( ! in_array( $file_host, $this->wpml_service->get_site_domains(), true ) ) {
			return null;
		}

		$upload_dir = wp_get_upload_dir();

		if ( ! is_array( $upload_dir ) || ! array_key_exists( 'basedir', $upload_dir ) ) {
			// The site has no upload dir at all.
			return null;
		}

		$attachment_file = $upload_dir['basedir'] . $attachment_path;

		if ( ! file_exists( $attachment_file ) ) {
			// File does not exist on system. Abort fetching ID of non-existing file.
			return null;
		}

		// Try to fetch id by using our toolset_post_guid_id table.
		// This method returns:
		// - NULL on old Types versions if the variable is not set (< 3.0 for sure).
		// - FALSE otherwise.
		// So it will must probably return FALSE by now
		$post_id = $this->types_guid_id_gateway->get_id_by_guid( $url, true );
		$post_id = ( false === $post_id || null === $post_id )
			? $post_id
			// Do not run over the WPML compatibility if we do not have an item to translate.
			// This will return NULL if the original attachment ID exists but its translation does not.
			: $this->wpml_service->translate_element( $post_id, 'attachment' );
		if ( $post_id || null === $post_id ) {
			$return = $post_id ? (int) $post_id : null;
			wp_cache_set( $cache_key, $return, self::CACHE_GROUP, self::CACHE_EXPIRATION_SECONDS );
			return $return;
		}

		global $wpdb;

		// Attachment vars
		$attachment_path_info = pathinfo( $attachment_file );
		// Post name: image
		$attachment_post_name = sanitize_title( $attachment_path_info['filename'] );
		// Post name with extension: my-image-jpg
		$attachment_post_name_with_ext = sanitize_title( $attachment_path_info['basename'] );

		$post_name_numeric_extension = 1;
		$attachment_id = false;

		/**
		 * When an image is queried by post name, the script will increase the numeric extension on every iteration.
		 * my-slug, my-slug-2, my-slug-3. Now if my-slug has no result at all, the $abort_if_no_result will be set
		 * to true. If than my-slug-2 also delivers no result at all, the loop will be aborted.
		 * Worst case: my-slug, my-slug-2, my-slug-3 are DELETED and the requested image has my-slug-4. In that case
		 * the id will not being catched. Will never happen.
		 *
		 * @var int
		 */
		$fetches_without_result_count = 0;
		$maximum_fetches_without_result = 3;

		do {
			$numeric_extension = $post_name_numeric_extension > 1 ? '-' . $post_name_numeric_extension : '';

			$query = $wpdb->prepare(
				"SELECT ID, guid FROM $wpdb->posts WHERE post_name = %s OR post_name = %s LIMIT 1",
				$attachment_post_name . $numeric_extension,
				$attachment_post_name_with_ext . $numeric_extension
			);

			$img = $wpdb->get_row( $query ); // phpcs:ignore

			if( empty( $img ) ) {
				/*
				 * When an image is uploaded with a filename like "header (1).jpg" WP will give it the post name
				 * header-1 (expected) but will change the file name to "header-1-1.jpg" (woot). So the image can't
				 * be found by searching for "header-1-1" in the postnames.
				 */
				$attachment_post_name_without_double_count_number = preg_replace( '/(-[0-9]{1,10})-[0-9]{1,10}$/', '$1', $attachment_post_name );
				if( $attachment_post_name_without_double_count_number !== $attachment_post_name ) {
					$query = $wpdb->prepare(
						"SELECT ID, guid FROM $wpdb->posts WHERE post_name = %s OR post_name = %s LIMIT 1",
						$attachment_post_name_without_double_count_number . $numeric_extension,
						$attachment_post_name_without_double_count_number . $numeric_extension
					);
				}

				$img = $wpdb->get_row( $query ); // phpcs:ignore
			}

			if ( ! empty( $img ) ) {
				// Check if guid matches with the attachment.
				if ( substr_compare( $img->guid, $attachment_path, -strlen( $attachment_path ) ) === 0 ) {
					// ID found.
					$attachment_id = (int) $img->ID;
					break;
				}

				// Not the image but row found. Reset allowe fetches without result.
				$fetches_without_result_count = 0;
			} else {
				// No entry found with the given post_name.
				if ( $fetches_without_result_count >= $maximum_fetches_without_result ) {
					// Maximum tries without result reached. Abort.
					$attachment_id = null;
					break;
				}

				// No result.
				$fetches_without_result_count++;
			}

			// Increase numeric extension to check my-post-name-x++ next iteration.
			$post_name_numeric_extension++;
		} while ( false === $attachment_id );

		if ( ! $attachment_id ) {
			// Last resort: Check by the _wp_attached_file postmeta in case the GUID doesn't
			// correspond to the current subpath + file anymore, for whatever reason
			// (it could be caused by some third-party manipulation with attachments, for example).
			//
			// The value in the postmeta is authoritative.
			$attachment_path_without_leading_slash = strpos( $attachment_path, '/' ) === 0
				? substr( $attachment_path, 1 )
				: $attachment_path;

			$query = $wpdb->prepare(
				"SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE meta_key = '_wp_attached_file'
					AND meta_value = %s",
				$attachment_path_without_leading_slash
			);

			$id_by_postmeta = (int) $wpdb->get_var( $query );

			if ( 0 !== $id_by_postmeta ) {
				$attachment_id = $id_by_postmeta;
			}
		}

		if ( null !== $attachment_id ) {
			// Again, make sure we return and cache the ID of the attachment in the correct (current) language.
			// Thhe types_guid_id table doesn't take this into account but we'll translate the ID again when we're
			// fetching it from there.
			$attachment_id = $this->wpml_service->translate_element( $attachment_id, 'attachment' );
		}

		// Update the table. Also doing this if $attachment_id is NULL to prevent future failing queries.
		$this->types_guid_id_gateway->insert( $url, $attachment_id );

		// Update the cache in case the call is repeated during the same request.
		wp_cache_set( $cache_key, $attachment_id, self::CACHE_GROUP, self::CACHE_EXPIRATION_SECONDS );

		// Return attachment id.
		return $attachment_id;
	}


	private function get_base_upload_directory() {
		if( null === $this->base_upload_directory ) {
			$upload_dir_info = wp_get_upload_dir();
			$this->base_upload_directory = toolset_getarr( $upload_dir_info, 'baseurl', WP_CONTENT_URL );
		}

		return $this->base_upload_directory;
	}


	/**
	 * Calculate cache key for a given URL, under which the attachment ID will be stored.
	 *
	 * @param string $url
	 * @return string
	 * @since Types 3.3.8
	 */
	private function get_cache_key( $url ) {
		// Make sure we never mix data for different languages (as the same URL can translate to different attachment IDs).
		$language_suffix = $this->wpml_service->is_wpml_active_and_configured()
			? '##' . $this->wpml_service->get_current_language()
			: '';

		return md5( self::CACHE_KEY_PREFIX . $url . $language_suffix );
	}

}
