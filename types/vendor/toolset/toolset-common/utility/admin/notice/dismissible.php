<?php

/**
 * Class Toolset_Admin_Notice_Dismissible
 * This message should be shown until the action is done.
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Dismissible
 *            All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Admin_Notice_Dismissible extends Toolset_Admin_Notice_Abstract {
	/**
	 * Not temporary
	 * @var bool
	 */
	protected $is_temporary = false;

	/**
	 * Notice type
	 * @var string
	 */
	protected $notice_type = 'toolset';

	/**
	 * Not dismissible
	 * @var bool
	 */
	protected $is_dismissible_permanent = true;

	/**
	 * default template file
	 */
	protected function set_default_template_file() {
		$this->template_file = TOOLSET_COMMON_PATH . '/templates/admin/notice/' . $this->notice_type . '.phtml';
	}

	/**
	 * Sets notice type: 'toolset', 'error', 'success' or 'warning'
	 *
	 * @param string $type Warning type.
	 * @since 3.2
	 */
	public function set_type( $type ) {
		if ( in_array( $type, array( 'toolset', 'warning', 'success', 'error' ) ) ) {
			$this->notice_type = $type;
			$this->set_default_template_file();
		}
	}
}
