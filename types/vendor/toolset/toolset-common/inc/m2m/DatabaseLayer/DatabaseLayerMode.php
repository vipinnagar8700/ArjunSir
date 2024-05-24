<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

/**
 * Provides and manages the version of the relationships database layer.
 *
 * This is the single source of information but it should be used only by the DatabaseLayerFactory
 *
 * @since 4.0
 */
class DatabaseLayerMode {

	/** @var string Name of the option that stores the database layer mode value. */
	const OPTION_NAME = 'toolset_relationship_db_layer';

	/** @var string First version of the database layer (introduced in Types 3.0) */
	const VERSION_1 = 'version1';

	/**
	 * @var string Fallback mode of the second version (with full support for translatable associations,
	 *     since Types 3.4).
	 */
	const FALLBACK = 'version2_fallback';

	/** @var array Represents the second version of the database structure, whatever particular mode is being used. */
	const VERSION_2 = [ self::FALLBACK ];

	/** @var string[] Valid database layer modes. */
	const VALID_MODES = [ self::VERSION_1, self::FALLBACK ];

	/** @var string */
	private $database_layer_mode;


	/**
	 * Retrieve the database layer mode.
	 *
	 * @return string Always a valid mode.
	 */
	public function get() {
		if ( null === $this->database_layer_mode ) {
			$option_value = get_option( self::OPTION_NAME );
			if ( ! $this->is_valid( $option_value ) ) {
				$option_value = self::VERSION_1;
			}
			$this->database_layer_mode = $option_value;
		}

		return $this->database_layer_mode;
	}


	/**
	 * Set a new database layer mode.
	 *
	 * Use with great caution.
	 *
	 * @param $new_database_layer_mode
	 *
	 * @throws \InvalidArgumentException
	 */
	public function set( $new_database_layer_mode ) {
		if ( ! $this->is_valid( $new_database_layer_mode ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Trying to set an invalid relationship database layer mode "%s".', $new_database_layer_mode )
			);
		}

		update_option( self::OPTION_NAME, $new_database_layer_mode, true );
		$this->database_layer_mode = null;
	}


	/**
	 * @param string $database_layer_mode
	 *
	 * @return bool
	 */
	private function is_valid( $database_layer_mode ) {
		return in_array( $database_layer_mode, self::VALID_MODES );
	}


	/**
	 * Safely compare the given mode value to the current mode.
	 *
	 * If an array of values is provided, the function returns true if at least one of them matches
	 * the current mode.
	 *
	 * @param string[]|string $mode
	 *
	 * @return bool
	 */
	public function is( $mode ) {
		return (
			is_array( $mode ) && in_array( $this->get(), $mode, true )
			|| is_string( $mode ) && $this->get() === $mode
		);
	}
}
