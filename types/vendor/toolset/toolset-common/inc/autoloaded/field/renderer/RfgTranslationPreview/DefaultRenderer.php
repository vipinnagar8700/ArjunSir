<?php

namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

use Toolset_Field_Renderer_Abstract;

/**
 * Default translation preview renderer for text-based field types.
 *
 * @since 4.0
 */
class DefaultRenderer extends Toolset_Field_Renderer_Abstract {


	/**
	 * @return string
	 */
	protected function get_value() {
		return nl2br( stripslashes( $this->get_normalized_value() ) );
	}


	/**
	 * Return a safe, trimmed, single field value as a string.
	 *
	 * @return string
	 */
	protected function get_normalized_value() {
		// The field value is always an array but RFGs, where this renderer is used, don't support
		// repeatable fields.
		$value_array = $this->field->get_value();

		$value = reset( $value_array );

		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		return trim( wp_kses_post( $value ) );
	}

}
