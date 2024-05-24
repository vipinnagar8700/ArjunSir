<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\OrderBy;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Order associations by a postmeta value of an (post) element of given role.
 *
 * Note: Using this on an element of a wrong domain will exclude all associations from the results.
 *
 * @since 4.0
 */
class OrderByPostmeta extends AbstractOrderBy {

	/**
	 * List of allowed casting types
	 *
	 * @var string[]
	 */
	const ALLOWED_MYSQL_TYPES = [ 'SIGNED', 'UNSIGNED', 'DATE', 'DATETIME', 'CHAR' ];


	/** @var RelationshipRole */
	private $for_role;


	/** @var string */
	private $meta_key;


	/**
	 * If the metakey needs to be casted into a different type (UNSIGNED, DATE, ..)
	 *
	 * @var string
	 */
	private $cast_to;


	/**
	 * @param string $meta_key
	 * @param RelationshipRole $role
	 * @param TableJoinManager $join_manager
	 * @param string $cast_to If the metakey needs to be casted into a different type
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$meta_key,
		RelationshipRole $role,
		TableJoinManager $join_manager,
		$cast_to = null
	) {
		parent::__construct( $join_manager );

		if ( ! is_string( $meta_key ) || empty( $meta_key ) ) {
			throw new InvalidArgumentException( 'Invalid meta_key provided.' );
		}

		$this->meta_key = $meta_key;
		$this->for_role = $role;
		if (
			null !== $cast_to
			&& (
				! is_string( $cast_to )
				|| ! in_array( strtoupper( $cast_to ), self::ALLOWED_MYSQL_TYPES, true )
			)
		) {
			throw new InvalidArgumentException( 'Unsupported MySQL data type to cast to provided.' );
		}
		$this->cast_to = $cast_to;
	}


	/**
	 * @inheritdoc
	 */
	public function register_joins() {
		$this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_orderby_clause() {
		$postmeta_table_alias = $this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
		if ( $this->cast_to ) {
			return " CAST({$postmeta_table_alias}.meta_value AS {$this->cast_to}) {$this->order} ";
		}

		return " {$postmeta_table_alias}.meta_value {$this->order} ";
	}
}
