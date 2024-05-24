<?php

namespace OTGS\Toolset\Types\Field\Group\Repeatable;

use Toolset_Field_Group;
use Toolset_Field_Group_Post_Factory;
use Types_Field_Group_Repeatable;
use Types_Field_Group_Repeatable_Service;

/**
 * @since 3.0.3
 */
class TreeFactory {

	/** @var Toolset_Field_Group_Post_Factory */
	private $field_group_factory;

	/** @var Types_Field_Group_Repeatable_Service */
	private $service_rfg;

	/** @var Toolset_Field_Group[] */
	private $_field_groups;


	/**
	 * TreeFactory constructor.
	 *
	 * @param Toolset_Field_Group_Post_Factory $field_group_factory
	 * @param Types_Field_Group_Repeatable_Service $service_rfg
	 */
	public function __construct( Toolset_Field_Group_Post_Factory $field_group_factory, Types_Field_Group_Repeatable_Service $service_rfg ) {
		$this->field_group_factory = $field_group_factory;
		$this->service_rfg = $service_rfg;
	}


	/**
	 * Get the tree of an RFG no matter which position the given $rfg has
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 *
	 * @return Tree
	 */
	public function getTreeByRFG( Types_Field_Group_Repeatable $rfg ) {
		// get a new tree object
		$tree = $this->newTreeObject();

		// add parents to tree
		foreach ( $this->loadParents( $rfg ) as $parent_rfg ) {
			$tree->add( $parent_rfg );
		}

		// the given rfg is between it's parents and children
		$tree->add( $rfg );

		// add childrens to tree
		foreach ( $this->loadChildren( $rfg ) as $child_rfg ) {
			$tree->add( $child_rfg );
		}

		// return
		return $tree;
	}


	/**
	 * Get the tree of an RFG by RFG Id
	 *
	 * @param int $rfg_id
	 *
	 * @return bool|Tree
	 */
	public function getTreeByRFGId( $rfg_id ) {
		if ( ! $rfg = $this->service_rfg->get_object_by_id( $rfg_id ) ) {
			// no rfg found by id = no tree
			return false;
		}

		return $this->getTreeByRFG( $rfg );
	}


	/**
	 * @return Tree
	 */
	private function newTreeObject() {
		return new Tree();
	}


	/**
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param array $parents
	 *
	 * @return array
	 */
	private function loadParents( Types_Field_Group_Repeatable $rfg, $parents = array() ) {
		if ( $parent_rfg = $this->getParentGroup( $rfg ) ) {
			$parents[ $parent_rfg->get_id() ] = $parent_rfg;

			// go further up in the tree
			return $this->loadParents( $parent_rfg, $parents );
		}

		// no more parents, return the collected in the reversed order
		// "array_reverse" because we collect them from bottom (nested) to top level
		// but we want the tree to be from top to bottom
		return array_reverse( $parents, true );
	}


	/**
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param array $children
	 *
	 * @return array
	 */
	private function loadChildren( Types_Field_Group_Repeatable $rfg, $children = array() ) {
		foreach ( $rfg->get_field_slugs() as $field_slug ) {
			if ( $child_rfg = $this->service_rfg->get_object_from_prefixed_string( $field_slug ) ) {
				$children[ $child_rfg->get_id() ] = $child_rfg;

				$children = $this->loadChildren( $child_rfg, $children );
			}
		}

		return $children;
	}


	/**
	 * Get parent group of an rfg
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 *
	 * @return false|Types_Field_Group_Repeatable
	 */
	private function getParentGroup( Types_Field_Group_Repeatable $rfg ) {
		foreach ( $this->getFieldGroups() as $field_group ) {
			$field_group_slugs = $field_group->get_field_slugs();
			foreach ( (array) $field_group_slugs as $field_slug ) {
				if ( $field_slug === $rfg->get_id_with_prefix() ) {
					// there can only be one parent
					return $this->service_rfg->get_object_by_id( $field_group->get_id() );
				}
			}
		}

		// no parent found
		return false;
	}


	/**
	 * @return Toolset_Field_Group[]
	 */
	private function getFieldGroups() {
		if ( $this->_field_groups === null ) {
			// field groups not loaded yet
			// query all fields including rfgs
			$this->_field_groups = $this->field_group_factory->query_groups(
				array(
					'purpose' => '*',
					'post_status' => 'any',
				)
			);

			if ( ! is_array( $this->_field_groups ) ) {
				$this->_field_groups = array();
			}
		}

		return $this->_field_groups;
	}
}
