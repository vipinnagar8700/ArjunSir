<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;

/**
 * Default element selector that takes the element ID directly from the connected element table.
 * Suitable for queries with only non-translatable elements.
 *
 * @since 4.0
 */
class DefaultSelector extends AbstractSelector {

	/**
	 * @var string[] Each item is a table alias and column name that holds the element ID,
	 *     to be used within the query itself. Indexed by role names.
	 */
	private $element_id_values = [];

	/**
	 * @var string[] Aliases for the connected element table that will be used in JOINs and throughout
	 *     the query. Indexed by role names.
	 */
	private $table_aliases = [];


	/** @var string[] Aliases for element ID columns that will be used in the SELECT clause, indexed by role names. */
	private $element_id_aliases = [];


	/**
	 * @inheritDoc
	 */
	public function get_element_id_alias(
		RelationshipRole $for_role, $which_element = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
	) {
		$this->request_element_in_results( $for_role );

		$role_name = $for_role->get_name();
		if ( ! array_key_exists( $role_name, $this->element_id_aliases ) ) {
			$this->element_id_aliases[ $role_name ] = SelectedColumnAliases::role_to_name( $for_role );
		}

		return $this->element_id_aliases[ $role_name ];
	}


	/**
	 * @inheritDoc
	 */
	public function request_element_in_results( RelationshipRole $role ) {
		parent::request_element_in_results( $role );

		// Make sure the connected element table is joined for the role.
		$this->request_connected_elements_table_alias( $role );
	}


	/**
	 * @inheritDoc
	 */
	public function get_element_id_value(
		RelationshipRole $for_role, $which_element = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
	) {
		$role_name = $for_role->get_name();
		if( ! array_key_exists( $role_name, $this->element_id_values ) ) {
			$this->element_id_values[ $role_name ] = sprintf(
				'%s.%s',
				$this->request_connected_elements_table_alias( $for_role ),
				ConnectedElementTable::ELEMENT_ID
			);
		}

		return $this->element_id_values[ $role_name ];
	}


	/**
	 * Generate an alias for the connected element table and store it, so that it is added
	 * to the output of get_join_clauses() later on. Idempotent.
	 *
	 * @param RelationshipRole $for_role
	 * @return string Table alias.
	 */
	private function request_connected_elements_table_alias( RelationshipRole $for_role ) {
		$role_name = $for_role->get_name();
		if( ! array_key_exists( $role_name, $this->table_aliases ) ) {
			$this->table_aliases[ $role_name ] = $this->table_alias->generate(
				$this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS ),
				true
			);
		}

		return $this->table_aliases[ $role_name ];
	}


	/**
	 * @inheritDoc
	 */
	public function get_select_clauses() {
		$clauses = $this->maybe_get_association_and_relationship();
		foreach( $this->requested_roles as $requested_role ) {
			$clauses[] = $this->get_element_id_value( $requested_role ) . ' AS ' . $this->get_element_id_alias( $requested_role );
		}

		return ' ' . implode( ', ', $clauses ) . ' ';
	}


	/**
	 * @inheritDoc
	 */
	public function get_join_clauses() {
		$table_name = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );

		$clauses = [];
		// Note that we're going through table aliases, not just roles requested in the select clause.
		// That's an important difference.
		foreach( $this->table_aliases as $role_name => $table_alias ) {
			$join = $role_name === \Toolset_Relationship_Role::INTERMEDIARY ? 'LEFT JOIN' : 'JOIN';
			$associations_table_alias = TableJoinManager::ALIAS_ASSOCIATIONS;
			$element_group_id_column = AssociationTable::role_to_column( \Toolset_Relationship_Role::role_from_name( $role_name ) );

			$connected_elements_group_id_column = ConnectedElementTable::GROUP_ID;
			$clauses[] =
				"$join $table_name AS $table_alias
				ON (
					$associations_table_alias.$element_group_id_column = $table_alias.$connected_elements_group_id_column
				)";
		}

		return ' ' . implode( PHP_EOL, $clauses ) . ' ';
	}


	/**
	 * @inheritDoc
	 */
	public function may_have_element_id_translated( RelationshipRole $role ) {
		return false;
	}
}
