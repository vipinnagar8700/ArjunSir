<?php

namespace OTGS\Toolset\Common\PublicAPI;

/**
 * Public interface for a custom field group.
 *
 * Safe to be used in third-party software.
 *
 * @package OTGS\Toolset\Common\PublicAPI
 * @since 3.4 (Types 3.3)
 */
interface CustomFieldGroup {

	/**
	 * Field group slug.
	 *
	 * @return string
	 */
	public function get_slug();


	/**
	 * Display name of the field group.
	 *
	 * @return string
	 */
	public function get_display_name();


	/**
	 * Fields that belong to the group.
	 *
	 * @return CustomFieldDefinition[]
	 */
	public function get_field_definitions();


	/**
	 * For a given field slug, return its model, if it exists within the group.
	 *
	 * @param string $field_slug
	 *
	 * @return CustomFieldDefinition|null
	 */
	public function get_field_definition( $field_slug );


	/**
	 * Returns the purpose of the field group.
	 *
	 * This is one of the constants from OTGS\Toolset\Common\PublicApi\CustomFieldPurpose.
	 *
	 * @return string
	 * @since Types 3.3.6
	 */
	public function get_purpose();

}
