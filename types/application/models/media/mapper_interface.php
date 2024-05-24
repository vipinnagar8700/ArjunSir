<?php

/**
 * Interface Types_Media_Mapper_Interface
 *
 * @since 2.3
 */
interface Types_Media_Mapper_Interface {

	/**
	 * @param string $url
	 *
	 * @return Types_Media|false
	 */
	public function find_by_url( $url );


	/**
	 * @param int $id
	 *
	 * @return Types_Media
	 */
	public function find_by_id( $id );


	/**
	 * @param Types_Interface_Media $media
	 *
	 * @return mixed
	 */
	public function store( Types_Interface_Media $media );
}
