<?php

namespace OTGS\Toolset\Common\Utils;


/**
 * Class for obtaining the current request mode, also acting as an enum for these modes.
 *
 * @package OTGS\Toolset\Common
 */
class RequestMode {

	// Request mode
	const UNDEFINED = '';
	const AJAX = 'ajax';
	const ADMIN = 'admin';
	const FRONTEND = 'frontend';


	/**
	 * @var string One of the mode constants.
	 */
	private $mode = self::UNDEFINED;


	/** @var \Toolset_Constants */
	private $constants;


	/**
	 * RequestMode constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct( \Toolset_Constants $constants = null ) {
		// Note: This class is used in dic.php, so we cannot really expect DIC to be used when instantiating it.
		$this->constants = $constants ?: new \Toolset_Constants();
	}

	/**
	 * Get current request mode.
	 *
	 * Possible values are:
	 * - self::UNDEFINED before the main controller initialization is completed
	 * - self::AJAX when doing an AJAX request
	 * - self::ADMIN when showing a WP admin page
	 * - self::FRONTEND when rendering a frontend page
	 *
	 * @return string
	 * @since 2.3
	 */
	public function get() {
		if( self::UNDEFINED == $this->mode ) {
			$this->determine_request_mode();
		}
		return $this->mode;
	}


	/**
	 * See get_request_mode().
	 */
	private function determine_request_mode() {
		if( is_admin() ) {
			if( $this->constants->defined( 'DOING_AJAX' ) && $this->constants->constant( 'DOING_AJAX' ) ) {
				$this->mode = self::AJAX;
			} else {
				$this->mode = self::ADMIN;
			}
		} else {
			$this->mode = self::FRONTEND;
		}
	}


	public static function all() {
		return array( self::ADMIN, self::AJAX, self::FRONTEND );
	}


	public function is_valid( $value ) {
		return in_array( $value, self::all() );
	}

}