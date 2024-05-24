<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use Toolset_Utils;

/**
 * Condition to query associations by a specific association ID.
 *
 * @since 4.0
 */
class AssociationId extends AbstractCondition {


	/** @var int */
	private $association_id;


	/**
	 * @param int $association_id
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $association_id ) {
		if ( ! Toolset_Utils::is_natural_numeric( $association_id ) ) {
			throw new InvalidArgumentException( 'Invalid association ID.' );
		}

		$this->association_id = (int) $association_id;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( ' %1$s.id = %2$d ', TableJoinManager::ALIAS_ASSOCIATIONS, $this->association_id );
	}
}
