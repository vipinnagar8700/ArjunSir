<?php

/**
 * Class Tribe__Tickets_Plus__Updater
 *
 * @since 4.7.1
 *
 */
class Tribe__Tickets_Plus__Updater extends Tribe__Updater {

	protected $version_option = 'event-tickets-plus-schema-version';

	/**
	 * Force upgrade script to run even without an existing version number
	 * The version was not previously stored for Filter Bar
	 *
	 * @since 4.7.1
	 *
	 * @return bool
	 */
	public function is_new_install() {
		return false;
	}
}