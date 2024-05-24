<?php

/**
 * @since 2.3
 */
interface Types_Interface_Media extends Types_Interface_Url {

	/**
	 * @param int $id
	 */
	public function set_id( $id );


	/**
	 * @return int
	 */
	public function get_id();


	/**
	 * @return string
	 */
	public function get_title();


	/**
	 * @return string
	 */
	public function get_description();


	/**
	 * @return string
	 */
	public function get_alt();


	/**
	 * @return string
	 */
	public function get_caption();
}
