<?php

/**
 * @since 2.0
 */
class Types_Helper_Create_Form extends Types_Helper_Create_Abstract {

	/**
	 * Creates a form for a given post type
	 *
	 * @param string $type
	 * @param bool|string $name Name for the Form
	 *
	 * @return false|int
	 * @since 2.0
	 */
	public function for_post( $type, $name = false ) {

		// abort if CRED is not installed
		if (
			! defined( 'CRED_CLASSES_PATH' )
			|| ! defined( 'CRED_FORMS_CUSTOM_POST_NAME' )
		) {
			return false;
		}

		// abort if FormCreator does not exists
		if ( ! file_exists( CRED_CLASSES_PATH . '/CredFormCreator.php' ) ) {
			return false;
		}

		// load form creator
		// TODO @refactoring get rid of the hardcoded dependency
		require_once( CRED_CLASSES_PATH . '/CredFormCreator.php' );

		// abort if cred_create_form is not available
		if ( ! class_exists( 'CredFormCreator' )
			|| ! method_exists( 'CredFormCreator', 'cred_create_form' ) ) {
			return false;
		}

		// create name if not given
		if ( ! $name ) {
			$type_object = get_post_type_object( $type );
			$name = sprintf( __( 'Form for %s', 'wpcf' ), $type_object->labels->name );
		}

		$name = $this->validate_name( $name );

		return (int) CredFormCreator::cred_create_form( $name, 'new', $type );
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
		$name_exists = $this->get_object_by_title(
			html_entity_decode( $name ),
			defined( 'CRED_FORMS_CUSTOM_POST_NAME' ) ? CRED_FORMS_CUSTOM_POST_NAME : 'cred-form'
		);

		if ( $name_exists ) {
			$name = $id > 1 ? rtrim( rtrim( $name, (string) ( $id - 1 ) ) ) : $name;

			return $this->validate_name( $name . ' ' . $id, $id + 1 );
		}

		return $name;
	}

}
