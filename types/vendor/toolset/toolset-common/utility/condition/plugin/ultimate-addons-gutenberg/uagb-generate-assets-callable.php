<?php

namespace OTGS\Toolset\Common\Condition\Plugin\UltimateAddonsGutenberg;

/**
 * Condition for deciding if the "generate_stylesheet" method of the "UAGB_Helper" classis callable.
 *
 * @since 3.5.8
 *
 * @codeCoverageIgnore
 */
class UAGBGenerateAssetsCallable implements \Toolset_Condition_Interface {
	/**
	 * Checks if the "generate_stylesheet" method of the "UAGB_Helper" class is callable.
	 *
	 * @return bool
	 */
	public function is_met() {
		return is_callable( array( '\UAGB_Helper', 'generate_assets' ) );
	}
}
