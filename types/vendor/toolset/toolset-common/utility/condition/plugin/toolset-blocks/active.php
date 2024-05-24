<?php

/**
 * Toolset_Condition_Plugin_Toolset_Blocks_Active
 *
 * @since 3.3.8
 * @deprecated Toolset Blocks is part of Toolset Views now,
 *     and now the right way to check the plugin flavour is by using the APi filter
 *     toolset_views_flavour_installed.
 */
class Toolset_Condition_Plugin_Toolset_Blocks_Active implements Toolset_Condition_Interface {

	public function is_met() {
		return ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) );
	}

}
