<?php

namespace OTGS\Toolset\Common\Field\Renderer;

use Toolset_Field_Renderer_Abstract;

/**
 * Field renderer that produces the raw output that Toolset usually works with.
 *
 * Echoing the value is not supported.
 *
 * @since Types 3.3.5
 */
class Raw extends Toolset_Field_Renderer_Abstract {

	/**
	 * @inheritDoc
	 *
	 * @param false $echo Echoing the raw value with this renderer is not supported, this needs to be always false.
	 *
	 * @return mixed Raw value of the field, always wrapped in an array (even for non-repeatable fields).
	 * @throws \RuntimeException If $echo is true.
	 */
	public function render( $echo = false ) {
		if ( $echo ) {
			throw new \RuntimeException( 'Printing the raw field value is not supported.' );
		}

		return $this->field->get_value();
	}
}
