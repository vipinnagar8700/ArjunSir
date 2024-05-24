<?php

/**
 * "Field Group Edit" page controller.
 * IMPORTANT: currently only used for >Repeatable Field Groups<
 *
 * @since m2m
 */
final class Types_Page_Field_Group_Edit {

	/**
	 * Types_Page_Field_Group_Edit constructor.
	 */
	public function __construct() {
		$this->prepare();
	}

	/**
	 * Set required hooks
	 */
	public function prepare() {
		$tcb = Toolset_Common_Bootstrap::get_instance();
		$tcb->register_gui_base();
		$tgb = Toolset_Gui_Base::get_instance();
		$tgb->init();

		add_action( 'current_screen', array( $this, 'prepare_dialogs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'legacy_add_js_info' ) );
	}

	/**
	 * Dialogs which are required for Post Reference Field and Repeatable Field Group.
	 */
	public function prepare_dialogs() {
		$twig = new Types_Helper_Twig();

		// dialog warning before deleting repeatable group
		$twig->prepare_dialog(
			'types-dialog-delete-repeatable-field-group',
			'/field/group/repeatable/backend/dialog-delete.twig'
		);

		// dialog save before rfg can be deleted
		$twig->prepare_dialog(
			'types-dialog-save-before-rfg-can-be-deleted',
			'/field/group/repeatable/backend/dialog-save-before-rfg-can-be-deleted.twig'
		);

		// dialog no group condition change if repeatable group or post reference field is active
		$twig->prepare_dialog(
			'types-dialog-condition-change-impossible',
			'/field/group/repeatable/backend/dialog-no-condition-change-possible.twig'
		);

		// dialog for deleting post reference field
		$twig->prepare_dialog(
			'types-dialog-post-reference-field-delete',
			'/field/group/dialog-post-reference-field-delete.twig'
		);

		// dialog for deleting post reference field
		$twig->prepare_dialog(
			'types-dialog-post-reference-field-make-repeatable',
			'/field/group/dialog-post-reference-field-make-repeatable.twig',
			array()
		);

		// dialog save before making prf repeatable
		$twig->prepare_dialog(
			'types-dialog-post-reference-field-save-required',
			'/field/group/dialog-post-reference-field-save-required.twig'
		);

		// dialog for displaying error message
		$twig->prepare_dialog(
			'types-dialog-field-group-error',
			'/field/group/dialog-error.twig'
		);

		// dialog about wrong translation mode for prf post type
		$twig->prepare_dialog(
			'types-dialog-prf-type-wrong-translation-mode',
			'/field/group/dialog-prf-type-wrong-translation-mode.twig'
		);

		// dialog that field group is not savable
		$twig->prepare_dialog(
			'types-dialog-saving-group-impossible',
			'/field/group/repeatable/backend/dialog-saving-group-impossible.twig'
		);

		// dialog no group condition change if repeatable group or post reference field is active
		$twig->prepare_dialog(
			'types-dialog-delete-field-group',
			'/field/group/dialog-delete-field-group.twig'
		);

		// dialog warning before moving a repeatable group to another parent (as all items will be deleted)
		$twig->prepare_dialog(
			'types-dialog-move-repeatable-field-group-to-another-parent',
			'/field/group/repeatable/backend/dialog-move-to-another-parent.twig'
		);
	}

	/**
	 * Enqueue all assets needed by the page.
	 *
	 * (Notice the dependencies on Toolset GUI base assets.)
	 */
	public function on_admin_enqueue_scripts() {
		$handle = 'types-field-group-edit-page';

		// Load Toolset GUI base
		Types_Asset_Manager::get_instance();

		// repeatable field group
		wp_enqueue_style(
			$handle,
			TYPES_RELPATH . '/public/page/field/group/field-group-edit.css',
			array(
				Toolset_Gui_Base::STYLE_GUI_BASE
			),
			TYPES_VERSION
		);

		// repeatable field group
		wp_enqueue_script(
			$handle,
			TYPES_RELPATH . '/public/page/field/group/field-group-edit.js',
			array(
				'jquery',
				'underscore',
				Types_Asset_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_UTILS,
				Toolset_Gui_Base::SCRIPT_GUI_ABSTRACT_PAGE_CONTROLLER,
				Toolset_Gui_Base::SCRIPT_GUI_JQUERY_COLLAPSIBLE
			),
			TYPES_VERSION,
			true
		);

		// repeatable field group
		wp_enqueue_style( 'toolset-types' );
	}

	/**
	 * The page is still rendered by using legacy code, we still need to pass data to js
	 */
	public function legacy_add_js_info() {
		echo '<script id="toolset_model_data" type="text/plain" >'
		     . base64_encode( wp_json_encode( $this->build_js_data() ) )
		     . '</script>';
	}

	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 */
	private function build_js_data() {
		$ajax_controller = Types_Ajax::get_instance();
		$action_name     = $ajax_controller->get_action_js_name( Types_Ajax::CALLBACK_FIELD_GROUP_EDIT_ACTION );

		$group_id = isset( $_REQUEST['group_id'] )
			? (int) $_REQUEST['group_id']
			: 0;

		$data = array(
			// 'templates' => $this->build_templates(),
			'strings'       => $this->build_strings_for_js(),
			'ajaxInfo'      => array(
				'fieldGroupEditAction' => array(
					'name'  => $action_name,
					'nonce' => wp_create_nonce( $action_name ),
					'fieldGroupId' => $group_id
				),
			),
			'extra' => [
				'isRequired' => false,
			],
		);

		if ( $group_id ) {
			$field_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
			$field_group = $field_group_factory->load( $group_id );
			if ( $field_group ) {
				$data['extra']['isRequired'] = $field_group->is_required();
			}
		}

		return $data;
	}


	/**
	 * Prepare an array of strings used in JavaScript.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_strings_for_js() {
		return array(
			'fieldIsRequired' => __( 'This field is required.', 'wpcf' ),
			'deleteRepeatableGroup' => __( 'Delete Repeatable Group', 'wpcf' ),
			'conditionChangeNotAllowed' => __( 'Conditions change not allowed', 'wpcf' ),
			'postReferenceNotAllowedInRFG' => __( 'Post Reference Field can not be placed into a Repeatable Group.', 'wpcf' ),
			'postReferenceFieldOnlyAllowedWithOneAssignedPostType' => __( 'Post Reference field is only available for field groups, which are assigned to a single post type.', 'wpcf' ),
			'postReferenceHasAssociations' => __( 'The Post Reference Field has associations.', 'wpcf' ),
			'postReferenceTypeNotSupportedTranslationMode' => __( 'Not supported translation mode', 'wpcf' ),
			'fieldGroupError' => __( 'Field Group Error', 'wpcf' ),
			'fieldConverted' => __( 'Field converted', 'wpcf' ),
			'makeFieldRepeatable' => __( 'Make field repeatable', 'wpcf' ),
			'savingGroupImpossible' => __( 'Saving Field Group Failed', 'wpcf' ),
			'button' => array(
				'apply' => __( 'Apply', 'wpcf' ),
				'cancel' => __( 'Cancel', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
				'close' => __( 'Close', 'wpcf' ),
				'make_this_change' => __( 'Make this change', 'wpcf' ),
				'prf_delete_associations' => __( 'Delete Associations', 'wpcf' ),
				'prf_keep_associations' => __( 'Keep Associations', 'wpcf' ),
				'convertAndDelete' => __( 'Convert & Delete', 'wpcf' ),
				'move' => __( 'Move', 'wpcf' ),
			),
			'deleteFieldGroupWarning' => array(
				'singular' => __( 'If you delete the Field Group you will also delete the Relationship created in the existing Repeatable Field Group.<br /><br />Do you want to transform the Repeatable Field Group listed below into one-to-many Relationship?', 'wpcf' ),
				'plural' => __( 'If you delete the Field Group you will also delete the Relationships created in the existing Repeatable Field Groups.<br /><br />Do you want to transform the Repeatable Field Groups listed below into one-to-many Relationships?', 'wpcf' ),
			),
			'deleteFieldGroupTitle' => __( 'Delete Field Group', 'wpcf' ),
			'moveRepeatableGroup' => __( 'Move Repeatable Group', 'wpcf' ),
			'unsavedChanges' => __( 'You have unsaved changes.', 'wpcf' ),
		);
	}
}
