<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
class WPToolset_Field_Image extends WPToolset_Field_File
{
    public function metaform()
    {
        $validation = $this->getValidationData();
        $validation = self::addTypeValidation($validation);
        $this->setValidationData($validation);
        return parent::metaform();
    }

    public static function addTypeValidation($validation)
    {
        $valid_extensions = array(
            'bmp',
            'gif',
            'ico',
            'jpeg',
            'jpg',
            'png',
            'svg',
            'webp',
        );
        $valid_extensions = apply_filters( 'toolset_valid_image_extentions', $valid_extensions);
        $validation['extension'] = array(
            'args' => array(
                'extension',
                implode('|', $valid_extensions),
            ),
            'message' => __( 'You can add only images.', 'wpv-views' ),
        );
        return $validation;
	}

	/**
	 * Get the default label for the Media Manager button when selecting a value.
	 *
	 * @return string
	 *
	 * @since 3.3
	 */
	protected function get_select_label() {
		if ( $this->isRepetitive() ) {
			return __( 'Select image(s)', 'wpv-views' );
		} else {
			return __( 'Select image', 'wpv-views' );
		}
	}


	/**
	 * Get the default label for the Media Manager button when editing a value.
	 *
	 * @return string
	 *
	 * @since 3.3
	 */
	protected function get_edit_label() {
		return __( 'Replace image', 'wpv-views' );
	}
}
