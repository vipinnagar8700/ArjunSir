<?php

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Error\LoaderError;

/**
 * Abstract controller for WP meta boxes handling
 *
 * @since m2m
 */
abstract class Types_Page_Extension_Meta_Box {


	/**
	 * Meta box ID
	 *
	 * @var string
	 * @since m2m
	 */
	const ID = '';


	/**
	 * Twig class
	 *
	 * @var Environment Twig Enviroment.
	 * @since m2m
	 */
	private $twig;


	/**
	 * Twig teplate path
	 *
	 * @var array
	 * @since m2m
	 */
	protected $twig_template_paths;


	/** @var int|false */
	private $current_post_id;


	/**
	 * Constructor
	 *
	 * @param Environment $twig
	 */
	protected function __construct( Environment $twig = null ) {
		$this->twig = $twig;
	}


	/**
	 * Prepares the class
	 *
	 * @since m2m
	 */
	abstract public function prepare();


	/**
	 * Builds the Twig context
	 *
	 * @param mixed $data Depending of the controller.
	 *
	 * @since m2m
	 */
	abstract protected function build_metabox_context( $data );


	/**
	 * Builds the Twig context
	 *
	 * @param mixed $data Depending of the controller.
	 *
	 * @since m2m
	 */
	abstract protected function get_main_twig_template( $data );


	/**
	 * Returns the current post id or false if none exists
	 *
	 * @return int|false
	 * @since 3.2
	 */
	protected function get_current_post_id() {
		if ( $this->current_post_id !== null ) {
			return $this->current_post_id;
		}

		$current_post_id = toolset_getget( 'post' );

		if ( ! $current_post_id ) {
			// new post, but the post id is already reserved by WP and stored in global $post_ID
			global $post_ID;
			$current_post_id = $post_ID;

			if ( ! $current_post_id ) {
				// no reservered post id found, leave hint that relationships can only be added to a saved post
				return $this->current_post_id = false;
			}
		}

		return $this->current_post_id = (int) $current_post_id;
	}


	/**
	 * Adds several meta boxex to a page
	 *
	 * @param array[]|mixed $metabox_data Array of ArrayAccess
	 *  [id]         => Metabox ID.
	 *  [title]      => Metabox title.
	 *  [arguments]  => Callback arguments.
	 *
	 * @throws InvalidArgumentException If it is not an array of arrays.
	 * @since m2m
	 */
	protected function add_meta_boxes( $metabox_data ) {
		// Normalizes array, must be an array of arrays.
		if ( ! is_array( $metabox_data ) && ! is_array( $metabox_data[0] ) ) {
			throw new InvalidArgumentException( 'Invalid list of meta boxes' );
		}
		// The controller may show different meta boxes.
		foreach ( $metabox_data as $data ) {
			$arguments = isset( $data['arguments'] ) ? $data['arguments'] : null;
			add_meta_box( 'types-related-content-' . $data['id'], $data['title'], array(
				$this,
				'render_meta_box',
			), null, 'advanced', 'default', $arguments );
		}
	}


	/**
	 * Returns GUI Base
	 *
	 * @return Toolset_Gui_Base
	 * @since m2m
	 */
	protected function get_gui_base() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
		$toolset_common_bootstrap->register_gui_base();
		Toolset_Gui_Base::initialize();

		return Toolset_Gui_Base::get_instance();
	}


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Environment
	 * @throws LoaderError
	 * @since m2m
	 */
	protected function get_twig() {
		if ( null === $this->twig ) {

			$gui_base = $this->get_gui_base();

			$this->twig = $gui_base->create_twig_environment( $this->twig_template_paths );
		}

		return $this->twig;
	}


	/**
	 * Renders the metabox.
	 *
	 * Gets the page context: strings, items... and echoes the result.
	 *
	 * @param WP_Post $post Current post.
	 * @param mixed $data Depending of the controller.
	 *
	 * @since m2m
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function render_meta_box( $post, $data ) {
		// If there is not post, there is not context data.
		$context = ! $this->get_current_post_id()
			? array()
			: $this->build_metabox_context( $data );

		try {
			$twig = $this->get_twig();
			echo $twig->render( '@' . static::ID . '/' . $this->get_main_twig_template( $data ), $context );
		} catch ( \OTGS\Toolset\Twig\Error\Error $e ) {
			echo 'Error during rendering the page. Please contact the Toolset user support.';
		}
	}
}
