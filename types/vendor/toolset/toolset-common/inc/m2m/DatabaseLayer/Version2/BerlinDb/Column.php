<?php
/**
 * Base Custom Database Table Column Class.
 *
 * @package     Database
 * @subpackage  Column
 * @copyright   Copyright (c) 2019
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Base class used for each column for a custom table.
 *
 * @since 4.0
 */
abstract class Column {

	/**
	 * Name for the database column.
	 *
	 * Required. Must contain lowercase alphabetical characters only. Use of any
	 * other character (number, ascii, unicode, emoji, etc...) will result in
	 * fatal application errors.
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * Type of database column.
	 * See: https://dev.mysql.com/doc/en/data-types.html
	 *
	 * @var string
	 */
	private $type = '';

	/**
	 * Length of database column.
	 * See: https://dev.mysql.com/doc/en/storage-requirements.html
	 *
	 * @var string
	 */
	private $length = false;

	/**
	 * Is integer unsigned?
	 * See: https://dev.mysql.com/doc/en/numeric-type-overview.html
	 *
	 * @var bool
	 */
	private $unsigned = true;

	/**
	 * Is integer filled with zeroes?
	 * See: https://dev.mysql.com/doc/en/numeric-type-overview.html
	 *
	 * @var bool
	 */
	private $zerofill = false;

	/**
	 * Is data in a binary format?
	 * See: https://dev.mysql.com/doc/en/binary-varbinary.html
	 *
	 * @var bool
	 */
	private $binary = false;

	/**
	 * Is null an allowed value?
	 * See: https://dev.mysql.com/doc/en/data-type-defaults.html
	 *
	 * @var bool
	 */
	private $allow_null = false;

	/**
	 * Typically empty/null, or date value.
	 * See: https://dev.mysql.com/doc/en/data-type-defaults.html
	 *
	 * @var string
	 */
	private $default = '';

	/**
	 * auto_increment, etc...
	 * See: https://dev.mysql.com/doc/en/data-type-defaults.html
	 *
	 * @var string
	 */
	private $extra = '';

	/**
	 * Typically inherited from the database interface (wpdb).
	 *
	 * By default, this will use the globally available database encoding. You
	 * most likely do not want to change this; if you do, you already know what
	 * to do.
	 *
	 * See: https://dev.mysql.com/doc/mysql/en/charset-column.html
	 *
	 * @var string
	 */
	private $encoding = '';

	/**
	 * Typically inherited from the database interface (wpdb).
	 *
	 * By default, this will use the globally available database collation. You
	 * most likely do not want to change this; if you do, you already know what
	 * to do.
	 *
	 * See: https://dev.mysql.com/doc/mysql/en/charset-column.html
	 *
	 * @var string
	 */
	private $collation = '';

	/**
	 * Is this the primary column?
	 *
	 * By default, columns are not the primary column. This is used by the Query
	 * class for several critical functions, including (but not limited to) the
	 * cache key, meta-key relationships, auto-incrementing, etc...
	 *
	 * @var bool
	 */
	private $primary = false;

	/**
	 * Is this the column used as a unique universal identifier?
	 *
	 * By default, columns are not UUIDs. This is used by the Query class to
	 * generate a unique string that can be used to identify a row in a database
	 * table, typically in such a way that is unrelated to the row data itself.
	 *
	 * @var bool
	 */
	private $uuid = false;


	/**
	 * Maybe validate this data before it is written to the database.
	 *
	 * By default, column data is validated based on the type of column that it
	 * is. You can set this to a callback function of your choice to override
	 * the default validation behavior.
	 *
	 * @var string|callable
	 */
	private $validate = '';

	/**
	 * Array of possible aliases this column can be referred to as.
	 *
	 * These are used by the Query class to allow for columns to be renamed
	 * without requiring complex architectural backwards compatibility support.
	 *
	 * @var   array
	 */
	private $aliases = array();

	/**
	 * @param string $type Type of database column
	 * @param string $name Name of database column
	 * @param int $length Length of database column
	 * @param bool $is_unsigned Is integer unsigned?
	 * @param bool $allow_null Is null an allowed value?
	 * @param mixed $default_value Typically empty/null, or date value
	 * @param string $extra auto_increment, etc...
	 * @param string $encoding Typically inherited from wpdb
	 * @param string $collation Typically inherited from wpdb
	 * @param bool $is_primary Is this the primary column?
	 * @param bool $is_uuid Is this the column used as a universally unique identifier?
	 * @param callable $validator A callback function used to validate on save.
	 * @param array $aliases Array of possible column name aliases.
	 *
	 */
	public function __construct(
		$type, $name, $length,
		$encoding = null, $collation = null,
		$is_unsigned = false, $is_primary = false,
		$allow_null = true, $default_value = null,  $extra = '', $is_uuid = false,
		$validator = null, $aliases = []
	) {
		$this->type = strtoupper( $type );
		$this->name = sanitize_key( $name );
		$this->length = (int) $length;
		$this->unsigned = (bool) $is_unsigned;
		$this->primary = (bool) $is_primary;
		$this->allow_null = (bool) $allow_null;
		$this->default = $default_value;
		$this->extra = $extra;
		$this->encoding = $encoding;
		$this->collation = $collation;
		$this->aliases = array_map( 'sanitize_key', $aliases );

		// All UUID columns need to follow a very specific pattern
		if ( $is_uuid ) {
			$this->name = 'uuid';
			$this->type = 'varchar';
			$this->length = 100;
		}

		$this->validate = $this->sanitize_validation( $validator );
	}


	/**
	 * Return if a column type is numeric or not.
	 *
	 * @return bool
	 */
	public function is_numeric() {
		return $this->is_type( array(
			'tinyint',
			'int',
			'mediumint',
			'bigint',
		) );
	}


