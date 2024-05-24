<?php
/** @noinspection TransitiveDependenciesUsageInspection */
/** @noinspection ClassConstantCanBeUsedInspection */

namespace OTGS\Toolset\Types\Controller\Interop;

use OTGS\Toolset\Common\Auryn\Injector;
use OTGS\Toolset\Common\Utils\RequestMode;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Controller\Interop\Managed\Brizy;
use OTGS\Toolset\Types\Controller\Interop\Managed\RepeatableFieldSortorderTranslation;
use OTGS\Toolset\Types\Controller\Interop\Managed\WpmlTridAutodraftOverride;
use WP_Theme;

/**
 * Provide interoperability with other plugins or themes when needed.
 *
 * Each plugin or a theme that Types needs to (actively) support should
 * have a dedicated "interoperability handler" that, when initialized,
 * will provide such support (preferably via actions and filters).
 *
 * Having everything located in one class will make it very easy to
 * handle and implement future compatibility issues and it will
 * reduce memory usage by loading the code only when needed.
 *
 * Use this as a singleton in production code.
 *
 * @since 2.2.7
 */
class InteropMediator {


	const DEF_IS_NEEDED = 'is_needed';

	const DEF_CLASS_NAME = 'class_name';

	const DEF_INIT_CALLBACK = 'init_callback';


	/** @var WpmlService */
	private $wpml_service;

	/** @var RequestMode */
	private $request_mode;


	/**
	 * OTGS\Toolset\Types\Controller\Interop\InteropMediator constructor.
	 *
	 * @param WpmlService $wpml_service
	 * @param RequestMode $request_mode
	 */
	public function __construct( WpmlService $wpml_service, RequestMode $request_mode ) {
		$this->wpml_service = $wpml_service;
		$this->request_mode = $request_mode;
	}


	/**
	 * Get definitions of all interop handlers.
	 *
	 * Each one has a method for checking whether the handler is needed
	 * and a name - there must be a corresponding class Types_Interop_Handler_{$name}
	 * implementing the Types_Interop_Handler_Interface.
	 *
	 * @return array
	 * @since 2.2.7
	 */
	private function get_interop_handler_definitions() {
		$wpml_service = $this->wpml_service;

		return [
			[
				self::DEF_IS_NEEDED => static function () use ( $wpml_service ) {
					return $wpml_service->is_wpml_active_and_configured();
				},
				self::DEF_CLASS_NAME => 'Wpml',
			],
			[
				self::DEF_IS_NEEDED => [ $this, 'is_divi_active' ],
				self::DEF_CLASS_NAME => 'Divi',
			],
			[
				self::DEF_IS_NEEDED => [ $this, 'is_use_any_font_active' ],
				self::DEF_CLASS_NAME => 'Use_Any_Font',
			],
			[
				self::DEF_IS_NEEDED => [ $this, 'is_the7_active' ],
				self::DEF_CLASS_NAME => 'The7',
			],
			[
				self::DEF_IS_NEEDED => static function () {
					return function_exists( 'sg_cachepress_purge_cache' );
				},
				self::DEF_INIT_CALLBACK => static function ( Injector $dic ) {
					$dic->make( '\OTGS\Toolset\Types\Controller\Interop\Managed\SiteGroundOptimizer' )
						->initialize();
				},
			],
			[
				self::DEF_IS_NEEDED => static function () {
					return defined( 'LITESPEED_ON' );
				},
				self::DEF_INIT_CALLBACK => static function ( Injector $dic ) {
					$dic->make( '\OTGS\Toolset\Types\Controller\Interop\Managed\LiteSpeedCache' )
						->initialize();
				},
			],
			[
				self::DEF_IS_NEEDED => '__return_true',
				self::DEF_INIT_CALLBACK => static function ( Injector $dic ) {
					$dic->make( '\OTGS\Toolset\Types\Controller\Interop\Managed\WordPressImageScaleDown' )
						->initialize();
				},
			],
			[
				self::DEF_IS_NEEDED => function() {
					return $this->request_mode->get() === RequestMode::ADMIN
						&& $this->wpml_service->is_wpml_active_and_configured();
				},
				self::DEF_INIT_CALLBACK => static function( Injector $dic ) {
					$dic->make( WpmlTridAutodraftOverride::class )
						->initialize();
				},
			],
			[
				self::DEF_IS_NEEDED => function() {
					$brizy_gateway = new Brizy\Gateway();
					return $this->request_mode->get() === RequestMode::FRONTEND
						&& $brizy_gateway->is_brizy_active();
				},
				self::DEF_INIT_CALLBACK => static function( Injector $dic ) {
					$dic->make( Brizy::class )->initialize();
				},
			],
			[
				self::DEF_IS_NEEDED => function() {
					return $this->wpml_service->is_wpml_active_and_configured();
				},
				self::DEF_INIT_CALLBACK => static function( Injector $dic ) {
					$dic->make( RepeatableFieldSortorderTranslation::class )->initialize();
				},
			],
		];
	}


