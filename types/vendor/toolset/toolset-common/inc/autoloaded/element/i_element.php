<?php

/**
 * Interface for an "element", which is a generic name for posts, users and terms.
 *
 * For instantiating elements, use Toolset_Element::get_instance().
 *
 * Note: All public methods dealing with fields need to call $this->initialize_fields() at the beginning.
 *
 * @since m2m
 */
interface IToolset_Element {


	/**
	 * @return string One of the Toolset_Field_Utils::get_domains() values.
	 */
	public function get_domain();


	/**
	 * @return int ID of the underlying object.
	 */
	public function get_id();


	/**
	 * @return string Element title.
	 */
	public function get_title();


	/**
	 * Load custom fields of the element if they're not loaded yet.
	 *
	 * @return void
	 * @since m2m
	 */
	public function initialize_fields();


	/**
	 * @return bool
	 */
	public function are_fields_loaded();


	/**
	 * Get the object this model is wrapped around.
	 *
	 * @return mixed Depends on the implementation.
	 * @since m2m
	 */
	public function get_underlying_object();


	/**
	 * Determine if the element has a particular field.
	 *
	 * It depends on the field definitions and field groups assigned to the element, not on the actual values in the
	 * database.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or a field slug.
	 * @return bool True if a field with given slug exists.
	 * @throws InvalidArgumentException When the field source has a wrong type.
	 * @since m2m
	 */
	public function has_field( $field_source );


	/**
	 * Get a field instance.
	 *
	 * Check if has_field() before, otherwise may get an exception.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or a field slug.
	 * @return Toolset_Field_Instance
	 * @throws InvalidArgumentException When the field source has a wrong type.
	 */
	public function get_field( $field_source );


	/**
	 * Get all field instances belonging to the element.
	 *
	 * @return Toolset_Field_Instance[]
	 * @since m2m
	 */
	public function get_fields();


	public function get_field_count();


	/**
	 * Determine whether the current element may have translations.
	 *
	 * @return bool
	 */
	public function is_translatable();


	/**
	 * Get element language.
	 *
	 * @return string Language code or an empty string if not applicable.
	 * @since m2m
	 */
	public function get_language();


	/**
	 * Return an element translation.
	 *
	 * If the element domain and type are non-translatable, it will return itself.
	 *
	 * If the element could be translated to the target language but is not,
	 * the return value will depend on the $exact_match_only parameter:
	 * If it's true, it will return null. Otherwise, it will return the best possible
	 * translation (default language/original/any).
	 *
	 * @param string $language_code
	 * @param bool $exact_match_only
	 *
	 * @return IToolset_Element|null
	 * @since 2.5.10
	 */
	public function translate( $language_code, $exact_match_only = false );


	/**
	 * ID of the element in the default language or same as get_id() if not applicable.
	 *
	 * @return int
	 * @since 2.5.10
	 */
	public function get_default_language_id();


	/**
	 * Obtain the group_id from the connected element table.
	 *
	 * Optionally, it can be created if missing.
	 *
	 * Note that this will work only when the second version of the relationship database layer is
	 * active, null will be returned otherwise.
	 *
	 * @param bool $create_if_missing Add the element to the table and provide a new group ID if
	 *     it's not there yet. Note that null can still be returned if the correct database layer isn't
	 *     active.
	 * @param bool $cached_only Only return cached value or null. Overrides the first parameter.
	 *
	 * @return int|null
	 * @since 4.0
	 */
	public function get_connected_group_id( $create_if_missing = false, $cached_only = false );


	/**
	 * Set the group_id value from the connected element table to this object's cache.
	 *
	 * It is the responsibility of the caller to make sure the value is correct.
	 * Nothing will be stored to the database this way, it is just a possible performance improvement.
	 *
	 * @param int $group_id
	 * @return void
	 * @since 4.0
	 */
	public function set_connected_group_id( $group_id );
}
