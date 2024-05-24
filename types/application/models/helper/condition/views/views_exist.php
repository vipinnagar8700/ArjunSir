<?php

/**
 * @since 2.0
 */
class Types_Helper_Condition_Views_Views_Exist extends Types_Helper_Condition_Views_Active {

	public static $views_per_post_type;


	public function valid() {
		// false if views not active
		if ( ! parent::valid() ) {
			return false;
		}

		global $wpdb;

		$cpt = Types_Helper_Condition::get_post_type();

		if ( isset( self::$views_per_post_type[ $cpt->name ] ) ) {
			return true;
		}

		// @todo use wpv_get_available_views API filter and optimize the performance here
		// $available_views = toolset_ensarr( apply_filters( 'wpv_get_available_views', [] ) );
		$views_settings = $wpdb->get_results(
			"SELECT postmeta.meta_value as meta_value, postmeta.post_id as post_id, post.post_title as title
			FROM $wpdb->postmeta AS postmeta
				JOIN $wpdb->posts AS post ON ( postmeta.post_id = post.ID )
			WHERE postmeta.meta_key = '_wpv_settings'
				AND post.post_status NOT IN ('draft', 'trash')"
		);

		foreach ( $views_settings as $setting ) {
			$setting->meta_value = unserialize( $setting->meta_value );
			if (
				! isset( $setting->meta_value['view-query-mode'] )
				|| $setting->meta_value['view-query-mode'] !== 'normal'
			) {
				// no "View"
				continue;
			}

			if (
				isset( $setting->meta_value['post_type'], $setting->post_id, $setting->title )
				&& in_array( $cpt->name, $setting->meta_value['post_type'], true )
			) {
				self::$views_per_post_type[ $cpt->name ][] = array(
					'id' => $setting->post_id,
					'name' => $setting->title,
				);
			}
		}

		if ( isset( self::$views_per_post_type[ $cpt->name ] ) ) {
			return true;
		}

		return false;
	}


	public static function get_views_of_post_type() {
		$cpt = Types_Helper_Condition::get_post_type();

		if ( isset( self::$views_per_post_type[ $cpt->name ] ) ) {
			return self::$views_per_post_type[ $cpt->name ];
		}

		return false;
	}
}
