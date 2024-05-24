<?php

abstract class Types_Helper_Create_Abstract {

	/**
	 * @param string $type
	 * @param bool|string $name Name for the object
	 * @return false|int
	 */
	abstract public function for_post( $type, $name = false );

	/**
	 * @param string $title
	 * @param string $type
	 * @return \WP_Post|false
	 */
	protected function get_object_by_title( $title, $type ) {
		$objects = get_posts(
			array(
				'post_type'              => $type,
				'title'                  => $title,
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		if ( ! empty ( $objects ) ) {
			return $objects[0];
		}

		return false;
	}
}
