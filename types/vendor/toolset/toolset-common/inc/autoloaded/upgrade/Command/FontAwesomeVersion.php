<?php

namespace OTGS\Toolset\Common\Upgrade\Command;

use OTGS\Toolset\Common\Result\SingleResult;
use OTGS\Toolset\Common\Settings\FontAwesomeSetting;
use OTGS\Toolset\Common\Upgrade\UpgradeCommand;
use Toolset_Settings;

/**
 * Setup the Font Awesome library version to load at 4 (4.7.0) on existing sites.
 *
 * @since 3.6.0
 */
class FontAwesomeVersion implements UpgradeCommand {

	/**
	 * Run the command.
	 *
	 * @return SingleResult
	 */
	public function run() {
		$toolset_settings = Toolset_Settings::get_instance();
		$font_awesome_setting = new FontAwesomeSetting( $toolset_settings );

		// Update an existing site, which has no value: set to load FA 4.
		if ( false === $font_awesome_setting->load_from_options() ) {
			$font_awesome_setting->set_value( FontAwesomeSetting::FA_4 );
		}

		return new SingleResult( true );
	}

}
