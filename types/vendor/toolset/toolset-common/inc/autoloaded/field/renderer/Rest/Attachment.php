<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

use OTGS\Toolset\Common\Utils\Attachments;

/**
 * Renderer for fields that contain an URL which may or may not correspond with an attachment.
 *
 * If there is an attachment, add an 'attachment_id' key for each value.
 *
 * @since Types 3.3
 */
class Attachment extends Raw {


	/** @var Attachments */
	private $attachment_utils;


	/**
	 * Attachment constructor.
	 *
	 * @param \Toolset_Field_Instance $field
	 * @param Attachments $attachment_utils
	 */
	public function __construct( \Toolset_Field_Instance $field, Attachments $attachment_utils ) {
		parent::__construct( $field );

		$this->attachment_utils = $attachment_utils;
	}


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	protected function get_value() {
		$output = parent::get_value();

		// PHP 5.3 compatibility, yuck.
		$attachment_utils = $this->attachment_utils;

		$output = $this->format_single_or_repeatable(
			$output,
			'attachment_id',
			function ( $single_raw_value ) use ( $attachment_utils ) {
				$attachment_id = (int) $attachment_utils->get_attachment_id_by_url( $single_raw_value );
				if ( 0 === $attachment_id ) {
					return null;
				}

				return $attachment_id;
			}
		);

		return $output;
	}


}
