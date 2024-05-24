<?php

namespace OTGS\Toolset\Common\Rest\Plugin;

use OTGS\Toolset\Common\Utils\RequestMode;

/**
 * Types REST API integration.
 *
 * It registers groups into native post, term and user native endpoints, but only
 * for those objects that indeed have a Toolset meta fields group assigned.
 *
 * Example URLs:
 * site.com/wp-json/wp/v2/{post_type_slug}/{post_id}
 * site.com/wp-json/wp/v2/{taxonomy_slug}/{term_id}
 * site.com/wp-json/wp/v2/users/{user_id}
 *
 * @since 3.4
 */
class Types {


	/** @var \OTGS\Toolset\Common\Rest\Controller */
	private $manager;

	/** @var \OTGS\Toolset\Common\Rest\Utils */
	private $utils;

	/** @var \Toolset_Element_Factory */
	private $element_factory;

	/** @var bool */
	private $do_exposure_filters;


	/**
	 * Types REST API integration constructor.
	 *
	 * @param \OTGS\Toolset\Common\Rest\Controller $manager
	 * @param \OTGS\Toolset\Common\Rest\Utils $utils
	 * @param \Toolset_Element_Factory $element_factory
	 */
	public function __construct(
		\OTGS\Toolset\Common\Rest\Controller $manager,
		\OTGS\Toolset\Common\Rest\Utils $utils,
		\Toolset_Element_Factory $element_factory
	) {
		$this->manager = $manager;
		$this->utils = $utils;
		$this->element_factory = $element_factory;
	}


	/**
	 * Initialize the integration.
	 */
	public function initialize() {
		$this->register_fields();
	}


	/**
	 * Register Types meta fields groups as API fields.
	 */
	private function register_fields() {
		$domains = \Toolset_Element_Domain::all();
		foreach ( $domains as $field_domain ) {
			$this->register_groups_by_domain( $field_domain );
		}
	}


	/**
	 * Register Types meta fields as API fields, per domain,
	 * but only on objects that have a fields group assigned.
	 *
	 * @param string $domain
	 */
	private function register_groups_by_domain( $domain ) {
		$groups_args = array(
			'domain' => $domain,
			'is_active' => true,
		);
		$groups = apply_filters( 'types_query_groups', array(), $groups_args );
		$object = array();

		foreach ( $groups as $group ) {
			$group_object = $this->get_objects_by_domain( $domain, $group );
			$group_object = toolset_ensarr( $group_object, array( $group_object ) );
			$object = array_merge( $object, $group_object );
		}

		if ( ! empty( $object ) ) {
			$group_in_rest = array(
				'object' => $object,
				'name' => 'toolset-meta',
				'callbacks' => array(
					'get_callback' => $this->get_callback_by_domain( $domain ),
					'update_callback' => null,
					'schema' => null,
				),
			);

			$this->utils->register_field( $group_in_rest );
		}
	}


	/**
	 * Get list of objects associated with a given group, based on the group domain.
	 *
	 * @param string $domain
	 * @param \Toolset_Field_Group $group
	 *
	 * @return bool|string|array
	 */
	private function get_objects_by_domain( $domain, \Toolset_Field_Group $group ) {
		switch ( $domain ) {
			case \Toolset_Element_Domain::POSTS:
				if ( ! $group instanceof \Toolset_Field_Group_Post ) {
					return false;
				}
				$assigned_post_types = $group->get_assigned_to_types();

				if ( empty( $assigned_post_types ) ) {
					// See documentation for get_assigned_to_types() to find out more about this logic.
					if ( $group->has_special_purpose() ) {
						return array();
					}

					return get_post_types();
				}

				return $assigned_post_types;
			case \Toolset_Element_Domain::TERMS:
				if ( ! $group instanceof \Toolset_Field_Group_Term ) {
					return false;
				}

				$assigned_taxonomies = $group->get_associated_taxonomies();

				return empty( $assigned_taxonomies ) ? get_taxonomies() : $assigned_taxonomies;

			case \Toolset_Element_Domain::USERS:
				// FIXME use "get_associated_roles"
				return 'user';
		}

		return false;
	}


	/**
	 * Get the righ callback to get groups and their fields, per domain.
	 *
	 * @param string $domain
	 *
	 * @return bool|callable
	 */
	private function get_callback_by_domain( $domain ) {
		switch ( $domain ) {
			case \Toolset_Element_Domain::POSTS:
				return array( $this, 'get_post_fields' );
			case \Toolset_Element_Domain::TERMS:
				return array( $this, 'get_term_fields' );
			case \Toolset_Element_Domain::USERS:
				return array( $this, 'get_user_fields' );
		}

		return false;
	}


