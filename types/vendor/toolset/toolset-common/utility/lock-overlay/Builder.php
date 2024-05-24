<?php

namespace OTGS\Toolset\Common\Utility\Lock_Overlay;

use OTGS\Toolset\Common\Utils\RequestMode as RequestMode;

/**
 * Class Builder
 *
 * @package OTGS\Toolset\Common\Utility\Lock_Overlay
 * @since 3.3.5
 * By default appends an overlay element over a container element given a $selector + a button-link to turn it on/off
 *     onclick can be easily overridden to change the text + the overlay content and its behaviour by manipulating
 *     _.template through its arguments
 */
class Builder {

	const JS_HANDLE = 'toolset-lock-overlay';
	const CSS_HANDLE = 'toolset-lock-overlay-css';
	const JS_NAMESPACED_OBJECT = 'ToolsetLockOverlaySettings';

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var string
	 * Override to provide a different _.template
	 */
	protected $template_selector = 'js-toolset-lock-overlay-default-tpl';

	/**
	 * @var string
	 * Override to provide a different message _.template
	 */
	protected $message_template_selector = 'js-toolset-lock-overlay-default-message-tpl';

	/**
	 * @var string
	 * Override this value if you want to run it earlier or later
	 */
	protected $default_action = 'the_post';

	/**
	 * @var string
	 * A name identifier for the Overlay created so that e.g. more than one overlay can be present in a page
	 */
	private $name;

	/**
	 * @var string
	 * the Javascript element selector to append the overlay to
	 */
	private $selector;

	/**
	 * @var \Toolset_Condition_Interface
	 * the condition object to define when the overlay should be used
	 */
	private $condition;

	/**
	 * @var string
	 */
	private $assets_url; // Action added in Gutenberg editor page only: https://github.com/WordPress/gutenberg/issues/1316

	/**
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * @var string
	 */
	private $post_type;

	/**
	 * @var RequestMode
	 */
	private $request_mode;

	/**
	 * Builder constructor.
	 *
	 * @param $name
	 * @param $selector
	 * @param \Toolset_Condition_Interface $toolset_condition
	 * @param RequestMode $request_mode
	 * @param int $post_id
	 * @param array $options
	 */
	public function __construct( $name, $selector, \Toolset_Condition_Interface $toolset_condition, RequestMode $request_mode, $post_id = 0, array $options = array() ) {
		$this->name = $name;
		$this->selector = $selector;
		$this->condition = $toolset_condition;
		$this->request_mode = $request_mode;
		$this->post_id = $post_id;
		$this->post_type = get_post_type( $this->post_id );
		$this->options = $this->set_up_default_options( $options );
		$this->set_assets_url();
	}

