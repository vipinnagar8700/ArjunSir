<?php

/**
 * Initialize the Auryn dependency injector and offer it through a toolset_dic filter and functions.
 *
 * @since 3.0.6
 *
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace {

	/**
	 * @return \OTGS\Toolset\Common\Auryn\Injector
	 */
	function toolset_dic() {
		static $dic;

		if ( null === $dic ) {
			/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
			$dic = new \OTGS\Toolset\Common\Auryn\Injector();
		}

		return $dic;
	}


	/** @noinspection PhpDocMissingThrowsInspection */
	/**
	 * @param $class_name
	 * @param array $args
	 *
	 * @return mixed
	 * @deprecated See https://github.com/rdlowrey/auryn#example-use-cases
	 */
	function toolset_dic_make( $class_name, $args = [] ) {
		/** @noinspection PhpUnhandledExceptionInspection */
		return toolset_dic()->make( $class_name, $args );
	}


	add_filter( 'toolset_dic', static function ( /** @noinspection PhpUnusedParameterInspection */ $ignored ) {
		return toolset_dic();
	} );

}


/**
 * Initialize the DIC for usage of Toolset Common classes.
 */

namespace OTGS\Toolset\Common\DicSetup {

	use OTGS\Toolset\Common\GuiBase\DialogBoxFactory;
	use OTGS\Toolset\Common\Relationships\DatabaseLayer\AssociationQueryCache;
	use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory;
	use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
	use OTGS\Toolset\Common\Utils\InMemoryCache;
	use OTGS\Toolset\Common\Utils\RequestMode;
	use OTGS\Toolset\Common\WPML\WpmlService;
	use Toolset_Element_Factory;
	use OTGS\Toolset\Common\Upgrade\ExecutedCommands;
	use OTGS\Toolset\Common\Relationships\MainController;
	use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Migration_Controller;

	/** @var \OTGS\Toolset\Common\Auryn\Injector $dic */
	$dic = apply_filters( 'toolset_dic', null );

	// To expose existing singleton classes, use delegate callbacks. These callbacks will
	// be invoked only when the instance is actually needed, thus save performance.
	// Only after a delegate is used, we'll use the $injector->share() method to
	// provide the singleton instance directly and to improve performance a bit further.
	$singleton_delegates = [
		\Toolset_Ajax::class => static function () {
			return \Toolset_Ajax::get_instance();
		},
		\Toolset_Assets_Manager::class => static function () {
			return \Toolset_Assets_Manager::get_instance();
		},
		\Toolset_Output_Template_Repository::class => static function () {
			return \Toolset_Output_Template_Repository::get_instance();
		},
		\Toolset_Post_Type_Repository::class => static function () {
			return \Toolset_Post_Type_Repository::get_instance();
		},
		\Toolset_Relationship_Definition_Repository::class => static function () {
			do_action( 'toolset_do_m2m_full_init' );

			return \Toolset_Relationship_Definition_Repository::get_instance();
		},
		Toolset_Relationship_Migration_Controller::class => static function () {
			/** @var MainController $relationship_controller */
			$relationship_controller = \OTGS\Toolset\Common\Relationships\MainController::get_instance();
			$relationship_controller->initialize();
			$relationship_controller->force_autoloader_initialization();

			return new \OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Migration_Controller();
		},
		\Toolset_Renderer::class => static function () {
			return \Toolset_Renderer::get_instance();
		},
		\Toolset_Constants::class => static function () {
			return new \Toolset_Constants();
		},
		\Toolset_WPML_Compatibility::class => static function () {
			return WpmlService::get_instance();
		},
		WpmlService::class => static function () {
			return WpmlService::get_instance();
		},
		\Toolset_Field_Group_Post_Factory::class => static function () {
			return \Toolset_Field_Group_Post_Factory::get_instance();
		},
		DialogBoxFactory::class => static function () {
			\Toolset_Common_Bootstrap::get_instance()->register_gui_base();

			return new DialogBoxFactory( \Toolset_Gui_Base::get_instance() );
		},
		\wpdb::class => static function () {
			global $wpdb;

			return $wpdb;
		},
		\Toolset_Field_Definition_Factory_Post::class => static function () {
			return \Toolset_Field_Definition_Factory_Post::get_instance();
		},
		\Toolset_Field_Definition_Factory_User::class => static function () {
			return \Toolset_Field_Definition_Factory_User::get_instance();
		},
		\Toolset_Field_Definition_Factory_Term::class => static function () {
			return \Toolset_Field_Definition_Factory_Term::get_instance();
		},
		\Toolset_Condition_Plugin_Views_Active::class => static function () {
			return new \Toolset_Condition_Plugin_Views_Active();
		},
		\Toolset_Condition_Plugin_Layouts_Active::class => static function () {
			return new \Toolset_Condition_Plugin_Layouts_Active();
		},
		\Toolset_Common_Bootstrap::class => static function () {
			return \Toolset_Common_Bootstrap::get_instance();
		},
		\WPCF_Roles::class => static function () {
			return \WPCF_Roles::getInstance();
		},
		'\WP_Views_plugin' => static function () {
			global $WP_Views;

			return $WP_Views;
		},
		DatabaseLayerMode::class => static function () {
			return new \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode();
		},
		\Toolset_Relationship_Controller::class => static function () {
			return \OTGS\Toolset\Common\Relationships\MainController::get_instance();
		},
		MainController::class => static function () {
			return \OTGS\Toolset\Common\Relationships\MainController::get_instance();
		},
		AssociationQueryCache::class => static function () {
			return AssociationQueryCache::get_instance();
		},
		\Toolset_Gui_Base::class => static function () {
			$toolset_common_bootstrap = \Toolset_Common_Bootstrap::get_instance();
			$toolset_common_bootstrap->register_gui_base();
			$gui_base = \Toolset_Gui_Base::get_instance();
			$gui_base->init();

			return $gui_base;
		},
		ExecutedCommands::class => static function () {
			return new \OTGS\Toolset\Common\Upgrade\ExecutedCommands();
		},
		DatabaseLayerFactory::class => static function() {
			global $wpdb;
			return new DatabaseLayerFactory(
				toolset_dic()->make( DatabaseLayerMode::class ), $wpdb, WpmlService::get_instance(), new Toolset_Element_Factory()
			);
		},
		InMemoryCache::class => static function() {
			return InMemoryCache::get_instance();
		},
	];

	foreach ( $singleton_delegates as $class_name => $callback ) {
		/** @noinspection PhpUnhandledExceptionInspection */
		$dic->delegate( $class_name, static function () use ( $callback, $dic ) {
			$instance = $callback();
			$dic->share( $instance );

			return $instance;
		} );
	}

	// Direct instances sharing; Use this *only* for classes that are used in 100% of requests.
	$dic->share( new RequestMode() );
}