	/**
	 * Load and initialize interop handlers if the relevant plugin/theme is active.
	 *
	 * @since 2.2.7
	 */
	public function initialize() {
		/**
		 * Filter types_get_interop_handler_definitions.
		 *
		 * Allows for adjusting interop handlers. See OTGS\Toolset\Types\Controller\Interop\InteropMediator::get_interop_handler_definitions() for details.
		 *
		 * @since 2.2.17
		 */
		$interop_handlers = apply_filters( 'types_get_interop_handler_definitions', $this->get_interop_handler_definitions() );

		foreach ( $interop_handlers as $handler_definition ) {
			$is_needed = call_user_func( $handler_definition[ self::DEF_IS_NEEDED ] );

			if ( ! $is_needed ) {
				continue;
			}

			$init_callback = toolset_getarr( $handler_definition, self::DEF_INIT_CALLBACK );
			if ( is_callable( $init_callback ) ) {
				$init_callback( toolset_dic() );
			} else {
				$handler_class_name = '\Types_Interop_Handler_' . $handler_definition[ self::DEF_CLASS_NAME ];
				call_user_func( $handler_class_name . '::initialize' );
			}
		}
	}


	/**
	 * Check whether the Divi theme is loaded.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	protected function is_divi_active() {
		return function_exists( 'et_setup_theme' );
	}


	/**
	 * Check whether the The7 theme is loaded.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	protected function is_the7_active() {
		return ( 'the7' === $this->get_parent_theme_slug() );
	}


	/**
	 * Check whether the Use Any Font plugin is loaded.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	protected function is_use_any_font_active() {
		return function_exists( 'uaf_activate' );
	}


	/**
	 * Retrieve a "slugized" theme name.
	 *
	 * @return string
	 * @since 2.2.16
	 */
	private function get_parent_theme_slug() {

		/**
		 * @var WP_Theme|null $theme It should be WP_Theme but experience tells us that sometimes the theme
		 * manages to send an invalid value our way.
		 */
		$theme = wp_get_theme();

		if ( ! $theme instanceof WP_Theme ) {
			// Something went wrong but we'll try to recover.
			$theme_name = $this->get_theme_name_from_stylesheet();
		} elseif ( is_child_theme() ) {

			$parent_theme = $theme->parent();

			// Because is_child_theme() can return true while $theme->parent() still returns false, oh dear god.
			if ( ! $parent_theme instanceof WP_Theme ) {
				$theme_name = $this->get_theme_name_from_stylesheet();
			} else {
				$theme_name = $parent_theme->get( 'Name' );
			}
		} else {
			$theme_name = $theme->get( 'Name' );
		}

		// Handle $theme->get() returning false when the Name header is not set.
		if ( false === $theme_name ) {
			return '';
		}

		return str_replace( '-', '_', sanitize_title( $theme_name ) );
	}


	private function get_theme_name_from_stylesheet() {
		$theme_name = '';

		$stylesheet = get_stylesheet();
		if ( is_string( $stylesheet ) && ! empty( $stylesheet ) ) {
			$theme_name = $stylesheet;
		}

		return $theme_name;
	}

}
