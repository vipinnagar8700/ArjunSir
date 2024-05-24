<?php

/**
 * Editor class for the Fusion Builder (Avada).
 *
 * Handles all the functionality needed to allow the Fusion Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */
class Toolset_User_Editors_Editor_Avada
	extends Toolset_User_Editors_Editor_Abstract {

	const AVADA_SCREEN_ID = 'avada';
	const FUSION_BUILDER_OPTION_NAME = 'fusion_builder_status';
	const FUSION_BUILDER_OPTION_VALUE = 'active';

	/**
	 * @var string
	 */
	protected $id = self::AVADA_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'Avada Builder';

	/**
	 * @var string
	 */
	protected $option_name = '_toolset_user_editors_avada_template';

	/**
	 * @var string
	 */
	protected $logo_class = 'dashicons-fusiona-logo';

	public function initialize() {
		if ( apply_filters( 'wpv_filter_is_native_editor_for_cts', false ) ) {
			// register medium slug
			add_filter( 'fusion_builder_default_post_types', array( $this, 'support_medium' ) );
			add_filter( 'fusion_builder_allowed_post_types', array( $this, 'support_medium' ) );
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

		add_action( 'edit_form_after_editor', array( $this, 'register_assets_for_avada_compatibility' ) );
		add_filter( 'the_content', array( $this, 'maybe_re_calculate_fusion_builder_columns' ), 2 );
	}

	/**
	 * Add support for managing extra postmeta keys when setting this user editor.
	 *
	 * @since Views 3.1
	 */
	public function set_extra_postmeta_support() {
		parent::set_extra_postmeta_support();

		add_action( 'toolset_update_fusion_builder_post_meta', array( $this, 'update_extra_post_meta_from_request' ), 10, 2 );
	}

	/**
	 * Set the extra postmeta for this given user editor.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function update_extra_post_meta( $post_id ) {
		update_post_meta( $post_id, self::FUSION_BUILDER_OPTION_NAME, self::FUSION_BUILDER_OPTION_VALUE );
	}

	/**
	 * Delete the extra postmeta for this given user editor.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function delete_extra_post_meta( $post_id ) {
		delete_post_meta( $post_id, self::FUSION_BUILDER_OPTION_NAME );
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
			$this->name = __( 'Avada Builder', 'wpv-view' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-avada-script' ) );
	}

	public function register_assets_for_avada_compatibility() {
		// The enqueueing of the style for Fusion Builder was moved outside the "CT editing" condition to also support
		// compatibility to the native post/page editor when Fusion Builder is used there too.
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-avada-editor-style' ) );
	}

	/**
	 * We need to register the slug of our Medium in Fusion Builder.
	 *
	 * @wp-filter fusion_builder_default_post_types
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
	 * When the widths and the margins of the Fusion Builder columns are calculated, this happens too early. For the case
	 * of a post/page that has a Content Template assigned, at that moment the Content Template content hasn't replace the
	 * content of the "the_content" yet. Thus on a later time, when the Content Template content has replaced the content
	 * of the page, we need to re-calculate the Fusion Builder columns.
	 *
	 * @param  string $content The content of the page coming from "the_content" hook.
	 *
	 * @return string The content with the re-calculated Fusion Builder Columns.
	 *
	 * @since 3.0.7
	 */
	public function maybe_re_calculate_fusion_builder_columns( $content ) {
		$post_id = get_the_ID();

		if (
			class_exists( 'FusionBuilder' ) &&
			is_callable( array( 'FusionBuilder', 'get_instance' ) ) &&
			is_callable( array( 'FusionBuilder', 'fusion_calculate_columns' ) ) &&
			$this->is_post_using_fusion_built_ct( $post_id )
		) {
			$content = FusionBuilder::get_instance()->fusion_calculate_columns( $content );
		}

		return $content;
	}

	/**
	 * Returns true if the post/page with ID equals to $post_id is built using Fusion Builder.
	 *
	 * @param int   $post_id The ID of the post to check for Fusion Builder built Content Template.
	 *
	 * @return bool True if the post to check has a Content Template assigned that is built using Fusion Builder.
	 *
	 * @since 3.0.7
	 */
	public function is_post_using_fusion_built_ct( $post_id ) {
		$ct_id = apply_filters( 'wpv_content_template_for_post', 0, get_post( $post_id ) );
		if (
			$ct_id &&
			self::FUSION_BUILDER_OPTION_VALUE === get_post_meta( $ct_id, self::FUSION_BUILDER_OPTION_NAME, true )
		) {
			return true;
		}

		return false;
	}
}
