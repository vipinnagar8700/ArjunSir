<?php

use OTGS\Toolset\Types\API\Modifier\PotentialAssociationQueryFilters\Factory;
use OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride;
use OTGS\Toolset\Types\User\AccessFactory;


/**
 * Retrieve options for the post reference field select2 input.
 *
 * @since 3.0
 */
class Types_Ajax_Handler_Post_Reference_Field extends Toolset_Ajax_Handler_Abstract {

	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	protected $relationships_factory;

	/** @var Factory */
	private $filter_factory;

	/** @var Toolset_Element_Factory */
	private $element_factory;

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var AccessFactory */
	private $user_access_factory;

	/** @var WpmlTridAutodraftOverride */
	private $wpml_trid_autodraft_override;


	/**
	 * Types_Ajax_Handler_Post_Reference_Field constructor.
	 *
	 * @param Types_Ajax $ajax_manager
	 * @param Factory $filter_factory
	 * @param Toolset_Element_Factory $element_factory
	 * @param Toolset_Relationship_Definition_Repository $definition_repository
	 * @param AccessFactory $user_access_factory
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	 * @param WpmlTridAutodraftOverride $wpml_trid_autodraft_override
	 */
	public function __construct(
		Types_Ajax $ajax_manager,
		Factory $filter_factory,
		Toolset_Element_Factory $element_factory,
		Toolset_Relationship_Definition_Repository $definition_repository,
		AccessFactory $user_access_factory,
		\OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory,
		WpmlTridAutodraftOverride $wpml_trid_autodraft_override
	) {
		parent::__construct( $ajax_manager );
		$this->filter_factory = $filter_factory;
		$this->element_factory = $element_factory;
		$this->relationship_definition_repository = $definition_repository;
		$this->user_access_factory = $user_access_factory;
		$this->relationships_factory = $relationships_factory;
		$this->wpml_trid_autodraft_override = $wpml_trid_autodraft_override;
	}


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
						->get_action_js_name( Types_Ajax::CALLBACK_POST_REFERENCE_FIELD ),
					'capability_needed' => 'edit_posts',
					'is_public' => toolset_getarr( $_REQUEST, 'skip_capability_check', false ),
				)
			);

		// Read and validate input
		$action = sanitize_text_field( toolset_getpost( 'post_reference_field_action' ) );

		$this->wpml_trid_autodraft_override->initialize(
			(int) toolset_getpost( 'parent_post_id' ),
			(int) toolset_getnest( $_POST, [ 'parent_post_translation_override', 'trid' ] ),
			esc_attr( toolset_getnest( $_POST, [ 'parent_post_translation_override', 'lang_code' ] ) )
		);

		// route action
		$this->route( $action );
	}


	/**
	 * Route ajax calls
	 *
	 * @param string $action
	 */
	protected function route( $action ) {
		if ( $action === 'json_post_reference_field_posts' ) {
			$this->json_posts();
		}
	}


	/**
	 * Function to get posts by search term
	 *
	 * This function exits the script (ajax response).
	 *
	 * @print json
	 */
	protected function json_posts() {
		$for_post_id = (int) toolset_getpost( 'post_id' );
		$post_type = sanitize_text_field( toolset_getpost( 'post_type' ) );
		$relationship_slug = sanitize_text_field( toolset_getpost( 'relationship_slug' ) );
		$search_string = sanitize_text_field( toolset_getpost( 'search' ) );
		$result_page = (int) toolset_getpost( 'page', 1 );
		$posts_per_page = Types_Field_Type_Post_View_Backend_Display::SELECT2_POSTS_PER_LOAD;

		$user = wp_get_current_user();
		$user_access = $this->user_access_factory->create( $user );
		$user_can_edit_any = $user_access->canEditAny( $post_type );
		$user_can_edit_own = $user_access->canEditOwn( $post_type );

		if ( ! $user_can_edit_any && ! $user_can_edit_own ) {
			$this->send_empty_result();

			return;
		}

		$relationship = $this->relationship_definition_repository->get_definition( $relationship_slug );
		if ( null === $relationship ) {
			$this->send_empty_result();

			return;
		}

		$query_args = array(
			'page' => $result_page,
			'items_per_page' => $posts_per_page,
			'count_results' => true,
			'wp_query_override' => $this->get_additional_wp_query_args( $relationship, $post_type ),
		);

		if ( ! $user_can_edit_any ) {
			// We already know that $user_can_edit_own.
			$query_args['wp_query_override']['author'] = $user->ID;
		}

		if ( '' !== $search_string ) {
			$query_args['search_string'] = $search_string;
		}

		try {
			$for_element = $this->element_factory->get_post( $for_post_id );
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			$this->send_empty_result();

			return;
		}

		$query = $this->relationships_factory->potential_association_query(
			$relationship, new Toolset_Relationship_Role_Parent(), $for_element, $query_args
		);
		// Either we're setting a new parent or overwriting an existing one.
		// Disabling checks for distinctiveness and possibility to check another element. Alternatively, we could
		// add the current element to the 'exclude_elements' argument, but that would mean another query which we can
		// avoid in this case.
		$posts = $query->get_results( false, false );

		$results = array_map(
			static function ( IToolset_Post $post ) {
				return array(
					'id' => $post->get_id(),
					'text' => $post->get_title(),
					'type' => $post->get_type(),
					'status' => $post->get_status(),
					'url' => get_permalink( $post->get_id() ),
				);
			}, $posts
		);

		$posts_count = $query->get_found_elements();

		wp_send_json(
			array(
				'items' => $results,
				'total_count' => $posts_count,
				'incomplete_results' => $posts_count > $posts_per_page,
				'posts_per_page' => $posts_per_page,
			)
		);
	}


	/**
	 * Get additional query filters by applying a set of filters.
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $post_type
	 *
	 * @return array
	 */
	private function get_additional_wp_query_args( IToolset_Relationship_Definition $relationship_definition, $post_type ) {
		$query_arguments = $this->filter_factory->argument_builder();

		$query_arguments->addFilter(
			$this->filter_factory->author_for_post_reference( $relationship_definition->get_slug(), $post_type )
		);

		$query_arguments->addFilter(
			$this->filter_factory->post_status_for_post_reference( $relationship_definition, new Toolset_Relationship_Role_Parent() )
		);

		$additional_query_arguments = $query_arguments->get();

		return toolset_ensarr( toolset_getarr( $additional_query_arguments, 'wp_query_override' ) );
	}


	/**
	 * Respond to the client with an empty result.
	 */
	private function send_empty_result() {
		wp_send_json(
			array(
				'items' => array(),
				'total_count' => 0,
				'incomplete_results' => false,
				'posts_per_page' => Types_Field_Type_Post_View_Backend_Display::SELECT2_POSTS_PER_LOAD,
			)
		);
	}

}
