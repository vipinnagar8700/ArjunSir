<?php

namespace OTGS\Toolset\Types\Field\Group;

/**
 * Field group viewmodel.
 *
 * At the moment, this is adjusted for the purposes of the Custom Field Group listing page.
 *
 * @since 3.2.5
 */
interface ViewmodelInterface {

	/**
	 * Produce an associative array with the field group representation.
	 *
	 * @return array
	 */
	public function to_json();


	/**
	 * Determine or set whether the field group is activated.
	 *
	 * @param null|bool $new_value If a boolean is provided, the value will be set to the field group.
	 *
	 * @return bool
	 */
	public function is_active( $new_value = null );
}
