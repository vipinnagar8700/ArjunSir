<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

/**
 * Shortcode generator cache controller: base class for post/term/user fields cache managers.
 *
 * @since 3.3.6
 */
abstract class Base {

	/**
	 * @var ManagerBase
	 */
	protected $manager;

	/**
	 * @var InvalidatorBase
	 */
	protected $invalidator;


	/**
	 * Constructor.
	 *
	 * @param ManagerBase $manager
	 * @param InvalidatorBase $invalidator
	 */
	public function __construct(
		ManagerBase $manager,
		InvalidatorBase $invalidator
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;

	}


	/**
	 * Initialize the controller:
	 * - initialize the cache manager.
	 * - initialize the cache invalidator.
	 *
	 * @since 3.3.6
	 */
	public function initialize() {
		$this->manager->initialize();
		$this->invalidator->initialize();
	}

}
