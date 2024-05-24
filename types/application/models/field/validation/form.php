<?php

/**
 * Validation actions for forms
 *
 * For now, it only add JS validation files due to legacy code and Post Reference code.
 *
 * @since 2.3
 */
class Types_Field_Validation_Form {

	/**
	 * Self object
	 *
	 * @var Types_Field_Validation_Form
	 * @since 2.3
	 */
	private static $self;


	/**
	 * Constructor
	 *
	 * @param String $form_name WPToolset_Forms_Validation.
	 */
	private function __construct( $form_name ) {
		// This class loads the JS files for fields validation.
		new WPToolset_Forms_Validation( $form_name, '' );
		wp_enqueue_style( Toolset_Assets_Manager::STYLE_TOOLSET_FORMS_BACKEND );
	}

	/**
	 * Singleton generator
	 *
	 * @param String $form_name WPToolset_Forms_Validation.
	 * @return Types_Field_Validation_Form
	 * @since 2.3
	 */
	public static function get_instance( $form_name = '' ) {
		if ( ! self::$self instanceof self ) {
			self::$self = new self( $form_name );
		}
		return self::$self;
	}
}
