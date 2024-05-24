<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation;

use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Shared functionality for adjusting the WP_Query behaviour.
 */
abstract class WpQueryAdjustment extends \Toolset_Wpdb_User {


	/** @var \IToolset_Relationship_Definition */
	protected $relationship;


	/** @var \IToolset_Element */
	protected $for_element;


	/** @var \IToolset_Relationship_Role_Parent_Child */
	protected $target_role;


	/** @var WpmlService */
	protected $wpml_service;


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\PotentialAssociation\JoinManager */
	protected $join_manager;


	/**
	 * Determine whether the WP_Query should be augmented.
	 *
	 * @return bool
	 */
	protected abstract function is_actionable();


	/**
	 * WpQueryAdjustment constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param \IToolset_Element $for_element
	 * @param JoinManager $join_manager
	 * @param WpmlService $wpml_service_di
	 * @param \wpdb|null $wpdb_di
	 */
	public function __construct(
		\IToolset_Relationship_Definition $relationship,
		RelationshipRoleParentChild $target_role,
		\IToolset_Element $for_element,
		JoinManager $join_manager,
		WpmlService $wpml_service_di = null,
		\wpdb $wpdb_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->relationship = $relationship;
		$this->for_element = $for_element;
		$this->target_role = $target_role;
		$this->wpml_service = $wpml_service_di ? : WpmlService::get_instance();
		$this->join_manager = $join_manager;
	}


	/**
	 * Hooks to filters in order to add extra clauses to the MySQL query.
	 */
	public function before_query() {
		if ( ! $this->is_actionable() ) {
			return;
		}

		add_filter( 'posts_join', array( $this, 'add_join_clauses' ) );
		add_filter( 'posts_where', array( $this, 'add_where_clauses' ) );

		// Filtering later because we want to have the last word.
		add_filter( 'posts_orderby', array( $this, 'add_orderby_clauses' ), 100, 2 );
	}


	/**
	 * Cleanup - unhooks the filters added in before_query().
	 */
	public function after_query() {
		if ( ! $this->is_actionable() ) {
			return;
		}

		remove_filter( 'posts_join', array( $this, 'add_join_clauses' ) );
		remove_filter( 'posts_where', array( $this, 'add_where_clauses' ) );
		remove_filter( 'posts_orderby', array( $this, 'add_orderby_clauses' ) );
	}



	/**
	 * @inheritDoc
	 */
	public function add_join_clauses( $join ) {
		return $join;
	}


	/**
	 * @inheritDoc
	 */
	public function add_where_clauses( $where ) {
		return $where;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_orderby_clauses( $orderby, \WP_Query $wp_query ) {
		return $orderby;
	}

}
