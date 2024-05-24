<?php

/**
 * Toolset_Condition_Plugin_The_Events_Calendar_Active
 *
 * @since 3.2.0
 */
class Toolset_Condition_Plugin_The_Events_Calendar_Tribe_Events_Query_Class_Exists implements Toolset_Condition_Interface {

	public function is_met() {
		return class_exists( 'Tribe__Events__Query' );
	}
}
