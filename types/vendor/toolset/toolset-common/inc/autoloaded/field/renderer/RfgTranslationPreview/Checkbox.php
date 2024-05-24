<?php

namespace OTGS\Toolset\Common\Field\Renderer\RfgTranslationPreview;

/**
 * Translation preview renderer for checkbox fields.
 *
 * Taken from the initial implementation in Types.
 *
 * @since 4.0
 */
class Checkbox extends DefaultRenderer {

	protected function get_value() {
		return empty( $this->get_normalized_value() )
			? '<i class="fa fa-square-o"></i>'
			: '<i class="fa fa-check-square-o"></i>';
	}

}
