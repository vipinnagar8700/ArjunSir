<?php

/**
 * Toolset_Condition_Post_Type_Editor_Is_Classic
 *
 * No more than the opposite result of Toolset_Condition_Post_Type_Editor_Is_Block
 *
 * @since 3.3.0
 */
class Toolset_Condition_Post_Type_Editor_Is_Classic extends Toolset_Condition_Post_Type_Editor_Is_Block {
	/**
	 * Check if post type uses classic editor
	 *
	 * @return bool
	 */
	public function is_met() {
		return ! parent::is_met();
	}
}