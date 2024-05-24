<?php

namespace OTGS\Toolset\Common\PublicAPI;

/**
 * Public interface for a field type.
 *
 * Safe to be used in third-party software.
 *
 * @package OTGS\Toolset\Common\PublicAPI
 * @since Types 3.3.5
 */
interface CustomFieldTypeDefinition {


	/**
	 * Get a field type slug.
	 *
	 * @return string
	 */
	public function get_slug();


	/**
	 * Get a display name of the field type.
	 *
	 * @return string
	 */
	public function get_display_name();


	/**
	 * Get a description of the field type.
	 *
	 * @return string
	 */
	public function get_description();


	/**
	 * Check if fields of this type can be repeating (if they can have multiple values).
	 *
	 * @return bool
	 */
	public function can_be_repeating();


	/**
	 * Retrieve CSS classes for a field type icon.
	 *
	 * To be placed in the i tag. Additional assets may be needed for this to work properly.
	 *
	 * @return string
	 */
	public function get_icon_classes();

}
