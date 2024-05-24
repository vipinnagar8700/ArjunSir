<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\RelationshipQuery;

use IToolset_Relationship_Definition;

/**
 * Holds a result of a relationship query and the number of found rows (if available).
 *
 * @since Types 3.4.7
 */
class CachedQueryResult {

	/** @var IToolset_Relationship_Definition[] */
	private $results;


	/** @var int|null */
	private $found_rows;


	/**
	 * CachedQueryResult constructor.
	 *
	 * @param IToolset_Relationship_Definition[] $results
	 * @param int|null $found_rows
	 */
	public function __construct( $results, $found_rows = null ) {
		$this->results = $results;
		$this->found_rows = $found_rows;
	}

	/**
	 * @return IToolset_Relationship_Definition[]
	 */
	public function get_results() {
		return $this->results;
	}


	/**
	 * @return int|null
	 */
	public function get_found_rows() {
		return $this->found_rows;
	}



}
