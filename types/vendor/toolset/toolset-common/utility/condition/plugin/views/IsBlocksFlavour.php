<?php

namespace OTGS\Toolset\Common\Condition\Views;

/**
 * Test if Toolset Views is active in the Block flavour.
 *
 * @since Types 3.3.8
 */
class IsBlocksFlavour extends \Toolset_Condition_Plugin_Views_Active {

	/**
	 * @return bool
	 */
	public function is_met() {
		return (
			parent::is_met()
			&& 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' )
		);
	}
}
