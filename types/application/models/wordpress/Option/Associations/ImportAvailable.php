<?php

namespace OTGS\Toolset\Types\Wordpress\Option\Associations;

use OTGS\Toolset\Common\Wordpress\Option\AOption;

/**
 * @since 3.0
 */
class ImportAvailable extends AOption {

	/**
	 * @return string
	 */
	public function getKey() {
		return '_toolset_associations_import_is_available';
	}


	/**
	 * Only true|false
	 *
	 * @param bool|mixed $value
	 * @param bool $autoload
	 */
	public function updateOption( $value = true, $autoload = true ) {
		update_option( $this->getKey(), (bool) $value, $this->isAlwaysAutoloaded() );
	}


	/**
	 * @inheritdoc
	 * @return bool
	 * @since 3.2.5
	 */
	protected function isAlwaysAutoloaded() {
		return true;
	}


}
