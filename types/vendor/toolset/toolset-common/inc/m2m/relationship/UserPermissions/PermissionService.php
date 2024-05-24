<?php

namespace OTGS\Toolset\Common\Relationships\UserPermissions;

/**
 * The class provides the necessary interface with Toolset Access APIs / WP Capabilities APIs.
 * And the necessary methods to check how a relationship post type capabilities are managed.
 *
 * @since Types 3.4.2
 */
class PermissionService {

	/**
	 * 2 Dimensional array of post types along with their capabilities.
	 *
	 * @var string[][]
	 */
	private $permissions;

	/**
	 * Relationship post types.
	 *
	 * @var string[]
	 */
	private $post_types = [];

	/**
	 * The key in this array refers to Toolset Access Option name.
	 * And the value refers to the WordPress capability name.
	 *
	 * @var string[]
	 */
	const OPTIONS = [
		'publish' => 'publish_posts',
		'edit_any' => 'edit_others_posts',
		'edit_own' => 'edit_posts',
		'delete_any' => 'delete_others_posts',
		'delete_own' => 'delete_posts',
	];


	/**
	 * PermissionService constructor.
	 *
	 * @param string[] ...$post_types
	 *
	 * @throws \InvalidArgumentException Thrown in case any of the post types isn't a string.
	 */
	public function __construct( ...$post_types ) {
		foreach ( array_merge( ...$post_types ) as $type ) {
			if ( false === is_string( $type ) ) {
				throw new \InvalidArgumentException();
			}
			$this->post_types[] = $type;
		}
	}


	/**
	 * Loops over the added post types, and the registered options.
	 * And checks whether the post type is managed by access or WP.
	 * And checks the capabilities based on that fact.
	 */
	private function check_permissions() {
		foreach ( $this->post_types as $post_type ) {
			$is_managed_by_access = apply_filters( 'toolset_access_check_if_post_type_managed', false, $post_type );
			foreach ( self::OPTIONS as $access_option => $cap ) {
				if ( true === $is_managed_by_access ) {
					$this->permissions[ $post_type ][ $access_option ] = (bool) apply_filters( 'toolset_access_api_get_post_type_permissions', false, $post_type, $access_option );
				} else {
					$this->permissions[ $post_type ][ $access_option ] = (bool) current_user_can( $cap );
				}
			}
		}
	}


	/**
	 * Returns a keyed array where the key is the capability option name and the value is a boolean.
	 *
	 * @param string $type Post type slug.
	 *
	 * @return bool[]
	 */
	public function get_user_caps( $type ) {
		if ( null === $this->permissions ) {
			$this->check_permissions();
		}

		return isset( $this->permissions[ $type ] ) ? $this->permissions[ $type ] : [];
	}
}
