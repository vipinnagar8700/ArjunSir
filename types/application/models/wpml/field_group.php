<?php

/**
 * @fixme this is missing documentation
 *
 * @since 2.3
 */
class Types_Wpml_Field_Group implements Types_Wpml_Interface {

	const STRING_NAME = 'name';

	const STRING_DESCRIPTION = 'description';

	/**
	 * @var Types_Wpml_Interface
	 */
	private $name;

	/**
	 * @var Types_Wpml_Interface
	 */
	private $description;


	/**
	 * Types_Wpml_Field_Group constructor.
	 *
	 * @param Toolset_Field_Group $group
	 */
	public function __construct( $group ) {
		// TODO @refactoring get rid of these hard dependencies
		$this->name = new Types_Wpml_Field_Group_String_Name( $group );
		$this->description = new Types_Wpml_Field_Group_String_Description( $group );
	}


	/**
	 * Translate name or description of group
	 *
	 * @param string $part
	 *
	 * @return string
	 */
	public function translate( $part = self::STRING_NAME ) {
		switch ( $part ) {
			case self::STRING_NAME:
				return $this->translate_name();
			case self::STRING_DESCRIPTION:
				return $this->translate_description();
			default:
				return '';
		}
	}


	/**
	 * Translate name of the group
	 *
	 * @return string
	 */
	public function translate_name() {
		return $this->name->translate();
	}


	/**
	 * Translate description of group
	 *
	 * @return string
	 */
	public function translate_description() {
		return $this->description->translate();
	}


	/**
	 * Registration of name and description strings
	 *
	 * @param bool|string $slug_update
	 */
	public function register( $slug_update = false ) {
		$this->name->register( $slug_update );
		$this->description->register( $slug_update );
	}
}
