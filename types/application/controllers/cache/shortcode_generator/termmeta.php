<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

/**
 * Termmeta cache controller.
 *
 * @since 3.3.6
 */
class Termmeta extends Base {

	const TRANSIENT_KEY = 'toolset_types_cache_sg_termmeta';

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta\Manager $manager
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta\Invalidator $invalidator
	 */
	public function __construct(
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta\Manager $manager,
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta\Invalidator $invalidator
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;
	}

}
