<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

/**
 * Class ViewWidget
 *
 * Handles the Toolset View Elementor Widget.
 *
 * @since 3.0.5
 */
class ViewWidget extends ToolsetElementorWidgetBase {
	const FRONTEND_WIDGET_SCRIPT = 'toolset-pageBuilder-elementor-widget-view-editor-js';

	const FRONTEND_WIDGET_STYLE = 'toolset-pageBuilder-elementor-widget-view-editor-frontend-css';

	const FRONTEND_WIDGET_SCRIPT_LOCALIZATION_OBJECT_NAME = 'toolsetPageBuilderElementorWidgetViewStrings';

	const VIEW_WIDGET_ASSETS_RELATIVE_URL = '/inc/autoloaded/interop/handler/elementor/widgets/view/assets';

	/**
	 * Initiliazes the Toolset View ELementor widget.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for the Toolset View ELementor widget.
	 */
	private function init_hooks() {
		parent::init_common_hooks();

		add_filter( 'elementor/widgets/black_list', array( $this, 'blacklist_views_widgets' ) );

		add_action( 'wpv_action_before_render_views_wp_widget_form', array( $this, 'maybe_use_view_elementor_widget_instead' ) );

		add_action( 'elementor/frontend/after_register_scripts/backend_preview', array( $this, 'register_editor_preview_widget_scripts' ) );

		add_action( 'elementor/frontend/after_enqueue_scripts/backend_preview', array( $this, 'enqueue_editor_preview_widget_scripts' ) );
	}

	/**
	 * The name of the Toolset View Elementor widget.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'toolset-view';
	}

	/**
	 * The title of the Toolset View Elementor widget.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Toolset View', 'wpv-view' );
	}

	/**
	 * The icon of the Toolset View Elementor widget.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'icon-views-logo';
	}

	/**
	 * The category of the Toolset View Elementor widget.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'toolset-modules' );
	}

	/**
	 * Registers the Toolset View Elementor widget controls.
	 */
	protected function register_controls() {
		$widget_controls = new ViewWidgetControls( $this );
		$widget_controls->register_controls();

	}

	/**
	 * Renders the Toolset View Elementor widget output on the frontend.
	 */
	public function render() {
		if ( $this->tc_bootstrap->get_request_mode() === $this->constants->constant( 'Toolset_Common_Bootstrap::MODE_FRONTEND' ) ) {
			$output = $this->render_frontend_widget();
		} else {
			$output = $this->render_backend_widget_preview();
		}

		echo $output;
	}

	protected function render_frontend_widget() {
		return $this->render_view();
	}

	/**
	 * Render the View using the function API so block-based Views get all their assets.
	 *
	 * This splits from the shared rendering mechanism based on shortcodes.
	 *
	 * @return string
	 */
	private function render_view() {
		$settings = $this->get_settings_for_display();
		$view_id = toolset_getarr( $settings, 'view', '0' );
		$view = get_post( $view_id );

		$attributes = [];

		if (
			null === $view ||
			(int) $view_id <= 0 ||
			(int) $view_id !== $view->ID
		) {
			return '';
		}

		if ( is_numeric( $view->ID ) ) {
			$attributes['id'] = $view->ID;
		} else {
			$attributes['name'] = $view->ID;
		}

		$attributes['limit'] = (int) toolset_getnest( $settings, array( 'limit', 'size' ), '0' );;
		$attributes['offset'] = (int) toolset_getnest( $settings, array( 'offset', 'size' ), '0' );
		$attributes['orderby'] = toolset_getarr( $settings, 'orderby', '' );
		$attributes['order'] = toolset_getarr( $settings, 'order', '' );
		$attributes['orderby_second'] = toolset_getarr( $settings, 'secondaryOrderby', '' );
		$attributes['order_second'] = toolset_getarr( $settings, 'secondaryOrder', '' );

		$attributes = array_filter( $attributes );

		$has_custom_search = toolset_getarr( $settings, 'has_custom_search', false );
		$has_submit_button = toolset_getarr( $settings, 'has_submit_button', false );
		$form_display = toolset_getarr( $settings, 'form_display', 'full' );
		$form_only_display = toolset_getarr( $settings, 'form_only_display', 'same_page' );
		$other_page = toolset_getarr( $settings, 'other_page', '0' );

		if (
			'true' === $has_custom_search &&
			'form' === $form_display
		) {
			if ( 'same_page' === $form_only_display ) {
				$attributes['target_id'] = 'self';
			} elseif (
				'true' === $has_submit_button &&
				'other_page' === $form_only_display &&
				'0' !== $other_page
			) {
				$attributes['target_id'] = $other_page;
			}
		}

		if (
			'true' === $has_custom_search &&
			'results' === $form_display
		) {
			$attributes['view_display'] = 'layout';
			$attributes['target_id'] = '';
		}

		$attributes = array_filter( $attributes );

		$return_true = function() { return true; };

		add_filter( 'wpv_filter_disable_caching', $return_true );
		$output = render_view( $attributes );
		remove_filter( 'wpv_filter_disable_caching', $return_true );

		return $output;
	}

