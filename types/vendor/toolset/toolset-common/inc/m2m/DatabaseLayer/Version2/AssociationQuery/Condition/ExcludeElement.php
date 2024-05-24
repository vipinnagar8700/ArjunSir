<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

/**
 * Condition to exclude a particular element from the results.
 *
 * See the parent class for details.
 */
class ExcludeElement extends ElementIdAndDomain {

	protected function get_operator() {
		return '!=';
	}

}
