<?php
namespace OTGS\Toolset\Common\Field\Instance;

use OTGS\Toolset\Common\Field\Accessor\Usermeta;
use RuntimeException;
use Toolset_Field_Accessor_Abstract;
use Toolset_Field_Instance;

/**
 * User field instance.
 *
 * This class exists to ensure that user field-specific operations will be performed consistently.
 *
 * TODO Work in progress, methods for storing data are missing. Check out the terms counterpart.
 *
 * @since Types 3.3
 */
class User extends Toolset_Field_Instance {

	/**
	 * Overwrite current field values with new ones.
	 *
	 * @param array $values Array of values. For non-repetitive field there must be exactly one value. Order of values
	 *     in this array will be stored as sort order.
	 *
	 * @throws RuntimeException
	 */
	public function update_all_values( $values ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		throw new RuntimeException( 'Not implemented' );
	}

	/**
	 * Add a single field value to the database.
	 *
	 * The value will be passed through filters as needed and stored, based on field configuration.
	 *
	 * @param mixed $value Raw value, which MUST be validated already.
	 *
	 * @throws RuntimeException
	 */
	public function add_value( $value ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		throw new RuntimeException( 'Not implemented' );
	}


	/**
	 * @return Toolset_Field_Accessor_Abstract An accessor to get the sort order for repetitive fields.
	 */
	public function get_order_accessor() {
		return new Usermeta(
			$this->get_object_id(), $this->get_order_meta_name(), false, true
		);
	}


}