	/**
	 * Renders the Toolset View Elementor widget output for the widget preview on the editor.
	 *
	 * @param bool $echo Determines if the output will be echo-ed or returned (mainly for unit-testing purposes).
	 *
	 * @return string The markup for the editor widget rendering echo-ed or returned.
	 */
	private function render_backend_widget_preview() {
		$settings = $this->get_settings_for_display();
		$view_id = toolset_getarr( $settings, 'view', '0' );
		$view = get_post( $view_id );

		if (
			null !== $view &&
			(int) $view_id > 0 &&
			(int) $view_id === $view->ID
		) {
			$html = $this->render_view();
			$html .= $this->render_module_overlay( $view->post_title );
		} else {
			$html = $this->render_no_view_selected_message();
		}

		return $html;
	}

	/**
	 * Creates the View shortcode according to the selected values on the widget options.
	 *
	 * @param null|array                                                                           $settings      The widget settings.
	 * @param null|\WP_Post $view The View instance
	 *
	 * @return string
	 */
	protected function create_widget_shortcode( $settings = null, $view = null ) {
		$settings = null !== $settings ? $settings : $this->get_settings_for_display();
		$view_id = toolset_getarr( $settings, 'view', '0' );
		$view = null !== $view ? $view : get_post( $view_id );

		$shortcode = '';
		if (
			null !== $view &&
			(int) $view_id > 0 &&
			(int) $view_id === $view->ID
		) {
			$shortcode_start = '[wpv-view';
			$shortcode_end = ']';
			$view_attr = is_numeric( $view->ID ) ? ' id="' . $view->ID . '"' : ' name="' . $view->ID . '"';

			$limit_value = (int) toolset_getnest( $settings, array( 'limit', 'size' ), '0' );
			$limit =  $limit_value > 0 ? ' limit="' . $limit_value . '"' : '';

			$offset_value = (int) toolset_getnest( $settings, array( 'offset', 'size' ), '0' );
			$offset = $offset_value > 0 ? ' offset="' . $offset_value . '"' : '';

			$orderby_value = toolset_getarr( $settings, 'orderby', '' );
			$orderby = '' !== $orderby_value ? ' orderby="' . $orderby_value . '"' : '';

			$order_value = toolset_getarr( $settings, 'order', '' );
			$order = '' !== $order_value ? ' order="' . $order_value . '"' : '';

			$secondary_order_by_value = toolset_getarr( $settings, 'secondaryOrderby', '' );
			$secondary_order_by = '' !== $secondary_order_by_value ? ' orderby_second="' . $secondary_order_by_value . '"' : '';

			$secondary_order_value = toolset_getarr( $settings, 'secondaryOrder', '' );
			$secondary_order = '' !== $secondary_order_value ? ' order_second="' . $secondary_order_value . '"' : '';

			$has_custom_search = toolset_getarr( $settings, 'has_custom_search', false );
			$has_submit_button = toolset_getarr( $settings, 'has_submit_button', false );
			$form_display = toolset_getarr( $settings, 'form_display', 'full' );
			$form_only_display = toolset_getarr( $settings, 'form_only_display', 'same_page' );
			$other_page = toolset_getarr( $settings, 'other_page', '0' );
			$target = '';
			$view_display = '';
			if (
				'true' === $has_custom_search &&
				'form' === $form_display
			) {
				$shortcode_start = '[wpv-form-view';

				if ( 'same_page' === $form_only_display ) {
					$target = ' target_id="self"';
				} elseif (
					'true' === $has_submit_button &&
					'other_page' === $form_only_display &&
					'0' !== $other_page
				) {
					$target = ' target_id="' . $other_page . '"';
				}
			}

			if (
				'true' === $has_custom_search &&
				'results' === $form_display
			) {
				$target = '';
				$view_display = ' view_display="layout"';
			}

			$shortcode = $shortcode_start . $view_attr . $limit . $offset . $orderby . $order . $secondary_order_by . $secondary_order . $target . $view_display . $shortcode_end;
		}

		return $shortcode;
	}

	/**
	 * Renders the Toolset View Elementor widget output for the widget preview on the editor when no View is selected.
	 *
	 * @return string The markup for the editor widget rendering, when no view is selected.
	 */
	private function render_no_view_selected_message() {
		$renderer = $this->toolset_renderer;
		$template_repository = \Toolset_Output_Template_Repository::get_instance();
		$context = array(
			'element_type' => __( 'View', 'wpv-views' ),
		);
		$html = $renderer->render(
			$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_ELEMENTOR_NOTHING_SELECTED' ) ),
			$context,
			false
		);

		return $html;
	}

