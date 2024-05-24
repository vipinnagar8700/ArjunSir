<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

/**
 * Condition for the association query.
 */
abstract class AbstractCondition implements \OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition {


	/**
	 * By default, there is nothing to join.
	 *
	 * @return string
	 */
	public function get_join_clause() {
		return '';
	}

}
