<?php

/**
 * Abstract factory for the item attribute GUI selector.
 *
 * Extended depending on the relationship cardinality.
 *
 * @since m2m
 */
abstract class Toolset_Shortcode_Attr_Item_Gui_Base {


	/**
	 * @var Toolset_Relationship_Definition
	 */
	protected $relationship_definition;


	/**
	 * @var null|WP_Post_Type
	 */
	protected $current_post_object;


	/**
	 * @var string
	 */
	protected $option_name;


	/**
	 * @var Toolset_Post_Type_Repository
	 */
	protected $post_type_repository;


	/**
	 * @var string[]
	 */
	protected $parent_types;


	/**
	 * @var string[]
	 */
	protected $child_types;


	/**
	 * @var string
	 */
	protected $intermediary_type;


	/**
	 * @var string[]
	 */
	protected $options = array();


	function __construct(
		Toolset_Relationship_Definition $relationship_definition,
		$current_post_object,
		$option_name,
		Toolset_Post_Type_Repository $post_type_repository
	) {
		$this->relationship_definition = $relationship_definition;
		$this->current_post_object = $current_post_object;
		$this->option_name = $option_name;
		$this->post_type_repository = $post_type_repository;

		$this->parent_types = $relationship_definition->get_parent_type()->get_types();
		$this->child_types = $relationship_definition->get_child_type()->get_types();
		$this->intermediary_type = $relationship_definition->get_intermediary_post_type();

		$this->set_options();
	}


	/**
	 * Get the option name attribute.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_option_name() {
		return $this->option_name;
	}


	/**
	 * Get the generated options.
	 *
	 * @return string[]
	 *
	 * @since m2m
	 */
	public function get_options() {
		return $this->options;
	}


	/**
	 * Get the label of a given role in the current relationship.
	 *
	 * @param string $role
	 * @param bool $singular
	 *
	 * @return null|string
	 *
	 * @since m2m
	 */
	public function get_post_type_label_by_role( $role, $singular = false ) {
		$types_for_role = $this->relationship_definition->get_element_type( $role )->get_types();
		$first_type_for_role = reset( $types_for_role );

		$post_type_for_role = $this->post_type_repository->get( $first_type_for_role );

		if ( null === $post_type_for_role ) {
			return null;
		}

		$requested_label_name = ( $singular )
			? Toolset_Post_Type_Labels::SINGULAR_NAME
			: Toolset_Post_Type_Labels::NAME;

		return $post_type_for_role->get_label( $requested_label_name );
	}


	/**
	 * Define options, to be implemnted by children classes.
	 *
	 * @since m2m
	 */
	abstract protected function set_options();

	/**
	 * Compose documentation links and add the GA arguments.
	 *
	 * @param string $url
	 * @param array $args
	 * @param string $anchor
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	protected function get_documentation_link( $url = '', $args = array(), $anchor = '' ) {
		if ( ! empty( $args ) ) {
			$url = esc_url( add_query_arg( $args, $url ) );
		}
		if ( ! empty( $anchor ) ) {
			$url .= '#' . esc_attr( $anchor );
		}
		return $url;
	}

	/**
	 * Check whether we are editing a Content Template,
	 * either in its native editor page, or somewhere else.
	 *
	 * @return bool
	 */
	protected function is_editing_a_content_template() {
		// Native CT editor
		if (
			is_admin()
			&& in_array( toolset_getget('page'), array( 'ct-editor' ), true )
		) {
			return true;
		}

		// AJAX callback to generate the Views shortcodes dialog
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			&& 'wpv_shortcode_gui_dialog_create' === toolset_getget('action')
		) {
			// AJAX callback from the native CT editor
			if ( in_array( toolset_getget('get_page'), array( 'ct-editor' ), true ) ) {
				return true;
			}
			// AJAX callback from any user editor page
			if ( Toolset_Post_Type_List::CONTENT_TEMPLATE === get_post_type( toolset_getget('post_id') ) ) {
				return true;
			}
		}

		// CT edited with any user editor in the native post edit page
		global $pagenow;
		if (
			in_array( $pagenow, array( 'post.php' ), true )
			&& Toolset_Post_Type_List::CONTENT_TEMPLATE === get_post_type( toolset_getget('post') )
		) {
			return true;
		}

		// Somewhere else where the global post is a CT
		global $post;
		if (
			isset( $post )
			&& ( $post instanceof WP_Post )
			&& Toolset_Post_Type_List::CONTENT_TEMPLATE === $post->post_type
		) {
			return true;
		}

		return false;
	}

}
