<?php

/**
 * Notice to guide the user to create Custom Fields after he creates a CPT
 * This should only be loaded on hook "load-toolset_page_wpcf-edit-type"
 * @since 3.0
 */
class Types_Admin_Notices_Custom_Fields_For_New_Cpt  {

	public function __construct() {
		// for the case it wasn't called on the "load-toolset_page_wpcf-edit-type"
		add_action( 'load-toolset_page_wpcf-edit-type', array( $this, 'show' ), 1000 );
	}

	/**
	 * Show the notice
	 */
	public function show() {
		if( ! isset( $_GET['wpcf-post-type'] ) || $_GET['wpcf-post-type'] === 'attachment' ) {
			return;
		}

		$field_groups = Toolset_Field_Group_Post_Factory::get_instance()->get_groups_by_post_type( $_GET['wpcf-post-type'] );

		if( ! empty( $field_groups ) ) {
			// there are field groups assigned to the post type
			return;
		}

		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		if( ! $post_type = $post_type_repository->get( $_GET['wpcf-post-type'] ) ) {
			// no post type found
			return;
		}

		if( $post_type->has_special_purpose() ) {
			// don't show message for intermediary or rfgs
			return;
		}

		$messsage = sprintf(
			__( 'To add custom fields and taxonomy to this type, please go to the %sToolset Dashboard%s.' ),
			'<a href="' . admin_url( 'admin.php?page=toolset-dashboard') . '">',
			'</a>'
		);

		$notice = new Toolset_Admin_Notice_Dismissible(
			'no-custom-fields-' . $_GET['wpcf-post-type'],
			$messsage
		);

		$notice->set_template_toolset_robot();

		Toolset_Admin_Notices_Manager::add_notice( $notice );

	}
}
