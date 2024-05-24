<?php

namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

/**
 * Translation preview renderer for radio fields.
 *
 * Taken from the initial implementation in Types.
 *
 * @since 4.0
 */
class Radio extends DefaultRenderer {

	protected function get_value() {
		$value = $this->get_normalized_value();
		$field_def_array = $this->field->get_definition()->get_definition_array();
		$result = '';
		foreach( $field_def_array['data']['options'] as $option_slug => $option_data ){
			if( $option_slug === 'default' ) {
				continue;
			}

			/** @noinspection TypeUnsafeComparisonInspection */
			$result .= $value == $option_data['value']
				? '<i class="fa fa-dot-circle-o"></i><br />'
				: '<i class="fa fa-circle-o"></i><br />';
		}

		return $result;
	}

}
