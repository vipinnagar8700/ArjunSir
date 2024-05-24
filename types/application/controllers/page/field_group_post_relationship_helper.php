<?php

use OTGS\Toolset\Common\Wordpress\Version;
use OTGS\Toolset\Common\Wordpress\WpSafeRedirect;

/**
 * Helper class that handles interoperability between the Edit Post Field Group page and Relationships page
 * when editing association fields.
 *
 * When the user wants to edit association fields on the Relationships page, they're redirected to the Edit Post Field
 * Group page and after saving, they're redirected back.
 *
 * @since m2m
 */
class Types_Page_Field_Group_Post_Relationship_Helper {

	// Names of GET arguments
	const GROUP_ID_KEY = 'group_id';

	const RELATIONSHIP_ID_KEY = 'relationship_id';

	/**
	 * Number of associations fixed each loop of the batch process
	 *
	 * @param int
	 */
	const ASSOCIATIONS_BATCH_LIMIT = 50;

	/**
	 * @var bool If true, it means that we're editing the field group of an intermediary post type - that means
	 *      we're editing association fields.
	 */
	private $is_actionable = false;

	/** @var Toolset_Relationship_Definition_Repository */
	private $relationship_definition_repository;

	/** @var Toolset_Field_Group_Post_Factory */
	private $field_group_factory;

	/** @var Types_Admin_Menu */
	private $admin_menu;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;

	/**
	 * Group object
	 *
	 * @var Toolset_Field_Group_Post|null
	 */
	private $group;


	/**
	 * Types_Page_Field_Group_Post_Relationship_Helper constructor.
	 *
	 * @param Toolset_Relationship_Definition_Repository|null $relationship_definition_repository_di
	 * @param Toolset_Field_Group_Post_Factory|null $field_group_post_factory_di
	 * @param Types_Admin_Menu|null $admin_menu_di
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 */
	public function __construct(
		Toolset_Relationship_Definition_Repository $relationship_definition_repository_di = null,
		Toolset_Field_Group_Post_Factory $field_group_post_factory_di = null,
		Types_Admin_Menu $admin_menu_di = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null
	) {
		// This can be instantiated only after m2m API is loaded in initialize().
		$this->relationship_definition_repository = $relationship_definition_repository_di;
		$this->field_group_factory = $field_group_post_factory_di ? : Toolset_Field_Group_Post_Factory::get_instance();
		$this->admin_menu = $admin_menu_di ? : Types_Admin_Menu::get_instance();
		$this->post_type_repository = $post_type_repository_di ? : Toolset_Post_Type_Repository::get_instance();
	}


	/**
	 * Determine whether we're editing association fields of an existing relationship.
	 *
	 * This needs to be called early on the Edit Post Field Group page.
	 */
	public function initialize() {
		$is_m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );
		$is_association_field_group = array_key_exists( self::RELATIONSHIP_ID_KEY, $_GET );

		// Checks if the group belongs to an intermediary.
		$is_association_field_group = $this->group_belongs_to_intermediary( $is_association_field_group );

