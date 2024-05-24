<?php /** @noinspection DuplicatedCode */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence;

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
 * @since 4.0
 */
class IntermediaryPostPersistence implements \OTGS\Toolset\Common\Relationships\API\IntermediaryPostPersistence {


	/**
	 * Number of items handled each loop.
	 */
	const DEFAULT_LIMIT = 50;


	/**
	 * @var IToolset_Relationship_Definition
	 */
	private $relationship;


	/** @var \OTGS\Toolset\Common\WPML\WpmlService */
	private $wpml_service;


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * Class constructor
	 *
	 * @param IToolset_Relationship_Definition|null $relationship Relationship.
	 * @param \OTGS\Toolset\Common\WPML\WpmlService $wpml_service
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory
	 */
	public function __construct(
		\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory,
		\OTGS\Toolset\Common\WPML\WpmlService $wpml_service,
		IToolset_Relationship_Definition $relationship = null
	) {
		$this->relationship = $relationship;
		$this->wpml_service = $wpml_service;
		$this->database_layer_factory = $database_layer_factory;
	}


	/**
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function create_empty_association_intermediary_post( $association ) {
		$intermediary_id = (int) $this->create_intermediary_post(
			$association->get_element( new Toolset_Relationship_Role_Parent() )->get_id(),
			$association->get_element( new Toolset_Relationship_Role_Child() )->get_id()
		);
		if ( $intermediary_id ) {
			$this->database_layer_factory->association_database_operations()
				->update_association_intermediary_id( $association->get_uid(), $intermediary_id );
		}

		return $intermediary_id;
	}


	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 *
	 * TODO this may be simplified since the default language no longer plays major role when dealing with IPTs.
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
