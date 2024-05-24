<?php

/**
 * Shortcode generator for Toolset Types
 *
 * @since m2m
 */
class Types_Shortcode_Generator extends Toolset_Shortcode_Generator {

	const SCRIPT_TYPES_SHORTCODE = 'types-shortcode';

	/**
	 * Admin bar shortcodes button priority.
	 *
	 * Set to 5 to follow an order for Toolset buttons:
	 * - 5 Types/Views
	 * - 6 Forms
	 * - 7 Access
	 */
	const ADMIN_BAR_BUTTON_PRIORITY = 5;

	/**
	 * Media toolbar shortcodes button priority. Note that the native button is loaded at 10.
	 *
	 * Set to 11 to follow an order for Toolset buttons:
	 * - 11 Types/Views
	 * - 12 Forms
	 * - 13 Access
	 */
	const MEDIA_TOOLBAR_BUTTON_PRIORITY = 11;

	/**
	 * MCE shortcodes button priority.
	 *
	 * Set to 5 to follow an order for Toolset buttons:
	 * - 5 Types/Views
	 * - 6 Forms
	 * - 7 Access
	 */
	const MCE_BUTTON_PRIORITY = 5;

	/**
	 * @var bool
	 */
	private $footer_dialog_needed = false;

	/**
	 * @var array
	 */
	private $dialog_groups = array();

	/**
	 * @var string
	 */
	private $footer_dialogs = '';


	/**
	 * @var bool
	 */
	private $views_available = false;


