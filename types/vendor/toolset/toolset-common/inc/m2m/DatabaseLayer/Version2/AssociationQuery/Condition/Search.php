<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;
use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use wpdb;

/**
 * Query by searching a text in elements of a given role.
 *
 * Note: This currently supports only posts, but in the future, it should be domain-agnostic.
 */
class Search extends AbstractCondition {


	/** @var string */
	private $search_string;


	/** @var bool */
	private $is_exact_search;


	/** @var RelationshipRole */
	private $for_role;


	/** @var TableJoinManager */
	private $join_manager;


	/** @var wpdb */
	private $wpdb;


	/**
	 * @param string $search_string
	 * @param bool $is_exact_search
	 * @param RelationshipRole $for_role
	 * @param TableJoinManager $join_manager
	 * @param wpdb $wpdb
	 */
	public function __construct(
		$search_string,
		$is_exact_search,
		RelationshipRole $for_role,
		TableJoinManager $join_manager,
		wpdb $wpdb
	) {
		if ( ! is_string( $search_string ) || empty( $search_string ) ) {
			throw new InvalidArgumentException( 'Invalid search string.' );
		}
		$this->search_string = $search_string;
		$this->join_manager = $join_manager;
		$this->is_exact_search = (bool) $is_exact_search;
		$this->for_role = $for_role;
		$this->wpdb = $wpdb;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$wp_posts = $this->join_manager->wp_posts( $this->for_role );
		$search_string = esc_sql( $this->get_sanitized_search_string() );

		return " {$wp_posts}.post_title LIKE '{$search_string}'
			OR {$wp_posts}.post_excerpt LIKE '{$search_string}'
			OR {$wp_posts}.post_content LIKE '{$search_string}' ";
	}


	/**
	 * Get a string prepared for using in the query.
	 *
	 * @return string
	 */
	private function get_sanitized_search_string() {
		$s = stripslashes( $this->search_string );
		$s = trim( $s );
		$s = str_replace( array( "\r", "\n", "\t" ), '', $s );
		if ( ! $this->is_exact_search ) {
			$s = '%' . $this->wpdb->esc_like( $s ) . '%';
		}

		return $s;
	}
}
