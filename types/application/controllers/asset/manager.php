<?php

/**
 * The script and style asset manager for Types implemented in a standard Toolset way.
 *
 * Keeping this separate from Types_Assets also for performance reasons (this is not needed at all times).
 *
 * @since 2.0
 * @refactoring because inheriting from Toolset_Assets_Manager causes double asset registration. We should _use_
 * that class, not extend it.
 */
class Types_Asset_Manager extends Toolset_Assets_Manager {

	// Script handles
	//
	// NEVER EVER use handles defined here as hardcoded strings, they may change at any time.

	const SCRIPT_ADJUST_MENU_LINK = 'types-adjust-menu-link';
	const SCRIPT_SLUG_CONFLICT_CHECKER = 'types-slug-conflict-checker';
	const SCRIPT_POINTER = 'types-pointer';
	const SCRIPT_PAGE_EDIT_POST_TYPE = 'types-page-edit-post-type';
	const SCRIPT_PAGE_EDIT_TAXONOMY = 'types-page-edit-taxonomy';
	const SCRIPT_M2M_ACTIVATION_DIALOG = 'types-dialog-m2m-activation';
	const SCRIPT_SUBMIT_ANYWAY = 'types-submit-anyway';
	const SCRIPT_POST_ADD_OR_EDIT = 'types-post-add-or-edit';
	const SCRIPT_POST_ADD_OR_EDIT_NO_COMPONENTS = 'types-post-add-or-edit-no-components';

	/**
	 * TinyMCE compatibility layer for WYSIWYG fields.
	 *
	 * Note: Also requires wp_enqueue_editor() to be called in order to work properly,
	 * and Types_Helper_TinyMCE::localize_dynamic_tinymce_init_script().
	 */
	const SCRIPT_TINYMCE_COMPATIBILITY = 'types-tinymce-compatibility';

	const STYLE_POST_ADD_OR_EDIT = 'types-post-add-or-edit';

	// Registered in legacy Types

	const SCRIPT_JQUERY_UI_VALIDATION = 'wpcf-form-validation';
	const SCRIPT_ADDITIONAL_VALIDATION_RULES = 'wpcf-form-validation-additional';

	const STYLE_BASIC_CSS = 'wpcf-css-embedded';


	/** @var Types_Asset_Manager */
	private static $types_instance;


	/**
	 * @return Types_Asset_Manager
	 */
	public static function get_instance() {
		if ( null === self::$types_instance ) {
			self::$types_instance = new self();
		}

		return self::$types_instance;
	}


	protected function initialize_styles() {

		$this->register_style(
			self::STYLE_BASIC_CSS,
			WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css',
			array(),
			TYPES_VERSION
		);

		$this->register_style(
			self::STYLE_POST_ADD_OR_EDIT,
			TYPES_RELPATH . '/public/css/post/bundle.add_or_edit.css',
			array(),
			TYPES_VERSION
		);
	}


	protected function initialize_scripts() {

		$this->register_script(
			self::SCRIPT_ADJUST_MENU_LINK,
			TYPES_RELPATH . '/public/page/adjust_submenu_links.js',
			array( 'jquery', 'underscore' ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_SLUG_CONFLICT_CHECKER,
			TYPES_RELPATH . '/public/js/slug_conflict_checker.js',
			array( 'jquery', 'underscore' ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_POINTER,
			TYPES_RELPATH . '/public/js/pointer.js',
			array( 'jquery', 'wp-pointer' )
		);

		$this->register_script(
			self::SCRIPT_PAGE_EDIT_POST_TYPE,
			TYPES_RELPATH . '/public/page/edit_post_type/main.js',
			array( 'jquery', 'underscore', self::SCRIPT_SLUG_CONFLICT_CHECKER, self::SCRIPT_UTILS ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_PAGE_EDIT_TAXONOMY,
			TYPES_RELPATH . '/public/page/edit_taxonomy/main.js',
			array( 'jquery', 'underscore', self::SCRIPT_SLUG_CONFLICT_CHECKER, self::SCRIPT_UTILS ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_JQUERY_UI_VALIDATION,
			TYPES_RELPATH . '/public/lib/jquery-form-validation/' . $this->choose_script_version( 'jquery.validate.min.js', 'jquery.validate.js'),
			array( 'jquery' ),
			'1.8.1'
		);


		$this->register_script(
			self::SCRIPT_ADDITIONAL_VALIDATION_RULES,
			$this->get_additional_validation_script_url(),
			array( 'jquery', self::SCRIPT_JQUERY_UI_VALIDATION ),
			TYPES_VERSION
		);


		$this->register_script(
			self::SCRIPT_M2M_ACTIVATION_DIALOG,
			TYPES_RELPATH . '/public/page/extension/m2m-migration-dialog.js',
			array( 'jquery', 'underscore', Toolset_Assets_Manager::SCRIPT_HEADJS, self::SCRIPT_KNOCKOUT, self::SCRIPT_UTILS ),
			TYPES_VERSION
		);


		$this->register_script(
			self::SCRIPT_SUBMIT_ANYWAY,
			TYPES_RELPATH . '/public/js/submitanyway.js',
			array( 'jquery' ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_POST_ADD_OR_EDIT,
			TYPES_RELPATH . '/public/js/post/bundle.add_or_edit.js',
			array(
				'wp-components',
				Toolset_Assets_Manager::SCRIPT_UTILS,
				'react-dom',
			),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_POST_ADD_OR_EDIT_NO_COMPONENTS,
			TYPES_RELPATH . '/public/js/post/bundle.add_or_edit_no_components.js',
			array( self::SCRIPT_SUBMIT_ANYWAY ),
			TYPES_VERSION
		);

		$this->register_script(
			self::SCRIPT_TINYMCE_COMPATIBILITY,
			TYPES_RELPATH . '/public/js/compatibility/bundle.tinymce.js',
			array( 'jquery' ),
			TYPES_VERSION
		);

		return parent::initialize_scripts();
	}


	/**
	 * Unfortunately, we need to have this public because of the Divi.
	 * And, unfortunately, we can't define it as a constant because PHP < 5.6 doesn't support that.
	 *
	 * @since 2.2.7
	 */
	public function get_additional_validation_script_url() {
		return TYPES_RELPATH . '/public/lib/jquery-form-validation/additional-methods.min.js';
	}


	/**
	 * Choose a production (usually minified) or debugging (non-minified) version of
	 * a script depending on the script debugging mode.
	 *
	 * See SCRIPT_DEBUG constant
	 *
	 * @param string $production_version File name of the production script version.
	 * @param string $debugging_version File name of the debugging script version.
	 *
	 * @return string
	 * @since 2.2.7
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function choose_script_version( $production_version, $debugging_version ) {
		$is_debug_mode = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
		return ( $is_debug_mode ? $debugging_version : $production_version );
	}


	/**
	 * @param Toolset_Script $script
	 */
	public function register_toolset_script( $script ) {
		if ( ! isset( $this->scripts[ $script->handle ] ) ) {
			$this->scripts[ $script->handle ] = $script;
		}
	}


	/**
	 * @param Toolset_Style $style
	 */
	public function register_toolset_style( $style ) {
		if( !isset( $this->styles[ $style->handle ] ) ) {
			$this->styles[ $style->handle ] = $style;
		}
	}

}
