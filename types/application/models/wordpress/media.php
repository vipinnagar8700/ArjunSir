<?php

/**
 * Class Types_Wordpress_Media
 *
 * @since 2.3
 */
class Types_Wordpress_Media implements Types_Wordpress_Media_Interface {

	/**
	 * @param string $url
	 *
	 * @return int|null
	 *
	 * @codeCoverageIgnore We test if the function exists, but nothing more as it's just an alias for a static method
	 */
	public function get_attachment_id_by_url( $url ) {
		return Toolset_Utils::get_attachment_id_by_url( $url );
	}


	/**
	 * @param int $id
	 *
	 * @return array|bool|null|WP_Post
	 */
	public function get_attachment_by_id( $id ) {
		$attachment = get_post( $id, ARRAY_A );

		if ( ! $attachment ) {
			return false;
		}

		$attachment['alt'] = get_post_meta( $attachment['ID'], '_wp_attachment_image_alt', true );

		return $attachment;
	}


	/**
	 * @param int|null $size If set it will return only the values of $size.
	 *     If $size is defined but not available, it will return false.
	 *
	 * @return false|array
	 */
	public function get_addtional_image_sizes( $size = null ) {
		global $_wp_additional_image_sizes;

		if ( $size === null ) {
			return $_wp_additional_image_sizes;
		}

		if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			return $_wp_additional_image_sizes[ $size ];
		}

		return false;
	}
}
