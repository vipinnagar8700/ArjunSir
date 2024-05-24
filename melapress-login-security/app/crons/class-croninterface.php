<?php
/**
 * Interface for defining cron actions.
 *
 * @since 2.1.0
 *
 * @package WordPress
 */

namespace PPMWP\Crons;

/**
 * An abstract class to be used when creating crons. This ensures a consistent
 * way of using them and invoking them.
 */
interface CronInterface {

	/**
	 * Register the cron task here, this is the entrypoint.
	 *
	 * @method register
	 * @since  2.1.0
	 */
	public function register();

	/**
	 * The action to run, optionally this can just register the hook.
	 *
	 * @method action
	 * @since  2.1.0
	 */
	public function action();
}
