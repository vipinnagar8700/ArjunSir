<?php

/**
 * Toolset_Condition_Plugin_Toolset_Blocks_Missing
 *
 * @since 3.3.8
 */
class Toolset_Condition_Plugin_Toolset_Blocks_Missing extends Toolset_Condition_Plugin_Toolset_Blocks_Active {

	public function is_met() {
		return ! parent::is_met();
	}

}