	public function is_primary() {
		return $this->primary;
	}


	public function get_name() {
		return $this->name;
	}


	/**
	 * Return if this column is of a certain type.
	 *
	 * @param mixed $type Default empty string. The type to check. Also accepts an array.
	 *
	 * @return bool True if of type, False if not
	 */
	private function is_type( $type = '' ) {

		// If string, cast to array
		if ( is_string( $type ) ) {
			$type = (array) $type;
		}

		// Make them lowercase
		$types = array_map( 'strtolower', $type );

		// Return if match or not
		return (bool) in_array( strtolower( $this->type ), $types, true );
	}


	/**
	 * Sanitize the validation callback
	 *
	 * @param string|callable $callback Default empty string. A callable PHP function name or method
	 *
	 * @return string|callable The most appropriate callback function for the value
	 */
	private function sanitize_validation( $callback = '' ) {

		// Return callback if it's callable
		if ( is_callable( $callback ) ) {
			return $callback;
		}

		// UUID special column
		if ( true === $this->uuid ) {
			$callback = array( $this, 'validate_uuid' );

			// Datetime fallback
		} elseif ( $this->is_type( 'datetime' ) ) {
			$callback = array( $this, 'validate_datetime' );

			// Decimal fallback
		} elseif ( $this->is_type( 'decimal' ) ) {
			$callback = array( $this, 'validate_decimal' );

			// Intval fallback
		} elseif ( $this->is_type( array( 'tinyint', 'int' ) ) ) {
			$callback = 'intval';
		}

		return $callback;
	}


	/**
	 * Fallback to validate a datetime value if no other is set.
	 *
	 * This assumes NO_ZERO_DATES is off or overridden.
	 *
	 * If MySQL drops support for zero dates, this method will need to be
	 * updated to support different default values based on the environment.
	 *
	 * @param string $value Default '0000-00-00 00:00:00'. A datetime value that needs validating
	 *
	 * @return string A valid datetime value
	 */
	public function validate_datetime( $value = '0000-00-00 00:00:00' ) {

		// Handle "empty" values
		if ( empty( $value ) || ( '0000-00-00 00:00:00' === $value ) ) {
			$value = ! empty( $this->default )
				? $this->default
				: '0000-00-00 00:00:00';

			// Convert to MySQL datetime format via date() && strtotime
		} elseif ( function_exists( 'date' ) ) {
			$value = date( 'Y-m-d H:i:s', strtotime( $value ) );
		}

		// Return the validated value
		return $value;
	}


	/**
	 * Validate a decimal
	 *
	 * (Recommended decimal column length is '18,9'.)
	 *
	 * This is used to validate a mixed value before it is saved into a decimal
	 * column in a database table.
	 *
	 * Uses number_format() which does rounding to the last decimal if your
	 * value is longer than specified.
	 *
	 * @param mixed $value Default empty string. The decimal value to validate
	 * @param int $decimals Default 9. The number of decimal points to accept
	 *
	 * @return float
	 */
	public function validate_decimal( $value = 0, $decimals = 9 ) {

		// Protect against non-numeric values
		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		// Protect against non-numeric decimals
		if ( ! is_numeric( $decimals ) ) {
			$decimals = 9;
		}

		// Is the value negative?
		$negative_exponent = ( $value < 0 )
			? - 1
			: 1;

		// Only numbers and period
		$value = preg_replace( '/[^0-9\.]/', '', (string) $value );

		// Format to number of decimals, and cast as float
		$formatted = number_format( $value, $decimals, '.', '' );

		// Adjust for negative values
		return $formatted * $negative_exponent;
	}


	/**
	 * Validate a UUID.
	 *
	 * This uses the v4 algorithm to generate a UUID that is used to uniquely
	 * and universally identify a given database row without any direct
	 * connection or correlation to the data in that row.
	 *
	 * From http://php.net/manual/en/function.uniqid.php#94959
	 *
	 * @param string $value The UUID value (empty on insert, string on update)
	 *
	 * @return string Generated UUID.
	 */
	public function validate_uuid( $value = '' ) {

		// Default URN UUID prefix
		$prefix = 'urn:uuid:';

		// Bail if not empty and correctly prefixed
		// (UUIDs should _never_ change once they are set)
		if ( ! empty( $value ) && ( 0 === strpos( $value, $prefix ) ) ) {
			return $value;
		}

		// Put the pieces together
		$value = sprintf( "{$prefix}%04x%04x-%04x-%04x-%04x-%04x%04x%04x",

			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);

		// Return the new UUID
		return $value;
	}

	/**
	 * Return a string representation of what this column's properties look like
	 * in a MySQL.
	 *
	 * @return string
	 * @todo
	 */
	public function to_string() {
		$tokens = [
			$this->name,
			$this->type . ( 0 !== $this->length ? '(' . $this->length . ')' : '' ),
		];

		if( $this->unsigned ) {
			$tokens[] = 'UNSIGNED';
		}

		if( ! $this->allow_null ) {
			$tokens[] = 'NOT NULL';
		}

		if ( ! empty( $this->default ) ) {
			$tokens[] = "DEFAULT '{$this->default}'";
		} elseif ( false !== $this->default ) {
			// A literal false means no default value

			if ( $this->is_numeric() ) {
				$tokens[] = "DEFAULT '0'";
			} elseif ( $this->is_type( 'datetime' ) ) {
				$tokens[] = "DEFAULT '0000-00-00 00:00:00'";
			} else {
				$tokens[] = "DEFAULT ''";
			}
		}

		$tokens[] = $this->extra;

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$result = implode( ' ', array_filter( $tokens, function( $token ) { return ! empty( $token ); } ) );

		// TODO encoding and collation
		return $result;
	}
}
