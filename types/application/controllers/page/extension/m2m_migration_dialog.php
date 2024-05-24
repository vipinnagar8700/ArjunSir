<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Migration_Controller;

/**
 * Puts the m2m activation dialog on an arbitrary admin site.
 *
 * The script that invokes the dialog needs the Types_Page_Extension_M2m_Migration_Dialog::MAIN_ASSET_HANDLE
 * as a dependency.
 *
 * Usage:
 *     // When preparing the admin page:
 *     $migration_dialog = new Types_Page_Extension_M2m_Migration_Dialog( $redirect_after_finish );
 *     $migration_dialog->prepare();
 *
 *     // When dialog should be opened:
 *     Toolset.hooks.doAction('types-open-m2m-migration-dialog');
 *
 * That should be all.
 *
 * @since 2.3-b4
 */
class Types_Page_Extension_M2m_Migration_Dialog {


	/** Unique ID of the dialog for the JS part to pick up the template. */
	const DIALOG_ID = 'types-m2m-activation-dialog-confirmation';


	/** Handle of the main dialog script. */
	const MAIN_ASSET_HANDLE = 'types-m2m-activation-dialog-confirmation';


	/** @var Toolset_Common_Bootstrap */
	private $toolset_common_bootstrap;


	/** @var Toolset_Output_Template_Factory */
	private $template_factory;


	/** @var null|string URL to redirect after the activation is completed successfully. */
	private $redirect_to;


	/** @var Toolset_Relationship_Controller */
	private $relationship_controller;


