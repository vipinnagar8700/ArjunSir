<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

/**
 * Usermeta cache controller.
 *
 * @since 3.3.6
 */
class Usermeta extends Base {

	const TRANSIENT_KEY = 'toolset_types_cache_sg_usermeta';

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta\Manager $manager
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta\Invalidator $invalidator
	 */
	public function __construct(
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta\Manager $manager,
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta\Invalidator $invalidator
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;
	}

}
