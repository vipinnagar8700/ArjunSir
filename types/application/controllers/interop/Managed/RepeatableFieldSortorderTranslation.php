<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed;

use InvalidArgumentException;
use OTGS\Toolset\Common\Field\FieldDefinitionRepositoryFactory;
use OTGS\Toolset\Common\WPML\CustomFieldTranslationSetting;
use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;
use OTGS\Toolset\Types\Wordpress\Hooks;
use OTGS\Toolset\Types\Wordpress\Meta;
use Toolset_Element_Domain;
use wpdb;

/**
 * Fixes the sort order for the values of repeatable custom fields that are
 * copied to other languages.
 *
 * The need for this interop code arises from a combinations of several facts:
 * - When saving custom fields, all the (post)meta is first deleted and then added again.
 *   That ensures it's in correct order and the natural sorting (by meta_id) when fetching the
 *   data usually works by default.
 * - Additionally, Types stores the order of meta_ids in a dedicated hidden sort-order meta value.
 * - If WPML is active and a repeatable field has the "Copy" translation setting, WPML will just
 *   copy its values but ignores the sort-order data.
 * - During this process, WPML doesn't recreate all metadata like Types does, it just adds or removes
 *   the records that actually differ.
 * - Hence, reordering custom fields on an original post is not reflected on the translations in this case.
 * - Types itself doesn't really use the sort-order value when rendering the fields,
 *   because it quietly relies on the way it saves the fields itself.
 *
 * To make sure everything works even for field values copied by WPML, we actually make use
 * of the sort-order meta value.
 *
 * 1. After WPML copies a repeatable field value, we translate also the sort-order. It can't be
 *    just copied because it references specific meta_id values which are different for the
 *    translated element, so we actually have to rebuild it.
 * 2. We adjust the field values after loading them from the database, using a new filter
 *    'types_maybe_apply_field_sortorder' (this solution has been chosen because it's used
 *    from a number of different contexts, including some *very* ancient code in Toolset Common).
 *
 * Note: The issue is currently present only for post fields, but this might need to be
 * extended to other domains if WPML introduces support for other metadata translation.
 *
 * @since 3.4.9
 */
class RepeatableFieldSortorderTranslation implements HandlerInterface2 {

	/** @var Hooks */
	private $hooks;

	/** @var Meta */
	private $meta;

	/** @var FieldDefinitionRepositoryFactory */
	private $field_definition_repository_factory;

	/** @var wpdb */
	private $wpdb;


	/**
	 * RepeatableFieldSortorderTranslation constructor.
	 *
	 * @param Hooks $hooks
	 * @param Meta $meta
	 * @param FieldDefinitionRepositoryFactory $field_definition_repository_factory
	 * @param wpdb $wpdb
	 */
	public function __construct(
		Hooks $hooks,
		Meta $meta,
		FieldDefinitionRepositoryFactory $field_definition_repository_factory,
		wpdb $wpdb
	) {
		$this->hooks = $hooks;
		$this->meta = $meta;
		$this->field_definition_repository_factory = $field_definition_repository_factory;
		$this->wpdb = $wpdb;
	}


	/**
	 * Initialize the interop handler.
	 */
	public function initialize() {
		$this->hooks->add_action( 'wpml_after_copy_custom_field', [ $this, 'on_custom_field_copied' ], 10, 3 );

		/**
		 * Filter types_maybe_apply_field_sortorder.
		 *
		 * @param string[] $field_values Values of a repeatable custom field.
		 * @param string $fields_slug
		 * @param string $domain
		 * @return string Reordered values of the custom field.
		 */
		$this->hooks->add_filter( 'types_maybe_apply_field_sortorder', [ $this, 'apply_sortorder' ], 10, 3 );
	}