	/**
	 * Renders the Toolset View Elementor widget overlay for the widget preview on the editor.
	 *
	 * @param string $view_title The title of the selected View.
	 *
	 * @return string The markup for the editor widget overlay rendering returned.
	 */
	private function render_module_overlay( $view_title ) {
		$renderer = $this->toolset_renderer;
		$template_repository = \Toolset_Output_Template_Repository::get_instance();
		$context = array(
			'module_title' => $view_title,
			'module_type' => __( 'View', 'wpv-views' ),
		);
		$html = $renderer->render(
			$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_OVERLAY' ) ),
			$context,
			false
		);

		return $html;
	}

	/**
	 * Registers the scripts required for the editor preview.
	 */
	public function register_editor_preview_widget_scripts() {
		wp_register_script(
			self::FRONTEND_WIDGET_SCRIPT,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . self::VIEW_WIDGET_ASSETS_RELATIVE_URL . '/js/' . $this->get_name() . '.editor.js',
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		$toolset_ajax_controller = \Toolset_Ajax::get_instance();
		wp_localize_script(
			self::FRONTEND_WIDGET_SCRIPT,
			self::FRONTEND_WIDGET_SCRIPT_LOCALIZATION_OBJECT_NAME,
			array(
				'ajaxURL' => admin_url( 'admin-ajax.php' ),
				'hasCustomSearchAction' => $toolset_ajax_controller->get_action_js_name( \Toolset_Ajax::CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS ),
				'hasCustomSearchNonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS ),
				'hasCustomSearchControlKey' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::HAS_CUSTOM_SEARCH_CONTROL_KEY,
				'hasSubmitButtonControlKey' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::HAS_SUBMIT_BUTTON_CONTROL_KEY,
				'hasBlocksControlKey' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::HAS_BLOCKS_CONTROL_KEY,
				'formOnlyDisplayControlKey' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::FORM_ONLY_DISPLAY,
				'formOnlyDisplayControlValueSamePage' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::SAME_PAGE,
				'dismissAddingSearchResultsToPage' => \OTGS\Toolset\Common\Interop\Handler\Elementor\ViewWidgetControls::DISMISS_ADDING_SEARCH_RESULTS_TO_PAGE,
				'editViewURL' => admin_url( 'admin.php?page=views-editor&view_id=' ),
				'editPostForResultsURL' => admin_url( 'post.php' ) . '?post=%1$s&action=edit&completeview=%2$s',
				'selectViewFirstMessage' => __( 'Please, select a View first!', 'wpv-views' ),
				'isPreviewMode' => \Elementor\Plugin::$instance->preview->is_preview_mode(),
				'ajaxErrorMessage' => __( 'Something went wrong with the AJAX request, please try again.', 'wpv-views' ),
			)
		);
	}

	/**
	 * Enqueues the scripts required for the editor preview.
	 */
	public function enqueue_editor_preview_widget_scripts() {
		wp_enqueue_script( self::FRONTEND_WIDGET_SCRIPT );
	}

	/**
	 * If one of the Views WordPress widgets is loaded in the editor, it prints a warning message encouraging the user to
	 * prefer using the Toolset View Elementor widget instead of the WordPress widget.
	 */
	public function maybe_use_view_elementor_widget_instead() {
		if ( \wp_doing_ajax() ) {
			$actions = isset( $_REQUEST['actions'] ) ? json_decode( stripslashes( $_REQUEST['actions'] ), true ) : array() ;
			if ( in_array( 'editor_get_wp_widget_form', array_keys( $actions ) ) ) {
				$widget_type = isset( $actions['editor_get_wp_widget_form']['data']['widget_type'] ) && strpos( $actions['editor_get_wp_widget_form']['data']['widget_type'], 'filter' ) ?
					__( 'WP Views Filter', 'wpv-views' ) :
					__( 'WP Views', 'wpv-views' );

				$template_repository = \Toolset_Output_Template_Repository::get_instance();
				$this->toolset_renderer->render(
					$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_ELEMENTOR_USE_VIEW_ELEMENTOR_WIDGET_INSTEAD' ) ),
					array(
						'widget_type' => $widget_type,
					),
					true
				);
			}
		}
	}

	/**
	 * Filter callback that blacklists the Views widget so that they are not offered through the Elementor sidebar.
	 *
	 * @param array $blacklisted_widgets The array with the widgets to be blacklisted.
	 *
	 * @return array The array with the widgets to be blacklisted.
	 */
	public function blacklist_views_widgets( $blacklisted_widgets ) {
		if ( class_exists( '\WPV_Settings' ) ) {
			$views_settings = \WPV_Settings::get_instance();

			if ( ! $views_settings->allow_views_wp_widgets_in_elementor ) {
				$blacklisted_widgets[] = 'WPV_Widget';
				$blacklisted_widgets[] = 'WPV_Widget_filter';
			}
		}

		return $blacklisted_widgets;
	}
}
