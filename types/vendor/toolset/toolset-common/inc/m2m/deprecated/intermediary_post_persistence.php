<?php

/**
 * Handles the persistence of intermediary posts.
 *
 * @since m2m
 * @deprecated Use \OTGS\Toolset\Common\Relationships\API\Factory::low_level_gateway() and then
 *     the intermediary_post_persistence() to instantiate this class (or DatabaseLayerFactory if within the
 *     \OTGS\Toolset\Common\Relationships\DatabaseLayer\ namespace.
 */
class Toolset_Association_Intermediary_Post_Persistence
	implements \OTGS\Toolset\Common\Relationships\API\IntermediaryPostPersistence {


	private $_actual_instance;


	/**
	 * Toolset_Association_Intermediary_Post_Persistence constructor.
	 *
	 * @param IToolset_Relationship_Definition|null $relationship_definition
	 * @param \OTGS\Toolset\Common\WPML\WpmlService|null $wpml_service_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory|null $database_layer_factory
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(
		IToolset_Relationship_Definition $relationship_definition = null,
		\OTGS\Toolset\Common\WPML\WpmlService $wpml_service_di = null,
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory = null
	) {
		$factory = new \OTGS\Toolset\Common\Relationships\API\Factory();
		$this->_actual_instance = $factory
			->low_level_gateway()
			->intermediary_post_persistence( $relationship_definition);
	}

	const DEFAULT_LIMIT = 50;

	public function create_intermediary_post( $parent_id, $child_id ) {
		return call_user_func_array( [ $this->_actual_instance, 'create_intermediary_post' ], func_get_args() );
	}


	private function get_default_intermediary_post_title( $parent_id, $child_id ) {
		return call_user_func_array( [ $this->_actual_instance, 'get_default_intermediary_post_title' ], func_get_args() );
	}


	public function create_empty_associations_intermediary_posts( $limit = 0 ) {
		return call_user_func_array( [ $this->_actual_instance, 'create_empty_associations_intermediary_posts' ], func_get_args() );
	}


	public function remove_associations_intermediary_posts( $limit = 0 ) {
		return call_user_func_array( [ $this->_actual_instance, 'remove_associations_intermediary_posts' ], func_get_args() );
	}


	public function create_empty_association_intermediary_post( $association ) {
		return call_user_func_array( [ $this->_actual_instance, 'create_empty_association_intermediary_post' ], func_get_args() );
	}


	public function maybe_delete_intermediary_post( IToolset_Association $association ) {
		return call_user_func_array( [ $this->_actual_instance, 'maybe_delete_intermediary_post' ], func_get_args() );
	}


	public function delete_intermediary_post( $post_id ) {
		return call_user_func_array( [ $this->_actual_instance, 'delete_intermediary_post' ], func_get_args() );
	}


	private function delete_post_translations( $post_id ) {
		return call_user_func_array( [ $this->_actual_instance, 'delete_post_translations' ], func_get_args() );
	}

}
