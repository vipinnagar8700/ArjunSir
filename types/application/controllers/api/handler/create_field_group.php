<?php

/**
 * Handler for the types_create_group filter.
 *
 * @since 2.3
 */
class Types_Api_Handler_Create_Field_Group implements Types_Api_Handler_Interface {

	public function __construct() {
	}

	/**
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return null|int The ID of the group or null on error.
	 */
	function process_call( $arguments ) {
		$domain    = toolset_getarr( $arguments, 1, null );
		$name      = toolset_getarr( $arguments, 2, null );
		$title     = toolset_getarr( $arguments, 3, null );
		$is_active = toolset_getarr( $arguments, 4, true );
		$status    = $is_active ? 'publish' : 'draft';

		if ( ! in_array( $domain, Toolset_Field_Utils::get_domains() ) || ! is_string( $name ) || ! is_string( $title ) ) {
			return null;
		}

		$field_group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );
		$field_group         = $field_group_factory->create_field_group( $name, $title, $status );

		return $field_group->get_id();
	}

}