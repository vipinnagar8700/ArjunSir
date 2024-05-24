<?php

/**
 * Interface for all potential association query filters.
 *
 * @since m2m
 * TODO create a properly namespaced alias for this
 */
interface Toolset_Potential_Association_Query_Filter_Interface {

	/**
	 * Main method to modiy the query arguments.
	 *
	 * @param array $query_arguments
	 *
	 * @since m2m
	 */
	public function filter( array $query_arguments );

}
