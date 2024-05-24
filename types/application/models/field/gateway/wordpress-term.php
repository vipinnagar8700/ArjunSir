<?php

/**
 * @since 2.3
 */
class Types_Field_Gateway_Wordpress_Term extends Types_Field_Gateway_Abstract {

	/**
	 * Returns all defined fields
	 *
	 * @return array
	 */
	public function get_fields() {
		return get_option( 'wpcf-termmeta', array() );
	}


	/**
	 * @param int $id
	 * @param string $field_slug
	 * @param bool $repeatable
	 * @param bool $third_party_field If it is conrtolled by Types.
	 *
	 * @return array|void
	 */
	public function get_field_user_value( $id, $field_slug, $repeatable = false, $third_party_field = false ) {
		$types_prefix = defined( 'WPCF_META_PREFIX' ) ? WPCF_META_PREFIX : 'wpcf-';

		$prefix = ! $third_party_field ? $types_prefix : '';
		$user_value = get_term_meta( $id, $prefix . $field_slug );

		if ( $repeatable ) {
			return $user_value;
		}

		if ( is_array( $user_value ) ) {
			return array_unique( $user_value, SORT_REGULAR );
		}

		return $user_value;
	}
}
