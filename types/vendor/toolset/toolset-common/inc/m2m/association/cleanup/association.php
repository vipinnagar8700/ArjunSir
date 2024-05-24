<?php

/**
 * Removes a single association from the database and cleans up after.
 *
 * That means also deleting the intermediary post, if it exists.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Association {

	/** @var Toolset_Association_Intermediary_Post_Persistence|null */
	private $_intermediary_post_persistence;


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * Toolset_Association_Cleanup_Association constructor.
	 *
	 * @param Toolset_Association_Intermediary_Post_Persistence|null $intermediary_post_persistence_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory
	 */
	public function __construct(
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory = null,
		Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence_di = null
	) {
		$this->_intermediary_post_persistence = $intermediary_post_persistence_di;
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * @return Toolset_Association_Intermediary_Post_Persistence
	 */
	private function get_intermediary_post_persistence() {
		if( null === $this->_intermediary_post_persistence ) {
			$this->_intermediary_post_persistence = new Toolset_Association_Intermediary_Post_Persistence();
		}
		return $this->_intermediary_post_persistence;
	}



	/**
	 * Permanently delete the provided association.
	 *
	 * @param IToolset_Association $association Association to delete. Do not use the instance
	 *     after passing it to this method.
	 *
	 * @return Toolset_Result
	 */
	public function delete( IToolset_Association $association ) {
		$this->get_intermediary_post_persistence()->maybe_delete_intermediary_post( $association );

		$result = $this->database_layer_factory->association_database_operations()->delete_association(
			$association
		);

		/**
		 * toolset_association_deleted
		 *
		 * Announce that an association no longer exists.
		 * Important, used for cache flushing.
		 *
		 * @since Types 3.1.3
		 */
		do_action( 'toolset_association_deleted', $association->get_uid() );

		return $result;
	}

}
