<?php

namespace OTGS\Toolset\Common\Interop\Handler\Divi;

use OTGS\Toolset\Common\Interop\HandlerInterface;

class DiviModules implements HandlerInterface {

	const PAGE_BUILDER_NAME = 'divi';

	public function initialize() {
		/**
		 * Initialize the Divi extension on each individual plugin, on demand.
		 *
		 * To include the Toolset Divi in your plugin:
		 * - Add the Toolset Divi repo as a Composer dependency.
		 * - Add a callback to this action loading the "/vendor/toolset/divi/loader.php" file.
		 *
		 * The loader file will take care of firing always the newest version,
		 * unless the Toolset Divi plugin is used as a standalone glue plugin:
		 * in that case, the glue plugin will always be used instead.
		 */
		do_action( 'toolset_divi_initialize_extension' );
	}
}
