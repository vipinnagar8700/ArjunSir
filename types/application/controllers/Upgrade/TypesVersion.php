<?php

namespace OTGS\Toolset\Types\Upgrade;

use OTGS\Toolset\Common\Upgrade\Version;
use Toolset_Constants;

/**
 * Provides access to Types version number (translated to integer).
 *
 * @since 3.4
 */
class TypesVersion implements Version {

	// Legacy option names used to store version string.
	const TYPES_DATABASE_VERSION_OPTION_LEGACY1 = 'WPCF_VERSION';

	const TYPES_DATABASE_VERSION_OPTION_LEGACY2 = 'wpcf-version';

	/** Name of the option used to store version number. */
	const TYPES_DATABASE_VERSION_OPTION = 'types_database_version';


	/** @var Toolset_Constants */
	private $constants;


	/**
	 * TypesVersion constructor.
	 *
	 * @param Toolset_Constants $constants
	 */
	public function __construct( Toolset_Constants $constants ) {
		$this->constants = $constants;
	}


	/**
	 * @inheritDoc
	 */
	public function get_current_version() {
		return $this->convert_version_string_to_number( $this->constants->constant( 'TYPES_VERSION' ) );
	}


	/**
	 * @inheritDoc
	 */
	public function get_version_from_database() {
		$version = (int) get_option( self::TYPES_DATABASE_VERSION_OPTION, 0 );

		if ( 0 === $version ) {
			$version = get_option( self::TYPES_DATABASE_VERSION_OPTION_LEGACY1, 0 );

			if ( 0 === $version ) {
				$version = get_option( self::TYPES_DATABASE_VERSION_OPTION_LEGACY2, 0 );
			}

			if ( $this->constants->constant( 'TYPES_VERSION' ) === $version ) {
				// The proper version in self::TYPES_DATABASE_VERSION_OPTION is not set but the value from
				// the legacy options say the current plugin version - that means it has been set by the
				// legacy codebase, and we need to run the upgrade to get up-to-date.
				//
				// Next time, this point won't be reached because self::TYPES_DATABASE_VERSION_OPTION will be set.
				return 0;
			}

			$version = $this->convert_version_string_to_number( $version );
		}

		return $version;
	}


	/**
	 * @inheritDoc
	 */
	public function update_database_version( $version_number ) {
		if ( is_numeric( $version_number ) ) {
			update_option( self::TYPES_DATABASE_VERSION_OPTION, (int) $version_number );
		}
	}


	/**
	 * Transform a version string to a version number.
	 *
	 * The version string looks like this: "major.minor[.maintenance[.revision]]". We expect that all parts have
	 * two digits at most.
	 *
	 * Conversion to version number is done like this:
	 * $ver_num  = MAJOR      * 1000000
	 *           + MINOR        * 10000
	 *           + MAINTENANCE    * 100
	 *           + REVISION         * 1
	 *
	 * That means, for example "1.8.11.12" will be equal to:
	 *                          1000000
	 *                        +   80000
	 *                        +    1100
	 *                        +      12
	 *                        ---------
	 *                        = 1081112
	 *
	 * @param string|int $version_string
	 *
	 * @return int
	 * @since 2.1
	 */
	private function convert_version_string_to_number( $version_string ) {
		if (  0 === $version_string ) {
			return 0;
		}

		$version_parts = explode( '.', $version_string );
		$multipliers = [ 1000000, 10000, 100, 1 ];

		$version_part_count = count( $version_parts );
		$version = 0;
		for ( $i = 0; $i < $version_part_count; ++ $i ) {
			$version_part = (int) $version_parts[ $i ];
			$multiplier = $multipliers[ $i ];

			$version += $version_part * $multiplier;
		}

		return $version;
	}
}
