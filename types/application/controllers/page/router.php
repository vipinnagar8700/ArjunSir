<?php

/**
 * Routes from a callback to a specific method on a specific page controller.
 *
 * First time a controller is used, it will be instantiated. This way, it is possible to load only
 * the specific page controller when it is needed.
 *
 * @since m2m
 */
class Types_Page_Router {


	private $routes = array();


	private $page_controllers = array();


	/**
	 * Add a new route.
	 *
	 * @param string $callback_name Name of the callback method that will be called on this object.
	 * @param string $class_to_instantiate Page controller class which needs to inherit from Types_Page_Persistent
	 * @param string $method_to_call Name of the method to call on the page controller.
	 * @param array $controller_args Parameter for the page controller constructor
	 * @param string $factory Name of the factory class to instantiate the correct page controller.
	 */
	public function add_route( $callback_name, $class_to_instantiate, $method_to_call, $controller_args, $factory ) {
		$this->routes[ $callback_name ] = array(
			'class' => $class_to_instantiate,
			'method' => $method_to_call,
			'args' => $controller_args,
			'factory' => $factory,
		);
	}


	private function has_route( $callback_name ) {
		return array_key_exists( $callback_name, $this->routes );
	}


	private function get_route( $callback_name ) {
		return toolset_getarr( $this->routes, $callback_name, null );
	}


	/**
	 * Redirect callbacks to a proper page controller.
	 *
	 * We use callbacks on this object in order to avoid loading all page controllers when building the admin menu.
	 * The one right page controller is instantiated only when it is actually needed.
	 *
	 * @param string $callback_name Method name.
	 * @param array $parameters Method parameters (ignored).
	 *
	 * @throws RuntimeException if a wrong call is made.
	 * @since m2m
	 */
	public function __call( $callback_name, $parameters ) {

		if ( ! $this->has_route( $callback_name ) ) {
			throw new RuntimeException();
		}

		$route = $this->get_route( $callback_name );

		$page_controller = $this->get_page_controller_instance( $route );

		if ( ! $page_controller instanceof Types_Page_Persistent ) {
			throw new RuntimeException();
		}

		$method_to_call = $route['method'];

		if ( ! is_callable( array( $page_controller, $method_to_call ) ) ) {
			throw new RuntimeException();
		}

		$page_controller->$method_to_call();
	}


	/**
	 * Get an instance of a persistent page controller. Create one if it doesn't exist yet.
	 *
	 * @param array $route
	 *
	 * @return null|Types_Page_Persistent Page controller or null if the page isn't defined.
	 * @since m2m
	 */
	private function get_page_controller_instance( $route ) {
		$class_name = $route['class'];

		if ( ! array_key_exists( $class_name, $this->page_controllers ) ) {
			/** @var Types_Page_Factory_Interface $controller_factory */
			$controller_factory = new $route['factory']();
			$this->page_controllers[ $class_name ] = $controller_factory->get_page_controller( $class_name, $route['args'] );
		}

		return $this->page_controllers[ $class_name ];
	}

}