	protected function set_up_default_options( $options ) {
		return wp_parse_args( $options, array(
			'overlay_name' => $this->get_name(),
			'post_id' => $this->get_post_id(),
			'post_type' => $this->get_post_type(),
		) );
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * @return false|string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * @return string
	 */
	private function set_assets_url() {
		if ( is_admin() ) {
			$this->assets_url = TOOLSET_COMMON_URL . DIRECTORY_SEPARATOR . 'utility/lock-overlay/res';
		} else {
			$this->assets_url = TOOLSET_COMMON_FRONTEND_URL . DIRECTORY_SEPARATOR . 'utility/lock-overlay/res';
		}

		$this->assets_url = untrailingslashit( $this->assets_url );

		return $this->assets_url;
	}

	/**
	 * @return void
	 * Registers scripts and styles and run them on Gutenberg the_post action, we can't register and check if we are in
	 *     Gutenberg at the same time, so we need to register first, then check if we are in Gutenberg, if we aren't we
	 *     don't enqueue
	 */
	public function add_hooks() {
		if ( ! $this->minimum_requirements() ) {
			return;
		}
		add_filter( 'toolset_add_registered_script', array( $this, 'register_scripts' ), 10, 1 );
		add_filter( 'toolset_add_registered_styles', array( $this, 'register_styles' ), 10, 1 );
		// Action added in Gutenberg editor page only: https://github.com/WordPress/gutenberg/issues/1316
		add_action( $this->default_action, array( $this, 'run_overlay' ) );

	}

	/**
	 * @return bool
	 * By default we want this to work in the admin and in a non-ajax request
	 * override this method to change requirements
	 */
	protected function minimum_requirements() {
		return ( $this->request_mode->get() === RequestMode::ADMIN );
	}

	/**
	 * @return void
	 * Enqueue scripts and styles and append templates only if Gutenberg is available
	 */
	public function run_overlay() {
		if ( ! $this->condition->is_met() ) {
			return;
		}
		add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ), 110 );
		add_action( 'admin_print_scripts', array( $this, 'enqueue_additional_scripts' ), 111 );
		add_action( 'admin_footer', array(
			$this,
			'lock_overlay_template',
		), 999 /* print it outside the footer divs*/ );
	}

	/**
	 * @param $scripts
	 *
	 * @return array
	 */
	public function register_scripts( $scripts ) {

		$scripts[ self::JS_HANDLE ] = new \Toolset_Script( self::JS_HANDLE, $this->get_assets_url() . '/js/lock-overlay.js', array(
			'jquery',
			'underscore',
			'toolset-utils',
			'toolset-event-manager',
		), TOOLSET_COMMON_VERSION, true );

		return array_merge( $scripts, $this->register_additional_scripts( $scripts ) );
	}

	/**
	 * @return string
	 */
	public function get_assets_url() {
		return $this->assets_url;
	}

	/**
	 * @param $scripts
	 *
	 * @return mixed
	 * Override this method to register your own JS controller
	 */
	protected function register_additional_scripts( $scripts ) {

		return $scripts;
	}

	/**
	 * @param $styles
	 *
	 * @return array
	 */
	public function register_styles( $styles ) {

		$styles[ self::CSS_HANDLE ] = new \Toolset_Style( self::CSS_HANDLE, $this->get_assets_url() . '/css/lock-overlay.css', array(), TOOLSET_COMMON_VERSION, 'screen' );

		return array_merge( $styles, $this->register_additional_styles( $styles ) );
	}

	/**
	 * @param $styles
	 *
	 * @return mixed
	 * Override this method to register your own CSS styles
	 */
	protected function register_additional_styles( $styles ) {

		return $styles;
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts() {

		do_action( 'toolset_enqueue_styles', $this->get_styles_to_enqueue() );
		do_action( 'toolset_enqueue_scripts', $this->get_scripts_to_enqueue() );

		do_action( 'toolset_localize_script', self::JS_HANDLE, self::JS_NAMESPACED_OBJECT, $this->get_localised_object() );
	}

	/**
	 * @return mixed
	 */
	public function get_styles_to_enqueue() {
		// use this filter to push additional CSS handles to enqueue
		return apply_filters( 'toolset_lock_overlay_get_styles_to_enqueue', array( self::CSS_HANDLE ) );
	}

	/**
	 * @return mixed
	 */
	protected function get_scripts_to_enqueue() {
		// use this filter to push additional JS handles to enqueue
		return apply_filters( 'toolset_lock_overlay_get_scripts_to_enqueue', array( self::JS_HANDLE ) );
	}

	/**
	 * @return mixed
	 */
	public function get_localised_object() {
		// use this filter to push additional localised variable and make them available in JS
		return apply_filters( 'toolset_lock_overlay_get_localised_object', array(
			'name' => $this->get_name(),
			'selector' => $this->get_selector(),
			'template_selector' => $this->template_selector,
			'message_template_selector' => $this->message_template_selector,
			'post_id' => $this->get_post_id(),
			'post_type' => $this->get_post_type(),
			'strings' => $this->get_localised_strings(),
			'template_object' => $this->get_template_object(),
		) );
	}

	/**
	 * @return string
	 */
	public function get_selector() {
		return $this->selector;
	}

	/**
	 * @return array
	 * Override this method to push i18n localised strings for JS use
	 */
	public function get_localised_strings() {
		return array();
	}

	/**
	 * @return mixed
	 * Override this method this way or use its filter to push variables into _.template
	 * protected function get_template_object(){
	 * return apply_filters( 'toolset_lock_overlay_get_template_object', array_merge( parent::get_template_object(),
	 *     $my_new_object ) );
	 * }
	 */
	public function get_template_object() {
		// use this filter to push additional variables to be available in Underscore template
		return apply_filters( 'toolset_lock_overlay_get_template_object', $this->options );
	}

	/**
	 * @return void
	 * Override this method to enqueue your styles and scripts
	 */
	public function enqueue_additional_scripts() {

	}

	/**
	 * @return void
	 * Override this method to load an alternative template (Underscore is welcome) for Overlay content and dismiss
	 *     link/button
	 */
	public function lock_overlay_template() {
		include TOOLSET_COMMON_PATH . '/utility/lock-overlay/templates/lock-overlay-default-template.tpl.php';
	}

	/**
	 * @return null/WP_Post
	 */
	public function get_post() {
		if ( $this->get_post_id() === 0 ) {
			return null;
		}

		return get_post( $this->get_post_id() );
	}

	/**
	 * @return string
	 */
	function get_default_action() {
		return $this->default_action;
	}
}
