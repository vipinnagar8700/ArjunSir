<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\PotentialAssociationQuery;

class O2MPostQuery extends PostQuery {

	public function get_results( $check_can_connect_another_element = true, $check_distinct_relationships = true ) {
		return parent::get_results( $check_can_connect_another_element, false );
	}

}
