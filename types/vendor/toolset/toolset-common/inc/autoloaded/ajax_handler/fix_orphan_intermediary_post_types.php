<?php

/**
 * Troubleshooting section callback.
 *
 * Turn orphaned intermediary posts (that have no relationship referring to them) into regular post types.
 *
 * @since Types 3.3.11
 */
class Toolset_Ajax_Handler_Fix_Orphan_Intermediary_Post_Types extends Toolset_Ajax_Handler_Abstract {


	/** @var Toolset_Post_Type_Query_Factory */
	private $post_type_query_factory;


	/** @var Toolset_Relationship_Query_Factory */
	private $relationship_query_factory;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;


	/**
	 * Toolset_Ajax_Handler_Fix_Orphan_Intermediary_Post_Types constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Toolset_Post_Type_Query_Factory $post_type_query_factory
	 * @param Toolset_Relationship_Query_Factory $relationship_query_factory
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		Toolset_Post_Type_Query_Factory $post_type_query_factory,
		Toolset_Relationship_Query_Factory $relationship_query_factory,
		Toolset_Post_Type_Repository $post_type_repository
	) {
		parent::__construct( $ajax_manager );

		$this->post_type_query_factory = $post_type_query_factory;
		$this->relationship_query_factory = $relationship_query_factory;
		$this->post_type_repository = $post_type_repository;
	}


	/**
	 * @inheritDoc
	 */
	function process_call( $arguments ) {
		$this->ajax_begin( [ 'nonce' => Toolset_Ajax::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES ] );

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$this->ajax_finish( [
				'continue' => false,
				'message' => __( 'Relationship functionality is not enabled, there is nothing to do.', 'wpv-views' ),
			], true );

			return;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$results = new Toolset_Result_Set();

		$intermediary_post_types = $this->post_type_query_factory->create( [ 'is_intermediary' => true ] )
			->get_results();

		foreach ( $intermediary_post_types as $intermediary_post_type ) {
			if ( ! $intermediary_post_type instanceof Toolset_Post_Type_From_Types ) {
				// Toolset_Post_Type_From_Types has the unset_as_intermediary() method, we're helpless without it.
				// translators: %s: Post type slug.
				$results->add( false, sprintf(
					__( 'The post type "%s" has been detected as an intermediary orphan but it is not being managed by Types. Skipping...', 'wpv-views' ),
					$intermediary_post_type->get_slug()
				) );
				continue;
			}

			$query = $this->relationship_query_factory->relationships_v2();
			$relationships = $query
				->add( $query->intermediary_type( $intermediary_post_type->get_slug() ) )
				->get_results();

			if ( empty( $relationships ) ) {
				// translators: %s: Post type slug.
				$results->add( true, sprintf(
					__( 'Fixing an orphaned intermediary post type "%s"...', 'wpv-views' ),
					$intermediary_post_type->get_slug()
				) );
				$intermediary_post_type->unset_as_intermediary();

				$was_saved = $this->post_type_repository->save( $intermediary_post_type );
				if ( ! $was_saved ) {
					$results->add( false, __( 'Error when updating the post type definition.', 'wpv-views' ) );
				}
			} elseif ( count( $relationships ) > 1 ) {
				// translators: %s: Post type slug.
				$results->add( false, sprintf(
					__( 'The intermediary post type "%s" seems to belong to multiple relationships. We cannot fix it here, please contact our support.', 'wpv-views' ),
					$intermediary_post_type->get_slug()
				) );
			} else {
				/** @var IToolset_Relationship_Definition $relationship */
				$relationship = array_pop( $relationships );
				// translators: 1: Post type slug. 2: Relationship slug.
				$results->add( true, sprintf(
					__( 'Intermediary post type "%1$s" is fine, it belongs to the relationship "%2$s".' ),
					$intermediary_post_type->get_slug(),
					$relationship->get_slug()
				) );
			}
		}

		$results->add( true, __( 'Operation completed.', 'wpv-views' ) );

		$this->ajax_finish( [
			'continue' => false,
			'message' => $results->concat_messages( "\n" ),
		], true );
	}
}
