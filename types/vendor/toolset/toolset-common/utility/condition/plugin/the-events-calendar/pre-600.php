<?php

namespace OTGS\Toolset\Common\Condition\Plugin\TheEventsCalendar;

/**
 * Condition for deciding if TheEventsCalendar plugin is in a version prior to 6.0.0
 */
class IsTheEventsCalendarPre600 implements \Toolset_Condition_Interface {

	/**
	 * Checks if the condition of Ultimate Addons Gutenberg plugin is in a version prior to 1.23.0 is met.
	 *
	 * @return bool
	 */
	public function is_met() {
		return (
			did_action( 'tribe_plugins_loaded' ) &&
			class_exists( '\Tribe__Events__Main' ) &&
			intval( \Tribe__Events__Main::VERSION ) < 6
		);
	}
}
