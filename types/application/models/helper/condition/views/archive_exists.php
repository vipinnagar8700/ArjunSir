<?php

/**
 * Types_Helper_Condition_Views_Archive_Exists
 *
 * @since 2.0
 * @refactoring Get rid of the hard Views dependency.
 */
class Types_Helper_Condition_Views_Archive_Exists extends Types_Helper_Condition_Views_Views_Exist {

	private static $template_id = array();

	private static $template_name = array();


	public function valid() {
		// if views not active
		if ( ! defined( 'WPV_VERSION' )
			|| ! function_exists( 'wpv_has_wordpress_archive' ) ) {
			return false;
		}

		// get current type name
		$type = self::get_type_name();

		// check stored validation
		if (
			isset( self::$template_id[ $type ] )
			&& self::$template_id[ $type ] !== false
		) {
			return true;
		}

		$archive = $type === 'post'
			? wpv_has_wordpress_archive()
			: wpv_has_wordpress_archive( 'post', $type );

		self::$template_id[ $type ] = $archive && $archive !== 0
			? $archive
			: false;

		return self::$template_id[ $type ];
	}


	public static function get_template_id() {
		$type = self::get_type_name();

		if ( ! isset( self::$template_id[ $type ] ) ) {
			$self = new Types_Helper_Condition_Views_Archive_Exists();
			$self->valid();
		}

		return self::$template_id[ $type ];
	}


	public static function get_template_name() {
		$type = self::get_type_name();

		if ( ! isset( self::$template_name[ $type ] ) ) {
			self::$template_name[ $type ] = get_the_title( self::get_template_id() );
		}

		return self::$template_name[ $type ];
	}


	/**
	 * Renders the conditional toolset links if present.
	 *
	 * @return string
	 */
	public static function get_terms_archives_list() {

		$views_assignments = apply_filters( 'wpv_get_archives_and_templates_assignments', [] );
		$archive_with_terms = [];
		$taxonomies = self::get_post_type_taxonomies();
		foreach ( $taxonomies as $taxonomy_name ) {
			$archive_with_terms = array_merge(
				$archive_with_terms,
				static::get_terms_archives_for_taxonomy( $taxonomy_name, $views_assignments )
			);
		}

		return self::get_twig()
			->render( '/page/dashboard/table/cell/archive-terms-cell.twig', [ 'archives' => $archive_with_terms ] );
	}


	/**
	 * @param string $taxonomy_name
	 * @param array $views_assignments
	 *
	 * @return array
	 */
	public static function get_terms_archives_for_taxonomy( $taxonomy_name, $views_assignments ) {
		$archive_with_terms = array();
		foreach ( $views_assignments as $views_assignment ) {
			if (
				! isset( $views_assignment['loop_type'] )
				||
				! isset( $views_assignment['slug'] )
				||
				! isset( $views_assignment['wpa_options'] )
				|| empty( $views_assignment['wpa_options'] )
			) {
				continue;
			}
			if ( 'taxonomy' === $views_assignment['loop_type'] && $taxonomy_name === $views_assignment['slug'] ) {
				$archive_assigned_terms = $views_assignment['wpa_options'];

				foreach ( $archive_assigned_terms as $wpa_id => $archive_assigned_term ) {
					$archive_with_terms[] = array(
						'title' => get_the_title( $wpa_id ),
						'link' => apply_filters(
							'wpv_filter_wpa_edit_link',
							sprintf( '%s%s%s', admin_url(), 'post.php?action=edit&post=', $wpa_id ),
							$wpa_id
						),
					);
				}
			}
		}

		return $archive_with_terms;
	}
}
