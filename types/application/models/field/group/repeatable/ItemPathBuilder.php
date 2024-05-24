<?php

namespace OTGS\Toolset\Types\Field\Group\Repeatable;

use IToolset_Element;
use OTGS\Toolset\Common\Relationships\API\Factory;
use Toolset_Relationship_Origin_Repeatable_Group;
use Toolset_Relationship_Role_Parent;

/**
 * For a given (potentially nested) RFG item ID, get it's path in the tree up to the parent post where the item belongs.
 *
 * Uses caching to prevent redundant database queries. Reuse if possible.
 *
 * @since 3.4
 */
class ItemPathBuilder {

	/** @var int[] Mapping from RFG item IDs to their parent IDs (or null if there's no parent) */
	private $item_to_parent_id = [];

	/** @var string[] Mapping known RFG item post titles by their IDs. */
	private $item_to_title = [];

	/** @var Factory */
	private $relationships_factory;


	/**
	 * ItemPathBuilder constructor.
	 *
	 * @param Factory $relationships_factory
	 */
	public function __construct( Factory $relationships_factory ) {
		$this->relationships_factory = $relationships_factory;
	}


	/**
	 * For a given RFG item ID, build its path from the parent post according to this item's nesting.
	 *
	 * @param int $item_id
	 *
	 * @return string[] Path from the parent to the item itself. RFG item post titles indexed by their IDs. The parent
	 *     post itself, where the RFG belongs, is not included.
	 */
	public function get_item_path( $item_id ) {
		$path = [ $item_id ];

		$next_item_id = $item_id;
		while( true ) {
			$parent_id = $this->get_item_parent( $next_item_id );

			if( ! $parent_id ) {
				break;
			}

			$path[] = $parent_id;
			$next_item_id = $parent_id;
		}

		// Remove the top-most parent since that's the parent post that is being translated.
		array_pop( $path );

		$results = [];
		foreach( array_reverse( $path ) as $parent_id ) {
			$results[ $parent_id ] = $this->get_item_title( $parent_id );
		}

		return $results;
	}


	/**
	 * Get item's parent if one exists with in-memory caching.
	 *
	 * @param int $item_id
	 *
	 * @return int|null
	 */
	private function get_item_parent( $item_id ) {
		if ( ! array_key_exists( $item_id, $this->item_to_parent_id ) ) {
			$this->item_to_parent_id[ $item_id ] = $this->query_item_parent( $item_id );
		}

		return $this->item_to_parent_id[ $item_id ];
	}


	/**
	 * Get item's post title with in-memory caching.
	 *
	 * @param int $item_id
	 *
	 * @return string|null
	 */
	private function get_item_title( $item_id ) {
		if ( ! array_key_exists( $item_id, $this->item_to_title ) ) {
			$item = get_post( $item_id );
			$this->item_to_title[ $item_id ] = $item instanceof \WP_Post ? $item->post_title : null;
		}

		return $this->item_to_title[ $item_id ];
	}


	/**
	 * Query item's parent using any relationship with a RFG origin (there will be only one).
	 *
	 * Store the parent's title in the in-memory cache.
	 *
	 * @param int $item_id
	 *
	 * @return int|null
	 */
	private function query_item_parent( $item_id ) {
		$query = $this->relationships_factory->association_query();

		// Depending on what the relationship API allows with the currently used database layer, make sure
		// some parent item will be returned if it exists.
		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			$query->force_display_as_translated_mode();
		} else {
			$query->include_original_language();
		}

		/** @var IToolset_Element[] $parents */
		$parents = $query->add( $query->child_id( $item_id ) )
			// To make sure we get only RFG items.
			->add( $query->has_origin( Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ) )
			->return_element_instances( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();

		if ( empty( $parents ) ) {
			return null;
		}

		/** @var IToolset_Element $parent */
		$parent = reset( $parents );
		$this->item_to_title[ $parent->get_id() ] = $parent->get_title();

		return $parent->get_id();
	}

}
