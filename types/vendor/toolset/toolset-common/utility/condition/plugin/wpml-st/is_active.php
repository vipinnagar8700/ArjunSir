<?php

/**
 * An "objectified" version of the check whether WPML String Translation is active.
 *
 * @since 3.2.4
 *
 */
class Toolset_Condition_Plugin_Wpml_String_Translation_Is_Active implements Toolset_Condition_Interface {

	/**
	 * @return bool
	 */
	public function is_met() {
		return Toolset_WPML_Compatibility::get_instance()->is_wpml_st_active();
	}
}