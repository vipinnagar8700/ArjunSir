<?php

/**
 * @since 2.3
 */
class Types_Media_Service {

	/**
	 * @var Types_Interface_Media[]
	 */
	private $media = array();

	/**
	 * @var Types_Media_Mapper_Interface
	 */
	private $mapper;


	/**
	 * @param string $url
	 *
	 * @return Types_Interface_Media|false
	 */
	public function find_by_url( $url ) {
		if ( array_key_exists( $url, $this->media ) ) {
			return $this->media[ $url ];
		}

		$media = $this->get_mapper()->find_by_url( $url );

		if ( $media ) {
			$id = $media->get_id();
			if ( $id ) {
				$this->media[ $id ] = $media;
			}
		}

		return $this->media[ $url ] = $media;
	}


	/**
	 * @param int $id
	 *
	 * @return Types_Interface_Media|Types_Media
	 */
	public function find_by_id( $id ) {
		if ( array_key_exists( $id, $this->media ) ) {
			return $this->media[ $id ];
		}

		$media = $this->get_mapper()->find_by_id( $id );

		return $this->media[ $media->get_url() ] = $this->media[ $id ] = $media;
	}


	/**
	 * @param Types_Interface_Media $media
	 * @param array $args
	 * @param bool $domain_image
	 *
	 * @return string
	 */
	public function resize_image( Types_Interface_Media $media, $args, $domain_image = true ) {
		// todo [since 2.3] this method is a container of legacy methods
		$args = array_merge( array(
			'return' => 'object',
			'suppress_errors' => false,
			'clear_cache' => false,
		), $args );

		if ( ! $domain_image && wpcf_get_settings( 'images_remote' ) ) {
			if ( ! function_exists( 'wpcf_fields_image_get_remote' ) ) {
				require_once( WPCF_EMBEDDED_ABSPATH . '/includes/fields/image.php' );
			}
			$remote = wpcf_fields_image_get_remote( $media->get_url() );

			if ( is_wp_error( $remote ) ) {
				return $media->get_url();
			}

			$image_abspath = $remote['abspath'];
		} else {
			if ( ! class_exists( 'Types_Image_Utils' ) ) {
				require_once WPCF_EMBEDDED_ABSPATH . '/views/image.php';
			}
			$image_abspath = Types_Image_Utils::getAbsPath( $media->get_url() );
		}

		WPCF_Loader::loadView( 'image' );
		$resized = types_image_resize( $image_abspath, $args );

		if ( is_wp_error( $resized ) ) {
			return $media->get_url();
		}

		$image_abspath = $resized->path;
		if ( wpcf_get_settings( 'add_resized_images_to_library' ) ) {
			if ( ! function_exists( 'wpcf_image_is_attachment' ) || ! function_exists( 'wpcf_image_add_to_library' ) ) {
				require_once( WPCF_EMBEDDED_ABSPATH . '/includes/fields/image.php' );
			}
			if ( ! wpcf_image_is_attachment( $resized->url ) ) {
				global $post;
				wpcf_image_add_to_library( $post, $image_abspath );
			}
		}

		return $resized->url;
	}


	/**
	 * Allow to override mapper
	 *
	 * @param Types_Media_Mapper_Interface $mapper
	 */
	public function set_mapper( Types_Media_Mapper_Interface $mapper ) {
		$this->mapper = $mapper;
	}


	/**
	 * If no mapper set it will use Types_Media_Mapper as default
	 *
	 * @return Types_Media_Mapper_Interface
	 */
	private function get_mapper() {
		if ( $this->mapper !== null ) {
			return $this->mapper;
		}

		return $this->mapper = new Types_Media_Mapper( new Types_Wordpress_Media() );
	}


	/**
	 * Delete all Types resized images by attachment post id
	 *
	 * @action delete_attachment
	 *
	 * @param int $attachment_post_id
	 */
	public function delete_resized_images_by_attachment_id( $attachment_post_id ) {
		if ( ! $attachment_file = get_attached_file( $attachment_post_id ) ) {
			// no attachment file for $attachment_post_id
			return;
		}

		$attachment_file_path_parts = pathinfo( $attachment_file );

		$files_pattern = $attachment_file_path_parts['dirname'] . DIRECTORY_SEPARATOR
			. $attachment_file_path_parts['filename'] . '-wpcf*';

		foreach ( glob( $files_pattern ) as $filename ) {
			@unlink( $filename );
		}
	}
}
