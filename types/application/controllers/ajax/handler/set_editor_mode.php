<?php

use OTGS\Toolset\Common\PostType\EditorMode;

/**
 * A simple AJAX call handler to change the "editor mode" setting of a post type from the Toolset Dashboard.
 *
 * @since 3.2.2
 */
class Types_Ajax_Handler_Set_Editor_Mode extends Toolset_Ajax_Handler_Abstract {


	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/**
	 * Types_Ajax_Handler_Set_Editor_Mode constructor.
	 *
	 * @param Types_Ajax $ajax_manager
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 */
	public function __construct( Types_Ajax $ajax_manager, Toolset_Post_Type_Repository $post_type_repository ) {
		parent::__construct( $ajax_manager );

		$this->post_type_repository = $post_type_repository;
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin( array(
			'nonce' => $this->get_ajax_manager()->get_action_js_name( Types_Ajax::CALLBACK_SET_EDITOR_MODE ),
		) );

		$post_type_slug = toolset_getpost( 'post_type' );
		$post_type = $this->post_type_repository->get( $post_type_slug );
		if ( null === $post_type ) {
			$this->ajax_finish( array( 'message' => 'Unknown post type.' ), false );

			return;
		}

		$editor_mode = toolset_getpost( 'editor_mode' );
		if ( ! EditorMode::is_valid( $editor_mode ) ) {
			$this->ajax_finish( array( 'message' => 'Invalid editor mode.' ), false );

			return;
		}

		if ( ! $post_type instanceof IToolset_Post_Type_From_Types ) {
			$this->ajax_finish( array(
				'message' => 'The post type is not being managed by Types, its editor mode setting cannot be adjusted.',
			), false );

			return;
		}

		// Got all we need.
		$post_type->set_editor_mode( $editor_mode );
		$this->post_type_repository->save( $post_type );

		$this->ajax_finish( array(), true );
	}

}
