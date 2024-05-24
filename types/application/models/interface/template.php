<?php

/**
 * @since 2.3
 */
interface Types_Interface_Template {

	/**
	 * @param string $file
	 * @param mixed $data
	 *
	 * @return string
	 */
	public function render( $file, $data );


	/**
	 * Renders a hidden dialog box
	 *
	 * @param string $id
	 * @param string $template_path
	 *
	 * @return mixed
	 */
	public function prepare_dialog( $id, $template_path );
}
