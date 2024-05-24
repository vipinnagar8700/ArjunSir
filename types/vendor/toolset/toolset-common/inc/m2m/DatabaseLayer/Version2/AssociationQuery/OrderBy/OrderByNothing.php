<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

/**
 * Don't order associations by anything.
 */
class OrderByNothing implements OrderByInterface {

	public function get_orderby_clause() {
		return '';
	}


	public function set_order( $order ) {
		// Nothing to do here.
	}


	public function register_joins() {
		// Nothing to do here.
	}
}
