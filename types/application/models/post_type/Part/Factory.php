<?php

namespace OTGS\Toolset\Types\PostType\Part;


use InvalidArgumentException;
use Toolset_Field_Group_Post;

/**
 * Class Factory
 *
 * @package OTGS\Toolset\Types\PostType\Part
 */
class Factory {


	/**
	 * Create ListingFields object
	 *
	 * @param string $slug
	 * @param array $cpt_array
	 *
	 * @return ListingFields
	 *
	 * @throws InvalidArgumentException
	 */
	public function create_cpt_listing_fields( $slug, $cpt_array ) {
		$cpt_listing_fields = new ListingFields( $slug );
		$cpt_listing_fields->init_by_cpt_array( $cpt_array );

		return $cpt_listing_fields;
	}


	/**
	 * Create FieldGroups object
	 *
	 * @param string $slug
	 * @param Toolset_Field_Group_Post[] $field_groups
	 *
	 * @return FieldGroups
	 */
	public function create_cpt_field_groups( $slug, $field_groups ) {
		$cpt_field_groups = new FieldGroups( $slug );

		foreach ( (array) $field_groups as $field_group ) {
			$cpt_field_groups->add_field_group_by_definition( $field_group );
		}

		return $cpt_field_groups;
	}
}
