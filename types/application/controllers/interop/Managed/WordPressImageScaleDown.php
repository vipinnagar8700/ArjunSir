<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed;

use Exception;
use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;
use Toolset_Files;

/**
 * Make Types work properly with the WordPress mechanism to automatically scale down large images on upload.
 *
 * By default, WordPress provides the scaled-down version of large images, but we need to store the original image,
 * otherwise we won't be able to utilize performance optimizations when rendering images via the "types" shortcode.
 *
 * So, when saving custom fields, we need to revert what WordPress does to the image URL coming from the Media Upload
 * dialog.
 *
 * @since 3.3.12 Extracted from legacy code into a dedicated class.
 */
class WordPressImageScaleDown implements HandlerInterface2 {


	/** @var int As hardcoded in WordPress code. */
	const DEFAULT_BIG_IMAGE_SIZE_THRESHOLD = 2560;

	/**
	 * @var string[] Primitive caching due to the fact that the image field value safe filter may be called
	 *     several times in a row due to the ebb and flow of legacy code.
	 */
	private $image_url_to_original = [];


	/** @var Toolset_Files */
	private $files;


	/** @var null|int */
	private $big_image_size_threshold;

	/** @var string[]|null */
	private $upload_dir_cache;


	/**
	 * WordPressImageScaleDown constructor.
	 *
	 * @param Toolset_Files $files
	 */
	public function __construct( Toolset_Files $files ) {
		$this->files = $files;
	}


	/**
	 * Initialization.
	 */
	public function initialize() {
		add_filter( 'wpcf_fields_type_image_value_save', [ $this, 'on_image_value_save' ] );
	}


	/**
	 * When saving an image field with a value that corresponds to an attachment ID, ensure that the URL
	 * corresponds to the original image version and not to a scaled-down one.
	 *
	 * This becomes especially relevant since WordPress 5.3:
	 *
	 * @link https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
	 *
	 * Note: This is happening during a save operation in the admin only, so performance is not a critical concern here,
	 * but still important, since there may be many images at once.
	 *
	 * @param string|string[]|mixed $image_url_or_urls
	 *
	 * @return string|string[]|mixed
	 * @noinspection PhpUnused
	 * @since 3.3.7
	 */
	public function on_image_value_save( $image_url_or_urls ) {
		/**
		 * Filter types_adjust_large_image_url_on_save.
		 *
		 * Allow turning off the mechanism of adjusting uploaded image URLs from large to original. It can be used
		 * to prevent performance issues on extremely large sites or compatibility issues with third-party plugins
		 * in very rare cases (when the plugin produces different output of the big_image_size_threshold filter
		 * for different images).
		 *
		 * @return bool
		 * @since 3.3.12
		 */
		if (
			! function_exists( 'wp_get_original_image_path' )
			|| ! apply_filters( 'types_adjust_large_image_url_on_save', true )
		) {
			return $image_url_or_urls;
		}

		$big_image_size_threshold = $this->get_big_image_size_threshold( $this->get_first_url( $image_url_or_urls ) );

		if ( $big_image_size_threshold ) {
			// It's a trap! We may receive multiple values here.
			if ( is_string( $image_url_or_urls ) ) {
				$image_url_or_urls = $this->update_single_url( $image_url_or_urls, $big_image_size_threshold );
			} elseif ( is_array( $image_url_or_urls ) ) {
				$image_url_or_urls = array_map( function ( $image_url_or_urls ) use ( $big_image_size_threshold ) {
					return $this->update_single_url( $image_url_or_urls, $big_image_size_threshold );
				}, $image_url_or_urls );
			}
		}

		return $image_url_or_urls;
	}


	/**
	 * @param string|string[]|mixed $image_url_or_urls
	 *
	 * @return string
	 */
	private function get_first_url( $image_url_or_urls ) {
		if ( is_string( $image_url_or_urls ) ) {
			return $image_url_or_urls;
		}

		if ( is_array( $image_url_or_urls ) ) {
			return reset( $image_url_or_urls );
		}

		return '';
	}


