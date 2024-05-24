<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\RelationshipDatabaseOperations;

/**
 * Condition that a relationship involves a certain element domain.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Has_Domain extends Toolset_Relationship_Query_Condition {


	/** @var string */
	private $domain_name;


	/** @var IToolset_Relationship_Role_Parent_Child */
	private $role;


	/** @var RelationshipDatabaseOperations */
	private $database_operations;


	/**
	 * Toolset_Relationship_Query_Condition_Has_Domain constructor.
	 *
	 * @param string $domain_name One of the Toolset_Field_Utils::DOMAIN_* values.
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $role
	 * @param RelationshipDatabaseOperations|null $database_operations_di
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$domain_name, \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $role,
		RelationshipDatabaseOperations $database_operations_di = null
	) {
		if( ! is_string( $domain_name ) || ! in_array( $domain_name, Toolset_Element_Domain::all() ) ) {
			throw new InvalidArgumentException( 'Invalid element domain provided: ' . sanitize_title( $domain_name ) );
		}

		$this->domain_name = $domain_name;

		$this->role = $role;

		$this->database_operations = $database_operations_di ?: new RelationshipDatabaseOperations();
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {
		$column_name = $this->database_operations->role_to_column(
			$this->role, RelationshipDatabaseOperations::COLUMN_DOMAIN
		);

		return sprintf(
			"relationships.{$column_name} = '%s'",
			esc_sql( $this->domain_name )
		);
	}
}
