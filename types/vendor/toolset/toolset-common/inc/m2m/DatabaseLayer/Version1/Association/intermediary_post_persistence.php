<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Association;
use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Constants;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;
use WP_Error;

/**
 * Handles the persistence of intermediary posts.
 *
 * @since m2m
 */
class Toolset_Association_Intermediary_Post_Persistence
	implements \OTGS\Toolset\Common\Relationships\API\IntermediaryPostPersistence {


	/**
	 * Number of items handled each loop
	 */
	const DEFAULT_LIMIT = 50;

	/**
	 * Relationship definition, the associations depend on a relationship, that is why it is neccesary.
	 *
	 * @var IToolset_Relationship_Definition
	 * @since m2m
	 */
	private $relationship;


	/** @var \OTGS\Toolset\Common\WPML\WpmlService */
	private $wpml_service;


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * Class constructor
	 *
	 * @param IToolset_Relationship_Definition $relationship Relationship.
	 * @param \OTGS\Toolset\Common\WPML\WpmlService|null $wpml_service_di
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory|null $database_layer_factory
	 *
	 * @since m2m
	 */
	public function __construct(
		IToolset_Relationship_Definition $relationship = null,
		\OTGS\Toolset\Common\WPML\WpmlService $wpml_service_di = null,
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory = null
	) {
		// todo Consider passing this specifically to methods that need it.
		$this->relationship = $relationship;
		$this->wpml_service = $wpml_service_di ? : \OTGS\Toolset\Common\WPML\WpmlService::get_instance();
		$this->database_layer_factory = $database_layer_factory
			? : toolset_dic_make( '\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory' );
	}


	/**
	 * Create an intermediary post for a new association.
	 *
	 * @param int $parent_id Association parent id.
	 * @param int $child_id Association child id.
	 *
	 * @return int|null ID of the new post or null if the post creation failed.
	 * @since m2m
	 */
	public function create_intermediary_post( $parent_id, $child_id ) {
		$post_type = $this->relationship->get_intermediary_post_type();

		if ( null === $post_type ) {
			return null;
		}


		/**
		 * toolset_build_intermediary_post_title
		 *
		 * Allow for overriding the post title of an intermediary post.
		 *
		 * @param string $post_title Post title default value.
		 * @param string $relationship_slug
		 * @param int $parent_id
		 * @param int $child_id
		 *
		 * @since m2m
		 */
		$post_title = wp_strip_all_tags(
			apply_filters(
				'toolset_build_intermediary_post_title',
				$this->get_default_intermediary_post_title( $parent_id, $child_id ),
				$this->relationship->get_slug(),
				$parent_id,
				$child_id
			)
		);

		/**
		 * toolset_build_intermediary_post_name
		 *
		 * Allow for overriding the post name (slug) of an intermediary post.
		 *
		 * @param string $post_slug Post slug default value.
		 * @param string $relationship_slug
		 * @param int $parent_id
		 * @param int $child_id
		 *
		 * @since m2m
		 */
		$post_name = apply_filters(
			'toolset_build_intermediary_post_name',
			$post_title,
			$this->relationship->get_slug(),
			$parent_id,
			$child_id
		);

		// Intermediary posts need to be always created in the default language, even if the IPT
		// is translatable. Otherwise, it will not be possible to persist the association
		// (it requires a default language translation of all involved elements).
		$needs_wpml_lang_switch = (
			$this->wpml_service->is_wpml_active_and_configured()
			&& ! $this->wpml_service->is_current_language_default()
		);

		if ( $needs_wpml_lang_switch ) {
			$this->wpml_service->switch_language( $this->wpml_service->get_default_language() );
		}

		$result = wp_insert_post(
			array(
				'post_type' => $post_type,
				'post_title' => $post_title,
				'post_name' => $post_name,
				'post_content' => '',
				'post_status' => 'publish',
			),
			true
		);

		if ( $needs_wpml_lang_switch ) {
			$this->wpml_service->switch_language_back();
		}

		if ( $result instanceof WP_Error ) {
			return null;
		}

		return $result;
	}


	/**
	 * Returns the default name for an association intermediary post.
	 *
	 * @param int $parent_id Association parent id.
	 * @param int $child_id Association child id.
	 *
	 * @return string
	 * @since m2m
	 */
	private function get_default_intermediary_post_title( $parent_id, $child_id ) {
		return sprintf(
			'%s: %d - %d',
			$this->relationship->get_display_name(),
			$parent_id,
			$child_id
		);
	}


	/**
	 * It there are associations belonging to the definition, intermediary post without field values has to be created.
	 *
	 * @param int $limit The number of associations in a loop.
	 *
	 * @since 2.3
	 */
	public function create_empty_associations_intermediary_posts( $limit = 0 ) {
		if ( (int) $limit <= 0 ) {
			$limit = self::DEFAULT_LIMIT;
		}

		$query = $this->database_layer_factory->association_query();
		$query->add( $query->relationship( $this->relationship ) )
			->add( $query->not( $query->has_intermediary_id() ) )
			->limit( $limit );
		$associations = $query->get_results();
		foreach ( $associations as $association ) {
			if ( ! $association->get_intermediary_id() ) {
				$this->create_empty_association_intermediary_post( $association );
			}
		}
	}


	/**
	 * Removes intermediary post from associations.
	 *
	 * @param int $limit The number of associations in a loop.
	 *
	 * @return int Number of associations updated.
	 * @since 2.3
	 */
	public function remove_associations_intermediary_posts( $limit = 0 ) {
		if ( (int) $limit <= 0 ) {
			$limit = self::DEFAULT_LIMIT;
		}

		$association_query = $this->database_layer_factory->association_query();
		$association_query->add( $association_query->relationship( $this->relationship ) )
			->add( $association_query->has_intermediary_id() )
			->limit( $limit );
		$associations = $association_query->get_results();
		foreach ( $associations as $association ) {
			// Don't use `maybe_delete_intermediary_post` because it tries to access an object it doesn't exist.
			$intermediary_id = $association->get_intermediary_id();
			if ( $intermediary_id ) {
				$this->database_layer_factory->association_database_operations()
					->update_association_intermediary_id( $association->get_uid(), 0 );
			}
		}

		return count( $associations );
	}


	/**
	 * Creates an empty association intermediary post
	 *
	 * @param IToolset_Association $association Association.
	 *
	 * @return int Post ID
	 * @since m2m
	 */
	public function create_empty_association_intermediary_post( $association ) {
		$intermediary_id = (int) $this->create_intermediary_post(
			$association->get_element( new Toolset_Relationship_Role_Parent() )->get_default_language_id(),
			$association->get_element( new Toolset_Relationship_Role_Child() )->get_default_language_id()
		);
		if ( $intermediary_id ) {
			$this->database_layer_factory->association_database_operations()
				->update_association_intermediary_id( $association->get_uid(), $intermediary_id );
		}

		return $intermediary_id;
	}


	/**
	 * Delete the intermediary post if it exists and it's not disabled by a filter.
	 *
	 * This also deletes all its translations.
	 *
	 * @param IToolset_Association $association
	 */
	public function maybe_delete_intermediary_post( IToolset_Association $association ) {
		if ( ! $association->has_intermediary_post() ) {
			return;
		}

		if ( ! $association->get_definition()->is_autodeleting_intermediary_posts() ) {
			return;
		}

		$intermediary_id = $association->get_element( new Toolset_Relationship_Role_Intermediary() )
			->get_default_language_id();
		$this->delete_intermediary_post( $intermediary_id );
	}


	/**
	 * Delete the intermediary post if it's not disabled by a filter.
	 *
	 * This also deletes all its translations.
	 *
	 * @param $post_id
	 */
	public function delete_intermediary_post( $post_id ) {

		/**
		 * toolset_deleting_association_intermediary_post
		 *
		 * Notify about deleting the intermediary post and allow avoiding it.
		 *
		 * @param bool $delete_post Whether the post should be deleted.
		 * @param int $intermediary_id ID of the intermediary post.
		 */
		$delete_post = apply_filters(
			'toolset_deleting_association_intermediary_post',
			true,
			$post_id
		);

		if ( $delete_post ) {
			add_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );

			// We also need to delete post translations, because WPML doesn't handle that at all.
			// This is out of the scope of the association query, so we'll query for translations
			// individually, per post which we're deleting.
			$this->delete_post_translations( $post_id );

			wp_delete_post( $post_id, true );
			remove_filter( Constants::IS_DELETING_INTERMEDIARY_POST_FILTER, '__return_true' );
		}
	}


	/**
	 * Delete posts which are translations of the provided post.
	 *
	 * Thanks to the implementation of Toolset_WPML_Compatibility::get_post_translations_directly(), this
	 * is safe without WPML as well.
	 *
	 * @param int $post_id
	 */
	private function delete_post_translations( $post_id ) {
		$post_translations = $this->wpml_service->get_post_translations_directly( $post_id );
		foreach ( $post_translations as $post_translation_id ) {
			wp_delete_post( $post_translation_id, true );
		}
	}

}
