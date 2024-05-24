<?php

/**
 * @since 2.1
 */
class Types_Page_Extension_Settings {

	/**
	 * @var null|Types_Helper_Twig
	 */
	private $twig;


	public function build() {
		// Custom content tab
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Relationship Migration
			add_filter( 'toolset_filter_toolset_register_settings_custom-content_section', array(
				$this,
				'custom_content_tab_m2m_activation',
			), 1, 2 );
		}

		add_filter(
			'toolset_filter_toolset_register_settings_custom-content_section',
			array( $this, 'add_rest_api_settings_section' ), 10, 2
		);

		// script
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_admin_scripts' ) );

		$bootstrap = Toolset_Common_Bootstrap::get_instance();
		$bootstrap->register_gui_base();
		Toolset_Gui_Base::get_instance()->init();

		add_action( 'admin_print_scripts', array( $this, 'prepare_dialogs' ) );

		$m2m_migration_dialog = new Types_Page_Extension_M2m_Migration_Dialog();
		$m2m_migration_dialog->prepare();
	}


	/**
	 * Admin Scripts
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
				Toolset_Gui_Base::STYLE_GUI_BASE,
			)
		);

		wp_enqueue_script(
			'types-toolset-settings',
			TYPES_RELPATH . '/public/js/settings.js',
			array(
				Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER,
				Toolset_Assets_Manager::SCRIPT_HEADJS,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
			),
			TYPES_VERSION,
			true
		);
	}


	public function print_admin_scripts() {
		echo '<script id="types_model_data" type="text/plain">'
			. base64_encode( wp_json_encode( $this->build_js_data() ) )
			. '</script>';
	}


	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 * @since m2m
	 */
	private function build_js_data() {

		$types_settings_action = Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_SETTINGS_ACTION );

		return array(
			'ajaxInfo' => array(
				'fieldAction' => array(
					'name' => $types_settings_action,
					'nonce' => wp_create_nonce( $types_settings_action ),
				),
			),
		);
	}


	/**
	 * Add a section with options related to REST API integration.
	 *
	 * @param array $sections Setting section definitions.
	 * @param Toolset_Settings $toolset_options
	 *
	 * @return array
	 */
	public function add_rest_api_settings_section( $sections, $toolset_options ) {
		/** @noinspection HtmlUnknownAttribute */
		$sections['toolset_rest_settings'] = array(
			'slug' => 'toolset_rest_settings',
			// translators: Title of a setting section.
			'title' => __( 'REST API', 'wpcf' ),
			'content' => sprintf(
				'<input type="checkbox" name="%1$s" data-types-setting-save="%1$s" value="1" %4$s><label>%2$s</label></input>'
				. '<p class="description wpcf-form-description">%3$s</p>',
				esc_attr( Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST ),
				__( 'Expose custom fields managed by Types for posts, users, and terms through the REST API', 'wpcf' ),
				sprintf(
					// translators: Description of a setting. The placeholder will be replaced with a link that says "our documentation" (or a translation thereof)
					__( 'This will add a "toolset-meta" property with custom fields to posts, users and terms that show in REST responses. Please refer to %s for further details.', 'wpcf' ),
					// translators: Part of a description of the REST API setting with a link to documentation.
					'<a href="'
					. Types_Helper_Url::get_url( 'rest-api-integration', true, false, 'gui' )
					. '">'
					. __( 'our documentation', 'wpcf' )
					. '</a>'
				),
				checked( $toolset_options->get( Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST ), true, false )
			),
		);

		return $sections;
	}


	/**
	 * @param array $sections
	 * @param mixed $toolset_options
	 *
	 * @return array
	 * @since m2m
	 */
	public function custom_content_tab_m2m_activation( $sections, /** @noinspection PhpUnusedParameterInspection */ $toolset_options ) {
		$context = $this->m2m_activation_context();

		$sections['toolset_is_m2m_enabled'] = array(
			'slug' => 'toolset_is_m2m_enabled',
			'title' => __( 'Relationships', 'wpcf' ),
			'content' => $this->get_twig()->render(
				'/setting/m2m/activation.twig', $context
			),
		);

		return $sections;
	}


	/**
	 * Generate context for m2m activation setting
	 *
	 * @return array
	 * @since m2m
	 */
	private function m2m_activation_context() {

		$gui_base = Toolset_Gui_Base::get_instance();
		$base_context = $gui_base->get_twig_context_base( Toolset_Gui_Base::TEMPLATE_LISTING, $this->m2m_prepare_js_model_data() );

		$context = array(
			'description' => __( 'Migrate from legacy post relationships to many-to-many post relationships', 'wpcf' ),
			'sections' => array( 'm2mActivation' => array() ),
			'm2m_enabled' => apply_filters( 'toolset_is_m2m_enabled', false ) === false,
		);

		return toolset_array_merge_recursive_distinct( $base_context, $context );
	}


	/**
	 * Prepare JS strings for dialog
	 *
	 * @return array
	 * @since m2m
	 */
	private function m2m_prepare_js_model_data() {
		return array(
			'sections' => array( 'm2mActivation' => array() ),
			'strings' => array(
				'confirmUnload' => __( 'There is an action in progress. Please do not leave or reload this page until it finishes.', 'wpcf' ),
			),
		);
	}


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Types_Helper_Twig
	 * @since m2m
	 */
	private function get_twig() {
		if ( null === $this->twig ) {
			$this->twig = new Types_Helper_Twig(
				array( 'settings' => TYPES_ABSPATH . '/application/views/setting' )
			);
		}

		return $this->twig;
	}


	/**
	 * @since m2m
	 */
	public function prepare_dialogs() {

	}
}
