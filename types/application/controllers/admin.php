<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

use OTGS\Toolset\Common\Upgrade\UpgradeController;
use OTGS\Toolset\Types\AdminNotice\DatabaseMigrationNoticeController;
use OTGS\Toolset\Types\Page\Extension\AddOrEditPost;

/**
 * Main backend controller for Types.
 *
 * @since 2.0
 */
final class Types_Admin {


	/**
	 * Initialize Types for backend.
	 *
	 * This is expected to be called during init.
	 *
	 * @since 2.0
	 */
	public function initialize() {
		

		$dic = toolset_dic();
		/** @noinspection PhpUnhandledExceptionInspection */
		$upgrade_controller = $dic->make(
			UpgradeController::class,
			[
				'command_definition_repository' => \OTGS\Toolset\Types\Upgrade\CommandDefinitionRepository::class,
				'version' => \OTGS\Toolset\Types\Upgrade\TypesVersion::class,
			]
		);
		$upgrade_controller->initialize();
		$upgrade_controller->check_upgrade();

		// Load menu - won't be loaded in embedded version.
		if ( apply_filters( 'types_register_pages', true ) ) {
			Types_Admin_Menu::initialize();
		}

		$this->init_page_extensions();
		$this->register_types_style();
	}


	/**
	 * Add hooks for loading page extensions.
	 *
	 * @since 2.1
	 */
	private function init_page_extensions() {
		$load_add_or_edit_post_extension = static function () {
			$dic = toolset_dic();
			/** @var AddOrEditPost $add_or_edit_extension */
			$add_or_edit_extension = $dic->make( AddOrEditPost::class );
			$add_or_edit_extension->initialize();
		};

		// extensions for post edit page
		add_action( 'load-post.php', static function () use ( $load_add_or_edit_post_extension ) {
			$dic = toolset_dic();
			/** @var Types_Page_Extension_Edit_Post $edit_post_extension */
			$edit_post_extension = $dic->make( Types_Page_Extension_Edit_Post::class );
			$edit_post_extension->initialize();

			$load_add_or_edit_post_extension();
		} );

		add_action( 'load-post-new.php', $load_add_or_edit_post_extension );

		// extension for post type edit page
		add_action( 'load-toolset_page_wpcf-edit-type', array(
			'Types_Page_Extension_Edit_Post_Type',
			'get_instance',
		) );

		// extension for post fields edit page
		add_action( 'load-toolset_page_wpcf-edit', array( 'Types_Page_Extension_Edit_Post_Fields', 'get_instance' ) );

		// Initialize the extension for the Toolset Settings page.
		add_action( 'load-toolset_page_toolset-settings', static function () {
			$settings = new Types_Page_Extension_Settings();
			$settings->build();
		} );

		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Related posts in edit pages.
			add_action( 'add_meta_boxes', static function() {
				$page_extension = toolset_dic()->make( Types_Page_Extension_Meta_Box_Related_Content::class );
				$page_extension->prepare();
			} );
		}

		// extension for cpt edit page
		add_action( 'load-toolset_page_wpcf-edit-type', static function () {
			Toolset_Singleton_Factory::get( 'Types_Admin_Notices_Custom_Fields_For_New_Cpt' );
		} );

		$this->maybe_show_migration_notices();
	}


	/**
	 * Registers Types style
	 * The goal for the future is to only have this Types css file.
	 */
	private function register_types_style() {
		wp_register_style(
			'toolset-types',
			TYPES_RELPATH . '/public/css/types.css',
			[ 'wp-components' ],
			TYPES_VERSION
		);
	}


	private function maybe_show_migration_notices() {
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// We need to do this now so that the following code can be clean, assuming relationships are enabled.
			return;
		}

		$dic = toolset_dic();
		/** @noinspection PhpUnhandledExceptionInspection */
		$migration_notice_controller = $dic->make( DatabaseMigrationNoticeController::class );
		$migration_notice_controller->initialize();
	}
}
