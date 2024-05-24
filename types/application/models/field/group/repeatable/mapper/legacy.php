<?php

use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\ElementStatusCondition;
use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Field\Group\Repeatable\SortOrderManagement;
use OTGS\Toolset\Types\Wordpress\Meta;

/**
 * @since m2m
 */
class Types_Field_Group_Repeatable_Mapper_Legacy implements Types_Field_Group_Mapper_Interface {


	const IS_CREATING_NEW_FIELD_GROUP_ITEM_FILTER = 'types_is_creating_new_field_group_item';


	/** @var Factory */
	private $relationships_factory;

	/** @var WpmlService */
	private $wpml_service;

	private $sort_order_management;


	/**
	 * Types_Field_Group_Repeatable_Mapper_Legacy constructor.
	 */
	public function __construct() {
		$this->relationships_factory = new Factory();
		$this->wpml_service = WpmlService::get_instance();
		$this->sort_order_management = new SortOrderManagement(
			new Meta(), $this->wpml_service
		);
	}


	/**
	 * Map repeatable group by the WP_Post object of the group.
	 *
	 * @param WP_Post $rfg_post
	 * @param int $depth
	 * @param WP_Post|null $parent_post To load items of RFG the associated post is necessary	 *
	 * @param SitePress|null $wpml
	 * @param Toolset_Post_Type_Repository|null $post_type_repository
	 * @param Types_Field_Group_Repeatable_Item_Builder|null $rfg_item_builder
	 * @param RelationshipRoleParentChild $relationship_role_parent
	 * @param RelationshipRoleParentChild $relationship_role_child
	 *
	 * @return bool|Types_Field_Group_Repeatable
	 */
	public function find_repeatable_group_by_post(
		WP_Post $rfg_post,
		$depth = 1,
		WP_Post $parent_post = null,
		SitePress $wpml = null,
		Toolset_Post_Type_Repository $post_type_repository = null,
		Types_Field_Group_Repeatable_Item_Builder $rfg_item_builder = null,
		RelationshipRoleParentChild $relationship_role_parent = null,
		RelationshipRoleParentChild $relationship_role_child = null
	) {
		// make sure depth is an int
		$depth = (int) $depth;

		if ( $rfg_post->post_type !== Toolset_Field_Group_Post::POST_TYPE ) {
			// no repeatable field group nor field group
			return false;
		}

		if ( $rfg_post->post_status !== 'hidden' ) {
			// we have a field group, BUT NO repeatable field group
			return false;
		}

		// start mapping group
		// TODO get rid of hard coded dependency
		$group = new Types_Field_Group_Repeatable( $rfg_post );

		// prove slug
		$slug = $group->get_slug();
		if ( empty( $slug ) ) {
			// invalid group. there shouldn't be a group without a slug.
			return false;
		}

		// WPML - make sure post rfg has same translation mode as parent
		if ( $wpml && $parent_post && function_exists( 'wpml_load_settings_helper' ) ) {
			// parent post can be another rfg (we need to use the post_name) or a usual post
			$parent_post_type = $parent_post->post_type !== 'wp-types-group'
				? $parent_post->post_type
				: $parent_post->post_name;

			$settings_helper = wpml_load_settings_helper();
			$translation_settings = $wpml->get_setting( 'custom_posts_sync_option' );

			if ( isset( $translation_settings[ $parent_post_type ] ) ) {
				$parent_translation_setting = $translation_settings[ $parent_post_type ];
				$rfg_translation_setting = isset( $translation_settings[ $rfg_post->post_name ] )
					? $translation_settings[ $rfg_post->post_name ]
					: null;

				/** @noinspection TypeUnsafeComparisonInspection */
				if ( $rfg_translation_setting != $parent_translation_setting ) {
					$translation_settings[ $rfg_post->post_name ] = $parent_translation_setting;
					$settings_helper->update_cpt_sync_settings( $translation_settings );
				}
			}
		}

		// Load post type of group
		$post_type_repository = $post_type_repository ? : Toolset_Post_Type_Repository::get_instance();
		$post_type = $this->get_group_post_type(
			$group,
			Types_Field_Group_Repeatable::OPTION_NAME_LINKED_POST_TYPE,
			$post_type_repository
		);

		if ( $post_type ) {
			$group->set_post_type( $post_type );
		}

		// Load items of group
		if ( $depth > 0 && $parent_post ) {
			// default dependencies
			$rfg_item_builder = $rfg_item_builder ? : new Types_Field_Group_Repeatable_Item_Builder();
			$relationship_role_parent = $relationship_role_parent ? : new Toolset_Relationship_Role_Parent();
			$relationship_role_child = $relationship_role_child ? : new Toolset_Relationship_Role_Child();

			$items = $this->get_group_items(
				$group,
				$parent_post,
				$depth,
				$rfg_item_builder,
				$relationship_role_parent,
				$relationship_role_child
			);

			foreach ( $items as $item ) {
				$group->add_post( $item['object'], $item['sortorder'] );
			}
		}

		return $group;
	}


