<?php

namespace OTGS\Toolset\Types\API\Modifier\PotentialAssociationQueryFilters\Post;

/**
 * Modifier for the query to populate the post reference fields selectors based on the author of options.
 *
 * @since m2m
 */
class AuthorForPostReference
	extends \Toolset_Potential_Association_Query_Filter_Posts_Author {

	/**
	 * @var string
	 */
	protected $field_slug;

	/**
	 * @var string
	 */
	protected $post_type;


	/**
	 * AuthorForPostReference constructor.
	 *
	 * @param string $field_slug
	 * @param string $post_type
	 */
	public function __construct( $field_slug, $post_type ) {
		$this->field_slug = $field_slug;
		$this->post_type = $post_type;
	}

	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Decides whether a filter by post author needs to be set by cascading a series of filters:
	 * - types_force_author_in_post_reference_by_slug_{field_slug}
	 * - types_force_author_in_post_reference_by_type_{post_type}
	 * - types_force_author_in_post_reference | gets also the field slug and the post type name
	 * - types_force_author_in_related_post
	 *
	 * Those filters should return either a post author ID or the keyword '$current', which is a placeholder
	 * for the currently logged in user; in case no user is logged in, we force empty query results.
	 *
	 * @param mixed $force_author
	 *
	 * @return mixed
	 *
	 * @since m2m
	 */
	protected function filter_by_plugin( $force_author ) {
		/**
		 * Force a post author on all Types interfaces to set a post reference field value, by field slug.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'types_force_author_in_post_reference_by_slug_' . $this->field_slug,
			$force_author
		);
		/**
		 * Force a post author on all Types interfaces to set a post reference field value, by target post type.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'types_force_author_in_post_reference_by_type_' . $this->post_type,
			$force_author
		);
		/**
		 * Force a post author on all Types interfaces to set a post reference field value.
		 *
		 * Include here extra data, for granularity:
		 * - The post reference field slug.
		 * - The requested post type.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'types_force_author_in_post_reference',
			$force_author,
			$this->field_slug,
			$this->post_type
		);
		/**
		 * Force a post author on all Types interfaces to set a related post.
		 *
		 * This is also used in the backend post edit page when setting a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'types_force_author_in_related_post',
			$force_author
		);

		return $force_author;
	}

}
