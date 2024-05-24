<?php

/**
 * @since 3.4.6
 */
class Types_Field_Service_Store {

	/** @var \Types_Field_Service_Store */
	private static $instance;

	/** @var \Types_Field_Service */
	private $stored;

	/**
	 * Singleton.
	 *
	 * @return \Types_Field_Service_Store
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the service.
	 *
	 * The service can optionally initialize the validation for fields.
	 * This is kept for legacy consistency with how the Types_Field_Service
	 * was instantiated in the past, as one object per field.
	 *
	 * @param bool $load_form_validation
	 * @return \Types_Field_Service
	 */
	public function get_service( $load_form_validation = true ) {
		if ( null === $this->stored ) {
			$this->stored = new Types_Field_Service( false );
		}

		if ( is_admin() && $load_form_validation ) {
			Types_Field_Validation_Form::get_instance( 'post' );
		}

		return $this->stored;
	}

}
