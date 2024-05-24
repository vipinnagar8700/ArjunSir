<?php

/**
 * Class Types_Field_Type_Post_View_Backend_Creation
 *
 * @since 2.3
 */
class Types_Field_Type_Post_View_Backend_Display {

	const SELECT2_POSTS_PER_LOAD = 10;

	/** @var \OTGS\Toolset\Common\WPML\WpmlService */
	private $wpml_service;

	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	private $relationships_factory;


	/**
	 * Types_Field_Type_Post_View_Backend_Display constructor.
	 *
	 * @param \OTGS\Toolset\Common\WPML\WpmlService $wpml_service
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	 */
	public function __construct(
		\OTGS\Toolset\Common\WPML\WpmlService $wpml_service,
		\OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	) {
		$this->wpml_service = $wpml_service;
		$this->relationships_factory = $relationships_factory;
	}

	/**
	 *
	 */
	public function prepare() {
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_js_data' ) );

		// Fix for GUTENBERG, which has already triggered 'admin_enqueue_scripts' add this point
		// It's a known issue see: https://github.com/WordPress/gutenberg/issues/4929
		if( did_action( 'admin_enqueue_scripts' ) ) {
			$this->on_admin_enqueue_scripts();
		}

		// also admin_print_scripts is already triggered by GUTENBERG out of order
		if( did_action( 'admin_print_scripts' ) ) {
			add_action( 'admin_footer', array( $this, 'print_js_data' ) );
		}
	}

	/**
	 * Scritps and Styles
	 */
	public function on_admin_enqueue_scripts() {
		if ( function_exists( 'wpcf_edit_post_screen_scripts' ) ) {
			wpcf_edit_post_screen_scripts();
		}

		WPToolset_Field_File::file_enqueue_scripts();

		wp_enqueue_script(
			'types-post-reference-field',
			TYPES_RELPATH . '/public/page/edit_post/post-reference-field.js',
			array(
				'jquery',
				'underscore',
				Types_Asset_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_UTILS,
				Types_Asset_Manager::SCRIPT_POINTER
			),
			TYPES_VERSION
		);
	}

	/**
	 * Print JS
	 */
	public function print_js_data() {
		echo '<script id="types_post_reference_model_data" type="text/plain">' . base64_encode( wp_json_encode( $this->build_js_data() ) ) . '</script>';
	}

	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 */
	private function build_js_data() {
		$types_settings_action = Types_Ajax::get_instance()->get_action_js_name( Types_Ajax::CALLBACK_POST_REFERENCE_FIELD );

		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : false;

		if( ! $post_id ) {
			// this happens on new post, but the post id is already resevered and stored in global $post_ID
			global $post_ID;
			$post_id = $post_ID ?: 0;
		}

		return array(
			'post_id' => $post_id,
			'action'  => array(
				'name'  => $types_settings_action,
				'nonce' => wp_create_nonce( $types_settings_action ),
			),
			'select2' => array(
				'posts_per_load' => self::SELECT2_POSTS_PER_LOAD
			)
		);
	}

	/**
	 * This renders the container for the repeatable group.
	 *
	 * The items of the repeatable group will be loaded via ajax, this way we not slowing down
	 * the initial load of the post edit screen.
	 *
	 * @param Types_Field_Type_Post $field
	 *
	 * @param string $additional_css_classes - used only for legacy conditions
	 *
	 * @return string
	 *
	 * @noinspection PhpUnusedParameterInspection Used inside the template file.
	 * @noinspection PhpUnusedLocalVariableInspection Ditto.
	 */
	public function render( Types_Field_Type_Post $field, $additional_css_classes = '' ) {
		ob_start();

		$is_wpml_active = $this->wpml_service->is_wpml_active_and_configured();
		$is_default_language = $this->wpml_service->is_current_language_default();
		$disabled =
			$this->relationships_factory->database_operations()->requires_default_language_post()
			&& $is_wpml_active
			&& ! $is_default_language
			&& ! $this->has_default_language_translation();

		include( TYPES_ABSPATH . '/application/views/field/post-reference/container.phtml' );

		return ob_get_clean();
	}


	/**
	 * Returns if the post is translated in the default language
	 *
	 * @return boolean
	 * @since m2m
	 */
	private function has_default_language_translation() {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : 0;
		if ( ! $post_id ) {
			return false;
		}

		return $this->wpml_service->has_default_language_translation( $post_id );
	}
}
