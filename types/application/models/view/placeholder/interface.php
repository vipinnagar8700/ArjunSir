<?php

/**
 * @since 2.3
 */
interface Types_View_Placeholder_Interface {
	/**
	 * @param string $string
	 * @param mixed $object
	 *
	 * @return mixed
	 */
	public function replace( $string, $object = null );
}
