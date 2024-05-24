<?php

/**
 * Utility methods used for TinyMCE issues.
 *
 * @since 2.3
 */
class Types_Helper_TinyMCE {

	/**
	 * It stores the mceInit json data for tinymce editor init
	 *
	 * @var array
	 * @since m2m
	 */
	private $mceinit = array();


	/**
	 * Parse settings using _WP_Editors
	 *
	 * @param String $id ID of the editor instance.
	 *
	 * @return array
	 */
	private function parse_settings( $id ) {
		return _WP_Editors::parse_settings( $id, [] );
	}


	/**
	 * Editor settings using _WP_Editors
	 *
	 * @param String $id ID of the editor instance.
	 * @param array $settings Array of editor arguments.
	 */
	private function editor_settings( $id, $settings = array() ) {
		_WP_Editors::editor_settings( $id, $settings );
	}


	/**
	 * Generates mceInit data for tinymce initialization
	 *
	 * It takes the html rendered inputs ans search for the textarea IDs. Why do we need that?
	 * Because wp_editor is rendered with a different ID because the same editor can be displayed
	 * several times in the same page.
	 *
	 * @param Toolset_Field_Instance[] $fields An array of fields.
	 * @param String $html Rendered inputs.
	 *
	 * @return array A list of editor configuration.
	 * @since m2m
	 */
	public function generate_mceinit_data( $fields, $html ) {
		// _WP_Editors doesn't have a public method to use the mceInit data, so a filter is needed. Not the best approach.
		add_filter( 'tiny_mce_before_init', array( $this, 'get_mceinit_data' ), 10, 2 );
		$fields_included = array();
		foreach ( $fields as $field ) {
			if ( 'wysiwyg' === $field->get_field_type()->get_slug() ) {
				$slug = $field->get_definition()->get_slug();
				if ( ! in_array( $slug, $fields_included, true ) ) {
					// Find each textarea instance inside the rendered html.
					preg_match_all( '#' . $slug . '_\d{5}#', $html, $ids );
					foreach ( $ids[0] as $id ) {
						if ( ! in_array( $id, $this->mceinit, true ) ) {
							$settings = $this->parse_settings( $id );
							$settings['textarea_name'] = $slug;
							$this->editor_settings( $id, $settings );
						}
					}
					$fields_included[] = $slug;
				}
			}
		}
		remove_filter( 'tiny_mce_before_init', array( $this, 'get_mceinit_data' ), 10 );

		return $this->mceinit;
	}


	/**
	 * Gets the mceinit data from a filter
	 *
	 * @param array $mceinit mceInit data.
	 * @param string $id editor ID.
	 *
	 * @return array
	 * @since m2m
	 */
	public function get_mceinit_data( $mceinit, $id ) {
		if ( ! preg_match( '#^wpcf-#', $id ) ) {
			$id = 'wpcf-' . $id;
		}

		if ( ! in_array( $id, $this->mceinit, true ) ) {
			$this->mceinit[ $id ] = $mceinit;
			$this->mceinit[ $id ]['formats'] = $this->parse_json( $this->mceinit[ $id ]['formats'] );
			$this->mceinit[ $id ]['wp_shortcut_labels'] = $this->parse_json( $this->mceinit[ $id ]['wp_shortcut_labels'] );
			$this->mceinit[ $id ]['selector'] = '#' . $id;
		}

		return $mceinit;
	}


	/**
	 * Adds quotes and parse to JSON
	 *
	 * @param String $text Json encode.
	 *
	 * @return Object
	 * @since m2m
	 */
	private function parse_json( $text ) {
		return json_decode(
			preg_replace( '#(\w+)\:#', '"$1":', $text ),
			false
		);
	}


	/** Editor ID used in the hack of get_default_editor_settings(). */
	const FAUX_EDITOR_ID = 'toolset_faux_editor_id';

	/** @var array|null Cache for get_default_editor_settings(). */
	private $editor_settings;

	/** @var bool */
	private $did_print_dynamic_tinymce_l10n = false;


