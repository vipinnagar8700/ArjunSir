<?php

namespace Tribe\Tickets\Plus\Meta;

use Tribe\Tickets\Plus\Meta\Field\Field_Information_Interface;

/**
 * Class Field_Types_Collection
 *
 * A collection of fields. Essentially a type-hinted array where you don't have
 * to check what kind of value you're getting.
 *
 * @since 4.12.1
 *
 * @package Tribe\Tickets\Plus\Meta
 */
class Field_Types_Collection {
	/**
	 * @since 4.12.1
	 *
	 * @var array $collection The collection,
	 */
	private $collection = [];

	/**
	 * Adds a field in the collection.
	 *
	 * @since 4.12.1
	 *
	 * @param string|Field_Information_Interface $field_type
	 */
	public function add( $field_type ) {
		if ( in_array( Field_Information_Interface::class, class_implements( $field_type ), true ) ) {
			$this->collection[ $field_type::get_identifier() ] = $field_type::get_name();
		}
	}

	/**
	 * Removes a field from the collection.
	 *
	 * To remove fields added through filters, unhook the filter that adds them.
	 *
	 * @since 4.12.1
	 * @param string|Field_Information_Interface $field_type The field type to remove.
	 *
	 * @return bool True if field removed. False if doesn't exist or invalid field type.
	 */
	public function remove( $field_type ) {
		// Early bail: Invalid field type.
		if ( ! in_array( Field_Information_Interface::class, class_implements( $field_type ), true ) ) {
			return false;
		}

		// Early bail: We don't have this field in the collection.
		if ( ! array_key_exists( $field_type::get_identifier(), $this->collection ) ) {
			return false;
		}

		unset( $this->collection[ $field_type::get_identifier() ] );

		return true;
	}

	/**
	 * Empties the Collection.
	 *
	 * @since 4.12.1
	 *
	 * @return void
	 */
	public function remove_all() {
		$this->collection = [];
	}

	/**
	 * Get the collection.
	 *
	 * @since 4.12.1
	 *
	 * @return array
	 */
	public function get() {
		$field_types = $this->collection;

		/**
		 * Allow filtering the available field types.
		 *
		 * @since 4.12.1
		 *
		 * @param array                  $field_types List of field types.
		 * @param Field_Types_Collection $collection  The collection object.
		 */
		return apply_filters( 'tribe_tickets_plus_field_types', $field_types, $this );
	}

	/**
	 * Gets the name of a field by it's identifier
	 *
	 * @since 4.12.1
	 *
	 * @param string $field_identifier
	 *
	 * @return string
	 */
	public function get_name_by_id( $field_identifier ) {
		return array_key_exists( $field_identifier, $this->get() ) ? $this->get()[ $field_identifier ] : ucwords( $field_identifier );
	}
}
