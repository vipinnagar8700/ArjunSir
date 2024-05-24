<?php

namespace OTGS\Toolset\Types\PostType\Part;

use InvalidArgumentException;
use Toolset_Field_Group_Post_Factory;
use Types_Utils_Post_Type_Option;
use Types_Wordpress_Filter;

/**
 * @since 3.2
 */
class Repository {

	/** @var Types_Utils_Post_Type_Option */
	private $gateway_cpts;

	/** @var Toolset_Field_Group_Post_Factory */
	private $gateway_field_groups;

	/** @var Factory */
	private $parts_factory;

	/** @var Types_Wordpress_Filter */
	private $wp_filter;

	/** @var array of CPTs */
	private $_cpts_array;


	/**
	 * Repository constructor.
	 *
	 * @param Types_Utils_Post_Type_Option $gateway_cpts
	 * @param Toolset_Field_Group_Post_Factory $gateway_field_groups
	 * @param Factory $parts_factory
	 * @param Types_Wordpress_Filter $wp_filter
	 */
	public function __construct(
		Types_Utils_Post_Type_Option $gateway_cpts,
		Toolset_Field_Group_Post_Factory $gateway_field_groups,
		Factory $parts_factory,
		Types_Wordpress_Filter $wp_filter
	) {
		$this->gateway_cpts = $gateway_cpts;
		$this->gateway_field_groups = $gateway_field_groups;
		$this->parts_factory = $parts_factory;
		$this->wp_filter = $wp_filter;
	}


	/**
	 * Get slugs of all cpts
	 *
	 * @return array
	 */
	public function get_all_cpt_slugs() {
		return array_keys( $this->get_cpts_array() );
	}


	/**
	 * Get cpt array
	 *
	 * @param string $cpt_slug
	 *
	 * @return array|null
	 */
	public function get_cpt_array_by_slug( $cpt_slug ) {
		if ( ! is_string( $cpt_slug ) || $cpt_slug !== $this->wp_filter->filter_slug( $cpt_slug ) ) {
			throw new InvalidArgumentException( '$slug must be a string.' );
		}

		$cpts_array = $this->get_cpts_array();

		if ( ! isset( $cpts_array[ $cpt_slug ] ) ) {
			// no cpt with requested slug
			return null;
		}

		return $cpts_array[ $cpt_slug ];
	}


	/**
	 * Get ListingFields for the given $cpt_slug
	 *
	 * @param string $cpt_slug
	 *
	 * @return ListingFields|null
	 */
	public function get_cpt_listing_fields_by_slug( $cpt_slug ) {
		if ( ! $cpt_array = $this->get_cpt_array_by_slug( $cpt_slug ) ) {
			return null;
		}

		return $this->parts_factory->create_cpt_listing_fields( $cpt_slug, $cpt_array );
	}


	/**
	 * Get FieldGroups for the given $cpt_slug
	 *
	 * @param string $cpt_slug
	 *
	 * @return FieldGroups|null
	 */
	public function get_cpt_field_groups_by_slug( $cpt_slug ) {
		if ( ! $this->get_cpt_array_by_slug( $cpt_slug ) ) {
			return null;
		}

		$field_groups = $this->gateway_field_groups->get_groups_by_post_type( $cpt_slug );

		return $this->parts_factory->create_cpt_field_groups( $cpt_slug, $field_groups );
	}


	/**
	 * Store given cpt parts
	 *
	 * @param IPostTypePart[] $cpt_parts
	 */
	public function store_cpt_parts( $cpt_parts ) {
		foreach ( $cpt_parts as $cpt_part ) {
			if ( $cpt_part instanceof IPostTypePart ) {
				$this->apply_cpt_part_to_cpt_array( $cpt_part );
			}
		}

		// store the changes
		$this->gateway_cpts->update_post_types( $this->get_cpts_array() );
	}


	/**
	 * Applies a single IPostTypePart to the cpt_array
	 * loop callback of store_cpt_parts( $cpt_parts )
	 *
	 * @param IPostTypePart $cpt_part
	 */
	private function apply_cpt_part_to_cpt_array( IPostTypePart $cpt_part ) {
		$cpts_array = $this->get_cpts_array();

		if ( ! isset( $cpts_array[ $cpt_part->get_cpt_slug() ] ) ) {
			// a part of a cpt can just be stored for existing cpts
			return;
		}

		// apply part to _cpts_array
		$cpt_array_updated = $cpt_part->apply_to_cpt_array( $cpts_array[ $cpt_part->get_cpt_slug() ] );
		$cpts_array[ $cpt_part->get_cpt_slug() ] = $cpt_array_updated;

		$this->_cpts_array = $cpts_array;
	}


	/**
	 * Get all CPTs as array (we store all cpts on one field in wp_potions)
	 *
	 * @return array
	 */
	private function get_cpts_array() {
		if ( $this->_cpts_array === null ) {
			// load cpts
			$this->_cpts_array = $this->gateway_cpts->get_post_types();

			if ( ! is_array( $this->_cpts_array ) ) {
				$this->_cpts_array = array();
			}
		}

		return $this->_cpts_array;
	}
}
