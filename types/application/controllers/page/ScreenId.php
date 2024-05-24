<?php


namespace OTGS\Toolset\Types;

use Toolset_Menu;
use Toolset_Settings_Screen;
use Types_Admin_Menu;
use Types_Page_Dashboard;

/**
 * Pseudo-enum class for holding screen IDs of various (mostly Toolset) pages as recognized by \WP_Screen.
 *
 * @since 3.4
 */
abstract class ScreenId {

	const TOPLEVEL_PAGE_PREFIX = 'toplevel_page_';
	const TOOLSET_PAGE_PREFIX = 'toolset_page_';

	const WP_DASHBOARD = 'dashboard';
	const TOOLSET_DASHBOARD = self::TOPLEVEL_PAGE_PREFIX . Types_Page_Dashboard::PAGE_SLUG;
	const RELATIONSHIPS = self::TOOLSET_PAGE_PREFIX . Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS;
	const TOOLSET_SETTINGS = self::TOOLSET_PAGE_PREFIX . Toolset_Settings_Screen::PAGE_SLUG;
	const TOOLSET_DEBUG_AND_TROUBLESHOOTING = self::TOOLSET_PAGE_PREFIX . Toolset_Menu::TROUBLESHOOTING_PAGE_SLUG;
}
