<?php

namespace OTGS\Toolset\Common\Upgrade;

use InvalidArgumentException;

/**
 * Factory for the OTGS\Toolset\Common\Upgrade\UpgradeCommand classes.
 *
 * @since 2.5.3
 */
class CommandFactory {

	/**
	 * @param string $command_class_name
	 *
	 * @return UpgradeCommand
	 * @throws InvalidArgumentException If the class doesn't exist
	 *     or doesn't implement the OTGS\Toolset\Common\Upgrade\UpgradeCommand interface.
	 */
	public function create( $command_class_name ) {
		if ( ! class_exists( $command_class_name, true ) ) {
			throw new InvalidArgumentException( 'Upgrade command not found.' );
		}

		$command_class = new $command_class_name();

		if ( ! $command_class instanceof UpgradeCommand ) {
			throw new InvalidArgumentException( 'Invalid upgrade command.' );
		}

		return $command_class;
	}

}
