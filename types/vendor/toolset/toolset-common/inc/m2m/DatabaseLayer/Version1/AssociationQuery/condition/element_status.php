<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use InvalidArgumentException;
use IToolset_Relationship_Role;
use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;

/**
 * Condition to query associations by a status of an element in a particular role.
 *
 * Allows querying for a specific status or for a set of statuses that may be
 * depending on other circumstances (e.g. capabilities of the current user).
 *
 * Note that the functionality may be different per each domain. Currently, only posts
 * are supported.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Element_Status extends Toolset_Association_Query_Condition {

	/**
	 * @deprecated Use ElementStatusCondition instead.
	 * @var string
	 */
	const STATUS_AVAILABLE = 'is_available';

	/**
	 * @deprecated Use ElementStatusCondition instead.
	 * @var string
	 */
	const STATUS_PUBLIC = 'is_public';

	/**
	 * @deprecated Use ElementStatusCondition instead.
	 * @var string
	 */
	const STATUS_ANY = 'any';


	/** @var string|string[] */
	private $statuses;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/** @var Toolset_Association_Query_Element_Selector_Provider */
	private $element_selector_provider;


	private $post_status;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Element_Status
	 * constructor.
	 *
	 * @param string|string[] $statuses One or more status values.
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRole $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param PostStatus $post_status
	 */
	public function __construct(
		$statuses,
		\OTGS\Toolset\Common\Relationships\API\RelationshipRole $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		PostStatus $post_status
	) {
		if ( ( ! is_string( $statuses ) && ! is_array( $statuses ) ) || empty( $statuses ) ) {
			throw new InvalidArgumentException();
		}

		$this->statuses = $statuses;
		$this->for_role = $for_role;
		$this->join_manager = $join_manager;
		$this->element_selector_provider = $element_selector_provider;
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

		if ( is_array( $this->statuses ) ) {
			$accepted_statuses = $this->statuses;
		} else {
			$single_status = $this->statuses;

			switch ( $single_status ) {
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
				case ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT:
				case ElementStatusCondition::STATUS_ANY:
					// Match anything, don't bother with adding a query.
					return ' 1 = 1 ';
				default:
					// Single status string. If this is a wrong input, we'll return zero results anyway.
					$accepted_statuses = array( $single_status );
					break;
			}
		}

		if ( empty( $accepted_statuses ) ) {
			// For some reason, we don't allow any post status. Match nothing.
			return ' 1 = 0 ';
		}

		return sprintf(
			' %s.post_status IN ( %s ) ',
			$this->join_manager->wp_posts( $this->for_role ),
			'\'' . implode( '\', \'', $accepted_statuses ) . '\''
		);
	}
}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
/** @noinspection PhpDeprecationInspection */
class_alias( Toolset_Association_Query_Condition_Element_Status::class, \Toolset_Association_Query_Condition_Element_Status::class );
