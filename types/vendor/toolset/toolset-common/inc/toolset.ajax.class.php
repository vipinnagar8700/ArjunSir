<?php

/**
 * Main AJAX call controller for Toolset.
 *
 *
 * When DOING_AJAX, you need to run initialize() to register the callbacks, only creating an instance will not be enough.
 *
 *
 * When implementing AJAX actions here, please follow these rules:
 *
 * 1.  All AJAX action names are automatically prefixed with 'wp_ajax_{$plugin_name}_'. Only lowercase characters
 *     and underscores can be used.
 *
 *     $plugin_name is in this case 'toolset' but it may be different in subclasses.
 *
 * 2.  Action names (without a prefix) should be defined as constants, and be part of array returned
 *     by get_callback_names().
 *
 * 3.  For each action, there should be a dedicated class implementing the Toolset_Ajax_Handler_Interface.
 *
 *     Name of the class must be {$capitalized_plugin_name}_Ajax_Handler_{$capitalized_action_name}.
 *
 *     So for example, for a hook to 'wp_ajax_types_field_control_action' you need to create a class
 *     'Types_Ajax_Handler_Field_Control_Action'.
 *
 * 4.  All callbacks must use the ajax_begin() and ajax_finish() methods.
 *
 *
 * When creating subclasses, you only need to do following:
 *
 * - Override get_plugin_slug().
 * - Override get_callback_names().
 * - Override additional_ajax_init() if you need to.
 *
 * @refactoring Alternatively, allow namespace usage for handler classes (also considering subclasses of Toolset_Ajax).
 *
 * @since m2m
 */
class Toolset_Ajax {


	/** Prefix for the callback method name */
	const CALLBACK_PREFIX = 'callback_';


	const DELIMITER = '_';


	const CALLBACK_MIGRATE_TO_M2M = 'migrate_to_m2m';
	const CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE = 'select2_suggest_posts_by_title';
	const CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE = 'select2_suggest_posts_by_post_type';
	const CALLBACK_SELECT2_SUGGEST_TERMS = 'select2_suggest_terms';
	const CALLBACK_SELECT2_SUGGEST_USERS = 'select2_suggest_users';
	const CALLBACK_GET_POST_BY_ID = 'get_post_by_id';
	const CALLBACK_GET_TERM_BY_ID = 'get_term_by_id';
	const CALLBACK_GET_USER_BY_ID = 'get_user_by_id';
	const CALLBACK_GET_VIEW_BLOCK_PREVIEW = 'get_view_block_preview';
	const CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW = 'get_content_template_block_preview';
	const CALLBACK_INTERMEDIARY_POST_CLEANUP = 'intermediary_post_cleanup';
	const CALLBACK_CODE_SNIPPETS_ACTION = 'code_snippets_action';
	const CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS = 'get_view_custom_search_status';
	const CALLBACK_CLEAR_POST_GUID_ID_CACHE = 'clear_post_guid_id_cache';
	const CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES = 'fix_orphan_intermediary_post_types';
	const CALLBACK_CLEANUP_AFTER_RELATIONSHIP_MIGRATION = 'cleanup_after_relationship_migration';
	const CALLBACK_ROLLBACK_AFTER_RELATIONSHIP_MIGRATION = 'rollback_after_relationship_migration';


	/** @var Toolset_Ajax */
	private static $instance;


	/**
	 * @return Toolset_Ajax|false
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		$called_class = get_called_class();
		/** @var Toolset_Ajax $instance */
		$instance = call_user_func( array( $called_class, 'get_instance' ) );

		$instance->register_callbacks();
		$instance->additional_ajax_init();

