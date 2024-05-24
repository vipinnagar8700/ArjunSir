<?php
/**
 * Base Custom Database Table Row Class.
 *
 * @package     Database
 * @subpackage  Row
 * @copyright   Copyright (c) 2019
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Base database row class.
 *
 * This class exists solely for other classes to extend (and to encapsulate
 * database schema changes for those objects) to help separate the needs of the
 * application layer from the requirements of the database layer.
 *
 * For example, if a database column is renamed or a return value needs to be
 * formatted differently, this class will make sure old values are still
 * supported and new values do not conflict.
 */
abstract class Row {


	private $fields = [];


	/**
	 * Construct a database object.
	 *
	 * @param mixed Null by default, Array/Object if not
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( $item = null ) {
		if ( ! empty( $item ) ) {
			$this->init( $item );
		}
	}


	/**
	 * Magic isset'ter for immutability.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @since 1.0.0
	 *
	 */
	public function __isset( $key = '' ) {

		// No more uppercase ID properties ever
		if ( 'ID' === $key ) {
			$key = 'id';
		}

		// Class method to try and call
		$method = "get_{$key}";

		// Return property if exists
		if ( method_exists( $this, $method ) ) {
			return true;

			// Return get method results if exists
		} elseif ( property_exists( $this, $key ) ) {
			return true;
		}

		// Return false if not exists
		return false;
	}


	/**
	 * Magic getter for immutability.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @since 1.0.0
	 *
	 */
	public function __get( $key = '' ) {

		// No more uppercase ID properties ever
		if ( 'ID' === $key ) {
			$key = 'id';
		}

		// Class method to try and call
		$method = "get_{$key}";

		// Return property if exists
		if ( method_exists( $this, $method ) ) {
			return call_user_func( array( $this, $method ) );
		} elseif ( array_key_exists( $key, $this->fields ) ) {
			// Return get method results if exists
			return $this->fields[$key];
		}

		// Return null if not exists
		return null;
	}


	/**
	 * Initialize class properties based on data array.
	 *
	 * @param array $data
	 *
	 * @since 1.0.0
	 *
	 */
	private function init( $data = array() ) {
		$this->set_vars( $data );
	}


	/**
	 * Determines whether the current row exists.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function exists() {
		return ! empty( $this->id );
	}


	/**
	 * Set class variables from arguments.
	 *
	 * @param array $args
	 *
	 * @since 1.0.0
	 */
	protected function set_vars( $args = array() ) {

		// Bail if empty or not an array
		if ( empty( $args ) ) {
			return;
		}

		// Cast to an array
		if ( is_object( $args ) ) {
			$args = (array) $args;
		}

		// Set all properties
		foreach ( $args as $key => $value ) {
			$this->fields[ $key ] = $value;
		}
	}


	public function to_array() {
		return $this->fields;
	}

}