	/**
	 * Callback of post fields, grouped.
	 *
	 * @param array $object Details of current post.
	 * @param string $registered_group_name Name of the group registered as API field.
	 * @param \WP_REST_Request $request Current request.
	 *
	 * @return array
	 */
	public function get_post_fields(
		/** @noinspection PhpUnusedParameterInspection */
		$object, $registered_group_name, \WP_REST_Request $request
	) {
		$group_factory = \Toolset_Field_Group_Post_Factory::get_instance();

		try {
			$element = $this->element_factory->get_post_untranslated( $object['id'] );
		} catch ( \Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			return array();
		}
		$element_type = $element->get_type();

		$groups = $group_factory->get_groups_for_element( $element );
		$groups = $this->filter_groups_for_element( \Toolset_Element_Domain::POSTS, $groups, $object['id'], $element_type );

		$outcome = array();
		foreach ( $groups as $group ) {
			$group_fields_outcome = array();
			$fields_in_group = $group->get_field_definitions();
			$fields_in_group = $this->filter_fields_for_element(
				\Toolset_Element_Domain::POSTS, $group, $fields_in_group, $object['id'], $element_type
			);

			foreach ( $fields_in_group as $field ) {
				$group_fields_outcome[ $field->get_slug() ] = $this->render_field_by_type( $field, $element->get_id() );
			}

			$outcome[ $group->get_slug() ] = $group_fields_outcome;
		}

		return $outcome;
	}


	/**
	 * Callback of term fields, grouped.
	 *
	 * @param array $object Details of current term.
	 * @param string $registered_group_name Name of the group registered as API field.
	 * @param \WP_REST_Request $request Current request.
	 *
	 * @return array
	 */
	public function get_term_fields(
		/** @noinspection PhpUnusedParameterInspection */
		$object, $registered_group_name, \WP_REST_Request $request
	) {
		$element_type = $object['taxonomy'];

		$group_factory = \Toolset_Field_Group_Term_Factory::get_instance();
		$groups = $group_factory->get_groups_by_taxonomy( $object['taxonomy'] );
		$groups = $this->filter_groups_for_element( \Toolset_Element_Domain::TERMS, $groups, $object['id'], $element_type );
		$outcome = array();

		foreach ( $groups as $group ) {
			$group_fields_outcome = array();

			$fields_in_group = $group->get_field_definitions();
			$fields_in_group = $this->filter_fields_for_element(
				\Toolset_Element_Domain::TERMS, $group, $fields_in_group, $object['id'], $element_type
			);

			foreach ( $fields_in_group as $field ) {
				$group_fields_outcome[ $field->get_slug() ] = $this->render_field_by_type( $field, $object['id'] );
			}

			$outcome[ $group->get_slug() ] = $group_fields_outcome;
		}

		return $outcome;
	}


	/**
	 * Callback of user fields, grouped.
	 *
	 * @param array $object Details of current user.
	 * @param string $registered_group_name Name of the group registered as API field.
	 * @param \WP_REST_Request $request Current request.
	 *
	 * @return array
	 */
	public function get_user_fields(
		/** @noinspection PhpUnusedParameterInspection */
		$object, $registered_group_name, \WP_REST_Request $request
	) {
		// Roles are only inlcuded in an edit context.
		// https://developer.wordpress.org/rest-api/reference/users/#schema-roles
		$roles = toolset_getarr( $object, 'roles', false );
		if ( false === $roles ) {
			$user_meta = get_userdata( $object['id'] );
			$roles = $user_meta->roles;
		}

		$user_roles = ( is_array( $roles ) && ! empty( $roles ) )
			? reset( $roles )
			: '';

		if ( empty( $user_roles ) ) {
			return array();
		}

		$element_type = $user_roles;

		$group_factory = \Toolset_Field_Group_User_Factory::get_instance();
		$groups = $group_factory->get_groups_by_role( $user_roles );
		$groups = $this->filter_groups_for_element( \Toolset_Element_Domain::USERS, $groups, $object['id'], $element_type );

		$outcome = array();
		foreach ( $groups as $group ) {
			$group_fields_outcome = array();

			$fields_in_group = $group->get_field_definitions();
			$fields_in_group = $this->filter_fields_for_element(
				\Toolset_Element_Domain::USERS, $group, $fields_in_group, $object['id'], $element_type
			);

			foreach ( $fields_in_group as $field ) {
				$group_fields_outcome[ $field->get_slug() ] = $this->render_field_by_type( $field, $object['id'] );
			}

			$outcome[ $group->get_slug() ] = $group_fields_outcome;
		}

		return $outcome;
	}


