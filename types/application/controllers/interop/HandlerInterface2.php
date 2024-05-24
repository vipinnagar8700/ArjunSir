<?php

namespace OTGS\Toolset\Types\Controller\Interop;

/**
 * Interface for an interop handler object that doesn't use the static access.
 *
 * @since 3.3
 */
interface HandlerInterface2 {

	/**
	 * Initialize the interop handler: Hook into relevant actions and filters, and so on.
	 *
	 * @return void
	 */
	public function initialize();

}
