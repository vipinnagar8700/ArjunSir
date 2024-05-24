<?php

use OTGS\Toolset\Common\Field\Group\TemplateFilter\TemplateFilterFactory;

/**
 * Post field group.
 *
 * @since 2.0
 */
class Toolset_Field_Group_Post extends Toolset_Field_Group {


	const POST_TYPE = 'wp-types-group';

	/**
	 * Postmeta that contains a comma-separated list of post type slugs where this field group is assigned.
	 *
	 * Note: There might be empty items in the list: ",,,post-type-slug,," Make sure to avoid those.
	 *
	 * Note: Empty value means "all groups". There also may be legacy value "all" with the same meaning.
	 *
	 * @since unknown
	 */
	const POSTMETA_POST_TYPE_LIST = '_wp_types_group_post_types';

	/**
	 * @var string Key of postmeta that contains a comma-separated list of taxonomy_term IDs where the field group
	 *    is assigned. Same warnings as for POSTMETA_POST_TYPE_LIST apply.
	 */
	const POSTMETA_TERM_LIST = '_wp_types_group_terms';

	/**
	 * @var string Key of postmeta that contains a comma-separated list of templates where the field group
	 *     is assigned. A template can be a native WP page template or a Content Template ID or a Content Template slug.
	 *     Same warnings as for POSTMETA_POST_TYPE_LIST apply.
	 */
	const POSTMETA_TEMPLATE_LIST = '_wp_types_group_templates';


	/**
	 * @var string Key of postmeta that contains the operator for evaluating the filters that determine where the
	 *     field group is displayed. Accepted values are 'any' and 'all', the former being the default one.
	 */
	const POSTMETA_FILTER_OPERATOR = '_wp_types_group_filters_association';

	// Field group purposes specific to post groups.


	/** Group is attached (only) to the indermediary post type of a relationship */
	const PURPOSE_FOR_INTERMEDIARY_POSTS = 'for_intermediary_posts';


	/** Group is attached to a post type that acts as a repeating field group */
	const PURPOSE_FOR_REPEATING_FIELD_GROUP = 'for_repeating_field_group';


	/** @var null|TemplateFilterFactory */
	private $template_filter_factory;


	/**
	 * @param WP_Post $field_group_post Post object representing a post field group.
	 *
	 * @param TemplateFilterFactory|null $template_filter_factory_di
	 */
	public function __construct( $field_group_post, TemplateFilterFactory $template_filter_factory_di = null ) {
		parent::__construct( $field_group_post );
		if ( self::POST_TYPE != $field_group_post->post_type ) {
			throw new InvalidArgumentException( 'incorrect post type' );
		}

		$this->template_filter_factory = $template_filter_factory_di;
	}


	/**
	 * @return Toolset_Field_Definition_Factory Field definition factory of the correct type.
	 */
	protected function get_field_definition_factory() {
		return Toolset_Field_Definition_Factory_Post::get_instance();
	}

	/**
	 * Assign a post type to the group
	 *
	 * @param $post_type
	 */
	public function assign_post_type( $post_type ) {
		$post_types = $this->get_assigned_to_types();
		$post_types[] = $post_type;

		$this->store_post_types( $post_types );
	}

	/**
	 * Stores an array of post types as list in database
	 *
	 * @param array $post_types
	 *
	 * @since m2m Allows to set a post type even though it's not currently registered
	 *     (needed for working with just created post type).
	 */
	protected function store_post_types( $post_types ) {
		// validate post types
		foreach ( $post_types as $type ) {
			if ( empty( $type ) ) {
				unset( $post_types[ $type ] );
			}
		}

		$this->update_assigned_types( $post_types );
		$post_types = empty( $post_types )
			? ''
			: implode( ',', $post_types );

		update_post_meta( $this->get_id(), self::POSTMETA_POST_TYPE_LIST, $post_types );
	}


