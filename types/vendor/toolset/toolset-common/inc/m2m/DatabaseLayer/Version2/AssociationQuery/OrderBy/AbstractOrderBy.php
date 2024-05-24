<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Shared functionality for OrderbyInterface implementations.
 */
abstract class AbstractOrderBy implements OrderByInterface {


	/** @var string */
	protected $order = 'ASC';


	/** @var TableJoinManager */
	protected $join_manager;


	/**
	 * @param TableJoinManager $join_manager
	 */
	public function __construct( TableJoinManager $join_manager ) {
		$this->join_manager = $join_manager;
	}


	/**
	 * Set the direction of sorting.
	 *
	 * @param string $order 'ASC'|'DESC'
	 *
	 * @throws InvalidArgumentException
	 */
	public function set_order( $order ) {
		$normalized_value = strtoupper( $order );
		if ( ! in_array( $normalized_value, array( 'ASC', 'DESC' ), true ) ) {
			throw new InvalidArgumentException( 'Invalid order value.' );
		}

		$this->order = $normalized_value;
	}


	/**
	 * @inheritdoc
	 */
	abstract public function register_joins();

}
