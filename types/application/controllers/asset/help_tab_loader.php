<?php

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Error\LoaderError;

/**
 * Manages adding help tabs to admin pages.
 *
 * Usage: Set array( Types_Asset_Help_Tab_Loader::get_instance, 'add_help_tab' ) to 'contextual_help_hook' in
 * the shared Toolset menu item configuration and then extend the get_help_config() method to return a valid
 * help tab configuration for the needed page name.
 *
 * @since 2.0
 */
final class Types_Asset_Help_Tab_Loader {

	private static $instance;

	/** @var Environment|null */
	private $twig;


	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private function __construct() {
	}


	private function __clone() {
	}


	/**
	 * Add help tabs to current screen.
	 *
	 * Used as a hook for 'contextual_help_hook' in the shared Toolset menu.
	 *
	 * @since 2.0
	 */
	public function add_help_tab() {

		$screen = get_current_screen();

		if ( is_null( $screen ) ) {
			return;
		}

		$current_page = toolset_getget( 'page', null );
		if ( null === $current_page ) {
			return;
		}
		$current_page = sanitize_text_field( $current_page );

		$help_content = $this->get_help_content( $current_page );
		if ( null === $help_content ) {
			return;
		}

		$args = array(
			'title' => toolset_getarr( $help_content, 'title' ),
			'id' => 'wpcf',
			'content' => toolset_getarr( $help_content, 'content' ),
			'callback' => false,
		);

		$screen->add_help_tab( $args );

		$this->add_need_help_tab();

	}


	/**
	 * Add multiple help tabs to current screen.
	 *
	 * Used as a hook for 'contextual_help_hook' in the shared Toolset menu.
	 *
	 * @since 2.3
	 */
	public function add_multiple_help_tabs() {

		$screen = get_current_screen();

		if ( is_null( $screen ) ) {
			return;
		}

		$current_page = toolset_getget( 'page', null );
		if ( null === $current_page ) {
			return;
		}
		$current_page = sanitize_text_field( $current_page );

		$help_contents = $this->get_multiple_help_contents( $current_page );
		if ( null === $help_contents ) {
			return;
		}

		foreach ( $help_contents as $help_content ) {
			$args = array(
				'title' => toolset_getarr( $help_content, 'title' ),
				'id' => toolset_getarr( $help_content, 'id' ),
				'content' => toolset_getarr( $help_content, 'content' ),
				'callback' => false,
			);

			$screen->add_help_tab( $args );
		}

		$this->add_need_help_tab();

	}


	/**
	 * Need Help section for a bit advertising.
	 *
	 * @since 2.0
	 */
	private function add_need_help_tab() {

		$args = array(
			'title' => __( 'Need More Help?', 'wpcf' ),
			'id' => 'custom_fields_group-need-help',
			'content' => wpcf_admin_help( 'need-more-help' ),
			'callback' => false,
		);

		$screen = get_current_screen();
		$screen->add_help_tab( $args );

	}


	/**
	 * Generate the configuration for help tab.
	 *
	 * The configuration needs to contain three keys:
	 * - title: Title of the tab.
	 * - template: Name of the Twig template (assuming the 'help' namespace is available)
	 * - context: Context object for Twig.
	 *
	 * @param string $page_name Name of current page.
	 *
	 * @return array|null Help tab configuration array or null when no help tab should be displayed.
	 * @since 2.0
	 */
	private function get_help_config( $page_name ) {

		switch ( $page_name ) {
			case Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL:
				$field_control_page_controller = Types_Page_Field_Control::get_instance();

				return $field_control_page_controller->get_help_config();

			case Types_Admin_Menu::PAGE_NAME_CUSTOM_FIELDS:
				$custom_fileds_page_controller = Types_Page_Custom_Fields::get_existing_instance();

				return $custom_fileds_page_controller->get_help_config();

			default:
				return null;
		}
	}


	/**
	 * Render help tab content from its configuration.
	 *
	 * @param string $page_name Name of current page.
	 *
	 * @return array|null Null when no help tab should be displayed, or an array with keys 'title' and 'content'.
	 * @since 2.0
	 */
	private function get_help_content( $page_name ) {

		$config = $this->get_help_config( $page_name );
		if ( null === $config ) {
			return null;
		}

		try {
			$twig = $this->get_twig();
		} catch ( LoaderError $e ) {
			return null;
		}

		try {
			return array(
				'title' => toolset_getarr( $config, 'title' ),
				'content' => $twig->render( toolset_getarr( $config, 'template' ), toolset_getarr( $config, 'context' ) ),
			);
		} catch ( \OTGS\Toolset\Twig\Error\Error $e ) {
			return null;
		}
	}


	/**
	 * Render multiple help tabs content from its configuration.
	 *
	 * Some pages, like Custom Fields, needs several tabs, one for section.
	 *
	 * @param string $page_name Name of current page.
	 *
	 * @return array|null Null when no help tab should be displayed, or an multiple arrays with keys 'title' and
	 *     'content'.
	 * @since 2.3
	 */
	private function get_multiple_help_contents( $page_name ) {
		try {
			$configs = array();
			$twig = $this->get_twig();

			foreach ( $this->get_help_config( $page_name ) as $config ) {
				if ( null === $config ) {
					return null;
				}

				$configs[] = array(
					'id' => toolset_getarr( $config, 'id' ),
					'title' => toolset_getarr( $config, 'title' ),
					'content' => $twig->render( toolset_getarr( $config, 'template' ), toolset_getarr( $config, 'context' ) ),
				);
			}

			return $configs;
		} catch ( \OTGS\Toolset\Twig\Error\Error $e ) {
			return null;
		}
	}


	/**
	 * @return Environment Initialized Twig environment object for help tab content rendering.
	 * @throws LoaderError
	 * @since 2.0
	 */
	private function get_twig() {
		if ( null === $this->twig ) {
			$tcb = Toolset_Common_Bootstrap::get_instance();
			$tcb->register_gui_base();
			Toolset_Gui_Base::initialize();
			$gui_base = Toolset_Gui_Base::get_instance();

			$this->twig = $gui_base->create_twig_environment(
				array(
					'help' => TYPES_ABSPATH . '/application/views/help',
				)
			);
		}

		return $this->twig;
	}

}