	/**
	 * Initialize the class.
	 *
	 * @since m2m
	 */
	public function initialize() {
		$doing_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		/*
		 * ---------------------
		 * Toolset fair play:
		 * When Views is installed, there is no Types shortcode generator button
		 * ---------------------
		 */
		$this->views_available = apply_filters( 'toolset_is_views_available', false );

		/*
		 * ---------------------
		 * Admin Bar
		 * ---------------------
		 */
		// Register the Fields and Views item in the backend Admin Bar
		add_filter( 'toolset_shortcode_generator_register_item', array(
			$this,
			'register_shortcode_generator',
		), self::ADMIN_BAR_BUTTON_PRIORITY );

		/*
		 * ---------------------
		 * Button and dialogs
		 * ---------------------
		 */
		// Register and collect fields groups
		$this->dialog_groups = array();
		add_action( 'types_action_register_shortcode_group', array( $this, 'register_shortcode_group' ), 10, 2 );
		add_action( 'types_action_collect_shortcode_groups', array( $this, 'register_builtin_groups' ), 1 );
		add_action( 'wpv_action_collect_shortcode_groups', array( $this, 'register_builtin_groups' ), 10 );

		// Types in native editors plus on demand:
		// - From media_buttons actions
		// - From Toolset arbitrary editor toolbars
		add_action( 'media_buttons', array( $this, 'generate_types_button' ), self::MEDIA_TOOLBAR_BUTTON_PRIORITY );
		add_action( 'toolset_action_toolset_editor_toolbar_add_buttons', array(
			$this,
			'generate_types_custom_button',
		), 10, 2 );

		// Shortcodes button in Gutenberg classic TinyMCE editor blocks
		add_filter( 'mce_external_plugins', array( $this, 'mce_button_scripts' ), self::MCE_BUTTON_PRIORITY );
		add_filter( 'mce_buttons', array( $this, 'mce_button' ), self::MCE_BUTTON_PRIORITY );

		// Unregister on known scenarios
		add_filter( 'types_filter_add_types_button', array( $this, 'unhook_types_button' ), 10, 2 );

		// Track whether dialogs re needed and have been rendered in the footer
		$this->footer_dialogs = '';

		// Generate and print the shortcodes dialogs in the footer,
		// both in frotend and backend, as long as there is anything to print.
		// Do it as late as possible because page builders tend to register their templates,
		// including native WP editors, hence shortcode buttons, in wp_footer:10.
		// This way we can extend the dialog groups for almost the whole page request.
		// Note that we avoid this o AJAX requests.
		if ( ! $doing_ajax ) {
			add_action( 'wp_footer', array( $this, 'render_footer_dialogs' ), PHP_INT_MAX );
			add_action( 'admin_footer', array( $this, 'render_footer_dialogs' ), PHP_INT_MAX );
		}

		/*
		 * ---------------------
		 * Assets
		 *
		 * Note that we avoid this on AJAX requests
		 * ---------------------
		 */
		// Register shortcodes dialogs assets
		if ( ! $doing_ajax ) {
			add_action( 'init', array( $this, 'register_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
		}

		// Ensure that shortcodes dialogs assets are enqueued
		// both when using the Admin Bar item and when a Types button is on the page.
		if ( ! $doing_ajax ) {
			add_action( 'types_action_enforce_shortcode_assets', array( $this, 'enforce_shortcode_assets' ) );
		}

		/*
		 * ---------------------
		 * Compatibility
		 * ---------------------
		 */
		add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );
	}


	/**
	 * Register the shortcode generator in the Toolset shortcodes admin bar entry.
	 *
	 * Hooked into the toolset_shortcode_generator_register_item filter.
	 *
	 * @since m2m
	 */
	public function register_shortcode_generator( $registered_sections ) {
		$this->footer_dialog_needed = true;
		$this->enforce_shortcode_assets();

		if ( $this->views_available ) {
			return $registered_sections;
		}

		$registered_sections['types'] = array(
			'id' => 'Types',
			'title' => __( 'Types fields', 'wpcf' ),
			'href' => '#types_shortcodes',
			'parent' => 'toolset-shortcodes',
			'meta' => 'js-types-shortcode-generator-node',
		);

		return $registered_sections;
	}


	/**
	 * Register all the dedicated shortcodes assets:
	 * - Shortcodes GUI script.
	 *
	 * @todo Move the assets registration to here
	 * @since m2m
	 */
	public function register_assets() {
		$toolset_assets_manager = Toolset_Assets_Manager::get_instance();

		$types_shortcodes_dependencies = array(
			Toolset_Assets_Manager::SCRIPT_TOOLSET_SHORTCODE,
			Toolset_Assets_Manager::SCRIPT_UTILS,
		);

		if ( is_admin() ) {
			// 'wp-color-picker'  is an asset only available in the backend
			// so it becomes an optional dependency, and the script itself checks its existence
			// before initializing it on the relevant shortcode options
			$types_shortcodes_dependencies[] = 'wp-color-picker';
		}
		$toolset_assets_manager->register_script(
			self::SCRIPT_TYPES_SHORTCODE,
			TYPES_RELPATH . '/public/js/types_shortcode.js',
			$types_shortcodes_dependencies,
			TYPES_VERSION,
			true
		);

		global $pagenow;
		$conditions = array(
			'toolsetViews' => new Toolset_Condition_Plugin_Views_Active(),
		);
		$types_shortcode_i18n = array(
			'action' => array(
				'insert' => __( 'Insert shortcode', 'wpcf' ),
				'create' => __( 'Create shortcode', 'wpcf' ),
				'update' => __( 'Update shortcode', 'wpcf' ),
				'close' => __( 'Close', 'wpcf' ),
				'cancel' => __( 'Cancel', 'wpcf' ),
				'back' => __( 'Back', 'wpcf' ),
				'previous' => __( 'Previous step', 'wpcf' ),
				'next' => __( 'Next step', 'wpcf' ),
				'save' => __( 'Save settings', 'wpcf' ),
				'loading' => __( 'Loading...', 'wpcf' ),
				'wizard' => __( 'Show me how', 'wpcf' ),
				'got_it' => __( 'Got it!', 'wpcf' ),
				'install' => array(
					'toolsetViews' => array(
						'label' => __( 'Install Toolset Views', 'wpcf' ),
						'url' => admin_url( 'plugin-install.php?tab=commercial' ),
					),
				),
			),
			'title' => array(
				'dialog' => __( 'Toolset - Types fields', 'wpcf' ),
				'generated' => __( 'Toolset - generated shortcode', 'wpcf' ),
				'button' => __( 'Types', 'wpcf' ),
			),
			'validation' => array(
				'mandatory' => __( 'This option is mandatory ', 'wpcf' ),
				'number' => __( 'Please enter a valid number', 'wpcf' ),
				'numberlist' => __( 'Please enter a valid comma separated number list', 'wpcf' ),
				'url' => __( 'Please enter a valid URL', 'wpcf' ),

			),
			'conditions' => array(
				'plugins' => array(
					'toolsetViews' => $conditions['toolsetViews']->is_met(),
				),
				'page' => array(
					'viewEditor' => ( 'admin.php' === $pagenow && 'views-editor' === toolset_getget( 'page' ) ),
					'ctEditor' => ( 'admin.php' === $pagenow && 'ct-editor' === toolset_getget( 'page' ) ),
				),
			),
			'mce' => array(
				'types' => array(
					'button' => __( 'Types', 'wpcf' ),
				),
			),
			'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
			'pagenow' => $pagenow,
			'attributes' => $this->get_fields_expected_attributes(),
			'repeatingAttributes' => $this->get_repeating_fields_extra_attributes(),
			'selectorGroups' => $this->get_selector_groups_attributes(),
		);
		$toolset_assets_manager->localize_script(
			'types-shortcode',
			'types_shortcode_i18n',
			$types_shortcode_i18n
		);
	}


	/**
	 * Enforce some assets that need to be in the frontend header, like styles,
	 * when we detect that we are on a page that needs them.
	 * Basically, this involves frontend page builders, detected by their own methods.
	 * Also enforces the generation of the dialog, just in case, in the footer.
	 *
	 * @uses is_frontend_editor_page which is a parent method.
	 * @since m2m
	 */
	public function frontend_enqueue_assets() {
		// Enqueue on the frontend pages that we know it is needed, maybe on users frontend editors only
		if ( $this->is_frontend_editor_page() ) {
			$this->footer_dialog_needed = true;
			$this->enforce_shortcode_assets();
		}
	}


	/**
	 * Enforce some assets that need to be in the backend header, like styles,
	 * when we detect that we are on a page that needs them.
	 * Also enforces the generation of the dialog, just in case, in the footer.
	 *
	 * Note that we enforce the shortcode assets in all known admin editor pages.
	 *
	 * @param string $hook
	 *
	 * @since m2m
	 * @uses is_admin_editor_page which is a parent method.
	 */
	public function admin_enqueue_assets( /** @noinspection PhpUnusedParameterInspection */ $hook ) {
		if ( $this->is_admin_editor_page() ) {
			$this->footer_dialog_needed = true;
			$this->enforce_shortcode_assets();
		}
	}


	/**
	 * Enforce the shortcodes assets when loaded at a late time.
	 * Note that there should be no problem with scripts,
	 * although styles might not be correctly enqueued.
	 *
	 * @usage do_action( 'types_action_enforce_shortcode_assets' );
	 *
	 * @since m2m
	 */
	public function enforce_shortcode_assets() {
		do_action( 'toolset_enqueue_scripts', array( 'types-shortcode' ) );
		do_action( 'toolset_enqueue_styles', array(
			Toolset_Assets_Manager::STYLE_TOOLSET_SHORTCODE,
			Toolset_Assets_Manager::STYLE_SELECT2_CSS,
			Toolset_Assets_Manager::STYLE_NOTIFICATIONS,
		) );
		do_action( 'otg_action_otg_enforce_styles' );
	}


	/**
	 * Unregister the Types button from editors on known problematic scenarios.
	 *
	 * @param bool $status
	 * @param mixed $editor
	 *
	 * @return bool
	 * @since m2m
	 */
	public function unhook_types_button( /** @noinspection PhpUnusedParameterInspection */ $status, $editor ) {
		// first determine what is the situation
		$is_elementor_page_builder = ( 'elementor' === toolset_getget( 'action' ) );

		// and after that, decide what to do
		if ( $is_elementor_page_builder ) {
			return false;
		}

		return $status;
	}


	/**
	 * Check whether the shortcodes generator button should not be included in editors.
	 *
	 * @param string $editor
	 *
	 * @return bool
	 * @since 3.2
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function is_editor_button_disabled( $editor = '' ) {
		if ( ! apply_filters( 'toolset_editor_add_form_buttons', true ) ) {
			return true;
		}

		/**
		 * Public filter to disable the shortcodes button on selected editors.
		 *
		 * @param bool
		 * @param string $editor The ID of the editor.
		 *
		 * @return bool
		 * @since m2m
		 */
		if ( ! apply_filters( 'types_filter_add_types_button', true, $editor ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Generate the button on native editors, using the media_buttons action.
	 * and also on demand using a custom action.
	 *
	 * @param string $editor
	 * @param array $args
	 *     output    string    'span'|'button'. Defaults to 'span'.
	 *
	 * @since m2m
	 */
	public function generate_types_button( $editor, $args = array() ) {
		if (
			empty( $args )
			&& $this->is_editor_button_disabled()
		) {
			return;
		}

		$this->footer_dialog_needed = true;
		$this->enforce_shortcode_assets();

		if ( $this->views_available ) {
			return;
		}

		$defaults = array(
			'output' => 'span',
		);

		$args = wp_parse_args( $args, $defaults );

		$button_label = __( 'Types', 'wpcf' );

		switch ( $args['output'] ) {
			case 'button':
				$button = '<button'
					. ' class="button button-secondary js-types-in-toolbar"'
					. ' data-editor="' . esc_attr( $editor ) . '">'
					. '<i class="icon-types-logo ont-icon-18"></i>'
					. '<span class="button-label">' . esc_html( $button_label ) . '</span>'
					. '</button>';
				break;
			case 'span':
			default:
				$button = '<span'
					. ' class="button js-types-in-toolbar"'
					. ' data-editor="' . esc_attr( $editor ) . '">'
					. '<i class="icon-types-logo fa fa-types-custom ont-icon-18 ont-color-gray"></i>'
					. '<span class="button-label">' . esc_html( $button_label ) . '</span>'
					. '</span>';
				break;
		}

		$this->enforce_shortcode_assets();

		echo $button;
	}


	/**
	 * Generate a button for custom editor toolbars, inside a <li></li> HTML tag.
	 *
	 * Hooked into the toolset_action_toolset_editor_toolbar_add_buttons action.
	 *
	 * @param string $editor The editor ID.
	 * @param string $source The Toolset plugin originting the call.
	 *
	 * @since m2m
	 */
	public function generate_types_custom_button( /** @noinspection PhpUnusedParameterInspection */ $editor, $source = '' ) {

		$this->footer_dialog_needed = true;
		$this->enforce_shortcode_assets();

		if ( $this->views_available ) {
			return;
		}

		$args = array(
			'output' => 'button',
		);
		echo '<li>';
		$this->generate_types_button( $editor, $args );
		echo '</li>';

	}


	/**
	 * Add a TinyMCE plugin script for the shortcodes generator button.
	 *
	 * Note that this only gets registered when editing a post with Gutenberg.
	 *
	 * @param array $plugin_array
	 *
	 * @return array
	 * @since 2.7
	 */
	public function mce_button_scripts( $plugin_array ) {
		if (
			$this->views_available
			|| ! $this->is_blocks_editor_page()
			|| $this->is_editor_button_disabled()
		) {
			return $plugin_array;
		}
		$this->gutenberg_enqueue_assets();
		$plugin_array['toolset_add_types_shortcode_button'] = TYPES_RELPATH
			. '/public/js/compatibility/bundle.tinymce.js?ver='
			. TYPES_VERSION;

		return $plugin_array;
	}


	/**
	 * Add a TinyMCE button for the shortcodes generator button.
	 *
	 * Note that this only gets registered when editing a post with Gutenberg.
	 *
	 * @param array $buttons
	 *
	 * @return array
	 * @since 2.7
	 */
	public function mce_button( $buttons ) {
		if (
			$this->views_available
			|| ! $this->is_blocks_editor_page()
			|| $this->is_editor_button_disabled()
		) {
			return $buttons;
		}

		$this->gutenberg_enqueue_assets();
		$buttons[] = 'toolset_types_shortcodes';
		$classic_editor_block_toolbar_icon_style = '.ont-icon-block-classic-toolbar::before {position:absolute;top:1px;left:2px;}';
		wp_add_inline_style(
			Toolset_Assets_Manager::STYLE_TOOLSET_COMMON,
			$classic_editor_block_toolbar_icon_style
		);

		return $buttons;
	}


	/**
	 * Enforce the shortcodes generator assets when using a Gutenberg editor.
	 *
	 * @since 2.7
	 */
	public function gutenberg_enqueue_assets() {
		$this->footer_dialog_needed = true;
		$this->enforce_shortcode_assets();
	}


	/**
	 * Register fields in some selected AJAX calbacks that require them.
	 *
	 * @since 3.2.5
	 */
	private function register_builtin_groups_in_ajax() {
		// Register the right fields in the Views loop wizard list of available items
		if ( 'wpv_loop_wizard_add_field' === toolset_getpost( 'action' ) ) {
			$domain = toolset_getpost( 'domain', 'posts' );

			// Note that the Views domains do not follow the Toolset_Field_Utils values
			switch ( $domain ) {
				case 'taxonomy':
					$this->register_meta_dialog_groups( Toolset_Element_Domain::TERMS );
					break;
				case 'users':
					$this->register_meta_dialog_groups( Toolset_Element_Domain::USERS );
					break;
				case 'posts':
				default:
					$this->register_meta_dialog_groups( Toolset_Element_Domain::POSTS );
					$this->register_meta_dialog_groups( Toolset_Element_Domain::USERS );
					break;
			}
		}
	}


	/**
	 * Register Types fields groups in the API.
	 *
	 * @since m2m
	 */
	public function register_builtin_groups() {
		global $pagenow;

		if ( 'admin-ajax.php' === $pagenow ) {
			$this->register_builtin_groups_in_ajax();

			return;
		}

		if (
			$pagenow === 'admin.php'
			&& in_array( toolset_getget( 'page' ), array(
				'views-editor',
				'ct-editor',
				'view-archives-editor',
				'dd_layouts_edit',
			), true )
		) {
			// We are on a Views object edit page, so add all Types postmeta groups and usermeta groups
			// We can also be on a Layouts object edit page, so we add all postmeta and usermeta groups too
			foreach ( Toolset_Element_Domain::all() as $domain ) {
				$this->register_meta_dialog_groups( $domain );
			}

			return;
		}

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			// We are on a post edit page, add the postmeta and usermeta groups
			$this->register_meta_dialog_groups( Toolset_Element_Domain::POSTS );
			$this->register_meta_dialog_groups( Toolset_Element_Domain::USERS );

			return;
		}

		if ( in_array( $pagenow, array( 'edit-tags.php', 'term.php' ), true ) ) {
			// We are on a term edit page, add the termmeta groups
			$this->register_meta_dialog_groups( Toolset_Element_Domain::TERMS );

			return;
		}

		if ( in_array( $pagenow, array( 'profile.php', 'user-new.php', 'user-edit.php' ), true ) ) {
			// We are on an user edit page, add the usermeta groups
			$this->register_meta_dialog_groups( Toolset_Element_Domain::USERS );

			return;
		}

		// We are elsewhere, add the postmeta and usermeta groups
		$this->register_meta_dialog_groups( Toolset_Element_Domain::POSTS );
		$this->register_meta_dialog_groups( Toolset_Element_Domain::USERS );

	}


	/**
	 * Adjust the data for each field before generating the shortcodes dialog items.
	 *
	 * Fields data lacks the following pieces:
	 * - For checkboxes and radios, WPML localization of option labels.
	 * - For all fields, the JS callback to fire when clicking the button.
	 *
	 * @param string $field_slug
	 * @param array $field_data
	 *
	 * @return array
	 * @since 3.3.6
	 */
	private function adjust_field_data_on_print( $field_slug, $field_data ) {
		$meta_type = toolset_getnest( $field_data, array( 'parameters', 'metaType' ) );

		if ( in_array( $meta_type, array( 'checkboxes', 'radio' ), true ) ) {
			$meta_options = toolset_getnest( $field_data, array( 'parameters', 'metaOptions' ), array() );
			foreach ( $meta_options as $meta_option_key => $meta_option_data ) {
				$meta_options[ $meta_option_key ]['title'] = wpcf_translate(
					'field ' . $field_slug . ' option ' . $meta_option_key . ' title',
					toolset_getarr( $meta_option_data, 'title' )
				);
			}
			$field_data['parameters']['metaOptions'] = $meta_options;
		}

		// Using base64_encode to prevent issues with breaking unicode characters (when using `esc_js( wp_json_encode( $parameters ) )`).
		$field_data['callback'] = "Toolset.Types.shortcodeGUI.shortcodeDialogOpen({
			shortcode: 'types',
			title: '" . esc_js( toolset_getarr( $field_data, 'name' ) ) . "',
			parameters: '" . base64_encode( wp_json_encode( toolset_getarr( $field_data, 'parameters', array() ) ) ) . '\'
		})';

		return $field_data;
	}


