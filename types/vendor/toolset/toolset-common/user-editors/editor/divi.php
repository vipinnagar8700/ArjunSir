<?php
/**
 * Editor class for the Divi Builder.
 *
 * Handles all the functionality needed to allow the Divi Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Divi
	extends Toolset_User_Editors_Editor_Abstract {

	const DIVI_SCREEN_ID = 'divi';
	const DIVI_BUILDER_OPTION_NAME = '_et_pb_use_builder';
	const DIVI_BUILDER_OPTION_VALUE = 'on';

	/**
	 * @var string
	 */
	protected $id = self::DIVI_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'Divi Builder';

	/**
	 * @var string
	 */
	protected $option_name = '_toolset_user_editors_divi_template';

	/**
	 * @var string
	 */
	protected $logo_class = 'toolset-divi-logo-for-ct-button';

	public function initialize() {
		if ( apply_filters( 'wpv_filter_is_native_editor_for_cts', false ) ) {
			// register medium slug
			add_filter( 'et_builder_post_types', array( $this, 'support_medium' ) );
			add_filter( 'et_builder_module_post_types', array( $this, 'support_medium' ) );
		}

		$this->set_extra_postmeta_support();
	}

	/**
	 * Initialize logic after checking that the user editor is available.
	 *
	 * @since 3.4.8
	 */
	public function after_editor_added() {
		if ( apply_filters( 'wpv_filter_is_native_editor_for_cts', false ) ) {
			add_action( 'edit_form_after_editor', array( $this, 'register_assets_for_backend_editor' ) );
		}

		add_action( 'wp_loaded', array( $this, 'add_filter_for_divi_modules_for_cts' ) );

		if ( $this->tc_bootstrap->get_request_mode() === $this->constants->constant( 'Toolset_Common_Bootstrap::MODE_FRONTEND' ) ) {
			add_filter( 'get_post_metadata', array( $this, 'maybe_post_uses_divi_built_ct' ), 10, 4 );
		}
	}

	public function add_filter_for_divi_modules_for_cts() {
		add_filter( 'et_builder_module_post_types', array( $this, 'support_medium' ) );
	}

	/**
	 * Add support for managing extra postmeta keys when setting this user editor.
	 *
	 * @since Views 3.1
	 */
	public function set_extra_postmeta_support() {
		parent::set_extra_postmeta_support();

		add_action( 'toolset_update_divi_builder_post_meta', array( $this, 'update_extra_post_meta_from_request' ), 10, 2 );
	}

	/**
	 * Set the extra postmeta for this given user editor.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function update_extra_post_meta( $post_id ) {
		update_post_meta( $post_id, self::DIVI_BUILDER_OPTION_NAME, self::DIVI_BUILDER_OPTION_VALUE );
	}

	/**
	 * Delete the extra postmeta for this given user editor.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function delete_extra_post_meta( $post_id ) {
		delete_post_meta( $post_id, self::DIVI_BUILDER_OPTION_NAME );
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if (
			defined( 'ET_BUILDER_THEME' )
			|| defined( 'ET_BUILDER_PLUGIN_VERSION' )
		) {
			$this->name = __( 'Divi Builder', 'wpv-views' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-divi-script' ) );
	}

	/**
	 * We need to register the slug of our Medium in Divi Builder.
	 *
	 * @wp-filter et_builder_post_types
	 * @param $allowed_types
	 * @return array
	 */
	public function support_medium( $allowed_types ) {
		if ( ! in_array( 'view-template', $allowed_types ) ) {
			$allowed_types[] = 'view-template';
		}

		return $allowed_types;
	}

	/**
	 * Hijack the "get_post_meta( $post_id, '_et_pb_use_builder', true )" call that checks if the post with ID equals to
	 * $post_id is built with Divi builder. The hijacking relates to checking on posts/pages that use content templates
	 * built with Divi. In this case, the post will be identified as one that uses Divi builder.
	 *
	 * @param  string $meta_value The value of the meta.
	 * @param  int    $post_id    The current post ID.
	 * @param  string $meta_key   The key of the meta.
	 * @param  bool   $single     Whether to return a single value.
	 * @return mixed
	 *
	 * @since 3.0.1 Narrow down the cases where this hijacking is applied as it is basically needed only on the frontend.
	 *              The problem was that when the hijacking also happened in the backend, Divi builder was force-used for
	 *              post/pages that don't actually use the builder but were assigned to a Content Template that is built
	 *              with it.
	 */
	public function maybe_post_uses_divi_built_ct( $meta_value, $post_id, $meta_key, $single ) {
		if ( $meta_key === $this->constants->constant( 'Toolset_User_Editors_Editor_Divi::DIVI_BUILDER_OPTION_NAME' ) ) {
			$ct_id = apply_filters( 'wpv_content_template_for_post', 0, get_post( $post_id ) );
			if ( $ct_id ) {
				$meta_value = get_post_meta( $ct_id, self::DIVI_BUILDER_OPTION_NAME, true );
			}
		}

		return $meta_value;
	}
}
