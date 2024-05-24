<?php

namespace OTGS\Toolset\Common\Condition\Views;

/**
 * Test if Toolset Views is active in the classic flavour (no Toolset Blocks) or not active at all.
 *
 * @since Types 3.3.8
 */
class IsClassicFlavourOrInactive implements \Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		// This will pass even if Views is not active.
		$result = ( 'classic' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) );

		return $result;
	}
}
