<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;
use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use Toolset_Query_Comparison_Operator;
use Toolset_Utils;

/**
 * Query condition by a postmeta value of a selected element role.
 *
 * Note: Using this will immediately exclude all non-post elements.
 */
class PostMeta extends AbstractCondition {


	/** @var string */
	private $meta_key;


	/** @var string */
	private $meta_value;


	/** @var string */
	private $comparison_operator;


	/** @var RelationshipRole */
	private $for_role;


	/** @var TableJoinManager */
	private $join_manager;


	/**
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $comparison_operator
	 * @param RelationshipRole $for_role
	 * @param TableJoinManager $join_manager
	 */
	public function __construct(
		$meta_key,
		$meta_value,
		$comparison_operator,
		RelationshipRole $for_role,
		TableJoinManager $join_manager
	) {
		if (
			! is_string( $meta_key ) || Toolset_Utils::is_field_value_truly_empty( $meta_key )
			|| ! in_array( $comparison_operator, Toolset_Query_Comparison_Operator::all() )
		) {
			throw new InvalidArgumentException( 'Invalid meta_key or comparison operator.' );
		}

		$this->meta_key = $meta_key;
		$this->meta_value = $meta_value;
		$this->comparison_operator = $comparison_operator;
		$this->for_role = $for_role;
		$this->join_manager = $join_manager;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$postmeta = $this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
		$meta_value = esc_sql( $this->meta_value );

		// The comparison operator is sanitized in the constructor.
		return " {$postmeta}.meta_value $this->comparison_operator '$meta_value' ";
	}

}
