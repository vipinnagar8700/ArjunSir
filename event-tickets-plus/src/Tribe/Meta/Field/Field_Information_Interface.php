<?php

namespace Tribe\Tickets\Plus\Meta\Field;

/**
 * Interface Field_Information_Interface
 *
 * Provides some basic information about the field.
 *
 * @since 4.12.1
 *
 * @package Tribe\Tickets\Plus\Meta\Field
 */
interface Field_Information_Interface {

	/**
	 * Return the name of the field.
	 * Example: Text, Date, Dropdown, etc.
	 *
	 * @since 4.12.1
	 *
	 * @return string
	 */
	public static function get_name();

	/**
	 * Return the identifier of the field.
	 * Example: text, date, telephone, etc.
	 *
	 * @since 4.12.1
	 *
	 * @return string
	 */
	public static function get_identifier();

}