	/**
	 * Retrieve the default settings for the TinyMCE editor.
	 *
	 * Use a hack to invoke the tiny_mce_before_init filter, where these settings are passed as an argument
	 * and can be catched.
	 *
	 * Needs to be called after wp_editor(), otherwise unexpected stuff will happen.
	 *
	 * Note that the output is cached within a single server request.
	 *
	 * @return array
	 */
	private function get_default_editor_settings() {
		if ( null === $this->editor_settings ) {

			// Prepare for extracting the settings from the filter callback.
			$settings = null;
			$expected_editor_id = self::FAUX_EDITOR_ID;
			$extract_settings = static function ( $mce_init, $editor_id ) use ( &$settings, $expected_editor_id ) {
				if ( $editor_id === $expected_editor_id ) {
					$settings = $mce_init;
				}

				return $mce_init;
			};

			add_filter( 'tiny_mce_before_init', $extract_settings, 10, 2 );

			// _WP_Editors::editor_settings() expects an array of settings with certain defaults.
			$default_editor_settings = _WP_Editors::parse_settings( self::FAUX_EDITOR_ID, array() );
			_WP_Editors::editor_settings( self::FAUX_EDITOR_ID, $default_editor_settings );

			remove_filter( 'tiny_mce_before_init', $extract_settings, 10 );

			$this->editor_settings = $settings;
		}

		return $this->editor_settings;
	}


	/**
	 * Print the localization data for the dynamically initiated TinyMCE editor in WYSIWYG fields.
	 *
	 * One of the problems with such TinyMCE instances (initiated via `wp.editor.initialize` in JS)
	 * is that they by default contain only a minimal set of quicktags.
	 *
	 * This sequence of hooks and callbacks that you can find below extracts the default editor settings in PHP,
	 * which also includes the list of quicktags for TinyMCE toolbars. Then we pass this information to JS,
	 * where it is going to be used in Toolset.Types.Compatibility.TinyMCE.InitWysiwyg.
	 *
	 * This method should be called during admin_enqueue_scripts, after enqueuing the script
	 * Types_Asset_Manager::SCRIPT_TINYMCE_COMPATIBILITY (or any script that has it as a dependency).
	 *
	 * Keep in mind that only the JavaScript initialization is dynamic: wp_enqueue_editor() still
	 * needs to be called and the HTML output for the WYSIWYG field still must be rendered via wp_editor().
	 *
	 * @since 3.3
	 */
	public function localize_dynamic_tinymce_init_script() {
		if ( $this->did_print_dynamic_tinymce_l10n ) {
			return;
		}

		// This will make sure we'll try localizing the script only after wp_editor() has been called.
		add_filter( 'the_editor_content', array( $this, 'the_editor_content_callback_for_dynamic_tinymce' ) );
	}


	/**
	 * Filter callback. Never use directly.
	 *
	 * @param string $content
	 *
	 * @return string
	 * @see localize_dynamic_tinymce_init_script
	 *
	 * @since 3.3
	 */
	public function the_editor_content_callback_for_dynamic_tinymce( $content ) {
		if ( ! $this->did_print_dynamic_tinymce_l10n ) {
			// Manually printing the script in the footer because at this point, we're way too late for
			// wp_localize_script();
			add_action( 'admin_print_footer_scripts', array( $this, 'print_footer_script_for_dynamic_tinymce' ) );
		}

		return $content;
	}


	/**
	 * Action callback. Never use directly.
	 *
	 * @see localize_dynamic_tinymce_init_script
	 * @since 3.3
	 */
	public function print_footer_script_for_dynamic_tinymce() {
		if ( $this->did_print_dynamic_tinymce_l10n ) {
			return;
		}

		/** @noinspection JSUnusedLocalSymbols */
		/** @noinspection ES6ConvertVarToLetConst */
		$script = "<script type='text/javascript'>"
			. 'var types_tinymce_compatibility_l10n = '
			. wp_json_encode( array( 'editor_settings' => $this->get_toolbar_settings_for_dynamic_tinymce() ) )
			. '</script>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $script;

		$this->did_print_dynamic_tinymce_l10n = true;
	}


	/**
	 * Directly extract the toolbar settings and return the value.
	 *
	 * All pre-requirements must be fulfilled if calling this directly (e.g. after rendering a RFG item).
	 *
	 * @return array
	 * @since 3.3.1
	 */
	public function get_toolbar_settings_for_dynamic_tinymce() {
		$editor_settings = $this->get_default_editor_settings();
		$toolbars = array(
			'toolbar1' => toolset_getarr( $editor_settings, 'toolbar1' ),
			'toolbar2' => toolset_getarr( $editor_settings, 'toolbar2' ),
			'toolbar3' => toolset_getarr( $editor_settings, 'toolbar3' ),
			'toolbar4' => toolset_getarr( $editor_settings, 'toolbar4' ),
		);

		// Filter out quicktags which are not supposed to be available for WYSIWYG fields.
		$forbidden_quicktags = array( 'fullscreen' );
		foreach ( $toolbars as $toolbar => $quicktags ) {
			$quicktag_array = explode( ',', $quicktags );
			$quicktag_array = array_diff( $quicktag_array, $forbidden_quicktags );

			$toolbars[ $toolbar ] = implode( ',', $quicktag_array );
		}

		return $toolbars;
	}
}