	/**
	 * Try to retrieve the threshold for big image size as carefully as possible.
	 *
	 * @param string $image_url
	 *
	 * @return mixed
	 */
	private function get_big_image_size_threshold( $image_url ) {
		if ( null === $this->big_image_size_threshold ) {
			// big_image_size_threshold
			//
			// This is a filter that WordPress core usually applies with parameters for a specific file,
			// which we don't have and can't have at this point (because of performance reasons).
			//
			// See https://developer.wordpress.org/reference/functions/wp_create_image_subsizes/
			//
			// If any third-party software that hooks into this filter can't deal with empty/unexpected input,
			// it ought to ignore it and pass along the original value. But we do our best not to rely on this
			// assumption. Hence try/catch block and attempt to pass reasonably looking data even though they may
			// not be correct.
			//
			// Note: This is rather safe because the vast majority of this filter usages don't even access
			// the additional parameters and just override the final value.
			//
			// Note: The try-catch block is constructed to catch what can be caught across PHP versions:
			// https://www.php.net/manual/en/language.errors.php7.php#119652
			try {
				$this->big_image_size_threshold = apply_filters(
					'big_image_size_threshold',
					self::DEFAULT_BIG_IMAGE_SIZE_THRESHOLD,
					// Let's pretend we have a large image.
					[ self::DEFAULT_BIG_IMAGE_SIZE_THRESHOLD, self::DEFAULT_BIG_IMAGE_SIZE_THRESHOLD ],
					// This has a very good chance of being a valid path.
					$this->image_url_to_local_path( $image_url ),
					// Unfortunately, we can't have the attachment ID because that would erase any performance saving
					// we're attempting here.
					0
				);
			} catch ( Exception $e ) {
				$this->big_image_size_threshold = self::DEFAULT_BIG_IMAGE_SIZE_THRESHOLD;
			} /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */ /** @noinspection PhpFullyQualifiedNameUsageInspection */
			catch ( \Throwable $t ) { // Don't import Throwable as it may not be defined in lower PHP versions.
				$this->big_image_size_threshold = self::DEFAULT_BIG_IMAGE_SIZE_THRESHOLD;
			}
		}

		return $this->big_image_size_threshold;
	}


	/**
	 * Process a single image URL and replace it with the original size one if necessary.
	 *
	 * Use a primitive cache to prevent redundant calls (which actually happens because of the legacy codebase).
	 *
	 * @param string $image_url
	 * @param int|false|mixed $big_image_size_threshold
	 *
	 * @return string Updated image URL.
	 */
	private function update_single_url( $image_url, $big_image_size_threshold ) {
		if ( ! array_key_exists( $image_url, $this->image_url_to_original ) ) {
			$this->image_url_to_original[ $image_url ] = $this->calculate_single_url( $image_url, $big_image_size_threshold );
		}

		return $this->image_url_to_original[ $image_url ];
	}


	/**
	 * Actually calculate the new URL for a single image.
	 *
	 * @param string $image_url
	 * @param int|false|mixed $big_image_size_threshold
	 *
	 * @return string
	 */
	private function calculate_single_url( $image_url, $big_image_size_threshold ) {
		// First, try the route without expensively querying the attachment ID by image URL.
		// If the current image doesn't have a suffix indicating it may be the scaled-down large image,
		// we'll consider it an original.
		//
		// Otherwise, we try to shoot straight for the image file without the suffix and if it exists,
		// that is our original one.
		//
		// Using "scaled" and "large-{$size}" suffixes since both can occur, depending on the WordPress version.
		$detected_suffix = null;
		foreach ( [ '-scaled', '-large-' . $big_image_size_threshold ] as $large_image_suffix ) {
			if ( strpos( $image_url, $large_image_suffix ) !== false ) {
				$detected_suffix = $large_image_suffix;
				break;
			}
		}

		if ( null === $detected_suffix ) {
			// No need to search for original as this is image is not affected by default settings of
			// https://developer.wordpress.org/reference/hooks/big_image_size_threshold/
			return $image_url;
		}

		// So, we know the $image_url already is a scaled-down large image at this point.
		$maybe_original_url = str_replace( $detected_suffix, '', $image_url );

		if ( $this->files->file_exists( $this->image_url_to_local_path( $maybe_original_url ) ) ) {
			return $maybe_original_url;
		}

		// If the performant method didn't work, we have to go the safe but expensive route.
		$attachment_id = attachment_url_to_postid( $image_url );
		if ( $attachment_id ) {
			$updated_url = wp_get_original_image_url( $attachment_id );
			if ( is_string( $updated_url ) ) {
				$image_url = $updated_url;
			}
		}

		return $image_url;
	}


	/**
	 * Attempt to translate the image URL into a path on the server.
	 *
	 * Note that the image may be on another server, in which case this won't provide a sensible result.
	 *
	 * @param string $image_url
	 *
	 * @return string
	 */
	private function image_url_to_local_path( $image_url ) {
		if ( null === $this->upload_dir_cache ) {
			// WordPress core has caching but also applies some filters every time, checks for directory existence,
			// and so on. We don't want that more than once here.
			$this->upload_dir_cache = wp_upload_dir();
		}

		$upload_dir = $this->upload_dir_cache;

		return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );
	}
}
