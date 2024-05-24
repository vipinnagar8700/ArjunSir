<?php

/**
 * Controller of a persistent page (one that shows in the admin menu at all times).
 *
 * Its specialty is only that it accepts attributes that have to be already known by Types_Admin_Menu before the
 * specific page is loaded.
 *
 * @since m2m
 */
abstract class Types_Page_Persistent extends Types_Page_Abstract {


	private $title;


	/**
	 * Page title
	 *
	 * Sometimes the page title must to be different than the Menu title
	 *
	 * @since 2.3
	 * @var string
	 */
	private $page_title;
	private $page_name;
	private $required_capability;


    /**
     * Types_Page_Persistent constructor.
     * @param array $args {
     *     Page information that needed to be determined before instantiating the controller.
     *
     *     @type string $title Page title (and heading).
     *     @type string $page_name Slug of the page.
     *     @type string $required_capability User capability needed to display this page.
     * }
     */
	public function __construct( $args ) {
		$this->title = sanitize_text_field( toolset_getarr( $args, 'title' ) );
		// Sometimes page title in menu is different than in heading
		$this->page_title = sanitize_text_field( toolset_getarr( $args, 'page_title' ) );
		if ( ! $this->page_title ) $this->page_title = $this->title;
		$this->page_name = sanitize_text_field( toolset_getarr( $args, 'page_name' ) );
		$this->required_capability = sanitize_text_field( toolset_getarr( $args, 'required_capability' ) );
		// There are inconsistencies between Types_Page_Persistent and Types_Page_Abstract.
		if ( empty ( $this->title ) ) {
			$this->title = sanitize_text_field( toolset_getarr( $args, 'page_title' ) );
		}
		if ( empty ( $this->page_name ) ) {
			$this->page_name = sanitize_text_field( toolset_getarr( $args, 'slug' ) );
		}
		if ( empty ( $this->required_capability ) ) {
			$this->required_capability = sanitize_text_field( toolset_getarr( $args, 'capability' ) );
		}

		if(
			! is_string( $this->title ) || empty( $this->title )
			|| ! is_string( $this->page_name ) || empty( $this->page_name )
			|| ! is_string( $this->required_capability ) || empty( $this->required_capability )
		) {
			// fixme this needs to go back before merging
			// throw new InvalidArgumentException();
		}
	}


	/**
	 * Title to be displayed on the menu as well in the page title.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Title to be displayed on the menu as well in the page title.
	 *
	 * @return string
	 * @since 2.3
	 */
	public function get_page_title() {
		return $this->page_title;
	}


	/**
	 * Callback for the page rendering action.
	 *
	 * @return callable
	 * @since 2.0
	 */
	public function get_render_callback() {
		return array( $this, 'render_page' );
	}

	/**
	 * Page name slug.
	 *
	 * Should be taken directly from constants in Types_Admin_Menu.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_page_name() {
		return $this->page_name;
	}


	/**
	 * User capability required to display the submenu item and access the page.
	 *
	 * @return string
	 * @since 2.0
	 */
	public function get_required_capability() {
		return $this->required_capability;
	}


	public abstract function render_page();

}
