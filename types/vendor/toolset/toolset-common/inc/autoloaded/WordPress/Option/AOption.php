<?php

namespace OTGS\Toolset\Common\Wordpress\Option;

/**
 * Abstract implementation of a WordPress option model.
 *
 * @since Types 3.0 implemented in Types
 * @since 3.3.5 (Types 3.2.5) moved to Toolset Common
 */
abstract class AOption implements IOption {


	/**
	 * Get option.
	 *
	 * @param bool $default Default value of the option.
	 *
	 * @return mixed
	 */
	public function getOption( $default = false ) {
		if ( $this->isAlwaysAutoloaded() ) {
			// If an option doesn't exist, WordPress would try to load it in an additional query once, and then
			// store it in the "notoptions" cache. But if we know that the option is always autoloaded,
			// we can prevent even this one query.
			//
			// The only downside is that the only filter that can influence this behaviour is "pre_cache_alloptions",
			// but that shouldn't cause any problems in the context in which this class is being used.
			$alloptions = wp_load_alloptions();
			if ( ! array_key_exists( $this->getKey(), $alloptions ) ) {
				return $default;
			}
		}

		return get_option( $this->getKey(), $default );
	}

	/**
	 * Update option.
	 *
	 * @param mixed $value Option value.
	 * @param bool $autoload Whether the option should be loaded on every request.
	 */
	public function updateOption( $value, $autoload = true ) {
		$autoload = $autoload || $this->isAlwaysAutoloaded();
		update_option( $this->getKey(), $value, $autoload );
	}

	/**
	 * Delete option.
	 */
	public function deleteOption() {
		delete_option( $this->getKey() );
	}


	/**
	 * Indicates whether the option is always being saved with the "autoload" parameter set to true.
	 *
	 * @return bool
	 * @since 3.2.5
	 */
	protected function isAlwaysAutoloaded() {
		return false;
	}
}
