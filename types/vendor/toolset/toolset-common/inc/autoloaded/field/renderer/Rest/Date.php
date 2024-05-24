<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

/**
 * Renderer for the date field in REST API.
 *
 * Besides the timestamp in the raw format, provide also a 'formatted' key, where the date
 * is formatted according to the site date and time format.
 *
 * @since Types 3.3
 */
class Date extends Raw {


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	protected function get_value() {
		$output = parent::get_value();

		// PHP 5.3 compatibility...
		$field = $this->field;

		$output = $this->format_single_or_repeatable(
			$output,
			'formatted',
			function ( $single_raw_value ) use ( $field ) {
				$timestamp = is_numeric( $single_raw_value ) ? (int) $single_raw_value : 0;
				if ( 0 === $timestamp ) {
					return null;
				}
				$formatted = date( get_option( 'date_format' ), $timestamp );

				$add_time = ( $field->get_definition()->get_datetime_option() === 'date_and_time' );
				if ( $add_time ) {
					$formatted .= ' ' . date( get_option( 'time_format' ), $timestamp );
				}

				return $formatted;
			}
		);

		return $output;
	}


}