	/**
	 * Load and parse a postmeta value that consists of comma-separated values.
	 *
	 * Survive excess separators with empty values.
	 *
	 * @param string $meta_key
	 * @param null|callable $sanitization_callback Callback for a single value sanitization. Optional.
	 * @param bool $parse_all_keyword If this is true, the value 'all' will be translated into an empty array.
	 *
	 * @return array
	 */
	private function parse_comma_separated_postmeta( $meta_key, $sanitization_callback = null, $parse_all_keyword = true ) {
		$raw_value = get_post_meta( $this->get_id(), $meta_key, true );

		// In old Types version, we may also store the value "all".
		if ( $parse_all_keyword && 'all' === $raw_value ) {
			return array();
		}

		// Keep your eyes open on storing values,
		// This is needed because legacy code produces values like ,,,,term-slug,,
		$trimmed_value = trim( $raw_value, ',' );

		if( empty( $trimmed_value ) ) {
			// Empty means all.
			return array();
		}

		$values = explode( ',', $trimmed_value );

		// make sure no empty values are returned (can happen due to our legacy storage like "posts,,cpt,cpt-2")
		$filtered_values = array_filter( $values );

		if( null === $sanitization_callback ) {
			$sanitized_values = $filtered_values;
		} else {
			$sanitized_values = array_map( $sanitization_callback, $filtered_values );
		}

		return $sanitized_values;
	}


	/**
	 * Retrieve term_taxonomy IDs of terms where this field group should be displayed (if the post has the particular term).
	 *
	 * @return int[]
	 * @since Types 3.3
	 */
	public function get_assigned_to_terms() {
		return $this->parse_comma_separated_postmeta( self::POSTMETA_TERM_LIST, 'intval' );
	}


	/**
	 * Retrieve filter objects describing where the field group should be displayed based on its template.
	 *
	 * See TemplateFilterInterface for more details.
	 *
	 * @return \OTGS\Toolset\Common\Field\Group\TemplateFilter\TemplateFilterInterface[]
	 * @since Types 3.3
	 */
	public function get_assigned_to_templates() {
		$template_names = $this->parse_comma_separated_postmeta( self::POSTMETA_TEMPLATE_LIST );

		$filter_factory = $this->template_filter_factory ?: new TemplateFilterFactory();
		return array_filter( array_map( function( $template ) use( $filter_factory ) {
			return $filter_factory->build_from_name( $template );
		}, $template_names ) );
	}


	/**
	 * @inheritdoc
	 *
	 * @return array
	 * @since 2.1
	 */
	protected function fetch_assigned_to_types() {
		return $this->parse_comma_separated_postmeta( self::POSTMETA_POST_TYPE_LIST );
	}


	/**
	 * @inheritdoc
	 * @return WP_Post[] Individual posts using this group.
	 * @since 2.1
	 */
	protected function fetch_assigned_to_items() {
		$assigned_posts = $this->get_assigned_to_types();

		if ( empty( $assigned_posts ) ) {
			$assigned_posts = array( 'all' );
		}

		$items = get_posts(
			array(
				'post_type' => $assigned_posts,
				'post_status' => 'any',
				'posts_per_page' => - 1,
			)
		);

		return $items;
	}


	/**
	 * Determine if the group is associated with a post type.
	 *
	 * @param string $post_type_slug
	 *
	 * @return bool
	 * @since m2m
	 * @deprecated Use is_assigned_to_type() instead.
	 */
	public function has_associated_post_type( $post_type_slug ) {
		return $this->is_assigned_to_type( $post_type_slug );
	}


	/**
	 * Get the backend edit link.
	 *
	 * @refactoring ! This doesn't belong to a model; separation of concerns!!
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . '/admin.php?page=wpcf-edit&group_id=' . $this->get_id();
	}


	/**
	 * @inheritdoc
	 *
	 * @return string[]
	 * @since m2m
	 */
	protected function get_allowed_group_purposes() {
		return array_merge(
			parent::get_allowed_group_purposes(),
			array(
				self::PURPOSE_FOR_INTERMEDIARY_POSTS,
				self::PURPOSE_FOR_REPEATING_FIELD_GROUP,
				self::PURPOSE_SYSTEM,
			)
		);
	}


	/**
	 * Return the value from self::POSTMETA_FILTER_OPERATOR.
	 *
	 * @return string 'any'|'all'
	 * @since Types 3.3
	 */
	public function get_filter_operator() {
		$value = get_post_meta( $this->get_id(), self::POSTMETA_FILTER_OPERATOR, true );
		if( ! in_array( $value, array( 'any', 'all' ) ) ) {
			$value = 'any';
		}

		return $value;
	}

}
