<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
class WPToolset_Field_Video extends WPToolset_Field_File
{
    protected $_settings = array('min_wp_version' => '3.6');

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
            '3gp',
            'aaf',
            'asf',
            'avchd',
            'avi',
            'cam',
            'dat',
            'dsh',
            'fla',
            'flr',
            'flv',
            'm1v',
            'm2v',
            'm4v',
            'mng',
            'mp4',
            'mxf',
            'nsv',
            'ogg',
            'rm',
            'roq',
            'smi',
            'sol',
            'svi',
            'swf',
            'wmv',
            'wrap',
            'mkv',
            'mov',
            'mpe',
            'mpeg',
            'mpg',
            'quicktime'
        );
        $valid_extensions = apply_filters( 'toolset_valid_video_extentions', $valid_extensions);
        $validation['extension'] = array(
            'args' => array(
                'extension',
                implode('|', $valid_extensions),
            ),
            'message' => __( 'You can add only video.', 'wpv-views' ),
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
			return __( 'Select video(s)', 'wpv-views' );
		} else {
			return __( 'Select video', 'wpv-views' );
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
		return __( 'Replace video', 'wpv-views' );
	}
}
