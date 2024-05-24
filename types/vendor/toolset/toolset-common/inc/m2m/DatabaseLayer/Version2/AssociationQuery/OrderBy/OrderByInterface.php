<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

/**
 * Classes implementing this interface define ordering of results in an association query.
 *
 * @since 4.0
 */
interface OrderByInterface {

	/**
	 * Set the order direction.
	 *
	 * @param string $order Constants::ORDER_ASC or ORDER_DESC.
	 * @return void
	 */
	public function set_order( $order );


	/**
	 * Build the ORDER BY clause (not including the "ORDER BY" keyword).
	 *
	 * @return string
	 */
	public function get_orderby_clause();


	/**
	 * If the class uses a join manager, request all needed joins now.
	 *
	 * @return void
	 */
	public function register_joins();


}
