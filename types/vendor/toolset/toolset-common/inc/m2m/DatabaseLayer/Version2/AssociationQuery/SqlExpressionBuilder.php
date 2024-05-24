<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery;

use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy\OrderByInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ResultTransformation\ResultTransformationInterface;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Builds the MySQL expression for the association query.
 */
class SqlExpressionBuilder {


	/** @var TableNames */
	private $table_names;


	/** @var TableJoinManager */
	private $join_manager;


	/**
	 * @param TableNames $table_names
	 */
	public function __construct(
		TableNames $table_names
	) {
		$this->table_names = $table_names;
	}


	public function setup( TableJoinManager $join_manager ) {
		$this->join_manager = $join_manager;
	}


	/**
	 * Build a complete MySQL query from the conditions.
	 *
	 * @param AssociationQueryCondition $root_condition
	 * @param int $offset
	 * @param int $limit
	 * @param OrderByInterface $orderby
	 * @param ElementSelectorInterface $element_selector
	 * @param bool $need_found_rows
	 * @param ResultTransformationInterface $result_transformation
	 *
	 * @return string
	 */
	public function build(
		AssociationQueryCondition $root_condition,
		$offset,
		$limit,
		OrderByInterface $orderby,
		ElementSelectorInterface $element_selector,
		$need_found_rows,
		ResultTransformationInterface $result_transformation
	) {
		$associations_table = $this->table_names->get_full_table_name( TableNames::ASSOCIATIONS );

		// Before building JOIN clauses, allow the ORDERBY builder also to add its own.
		$orderby->register_joins();

		// Same for the element selector. Otherwise the initialization would run
		// from inside of $this->join_manager->get_join_clause() which is too late.
		$element_selector->initialize();

		// Conditions can either use the JOIN manager object to share JOINed tables
		// or handle it entirely on their own. We need to get results from both sources here.
		//
		// Timing is extra important here: First, we get the JOIN clauses, so that the conditions
		// can create table aliases that are later used when building the WHERE clauses.
		//
		// Then come ORDER BY clauses.
		//
		// Then we ask the result transformation object to talk to the element selector,
		// and tell it which elements it will need in the select clause. This will
		// influence the following step as well and can't be done later.
		//
		// Then we collect the JOIN clauses from the join manager object, which may have been used also
		// when building the WHERE or ORDER BY clauses. This also includes JOINs coming from
		// the element selector.
		//
		// Finally, we already know what we're going to need in the results and we can
		// obtain the optimized select clauses from the element selector.
		$join_clause = $root_condition->get_join_clause();
		$where_clause = $root_condition->get_where_clause();
		$orderby_clause = $orderby->get_orderby_clause();
		$result_transformation->request_element_selection( $element_selector );

		$join_clause = $this->join_manager->get_join_clause( $element_selector ) . ' ' . $join_clause;
		$select_elements = $element_selector->get_select_clauses();
		// End of the timing-critical part.

		$sql_found_rows = ( $need_found_rows ? 'SQL_CALC_FOUND_ROWS' : '' );
		if ( ! empty( $orderby_clause ) ) {
			$orderby_clause = "ORDER BY $orderby_clause";
		}

		$limit = (int) $limit;
		$offset = (int) $offset;

		$maybe_distinct = $element_selector->maybe_get_distinct_modifier();

		// Make sure we glue the pieces together well and leave no extra comma at the end
		// in case $select_elements is empty.
		$final_select_elements = [];
		$select_elements_trimmed = trim( $select_elements );
		if ( ! empty( $select_elements_trimmed ) ) {
			$final_select_elements[] = $select_elements;
		}
		if( empty( $final_select_elements ) ) {
			// Failsafe against a very unprobable case when the element selector doesn't actually provide any output.
			$final_select_elements[] = "{$associations_table}." . AssociationTable::ID;
		}
		$final_select_elements = implode( ', ' . PHP_EOL, $final_select_elements );

		// We rely on all the moving parts which are supposed to have provided properly escaped strings.
		$associations_alias = TableJoinManager::ALIAS_ASSOCIATIONS;
		$query = "
			SELECT {$maybe_distinct}
				{$sql_found_rows}
				{$final_select_elements}
			FROM {$associations_table} AS {$associations_alias} {$join_clause}
			WHERE {$where_clause}
			{$orderby_clause}
			LIMIT {$limit}
			OFFSET {$offset}";

		// This special constant attaches the debug backtrace as a comment to the MySQL clause.
		// Useful for troubleshooting performance issues in environments without xdebug but with MySQL query logging.
		if ( defined( 'TOOLSET_ASSOCIATION_QUERY_DEBUG' ) && TOOLSET_ASSOCIATION_QUERY_DEBUG ) {
			// @codeCoverageIgnoreStart
			ob_start();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_print_backtrace
			debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$debug_backtrace = ob_get_clean();
			$debug_backtrace = str_replace( '#', "\n#", $debug_backtrace );
			$query .= "\n\n/* debug backtrace leading to this query:\n\n$debug_backtrace\n*/";
			// @codeCoverageIgnoreEnd
		}

		return $query;
	}

}