	/**
	 * @param WP_Post $post
	 * @param int $depth
	 *
	 * @return Types_Field_Group_Repeatable[]
	 */
	public function find_by_post( WP_Post $post, $depth = 1 ) {
		$repeatable_group = $this->find_repeatable_group_by_post( $post, $depth );
		if ( ! $repeatable_group ) {
			return [];
		}

		return [ $repeatable_group ];
	}



	/**
	 * Delete a item of an repeatable group
	 *
	 * - deletes post
	 * - deletes translations
	 * - deletes associations
	 *
	 * @param WP_Post $item
	 *
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 * @param Toolset_Relationship_Service $relationship_service
	 *
	 * @param SitePress|null $wpml
	 *
	 * @return bool
	 */
	public function delete_item_by_post(
		WP_Post $item,
		Toolset_Post_Type_Repository $post_type_repository,
		Toolset_Relationship_Service $relationship_service,
		SitePress $wpml = null
	) {
		// Check that the item belongs to an repeatable field group
		$post_type_the_item_belongs_to = $post_type_repository->get( $item->post_type );
		if ( ! $post_type_the_item_belongs_to || ! $post_type_the_item_belongs_to->is_repeating_field_group() ) {
			// no item of a repeatable field group
			throw new InvalidArgumentException( 'The item is not part of a repeatable field group' );
		}

		// Get children items (nested rfgs)
		if ( $children = $relationship_service->find_children_ids_by_parent_id( $item->ID ) ) {
			// remove children
			foreach ( $children as $child_id ) {
				if ( $item_post = get_post( $child_id ) ) {
					$this->delete_item_by_post( $item_post, $post_type_repository, $relationship_service, $wpml );
				}
			}
		}

		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			return $this->delete_item_and_translations_by_default_post( $item, $wpml );
		}

		// Delete the post and the whole translation group if the post belongs to one.
		$post_ids_to_delete = [ $item->ID ];

		$item_trid = $this->wpml_service->get_post_trid( $item->ID );
		if ( $item_trid ) {
			$post_ids_to_delete = array_merge(
				$this->wpml_service->get_post_translations( $item_trid ),
				$post_ids_to_delete
			);
		}

		$post_ids_to_delete = array_unique( $post_ids_to_delete );
		foreach ( $post_ids_to_delete as $post_id ) {
			wp_delete_post( $post_id );
		}

