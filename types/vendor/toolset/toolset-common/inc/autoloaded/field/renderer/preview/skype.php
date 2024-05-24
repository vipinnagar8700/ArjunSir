<?php

/**
 * Preview renderer for Skype fields.
 * 
 * @since 1.9.1
 */
final class Toolset_Field_Renderer_Preview_Skype extends Toolset_Field_Renderer_Preview_Base {


	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {
		if ( is_array( $value ) ) {
			// Legacy format with an array.
			$skype_name = toolset_getarr( $value, 'skypename' );
			$skype_name = is_string( $skype_name ) ? $skype_name : '';
		} elseif ( is_string( $value ) ) {
			// New format.
			$skype_name = $value;
		} else {
			// Fallback.
			$skype_name = '';
		}

		return sanitize_text_field( $skype_name );
	}


}
