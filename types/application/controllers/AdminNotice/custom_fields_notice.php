<?php
/**
 * Shows a notices explaining how Custom Fields works.
 *
 * @since 2.3
 */
class Types_Custom_Fields_Admin_Notice_Dismissible extends Toolset_Admin_Notice_Dismissible {


	/**
	 * Default template file.
	 */
	protected function set_default_template_file() {
		$this->template_file = TYPES_ABSPATH . '/application/views/admin_notice/custom_fields.phtml';
	}
}
