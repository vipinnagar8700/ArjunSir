<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

/**
 * Condition for the Toolset_Association_Query_V2.
 *
 * Provides a wpdb instance to all its subclasses.
 *
 * @since 2.5.8
 */
abstract class Toolset_Association_Query_Condition implements \OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition {


	/**
	 * By default, there is nothing to join.
	 *
	 * @return string
	 */
	public function get_join_clause() {
		return '';
	}

}
