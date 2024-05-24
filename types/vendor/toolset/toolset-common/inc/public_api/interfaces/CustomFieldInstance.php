<?php

namespace OTGS\Toolset\Common\PublicAPI;

/**
 * Public interface for a field instance.
 *
 * Field instance means this is a particular field for a particular element (post/term/user),
 * and it is possible to access the field value.
 *
 * Safe to be used in third-party software.
 *
 * @package OTGS\Toolset\Common\PublicAPI
 * @since Types 3.3.5
 */
interface CustomFieldInstance {

	/**
	 * Get the custom field definition.
	 *
	 * @return CustomFieldDefinition
	 */
	public function get_definition();


	/**
	 * Get the raw custom field value.
	 *
	 * The result is not sanitized and the data structure returns on the field type and other properties.
	 * It is recommended to use the render() method instead.
	 *
	 * @return mixed
	 */
	public function get_value();


	/**
	 * Render the field value for a given purpose.
	 *
	 * @param string $purpose One of the purposes as defined in OTGS\Toolset\Common\PublicAPI\CustomFieldRenderPurpose.
	 * @return mixed Depending on the field type and purpose.
	 */
	public function render( $purpose );

}
