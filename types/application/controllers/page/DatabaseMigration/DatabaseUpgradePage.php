<?php

namespace OTGS\Toolset\Types\Controller\Page\DatabaseMigration;

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\BatchSizeHelper;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Ajax\Handler\RelationshipsDatabaseUpgrade;
use OTGS\Toolset\Types\Controller\Page\PageControllerInterface;
use RuntimeException;
use Toolset_Assets_Manager;
use Toolset_Menu;
use Types_Admin_Menu;
use Types_Ajax;

/**
 * Controller of the Database Upgrade page, which at the moment handles only the migration
 * between first and second version of the relationships database layer version.
 *
 * @since 3.4
 */
class DatabaseUpgradePage implements PageControllerInterface {


	// Same value but different meanings, let's not mix them.
	const PAGE_SLUG = Types_Admin_Menu::PAGE_NAME_DATABASE_UPGRADE;

	const MAIN_SCRIPT_HANDLE = self::PAGE_SLUG;

	const ROOT_ANCHOR_ID = self::PAGE_SLUG;


	/** @var WpmlService */
	private $wpml_service;

	/** @var Types_Ajax */
	private $ajax_manager;

	private $relationships_factory;


	/**
	 * DatabaseUpgradePage constructor.
	 *
	 * @param WpmlService $wpml_service
	 * @param Types_Ajax $ajax_manager
	 * @param Factory $relationships_factory
	 */
	public function __construct( WpmlService $wpml_service, Types_Ajax $ajax_manager, Factory $relationships_factory ) {
		$this->wpml_service = $wpml_service;
		$this->ajax_manager = $ajax_manager;
		$this->relationships_factory = $relationships_factory;
	}


	/**
	 * @inheritDoc
	 */
	public function get_title() {
		return __( 'Database upgrade', 'wpcf' );
	}


	/**
	 * @inheritDoc
	 */
	public function get_render_callback() {
		return [ $this, 'render' ];
	}


	/**
	 * @inheritDoc
	 */
	public function get_page_name() {
		return self::PAGE_SLUG;
	}


	/**
	 * @inheritDoc
	 */
	public function get_required_capability() {
		return 'manage_options';
	}


	/**
	 * @inheritDoc
	 */
	public function prepare() {
		if ( ! $this->relationships_factory->low_level_gateway()->get_available_migration_controller() ) {
			throw new RuntimeException( __( 'There is no database upgrade available at the time.', 'wpcf' ) );
		}

		wp_enqueue_script(
			self::MAIN_SCRIPT_HANDLE,
			TYPES_RELPATH . '/public/js/page/bundle.database_upgrade.js',
			[
				'wp-components',
				'wp-element',
				'wp-i18n',
				'react',
				'react-dom',
				Toolset_Assets_Manager::SCRIPT_UTILS,
			],
			TYPES_VERSION
		);

		$ajax_action_name = $this->ajax_manager->get_action_js_name( RelationshipsDatabaseUpgrade::AJAX_ACTION );
		wp_localize_script(
			self::MAIN_SCRIPT_HANDLE,
			'toolsetTypesDatabaseUpgradePageL10n',
			[
				'anchorId' => self::ROOT_ANCHOR_ID,
				'isWpmlActiveAndConfigured' => $this->wpml_service->is_wpml_active_and_configured(),
				'ajaxActionName' => $ajax_action_name,
				'nonce' => wp_create_nonce( $ajax_action_name ),
				'batchSize' => BatchSizeHelper::get_batch_size(),
				'supportForumURL' => 'https://toolset.com/forums/forum/professional-support/',
				'toolsetDashboardURL' => esc_url_raw( add_query_arg(
					[ 'page' => Types_Admin_Menu::PAGE_NAME_DASHBOARD ],
					admin_url( 'admin.php' )
				) ),
				'toolsetTroubleshootingURL' => esc_url_raw( add_query_arg(
					[ 'page' => Toolset_Menu::TROUBLESHOOTING_PAGE_SLUG ],
					admin_url( 'admin.php' )
				) ),
				'returnToUrl' => esc_url_raw( get_site_url() . urldecode( toolset_getget( 'return_to', 'wp-admin' ) ) ),
				'returnToTitle' => sanitize_text_field( toolset_getget( 'return_to_title' ) ),
			]
		);

		wp_enqueue_style(
			self::MAIN_SCRIPT_HANDLE,
			TYPES_RELPATH . '/public/css/page/bundle.database_upgrade.css',
			[
				'toolset-types',
				'font-awesome',
			],
			TYPES_VERSION
		);
	}


	/**
	 * @inheritDoc
	 */
	public function get_load_callback() {
		// Nothing to do here.
		return null;
	}


	public function render() {
		printf( '<div id="%s"></div>', self::ROOT_ANCHOR_ID );
	}
}
