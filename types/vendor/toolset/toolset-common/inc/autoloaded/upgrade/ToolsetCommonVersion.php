<?php


namespace OTGS\Toolset\Common\Upgrade;


use Toolset_Constants;

/**
 * Provides access to database version of Toolset Common.
 *
 * Please note that the Relationships (m2m) module has its own state that is independent of this versioning.
 *
 * @since 4.0
 */
class ToolsetCommonVersion implements Version {

	/** Name of the option used to store version number. */
	const DATABASE_VERSION_OPTION_NUMBER = 'toolset_data_structure_version';

	/** @var Toolset_Constants */
	private $constants;


	/**
	 * ToolsetCommonVersion constructor.
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
		return $this->constants->defined( 'TOOLSET_DATA_STRUCTURE_VERSION' )
			? (int) $this->constants->constant( 'TOOLSET_DATA_STRUCTURE_VERSION' )
			: 0;
	}


	/**
	 * @inheritDoc
	 */
	public function get_version_from_database() {
		return (int) get_option( self::DATABASE_VERSION_OPTION_NUMBER, 0 );
	}


	/**
	 * @inheritDoc
	 */
	public function update_database_version( $version_number ) {
		if ( is_numeric( $version_number ) ) {
			update_option( self::DATABASE_VERSION_OPTION_NUMBER, (int) $version_number, true );
		}
	}
}