	/**
	 * Register the meta fields groups given a valid domain.
	 *
	 * On a previous iteration RFGs were only available for the Views loop wizard,
	 *     or when editing a Views object; this restriction was lifted and now they can
	 *     be added anywhere.
	 *
	 * @param string $domain
	 *
	 * @since m2m
	 * @since 3.3.6 Use the dedicated cache to gather the groups and fields to include.
	 */
	private function register_meta_dialog_groups( $domain ) {
		$cached_groups = apply_filters( 'types_get_sg_' . $domain . '_meta_cache', array() );

		foreach ( $cached_groups as $group_id => $group_data ) {
			if ( empty( $group_data['fields'] ) ) {
				continue;
			}
			foreach ( $group_data['fields'] as $field_slug => $field_data ) {
				$group_data['fields'][ $field_slug ] = $this->adjust_field_data_on_print( $field_slug, $field_data );
			}

			do_action( 'types_action_register_shortcode_group', $group_id, $group_data );
			do_action( 'wpv_action_register_shortcode_group', $group_id, $group_data );
		}
	}


	/**
	 * Register a dialog group with its fields.
	 *
	 * @param string $group_id The group unique ID.
	 * @param array $group_data The group data:
	 *     name        string    The group name that will be used over the group fields.
	 *     fields    array    Optional. The group fields. Leave blank or empty to just pre-register the group.
	 *
	 * @usage do_action( 'types_action_register_shortcode_group', $group_id, $group_data );
	 * @since m2m
	 */
	public function register_shortcode_group( $group_id = '', $group_data = array() ) {
		$group_id = sanitize_text_field( $group_id );

		if ( empty( $group_id ) ) {
			return;
		}

		$group_data['fields'] = ( isset( $group_data['fields'] ) && is_array( $group_data['fields'] ) )
			? $group_data['fields'] : array();

		$dialog_groups = $this->dialog_groups;

		if ( isset( $dialog_groups[ $group_id ] ) ) {

			// Extending an already registered group, which should have a name already.
			if ( ! array_key_exists( 'name', $dialog_groups[ $group_id ] ) ) {
				return;
			}
			foreach ( $group_data['fields'] as $field_key => $field_data ) {
				$dialog_groups[ $group_id ]['fields'][ $field_key ] = $field_data;
			}

		} else {

			// Registering a new group, the group name is mandatory
			if ( ! array_key_exists( 'name', $group_data ) ) {
				return;
			}
			$dialog_groups[ $group_id ]['name'] = $group_data['name'];
			$dialog_groups[ $group_id ]['fields'] = $group_data['fields'];

		}
		$this->dialog_groups = $dialog_groups;
	}