	/** @var Toolset_Relationship_Migration_Controller|null */
	private $_migration_controller;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Types_Page_Extension_M2m_Migration_Dialog constructor.
	 *
	 * @param null|string $redirect_to URL to redirect after the activation is completed successfully.
	 * @param Toolset_Common_Bootstrap|null $toolset_common_bootstrap_di
	 * @param Toolset_Output_Template_Factory|null $template_factory_di
	 * @param Toolset_Relationship_Controller|null $relationship_controller_di
	 * @param Toolset_Relationship_Migration_Controller|null $migration_controller_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		$redirect_to = null,
		Toolset_Common_Bootstrap $toolset_common_bootstrap_di = null,
		Toolset_Output_Template_Factory $template_factory_di = null,
		Toolset_Relationship_Controller $relationship_controller_di = null,
		Toolset_Relationship_Migration_Controller $migration_controller_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->redirect_to = $redirect_to;
		$this->toolset_common_bootstrap = $toolset_common_bootstrap_di ?: Toolset_Common_Bootstrap::get_instance();
		$this->template_factory = $template_factory_di ?: new Toolset_Output_Template_Factory();
		$this->relationship_controller = $relationship_controller_di ?: Toolset_Relationship_Controller::get_instance();
		$this->_migration_controller = $migration_controller_di;
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
	}


	/**
	 * Run this in the prepare() method of the page controller.
	 */
	public function prepare() {
		$this->toolset_common_bootstrap->register_gui_base();
		add_action( 'admin_print_scripts', array( $this, 'prepare_dialogs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
	}


	/**
	 * Prepare the dialog box.
	 */
	public function prepare_dialogs() {
		Toolset_Gui_Base::get_instance()->init();

		$template = $this->template_factory->twig_template(
			TYPES_TEMPLATES . '/page/extension/m2m_migration_dialog',
			'main.twig',
			array(
				'settings' => TYPES_TEMPLATES . '/page/extension/m2m_migration_dialog'
			)
		);

		$dialog = new Toolset_Template_Dialog_Box(
			self::DIALOG_ID,
			$template,
			array(
				'dynamicStrings' => array(
					'translationModeTooltip' => $this->get_translation_mode_change_tooltip_string()
				)
			)
		);

		$dialog->initialize();
	}


	/**
	 * Enqueue all assets necessary for the dialog to work.
	 */
	public function on_admin_enqueue_scripts() {

		$asset_manager = Toolset_Assets_Manager::get_instance();

		$asset_manager->enqueue_styles(
			array(
				'wp-admin',
				'common',
				'font-awesome',
				'wpcf-css-embedded',
				'wp-jquery-ui-dialog',
				'wp-pointer',
				Toolset_Gui_Base::STYLE_GUI_BASE,
				Toolset_Assets_Manager::STYLE_NOTIFICATIONS,
			)
		);

		wp_enqueue_script(
			self::MAIN_ASSET_HANDLE,
			TYPES_RELPATH . '/public/page/extension/m2m-migration-dialog/main.js',
			array(
				'jquery',
				'underscore',
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Toolset_Assets_Manager::SCRIPT_UTILS,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_CREATE_DIALOG,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_KNOCKOUT_EXTENSIONS,
				Types_Asset_Manager::SCRIPT_POINTER,
			),
			TYPES_VERSION
		);

		wp_localize_script(
			self::MAIN_ASSET_HANDLE,
			'types_page_extension_m2m_migration_dialog',
			array(
				'confirmationDialog' => array(
					'title' => __( "Let's update the post relationships in your site", 'wpcf' ),
					'activateButton' => __( 'Start the update process', 'wpcf' ),
					'cancelButton' => __( 'Cancel', 'wpcf' ),
					'finishButton' => __( 'Finish', 'wpcf' ),
					'retryButton' => __( 'Try again', 'wpcf' ),
					'goToSupportButton' => __( 'Go to support forum', 'wpcf' ),
					'inProgressImageUrl' => WPCF_EMBEDDED_TOOLSET_RELPATH . '/toolset-common/res/images/icon-help-message.png',
					'resultMessage' => array(
						'warning' => __( 'The activation has finished with some warnings. This may be caused by small inconsistencies in the database and it most probably has no impact on your site. Please check technical details for more information.', 'wpcf' ),
						'error' => __( 'An error has occurred during the activation. Please contact the Toolset support forum with the copy of the technical details you will find below.', 'wpcf' ),
					),
					'previewRelationships' => array(
						'actionName' => Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_M2M_MIGRATION_PREVIEW_RELATIONSHIPS ),
						'nonce' => wp_create_nonce( Types_Ajax::CALLBACK_M2M_MIGRATION_PREVIEW_RELATIONSHIPS ),
					),
					'scanLegacyCodeUsage' => array(
						'actionName' => Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_M2M_SCAN_LEGACY_CUSTOM_CODE ),
						'nonce' => wp_create_nonce( Types_Ajax::CALLBACK_M2M_SCAN_LEGACY_CUSTOM_CODE ),
					),
					'supportForumURL' => 'https://toolset.com/support/support-forum-archive/?utm_source=plugin&utm_medium=gui&utm_campaign=types',
					'redirectAfterFinish' => $this->get_redirect_url(),
					'canSetCptTranslationStatus' => $this->can_set_post_translation_status(),
					//'translationSettingsURL' => $translation_settings_url,
				),
				'actionName' => Toolset_Ajax::get_instance()->get_action_js_name( Toolset_Ajax::CALLBACK_MIGRATE_TO_M2M ),
				'nonce' => wp_create_nonce( Toolset_Ajax::CALLBACK_MIGRATE_TO_M2M ),
				'hasTranslatablePostTypesInRelationships' => $this->has_translatable_post_types_in_relationships(),
			)
		);


		wp_enqueue_style(
			self::MAIN_ASSET_HANDLE,
			TYPES_RELPATH . '/public/page/extension/m2m-migration-dialog/style.css',
			array(),
			TYPES_VERSION
		);
	}


	/**
	 * Because wpml_set_translation_mode_for_post_type will be available only in WPML 4.0.0.
	 *
	 * See wpmlcore-5153.
	 *
	 * @return bool
	 */
	private function can_set_post_translation_status() {
		$wpml_version = $this->wpml_service->get_wpml_version();

		if ( null === $wpml_version ) {
			return false;
		}

		return (bool) version_compare( $this->wpml_service->get_wpml_version(), '4.0.0', '>=' );
	}


	/**
	 * @return string
	 */
	private function get_redirect_url() {
		if( null === $this->redirect_to ) {
			$this->redirect_to = add_query_arg(
				array(
					'page' => Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS
				),
				admin_url( 'admin.php' )
			);
		}

		return esc_url_raw( $this->redirect_to );
	}


	/**
	 * @return bool True if any of the post types involved in a legacy relationship is translatable.
	 */
	private function has_translatable_post_types_in_relationships() {
		if( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return false;
		}

		$this->relationship_controller->initialize();
		$this->relationship_controller->force_autoloader_initialization();

		$migration_controller = $this->get_migration_controller();
		$relationship_definitions = $migration_controller->get_legacy_relationship_post_type_pairs();
		$involved_post_types = array();
		foreach( $relationship_definitions as $relationship_definition ) {
			$involved_post_types[ $relationship_definition['parent'] ] = $relationship_definition['parent'];
			$involved_post_types[ $relationship_definition['child'] ] = $relationship_definition['child'];
		}

		foreach( $involved_post_types as $post_type_slug ) {
			if( $this->wpml_service->is_post_type_translatable( $post_type_slug ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @return Toolset_Relationship_Migration_Controller
	 */
	private function get_migration_controller() {
		if( null === $this->_migration_controller ) {
			$this->_migration_controller = new Toolset_Relationship_Migration_Controller();
		}
		return $this->_migration_controller;
	}


	private function get_translation_mode_change_tooltip_string() {
		$explanation = "<p>"
			. __( 'The marked post types (<strong>*</strong>) are using the "Translatable - only show translated items" WPML translation mode, but the relationships require always having a default language version of each post that has any connections to other post.', 'wpcf' )
			. "</p><p>"
			. __( 'WPML offers a new translation mode "Translatable - use translation if available or fallback to default language" which supports the required workflow much better.', 'wpcf' )
			. "</p><p>"
			. __( 'We suggest changing the translation mode for involved post types, but you may want to try this in a staging environment and check for side-effects before proceeding.', 'wpcf' )
			. "</p>";

		if( ! $this->can_set_post_translation_status() ) {
			$translation_settings_url = $this->wpml_service->is_wpml_active_and_configured()
				? $this->wpml_service->get_post_type_translation_settings_url()
				: '';
			$explanation .= "<p><strong>" . __( 'The current version of WPML requires that you change the translation mode manually.' )
				. '</strong></p><p><a href="' . esc_url_raw( $translation_settings_url ) . '">'
				. __( 'Go to Translation options', 'wpcf' ) . "</a></p>";
		}

		return $explanation;
	}
}
