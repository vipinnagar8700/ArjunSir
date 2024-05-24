<?php

use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;

/**
 * Related Posts. Elements related to a specific post.
 *
 * @since m2m
 */
class Types_Viewmodel_Related_Content_Post extends Types_Viewmodel_Related_Content {


	/**
	 * The number of rows found
	 *
	 * @var int
	 * @since m2m
	 */
	private $rows_found;


	/**
	 * Returns the related posts
	 *
	 * @param int $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param int $page_number Page number.
	 * @param int $items_per_page Limit.
	 * @param string $role_name Needed for subqueries.
	 * @param string $sort ASC or DESC.
	 * @param string $sort_by The field name or 'displayName'.
	 * @param string $sort_origin The origin of the field: post field, relationship field or post_title.
	 *
	 * @return array Related posts.
	 * @throws InvalidArgumentException If arguments are not passed.
	 * @since m2m
	 */
	public function get_related_content(
		$post_id = null,
		$post_type = '',
		$page_number = 1,
		$items_per_page = 0,
		$role_name = null,
		$sort = null,
		$sort_by = null,
		$sort_origin = null
	) {
		if ( null === $post_id || 0 === (int) $post_id || empty( $post_type ) ) {
			throw new InvalidArgumentException( 'Invalid post id or type.' );
		}

		$role = Toolset_Relationship_Role::parent_or_child_from_name( $role_name );

		if ( ! $items_per_page ) {
			$items_per_page = Types_Page_Extension_Meta_Box_Related_Content::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE;
		}

		$query = $this->relationships_factory->association_query();
		$query->add( $query->relationship( $this->relationship ) )
			->need_found_rows()
			->offset( ( $page_number - 1 ) * $items_per_page )
			->limit( $items_per_page );

		// We want all associations to fall back to the default language post if the current one doesn't exist,
		// even if they are in the "show only translated items" mode. This gives the user the chance to
		// easily see that they might want to translate these posts.
		//
		// This is especially important in relationship roles with the cardinality "1", where it's not possible
		// to connect another parent post, but the connected one wouldn't be displayed either.
		$query->force_display_as_translated_mode();

		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			// element_trid_or_id_and_domain is not available in this mode.
			$query->add( $query->element_id_and_domain(
				$post_id, Toolset_Element_Domain::POSTS, $role
			) );
		} else {
			// We need to query by TRID if it exists in case it is being overridden by WpmlTridAutodraftOverride.
			// In such situations, querying by element ID wouldn't work because it still has a wrong TRID and
			// language stored in the database (see WpmlTridAutodraftOverride for a detailed explanation).
			$query->add( $query->element_trid_or_id_and_domain(
				$this->wpml_service->get_post_trid( $post_id ), $post_id, Toolset_Element_Domain::POSTS, $role
			) );

			// Additionally, we also have to include the original language so that we don't miss results that don't
			// have any default language version.
			$query->include_original_language();
		}

