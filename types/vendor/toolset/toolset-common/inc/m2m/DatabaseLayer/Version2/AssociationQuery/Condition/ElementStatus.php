<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;

/**
 * Condition to query associations by a status of an element in a particular role.
 *
 * Allows querying for a specific status or for a set of statuses that may be
 * depending on other circumstances (e.g. capabilities of the current user).
 *
 * Note that the functionality may be different per each domain. Currently, only posts
 * are supported.
 *
 * @since 4.0
 */
class ElementStatus extends AbstractCondition {

	/** @var string|string[] */
	private $statuses;


	/** @var RelationshipRole */
	private $for_role;


	/** @var TableJoinManager */
	private $join_manager;


	/** @var PostStatus */
	private $post_status;


	/**
	 * @param string|string[] $statuses One or more status values.
	 * @param RelationshipRole $for_role
	 * @param TableJoinManager $join_manager
	 * @param PostStatus $post_status
	 */
	public function __construct(
		$statuses,
		RelationshipRole $for_role,
		TableJoinManager $join_manager,
		PostStatus $post_status
	) {
		if ( ( ! is_string( $statuses ) && ! is_array( $statuses ) ) || empty( $statuses ) ) {
			throw new InvalidArgumentException( 'Invalid statuses provided' );
		}

		$this->statuses = $statuses;
		$this->for_role = $for_role;
		$this->join_manager = $join_manager;
		$this->post_status = $post_status;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return $this->get_where_clause_for_posts();
	}


	/**
	 * Get the WHERE clause if the domain is known to be posts.
	 *
	 * @return string
	 */
	private function get_where_clause_for_posts() {
		$accepted_statuses = $this->get_list_of_statuses();

		if ( null === $accepted_statuses ) {
			// No post status condition at all.
			return ' 1 = 1 ';
		}

		if ( empty( $accepted_statuses ) ) {
			// For some reason, we don't allow any post status. Match nothing.
			// Note: This cannot be reached because of the validation in the constructor.
			return ' 1 = 0 '; // @codeCoverageIgnore
		}

		return sprintf(
			' %s.post_status IN ( %s ) ',
			$this->join_manager->wp_posts( $this->for_role ),
			'\'' . implode( '\', \'', $accepted_statuses ) . '\''
		);
	}


	private function get_list_of_statuses() {
		if ( is_array( $this->statuses ) ) {
			return $this->statuses;
		}

		switch ( $this->statuses ) {
			case ElementStatusCondition::STATUS_PUBLIC:
				$accepted_statuses = array( 'publish' );
				break;
			case ElementStatusCondition::STATUS_AVAILABLE:
				// FIXME make the logic complete (involving WP_Query business logic and Access)
				$accepted_statuses = $this->post_status->get_available_post_statuses();
				if ( current_user_can( 'read_private_posts' ) ) {
					$accepted_statuses[] = 'private';
				}
				break;
			case ElementStatusCondition::STATUS_ANY:
			case ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT:
				// Match anything, don't bother with adding a query.
				return null;
			default:
				// Single status string. If this is a wrong input, we'll return zero results anyway.
				$accepted_statuses = array( $this->statuses );
				break;
		}

		return $accepted_statuses;
	}


	/**
	 * @return bool Determine whether auto-draft posts MAY be included in the result after this condition
	 *     is applied for the given role. This is important for MySQL query optimization in the context of WPML.
	 */
	public function includes_auto_draft() {
		return ( is_string( $this->statuses ) && ElementStatusCondition::STATUS_ANY === $this->statuses )
			|| ( is_array( $this->statuses ) && in_array( 'auto-draft', $this->get_list_of_statuses(), true ) );
	}
}
