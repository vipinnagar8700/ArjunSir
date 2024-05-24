<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

use OTGS\Toolset\Common\Interop\HandlerInterface;

/**
 * Class ElementorModules
 *
 * Handles the registration of all the Toolset Elementor Widgets.
 *
 * @since 3.0.5
 */
class ElementorModules implements HandlerInterface {
	/**
	 * Minimum Elementor Version
	 *
	 * @var string Minimum Elementor version required to run the widget.
	 */
	const MINIMUM_ELEMENTOR_VERSION             = '2.0.0';
	const ELEMENTOR_VERSION_REGISTER_API_CHANGE = '3.5.0';

	const PAGE_BUILDER_NAME = 'elementor';

	private $toolset_elementor_widgets;

	protected $constants;

	protected $is_views_active;

	protected $is_forms_active;

	public function __construct(
		\Toolset_Constants $constants = null,
		\Toolset_Condition_Plugin_Views_Active $is_views_active = null,
		\Toolset_Condition_Plugin_Cred_Active $is_forms_active = null
	) {
		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();

		$this->is_views_active = $is_views_active
			? $is_views_active
			: new \Toolset_Condition_Plugin_Views_Active();

		$this->is_forms_active = $is_forms_active
			? $is_forms_active
			: new \Toolset_Condition_Plugin_Cred_Active();
	}

	/**
	 * @param  string $version_number
	 *
	 * @return bool
	 */
	private function is_version_above( $version_number ) {
		return version_compare( $this->constants->constant( 'ELEMENTOR_VERSION' ), self::MINIMUM_ELEMENTOR_VERSION, '>=' );
	}

	/**
	 * Initializes the Page Builder Module integration for the Elementor page builder.
	 */
	public function initialize() {
		// Check if Elementor installed and activated.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Check for required minimum Elementor version.
		if ( ! $this->is_version_above( self::MINIMUM_ELEMENTOR_VERSION ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_widgets_category' ) );

		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Checks that Elementor is loaded and is on the proper version to register the Toolset Elementor widgets.
	 *
	 * @since 3.0.7 Added a "Toolset" category of widgets on the Elementor sidebar.
	 */
	public function init_hooks() {
		// Register Frontend Widget Scripts.
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_frontend_widget_scripts' ) );

		// Enqueue Frontend Widget Scripts.
		add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_frontend_widget_scripts' ) );

		// Εnqueue Editor Widget Styles.
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_widget_styles' ) );

		// Register Frontend Widget Styles.
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_frontend_widget_styles' ) );

		// Enqueue Frontend Widget Styles.
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_frontend_widget_styles' ) );

		// Register widgets category.
		add_action( 'elementor/elements/elements_registered', array( $this, 'register_widgets_category' ) );

		// Register widgets.
		if ( $this->is_version_above( self::MINIMUM_ELEMENTOR_VERSION ) ) {
			add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		} else {
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
		}

		// Initialize widgets.
		$this->initialize_widgets();
	}

	/**
	 * Initilizes the Toolset Elementor page builder widgets.
	 *
	 * @param ViewWidget|null $view_widget
	 * @param FormWidget|null $form_widget
	 */
	public function initialize_widgets(
		ViewWidget $view_widget = null,
		FormWidget $form_widget = null
	) {

		if ( $this->is_views_active->is_met() ) {
			$this->view_widget = $view_widget
				? $view_widget
				: new ViewWidget();

			$this->toolset_elementor_widgets['view'] = $this->view_widget;
		}

		if ( $this->is_forms_active->is_met() ) {
			$this->form_widget = $form_widget
				? $form_widget
				: new FormWidget();

			$this->toolset_elementor_widgets['form'] = $this->form_widget;
		}

		/**
		 * Hook for the initialization script of the Toolset Elementor widgets.
		 *
		 * @since 3.0.7
		 */
		do_action( 'toolset_initialize_elementor_widgets' );
	}

	/**
	 * Register the Toolset Elementor widgets.
	 *
	 * @param  \Elementor\Widgets_Manager $widgets_manager
	 *
	 * @throws Exception
	 */
	public function register_widgets( $widgets_manager ) {
		foreach ( $this->toolset_elementor_widgets as $widget ) {
			$widgets_manager->register_widget_type( $widget );
		}
	}

	/**
	 * Register the Toolset widgets category.
	 *
	 * @since 3.0.7
	 */
	public function register_widgets_category() {
		\Elementor\Plugin::instance()->elements_manager->add_category(
			'toolset-modules',
			array(
				'title' => __( 'Toolset', 'wpv-views' ),
			)
		);
	}

	/**
	 * Registers the frontend scripts required for each widget.
	 */
	public function register_frontend_widget_scripts() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			/**
			 * Register frontend scripts for the backend widget preview.
			 *
			 * Elementor handles the scripts for the backend preview as frontend scripts, so if we want to only register the
			 * scripts on the backend for widget preview, we need to determine if we are on "preview mode".
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_register_scripts/backend_preview' );
		} else {
			/**
			 * Register frontend scripts for the frontend widget rendering.
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_register_scripts/frontend_rendering' );
		}
	}

	/**
	 * Enqueues the frontend scripts required for each widget.
	 */
	public function enqueue_frontend_widget_scripts() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			/**
			 * Enqueue frontend scripts for the backend widget preview.
			 *
			 * Elementor handles the scripts for the backend preview as frontend scripts, so if we want to only enqueue the
			 * scripts on the backend for widget preview, we need to determine if we are on "preview mode".
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_enqueue_scripts/backend_preview' );
		} else {
			/**
			 * Enqueue frontend scripts for the frontend widget rendering.
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_enqueue_scripts/frontend_rendering' );
		}
	}

	/**
	 * Enqueues the styles required for each widget.
	 */
	public function enqueue_editor_widget_styles() {
		/**
		 * Enqueue styles for the editpr.
		 *
		 * @since 3.0.7
		 */
		do_action( 'elementor/editor/after_enqueue_styles/backend' );
	}

	/**
	 * Registers the frontend scripts required for each widget.
	 */
	public function register_frontend_widget_styles() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			/**
			 * Register frontend styles for the backend widget preview.
			 *
			 * Elementor handles the styles for the backend preview as frontend styles, so if we want to only register the
			 * styles on the backend for widget preview, we need to determine if we are on "preview mode".
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_register_styles/backend_preview' );
		} else {
			/**
			 * Register frontend styles for the frontend widget rendering.
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_register_styles/frontend_rendering' );
		}
	}

	/**
	 * Enqueues the frontend styles required for each widget.
	 */
	public function enqueue_frontend_widget_styles() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			/**
			 * Enqueue frontend styles for the backend widget preview.
			 *
			 * Elementor handles the styles for the backend preview as frontend styles, so if we want to only enqueue the
			 * styles on the backend for widget preview, we need to determine if we are on "preview mode".
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_enqueue_styles/backend_preview' );
		} else {
			/**
			 * Enqueue frontend styles for the frontend widget rendering.
			 *
			 * @since 3.0.7
			 */
			do_action( 'elementor/frontend/after_enqueue_styles/frontend_rendering' );
		}
	}

	public function get_toolset_elementor_widgets() {
		return $this->toolset_elementor_widgets;
	}
}
