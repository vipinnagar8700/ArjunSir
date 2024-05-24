<?php

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Stores relationship settings
 *
 * These options are:
 *   - What fields (columns) will be displayed in related content
 *
 * @since m2m
 */
class Types_Post_Type_Relationship_Settings {

	/**
	 * WP option name pattern
	 *
	 * @var string
	 * @since m2m
	 */
	const OPTION_NAME_PATTERN = 'wpcf-listing-fields-%s-%d';


	/**
	 * WP option name
	 *
	 * @var string
	 * @since m2m
	 */
	protected $option_name;


	/**
	 * Fields displayed in related content
	 *
	 * @var string[]
	 * @since m2m
	 */
	protected $fields_displayed;


	/**
	 * Current post type slug
	 *
	 * @var string
	 * @since m2m
	 */
	protected $current_post_type_slug;


	/**
	 * Relationship id
	 *
	 * @var int
	 * @since m2m
	 */
	protected $relationship_id;


	/**
	 * Relationship
	 *
	 * @var IToolset_Relationship_Definition
	 * @since m2m
	 */
	protected $relationship;


	/**
	 * Constructor
	 *
	 * @param IToolset_Post_Type|string $post_type Post type or post type slug
	 * @param IToolset_Relationship_Definition|null $relationship Relationship
	 *
	 * @throws InvalidArgumentException In case of error.
	 * @since m2m
	 */
	public function __construct( $post_type, $relationship ) {
		if ( ! $relationship ) {
			throw new InvalidArgumentException( 'Invalid relationship.' );
		}

		$this->current_post_type_slug = $post_type instanceof IToolset_Post_Type
			? $post_type->get_slug()
			: $post_type;

		$this->relationship = $relationship;
		$this->option_name = sprintf( static::OPTION_NAME_PATTERN, $this->current_post_type_slug, $this->relationship->get_row_id() );
		$this->load_data();
	}


	/**
	 * Shows field in related content metabox
	 *
	 * @param string $field_slug Field slug.
	 *
	 * @since m2m
	 */
	public function show_field_related_content( $field_slug ) {
		if ( ! in_array( $field_slug, $this->fields_displayed ) ) {
			$this->fields_displayed[] = (string) $field_slug;
		}
	}


	/**
	 * Shows field in related content metabox
	 *
	 * @param string $field_slug Field slug.
	 *
	 * @since m2m
	 */
	public function hide_field_related_content( $field_slug ) {
		$this->fields_displayed = array_diff( $this->fields_displayed, array( (string) $field_slug ) );
	}


	/**
	 * Sets a list of fields to be displayed
	 *
	 * @param string[] $field_slugs List of field slugs
	 *
	 * @throws InvalidArgumentException If error.
	 * @since m2m
	 */
	public function set_fields_list_related_content( $field_slugs ) {
		if ( ! is_array( $field_slugs ) ) {
			throw new InvalidArgumentException( 'List of field slugs must be an array.' );
		}
		$this->fields_displayed = array();
		foreach ( $field_slugs as $field_slug ) {
			$this->show_field_related_content( $field_slug );
		}
	}


	/**
	 * Gets the list of fields to be displayed
	 *
	 * @return string[] $field_slugs List of field slugs
	 * @since m2m
	 */
	public function get_fields_list_related_content() {
		// Because of some array indexes are missed, JS change this array to a json object.
		return array_values( $this->fields_displayed );
	}


	/**
	 * Loads data
	 *
	 * @since m2m
	 */
	public function load_data() {
		$this->fields_displayed = get_option( $this->option_name, false );
		if ( false === $this->fields_displayed ) {
			// First time must have all the fields
			$this->fields_displayed = $this->get_all_fields();
			$this->save_data();
		}
	}


	public function save_data() {
		update_option( $this->option_name, $this->fields_displayed, false );
	}


	/**
	 * Updates the new slug in all the lists of selected fields to be displayed in related content.
	 *
	 * It uses wpdb because it is not possible to know what lists exists previously, so a pattern match is needed.
	 *
	 * @param string $prev_slug Previous slug.
	 * @param string $curr_slug Current slug.
	 *
	 * @since m2m
	 */
	public static function update_slug_fields_selected_related_content( $prev_slug, $curr_slug ) {
		global $wpdb;
		$option_name = str_replace( '%s-%d', '%', self::OPTION_NAME_PATTERN );
		$options_names = $wpdb->get_results( $wpdb->prepare(
			"SELECT option_name
				FROM $wpdb->options
				WHERE option_name LIKE %s
					AND option_value LIKE %s",
			$option_name, '%' . $prev_slug . '%'
		) );
		foreach ( $options_names as $option_name ) {
			$fields = get_option( $option_name->option_name );
			$fields = array_map( static function ( $v ) use ( $prev_slug, $curr_slug ) {
				return $v === $prev_slug ? $curr_slug : $v;
			}, $fields );
			update_option( $option_name->option_name, $fields, false );
		}
	}


	/**
	 * Gets all post type fields
	 *
	 * @return string[]
	 * @since m2m
	 */
	protected function get_all_fields() {
		$fields = array();
		$field_group_post_factory = Toolset_Field_Group_Post_Factory::get_instance();
		$field_groups = $field_group_post_factory->get_groups_by_post_type( $this->current_post_type_slug );
		foreach ( $field_groups as $field_group ) {
			if ( $field_group->is_active() ) {
				$definitions = $field_group->get_field_definitions();
				foreach ( $definitions as $definition ) {
					$fields[] = $definition->get_slug();
				}
			}
		}

		return $fields;
	}


	/**
	 * Adds a new slug to any field list belonging to a post type
	 *
	 * @param string $field_slug New field slug.
	 * @param string[] $post_types List of post types
	 *
	 * @since m2m
	 */
	public static function add_slug_fields_selected_related_content( $field_slug, $post_types ) {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}
		$relationships_factory = new Factory();
		foreach ( $post_types as $post_type ) {
			$query = $relationships_factory->relationship_query();
			$results = $query
				->do_not_add_default_conditions()
				->add( $query->has_domain( Toolset_Element_Domain::POSTS ) )
				->add( $query->do_or(
					$query->has_type( $post_type ),
					$query->intermediary_type( $post_type )
				) )
				->get_results();
			foreach ( $results as $relationship ) {
				$relationship_settings = new self( $post_type, $relationship );
				$relationship_settings->show_field_related_content( $field_slug );
				$relationship_settings->save_data();
			}
		}
	}


	/**
	 * Removes a new slug to any field list belonging to a post type
	 *
	 * @param string $field_slug New field slug.
	 * @param string[] $post_types List of post types
	 *
	 * @since m2m
	 */
	public static function delete_slug_fields_selected_related_content( $field_slug, $post_types ) {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}
		$relationships_factory = new Factory();
		foreach ( $post_types as $post_type ) {
			$query = $relationships_factory->relationship_query();
			$results = $query
				->do_not_add_default_conditions()
				->add( $query->has_domain( Toolset_Element_Domain::POSTS ) )
				->add( $query->do_or(
					$query->has_type( $post_type ),
					$query->intermediary_type( $post_type )
				) )
				->get_results();
			foreach ( $results as $relationship ) {
				$relationship_settings = new self( $post_type, $relationship );
				$relationship_settings->hide_field_related_content( $field_slug );
				$relationship_settings->save_data();
			}
		}
	}
}
