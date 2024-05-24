<?php

/**
 * User field group.
 *
 * @since 2.0
 */
class Toolset_Field_Group_User extends Toolset_Field_Group {


	const POST_TYPE = 'wp-types-user-group';

	/**
	 * Postmeta that contains a comma-separated list of role slugs where this field group is assigned.
	 *
	 * Note: There might be empty items in the list: ",,,role-slug,," Make sure to avoid those.
	 *
	 * Note: Empty value means "all groups". There also may be legacy value "all" with the same meaning.
	 *
	 * @since unknown
	 */
	const USERMETA_USER_ROLE_LIST = '_wp_types_group_showfor';

	/**
	 * @param WP_Post $field_group_post Post object representing a user field group.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $field_group_post ) {
		parent::__construct( $field_group_post );
		if( self::POST_TYPE != $field_group_post->post_type ) {
			throw new InvalidArgumentException( 'incorrect post type' );
		}
	}


	/**
	 * @return Toolset_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected function get_field_definition_factory() {
		return Toolset_Field_Definition_Factory_User::get_instance();
	}

	/**
	 * Get roles that are associated with this field group.
	 *
	 * @return string[] Role slugs. Empty array means that this group should be displayed with all roles.
	 * @since 3.1
	 */
	public function get_associated_roles() {
		$db_assigned_to = get_post_meta( $this->get_id(), self::USERMETA_USER_ROLE_LIST, true );

		// in old types version we store "all"
		if ( 'all' == $db_assigned_to ) {
			return array();
		}

		// Keep your eyes open on storing values,
		// This is needed because legacy code produces values like ,,,,role-slug,,
		$db_assigned_to = trim( $db_assigned_to, ',' );

		// empty means all post types are selected
		if ( empty( $db_assigned_to ) ) {
			return array();
		}

		// we have selected post types
		$db_assigned_to_array = explode( ',', $db_assigned_to );
		$db_assigned_to_array = array_filter( $db_assigned_to_array );

		return array_values( $db_assigned_to_array );
	}

	/**
	 * Quickly determine whether given role is associated with this group.
	 *
	 * @param string $role
	 * @return bool
	 * @since 3.1
	 */
	public function has_associated_role( $role ) {
		$roles = $this->get_associated_roles();
		return ( empty( $roles ) || in_array( $role, $roles ) );
	}
}
