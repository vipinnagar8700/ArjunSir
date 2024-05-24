<?php

namespace OTGS\Toolset\Types\PostType\Part;

use Toolset_Field_Group_Post;

/**
 * This class is for handling the assignment of Fields Groups
 *
 * @since 3.2
 */
class FieldGroups extends APostTypePart {

	/** @var Toolset_Field_Group_Post[]  */
	private $field_group_defintions = array();


	/**
	 * Apply changes to listing fields to the given $cpt array.
	 *
	 * @param array $cpt_array
	 *
	 * @return array
	 */
	public function apply_to_cpt_array( $cpt_array ) {
		if( ! is_array( $cpt_array ) ) {
			return $cpt_array;
		}

		$cpt_array['custom-field-group'] = array();

		foreach( $this->field_group_defintions as $field_group_defintion ) {
			if( ! $assigned_to_types = $field_group_defintion->get_assigned_to_types() ) {
				// we have the odd legacy decision that a group, assigned to nothing, is showed everywhere, BUT
				// in addition to that odd decision, we also do not store these group in the legacy cpts array
				// means we just continue here... doing nothing
				continue;
			}

			if( in_array( $this->get_cpt_slug(), $assigned_to_types, true ) ) {
				// at least consistent in oddness...
				// this is how the field groups are stored [group_id] = 1
				$cpt_array['custom-field-group'][ $field_group_defintion->get_id() ] = 1;
			}
		}

		if( empty( $cpt_array['custom-field-group'] ) ) {
			// no need to store it empty
			unset( $cpt_array['custom-field-group'] );
		}

		return $cpt_array;
	}

	/**
	 * @param Toolset_Field_Group_Post $field_group
	 */
	public function add_field_group_by_definition( Toolset_Field_Group_Post $field_group ) {
		// overwritting is allowed
		$this->field_group_defintions[ $field_group->get_slug() ] = $field_group;
	}

	/**
	 * Check if cpt has field group
	 *
	 * @param string $field_slug
	 *
	 * @return bool
	 */
	public function has_field_by_slug( $field_slug ) {
		return array_key_exists( $field_slug, $this->field_group_defintions );
	}

	/**
	 * Check if CPT has a specific field assigned through any field group
	 *
	 * @param string $requested_field_slug
	 *
	 * @return bool
	 */
	public function contains_field_by_slug( $requested_field_slug ) {
		foreach( $this->field_group_defintions as $field_group_definition ) {
			foreach( $field_group_definition->get_field_slugs() as $field_slug ) {
				if( $field_slug === $requested_field_slug ) {
					// CPT has requested field assigned
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Remove a field group by the slug of the field group
	 *
	 * @param string $field_group_slug
	 */
	public function remove_field_group_by_slug( $field_group_slug ) {
		unset( $this->field_group_defintions[ $field_group_slug ] );
	}

}
