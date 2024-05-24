<?php

namespace OTGS\Toolset\Common\Field\Renderer\Rest;

/**
 * Abstract field renderer for the REST API.
 *
 * Only for convenience.
 *
 * @since Types 3.3
 */
abstract class AbstractRenderer extends \Toolset_Field_Renderer_Abstract {

	/**
	 * @param bool $echo
	 *
	 * @return array
	 */
	public function render( $echo = false ) {
		$value = $this->get_value();

		if ( $echo ) {
			// ... because the value should be already safe when it comes out of get_value(), or additional escaping needs
			// to be performed on the renderer output.
			//
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $value;
		}

		return $value;
	}

}
