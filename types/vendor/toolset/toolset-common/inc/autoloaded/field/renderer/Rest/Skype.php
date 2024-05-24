<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

/**
 * Renderer for the Skype field in REST API.
 *
 * Besides the raw value, which may be either the skype name or a legacy data structure, also provide
 * a 'skypename' element which will always contain the skype name.
 *
 * @since Types 3.3
 */
class Skype extends Raw {


	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	protected function get_value() {
		$output = parent::get_value();

		$output = $this->format_single_or_repeatable(
			$output,
			'skypename',
			function ( $single_raw_value ) {
				if ( is_array( $single_raw_value ) ) {
					// Legacy format of the field value.
					return toolset_getarr( $single_raw_value, 'skypename' );
				}

				return $single_raw_value;
			} );

		return $output;
	}

}
