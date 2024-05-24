<?php

namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

/**
 * Translation preview renderer for select fields.
 *
 * Taken from the initial implementation in Types.
 *
 * @since 4.0
 */
class Select extends DefaultRenderer {

	protected function get_value() {
		$value = $this->get_normalized_value();
		$field_def_array = $this->field->get_definition()->get_definition_array();
		foreach ( $field_def_array['data']['options'] as $option_slug => $option_data ) {
			if ( $option_slug === 'default' ) {
				continue;
			}

			/** @noinspection TypeUnsafeComparisonInspection */
			if ( $value == $option_data['value'] ) {
				return $option_data['title'];
			}
		}

		return '';
	}

}
