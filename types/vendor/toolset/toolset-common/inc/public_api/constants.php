<?php
/**
 * Constants representing a purpose for which a custom field value is rendered.
 *
 * Safe to be used in third-party software as long constants are used (do not hardcode the values directly).
 *
 * @since Types 3.3.5
 */

namespace OTGS\Toolset\Common\PublicAPI\CustomFieldRenderPurpose {

	use Toolset_Field_Renderer_Purpose;

	/**
	 * Field preview (used in the admin, for example in term listings). This will produce HTML code with a preview
	 * of the field value. Depending on the type, it may be a small thumbnail, an excerpt of the value, first few
	 * values of a repeating field, etc. It is probable that it will not show a complete information.
	 */
	const PREVIEW = Toolset_Field_Renderer_Purpose::PREVIEW;

	/**
	 * Probably the most usable format. Field value in an associative array of the same format that is provided
	 * via REST API. Read from here for more information:
	 *
	 * @link https://toolset.com/documentation/programmer-reference/toolset-integration-with-the-rest-api/#formatted-output-for-single-and-repeatable-fields
	 */
	const REST = Toolset_Field_Renderer_Purpose::REST;

	/**
	 * Obtain a raw value of the field. This is equivalent of using the get_value() method of the field instance.
	 */
	const RAW = Toolset_Field_Renderer_Purpose::RAW;
}

/**
 * Constants for element domains.
 *
 * An element as understood by Toolset terminology is either a post, a term or a user. The domain of an element is
 * the information about which one of those three it is. Often, information about the domain needs to be passed explicitly,
 * because we have just the element ID and we wouldn't know what exactly we're dealing with.
 *
 * Safe to be used in third-party software as long constants are used (do not hardcode the values directly).
 *
 * @since Types 3.3.5
 */
namespace OTGS\Toolset\Common\PublicAPI\ElementDomain {

	use Toolset_Element_Domain;

	const POSTS = Toolset_Element_Domain::POSTS;
	const TERMS = Toolset_Element_Domain::TERMS;
	const USERS = Toolset_Element_Domain::USERS;

}

/**
 * Constants for describing a purpose of a custom field group.
 *
 * These values are being returned by CustomFieldGroup::get_purpose().
 *
 * @since Types 3.3.6
 */
namespace OTGS\Toolset\Common\PublicAPI\CustomFieldGroupPurpose {

	use Toolset_Field_Group;
	use Toolset_Field_Group_Post;

	/**
	 * Default field group purpose: Simply a group that can be assigned to multiple post types.
	 */
	const GENERIC = Toolset_Field_Group::PURPOSE_GENERIC;


	/**
	 * Group is attached (only) to the indermediary post type of a relationship
	 */
	const FOR_INTERMEDIARY_POSTS = Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS;


	/**
	 * Group is attached to a post type that acts as a repeating field group
	 */
	const FOR_REPEATING_FIELD_GROUP = Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP;
}