	/**
	 * Obtain the field value, using the renderer for the REST API.
	 *
	 * @param \Toolset_Field_Definition $field_definition
	 * @param int $element_id
	 *
	 * @return array
	 */
	private function render_field_by_type( \Toolset_Field_Definition $field_definition, $element_id ) {
		return $field_definition
			->instantiate( $element_id )
			->get_renderer(
				\Toolset_Field_Renderer_Purpose::REST,
				RequestMode::ADMIN,
				array()
			)->render();
	}


	/**
	 * Apply filters to limit which field groups will be exposed for a particular element.
	 *
	 * @param string $domain
	 * @param \Toolset_Field_Group[] $groups
	 * @param int $element_id
	 * @param string $element_type
	 *
	 * @return \Toolset_Field_Group[]
	 */
	private function filter_groups_for_element( $domain, $groups, $element_id, $element_type ) {
		if( ! $this->should_do_exposure_filters() ) {
			return $groups;
		}

		return array_filter(
			$groups,
			function ( \Toolset_Field_Group $group ) use ( $domain, $element_id, $element_type ) {
				/**
				 * toolset_rest_expose_field_group
				 *
				 * Determine whether fields of a particular field group should be exposed in the REST API.
				 * The filter is applied only for field groups which actually belong to the element.
				 *
				 * @param bool $expose_field_group True by default.
				 * @param string $domain Domain of the field group: 'posts'|'users'|'terms'.
				 * @param string $group_slug Slug of the custom field group.
				 * @param mixed $element_type Type of the element for which we're deciding. Depending on the domain, this can be:
				 *     - post type slug
				 *     - taxonomy slug
				 *     - user role name or an array with user role names
				 * @param int $element_id ID of the element.
				 * @param \Toolset_Field_Group $group For internal use only. The instance of the field group model.
				 *
				 * @return bool True if the field group should be exposed.
				 * @since Types 3.3
				 */
				return (bool) apply_filters(
					'toolset_rest_expose_field_group',
					true,
					$domain,
					$group->get_slug(),
					$element_type,
					$element_id,
					$group
				);
			}
		);
	}


	/**
	 * Apply filters to limit which custom fields will be exposed for a particular element.
	 *
	 * @param string $domain
	 * @param \Toolset_Field_Group $group
	 * @param \Toolset_Field_Definition[] $fields
	 * @param int $element_id
	 * @param mixed $element_type
	 *
	 * @return array|\Toolset_Field_Definition[]
	 */
	private function filter_fields_for_element( $domain, \Toolset_Field_Group $group, $fields, $element_id, $element_type ) {
		if( ! $this->should_do_exposure_filters() ) {
			return $fields;
		}

		return array_filter(
			$fields,
			function( \Toolset_Field_Definition $field_definition ) use( $domain, $group,  $element_id, $element_type ) {
				/**
				 * toolset_rest_expose_field
				 *
				 * Determine whether a particular custom field should be exposed in the REST API.
				 * The filter is applied only for fields which actually belong to the element.
				 *
				 * @param bool $expose_field True by default.
				 * @param string $domain Domain of the field group: 'posts'|'users'|'terms'.
				 * @param string $group_slug Slug of the custom field group where the field belongs.
				 * @param string $field_slug Slug of the custom field.
				 * @param mixed $element_type Type of the element for which we're deciding. Depending on the domain, this can be:
				 *     - post type slug
				 *     - taxonomy slug
				 *     - user role name or an array with user role names
				 * @param int $element_id ID of the element.
				 * @param \Toolset_Field_Group $group For internal use only. The instance of the field group model.
				 * @param \Toolset_Field_Definition $field_definition For internal use only. The instance of the field definition model.
				 *
				 * @return bool True if the custom field should be exposed.
				 * @since Types 3.3
				 */
				return (bool) apply_filters(
					'toolset_rest_expose_field',
					true,
					$domain,
					$group->get_slug(),
					$field_definition->get_slug(),
					$element_type,
					$element_id,
					$group,
					$field_definition
				);
			}
		);
	}


	/**
	 * @return bool
	 */
	private function should_do_exposure_filters() {
		if( null === $this->do_exposure_filters ) {
			/**
			 * toolset_rest_run_exposure_filters
			 *
			 * Enable or disable filters for fine-tuning which custom field groups and custom fields
			 * will be exposed in the REST API.
			 *
			 * @param bool $run_filters Filtered value, false by default.
			 * @return bool
			 * @since Types 3.3
			 */
			$this->do_exposure_filters = (bool) apply_filters( 'toolset_rest_run_exposure_filters', false );
		}

		return $this->do_exposure_filters;
	}
}
