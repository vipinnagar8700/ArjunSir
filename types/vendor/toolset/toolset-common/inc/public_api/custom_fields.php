<?php /** @noinspection PhpUnused */

use OTGS\Toolset\Common\PublicAPI\CustomFieldDefinition;
use OTGS\Toolset\Common\PublicAPI\CustomFieldGroup;
use OTGS\Toolset\Common\PublicAPI\CustomFieldInstance;

/**
 * Get field groups based on query arguments.
 *
 * @param array $args Following arguments are supported.
 *     - 'domain' (string): The only mandatory argument, must be 'posts'|'users'|'terms'.
 *     - 'types_search': String for extended search.
 *     - 'is_active' (bool): If defined, only active/inactive field groups will be returned.
 *     - 'assigned_to_post_type' string: For post field groups only, filter results by being assinged to a
 *     particular post type.
 *     - 'purpose' (string): See Toolset_Field_Group::get_purpose() for information about this argument.
 *        Default is Toolset_Field_Group::PURPOSE_GENERIC. Special value '*' will return groups of all purposes.
 *
 * @return \OTGS\Toolset\Common\PublicAPI\CustomFieldGroup[]
 * @throws \InvalidArgumentException On invalid input.
 * @since 3.4 (Types 3.3)
 */
function toolset_get_field_groups( $args ) {

	if ( ! apply_filters( 'types_is_active', false ) ) {
		return array();
	}

	$domain = toolset_getarr( $args, 'domain' );
	if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
		throw new \InvalidArgumentException( 'Invalid field group domain.' );
	}

	$field_group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );

	return $field_group_factory->query_groups( $args );
}


/**
 * Retrieve a particular field group by its slug.
 *
 * @param string $group_slug Slug of the field group.
 * @param string $domain Domain of the field group, 'posts'|'users'|'terms'.
 *
 * @return \OTGS\Toolset\Common\PublicAPI\CustomFieldGroup|null
 * @throws \InvalidArgumentException On invalid input.
 * @since 3.4 (Types 3.3)
 */
function toolset_get_field_group( $group_slug, $domain ) {
	if ( ! apply_filters( 'types_is_active', false ) ) {
		return null;
	}

	if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
		throw new \InvalidArgumentException( 'Invalid field group domain.' );
	}

	$field_group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );

	return $field_group_factory->load_field_group( $group_slug );

}


/**
 * For a given element, query field groups that ought to be displayed for it.
 *
 * Note: At the moment, only the post domain is supported. File a feature request if you need to use this for users or terms.
 *
 * Note: This involves evaluating field group display conditions and may be performance-intensive. Make sure that
 * your code scales well and you're not looping over all posts and calling this method, for example.
 *
 * @param int|WP_Post|WP_User|WP_Term $element_source The element whose field groups need to be returned.
 * @param string $domain One of the values from \OTGS\Toolset\Common\PublicAPI\ElementDomain.
 *
 * @return CustomFieldGroup[]|null
 * @throws \InvalidArgumentException If obviously invalid arguments are provided.
 * @since Types 3.3.5
 */
function toolset_get_groups_for_element( $element_source, $domain ) {
	if ( ! apply_filters( 'types_is_active', false ) ) {
		return null;
	}

	if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
		throw new \InvalidArgumentException( 'Invalid element domain.' );
	}

	$element_factory = new Toolset_Element_Factory();
	try {
		$element_model = $element_factory->get_element( $domain, $element_source );
	} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
		return null;
	}

	$group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );

	return $group_factory->get_groups_for_element( $element_model );
}


/**
 * For the given element, retrieve the array of custom fields that should be displayed with it.
 *
 * Note: At the moment, only the post domain is supported. File a feature request if you need to use this for users or terms.
 *
 * Note: Internally uses toolset_get_groups_for_element(), make sure you read its description before using
 * this function.
 *
 * @param int|WP_Post|WP_User|WP_Term $element_source The element whose field groups need to be returned.
 * @param string $domain One of the values from \OTGS\Toolset\Common\PublicAPI\ElementDomain.
 *
 * @return CustomFieldDefinition[]|null
 * @throws \InvalidArgumentException If obviously invalid arguments are provided.
 * @since Types 3.3.5
 */
function toolset_get_fields_for_element( $element_source, $domain ) {
	if ( ! apply_filters( 'types_is_active', false ) ) {
		return null;
	}

	return array_reduce(
		array_map(
			static function ( CustomFieldGroup $group ) {
				return $group->get_field_definitions();
			},
			toolset_get_groups_for_element( $element_source, $domain )
		),
		static function ( $carry, $field_definitions_from_group ) {
			foreach ( $field_definitions_from_group as $field_definition ) {
				if ( ! array_key_exists( $field_definition->get_slug(), $carry ) ) {
					$carry[ $field_definition->get_slug() ] = $field_definition;
				}
			}

			return $carry;
		},
		[]
	);
}


/**
 * For the given element, provide instances of its custom fields.
 *
 * This is the way of getting access to all field values of an element.
 *
 * Note: At the moment, only the post domain is supported. File a feature request if you need to use this for users or terms.
 *
 * Note: Internally uses toolset_get_fields_for_element(), make sure you read its description before using
 * this function.
 *
 * @param int|WP_Post|WP_User|WP_Term $element_source The element whose field groups need to be returned.
 * @param string $domain One of the values from \OTGS\Toolset\Common\PublicAPI\ElementDomain.
 *
 * @return CustomFieldInstance[]
 * @throws \InvalidArgumentException If obviously invalid arguments are provided.
 * @since Types 3.3.5
 */
function toolset_get_field_instances( $element_source, $domain ) {
	if ( ! apply_filters( 'types_is_active', false ) ) {
		return null;
	}

	return array_map(
		static function ( CustomFieldDefinition $field_definition ) use ( $element_source ) {
			return $field_definition->instantiate( $element_source );
		},
		toolset_get_fields_for_element( $element_source, $domain )
	);
}


/**
 * Get a specific field instance (to access one custom field value from a specific element).
 *
 * Note: At the moment, only the post domain is supported. File a feature request if you need to use this for users or terms.
 *
 * @param int|WP_Post|WP_User|WP_Term $element_source The element whose field groups need to be returned.
 * @param string $domain One of the values from \OTGS\Toolset\Common\PublicAPI\ElementDomain.
 * @param string $field_slug Slug (not meta_key) of the custom field.
 *
 * @return CustomFieldInstance|null
 * @throws \InvalidArgumentException If obviously invalid arguments are provided.
 * @since Types 3.3.5
 */
function toolset_get_field_instance( $element_source, $domain, $field_slug ) {
	if ( ! apply_filters( 'types_is_active', false ) ) {
		return null;
	}

	if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
		throw new \InvalidArgumentException( 'Invalid element domain.' );
	}

	$element_factory = new Toolset_Element_Factory();
	try {
		$element = $element_factory->get_element( $domain, $element_source );
	} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
		return null;
	}

	if ( ! $element->has_field( $field_slug ) ) {
		return null;
	}

	return $element->get_field( $field_slug );
}
