<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

/**
 * REST API renderer that produces only the raw value of a field.
 *
 * @since Types 3.3
 */
class Raw extends AbstractRenderer {


	/**
	 * @inheritdoc
	 * @return array
	 */
	protected function get_value() {
		$value = $this->field->get_value();

		// The value is always an array. For single-value fields, get rid of the encompassing array and work with
		// the field value directly.
		if (
			! $this->field->get_definition()->is_repeatable()
			&& is_array( $value )
		) {
			$value = ( count( $value ) === 1 ? array_pop( $value ) : '' );
		}

		return array(
			'type' => $this->field->get_field_type()->get_slug(),
			'raw' => $value,
		);
	}


	/**
	 * For a given output (result of the get_value() method), process individual field values with a custom formatter.
	 *
	 * If the formatter produces a value, it will be added with a given key.
	 *
	 * For single-value fields, the key will be added directly to $output. For repeatable fields, a key "repeatable"
	 * will be added, with an array of single field values. Each of these values will contain the "raw" value again
	 * and then the formatted value with selected key.
	 *
	 * Example:
	 * $input = [ 'raw' => [ 'a', 'b', 'c' ] ];
	 * $output = $this->format_single_or_repeatable(
	 *     $input, 'formatted_value', function( $single_val ) { return $single_value . '_formatted'; }
	 * );
	 *
	 * This will produce: $output = [
	 *     'raw' => [ 'a', 'b', 'c' ],
	 *     'repeatable' => [
	 *         [ 'raw' => 'a', 'formatted_value' => 'a_formatted' ],
	 *         [ 'raw' => 'b', 'formatted_value' => 'b_formatted' ],
	 *         [ 'raw' => 'c', 'formatted_value' => 'c_formatted' ],
	 *     ]
	 * ];
	 *
	 * While for a single $input = [ 'raw' => 'a' ], the result is going to be: $output = [
	 *     'raw' => 'a',
	 *     'formatted_value' => 'a_formatted',
	 * ]
	 *
	 * @param array $input Field values. Must contain the 'raw' key with raw field value.
	 * @param string $single_value_key Key that should be used for formatted values.
	 * @param callable $value_formatter Function to format a single field value.
	 *
	 * @return array
	 */
	protected function format_single_or_repeatable( $input, $single_value_key, $value_formatter ) {
		$raw_value = $input['raw'];
		if ( $this->field->get_definition()->is_repeatable() ) {
			$formatted_values = array();
			foreach ( toolset_ensarr( $raw_value ) as $single_raw_value ) {

				$single_formatted_value = array( 'raw' => $single_raw_value );

				$formatter_output = $value_formatter( $single_raw_value );
				if ( null !== $formatter_output ) {
					$single_formatted_value[ $single_value_key ] = $formatter_output;
				}

				$formatted_values[] = $single_formatted_value;
			}
			$input['repeatable'] = $formatted_values;
		} else {
			$input[ $single_value_key ] = $value_formatter( $raw_value );
		}

		return $input;
	}
}