	/**
	 * Generate the main shortcode GUI dialog listing all the available relevant fields.
	 *
	 * @since m2m
	 * @todo Move this to a proper template
	 */
	public function generate_shortcodes_dialog() {
		$dialog_content = '';

		foreach ( $this->dialog_groups as $group_data ) {

			if ( empty( $group_data['fields'] ) ) {
				continue;
			}

			$dialog_content .= '<div class="toolset-collapsible js-toolset-collapsible is-opened">'
				. '<h4 class="toolset-collapsible__header">'
				. '<button type="button" aria-expanded="true" class="toolset-collapsible__toggle js-toolset-collapsible__toggle">
							<svg class="toolset-collapsible__toggle-arrow" width="24px" height="24px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" focusable="false"><g><path fill="none" d="M0,0h24v24H0V0z"></path></g><g><path d="M12,8l-6,6l1.41,1.41L12,10.83l4.59,4.58L18,14L12,8z"></path></g></svg>
							<span class="toolset-collapsible__title">'
				. esc_html( $group_data['name'] )
				. '</span>
						</button>'
				. '</h4>';
			$dialog_content .= "\n";
			$dialog_content .= '<div class="toolset-collapsible__body toolset-shortcodes__group js-types-shortcode-gui-group-list">';
			$dialog_content .= "\n";
			foreach ( $group_data['fields'] as $group_data_field_data ) {
				if (
					! isset( $group_data_field_data['callback'] )
					|| empty( $group_data_field_data['callback'] )
				) {
					$dialog_content .= sprintf(
						'<button class="toolset-shortcode-button js-toolset-shortcode-button js-types-shortcode-gui-no-attributes" data-shortcode="%s" >%s</button>',
						'[' . esc_attr( $group_data_field_data['shortcode'] ) . ']',
						esc_html( $group_data_field_data['name'] )
					);
				} else {
					/** @noinspection BadExpressionStatementJS */
					$dialog_content .= sprintf(
						'<button class="toolset-shortcode-button js-toolset-shortcode-button js-types-shortcode-gui" onclick="%s; return false;">%s</button>',
						$group_data_field_data['callback'],
						esc_html( $group_data_field_data['name'] )
					);
				}
				$dialog_content .= "\n";
			}
			$dialog_content .= '</div>';
			$dialog_content .= "\n";
			$dialog_content .= '</div>';
		}

		// generate output content
		$out = '
		<div id="js-types-shortcode-gui-dialog-container-main" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body">'
			. $this->get_shortcodes_search_bar( 'types-shortcode-gui-dialog-searchbar-input-for-types' )
			. '<div class="toolset-shortcodes__wrapper js-toolset-shortcodes__wrapper js-types-shortcode-gui-dialog-content">'
			. "\n"
			. $dialog_content
			. '
			</div>
		</div>';

		$this->footer_dialogs .= $out;
	}


	/**
	 * Render the footer dialog, when needed.
	 *
	 * @since m2m
	 */
	public function render_footer_dialogs() {
		if ( ! $this->footer_dialog_needed ) {
			return;
		}

		// Generate foter dialogs even if Views is active, so we can
		// offer to append Types shortcodes directly
		do_action( 'types_action_collect_shortcode_groups' );
		$this->generate_shortcodes_dialog();
		// Make sure that Toolset Common shared templates are included,
		// and also the custom own ones.
		do_action( 'toolset_action_require_shortcodes_templates' );
		$this->generate_shortcode_templates();

		$footer_dialogs = $this->footer_dialogs;
		if ( '' !== $footer_dialogs ) {
			?>
			<div class="js-types-footer-dialogs" style="display:none">
				<?php
				echo $footer_dialogs;
				?>
			</div>
			<?php
		}
	}


	/**
	 * Generate the repeating fields attribute for the index and separator.
	 * This will be automatically appended when the field is a repeating one.
	 *
	 * @since m2m
	 */
	private function get_repeating_fields_extra_attributes() {
		$attributes = array();

		$attributes['index'] = array(
			'label' => __( 'Field index to display', 'wpcf' ),
			'type' => 'text',
			'defaultValue' => '',
			'description' => __( 'Zero-based index number of the field to be output.', 'wpcf' ),
		);

		$attributes['separator'] = array(
			'label' => __( 'Separator between multiple values', 'wpcf' ),
			'type' => 'text',
			'defaultForceValue' => ', ',
		);

		return $attributes;
	}