		$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( ! $is_m2m_enabled || ( ! $is_ajax && ! $is_association_field_group ) ) {
			return;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$this->is_actionable = true;

		if ( null === $this->relationship_definition_repository ) {
			$this->relationship_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		if ( ! $is_association_field_group && null === $this->get_relationship_definition() ) {
			$this->is_actionable = false;
		}

	}


	/**
	 * Checks the purpose of the group field
	 *
	 * @param Boolean $is_association_field_group The previous value.
	 *
	 * @return Boolean
	 * @since m2m
	 */
	private function group_belongs_to_intermediary( $is_association_field_group ) {
		$group = $this->field_group_factory->load_field_group( $this->get_field_group_id() );

		if ( $group instanceof Toolset_Field_Group_Post ) {
			$this->group = $group;

			if ( $group->get_purpose() === Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS ) {
				$is_association_field_group = true;
			}
		}

		return $is_association_field_group;
	}


	/**
	 * Gets the relationship slug, it may came from an URL parameter or from the assigned types,
	 * but if don't it has to check the assigned types of the relationship in order to know if the
	 * only assinged type is an intermediary post type.
	 *
	 * @return string|null
	 * @since 2.3
	 */
	private function get_returning_relationship_slug() {
		if ( ( $relationship_id = toolset_getget( self::RELATIONSHIP_ID_KEY ) ) ) {
			$relationship = Toolset_Relationship_Definition_Repository::get_instance()
				->get_definition_by_row_id( $relationship_id );
			if ( $relationship ) {
				return $relationship->get_slug();
			}
		} elseif ( $this->group ) {
			$assigned_types = $this->group->get_assigned_to_types();
			// If there is more than one type it is not an intermediary post type.
			if ( count( $assigned_types ) !== 1 ) {
				return null;
			}
			$post_type_slug = $assigned_types[0];
			$post_type_repository = Toolset_Post_Type_Repository::get_instance();
			if ( ! $post_type_object = $post_type_repository->get( $post_type_slug ) ) {
				return null;
			}

			if ( $post_type_object->is_intermediary() ) {
				// Find a relationship having this intermediary post.
				$definitions = Toolset_Relationship_Definition_Repository::get_instance()->get_definitions();
				foreach ( $definitions as $definition ) {
					if ( $definition->get_intermediary_post_type() === $post_type_slug ) {
						return $definition->get_slug();
					}
				}
			}
		}

		return null;
	}


	private function get_field_group_id() {
		return (int) toolset_getget( self::GROUP_ID_KEY );
	}


	private function is_new_field_group_page() {
		return ( $this->get_field_group_id() === 0 );
	}


	/**
	 * @return IToolset_Relationship_Definition|null
	 */
	private function get_relationship_definition() {
		$relationship_slug = $this->get_returning_relationship_slug();
		if ( ! is_string( $relationship_slug ) || empty( $relationship_slug ) ) {
			return null;
		}

		return $this->relationship_definition_repository->get_definition( $relationship_slug );
	}


	/**
	 * Retrieve the intermediary post type.
	 *
	 * If it doesn't exist, create a new one and assign it to the relationship definition.
	 *
	 * @param Toolset_Relationship_Definition $relationship
	 *
	 * @return string Post type slug.
	 */
	private function ensure_intermediary_post_type( $relationship ) {
		$post_type = $relationship->get_intermediary_post_type();

		if ( null !== $post_type && $this->post_type_repository->has( $post_type ) ) {
			return $post_type;
		}

		/** @var Toolset_Relationship_Driver $relationship_driver */
		$relationship_driver = $relationship->get_driver();

		$new_post_type_slug = $relationship_driver->create_intermediary_post_type( $post_type );

		$this->relationship_definition_repository->persist_definition( $relationship );

		return $new_post_type_slug;
	}


	/**
	 * Create a new post field group for association fields.
	 *
	 * @param Toolset_Relationship_Definition $relationship
	 *
	 * @return Toolset_Field_Group_Post|null
	 */
	private function create_post_field_group( $relationship ) {
		$group_slug = $relationship->get_slug() . '_fields';
		$group_title = sprintf(
			__( 'Fields for the "%s" relationship', 'wpcf' ),
			$relationship->get_display_name()
		);

		$field_group = $this->field_group_factory->create_field_group(
			$group_slug, $group_title, 'publish', Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS
		);

		if ( ! $field_group instanceof Toolset_Field_Group_Post ) {
			return null;
		}

		return $field_group;
	}


	/**
	 * Redirect to an Edit Post Field Group page for association fields.
	 *
	 * @param string $relationship_slug Slug of the relationship whose association fields will be edited.
	 * @param int $group_id ID of the field group.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	private function redirect_to_field_group_page( $relationship_slug, $group_id ) {
		$edit_url = $this->admin_menu->get_page_url( Types_Admin_Menu::LEGACY_PAGE_EDIT_POST_FIELD_GROUP );

		$args = array(
			self::GROUP_ID_KEY => (int) $group_id,
		);

		$redirect = new WpSafeRedirect( new Version() );
		if( $redirect->wp_safe_redirect( esc_url_raw( add_query_arg( $args, $edit_url ) ) ) ) {
			exit;
		}
	}


	/**
	 * When there's no intermediary post type or when it doesn't have a field group assigned
	 * and the user clicks on the Edit Fields button on the Edit Relationship screen,
	 * they will be redirected to the Edit Post Field Group page with group ID "0", which
	 * means a new field group will be created.í
	 *
	 * We will make it significantly simpler for the user:
	 * - Create (and save) the field group right away with pre-defined name and slug.
	 * - Assign it to the intermediary post type of the relationship (which will also be created if it doesn't exist
	 * yet).
	 * - Redirect (again) to the Edit Post Field Group page, this time with the ID of the new group.
	 */
	public function handle_association_field_group_creation() {
		if (
			! $this->is_actionable
			|| ! $this->is_new_field_group_page()
		) {
			return;
		}

		/** @var Toolset_Relationship_Definition $relationship */
		$relationship = $this->get_relationship_definition();

		$field_group = $this->create_post_field_group( $relationship );

		if ( null === $field_group ) {
			return;
		}

		$intermediary_post_type = $this->ensure_intermediary_post_type( $relationship );

		$field_group->assign_post_type( $intermediary_post_type );

		$this->redirect_to_field_group_page( $relationship->get_slug(), $field_group->get_id() );
	}


	/**
	 * For association field groups, hide the Delete link.
	 *
	 * @return bool
	 */
	public function is_field_group_deletion_forbidden() {
		return $this->is_actionable;
	}


	/**
	 * For association field groups, don't allow the user to select where this group will be displayed.
	 *
	 * That is already fixed to the intermediary post type. Instead, display a static text with
	 * relevant information.
	 *
	 * @return false|string False if no overriding should take place, escaped HTML output otherwise.
	 */
	public function maybe_override_group_usage_pseudometabox() {
		if ( ! $this->is_actionable ) {
			return false;
		}

		/** @var Toolset_Relationship_Definition $relationship */
		$relationship = $this->get_relationship_definition();

		return sprintf(
			esc_html__( 'This field group will be used exclusively for the %s post type which acts as a placeholder for custom fields of the relationship %s.', 'wpcf' ),
			'<strong>' . esc_html( $relationship->get_intermediary_post_type() ) . '</strong>',
			'<strong>' . esc_html( $relationship->get_display_name() ) . '</strong>'
		);
	}


	/**
	 * Get relationship edit url
	 *
	 * @return false|string
	 *
	 * @since 3.2
	 */
	public function get_relationship_edit_url() {
		if ( ! $relationship = $this->get_relationship_definition() ) {
			// no relationship for the field group
			return false;
		}

		$relationship_slug = $relationship->get_slug();

		$url = $this->admin_menu->get_page_url( Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS );

		return add_query_arg(
			array(
				'action' => 'edit',
				'slug' => $relationship_slug,
			),
			$url
		);
	}


	/**
	 * When we augmented the GUI for post type assignment, the legacy saving mechanism doesn't get any value and
	 * defaults to "all post types", erasing the original assignment to the intermediary post type that
	 * we wanted to preserve and enforce. This obviously needs to be prevented.
	 *
	 * @return bool
	 */
	public function allow_saving_post_type_assignments() {
		return ( ! $this->is_actionable );
	}


	/**
	 * If a relationship is created without an intermediary post type and during editing a group field is added,
	 * it is neccesary to create it.
	 *
	 * @param int $group_id Group ID.
	 *
	 * @since 2.3
	 */
	public function create_intermediary_group_if_needed( $group_id ) {
		$relationship_id = toolset_getget( self::RELATIONSHIP_ID_KEY );
		if ( $relationship_id ) {
			do_action( 'toolset_do_m2m_full_init' );
			$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
			$definition = $definition_repository->get_definition_by_row_id( $relationship_id );
			$field_definitions = $definition->get_association_field_definitions();
			if ( $definition
				&& ( ! $definition->get_intermediary_post_type()
					|| ( $definition->get_intermediary_post_type() && empty( $field_definitions ) )
				)
			) {
				$intermediary_post_type = $definition->get_driver()
					->create_intermediary_post_type( $definition->get_slug(), false );
				$definition_repository->persist_definition( $definition );
				$field_group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( Toolset_Element_Domain::POSTS );
				$field_group = $field_group_factory->load_field_group( $group_id );
				$field_group->set_purpose( Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS );
				update_post_meta( $group_id, '_wp_types_group_post_types', $intermediary_post_type );
			}
		}
	}


	/**
	 * Gets the number of associations without intermediary post belonging to a relationship
	 *
	 * @return int
	 * @since 2.3
	 */
	public function get_number_associations_without_intermediary_posts() {
		$relationship = $this->get_relationship_definition();

		if ( $relationship ) {
			$query = new Toolset_Association_Query_V2();

			return $query
				->add( $query->relationship( $relationship ) )
				->add( $query->not( $query->has_intermediary_id() ) )
				->get_found_rows_directly();
		}

		return 0;
	}


	/**
	 * Creates empty associations intermediary posts when they don't exist
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return int Number of remaining associations without internemdiary posts.
	 * @since 2.3
	 */
	public function create_empty_associations_intermediary_posts( $group_id ) {
		$group = $this->field_group_factory->load_field_group( $group_id );

		if ( $group instanceof Toolset_Field_Group_Post ) {
			$this->group = $group;
		}

		if ( $this->group ) {
			$definition = $this->get_relationship_definition();
			$intermediary_post_persistence = new Toolset_Association_Intermediary_Post_Persistence( $definition );
			$intermediary_post_persistence->create_empty_associations_intermediary_posts( self::ASSOCIATIONS_BATCH_LIMIT );
		}

		return $this->get_number_associations_without_intermediary_posts();
	}
}
