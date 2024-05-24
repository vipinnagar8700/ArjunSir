<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Relationship_Role;

/**
 * Interface OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\IToolset_Association_Query_Result_Transformation
 *
 * Object that performs a transformation of a single database row from the
 * association query into a the desired result.
 *
 * @since 2.5.8
 */
interface IToolset_Association_Query_Result_Transformation {


	/**
	 * @param object $database_row It is safe to expect only properties that are always
	 *     preset in results of a query from OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Sql_Expression_Builder.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return mixed
	 */
	public function transform( $database_row, IToolset_Association_Query_Element_Selector $element_selector );


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return void
	 * @since 2.5.10
	 */
	public function request_element_selection( IToolset_Association_Query_Element_Selector $element_selector );


	/**
	 * Determine what roles *may* need to be included in the results.
	 *
	 * That means, if a role is not returned by this method, it will definitely *not* be needed during the result
	 * transformation. It doesn't work the opposite way, though.
	 *
	 * @return IToolset_Relationship_Role[]
	 */
	public function get_maximum_requested_roles();

}
