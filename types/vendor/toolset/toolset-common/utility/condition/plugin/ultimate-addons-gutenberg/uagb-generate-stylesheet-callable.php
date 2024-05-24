<?php

namespace OTGS\Toolset\Common\Condition\Plugin\UltimateAddonsGutenberg;

/**
 * Condition for deciding if the "generate_stylesheet" method of the "UAGB_Helper" class is callable.
 *
 * @since 3.5.8
 *
 * @codeCoverageIgnore
 *
 * @deprecated The "generate_stylesheet" method was removed from the "UAGB_Helper" class since v1.5.1. This condition
 *             will always return false. Use "\OTGS\Toolset\Common\Condition\Plugin\UltimateAddonsGutenberg\UAGBGenerateAssetsCallable:is_met" instead.
 */
class UAGBGenerateStylesheetCallable implements \Toolset_Condition_Interface {
	/**
	 * Checks if the "generate_stylesheet" method of the "UAGB_Helper" class is callable.
	 *
	 * @return bool
	 */
	public function is_met() {
		return is_callable( array( '\UAGB_Helper', 'generate_stylesheet' ) );
	}
}