		return $instance;
	}

	protected function get_callback_names() {
		return array(
			self::CALLBACK_MIGRATE_TO_M2M,
			self::CALLBACK_INTERMEDIARY_POST_CLEANUP,
			self::CALLBACK_GET_VIEW_BLOCK_PREVIEW,
			self::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW,
			self::CALLBACK_CODE_SNIPPETS_ACTION,
			self::CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS,
			self::CALLBACK_CLEAR_POST_GUID_ID_CACHE,
			self::CALLBACK_CLEANUP_AFTER_RELATIONSHIP_MIGRATION,
			self::CALLBACK_ROLLBACK_AFTER_RELATIONSHIP_MIGRATION,
			self::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES,
		);
	}


	protected function get_public_callback_names() {
		return array(
			self::CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE,
			self::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE,
			self::CALLBACK_SELECT2_SUGGEST_TERMS,
			self::CALLBACK_SELECT2_SUGGEST_USERS,
			self::CALLBACK_GET_POST_BY_ID,
			self::CALLBACK_GET_TERM_BY_ID,
			self::CALLBACK_GET_USER_BY_ID,
			self::CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS,
		);
	}


	protected $callbacks_registered = false;


	/**
	 * Register privileged callbacks.
	 *
	 * Each callback is registered as a "{$plugin_slug}_{$callback}" action and needs to have a "callback_{$callback_name}"
	 * method in this class.
	 *
	 * Valid for AJAX callbacks executed by logged in users.
	 *
	 * @since m2m
	 *
	 * @param string[] $callback_names
	 */
	private function register_priv_callbacks( $callback_names ) {
		foreach ( $callback_names as $callback_name ) {
			$action_name = 'wp_ajax_' . $this->get_plugin_slug() . self::DELIMITER . $callback_name;
			add_action(
				$action_name,
				array( $this, self::CALLBACK_PREFIX . $callback_name )
			);
		}
	}


	/**
	 * Register unprivileged callbacks.
	 *
	 * Each callback is registered as a "{$plugin_slug}_{$callback}" action and needs to have a "callback_{$callback_name}"
	 * method in this class.
	 *
	 * Valid for AJAX callbacks executed by not logged in users.
	 *
	 * @since m2m
	 *
	 * @param string[] $callback_names
	 */
	private function register_nopriv_callbacks( $callback_names ) {
		foreach ( $callback_names as $callback_name ) {
			$action_name = 'wp_ajax_nopriv_' . $this->get_plugin_slug() . self::DELIMITER . $callback_name;
			add_action(
				$action_name,
				array( $this, self::CALLBACK_PREFIX . $callback_name )
			);
		}
	}


	/**
	 * Register all callbacks.
	 *
	 * Each callback is registered as a "{$plugin_slug}_{$callback}" action and needs to have a "callback_{$callback_name}"
	 * method in this class.
	 *
	 * @since 2.0
	 */
	private function register_callbacks() {

		if ( $this->callbacks_registered ) {
			return;
		}

		$callback_names = $this->get_callback_names();
		$this->register_priv_callbacks( $callback_names );

		$public_callback_names = $this->get_public_callback_names();
		$this->register_priv_callbacks( $public_callback_names );
		$this->register_nopriv_callbacks( $public_callback_names );

		$this->callbacks_registered = true;

	}


	protected function get_plugin_slug( $capitalized = false ) {
		return ( $capitalized ? 'Toolset' : 'toolset' );
	}


	protected function get_handler_class_prefix() {
		return $this->get_plugin_slug( true ) . '_Ajax_Handler_';
	}


	public function get_action_js_name( $action ) {
		return $this->get_plugin_slug( false ) . self::DELIMITER . $action;
	}


	/**
	 * Handle a call to undefined method on this class, hopefully an AJAX call.
	 *
	 * @param string $name Method name.
	 * @param array $parameters Method parameters.
	 *
	 * @since 2.1
	 */
	public function __call( $name, $parameters ) {
		// Check for the callback prefix in the method name
		$name_parts = explode( self::DELIMITER, $name );
		if ( 0 !== strcmp( $name_parts[0] . self::DELIMITER, self::CALLBACK_PREFIX ) ) {
			// Not a callback, resign.
			return;
		}

		// Deduct the handler class name from the callback name
		unset( $name_parts[0] );
		$class_name = $this->name_parts_to_callback_name( $name_parts );

		if( ! class_exists( $class_name ) ) {
			// Let's not even try.
			return;
		}

		// Obtain an instance of the handler class.
		try {
			/** @var \OTGS\Toolset\Common\Auryn\Injector $dic */
			$dic = apply_filters( 'toolset_dic', false );
			// This is pretty important because $this may be a subclass of Toolset_Ajax, not Toolset_Ajax itself.
			$dic->share( $this );
			// If the handler doesn't override the constructor, it will have "Toolset_Ajax $ajax_manager" as the
			// first parameter. But when the handler belongs to a plugin, we want to inject the plugin's AJAX manager
			// and not the one from Toolset Common.
			$handler = $dic->make( $class_name, array( ':ajax_manager' => $this ) );
		} catch ( \OTGS\Toolset\Common\Auryn\InjectionException $injection_exception ) {
			// For some reason, we're unable to instantiate the class with DIC. Use the old way, assuming
			// the handler constructor is handling everything on its own.
			//
			// This happens mostly when the constructor contains other parameters than just the $ajax_manager, and
			// these parameters have typehints or missing default values, which the DIC is unable to solve.
			$handler = new $class_name( $this );
		} catch ( Exception $e ) {
			// The handler class could not have been instantiated, resign.
			return;
		}

		// Success
		$handler->process_call( $parameters );
	}


	/**
	 * Turn the parts of the action name into a handler class name.
	 *
	 * @param string[] $name_parts Parts of the action name without any prefixes.
	 *
	 * @return string Fully qualified class name of the AJAX call handler. Must be case-sensitive because of our
	 *     classmap-based autoloader.
	 *
	 * @since 4.0 extracted from __call() because we need to override this in Types.
	 */
	protected function name_parts_to_callback_name( $name_parts ) {
		$class_name = implode( self::DELIMITER, $name_parts );
		$class_name = strtolower( $class_name );
		$class_name = Toolset_Utils::resolve_callback_class_name( $class_name );
		$class_name = $this->get_handler_class_prefix() . $class_name;
		return $class_name;
	}


	/**
	 * Perform basic authentication check.
	 *
	 * Check user capability and nonce. Dies with an error message (wp_json_error() by default) if the authentization
	 * is not successful.
	 *
	 * @param array $args Arguments (
	 *
	 * @type string $nonce Name of the nonce that should be verified. Mandatory
	 * @type string $nonce_parameter Name of the parameter containing nonce value.
	 *         Optional, defaults to "wpnonce".
	 * @type string $parameter_source Determines where the function should look for the nonce parameter.
	 *         Allowed values are 'get' and 'post'. Optional, defaults to 'post'.
	 * @type string $capability_needed Capability that user has to have in order to pass the check.
	 *         Optional, default is "manage_options".
	 * @type bool $is_public Whether the action is publicly available without capability checks.
	 *         Optional, default is FALSE.
	 * @type string $type_of_death How to indicate failure:
	 *         - 'die': Call wp_json_error with array( 'type' => 'capability'|'nonce', 'message' => $error_message )
	 *         - 'return': Do not die, just return the error array as above.
	 *         Optional, default is 'die'.
	 *     )
	 *
	 * @return mixed
	 *
	 * @since 2.0
	 */
	private function ajax_authenticate( $args = array() ) {
		// Read arguments
		$type_of_death = toolset_getarr( $args, 'type_of_death', 'die', array( 'die', 'return' ) );
		$nonce_name = toolset_getarr( $args, 'nonce' );
		$nonce_parameter = toolset_getarr( $args, 'nonce_parameter', 'wpnonce' );
		$capability_needed = toolset_getarr( $args, 'capability_needed', 'manage_options' );
		$is_public = toolset_getarr( $args, 'is_public', false );
		$parameter_source_name = toolset_getarr( $args, 'parameter_source', 'post', array( 'get', 'post' ) );
		$parameter_source = ( $parameter_source_name == 'get' ) ? $_GET : $_POST;

		$is_error = false;
		$error_message = null;
		$error_type = null;

		// Check permissions
		if ( ! $is_public && ! current_user_can( $capability_needed ) ) {
			$error_message = __( 'You do not have permissions for that.', 'wpv-views' );
			$error_type = 'capability';
			$is_error = true;
		}

		// Check nonce
		if ( ! $is_error && ! wp_verify_nonce( toolset_getarr( $parameter_source, $nonce_parameter, '' ), $nonce_name ) ) {
			$error_message = __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' );
			$error_type = 'nonce';
			$is_error = true;
		}

		if ( $is_error ) {
			$error_description = array( 'type' => $error_type, 'message' => $error_message );
			switch ( $type_of_death ) {

				case 'die':
					wp_send_json_error( $error_description );
					break;

				case 'return':
				default:
					return $error_description;
			}
		}

		return true;
	}


	/**
	 * Begin an AJAX call handling.
	 *
	 * To be extended in the future.
	 *
	 * @param array $args See ajax_authenticate for details
	 *
	 * @return mixed
	 * @since 2.0
	 */
	public function ajax_begin( $args ) {
		return $this->ajax_authenticate( $args );
	}


	/**
	 * Complete an AJAX call handling.
	 *
	 * Sends a success/error response in a standard way.
	 *
	 * To be extended in the future.
	 *
	 * @param array|mixed $response Custom response data
	 * @param bool $is_success
	 *
	 * @since 2.0
	 */
	public function ajax_finish( $response = array(), $is_success = true ) {

		/**
		 * toolset_before_ajax_finish
		 *
		 * Announce that an AJAX call in Toolset is about to be completed.
		 *
		 * @param string $action Name of the AJAX action.
		 * @param array $response Custom response data.
		 * @param bool $is_success Indication of success or failure.
		 *
		 * @since Types 3.3
		 */
		do_action( 'toolset_before_ajax_finish', toolset_getarr( $_REQUEST, 'action' ), $response, $is_success );

		if ( $is_success ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}


	/**
	 * Handles all initialization that is needed when doing AJAX,
	 * except the actual AJAX callbacks.
	 *
	 * Note that this gets fired when the class is intialized, not only during AJAX calls.
	 *
	 * @since m2m
	 */
	protected function additional_ajax_init() {
	}

}
