<?php

namespace OTGS\Toolset\Types\Ajax\Handler\MergeRelationships;

use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\ExceptionWithMessage;
use Toolset_Relationship_Definition_Repository;
use Toolset_Relationship_Query_Factory;

/**
 * Command for the relationship merge batch process, with shared functionality.
 *
 * @package OTGS\Toolset\Types\Ajax\Handler\MergeRelationships
 * @since 3.0.4
 */
class AbstractCommand {

	/** @var Toolset_Relationship_Query_Factory */
	protected $query_factory;

	/** @var Toolset_Relationship_Definition_Repository */
	protected $relationship_definition_repository;


	public function __construct(
		Toolset_Relationship_Query_Factory $query_factory,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository
	) {
		$this->query_factory = $query_factory;
		$this->relationship_definition_repository = $relationship_definition_repository;
	}


	/**
	 * From the option array coming from the Merge Relationships dialog, extract information about the old
	 * relationship definitions (to be merged into a new one).
	 *
	 * Throw an ExceptionWithMessage if a relationship doesn't exist.
	 *
	 * @param array $options
	 *
	 * @return IToolset_Relationship_Definition[]
	 * @throws ExceptionWithMessage
	 */
	protected function get_old_relationships( $options ) {
		// Initial validation that we do for all phases.
		$relationship_left = $this->relationship_definition_repository->get_definition( toolset_getarr( $options, 'relationshipLeft' ) );
		$relationship_right = $this->relationship_definition_repository->get_definition( toolset_getarr( $options, 'relationshipRight' ) );

		if ( null === $relationship_left || null === $relationship_right ) {
			throw new ExceptionWithMessage( __( 'Source relationship definition doesn\'t exist.', 'wpcf' ) );
		}

		return array( $relationship_left, $relationship_right );
	}

}
