<?php

use OTGS\Toolset\Common\Utils\InMemoryCache;
use OTGS\Toolset\Common\WpPostFactory;
use OTGS\Toolset\Common\WpQueryFactory;

/**
 * Factory for the Toolset_Field_Group_User class.
 *
 * @since 2.0
 */
class Toolset_Field_Group_User_Factory extends Toolset_Field_Group_Factory {


	/**
	 * @return Toolset_Field_Group_User_Factory
	 * @noinspection SenselessProxyMethodInspection
	 */
	public static function get_instance() {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return parent::get_instance();
	}


	/**
	 * Toolset_Field_Group_User_Factory constructor.
	 *
	 * @param WpQueryFactory|null $wp_query_factory
	 * @param WpPostFactory|null $wp_post_factory
	 * @param InMemoryCache|null $cache
	 */
	public function __construct(
		WpQueryFactory $wp_query_factory = null, WpPostFactory $wp_post_factory = null, InMemoryCache $cache = null
	) {
		parent::__construct( $wp_query_factory, $wp_post_factory, $cache );

		add_action( 'wpcf_group_updated', array( $this, 'on_group_updated' ), 10, 2 );
	}


	/**
	 * Load a field group instance.
	 *
	 * @param int|string|WP_Post $field_group Post ID of the field group, it's name or a WP_Post object.
	 *
	 * @return null|Toolset_Field_Group_User Field group or null if it can't be loaded.
	 */
	public static function load( $field_group ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return Toolset_Field_Group_User_Factory::get_instance()->load_field_group( $field_group );
	}


	/**
	 * Create new field group.
	 *
	 * @param string $name Sanitized field group name. Note that the final name may change when new post is inserted.
	 * @param string $title Field group title.
	 *
	 * @return null|Toolset_Field_Group The new field group or null on error.
	 */
	public static function create( $name, $title = '' ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Toolset_Field_Group_Term_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return static::get_instance()->create_field_group( $name, $title );
	}


	public function get_post_type() {
		return Toolset_Field_Group_User::POST_TYPE;
	}


	protected function get_field_group_class_name() {
		return 'Toolset_Field_Group_User';
	}

	private $roles_assignment_cache = null;

	/**
	 * Get the roles registered on the site, in a slug -> label array.
	 *
	 * @return array
	 * @since 3.1
	 */
	private function get_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$available_roles_names = $wp_roles->get_names();

		return $available_roles_names;
	}

	/**
	 * Get all field groups sorted by their association with roles.
	 *
	 * @return Toolset_Field_Group_User[][] For each role, there will be an array element, which is
	 *     an array of user field groups associated to it.
	 * @since 3.1
	 */
	public function get_groups_by_roles() {
		if ( null == $this->roles_assignment_cache ) {
			$groups = $this->query_groups();
			$roles = $this->get_roles();

			$this->roles_assignment_cache = array();
			foreach( $roles as $role_slug => $role_label ) {
				$groups_for_role = array();

				foreach( $groups as $group ) {
					if ( $group instanceof Toolset_Field_Group_User
						&& $group->is_active()
						&& $group->has_associated_role( $role_slug )
					) {
						$groups_for_role[] = $group;
					}
				}

				$this->roles_assignment_cache[ $role_slug ] = $groups_for_role;
			}
		}

		return $this->roles_assignment_cache;
	}

	/**
	 * Get array of groups that are associated with given role.
	 *
	 * @param string $role Slug of the role.
	 * @return Toolset_Field_Group_User[] Associated user field groups.
	 * @since 3.1
	 */
	public function get_groups_by_role( $role ) {
		$groups_by_roles = $this->get_groups_by_roles();
		return toolset_ensarr( toolset_getarr( $groups_by_roles, $role ) );
	}


	/**
	 * This needs to be executed whenever an usermeta field group is updated.
	 *
	 * Hooked into the wpcf_group_updated action.
	 * Erases cache for the get_groups_by_roles() method.
	 *
	 * @param int $group_id Ignored
	 * @param Toolset_Field_Group $group Field group that has been just updated.
	 * @since 3.1
	 */
	public function on_group_updated( /** @noinspection PhpUnusedParameterInspection */ $group_id = null, $group = null ) {
		if ( $group instanceof Toolset_Field_Group_User ) {
			$this->roles_assignment_cache = null;
		}
	}


	/**
	 * Retrieve groups that should be displayed with a certain element, taking all possible conditions into account.
	 *
	 * @param IToolset_Element $element Element of the domain matching the field group.
	 *
	 * @throws RuntimeException Until the method is implemented for this domain.
	 */
	public function get_groups_for_element( IToolset_Element $element ) {
		throw new RuntimeException( 'Not implemented.' );
	}


	/**
	 * @inheritdoc
	 * @return string
	 * @since 3.4
	 */
	public function get_domain() {
		return Toolset_Element_Domain::USERS;
	}

}
