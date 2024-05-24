<?php
namespace OTGS\Toolset\Types\Controller\Interop\Managed\Brizy;

use Brizy_Admin_Templates;

/**
 * Gateway to the Brizy theme codebase (hard to test parts).
 *
 * @codeCoverageIgnore
 * @since 3.4.2
 */
class Gateway {

	/**
	 * @return bool
	 */
	public function is_brizy_active() {
		return defined( 'BRIZY_VERSION' );
	}


	/**
	 * @return bool
	 */
	public function brizy_admin_templates_class_exists() {
		return class_exists( Brizy_Admin_Templates::class );
	}


	/**
	 * @return Brizy_Admin_Templates
	 */
	public function get_brizy_admin_templates_instance() {
		// This should be safe because the init method is idempotent.
		return Brizy_Admin_Templates::_init();
	}
}
