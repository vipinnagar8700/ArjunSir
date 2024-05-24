<?php

/**
 * Perform the cleanup after migrating between relationship database layer versions.
 *
 * @since 4.0
 */
class Toolset_Ajax_Handler_Cleanup_After_Relationship_Migration extends Toolset_Ajax_Handler_Abstract {


	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	private $relationship_api_factory;


	/**
	 * Toolset_Ajax_Handler_Cleanup_After_Relationship_Migration constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory $relationship_api_factory
	 */
	public function __construct( \Toolset_Ajax $ajax_manager, \OTGS\Toolset\Common\Relationships\API\Factory $relationship_api_factory ) {
		parent::__construct( $ajax_manager );
		$this->relationship_api_factory = $relationship_api_factory;
	}


	/**
	 * @inheritDoc
	 */
	function process_call( $arguments ) {
		$this->ajax_begin( [ 'nonce' => \Toolset_Ajax::CALLBACK_CLEANUP_AFTER_RELATIONSHIP_MIGRATION ] );

		$results = new \OTGS\Toolset\Common\Result\ResultSet();

		try {
			$results->add(
				$this->relationship_api_factory->low_level_gateway()->do_after_migration_cleanup()
			);
		} catch ( Exception $e ) {
			$results->add( $e );
		}

		$this->ajax_finish( [
			'continue' => false,
			'message' => $results->get_message(),
		] );
	}
}
