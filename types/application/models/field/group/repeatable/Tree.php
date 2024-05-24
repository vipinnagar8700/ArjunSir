<?php

namespace OTGS\Toolset\Types\Field\Group\Repeatable;

use InvalidArgumentException;
use Types_Field_Group_Repeatable;

/**
 * Repeatable Field Groups can be nested. This class is a collection of
 *
 * @since 3.0.3
 */
class Tree {

	/**
	 * @var Types_Field_Group_Repeatable[]
	 */
	private $tree = array();


	/**
	 * Add another RFG to the top of the tree
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 */
	public function add( Types_Field_Group_Repeatable $rfg ) {
		$this->tree[ $rfg->get_id() ] = $rfg;
	}


	/**
	 * Get the parents of the given $rfg
	 * The returned array will start with the top parent
	 *
	 * Examples: Given RFGS: A -> B -> C -> D
	 * Example 1: Requesting D will return [ A, B, C ]
	 * Example 2: Requesting B will return [ A ]
	 * Example 3: Requesting A will return [ ] (as A has no parents)
	 *
	 * @param Types_Field_Group_Repeatable|int|mixed $rfg_object_or_id or ID of the group
	 *
	 * @param bool $from_bottom_to_top Setting this to true will reverse the order of parents.
	 *     For Example 1 the returned array would be [ C, B, A ]
	 *
	 * @return Types_Field_Group_Repeatable[]
	 */
	public function getParentsOf( $rfg_object_or_id, $from_bottom_to_top = false ) {
		if ( ! $rfg_object_or_id instanceof Types_Field_Group_Repeatable && ! is_int( $rfg_object_or_id ) ) {
			throw new InvalidArgumentException(
				'Input must be an object of \Types_Field_Group_Repeatable or the post id'
			);
		}

		$rfg_id = $rfg_object_or_id instanceof Types_Field_Group_Repeatable
			? $rfg_object_or_id->get_id()
			: $rfg_object_or_id;

		if ( ! isset( $this->tree[ $rfg_id ] ) ) {
			// the requested id is not part of the tree, means no parents
			return array();
		}

		// get position of the current
		$position_requested = array_search( $rfg_id, array_keys( $this->tree ), false );

		// get all parents
		$parents = array_slice( $this->tree, 0, $position_requested, true );

		// return in choosen order
		return $from_bottom_to_top
			? array_reverse( $parents, true )
			: $parents;
	}
}
