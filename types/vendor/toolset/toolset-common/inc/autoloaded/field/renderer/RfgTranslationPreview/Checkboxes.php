<?php


namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

/**
 * Translation preview renderer for checkboxes fields.
 *
 * Taken from the initial implementation in Types.
 *
 * @since 4.0
 */
class Checkboxes extends DefaultRenderer {

	protected function get_value() {
		$field_def_array = $this->field->get_definition()->get_definition_array();
		$value = toolset_ensarr( $this->field->get_value() );
		if( ! empty( $value ) ) {
			$value = reset( $value );
		}
		$result = '';
		foreach ( $field_def_array['data']['options'] as $option_slug => $option_data ) {
			$result .= isset( $value[ $option_slug ] ) && ! empty( $value[ $option_slug ] )
				? '<i class="fa fa-check-square-o"></i><br />'
				: '<i class="fa fa-square-o"></i><br />';
		}

		return $result;
	}


}
