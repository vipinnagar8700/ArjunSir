<?php

namespace OTGS\Toolset\Types\Field\Group\Repeatable;

use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Wordpress\Meta;
use Toolset_Post;

/**
 * Handles the RFG item sort order reading and storage in the context of potentially translatable content.
 *
 * We keep the sort order manually synchronized across item translations, since canonical WPML TM hooks are taken
 * out of the equation during this process.
 *
 * @since 3.4.2
 */
class SortOrderManagement {

	/** @var Meta */
	private $meta;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * SortOrderManagement constructor.
	 *
	 * @param Meta $meta
	 */
	public function __construct( Meta $meta, WpmlService $wpml_service ) {
		$this->meta = $meta;
		$this->wpml_service = $wpml_service;
	}



	/**
	 * Get the stored sort order value for a specific item.
	 *
	 * Defaults to zero if not set.
	 *
	 * @param int $item_id
	 * @return int
	 */
	public function get_sort_order( $item_id ) {
		return (int) $this->meta->get_post_meta( $item_id, Toolset_Post::SORTORDER_META_KEY, true );
	}


	/**
	 * Set the sort order for a new item translation.
	 *
	 * This assumes the original item, regardless of its language, has the correct sort order (which definitely
	 * should be the case because of how update_sort_order() is implemented.
	 *
	 * @param int $item_id ID of the newly created RFG item translation.
	 * @param int $original_item_id ID of the original item version that is being translated.
	 */
	public function initialize_sort_order( $item_id, $original_item_id ) {
		// Use the same sort order as the original item.
		// From now on, these values will be synchronized on saving.
		$original_sort_order = (int) $this->meta->get_post_meta( $original_item_id, Toolset_Post::SORTORDER_META_KEY, true );
		$this->meta->update_post_meta( $item_id, Toolset_Post::SORTORDER_META_KEY, $original_sort_order );
	}


	/**
	 * Update sort order for a particular RFG item and all of its translations in case it's translatable.
	 *
	 * @param int $source_item_id ID of the item to update.
	 * @param int $sort_order The new sort order value.
	 */
	public function update_sort_order( $source_item_id, $sort_order ) {
		$item_ids = $this->get_items_to_update( $source_item_id );

		foreach( $item_ids as $item_id ) {
			$this->meta->update_post_meta( $item_id, Toolset_Post::SORTORDER_META_KEY, (int) $sort_order );
		}
	}


	private function get_items_to_update( $item_id ) {
		$trid = $this->wpml_service->get_post_trid( $item_id );

		if ( ! $trid ) {
			// The post is not translatable.
			return [ $item_id ];
		}

		return $this->wpml_service->get_post_translations( $trid );
	}

}
