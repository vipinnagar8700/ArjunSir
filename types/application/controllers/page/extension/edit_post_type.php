<?php

use OTGS\Toolset\Common\Relationships\API\RelationshipQuery;

/**
 * This controller extends all post edit pages
 *
 * @since 2.0
 */
final class Types_Page_Extension_Edit_Post_Type {

	const POST_TYPE_PARAMETER = 'wpcf-post-type';
	const METABOX_PREFIX = 'types-related-content-';


	/**
	 * Admin menu
	 *
	 * @var Types_Admin_Menu
	 */
	private $admin_menu;


	/** @var Toolset_Relationship_Query_Factory|null */
	private $_query_factory;

	/**
	 * Output template repository
	 *
	 * @var Types_Output_Template_Repository
	 */
	private $output_template;

	/**
	 * Renderer
	 *
	 * @var Toolset_Renderer
	 */
	private $renderer;

	/**
	 * Post Type Repository
	 *
	 * @var Toolset_Post_Type_Repository
	 */
	private $post_type_repository;

	/**
	 * Is M2M enabled
	 *
	 * @var boolean
	 */
	private $is_m2m_enabled;

	private static $instance;

	/**
	 * @var Toolset_Relationship_Query_Factory|null
	 */
	private $relationship_query_factory;


	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Constructor
	 *
	 * @param Types_Admin_Menu                   $admin_menu_di For testing purposes.
	 * @param Types_Output_Template_Repository   $output_template_di For testing purposes.
	 * @param Toolset_Renderer                   $renderer_di For testing purposes.
	 * @param boolean                            $do_prepare For testing purposes.
	 * @param Toolset_Post_Type_Repository       $post_type_repository_di For testing purposes.
	 * @param Toolset_Relationship_Query_Factory $relationship_query_factory_di For testing purposes.
	 * @since m2m
	 */
	public function __construct(
		Types_Admin_Menu $admin_menu_di = null,
		Types_Output_Template_Repository $output_template_di = null,
		Toolset_Renderer $renderer_di = null,
		$do_prepare = true,
		Toolset_Post_Type_Repository $post_type_repository_di = null,
		Toolset_Relationship_Query_Factory $relationship_query_factory_di = null
	) {

		$this->is_m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );

		$this->admin_menu = $admin_menu_di ?: Types_Admin_Menu::get_instance();
		$this->output_template = $output_template_di ?: Types_Output_Template_Repository::get_instance();
		$this->renderer = $renderer_di ?: Toolset_Renderer::get_instance();
		$this->post_type_repository = $post_type_repository_di ?: Toolset_Post_Type_Repository::get_instance();
		$this->relationship_query_factory = null === $relationship_query_factory_di && $this->is_m2m_enabled ? new Toolset_Relationship_Query_Factory() : $relationship_query_factory_di;

