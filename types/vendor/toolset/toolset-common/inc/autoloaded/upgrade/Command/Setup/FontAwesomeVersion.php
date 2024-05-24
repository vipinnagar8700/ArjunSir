<?php

namespace OTGS\Toolset\Common\Upgrade\Command\Setup;

use OTGS\Toolset\Common\Settings\FontAwesomeSetting;
use Toolset_Result;
use Toolset_Settings;

/**
 * Setup the Font Awesome library version to load at 5 (5.13.0) on new sites.
 *
 * @since 3.6.0
 */
class FontAwesomeVersion implements \OTGS\Toolset\Common\Upgrade\UpgradeCommand {

	/**
	 * Run the command.
	 *
	 * @return Toolset_Result
	 */
	public function run() {
		$toolset_settings = Toolset_Settings::get_instance();
		$font_awesome_setting = new FontAwesomeSetting( $toolset_settings );

		// Setup a new site, which has no value: set to load FA 5.
		if ( false === $font_awesome_setting->load_from_options() ) {
			$font_awesome_setting->set_value( FontAwesomeSetting::FA_5 );
		}

		return new Toolset_Result( true );
	}

}