		return true;
	}


	/**
	 * Deleting an item and its translation for the first database layer version.
	 *
	 * Consider this legacy code.
	 *
	 * @param WP_Post $item
	 * @param SitePress|null $wpml
	 *
	 * @return array|bool|WP_Post|null
	 */
	public function delete_item_and_translations_by_default_post( WP_Post $item, SitePress $wpml = null ) {
		// Remove Translations
		if ( $wpml ) {
			$trid = $wpml->get_element_trid( $item->ID );
			$translations = $wpml->get_element_translations(
				$trid,
				$item->post_type,
				false,                              // $skip_empty
				true                                // $all_statuses
			);

			if ( is_array( $translations ) && ! empty( $translations ) ) {
				$default_language_id = $translations[ $wpml->get_default_language() ]->element_id;

				/** @noinspection TypeUnsafeComparisonInspection */
				if ( $default_language_id == $item->ID ) {
					// the default language item is delete... -> delete all translations of this item
					foreach ( $translations as $translation ) {
						/** @noinspection TypeUnsafeComparisonInspection */
						if ( $translation->element_id == $item->ID ) {
							// original item is deleted later
							continue;
						}

						// delete translation post
						wp_delete_post( $translation->element_id );
					}
				}
			}
		}

		// Delete the items post
		return wp_delete_post( $item->ID );
	}


	/**
	 * Update item title
	 * This will NOT trigger update_post hook.
	 *
	 * @param WP_Post $item
	 * @param string|mixed|null $title Optional. If not set $item->post_title will be used
	 *
	 * @return bool
	 */
	public function update_item_title( WP_Post $item, $title = null ) {
		$new_title = $item->post_title;

		if ( $title !== null ) {
			$new_title = $title;
		}

		if ( is_array( $new_title ) ) {
			throw new InvalidArgumentException( 'Title cannot be an array.' );
		}

		$result = wp_update_post( array(
			'ID' => $item->ID,
			'post_title' => sanitize_text_field( $new_title ),
		) );

		if ( ! $result ) {
			return false;
		}

		return true;
	}


	/**
	 * Load the post type, to which the Repeatable Field Group is assigned to.
	 *
	 * @param Types_Field_Group_Repeatable $group
	 *
	 * @param string $option_name_for_rfg_post
	 *
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 *
	 * @return false|IToolset_Post_Type
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function get_group_post_type(
		$group,
		$option_name_for_rfg_post,
		Toolset_Post_Type_Repository $post_type_repository
	) {
		$post_type_slug = get_post_meta(
			$group->get_id(),
			$option_name_for_rfg_post,
			true
		);

		if ( ! $post_type_slug || empty( $post_type_slug ) ) {
			// no linked post type
			return false;
		}

		if ( $post_type = $post_type_repository->get( $post_type_slug ) ) {
			return $post_type;
		}

		return false;
	}


	/**
	 * Get items of the group.
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param WP_Post $parent_post
	 * @param int $depth
	 *
	 * @param Types_Field_Group_Repeatable_Item_Builder $rfg_item_builder
	 * @param RelationshipRoleParentChild $relationship_role_parent
	 * @param RelationshipRoleParentChild $relationship_role_child
	 *
	 * @return array
	 */
	private function get_group_items(
		Types_Field_Group_Repeatable $rfg,
		WP_Post $parent_post,
		$depth,
		Types_Field_Group_Repeatable_Item_Builder $rfg_item_builder,
		RelationshipRoleParentChild $relationship_role_parent,
		RelationshipRoleParentChild $relationship_role_child
	) {
		do_action( 'toolset_do_m2m_full_init' );

		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			return $this->get_group_items_by_default_language_post(
				$rfg, $parent_post, $depth, $rfg_item_builder, $relationship_role_parent, $relationship_role_child
			);
		}

		$query = $this->relationships_factory->association_query();

		// Note the use of include_original_language(): This is extremely important, as it ensures we always get
		// some result per translation group, even if it's in ternary (non-current secondary) language only.
		//
		// It is also very important that we query by the original language of the parent post, which will
		// prevent issues with fetching RFG items on a new translation auto-draft post that has TRID set
		// via WpmlTridAutodraftOverride but it's not yet in the database, hence it wouldn't be matched by the query.
		//
		// We don't care about the status of the RFG items, because they're always 'publish', which allows us to use
		// the STATUS_ANY_BUT_AUTODRAFT constant that results in a performance optimization. For the parent post,
		// on the other hand, we need to include autodrafts so that RFGs work properly on newly created post translations.
		/** @var IToolset_Post[] $item_sources */
		$item_sources = $query
			->add( $query->element_id_and_domain(
				$parent_post->ID,
				Toolset_Element_Domain::POSTS,
				$relationship_role_parent,
				false,
				true,
				false,
				ElementIdentification::ORIGINAL_LANGUAGE
			) )
			->add( $query->has_domain_and_type( Toolset_Element_Domain::POSTS, $rfg->get_slug(), $relationship_role_child ) )
			->add( $query->element_status( ElementStatusCondition::STATUS_ANY, $relationship_role_parent ) )
			->add( $query->element_status( ElementStatusCondition::STATUS_ANY_BUT_AUTODRAFT, $relationship_role_child ) )
			->limit( 1000 )
			->include_original_language()
			->return_element_instances( $relationship_role_child )
			->get_results();

		$group_items = [];

		foreach ( $item_sources as $item_source ) {
			// Make sure we have the WP_Post object representing the RFG item in current language.
			$translated_item_id = $this->get_rfg_item_translation_or_create_it(
				$item_source->get_id(), null, $item_source->get_underlying_object()
			);

			$translated_item_post = $translated_item_id === $item_source->get_id()
				? $item_source->get_underlying_object()
				: get_post( $translated_item_id );

			if ( null === $translated_item_post ) {
				continue;
			}

			// add the post (item) to the group
			$rfg_item_builder->reset();
			$rfg_item_builder->set_wp_post( $translated_item_post );
			$rfg_item_builder->set_belongs_to_rfg( $rfg );
			$rfg_item_builder->load_assigned_field_groups( $depth );
			$rfg_item = $rfg_item_builder->get_types_post();

			$group_items[] = [
				'object' => $rfg_item,
				'sortorder' => $this->get_sortorder( $item_source->get_id() ),
			];
		}

		return $group_items;
	}


	/**
	 * Obtain items for the repeatable group using the first relationship database layer version (based on the
	 * assumption that there's always a default language post).
	 *
	 * Consider this legacy code.
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param WP_Post $parent_post
	 * @param int $depth
	 * @param Types_Field_Group_Repeatable_Item_Builder $rfg_item_builder
	 * @param RelationshipRoleParentChild $relationship_role_parent
	 * @param RelationshipRoleParentChild $relationship_role_child
	 *
	 * @return array
	 */
	private function get_group_items_by_default_language_post(
		Types_Field_Group_Repeatable $rfg,
		WP_Post $parent_post,
		$depth,
		Types_Field_Group_Repeatable_Item_Builder $rfg_item_builder,
		RelationshipRoleParentChild $relationship_role_parent,
		RelationshipRoleParentChild $relationship_role_child
	) {
		// when wpml is active we need to use the post of the default language
		$post_which_holds_associations = $this->get_post_which_holds_associations( $parent_post );
		/** @noinspection TypeUnsafeComparisonInspection */
		$is_default_language_active = $post_which_holds_associations->ID == $parent_post->ID;

		// post status "any"
		$post_status_any = ElementStatusCondition::STATUS_ANY;

		// Note: Don't touch this, this is for version 1 of the database layer.
		$association_query = $this->relationships_factory->association_query();
		$association_query
			->add( $association_query->element_id( $post_which_holds_associations->ID, $relationship_role_parent ) )
			->add( $association_query->has_type( $rfg->get_slug(), $relationship_role_child ) )
			->add( $association_query->element_status( $post_status_any, $relationship_role_parent ) )
			->add( $association_query->element_status( $post_status_any, $relationship_role_child ) );

		// get group elements as array of post ids
		$group_elements = $association_query
			->limit( 1000 )
			->return_element_ids( $relationship_role_child )
			->dont_translate_results()
			->get_results();

		$group_items = array();

		foreach ( $group_elements as $element_id ) {
			if ( ! $wp_post = get_post( $element_id ) ) {
				// the element id is invalid, skip it
				continue;
			}

			if ( ! $is_default_language_active ) {
				// as we now loop through the items of the default language,
				// we need to get the translation of the current language
				$wp_post_translated_id = $this->get_rfg_item_translation_or_create_it( $wp_post->ID, null, $wp_post );

				$wp_post = $wp_post_translated_id !== $wp_post->ID
					? get_post( $wp_post_translated_id )
					: $wp_post;
			}

			if ( ! $wp_post ) {
				continue;
			}

			// add the post (item) to the group
			$rfg_item_builder->reset();
			$rfg_item_builder->set_wp_post( $wp_post );
			$rfg_item_builder->set_belongs_to_rfg( $rfg );
			$rfg_item_builder->load_assigned_field_groups( $depth );
			$rfg_item = $rfg_item_builder->get_types_post();

			$group_items[] = array( 'object' => $rfg_item, 'sortorder' => $this->get_sortorder( $wp_post->ID ) );
		}

		return $group_items;
	}


	/**
	 * For a RFG item, provide a meaningful sortorder value.
	 *
	 * @param int $post_id
	 *
	 * @return int
	 */
	private function get_sortorder( $post_id ) {
		return $this->sort_order_management->get_sort_order( $post_id );
	}


	/**
	 * Retrieve the translation of a RFG item, create it if necessary.
	 *
	 * @param int $id_source_lang ID of the RFG item in the default language
	 * @param string|null $language_code Language code that is beign requested. If not set, the current language will
	 *     be used.
	 * @param WP_Post|null $source_wp_post The post object representing the RFG item in the default language.
	 *      If not available, it will be loaded.
	 *
	 * @return int|null ID of the translated RFG item, null on error.
	 */
	public function get_rfg_item_translation_or_create_it( $id_source_lang, $language_code, WP_Post $source_wp_post = null ) {
		global $sitepress;

		if ( ! $sitepress || ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return (int) $id_source_lang;
		}

		if ( null === $language_code ) {
			$language_code = $sitepress->get_current_language();
		}

		$id_target_lang = apply_filters(
			'wpml_object_id',
			$id_source_lang,
			'any',
			false, // $return_original_if_missing
			$language_code // we cannot use "null" for current language here, as it would not work when the user has the "All languages" mode active.
		);

		if ( $id_target_lang === null ) {
			// wpml is active, but there is no translated item
			if ( ! $source_wp_post ) {
				/** @noinspection CallableParameterUseCaseInTypeContextInspection */
				$source_wp_post = get_post( $id_source_lang );
			}
			if ( ! $source_wp_post instanceof WP_Post ) {
				// the element id is invalid, skip it
				return null;
			}

			if ( apply_filters( self::IS_CREATING_NEW_FIELD_GROUP_ITEM_FILTER, false ) ) {
				// Prevent infinite recursion via the wpml_tm_translation_job_data filter and wp_insert_post().
				return null;
			}

			add_filter( self::IS_CREATING_NEW_FIELD_GROUP_ITEM_FILTER, '__return_true' );

			// create a post for the translated item, based on the default language
			$id_target_lang = wp_insert_post( array(
				'post_name' => $source_wp_post->post_name,
				'post_title' => $source_wp_post->post_title,
				'post_type' => $source_wp_post->post_type,
				'post_status' => $source_wp_post->post_status,
			) );

			$this->sort_order_management->initialize_sort_order( $id_target_lang, $id_source_lang );

			// tell WPML that the new created post is the translation of default language
			$trid = $sitepress->get_element_trid( $source_wp_post->ID );
			$source_lang = isset( $_REQUEST['source_lang'] ) ? $_REQUEST['source_lang'] : null;

			$sitepress->set_element_language_details(
				$id_target_lang,
				'post_' . $source_wp_post->post_type,
				$trid,
				$language_code,
				$source_lang
			);

			// WPML
			$this->wpml_save_rfg_item( $id_target_lang );

			remove_filter( self::IS_CREATING_NEW_FIELD_GROUP_ITEM_FILTER, '__return_true' );
		}

		return (int) $id_target_lang;
	}


	/**
	 * For WPML setup (sync fields) we need to fire the save_post hook.
	 *
	 * @param int $rfg_item_id
	 */
	private function wpml_save_rfg_item( $rfg_item_id ) {
		// normally WPML does not fire save hooks for a different post than the current
		// so we temporary set our rfg item to $_POST['post_ID']
		$_POST_id_backup = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : null;
		$_POST['post_ID'] = $rfg_item_id;
		// tell WPML that our rfg item is a translatable post type
		add_filter( 'pre_wpml_is_translated_post_type', array( $this, 'filter_pre_wpml_is_translated_post_type' ) );

		$is_wpml_tm_save_post_action_active = has_action( 'wpml_tm_save_post', 'wpml_tm_save_post' );
		// disable wpml translation job update for RFG item (it will be updated by the parent post)
		if ( $is_wpml_tm_save_post_action_active ) {
			remove_action( 'wpml_tm_save_post', 'wpml_tm_save_post', 10 ); // prevent creating a translation job
		}

		// fire the save post hooks for the rfg item
		do_action( 'save_post', $rfg_item_id, get_post( $rfg_item_id ), true );

		// if( $is_wpml_tm_save_post_action_active ) {
		// add_action( 'wpml_tm_save_post', 'wpml_tm_save_post', 10, 3 );
		// }


		// undo all previous changes
		remove_filter( 'pre_wpml_is_translated_post_type', array( $this, 'filter_pre_wpml_is_translated_post_type' ) );

		if ( $_POST_id_backup !== null ) {
			$_POST['post_ID'] = $_POST_id_backup;
		} else {
			unset( $_POST['post_ID'] );
		}
	}


	/**
	 * We need this to apply wpml settings for rfg item
	 *
	 * @return bool
	 */
	public function filter_pre_wpml_is_translated_post_type() {
		return true;
	}


	/**
	 * @param WP_Post $original_post
	 *
	 * @return null|WP_Post
	 */
	private function get_post_which_holds_associations( WP_Post $original_post ) {
		try {
			$element_factory = new Toolset_Element_Factory();
			/** @var IToolset_Post $post */
			$post = $element_factory->get_post( $original_post );

		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// something went wrong, return input
			return $original_post;
		}

		$default_language_id = $post->get_default_language_id();

		if ( $default_language_id ) {
			return get_post( $default_language_id );
		}

		return $original_post;
	}

}