		if ( $do_prepare ) {
			if ( ! isset( $_GET['wpcf-post-type'] ) ) {
				return;
			}

			$post_type_slug = sanitize_text_field( $_GET['wpcf-post-type'] );

			Types_Helper_Placeholder::set_post_type( $post_type_slug );
			Types_Helper_Condition::set_post_type( $post_type_slug );

			$this->prepare();
		}
	}

	private function __clone() { }


	public function prepare() {
		// documentation urls
		Types_Helper_Url::load_documentation_urls();

		// set analytics medium
		Types_Helper_Url::set_medium( 'cpt_editor' );
	}


	/**
	 * Echoes the relationship metabox in the post type editing page.
	 *
	 * @param bool|null $echo If this is anything but FALSE, print the metabox markup. Note that null must also
	 *     be handled as true, because of the way this is called by do_meta_boxes().
	 *
	 * @return string
	 * @since m2m
	 */
	public function metabox_relationships( $echo = true ) {
		$post_type_slug = toolset_getget( self::POST_TYPE_PARAMETER );

		$template_repository = $this->output_template;
		$renderer = $this->renderer;

		if ( ! $post_type_slug ) {
			$html = $renderer->render(
				$template_repository->get( Types_Output_Template_Repository::POST_TYPE_METABOX_RELATIONSHIPS_UNSAVED ),
				array(),
				false // do not print but return the result
			);
		} else {
			$post_type = $this->post_type_repository->get( $post_type_slug );

			if ( $this->is_m2m_enabled && $post_type && $post_type->is_intermediary() ) {
				$query = $this->relationship_query_factory->relationships_v2();
				$results = $query
					->add( $query->has_domain( Toolset_Element_Domain::POSTS ) )
					->add( $query->intermediary_type( $post_type_slug ) )
					->get_results();

				if ( ! empty( $results ) ) {
					$edit_link = add_query_arg(
						array(
							'action' => 'edit',
							'slug' => $results[0]->get_slug(),
						),
						$this->admin_menu->get_page_url( Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS )
					);
					$context = array(
						'link' => $edit_link,
						'name' => $results[0]->get_display_name(),
					);
					$html = $renderer->render(
						$template_repository->get( Types_Output_Template_Repository::POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY ),
						$context,
						false // do not print but return the result
					);
				} else {
					// This post type is an "orphan" of sorts - marked as an intermediary post type but
					// there's no relationship it belongs to.
					$html = $renderer->render(
						$template_repository->get( Types_Output_Template_Repository::POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY_ORPHAN ),
						[ 'troubleshooting_page_link' => esc_attr( add_query_arg( [ 'page' => Toolset_Menu::TROUBLESHOOTING_PAGE_SLUG ], 'admin.php' ) ) ],
						false // do not print but return the result
					);
				}
			} else {
				$relationships_page_url = $this->admin_menu->get_page_url( Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS );

				$context = array(
					'createRelationshipURL' => add_query_arg(
						array(
							'action' => 'add_new',
						),
						$relationships_page_url
					),
					'relationships' => $this->get_relationships_data_for_post_type( $post_type_slug ),
					// translators: URL; icon.
					'relationshipsPageNotice' => sprintf(
						__( 'You can manage post relationships on the new <a href="%s" target="_blank">Relationships page</a>%s', 'wpcf' ),
						esc_url( $relationships_page_url ),
						'<span class="dashicons dashicons-external" style="font-size: 100%;"></span>' ),
				);

				$html = $renderer->render(
					$template_repository->get( Types_Output_Template_Repository::POST_TYPE_METABOX_RELATIONSHIPS ),
					$context,
					false // do not print but return the result
				);
			}
		}

		// Careful, null also means we should echo it.
		if ( false !== $echo ) {
			echo $html;
		}

		return $html;
	}


	/**
	 * Gets and formats the relationships related to a post type
	 *
	 * @param string $post_type Post type slug.
	 * @return array
	 * @since m2m
	 */
	private function get_relationships_data_for_post_type( $post_type ) {
		$results = $this->get_relationships_by_post_type( $post_type );
		$relationships = array();
		foreach ( $results as $relationship ) {
			$type = $relationship->get_cardinality()->get_type();
			$edit_link = add_query_arg(
				array(
					'action' => 'edit',
					'slug' => $relationship->get_slug(),
				),
				$this->admin_menu->get_page_url( Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS )
			);
			$relationships[] = array(
				'slug' => $relationship->get_slug(),
				'type' => $type,
				'typeFormatted' => ucfirst( $type ),
				'editRelationshipLink' => '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $relationship->get_display_name() ) . '</a>',
				'postTypes' => $this->get_related_post_types( $relationship, $post_type ),
				'visible' => $this->is_metabox_visible( $relationship, $post_type ),
			);
		}
		return $relationships;
	}


	/**
	 * Gets the relationships related to a post type
	 *
	 * @param string $post_type Post type slug.
	 * @return array
	 * @since m2m
	 */
	private function get_relationships_by_post_type( $post_type ) {
		if ( ! $this->is_m2m_enabled ) {
			return array();
		}
		$query = $this->get_relationship_query();

		return $query
			->add( $query->has_domain_and_type( $post_type, 'posts' ) )
			->get_results();
	}


	/**
	 * Gets relationship query
	 *
	 * @return RelationshipQuery
	 * @since m2m
	 */
	private function get_relationship_query() {
		if( null === $this->_query_factory ) {
			$this->_query_factory = new Toolset_Relationship_Query_Factory();
		}
		return $this->_query_factory->relationships_v2();
	}


	/**
	 * Returns if a metabox is visible for the given post type.
	 *
	 * @param Toolset_Relationship_Definition $relationship Relationship.
	 * @param string $post_type_slug Post type related to.
	 *
	 * @return boolean
	 * @since m2m
	 */
	private function is_metabox_visible( $relationship, $post_type_slug ) {
		$post_type = $this->post_type_repository->get( $post_type_slug );
		if ( ! $post_type ) {
			return false;
		}

		if (
			$post_type instanceof IToolset_Post_Type_Registered
			&& ! $post_type instanceof IToolset_Post_Type_From_Types
		) {
			// For post types that are not under our control (at least not yet),
			// we definitely want to display the related metabox for every relationship that involves them.
			//
			// This can happen when editing a built-in post for the first time, for example.
			return true;
		}

		return (
			$post_type instanceof IToolset_Post_Type_From_Types
			&& ! in_array( $relationship->get_slug(), $post_type->get_hidden_related_content_metaboxes(), true )
		);
	}

	/**
	 * The a list of post types linked to editing post type page
	 *
	 * @param Toolset_Relationship_Definition $relationship Relationship.
	 * @param string $post_type Post type related to.
	 * @return string A comma-separated list (for display purposes, contains HTML).
	 * @since m2m
	 */
	private function get_related_post_types( $relationship, $post_type ) {
		$types = array();
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			$role_types = $relationship->get_element_type( $role )->get_types();
			if ( ! in_array( $post_type, $role_types, true ) ) {
				$types = $role_types;
			}
		}
		$post_types = array();
		foreach ( $types as $type ) {
			$post_type_object = get_post_type_object( $type );
			if ( ! $post_type_object ) {
				continue;
			}

			$edit_post_link = add_query_arg(
				array(
					self::POST_TYPE_PARAMETER => $type,
				),
				$this->admin_menu->get_page_url( Types_Admin_Menu::LEGACY_PAGE_EDIT_POST_TYPE )
			);
			$post_types[] = '<a href="' . esc_url( $edit_post_link ) . '">' . esc_html( $post_type_object->labels->name ) . '</a>';
		}
		return implode( ', ', $post_types );
	}


	/**
	 * Shows Relationships metaboxes in editing post page
	 *
	 * @param string $post_type_slug Post type slug.
	 * @param array $relationships_to_show Relationships list.
	 * @param bool $delay_update If set to true, the post type will be updated during the shutdown action.
	 *
	 * @since m2m
	 */
	public function show_relationships_in_post( $post_type_slug, $relationships_to_show, $delay_update = false ) {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		$post_type = $this->post_type_repository->get( $post_type_slug );
		if ( ! $post_type instanceof IToolset_Post_Type_From_Types ) {
			// The post type definition can't be found, do nothing.
			return;
		}
		/** @var IToolset_Post_Type_From_Types $post_type */

		$relationships_slugs = array();
		foreach ( $this->get_relationships_by_post_type( $post_type_slug ) as $relationship ) {
			$relationships_slugs[] = $relationship->get_slug();
		}
		$relationships_to_hide = array_diff( $relationships_slugs, $relationships_to_show );

		if ( $delay_update ) {
			// This may be necessary when this is calling during the saving of the Edit Post Type page.
			// The legacy implementation of that page directly overwrites the option with post type
			// definitions and our changes wouldn't be persisted at this point.
			//
			// Instead, we wait for the legacy disaster to be done with the option and
			// then do things properly here.
			add_action( 'shutdown', function () use ( $post_type_slug, $relationships_to_hide ) {
				$this->post_type_repository->refresh_all_post_types();
				$post_type = $this->post_type_repository->get( $post_type_slug );
				if ( ! $post_type instanceof IToolset_Post_Type_From_Types ) {
					return;
				}
				$post_type->set_hidden_related_content_metaboxes( $relationships_to_hide );
				$this->post_type_repository->save( $post_type );
			} );
		} else {
			$post_type->set_hidden_related_content_metaboxes( $relationships_to_hide );
			$this->post_type_repository->save( $post_type );
		}

		// Earlier, we used to mistakenly store this setting only per current user (which is probably
		// always the admin).
		//
		// Now, let's remove the settings for displaying the Related Content metaboxes for the current user,
		// if they have any preferences (other users still have to manually display the metabox
		// from screen options if they have it hidden, we can't update them all because that
		// doesn't scale and it may be entirely undesired).
		$hidden_metaboxes = $this->get_hidden_metaboxes_per_user( $post_type_slug );
		if ( ! is_array( $hidden_metaboxes ) ) {
			// We must not manipulate the "metaboxhidden_" usermeta if it's not an array (but an empty string by default).
			// Empty string is interpreted as "default behaviour" while an empty array means "show all metaboxes",
			// including ones that we don't want to show by default (native slugdiv, custom fields, etc.)
			return;
		}

		foreach ( $hidden_metaboxes as $i => $metabox ) {
			if ( false !== strpos( $metabox, self::METABOX_PREFIX ) ) {
				unset( $hidden_metaboxes[ $i ] );
			}
		}

		update_user_meta( get_current_user_id(), 'metaboxhidden_' . $post_type_slug, $hidden_metaboxes );
	}


	/**
	 * Gets hidden metaboxes
	 *
	 * @param string $post_type Post type slug.
	 * @return array
	 * @since m2m
	 */
	private function get_hidden_metaboxes_per_user( $post_type ) {
		return get_user_meta( get_current_user_id(), 'metaboxhidden_' . $post_type, true );
	}
}
