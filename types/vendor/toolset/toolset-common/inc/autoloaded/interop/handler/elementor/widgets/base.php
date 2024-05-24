<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

/**
 * Class ToolsetElementorWidgetBase
 *
 * The base class for the Toolset Elementor widgets.
 *
 * @since 3.0.7
 */
abstract class ToolsetElementorWidgetBase extends \Elementor\Widget_Base {
	const COMMON_FRONTEND_WIDGET_STYLE = 'toolset-pageBuilder-elementor-widget-common-editor-frontend-css';

	const COMMON_BACKEND_WIDGET_STYLE = 'toolset-pageBuilder-elementor-widget-common-editor-css';

	const COMMON_WIDGET_ASSETS_RELATIVE_URL = '/inc/autoloaded/interop/handler/elementor/widgets/common/assets';

	protected $constants;

	protected $tc_bootstrap;

	protected $toolset_renderer;

	protected $initial_settings;

	public function __construct(
		array $data = array(),
		array $args = null,
		\Toolset_Common_Bootstrap $tc_bootstrap = null,
		\Toolset_Constants $constants = null,
		\Toolset_Renderer $toolset_renderer = null
	) {
		$this->initial_settings = toolset_getarr( $data, 'settings', array() );

		parent::__construct( $data, $args );

		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();

		$this->tc_bootstrap = $tc_bootstrap
			? $tc_bootstrap
			: \Toolset_Common_Bootstrap::get_instance();

		$this->toolset_renderer = $toolset_renderer
			? $toolset_renderer
			: \Toolset_Renderer::get_instance();

		add_action( 'toolset_initialize_elementor_widgets', array( $this, 'initialize' ) );
	}

	/**
	 * Initializes the Toolset Elementor widgets common hooks.
	 */
	public function init_common_hooks() {
		add_action( 'elementor/editor/after_enqueue_styles/backend', array( $this, 'enqueue_common_editor_widget_styles' ), 9 );

		add_action( 'elementor/frontend/after_register_styles/backend_preview', array( $this, 'register_common_editor_preview_widget_styles' ) );

		add_action( 'elementor/frontend/after_enqueue_styles/backend_preview', array( $this, 'enqueue_common_editor_preview_widget_styles' ) );
	}

	/**
	 * Registers the Toolset Elementor widgets common editor preview styles.
	 */
	public function register_common_editor_preview_widget_styles() {
		wp_register_style(
			self::COMMON_FRONTEND_WIDGET_STYLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . self::COMMON_WIDGET_ASSETS_RELATIVE_URL . '/css/common.editor.frontend.css',
			array(
				$this->constants->constant( 'Toolset_Assets_Manager::STYLE_ONTHEGOSYSTEMS_ICONS' ),
			),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);
	}

	/*
	 * Enqueues the Toolset Elementor widgets common common editor preview styles.
	 */
	public function enqueue_common_editor_preview_widget_styles() {
		wp_enqueue_style( self::COMMON_FRONTEND_WIDGET_STYLE );
	}

	/**
	 * Enqueues the Toolset Elementor widgets common styles required for the editor.
	 */
	public function enqueue_common_editor_widget_styles() {
		$this->register_otgs_icons_style();

		wp_enqueue_style(
			self::COMMON_BACKEND_WIDGET_STYLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . self::COMMON_WIDGET_ASSETS_RELATIVE_URL . '/css/common.editor.css',
			array(
				$this->constants->constant( 'Toolset_Assets_Manager::STYLE_ONTHEGOSYSTEMS_ICONS' ),
			),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);
	}

	/**
	 * Registers the OTGS Icons style.
	 */
	public function register_otgs_icons_style() {
		wp_register_style(
			$this->constants->constant( 'Toolset_Assets_Manager::STYLE_ONTHEGOSYSTEMS_ICONS' ),
			$this->constants->constant( 'ON_THE_GO_SYSTEMS_BRANDING_REL_PATH' ) . 'onthegosystems-icons/css/onthegosystems-icons.css',
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);
	}

	/**
	 * Renders the Toolset Form Elementor widget output for the frontend.
	 *
	 * @return bool|string The markup for the frontend widget rendering.
	 *
	 * @since 3.0.7
	 */
	protected function render_frontend_widget() {
		// The expanded shortcode needs to be returned here. For the case of posts designed with Elementor there shouldn't
		// be a problem to just return the plain shortcode but for the case of Elementor Templates the widget output is not
		// passed through the "the_content" hook and the shortcodes are not getting expanded during the page rendering.
		return do_shortcode( $this->create_widget_shortcode() );
	}

	abstract public function initialize();

	abstract protected function create_widget_shortcode();
}