	/**
	 * Generate the item selector attribute for postmeta, termmeta and usermeta fields.
	 * This will be automatically appended to all fields and the matching template is shard in Toolset Common.
	 *
	 * @since m2m
	 */
	private function get_selector_groups_attributes() {
		return array(
			'typesUserSelector' => array(
				'header' => __( 'User selection', 'wpcf' ),
				'fields' => array(
					'item' => array(
						'label' => __( 'Display the field for this user', 'wpcf' ),
						'type' => 'typesUserSelector',
						'defaultForceValue' => 'author',
					),
				),
			),
			'typesViewsUserSelector' => array(
				'header' => __( 'User selection', 'wpcf' ),
				'fields' => array(
					'item' => array(
						'label' => __( 'Display the field for this user', 'wpcf' ),
						'type' => 'typesViewsUserSelector',
						'defaultForceValue' => 'viewloop',
					),
				),
			),
			'typesViewsTermSelector' => array(
				'header' => __( 'Term selection', 'wpcf' ),
				'fields' => array(
					'term_id' => array(
						'label' => __( 'Display the field for this term', 'wpcf' ),
						'type' => 'typesViewsTermSelector',
						'defaultValue' => 'viewloop',
					),
				),
			),
		);
	}


	/**
	 * List the expected attributed of each field type.
	 * Provide a fallback for generic fields not available in this API.
	 *
	 * Note that the 'displayOptions' group should be always included, even empty,
	 * since this is where repeating fields attributes are appended into.
	 *
	 * @since m2m
	 */
	private function get_fields_expected_attributes() {
		$attributes = array();

		$attributes['typesGenericType'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(),
			),
		);

