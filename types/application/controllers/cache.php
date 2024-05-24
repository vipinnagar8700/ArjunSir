<?php

namespace OTGS\Toolset\Types\Controller;

/**
 * Plugin cache controller.
 *
 * This sould be the main cache manager for Toolset Types.
 *
 * @since 3.3.6
 */
class Cache {

	/**
	 * @var \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Base[]
	 */
	private $shortcode_generator_cache = array();

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Postmeta $sg_postmeta_cache
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta $sg_termmeta_cache
	 * @param \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta $sg_usermeta_cache
	 */
	public function __construct(
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Postmeta $sg_postmeta_cache,
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta $sg_termmeta_cache,
		\OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Usermeta $sg_usermeta_cache
	) {
		$this->shortcode_generator_cache = array(
			\Toolset_Element_Domain::POSTS => $sg_postmeta_cache,
			\Toolset_Element_Domain::TERMS => $sg_termmeta_cache,
			\Toolset_Element_Domain::USERS => $sg_usermeta_cache,
		);
	}

	/**
	 * Initialize the plugin cache management.
	 *
	 * @since 3.3.6
	 */
	public function initialize() {
		foreach ( $this->shortcode_generator_cache as $cache_per_domain ) {
			$cache_per_domain->initialize();
		}
	}

}
