<?php

use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\ResponseAssociationConflict;
use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\ResponseAssociationDelete;
use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\ResponseAssociationMissingData;
use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\ResponseAssociationSave;
use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\ResponseHandler;
use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\Result;
use OTGS\Toolset\Types\Model\Post\Intermediary\Request;
use OTGS\Toolset\Types\User\Access;

/**
 * @since 3.0
 */
class Types_Ajax_Handler_Intermediary_Parent_Child extends Types_Ajax_Handler_Post_Reference_Field {


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()
			->ajax_begin(
				array(
					'nonce' => $this->get_ajax_manager()
						->get_action_js_name( Types_Ajax::CALLBACK_INTERMEDIARY_PARENT_CHILD ),
					'capability_needed' => 'edit_posts',
				)
			);

		// Read and validate input
		$action = sanitize_text_field( toolset_getpost( 'intermediary_action' ) );

		$this->route( $action );
	}


	/**
	 * Route ajax calls
	 *
	 * @param string $action
	 */
	protected function route( $action ) {
		switch ( $action ) {
			case 'json_intermediary_parent_child_posts':
				$this->json_posts();
				return;
			case 'json_save_association':
				$this->json_save_association();
				return;
		}
	}


	/**
	 * Types_Ajax_Handler_Post_Reference_Field::json_posts()
	 * Just added here for better class overview.
	 */
	protected function json_posts() {
		$post_type = sanitize_text_field( toolset_getpost( 'post_type' ) );
		$search = sanitize_text_field( toolset_getpost( 'search' ) );
		$page = sanitize_text_field( toolset_getpost( 'page' ) );
		$post_id = sanitize_text_field( toolset_getpost( 'post_id' ) );
		$posts_per_page = Types_Field_Type_Post_View_Backend_Display::SELECT2_POSTS_PER_LOAD;

		global $wpdb;

		$prepare_values = array( $post_type );

		// SEARCH
		$search_where = " AND p.post_status IN ('publish', 'draft') ";

		// user access
		$user = wp_get_current_user();
		$user_access = new Access( $user );
		$user_can_edit_any = $user_access->canEditAny( $post_type );
		$user_can_edit_own = $user_access->canEditOwn( $post_type );

		if ( ! $user_can_edit_any && ! $user_can_edit_own ) {
			wp_send_json(
				array(
					'items' => array(),
					'total_count' => 0,
					'incomplete_results' => false,
					'posts_per_page' => $posts_per_page,
				)
			);
		}

		if ( ! $user_can_edit_any ) {
			// We already know $user_can_edit_own === true.
			$search_where .= ' AND p.post_author = ' . $user->ID . ' ';
		}

		if ( $post_id ) {
			// don't display current post in list of assignable posts
			$search_where .= ' AND p.ID != %d ';
			$prepare_values[] = $post_id;
		}

		if ( $search !== '' ) {
			if ( method_exists( $wpdb, 'esc_like' ) ) {
				$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			} else {
				/** @noinspection PhpDeprecationInspection */
				$search_term = '%' . like_escape( esc_sql( $search ) ) . '%';
			}
			$search_where .= ' AND p.post_title LIKE %s ';
			$prepare_values[] = $search_term;
			$orderby = ' ORDER BY p.post_title ';
		} else {
			$orderby = ' ORDER BY p.post_date DESC ';
		}

		// PAGE
		if ( preg_match( '/^\d+$/', $page ) ) {
			$prepare_values[] = ( (int) $page - 1 ) * $posts_per_page;
		} else {
			$prepare_values[] = 0;
		}

		$prepare_values[] = $posts_per_page;

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS
					p.ID as id,
					p.post_title as text,
					p.post_type as type,
					p.post_status as status
				FROM $wpdb->posts p
				WHERE p.post_type IN ('%s')
				$search_where
				$orderby
				LIMIT %d, %d",
				$prepare_values
			)
		);

		$posts_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		foreach ( $posts as $post ) {
			$post->url = get_permalink( $post->id );
		}

		wp_send_json(
			array(
				'items' => $posts,
				'total_count' => $posts_count,
				'incomplete_results' => $posts_count > $posts_per_page,
				'posts_per_page' => $posts_per_page,
			)
		);
	}


	/**
	 *
	 */
	private function json_save_association() {
		try {
			// get user data
			$intermediary_id = sanitize_text_field( toolset_getpost( 'intermediary_id' ) );
			$parent_id = sanitize_text_field( toolset_getpost( 'parent_id' ) );
			$child_id = sanitize_text_field( toolset_getpost( 'child_id' ) );

			// response handler
			$response_handler = new ResponseHandler();
			$response_handler->addResponse(
				new ResponseAssociationDelete(
					$this->relationships_factory
				)
			);
			$response_handler->addResponse(
				new ResponseAssociationMissingData()
			);
			$response_handler->addResponse(
				new ResponseAssociationConflict()
			);
			$response_handler->addResponse(
				new ResponseAssociationSave(
					$this->relationships_factory
				)
			);

			$request = new Request(
				new Toolset_Element_Factory(),
				Toolset_Post_Type_Repository::get_instance(),
				$this->relationships_factory,
				new Toolset_Relationship_Role_Parent(),
				new Toolset_Relationship_Role_Child(),
				new Toolset_Relationship_Role_Intermediary()
			);

			$request->setIntermediaryId( $intermediary_id );
			$request->setParentId( $parent_id );
			$request->setChildId( $child_id );

			$result = new Result();

			wp_send_json( $response_handler->response( $request, $result ) );

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				/** @noinspection ForgottenDebugOutputInspection */
				error_log( $e->getMessage() );
			}

			wp_send_json( new Result(
				'', Result::RESULT_SYSTEM_ERROR
			) );
		}
	}
}