	/**
	 * Handles step 1 from the class docblock.
	 *
	 * @param int|numeric-string $post_id_from Original post ID.
	 * @param int|numeric-string $post_id_to Translation of the post where the custom field has been copied.
	 * @param string $meta_key Custom field meta_key (not a slug).
	 */
	public function on_custom_field_copied( $post_id_from, $post_id_to, $meta_key ) {
		$field_definition = $this->field_definition_repository_factory
			->get_repository( Toolset_Element_Domain::POSTS )
			->get_field_definition_by_meta_key( $meta_key );

		if (
			! $field_definition
			|| ! $field_definition->is_repeatable()
			|| ! $field_definition->is_managed_by_types()
			|| CustomFieldTranslationSetting::COPY !== $field_definition->get_translation_setting()
		) {
			// Nothing to do here.
			return;
		}

		try {
			$original_field = $field_definition->instantiate( (int) $post_id_from );
			$copied_field = $field_definition->instantiate( (int) $post_id_to );
		} catch ( InvalidArgumentException $e ) {
			// Something unexpected happened (probably, one of the posts doesn't exist).
			// We can't do anything but bail quietly.
			return;
		}

		// This should be an array of meta_id values in the desired order.
		$original_sort_order = $original_field->get_sort_order();
		if ( ! is_array( $original_sort_order ) ) {
			return;
		}

		// We need to translate it to the meta_ids of *corresponding* postmeta
		// values of the translated post.
		$translation_sort_order = [];
		foreach ( $original_sort_order as $index => $meta_id ) {
			$meta_row = $this->meta->get_post_meta_by_id( $meta_id );
			if ( ! is_object( $meta_row ) ) {
				return;
			}

			$translation_meta_id = $this->get_meta_id_by_value(
				$field_definition->get_meta_key(),
				$post_id_to,
				$meta_row->meta_value
			);

			$translation_sort_order[ $index ] = $translation_meta_id;
		}

		$copied_field->set_sort_order( $translation_sort_order );
	}


	/**
	 * Applies the custom sort order on values of a repeatable field.
	 *
	 * Only post fields are supported at the moment.
	 *
	 * @param string[] $values Values of the repeatable field.
	 * @param string $field_slug Field slug (not meta_key).
	 * @param string $domain Element domain.
	 *
	 * @return string[] Reordered values of the repeatable field.
	 */
	public function apply_sortorder( $values, $field_slug, $domain ) {
		$sorted_values = $this->get_sorted_values( $values, $field_slug, $domain );

		return $sorted_values ? : $values;
	}


	/**
	 * Sort repeatable custom field values according to the sort-order data stored
	 * in a separate meta entry.
	 *
	 * @param string[] $values Values to sort.
	 * @param string $field_slug Field slug (not meta_key).
	 * @param string $domain Element domain. Only posts are supported at the moment.
	 *
	 * @return array|null Sorted values or null if not applicable.
	 */
	private function get_sorted_values( $values, $field_slug, $domain ) {
		global $post;

		if ( ! is_array( $values )
			|| empty( $values )
			|| Toolset_Element_Domain::POSTS !== $domain
			|| ! $post
		) {
			// We can't work in this context.
			return null;
		}

		$field_definition = $this->field_definition_repository_factory
			->get_repository( $domain )
			->load_field_definition( $field_slug );

		if ( ! $field_definition || ! $field_definition->is_repeatable() ) {
			return null;
		}

		try {
			$field = $field_definition->instantiate( $post->ID );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		$sort_order = $field->get_sort_order();
		if ( ! is_array( $sort_order ) || empty( $sort_order ) ) {
			return null;
		}

		// Now we need to get the meta_id for each value, which will be then used for
		// sorting.
		//
		// Basically, we will have:
		// $sort_order: $index => $meta_id
		// $values_by_mid: $meta_id => $value
		//
		// And we need: $index => $value.
		$values_by_mid = [];
		foreach ( $values as $value ) {
			$meta_id = $this->get_meta_id_by_value( $field_definition->get_meta_key(), $post->ID, $value );
			if ( 0 === $meta_id ) {
				return null; // Something is wrong, we just bail.
			}
			$values_by_mid[ $meta_id ] = $value;
		}

		// Sort by the position of the array key (meta_id) in the $sort_order array.
		uksort( $values_by_mid, function ( $a, $b ) use ( $sort_order ) {
			$a_order = $this->get_order_by_meta_id( $sort_order, $a );
			$b_order = $this->get_order_by_meta_id( $sort_order, $b );

			return $a_order - $b_order;
		} );

		return array_values( $values_by_mid );
	}


	private function get_order_by_meta_id( $sort_order, $meta_id ) {
		return (int) array_search( $meta_id, $sort_order, false );
	}


	/**
	 * Retrieve the meta_id for a particular custom field value.
	 *
	 * @param string $meta_key
	 * @param int $post_id
	 * @param string $value
	 *
	 * @return int Meta_id value or zero if not found.
	 */
	private function get_meta_id_by_value( $meta_key, $post_id, $value ) {
		return (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT meta_id
				FROM {$this->wpdb->postmeta}
				WHERE post_id = %d
					AND meta_key = %s
					AND meta_value = %s",
			$post_id,
			$meta_key,
			$value
		) );
	}
}
