<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta;

use OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\ManagerBase;

/**
 * Cache manager for termmeta fields in the shortcodes GUI.
 */
class Manager extends ManagerBase {

	/**
	 * Get domain.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	protected function get_domain() {
		return \Toolset_Element_Domain::TERMS;
	}

	/**
	 * Get the key for the transient cache.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	protected function get_transient_key() {
		return \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\Termmeta::TRANSIENT_KEY;
	}

	/**
	 * Get the attribute that identifies the meta key in the to-be-generated shortcode.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	protected function get_shortcode_meta_attribute() {
		return 'termmeta';
	}

}
