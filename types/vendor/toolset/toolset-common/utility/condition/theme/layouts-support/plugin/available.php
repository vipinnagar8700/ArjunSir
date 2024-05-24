<?php

/**
 * Toolset_Condition_Theme_Layouts_Support_Plugin_Available
 *
 * @since 2.3.0
 */
class Toolset_Condition_Theme_Layouts_Support_Plugin_Available implements Toolset_Condition_Interface {

	private static $is_met_result;

	/**
	 * @return bool
	 */
	public function is_met() {
		if ( self::$is_met_result !== null ) {
			// we have a cached result
			return self::$is_met_result === false ? false : true;
		}

		self::$is_met_result = $this->get_supported_plugin_integration();
		return self::$is_met_result === false ? false : true;
	}

	/**
	 * Returns array of information (see get_supported_themes())
	 *
	 * @return array|false
	 */
	public function get_supported_plugin_integration() {
		if ( self::$is_met_result !== null ) {
			// we have a cached result
			return self::$is_met_result;
		}

		$current_theme = wp_get_theme( get_template() );

		if ( ! is_object( $current_theme )
		     || ! method_exists( $current_theme, 'get' )
		     || $current_theme->get( 'Name' ) === false ) {
			return false;
		}

		$theme_name = $current_theme->get( 'Name' );

		$supported_themes = $this->get_supported_themes();

		if ( ! array_key_exists( $theme_name, $supported_themes ) ) {
			return false;
		}

		return $supported_themes[ $theme_name ];
	}

	/**
	 * Returns a list of supported themes
	 */
	public function get_supported_themes() {
		return array(
			'Avada' => array(
				'theme_name'  => 'Avada',
				'plugin_name' => 'Toolset Avada Integration',
				'doc_link'    => 'https://toolset.com/course-lesson/using-toolset-with-avada/' .
				                 '?utm_source=plugin' .
				                 '&utm_campaign=toolset' .
				                 '&utm_medium=gui' .
				                 '&utm_term=how-to-design-Avada-sites' .
				                 '&utm_content=Avada'
			),

			'Cornerstone, for WordPress' => array(
				'theme_name'  => 'Cornerstone',
				'plugin_name' => 'Toolset Cornerstone Integration',
				'doc_link'    => 'https://toolset.com/'
			),

			'Divi' => array(
				'theme_name'  => 'Divi',
				'plugin_name' => 'Toolset Divi Integration',
				'doc_link'    => 'https://toolset.com/course-lesson/using-toolset-with-divi/' .
				                 '?utm_source=plugin' .
				                 '&utm_campaign=toolset' .
				                 '&utm_medium=gui' .
				                 '&utm_term=how-to-design-Divi-sites' .
				                 '&utm_content=Divi'
			),

			'Genesis' => array(
				'theme_name'  => 'Genesis',
				'plugin_name' => 'Toolset Genesis Integration',
				'doc_link'    => 'https://toolset.com/'
			),

			'Customizr' => array(
				'theme_name'  => 'Customizr',
				'plugin_name' => 'Toolset Customizr Integration',
				'doc_link'    => 'https://toolset.com/'
			),

			'Twenty Sixteen' => array(
				'theme_name'  => 'Twenty Sixteen',
				'plugin_name' => 'Toolset Twenty Sixteen Integration',
				'doc_link'    => 'https://toolset.com/'
			),

			'Twenty Fifteen' => array(
				'theme_name'  => 'Twenty Fifteen',
				'plugin_name' => 'Toolset Twenty Fifteen Integration',
				'doc_link'    => 'https://toolset.com/'
			),

			'Twenty Seventeen' => array(
				'theme_name'  => 'Twenty Seventeen',
				'plugin_name' => 'Toolset Twenty Seventeen Integration',
				'doc_link'    => 'https://toolset.com/'
			),
		);
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_supported_theme_by_name( $name ) {
		foreach( $this->get_supported_themes() as $theme ) {
			if( $theme['theme_name'] == $name ) {
				return $theme;
			}
		}
	}
}

