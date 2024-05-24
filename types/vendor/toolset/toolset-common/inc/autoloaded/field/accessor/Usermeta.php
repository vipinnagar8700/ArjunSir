<?php

namespace OTGS\Toolset\Common\Field\Accessor;

/**
 * Generic accessor to user meta.
 *
 * @since Types 3.3
 */
class Usermeta extends \Toolset_Field_Accessor_Abstract {

	/**
	 * @return mixed Field value from the database.
	 */
	public function get_raw_value() {
		// Since meta data was historically loaded by get_*_meta() with $single = false,
		// it always returned an array even for single fields. Keeping that for compatibility with toolset-forms and
		// simplicity.
		return get_user_meta( $this->object_id, $this->meta_key, $this->force_single_raw_value );
	}


	/**
	 * @param mixed $value New value to be saved to the database.
	 * @param mixed $prev_value Previous field value. Use if updating an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function update_raw_value( $value, $prev_value = '' ) {
		return update_user_meta( $this->object_id, $this->meta_key, $value, $prev_value );
	}


	/**
	 * Add new metadata. Note that if the accessor is set up for a repetitive field, the is_unique argument
	 * of add_*_meta should be false and otherwise it should be true.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_term_meta/
	 *
	 * @param mixed $value New value to be saved to the database.
	 *
	 * @return mixed
	 */
	public function add_raw_value( $value ) {
		return add_user_meta( $this->object_id, $this->meta_key, $value, $this->is_single );
	}


	/**
	 * Delete field value from the database.
	 *
	 * @param string $value Specific value to be deleted. Use if deleting an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function delete_raw_value( $value = '' ) {
		return delete_user_meta( $this->object_id, $this->meta_key, $value );
	}
}
