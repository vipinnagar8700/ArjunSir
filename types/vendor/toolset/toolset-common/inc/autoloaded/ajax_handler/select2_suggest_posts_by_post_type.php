<?php

/**
 * Handles toolset_select2 instances that suggest post by title.
 *
 * If a given post type is not provided, it will return no results.
 * valueType - If a return value among [ 'ID', 'post_name' ] is not provided, it will return result built upon post ID.
 * orderBy - Can order by 'date', 'title', 'ID'.
 * order - Can order as 'DESC' or 'ASC'.
 * author - Can filter by an author ID, or return no results if passed as zero.
 *
 * @since m2m
 */
class Toolset_Ajax_Handler_Select2_Suggest_Posts_By_Post_Type extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Transform callback sorting arguments into database tables names.
	 *
	 * @var array
	 */
	private $orderby_table_match = array(
		'date' => 'post_date',
		'title' => 'post_title',
	);


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( [
			'nonce' => Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE,
			'is_public' => true,
		] );

		$post_type = toolset_getpost( 'postType' );

		if ( empty( $post_type ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing query.', 'wpv-views' ) ), false );

			return;
		}

		$return_field = toolset_getpost( 'valueType', 'ID', [ 'ID', 'post_name' ] );

		$orderby = toolset_getpost( 'orderBy', 'ID', array( 'date', 'title', 'ID' ) );
		$orderby = toolset_getarr( $this->orderby_table_match, $orderby, $orderby );

		$this->maybe_set_current_language();

		$posts = $this->query_posts(
			$post_type,
			toolset_getpost( 'author', null ),
			$orderby,
			toolset_getpost( 'order', 'DESC', array( 'ASC', 'DESC' ) ),
			0,
			15
		);

		$results = array_map( function ( \WP_Post $post ) use ( $return_field ) {
			return [
				'id' => $post->$return_field,
				'text' => $post->post_title,
			];
		}, $posts );


		$this->ajax_finish( $results, true );
	}


	private function query_posts( $post_type, $author_id, $order_by, $order, $offset, $limit ) {
		$query_args = [
			'ignore_sticky_posts' => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'no_found_rows' => true,
			'post_type' => $post_type,
			'orderby' => $order_by,
			'order' => $order,
			'post_status' => 'publish',
			'offset' => $offset,
			'posts_per_page' => $limit,
		];

		if ( ! empty( $author_id ) ) {
			$query_args['author'] = $author_id;
		}

		$query = new \WP_Query( $query_args );

		return $query->posts;
	}


	/**
	 * If WPML is active, we will tell it what is the current language - it cannot determine
	 * it on its own in an AJAX call.
	 *
	 * @since 4.0
	 */
	private function maybe_set_current_language() {
		$lang_code = toolset_getpost( 'current_language', '' );
		if ( ! empty( $lang_code ) ) {
			do_action( 'wpml_switch_language', $lang_code );
		}
	}
}
