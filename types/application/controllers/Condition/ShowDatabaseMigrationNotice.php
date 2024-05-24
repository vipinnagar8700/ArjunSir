<?php


namespace OTGS\Toolset\Types\Condition;

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Types\Model\Wordpress\Screen;
use OTGS\Toolset\Types\ScreenId;
use Toolset_Condition_Interface;

/**
 * Condition class to determine whether a notice about database structure upgrade and data migration should be
 * displayed in the current context.
 *
 * @since 3.4
 */
class ShowDatabaseMigrationNotice implements Toolset_Condition_Interface {


	/** @var Factory */
	private $relationships_factory;


	/** @var Screen */
	private $screen;


	/**
	 * ShowDatabaseMigrationNotice constructor.
	 *
	 * @param Factory $relationships_factory
	 * @param Screen $screen
	 */
	public function __construct(
		Factory $relationships_factory,
		Screen $screen
	) {
		$this->relationships_factory = $relationships_factory;
		$this->screen = $screen;
	}


	/**
	 * @return bool
	 */
	public function is_met() {
		if ( $this->it_is_hidden() && ! $this->is_on_toolset_dashboard() ) {
			return false;
		}

		if ( ! $this->is_on_relevant_page() ) {
			return false;
		}

		$migration_controller = $this->relationships_factory->low_level_gateway()->get_available_migration_controller();

		if ( null === $migration_controller ) {
			return false;
		}

		if ( ! $migration_controller->can_migrate() ) {
			return false;
		}

		return true;
	}


	private function is_on_relevant_page() {
		$current_screen = $this->screen->get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		return in_array( $current_screen->base, [
			ScreenId::WP_DASHBOARD,
			ScreenId::TOOLSET_DASHBOARD,
			ScreenId::RELATIONSHIPS,
			ScreenId::TOOLSET_SETTINGS,
			ScreenId::TOOLSET_DEBUG_AND_TROUBLESHOOTING,
		], true );
	}


	/**
	 * Check if the current page is Toolset Dashboard.
	 *
	 * @return bool
	 */
	private function is_on_toolset_dashboard() {
		$current_screen = $this->screen->get_current_screen();

		if ( ! $current_screen ) {
			return false;
		}

		return in_array( $current_screen->base, [
			ScreenId::TOOLSET_DASHBOARD,
		], true );
	}


	/**
	 * Checks if the notice config makes it hidden
	 *
	 * @return bool
	 */
	private function it_is_hidden() {
		$toolset_settings = \Toolset_Settings::get_instance();
		$show_notice_setting = $toolset_settings->get( \Toolset_Settings::DATABASE_MIGRATION_NOTICE_SHOW );
		return 'no' === $show_notice_setting;
	}
}
