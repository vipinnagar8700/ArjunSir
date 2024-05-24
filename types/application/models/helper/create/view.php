<?php

class Types_Helper_Create_View extends Types_Helper_Create_Abstract {

	/**
	 * Creates a View for a given post type
	 *
	 * @param string $type
	 * @param bool|string $name Name for the View
	 *
	 * @return false|int
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {
		// check if Views is active
		// TODO @refactoring get rid of the hardcoded dependency
		if (
			! class_exists( WPV_View::class )
			|| ! method_exists( WPV_View::class, 'create' )
		) {
			return false;
		}

		// create name if not set
		if ( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'View for %s', 'wpcf' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		$args = array(
			'view_settings' => array(
				'view-query-mode' => 'normal',
				'view_purpose' => 'all',
				'post_type' => array( $type ),
			),
		);

		$view = WPV_View::create( $name, $args );

		return (int) $view->id;
	}


	/**
	 * Will proof if given name is already in use.
	 * If so it adds an running number until name is available
	 *
	 * @param string $name
	 * @param int $id
	 *
	 * @return string
	 * @since 2.0
	 */
	private function validate_name( $name, $id = 1 ) {
		$name_exists = $this->get_object_by_title( html_entity_decode( $name ), 'view' );
		if ( $name_exists ) {
			$name = $id > 1 ? rtrim( rtrim( $name, (string) ( $id - 1 ) ) ) : $name;

			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
