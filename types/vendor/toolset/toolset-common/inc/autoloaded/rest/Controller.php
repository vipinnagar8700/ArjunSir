<?php

namespace OTGS\Toolset\Common\Rest;

/**
 * Toolset Common Rest API manager.
 *
 * Initializes the REST API integration for individual plugins.
 *
 * @since 3.4
 */
class Controller {


	const VERSION = 1;

	/** @var string */
	public $endpoint_namespace;


	/**
	 * Manager constructor.
	 */
	public function __construct() {
		$this->endpoint_namespace = 'toolset/v' . self::VERSION;
	}


	/**
	 * Initialize the REST API integration.
	 */
	public function initialize() {
		add_action( 'rest_api_init', array( $this, 'toolset_rest_api_init' ) );
	}


	/**
	 * Register and initialize each plugin integration.
	 */
	public function toolset_rest_api_init() {
		$utils = new Utils();

		$types_condition = new \Toolset_Condition_Plugin_Types_Active();
		$settings = \Toolset_Settings::get_instance();
		if ( $types_condition->is_met() && $settings->get( \Toolset_Settings::EXPOSE_CUSTOM_FIELDS_IN_REST ) ) {
			$types_rest = new Plugin\Types( $this, $utils, new \Toolset_Element_Factory() );
			$types_rest->initialize();
		}
	}

}
