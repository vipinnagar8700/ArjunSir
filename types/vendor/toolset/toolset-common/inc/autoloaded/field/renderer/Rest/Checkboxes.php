<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

/**
 * Renderer for the checkboxes field in REST API.
 *
 * Besides raw value, it produces two further elements:
 * - 'checked': An array of "values to save to database" of checked checkboxes.
 * - 'formatted': A comma-separated list of field values as per field definition.
 *
 * @since Types 3.3
 */
class Checkboxes extends Raw {


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	protected function get_value() {
		$output = parent::get_value();

		$output['checked'] = array_reduce( toolset_ensarr( $output['raw'] ), function ( $carry, $item ) {
			if ( ! is_array( $item ) || empty( $item ) ) {
				// Not a checked item.
				return $carry;
			}
			$option_value = array_pop( $item );
			if (
				( is_numeric( $option_value ) && 0 === (int) $option_value )
				|| empty( $option_value )
			) {
				// Not a checked item.
				return $carry;
			}

			$carry[] = $option_value;

			return $carry;
		}, array() );

		// We know that Types is active at this point because of how the REST API extension is initialized in Toolset.
		$output['formatted'] = types_render_field(
			$this->field->get_definition()->get_slug(),
			array( 'item' => $this->field->get_object_id() )
		);

		return $output;
	}


}
