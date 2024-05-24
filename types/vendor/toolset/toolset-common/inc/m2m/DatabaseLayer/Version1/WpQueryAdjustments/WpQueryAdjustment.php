<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\JoinManager;

/**
 * Subclass of WpQueryAdjustment with some specifics for the version 1 database layer only.
 *
 * @since 4.0
 */
abstract class WpQueryAdjustment extends \OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\WpQueryAdjustment {


	/** @var null|\Toolset_Relationship_Table_Name */
	private $_table_names;


	/**
	 * WpQueryAdjustment constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param RelationshipRoleParentChild $target_role
	 * @param \IToolset_Element $for_element
	 * @param JoinManager $join_manager
	 * @param \Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param \Toolset_Relationship_Table_Name|null $table_names_di
	 * @param \wpdb|null $wpdb_di
	 */
	public function __construct( \IToolset_Relationship_Definition $relationship, RelationshipRoleParentChild $target_role, \IToolset_Element $for_element, JoinManager $join_manager, \Toolset_WPML_Compatibility $wpml_service_di = null, \Toolset_Relationship_Table_Name $table_names_di = null, \wpdb $wpdb_di = null ) {
		parent::__construct( $relationship, $target_role, $for_element, $join_manager, $wpml_service_di, $wpdb_di );

		$this->_table_names = $table_names_di;
	}


	/**
	 * @return \Toolset_Relationship_Table_Name
	 */
	protected function get_table_names() {
		if( null === $this->_table_names ) {
			$this->_table_names = new \Toolset_Relationship_Table_Name();
		}

		return $this->_table_names;
	}


	/**
	 * @return \wpdb
	 */
	protected function get_wpdb() {
		return $this->wpdb;
	}

}
