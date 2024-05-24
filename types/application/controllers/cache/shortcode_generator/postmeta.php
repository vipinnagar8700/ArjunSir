<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

use OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Postmeta\Invalidator;
use OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Postmeta\Manager;

/**
 * Postmeta cache controller.
 *
 * @since 3.3.6
 */
class Postmeta extends Base {

	const TRANSIENT_KEY = 'toolset_types_cache_sg_postmeta';


	/**
	 * Constructor.
	 *
	 * @param Manager $manager
	 * @param Invalidator $invalidator
	 */
	public function __construct( Manager $manager, Invalidator $invalidator ) {
		parent::__construct( $manager, $invalidator );
	}

}