		// In this context, editing a post, the status of the post shouldn't matter to show the related items.
		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT, $role->other() ) );

		// We also explicitly add the condition to query by all statuses on the current post
		// because we want to show some results even for auto-draft posts (they might be excluded unless
		// explicitly required).
		//
		// This is not in a conflict with the previous element_status condition, nor should it impact the performance
		// in a negative way (it will result only in a 'AND 1=1' statement), but this condition needs to be on
		// the top level for things to work properly (unlike the above which is nested in a not() condition).
		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY, $role ) );

		if ( $sort ) {
			$query->order( $sort );
		}

		// Sorting.
		if ( $sort_origin ) {
			$field_definition_factory = Toolset_Field_Definition_Factory_Post::get_instance();
			switch ( $sort_origin ) {
				case 'post_title':
					$query->order_by_title( $role->other() );
					break;
				case 'post':
					$query->order_by_field_value(
						$field_definition_factory->load_field_definition( $sort_by ), $role->other()
					);
					break;
				case 'relationship':
					if ( 'intermediary-title' === $sort_by ) {
						$query->order_by_title( new Toolset_Relationship_Role_Intermediary() );
						break;
					}
					$query->order_by_field_value(
						$field_definition_factory->load_field_definition( $sort_by ),
						new Toolset_Relationship_Role_Intermediary()
					);
					break;
			}
		}

		$associations = $query->get_results();
		$this->rows_found = $query->get_found_rows();

		return $this->get_related_content_data( $associations );
	}


	/**
	 * Get related posts data
	 *
	 * @param IToolset_Association[] $associations Array of related content.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_related_content_data( $associations ) {
		$related_posts = array();
		foreach ( $associations as $association ) {
			// The related post.
			try {
				$post = $association->get_element( Toolset_Relationship_Role::role_from_name( $this->related_element_role ) );
				$fields = $association->get_fields();
				$uid = $association->get_uid();
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				// An element was supposed to be in the database but it's missing. We're going to
				// report a data integrity issue and skip it.
				do_action(
					'toolset_report_m2m_integrity_issue',
					new Toolset_Relationship_Database_Issue_Missing_Element(
						$e->get_domain(),
						$e->get_element_id()
					)
				);

				continue;
			}

			$intermediary_id = $association->get_intermediary_id();
			$related_posts[] = array(
				'uid' => $uid,
				'role' => $this->related_element_role,
				'post' => $post,
				'fields' => $fields,
				'relatedPosts' => $this->get_related_posts( $post ),
				'has_intermediary_fields' => ! empty( $fields ),
				'flag' => $this->get_language_flag( $post->get_id() ),
				'fieldsFlag' => $intermediary_id ? $this->get_language_flag( $intermediary_id ) : '',
			);
		}

		return $related_posts;
	}


	/**
	 * Returns the number of rows found
	 *
	 * @return integer
	 * @since m2m
	 */
	public function get_rows_found() {
		return $this->rows_found;
	}


	/**
	 * Gets a related posts using its UID
	 *
	 * @param int $association_uid Used to get only a specific related content.
	 *
	 * @return array Related post.
	 * @since m2m
	 */
	public function get_related_content_from_uid( $association_uid ) {
		$association_query = $this->relationships_factory->association_query();
		$association = $association_query->add( $association_query->association_id( $association_uid ) )
			->add( $association_query->element_status( ElementStatusCondition::STATUS_ANY ) )
			->get_results();

		return $this->get_related_content_data( $association );
	}


	/**
	 * Gets a related posts using its UID
	 *
	 * @param int $association_uid Used to get only a specific related content.
	 *
	 * @return array Related posts.
	 * @since m2m
	 */
	public function get_related_content_from_uid_array( $association_uid ) {
		$associations = $this->get_related_content_from_uid( $association_uid );

		return $this->format_related_content_array( $associations );
	}


	/**
	 * Gets the related posts as an array for using in the admin frontend
	 *
	 * @param int|null $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param int $page_number Page number.
	 * @param int $items_per_page Limit.
	 * @param string|null $role Needed for subqueries.
	 * @param string $sort ASC or DESC.
	 * @param string $sort_by The field name or 'displayName'.
	 * @param string $sort_origin The origin of the field: post field, relationship field or post_title.
	 *
	 * @return array
	 * @since m2m
	 */
	public function get_related_content_array(
		$post_id = null,
		$post_type = '',
		$page_number = 1,
		$items_per_page = 0,
		$role = null,
		$sort = 'ASC',
		$sort_by = 'displayName',
		$sort_origin = 'post_title'
	) {
		// Data represents the items and columns the info for the table.
		$related_posts_array = array(
			'data' => array(),
			'columns' => $this->get_fields(),
			'fieldsListing' => $this->get_fields_listing( $role ),
			'conditionals' => $this->get_conditional_data(),
		);
		$related_posts = $this->get_related_content( $post_id, $post_type, $page_number, $items_per_page, $role, $sort, $sort_by, $sort_origin );
		$related_posts_array['data'] = $this->format_related_content_array( $related_posts );

		return array_merge( $related_posts_array, $this->get_disabled_fields_by_post( $related_posts ) );
	}


	/**
	 * Formats an array of related posts
	 *
	 * @param array $related_posts Array of associations.
	 *
	 * @return array
	 * @since m2m
	 */
	private function format_related_content_array( $related_posts ) {
		$items = array();
		foreach ( $related_posts as $related_post ) {
			$item = array();
			$item['association_uid'] = $related_post['uid'];
			$item['role'] = $related_post['role'];
			/** @var IToolset_Element $related_post_object */
			$related_post_object = $related_post['post'];
			/** @var WP_Post $post */
			$post = $related_post_object->get_underlying_object();
			$item['post_id'] = $related_post_object->get_id();
			$item['author_id'] = (int) $post->post_author;
			$item['is_published'] = $related_post_object->get_underlying_object()->post_status === 'publish';
			$item['post_status'] = $post->post_status;
			$item['displayName'] = $post->post_title;
			$item['editPage'] = get_edit_post_link( $related_post_object->get_id(), '' );
			$item['strings'] = $this->get_js_strings( $related_post );
			// Post fields. It gets posts fields and relationship fields.
			$item['fields'] = array(
				'post' => $related_post_object->get_fields(),
				'relationship' => $related_post['fields'],
			);
			$item['relatedPosts'] = $related_post['relatedPosts'];
			$item['has_intermediary_fields'] = $related_post['has_intermediary_fields'];
			$item['flag'] = $related_post['flag'];
			$item['fieldsFlag'] = $related_post['fieldsFlag'];

			$items[] = $item;
		}

		return $items;
	}


	/**
	 * Returns the strings for the knockout
	 *
	 * @param array $association The related content.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_js_strings( $association ) {
		$strings = array();
		$post_type = $association['post']->get_type();
		$post_type_object = get_post_type_object( $post_type );
		$strings['titles'] = array();
		// translators: Post type singular label.
		$strings['titles']['postHeading'] = sprintf( __( '%s fields', 'wpcf' ), $post_type_object->labels->singular_name );
		$strings['titles']['postTitleLabel'] = $post_type_object->labels->singular_name;

		return $strings;
	}


	/**
	 * Gets the columns data from the fields
	 *
	 * @return array The fields definition data for table columns.
	 * @since m2m
	 */
	private function get_fields() {
		$skip_relationship_columns = apply_filters( 'types_skip_related_content_relationship_columns', false );

		$columns = array(
			'post' => array(),
			'relationship' => array(),
			'relatedPosts' => array(),
		);
		// Post Fields.
		$element_type = $this->relationship->get_element_type( $this->related_element_role );
		$post_types = $element_type->get_types();
		if ( Toolset_Element_Domain::POSTS === $element_type->get_domain() ) {
			$field_groups = Toolset_Field_Group_Post_Factory::get_instance()->get_groups_for_new_post( $post_types[0] );
			foreach ( $field_groups as $field_group ) {
				foreach ( $field_group->get_field_definitions() as $definition ) {

					if (
						$skip_relationship_columns
						&& $definition->get_type_slug() === Toolset_Field_Type_Definition_Factory::POST
					) {
						// We may want to skip post reference fields.
						continue;
					}

					$columns['post'][] = array(
						'slug' => $definition->get_slug(),
						'displayName' => $definition->get_name(),
						'fieldType' => $definition->get_type()->get_slug(),
					);
				}
			}
		}
		// Intermediary post
		$intermediary_post_type = $this->relationship->get_intermediary_post_type();
		if ( null !== $intermediary_post_type ) {
			$intermediary_post_type_object = get_post_type_object( $intermediary_post_type );
			if ( $intermediary_post_type_object->show_ui ) {
				$columns['relationship'][] = array(
					'slug' => 'intermediary-title',
					'displayName' => __( 'Intermediary Title', 'wpcf' ),
				);
			}
		}

		// Relationship fields.
		foreach ( $this->relationship->get_association_field_definitions() as $field ) {
			$columns['relationship'][] = array(
				'slug' => $field->get_slug(),
				'displayName' => $field->get_name(),
				'fieldType' => $field->get_type()->get_slug(),
			);
		}
		// Related posts.
		if ( ! $skip_relationship_columns ) {
			$actual_post_types = $this->relationship->get_element_type( $this->role )->get_types();
			$relationship_query = $this->relationships_factory->relationship_query();
			$cardinality = $relationship_query->cardinality();
			$relationship_query->add( $relationship_query->has_domain_and_type( $post_types[0], 'posts', new Toolset_Relationship_Role_Child() ) )
				->add( $relationship_query->exclude_relationship( $this->relationship ) )
				->add( $relationship_query->do_or(
					$relationship_query->has_cardinality( $cardinality->one_to_many() ),
					$relationship_query->has_cardinality( $cardinality->one_to_one() )
				) )
				->add( $relationship_query->exclude_type( $actual_post_types[0] ) );
			// Used to avoid post types duplications.
			$used_post_types = array();
			foreach ( $relationship_query->get_results() as $relationship ) {
				$parent_types = $relationship->get_element_type( new Toolset_Relationship_Role_Parent() )->get_types();
				foreach ( $parent_types as $parent_type ) {
					if ( in_array( $parent_type, $used_post_types, true ) ) {
						continue;
					}
					$parent_type_object = get_post_type_object( $parent_type );
					$columns['relatedPosts'][] = array(
						'slug' => $parent_type,
						'displayName' => $parent_type_object->labels->singular_name,
						'fieldType' => 'relatedPost',
					);
					$used_post_types[] = $parent_type;
				}
			}
		}

		return $columns;
	}


	/**
	 * Get post WPML language flag <img>
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 * @since m2m
	 */
	private function get_language_flag( $post_id ) {
		$flag_url = $this->wpml_service->get_language_flag_url( $post_id );

		if ( ! $flag_url ) {
			return '';
		}

		return '<img src="' . esc_url( $flag_url ) . '" class="types-language-flag" /> ';
	}


	/**
	 * Returns the list of fields to be displayed
	 *
	 * @param string $role_name Role name.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_fields_listing( $role_name ) {
		$role = Toolset_Relationship_Role::parent_or_child_from_name( $role_name );
		$post_type = $this->relationship->get_element_type( $role->other() )->get_types();
		$post_type = reset( $post_type );
		$ipt = $this->relationship->get_intermediary_post_type();

		$post_fields_selected = new Types_Post_Type_Relationship_Settings( $post_type, $this->relationship );
		$relationship_fields_selected = new Types_Post_Type_Relationship_Settings( $ipt, $this->relationship );
		$related_posts_columns_selected = new Types_Post_Type_Relationship_Related_Posts_Settings( $post_type, $this->relationship );

		return array(
			'post' => $post_fields_selected->get_fields_list_related_content(),
			'relationship' => $relationship_fields_selected->get_fields_list_related_content(),
			'relatedPosts' => $related_posts_columns_selected->get_fields_list_related_content(),
		);
	}


	/**
	 * Returns related posts associated to a post only if post is a child of a 1-to-many or 1-to-1 relationship, or it
	 * has a RPF
	 *
	 * @param IToolset_Element $post Toolset Post object.
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_related_posts( $post ) {
		if ( apply_filters( 'types_skip_related_content_relationship_columns', false ) ) {
			return [];
		}

		$query = $this->relationships_factory->association_query();
		$query->add(
			$query->not( $query->relationship_id( $this->relationship->get_row_id() ) )
		)
			->add(
				$query->do_or(
					$query->do_and(
						$query->element_id( $post->get_id(), new Toolset_Relationship_Role_Child() ),
						$query->has_origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD )
					),
					$query->do_and(
						$query->element_id( $post->get_id(), new Toolset_Relationship_Role_Parent() ),
						$query->has_origin( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD )
					)
				)
			)
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 100 ); // Not best solution.

		// We use the most performant option (don't care about post status and allow autodrafts to be
		// ignored in translatable post types) and offload the filtering by post status to individual results below.
		$query->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT ) );

		$related_posts = array();
		foreach ( $query->get_results() as $parent_post_id ) {
			$parent_post = get_post( $parent_post_id );
			if (
				null === $parent_post
				|| ! in_array( $parent_post->post_status, [ 'publish', 'future', 'draft', 'pending', 'private' ], true )
			) {
				continue;
			}

			$related_posts[ $parent_post->post_type ] = array(
				'post_id' => $parent_post_id,
				'displayName' => $parent_post->post_title,
				'editPage' => get_edit_post_link( $parent_post_id, '' ),
			);
		}

		return $related_posts;
	}


	/**
	 * Gets conditional data, used with conditionals.js, for display conditionals fields.
	 *
	 * There is data foreach field belonging to the related post: post + relationship's fields
	 *
	 * @return array
	 * @since 3.0.7
	 * @link https://git.onthegosystems.com/toolset/types/wikis/Fields-conditionals:-Toolset-forms-conditionals.js
	 */
	private function get_conditional_data() {
		// Post fields
		$field_groups = $this->get_field_groups();
		$field_conditionals = array();
		foreach ( $field_groups as $field_group ) {
			$fields = $field_group->get_field_definitions();
			$group_conditional = $field_group->get_conditional_display_by_fields();
			if ( ! empty( $group_conditional ) ) {
				$field_conditionals[] = $this->transform_conditionals_array_ids( $group_conditional, 'wpcf[post][%s]' );
			}
			foreach ( $fields as $field ) {
				$conditionals = $field->get_conditional_display();
				if ( $conditionals ) {
					$field_conditionals[] = $this->transform_conditionals_array_ids( $conditionals, 'wpcf[post][%s]' );
				}
			}
		}
		// Relationship fields.
		$fields = $this->relationship->get_association_field_definitions();
		foreach ( $fields as $field ) {
			$conditionals = $field->get_conditional_display();
			if ( $conditionals ) {
				$field_conditionals[] = $this->transform_conditionals_array_ids( $conditionals, 'wpcf[relationship][%s]' );
			}
		}

		return $field_conditionals;
	}


	/**
	 * Transform conditional names/ids to work with related content field names
	 *
	 * @param array $conditionals Array containing fields and triggers values
	 * @param string $pattern New name patters
	 *
	 * @return array
	 * @since 3.0.7
	 */
	private function transform_conditionals_array_ids( $conditionals, $pattern ) {
		// Fields
		foreach ( $conditionals['fields'] as $id => $fields ) {
			$new_id = sprintf( $pattern, str_replace( 'wpcf-', '', $id ) );
			foreach ( $fields['conditions'] as $i => $conditional ) {
				$fields['conditions'][ $i ]['id'] = sprintf( $pattern, str_replace( 'wpcf-', '', $conditional['id'] ) );
			}
			$conditionals['fields'][ $new_id ] = $fields;
			unset( $conditionals['fields'][ $id ] );
		}

		// Triggers
		foreach ( $conditionals['triggers'] as $id => $triggers ) {
			$new_id = sprintf( $pattern, str_replace( 'wpcf-', '', $id ) );
			foreach ( $triggers as $i => $trigger ) {
				$triggers[ $i ] = sprintf( $pattern, str_replace( 'wpcf-', '', $trigger ) );
			}
			$conditionals['triggers'][ $new_id ] = $triggers;
			unset( $conditionals['triggers'][ $id ] );
		}

		return $conditionals;
	}


	/**
	 * Gets field Groups
	 *
	 * @return Toolset_Field_Group_Post[]
	 * @since 3.0.8
	 */
	private function get_field_groups() {
		$element_type = $this->relationship->get_element_type( $this->related_element_role );
		$post_types = $element_type->get_types();

		return Toolset_Field_Group_Post_Factory::get_instance()->get_groups_by_post_type( $post_types[0] );
	}


	/**
	 * Gets disabled fields by post due to conditional groups by term, used in Related Content metabox
	 *
	 * Field groups can be assigned to a term, this conditions the field display
	 * There are two different kind of results: by post and all
	 *   - by post: if the post is not assigned to the group field term,
	 *     the fields belonging to the group will be removed from the related content
	 *   - all: when creating a new related content, the new post is not assigned to
	 *     any term so needs to remove any field belonging to a group assigned to a term
	 *
	 * @param array $related_posts
	 *
	 * @return array [
	 *                 'disabled_fields_by_post' => [ 'field_slug_1', 'field_slug_2' ]
	 *                 'disabled_fields_all' => [ 'field_slug_3', 'field_slug_4' ]
	 *               ]
	 */
	private function get_disabled_fields_by_post( $related_posts ) {
		$disabled = array( 'disabled_fields_by_post' => array(), 'disabled_fields_all' => array() );
		$field_groups = $this->get_field_groups();
		$terms = array();
		foreach ( $field_groups as $field_group ) {
			$fields = $field_group->get_field_definitions();
			$fields_slug = array();
			foreach ( $fields as $field ) {
				$fields_slug[] = $field->get_slug();
			}
			$group_terms = get_post_meta( $field_group->get_id(), '_wp_types_group_terms', true );
			$group_terms = ! empty( $group_terms ) && 'all' !== $group_terms
				? array_filter( explode( ',', $group_terms ) )
				: false;
			if ( $group_terms ) {
				$disabled['disabled_fields_all'] = array_merge( $disabled['disabled_fields_all'], $fields_slug );
			}
			foreach ( $related_posts as $related_post ) {
				$post_id = $related_post['post']->get_id();
				if ( ! isset( $terms[ $post_id ] ) ) {
					$terms[ $post_id ] = toolset_ensarr( wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) ) );
				}
				if ( $group_terms ) {
					$terms_diff = array_intersect( $terms[ $post_id ], $group_terms );
					if ( count( $group_terms ) !== count( $terms_diff ) ) {
						if ( ! isset( $disabled['disabled_fields_by_post'][ $post_id ] ) ) {
							$disabled['disabled_fields_by_post'][ $post_id ] = array();
						}
						$disabled['disabled_fields_by_post'][ $post_id ] = array_merge( $disabled['disabled_fields_by_post'][ $post_id ], $fields_slug );
					}
				}
			}
		}

		return $disabled;
	}
}
