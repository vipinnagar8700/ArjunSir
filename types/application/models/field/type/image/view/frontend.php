<?php

/**
 * Handles view specific tasks for field "Image"
 *
 * @since 2.3
 */
class Types_Field_Type_Image_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/** @var Types_Wordpress_Media_Interface */
	private $wordpress_media;

	/** @var Types_Media_Service */
	private $media_service;

	/** @var Types_Site_Domain */
	private $site_domain;

	/** @var Types_View_Placeholder_Interface */
	private $view_placeholder;

	/** @var Types_View_Decorator_Image */
	private $decorator_image;

	/** @var Types_View_Decorator_Index */
	private $decorator_index;

	/** @var bool */
	private $_always_display_media_library_modifications;


	/**
	 * Types_Field_Type_Single_Line_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Image $entity
	 * @param Types_Wordpress_Media $wordpress_media
	 * @param Types_Media_Service $media_service
	 * @param Types_Site_Domain $site_domain
	 * @param Types_View_Placeholder_Media $view_placeholder_media
	 * @param Types_View_Decorator_Image $decorator_image
	 * @param Types_View_Decorator_Index $decorator_index
	 * @param array $params
	 */
	public function __construct(
		Types_Field_Type_Image $entity,
		Types_Wordpress_Media $wordpress_media,
		Types_Media_Service $media_service,
		Types_Site_Domain $site_domain,
		Types_View_Placeholder_Media $view_placeholder_media,
		Types_View_Decorator_Image $decorator_image,
		Types_View_Decorator_Index $decorator_index,
		$params = array()
	) {
		$this->entity = $entity;
		$this->wordpress_media = $wordpress_media;
		$this->media_service = $media_service;
		$this->site_domain = $site_domain;
		$this->view_placeholder = $view_placeholder_media;
		$this->decorator_image = $decorator_image;
		$this->decorator_index = $decorator_index;

		$this->prepare_params( $this->normalise_user_values( $params ) );
	}


	/**
	 * Gets value when output is not html
	 *
	 * @return string
	 */
	public function get_value() {
		$rendered_values = [];
		if ( $this->is_raw_output() ) {
			// raw value
			$rendered_values = $this->entity->get_value_filtered( $this->params );
		} else {
			$is_filter_used = serialize( $this->entity->get_value() )
				!== serialize( $this->entity->get_value_filtered( $this->params ) );

			if ( $is_filter_used ) {
				foreach ( (array) $this->entity->get_value_filtered( $this->params ) as $value ) {
					$rendered_values[] = $this->filter_field_value_after_decorators( $value, $value );
				}
			} else {
				$rendered_values = $this->get_images();
			}
		}

		return $this->get_rendered_value( $rendered_values );
	}


	/**
	 * @return array
	 */
	private function get_images() {
		$rendered_value = array();

		// user settings via shortcode
		$output_registered_image_size = $this->output_registered_image_size();
		$output_custom_image_size = $this->output_custom_image_size();

		$values = $this->entity->get_value();

		// check if a specific image of a repeatable image field is requested
		/** @noinspection TypeUnsafeComparisonInspection */
		if ( isset( $this->params['index'] ) && ( ! empty( $this->params['index'] ) || $this->params['index'] == 0 ) ) {
			$values = $this->decorator_index->get_value( $values, $this->params );

			// normally the index attribute will be considered as last step pre-rendering a field
			// but as image loading is not only reading the db value of the field we apply it before loading the data
			// to prevent another run through the Types_View_Decorator_Index we need to null the 'index' attribute
			$this->params['index'] = null;
		}

		// loop over images (maybe more images due to repetitive option)
		foreach ( (array) $values as $url ) {
			$media = null;
			$final_url = $url;

			$view_params = $this->params;
			$view_params['title'] = $this->replace_view_placeholder( $this->params['title'], $url );
			$view_params['alt'] = $this->replace_view_placeholder( $this->params['alt'], $url );
			$view_params['class'] = implode( ' ', (array) $this->params['class'] );
			$view_params['style'] = implode( ' ', (array) $this->params['style'] );

			// change $final_url if user wants a registered image size
			if ( $output_registered_image_size ) {
				$media = $this->media_service->find_by_url( $url );
				$final_url = $this->get_registered_image_size( $media );
				if ( empty( $final_url ) && $output_custom_image_size ) {
					// registered size not available, get custom if possible
					$final_url = $this->get_custom_image_size( $media );
				}

				// change $final_url if user wants a custom image size
			} elseif ( $output_custom_image_size ) {
				$media = $this->media_service->find_by_url( $url );
				$final_url = $this->get_custom_image_size( $media );
			} elseif ( $this->always_display_media_library_modifications() ) {
				// no registered size, no custom size selected,
				// but user wants to check underlying attachment id to make sure
				// image modifiactions (done by media library) are applied
				$media = $this->media_service->find_by_url( $url );
				if ( $media ) {
					$final_url = $media->get_url();
				}
			}

			$rendered = $this->decorator_image->get_value( $final_url, $view_params );
			$rendered_value[] = $this->filter_field_value_after_decorators( $rendered, $url );
		}

		return $rendered_value;
	}


	private function replace_view_placeholder( $input, $media ) {
		if ( $this->view_placeholder instanceof Types_View_Placeholder_Media ) {
			return $this->view_placeholder->replace( $input, $media, $this->media_service );
		}

		return $this->view_placeholder->replace( $input, $media );
	}


	/**
	 * Check if user wants to output a registered image size
	 *
	 * @return bool
	 */
	private function output_registered_image_size() {
		if ( empty( $this->params['size'] ) || $this->params['size'] === 'custom' ) {
			// no size given or explicit set to custom or resize method is crop
			return false;
		}

		// check for registered size
		$registered_image_sizes = get_intermediate_image_sizes();
		if ( in_array( $this->params['size'], $registered_image_sizes, false ) ) {
			// registered size requested
			return true;
		}

		// the requested size is not an registered size
		return false;
	}


	/**
	 * Internal means the image is registered in the database
	 *
	 * @param Types_Interface_Media $media
	 *
	 * @return false|string
	 */
	private function get_registered_image_size( Types_Interface_Media $media ) {
		if ( $this->params['url'] ) {
			// user wants blank url
			if ( $this->params['size'] !== 'full' ) {
				// specific size wanted
				if ( $image = wp_get_attachment_image_src( $media->get_id(), $this->params['size'] ) ) {
					return $image[0];
				}

				// specific size not available
				return false;
			}

			return $media->get_url();
		}

		// image
		return wp_get_attachment_image(
			$media->get_id(),
			$this->params['size'],
			false,
			array(
				'title' => $this->view_placeholder->replace( $this->params['title'], $media ),
				'alt' => $this->view_placeholder->replace( $this->params['alt'], $media ),
				'class' => implode( ' ', (array) $this->params['class'] ),
				'style' => implode( ' ', (array) $this->params['style'] ),
			)
		);
	}


	/**
	 * Check if User wants to output custom image size
	 *
	 * @return bool
	 */
	private function output_custom_image_size() {
		if ( ! empty( $this->params['width'] ) || ! empty( $this->params['height'] ) ) {
			return true;
		}

		return false;
	}


	/**
	 * @param Types_Interface_Media $media
	 *
	 * @return false|string
	 */
	private function get_custom_image_size( Types_Interface_Media $media ) {
		if ( ! $this->output_custom_image_size() ) {
			return false;
		}

		$args = array(
			'resize' => $this->params['resize'],
			'padding_color' => $this->params['padding_color'],
			'width' => $this->params['width'],
			'height' => $this->params['height'],
		);

		return $this->media_service->resize_image( $media, $args, $this->site_domain->contains( $media ) );
	}


	/**
	 * Fill user params with default params
	 *
	 * @param array $params
	 */
	private function prepare_params( $params ) {
		$this->params = array_merge( array(
			'alt' => false,
			'title' => false,
			'style' => '',
			'size' => false,
			'url' => false,
			'onload' => false,
			'padding_color' => '#fff',
		), $params );

		// class
		$this->params['class'] = array();

		if ( isset( $params['size'] ) && ! empty( $params['size'] ) ) {
			$this->params['class'][] = 'attachment-' . $params['size'];
		}

		if ( isset( $params['align'] ) && ! empty( $params['align'] ) && $params['align'] !== 'none' ) {
			$this->params['class'][] = 'align' . $params['align'];
		}

		if ( isset( $params['class'] ) && ! empty( $params['class'] ) ) {
			$this->params['class'][] = $params['class'];
		}

		// 'proportional' is just for backwards compatibility (was replaced by 'resize')
		$proportional = isset( $params['proportional'] ) && $params['proportional']
			? 'proportional'
			: 'crop';

		$this->params['resize'] = isset( $params['resize'] ) ? $params['resize'] : $proportional;

		// width and height
		$this->params['width'] = isset( $params['width'] ) ? $params['width'] : false;
		$this->params['height'] = isset( $params['height'] ) ? $params['height'] : false;

		if ( ! $this->params['width'] && ! $this->params['height'] && $this->params['size'] ) {
			switch ( $this->params['size'] ) {
				case 'thumbnail':
					$width = get_option( 'thumbnail_size_w' );
					$height = get_option( 'thumbnail_size_h' );
					break;

				case 'medium':
					$width = get_option( 'medium_size_w' );
					$height = get_option( 'medium_size_h' );
					break;

				case 'large':
					$width = get_option( 'large_size_w' );
					$height = get_option( 'large_size_h' );
					break;

				default:
					if ( $size = $this->wordpress_media->get_addtional_image_sizes( $params['size'] ) ) {
						$width = $size['width'];
						$height = $size['height'];
					}

			}
		}
		if ( isset( $width ) && $width ) {
			$this->params['width'] = $width;
		}

		if ( isset( $height ) && $height ) {
			$this->params['height'] = $height;
		}
	}


	/**
	 * Get Toolset Setting > Custom Content > Images > Always apply image modifications done using Media Library
	 *
	 * @return bool
	 */
	private function always_display_media_library_modifications() {
		if ( $this->_always_display_media_library_modifications === null ) {
			$this->_always_display_media_library_modifications
				= (bool) wpcf_get_settings( 'images_always_apply_media_library_modifications' );
		}

		return $this->_always_display_media_library_modifications;
	}
}
