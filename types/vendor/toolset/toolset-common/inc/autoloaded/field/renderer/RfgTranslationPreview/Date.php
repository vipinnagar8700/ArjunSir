<?php

namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

/**
 * Translation preview renderer for date fields.
 *
 * Taken from the initial implementation in Types.
 *
 * @since 4.0
 */
class Date extends DefaultRenderer {

	protected function get_value() {
		$value = $this->get_normalized_value();

		if ( empty( $value ) ) {
			return '';
		}

		return date( get_option( 'date_format' ), $value );
	}

}