		// Audio is OK
		$attributes['audio'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Audio player', 'wpcf' ),
							'raw' => __( 'Raw audio file URL', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'preload' => array(
						'label' => __( 'Preload', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Begin the media download as soon as the page is loaded', 'wpcf' ),
							'off' => __( 'Hold the media download until the visitor plays it', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
					'loop' => array(
						'label' => __( 'Loop', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Replay the audio field once it reaches the end', 'wpcf' ),
							'off' => __( 'Stop the audio play when it reaches the end', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
					'autoplay' => array(
						'label' => __( 'Autoplay', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Begin the media play as soon as the page is loaded', 'wpcf' ),
							'off' => __( 'Hold the media play until the visitor starts it', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
				),
			),
		);

		// Checkbox is OK
		// state='(un)checked' is not working
		$attributes['checkbox'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Normal', 'wpcf' ),
							'raw' => __( 'Raw value', 'wpcf' ),
							'custom' => __( 'Custom values for selected and not selected states', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'outputCustomCombo' => array(
						'label' => __( 'Custom values', 'wpcf' ),
						'type' => 'group',
						'hidden' => true,
						'fields' => array(
							'selectedValue' => array(
								'pseudolabel' => __( 'When selected', 'wpcf' ),
								'type' => 'text',
							),
							'unselectedValue' => array(
								'pseudolabel' => __( 'When not selected', 'wpcf' ),
								'type' => 'text',
							),
						),
					),
					'show_name' => array(
						'label' => __( 'Field name', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'true' => __( 'Show the field name before its value', 'wpcf' ),
							'false' => __( 'Do not show the field name before its value', 'wpcf' ),
						),
						'defaultValue' => 'false',
					),
				),
			),
		);

		// Checkboxes also support a "checked"/"unchecked" values for the state attribute
		// Checkboxes fields offer one custom output per value
		$attributes['checkboxes'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Normal', 'wpcf' ),
							'raw' => __( 'Raw values', 'wpcf' ),
							'custom' => __( 'Custom values for selected and not selected states', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'separator' => array(
						'label' => __( 'Separator between multiple values', 'wpcf' ),
						'type' => 'text',
						'defaultForceValue' => ', ',
					),
					'outputCustomCombo' => array(
						'label' => __( 'Custom values', 'wpcf' ),
						'type' => 'group',
						'hidden' => true,
						'fields' => array(
							'selectedValue' => array(
								'pseudolabel' => __( '%%OPTION%% selected', 'wpcf' ),
								'type' => 'text',
							),
							'unselectedValue' => array(
								'pseudolabel' => __( '%%OPTION%% not selected', 'wpcf' ),
								'type' => 'text',
							),
						),
					),
				),
			),
		);

		// Colorpicker is OK
		$attributes['colorpicker'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(),
			),
		);

		// Date is OK
		$info_about_escaping = __( 'Use % to escape characters, because Wordpress removes backslashes from shortcode attributes.', 'wpcf' );

		$date_site_settings = get_option( 'date_format' );
		$date_site_settings_has_escaped_chars = strpos( $date_site_settings, '\\' ) !== false;
		$date_site_settings_formatted = str_replace( '\\', '%', $date_site_settings );

		$date_site_settings_info = $date_site_settings_has_escaped_chars
			? '<br /><span class="description">' . $info_about_escaping . '</span>'
			: ''; // no escaped characters on site settings date, so no extra info needed here.

		$attributes['date'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Formatted date', 'wpcf' ),
							'raw' => __( 'Raw timestamp', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'style' => array(
						'label' => __( 'Output style', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'calendar' => __( 'Calendar', 'wpcf' ),
							'text' => __( 'Plain text', 'wpcf' ),
						),
						'defaultForceValue' => 'text',
					),
					'format' => array(
						'label' => __( 'Format', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							$date_site_settings_formatted => $date_site_settings_formatted
								. ' - '
								. date_i18n( $date_site_settings )
								. $date_site_settings_info,
							'F j, Y g:i a' => 'F j, Y g:i a - ' . date_i18n( 'F j, Y g:i a' ),
							'F j, Y' => 'F j, Y - ' . date_i18n( 'F j, Y' ),
							'd/m/y' => 'd/m/y - ' . date_i18n( 'd/m/y' ),
							'toolsetCombo' => __( 'Custom', 'wpcf' ),
						),
						'defaultForceValue' => $date_site_settings_formatted,
					),
					'toolsetCombo:format' => array(
						'type' => 'text',
						'hidden' => true,
						'placeholder' => 'l, F j, Y',
						'description' => $info_about_escaping,
					),
				),
			),
		);

		// Email is OK
		// class attribute is not working
		$attributes['email'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Email link', 'wpcf' ),
							'raw' => __( 'Raw email address', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'title' => array(
						'label' => __( 'Email link label', 'wpcf' ),
						'type' => 'text',
						'defaultValue' => '',
						'description' => __( 'If empty, the same email address value will be used instead', 'wpcf' ),
					),
					'attributesCombo' => array(
						'type' => 'group',
						'fields' => array(
							'class' => array(
								'label' => __( 'Email link extra classes', 'wpcf' ),
								'type' => 'text',
							),
							'style' => array(
								'label' => __( 'Email link inline style', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Include specific classnames in the email link, or add your own inline styles.', 'wpcf' ),
					),
				),
			),
		);

		// Embed is OK
		$attributes['embed'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Embed player', 'wpcf' ),
							'raw' => __( 'Raw field content', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'sizeCombo' => array(
						'label' => __( 'Size', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'width' => array(
								'pseudolabel' => __( 'Width', 'wpcf' ),
								'type' => 'text',
							),
							'height' => array(
								'pseudolabel' => __( 'Height', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Images will be resized before being sent to the client. width and height will be ignored if size is set. For Embedded media the width and height are maximum values and may be ignored if $content_width is set for the theme.', 'wpcf' ),
					),
				),
			),
		);

		// File is OK
		$attributes['file'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'File link', 'wpcf' ),
							'raw' => __( 'Raw file URL', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'title' => array(
						'label' => __( 'File link title', 'wpcf' ),
						'type' => 'text',
					),
					'attributesCombo' => array(
						'type' => 'group',
						'fields' => array(
							'class' => array(
								'label' => __( 'File link extra classes', 'wpcf' ),
								'type' => 'text',
							),
							'style' => array(
								'label' => __( 'File link inline style', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Include specific classnames in the file link, or add your own inline styles.', 'wpcf' ),
					),
				),
			),
		);

		// Image is OK
		// class attribute is not working
		$image_size_attributes = array(
			'full' => __( 'Original image', 'wpcf' ),
			'thumbnail' => sprintf( __( 'Thumbnail - %s', 'wpcf' ),
				get_option( 'thumbnail_size_w' ) . 'x' . get_option( 'thumbnail_size_h' ) ),
			'medium' => sprintf( __( 'Medium - %s', 'wpcf' ),
				get_option( 'medium_size_w' ) . 'x' . get_option( 'medium_size_h' ) ),
			'large' => sprintf( __( 'Large - %s', 'wpcf' ),
				get_option( 'large_size_w' ) . 'x' . get_option( 'large_size_h' ) ),
		);
		$wp_image_sizes = (array) get_intermediate_image_sizes();
		foreach ( $wp_image_sizes as $wp_size ) {
			if (
				$wp_size !== 'post-thumbnail'
				&& ! array_key_exists( $wp_size, $image_size_attributes )
			) {
				$image_size_attributes[ $wp_size ] = $wp_size;
			}
		}
		$image_size_attributes['custom'] = __( 'Custom size...', 'wpcf' );
		$attributes['image'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Image tag', 'wpcf' ),
							'raw' => __( 'Raw field value', 'wpcf' ),
							'url' => __( 'URL of a resized version of the image', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'titleAltCombo' => array(
						'label' => __( 'Title and alternative text', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'title' => array(
								'pseudolabel' => __( 'Title', 'wpcf' ),
								'type' => 'text',
								'defaultForceValue' => '%%TITLE%%',
							),
							'alt' => array(
								'pseudolabel' => __( 'Alt', 'wpcf' ),
								'type' => 'text',
								'defaultForceValue' => '%%ALT%%',
							),
						),
						'description' => __( 'You can use placeholders to output the values of standard image fields added in WordPress: %%TITLE%%, %%ALT%%, %%CAPTION%%, and %%DESCRIPTION%%.', 'wpcf' ),
					),
					'align' => array(
						'label' => __( 'Align', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'none' => __( 'None', 'wpcf' ),
							'left' => __( 'Left', 'wpcf' ),
							'center' => __( 'Center', 'wpcf' ),
							'right' => __( 'Right', 'wpcf' ),
						),
						'defaultValue' => 'none',
					),
					'size' => array(
						'label' => __( 'Image size', 'wpcf' ),
						'type' => 'radio',
						'options' => $image_size_attributes,
						'defaultForceValue' => 'full',
					),
					'sizeCombo' => array(
						'label' => __( 'Size', 'wpcf' ),
						'type' => 'group',
						'hidden' => true,
						'fields' => array(
							'width' => array(
								'pseudolabel' => __( 'Width', 'wpcf' ),
								'type' => 'text',
							),
							'height' => array(
								'pseudolabel' => __( 'Height', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Image will be resized before being sent to the client. For Embedded media, the width and height are maximum values and may be ignored if $content_width is set for the theme.', 'wpcf' ),
					),
					'proportional' => array(
						'label' => __( 'Proportional', 'wpcf' ),
						'type' => 'radio',
						'hidden' => true,
						'options' => array(
							'false' => __( 'Do not keep image proportional', 'wpcf' ),
							'true' => __( 'Keep image proportional', 'wpcf' ),
						),
						'defaultValue' => 'true',
						'description' => __( 'Image will be cropped to specified height and width.', 'wpcf' ),
					),
					'resize' => array(
						'label' => __( 'Image adjustment', 'wpcf' ),
						'type' => 'radio',
						'hidden' => true,
						'options' => array(
							'proportional' => __( 'Resize images to fit inside the new size. Width or height might be smaller than the specified dimensions', 'wpcf' ),
							'crop' => __( 'Crop images, so that they fill the specified dimensions exactly', 'wpcf' ),
							'stretch' => __( 'Stretch images', 'wpcf' ),
							'pad' => __( 'Pad images, so that they fill the specified dimensions exactly', 'wpcf' ),
						),
						'defaultForceValue' => 'proportional',
					),
					'padding_color' => array(
						'label' => __( 'Padding color', 'wpcf' ),
						'type' => 'radio',
						'hidden' => true,
						'options' => array(
							'transparent' => __( 'Transparent', 'wpcf' ),
							'toolsetCombo' => __( 'Custom', 'wpcf' ),
						),
						'defaultValue' => 'transparent',
					),
					'toolsetCombo:padding_color' => array(
						'type' => 'text',
						'hidden' => true,
					),
					'class' => array(
						'label' => __( 'Image tag extra classes', 'wpcf' ),
						'type' => 'text',
					),
					'style' => array(
						'label' => __( 'Image tag inline style', 'wpcf' ),
						'type' => 'text',
					),
				),
			),
		);

		// Numeric is OK
		$attributes['numeric'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'As described in the following format', 'wpcf' ),
							'raw' => __( 'Raw value from the database', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'format' => array(
						'label' => __( 'Format', 'wpcf' ),
						'type' => 'text',
						'required' => true,
						'defaultForceValue' => 'FIELD_VALUE',
						'description' => __( 'Use the placeholders FIELD_NAME and FIELD_VALUE in the format to ouput the name and value', 'wpcf' ),
					),
				),
			),
		);

		// Phone is OK
		$attributes['phone'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(),
			),
		);

		// Post seems to be OK
		// This might not be needed at all, could be removed
		$attributes['post'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(),
			),
		);

		// Radio is OK
		// Radio fields offer one custom output per value
		$attributes['radio'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Display value of the selected option', 'wpcf' ),
							'raw' => __( 'Raw database value of the selected option', 'wpcf' ),
							'custom' => __( 'Custom values for selected and not selected states', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'outputCustomCombo' => array(
						'label' => __( 'Custom values', 'wpcf' ),
						'type' => 'group',
						'hidden' => true,
						'fields' => array(
							'selectedValue' => array(
								'pseudolabel' => __( '%%OPTION%% selected', 'wpcf' ),
								'type' => 'text',
							),
						),
					),
				),
			),
		);

		// Select is OK
		$attributes['select'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Display value of the selected option', 'wpcf' ),
							'raw' => __( 'Raw database value of the selected option', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
				),
			),
		);

		// Skype is OK
		// class attribute is not working
		$attributes['skype'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Rich Skype button', 'wpcf' ),
							'raw' => __( 'Raw Skype name', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'skype_button_style' => array(
						'label' => __( 'Button', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'button' => array(
								'pseudolabel' => __( 'Style', 'wpcf' ),
								'type' => 'select',
								'options' => array(
									'bubble' => __( 'Bubble (fixed position at the bottom right of the window)', 'wpcf' ),
									'rounded' => __( 'Rounded (in place of the shortcode)', 'wpcf' ),
									'rectangle' => __( 'Rectangle (in place of the shortcode)', 'wpcf' ),
								),
							),
							'button-color' => array(
								'pseudolabel' => __( 'Color', 'wpcf' ),
								'type' => 'text',
								'defaultValue' => '#00AFF0',
							),
						),
					),
					'skype_button_style_enhanced' => array(
						'pseudolabel' => __( 'Button Style', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'button-icon' => array(
								'pseudolabel' => __( 'Icon', 'wpcf' ),
								'type' => 'select',
								'options' => array(
									'enabled' => __( 'Enabled', 'wpcf' ),
									'disabled' => __( 'Disabled', 'wpcf' ),
								),
								'defaultValue' => 'enabled',
							),
							'button-label' => array(
								'pseudolabel' => __( 'Label', 'wpcf' ),
								'type' => 'text',
								'placeholder' => 'Contact Us',
							),
						),
					),

					'skype_chat_style' => array(
						'label' => __( 'Chat Color', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'chat-color' => array(
								'type' => 'text',
								'defaultValue' => '#80DDFF',
							),
						),
					),

					'skype_preview' => array(
						'label' => __( 'Preview', 'wpcf' ),
						'type' => 'skype',
						'i18n' => array(
							'button' => __( 'Button', 'wpcf' ),
							'chat' => __( 'Chat', 'wpcf' ),
							'preview_title' => __( 'Preview', 'wpcf' ),
							'cdn_not_reachable' => __( 'The required files for the preview could not be loaded from the Skype servers. Continue without preview or try again later.', 'wpcf' ),
						),
					),

					'receiver' => array(
						'label' => __( 'Treat field value as' ),
						'type' => 'select',
						'options' => array(
							'user' => __( 'User (Skype User ID)', 'wpcf' ),
							'bot' => __( 'Bot (Microsoft App ID)', 'wpcf' ),
						),
						'defaultValue' => 'user',
						'description' => sprintf( __( 'To learn more about Skype Bots see %s', 'wpcf' ),
							'<a href="https://dev.skype.com/bots" target="_blank">https://dev.skype.com/bots</a>' ),
					),

					'class' => array(
						'label' => __( 'Skype button extra classes', 'wpcf' ),
						'type' => 'text',
						'description' => __( 'Include specific classnames in the Skype button.', 'wpcf' ),
					),
				),
			),
		);

		// Textarea is OK
		$attributes['textarea'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Normal', 'wpcf' ),
							'raw' => __( 'Raw', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
				),
			),
		);

		// Textfield is OK
		$attributes['textfield'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(),
			),
		);

		// URL is OK
		// class attribute is not working
		$attributes['url'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'URL link', 'wpcf' ),
							'raw' => __( 'Raw URL', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'title' => array(
						'label' => __( 'URL link label', 'wpcf' ),
						'type' => 'text',
						'defaultValue' => '',
						'description' => __( 'If empty, the same URL value will be used instead', 'wpcf' ),
					),
					'attributesCombo' => array(
						'type' => 'group',
						'fields' => array(
							'class' => array(
								'label' => __( 'URL link extra classes', 'wpcf' ),
								'type' => 'text',
							),
							'style' => array(
								'label' => __( 'URL link inline style', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Include specific classnames in the URL link, or add your own inline styles.', 'wpcf' ),
					),
					'target' => array(
						'label' => __( 'Open link in:', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'_blank' => __( 'New window or tab', 'wpcf' ),
							'_self' => __( 'Same frame as clicked', 'wpcf' ),
							'_parent' => __( 'Parent frame', 'wpcf' ),
							'_top' => __( 'Full body of the window', 'wpcf' ),
							'toolsetCombo' => __( 'Named frame', 'wpcf' ),
						),
						'defaultValue' => '_self',
					),
					'toolsetCombo:target' => array(
						'label' => __( 'Name of the frame', 'wpcf' ),
						'type' => 'text',
						'hidden' => true,
					),
				),
			),
		);

		// Video is OK
		$attributes['video'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Normal', 'wpcf' ),
							'raw' => __( 'Raw', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'sizeCombo' => array(
						'label' => __( 'Size', 'wpcf' ),
						'type' => 'group',
						'fields' => array(
							'width' => array(
								'pseudolabel' => __( 'Width', 'wpcf' ),
								'type' => 'text',
							),
							'height' => array(
								'pseudolabel' => __( 'Height', 'wpcf' ),
								'type' => 'text',
							),
						),
						'description' => __( 'Image will be resized before being sent to the client. width and height will be ignored if size is set. For Embedded media the width and height are maximum values and may be ignored if $content_width is set for the theme.', 'wpcf' ),
					),
					'poster' => array(
						'label' => __( 'Poster cover image URL', 'wpcf' ),
						'type' => 'text',
						'description' => __( 'The poster image will be displayed while the video is not playing. The image height will be set to match the height of the video container', 'wpcf' ),
					),
					'preload' => array(
						'label' => __( 'Preload', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Begin the media download as soon as the page is loaded', 'wpcf' ),
							'off' => __( 'Hold the media download until the visitor plays it', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
					'loop' => array(
						'label' => __( 'Loop', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Replay the audio field once it reaches the end', 'wpcf' ),
							'off' => __( 'Stop the audio play when it reaches the end', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
					'autoplay' => array(
						'label' => __( 'Autoplay', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'on' => __( 'Begin the media play as soon as the page is loaded', 'wpcf' ),
							'off' => __( 'Hold the media play until the visitor starts it', 'wpcf' ),
						),
						'defaultValue' => 'off',
					),
				),
			),
		);

		// WYSIWYG is OK
		$attributes['wysiwyg'] = array(
			'displayOptions' => array(
				'header' => __( 'Display options', 'wpcf' ),
				'fields' => array(
					'output' => array(
						'label' => __( 'Output mode', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'normal' => __( 'Normal', 'wpcf' ),
							'raw' => __( 'Raw', 'wpcf' ),
						),
						'defaultValue' => 'normal',
					),
					'suppress_filters' => array(
						'label' => __( 'Third party filters', 'wpcf' ),
						'type' => 'radio',
						'options' => array(
							'true' => __( 'Do not apply third party filters to the output', 'wpcf' ),
							'false' => __( 'Apply third party filters to the output', 'wpcf' ),
						),
						'defaultValue' => 'false',
					),
				),
			),
		);

		return apply_filters( 'types_extend_fields_expected_attributes', $attributes );
	}


	/**
	 * Generate the templates needed for Types shortcodes:
	 * - typesUserSelector for usermeta fields
	 * - typesViewsUserSelector for usermeta fields in scenarios where the View target is users
	 * - typesViewsTermSelector for termmeta fields in scenarios where the View target is terms
	 *
	 * @since m2m
	 */
	private function generate_shortcode_templates() {
		$toolset_ajax = Toolset_Ajax::get_instance();
		$template_repository = Types_Output_Template_Repository::get_instance();
		$renderer = Toolset_Renderer::get_instance();
		?>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-typesUserSelector">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-user-id-author">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id-author"
							name="toolset_shortcode_gui_object_id" value="author" checked="checked" />
						<?php _e( 'Author of the current post', 'wpcf' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-user-id-current">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id-current"
							name="toolset_shortcode_gui_object_id" value="current" />
						<?php _e( 'The current logged in user', 'wpcf' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
					<label for="toolset-shortcode-gui-item-selector-user-id">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id" name="toolset_shortcode_gui_object_id"
							value="object_id" />
						<?php _e( 'A specific user', 'wpcf' ); ?>
					</label>
					<div
						class="toolset-advanced-settingtoolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related"
						style="display:none;padding-top:10px;">
						<select id="toolset-shortcode-gui-item-selector-user-id-object_id"
							class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
							name="specific_object_id"
							data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) ); ?>"
							data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) ); ?>"
							data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ); ?>"
							data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ); ?>"
							data-placeholder="<?php echo esc_attr( __( 'Search for a user', 'wpcf' ) ); ?>">
						</select>
					</div>
				</li>
			</ul>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-typesViewsUserSelector">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-user-id-viewloop">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id-viewloop"
							name="toolset_shortcode_gui_object_id" value="viewloop" checked="checked" />
						<?php _e( 'The current user in the loop', 'wpcf' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-user-id-current">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id-current"
							name="toolset_shortcode_gui_object_id" value="current" />
						<?php _e( 'The current logged in user', 'wpcf' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
					<label for="toolset-shortcode-gui-item-selector-user-id">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-user-id" name="toolset_shortcode_gui_object_id"
							value="object_id" />
						<?php _e( 'A specific user', 'wpcf' ); ?>
					</label>
					<div
						class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related"
						style="display:none;padding-top:10px;">
						<select id="toolset-shortcode-gui-item-selector-user-id-object_id"
							class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
							name="specific_object_id"
							data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ) ); ?>"
							data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ) ); ?>"
							data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_USERS ); ?>"
							data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_USER_BY_ID ); ?>"
							data-placeholder="<?php echo esc_attr( __( 'Search for a user', 'wpcf' ) ); ?>">
						</select>
					</div>
				</li>
			</ul>
		</script>
		<script type="text/html" id="tmpl-toolset-shortcode-attribute-typesViewsTermSelector">
			<ul id="{{{data.shortcode}}}-{{{data.attribute}}}">
				<li class="toolset-shortcode-gui-item-selector-option">
					<label for="toolset-shortcode-gui-item-selector-term-id-viewloop">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-term-id-viewloop"
							name="toolset_shortcode_gui_object_id" value="viewloop" checked="checked" />
						<?php _e( 'The current term in the loop', 'wpcf' ); ?>
					</label>
				</li>

