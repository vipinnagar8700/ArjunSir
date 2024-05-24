<?php

/**
 * Renders a dialog box using a controller output
 *
 * @since m2m
 */
class Types_Controller_Dialog_Box extends Toolset_DialogBoxes {

	/**
	 * Dialog ID
	 *
	 * @var string
	 * @since m2m
	 */
	private $dialog_id;


	/**
	 * Controller object
	 *
	 * @var object
	 * @since m2m
	 */
	private $controller;


	/**
	 * Renderer method
	 *
	 * @var string
	 * @since m2m
	 */
	private $method;


	/**
	 * Renderer method arguments
	 *
	 * @var array
	 * @since m2m
	 */
	private $arguments;


	/**
	 * Constructor
	 *
	 * @param string $dialog_id Dialog ID.
	 * @param Object $controller The object that renders the dialog output.
	 * @param string $method The object's method that renders the output.
	 * @since m2m
	 */
	public function __construct( $dialog_id, $controller, $method, $arguments = array() ) {
		$this->dialog_id = $dialog_id;

		$current_screen = get_current_screen();
		parent::__construct( array( $current_screen->id ) );

		$this->controller = $controller;
		$this->method = $method;
		$this->arguments = $arguments;

		$this->init_screen_render();
	}


	/**
	 * Renders the controller output
	 *
	 * @since m2m
	 */
	public function template() {
		$output = '';

		if ( method_exists( $this->controller, $this->method ) ) {
			$output = call_user_func_array( array( $this->controller, $this->method ), $this->arguments );
		}
		printf(
			'<script type="text/html" id="%s">%s</script>',
			esc_attr( $this->dialog_id ),
			$output
		);
	}

}
