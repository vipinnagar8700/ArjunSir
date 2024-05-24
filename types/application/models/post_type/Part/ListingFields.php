<?php

namespace OTGS\Toolset\Types\PostType\Part;

use InvalidArgumentException;

/**
 * This class is for handling the fields the user has chosen to show on the posts overview listing table.
 *
 * @since 3.2
 */
class ListingFields extends APostTypePart {

	/**
	 * @var array
	 */
	private $custom_field_slugs = array();


	/**
	 * Apply changes to listing fields to the given $cpt array.
	 *
	 * @param array $cpt_array
	 *
	 * @return array
	 */
	public function apply_to_cpt_array( $cpt_array ) {
		if ( ! is_array( $cpt_array ) ) {
			return $cpt_array;
		}

		$cpt_array['custom_fields'] = array();

		// apply custom fields
		foreach ( $this->custom_field_slugs as $field_slug ) {
			// that's how the legacy structure is build and we need to respect it for now
			$cpt_array['custom_fields'][ $field_slug ] = 1;
		}

		if ( empty( $cpt_array['custom_fields'] ) ) {
			// no need to store it empty
			unset( $cpt_array['custom_fields'] );
		}

		return $cpt_array;
	}


	/**
	 * Add a field slug
	 *
	 * @param string|int|mixed $field_slug
	 */
	public function add_field_by_slug( $field_slug ) {
		if ( ! is_string( $field_slug ) && ! is_int( $field_slug ) ) {
			throw new InvalidArgumentException( 'slug must be a string or integer' );
		}

		if ( $this->has_field_by_slug( $field_slug ) ) {
			// already exist
			return;
		}

		$this->custom_field_slugs[] = $field_slug;
	}


	/**
	 * Remove a field slug
	 *
	 * @param string $field_slug
	 */
	public function remove_field_by_slug( $field_slug ) {
		$field_key = array_search( $field_slug, $this->custom_field_slugs, true );

		if ( $field_key === false ) {
			// check for prefix version
			$field_key = array_search( 'wpcf-' . $field_slug, $this->custom_field_slugs, true );
			if ( $field_key === false ) {
				// the field does not exist in custom fields
				return;
			}
		}

		unset( $this->custom_field_slugs[ $field_key ] );
	}


	/**
	 * Search if a field exists on the listing
	 *
	 * @param string $field_slug
	 *
	 * @return bool
	 */
	public function has_field_by_slug( $field_slug ) {
		return in_array( $field_slug, $this->custom_field_slugs, true )
			|| in_array( 'wpcf-' . $field_slug, $this->custom_field_slugs, true );
	}


	/**
	 * Each part handles the extraction of the required data of the $cpt_array
	 * This is not optimal, but he have to work with the givens of the past.
	 *
	 * @param array $cpt_array
	 */
	public function init_by_cpt_array( $cpt_array ) {
		if ( ! isset( $cpt_array['custom_fields'] ) || ! is_array( $cpt_array['custom_fields'] ) ) {
			return;
		}

		foreach ( $cpt_array['custom_fields'] as $field_slug => $one ) {
			$this->add_field_by_slug( $field_slug );
		}
	}
}