				<li class="toolset-shortcode-gui-item-selector-option toolset-shortcode-gui-item-selector-has-related js-toolset-shortcode-gui-item-selector-has-related">
					<label for="toolset-shortcode-gui-item-selector-term-id">
						<input type="radio" class="js-toolset-shortcode-gui-item-selector"
							id="toolset-shortcode-gui-item-selector-term-id" name="toolset_shortcode_gui_object_id"
							value="object_id" />
						<?php _e( 'A specific term', 'wpcf' ); ?>
					</label>
					<div
						class="toolset-advanced-setting toolset-shortcode-gui-item-selector-is-related js-toolset-shortcode-gui-item-selector-is-related"
						style="display:none;padding-top:10px;">
						<select id="toolset-shortcode-gui-item-selector-term-id-object_id"
							class="js-toolset-shortcode-gui-item-selector_object_id js-toolset-shortcode-gui-field-ajax-select2"
							name="specific_object_id"
							data-action="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_TERMS ) ); ?>"
							data-prefill="<?php echo esc_attr( $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_GET_TERM_BY_ID ) ); ?>"
							data-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_TERMS ); ?>"
							data-prefill-nonce="<?php echo wp_create_nonce( Toolset_Ajax::CALLBACK_GET_TERM_BY_ID ); ?>"
							data-placeholder="<?php echo esc_attr( __( 'Search for a term', 'wpcf' ) ); ?>">
						</select>
					</div>
				</li>
			</ul>
		</script>
		<?php
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_POST_REFERENCE_FIELD_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_POST_REFERENCE_FIELD_WIZARD_FIRST_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_POST_REFERENCE_FIELD_WIZARD_SECOND_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_POST_REFERENCE_FIELD_WIZARD_THIRD_TEMPLATE ),
			null
		);

		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_REPEATING_FIELDS_GROUP_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_REPEATING_FIELDS_GROUP_WIZARD_FIRST_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_REPEATING_FIELDS_GROUP_WIZARD_SECOND_TEMPLATE ),
			null
		);
		$renderer->render(
			$template_repository->get( Types_Output_Template_Repository::INSERT_REPEATING_FIELDS_GROUP_WIZARD_THIRD_TEMPLATE ),
			null
		);
	}

	/*
	 * ====================================
	 * Compatibility
	 * ====================================
	 */

	/**
	 * Gravity Forms compatibility.
	 *
	 * GF removes all assets from its admin pages, and offers a series of hooks to add your own to its whitelist.
	 * Those two callbacks are hooked to these filters.
	 *
	 * @param array $required_objects
	 *
	 * @return array
	 * @since m2m
	 */
	public function gform_noconflict_scripts( $required_objects ) {
		$required_objects[] = 'types-shortcode';

		return $required_objects;
	}


	public function gform_noconflict_styles( $required_objects ) {
		$required_objects[] = Toolset_Assets_Manager::STYLE_TOOLSET_SHORTCODE;
		$required_objects[] = Toolset_Assets_Manager::STYLE_SELECT2_CSS;
		$required_objects[] = Toolset_Assets_Manager::STYLE_NOTIFICATIONS;
		$required_objects[] = 'onthego-admin-styles';

		return $required_objects;
	}

}
