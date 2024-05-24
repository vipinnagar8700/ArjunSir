<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

/**
 * Class FormWidget
 *
 * Handles the Toolset Form Elementor Widget.
 *
 * @since 3.0.7
 */
class FormWidget extends ToolsetElementorWidgetBase {
	const FRONTEND_WIDGET_SCRIPT = 'toolset-pageBuilder-elementor-widget-form-editor-js';

	const FRONTEND_WIDGET_STYLE = 'toolset-pageBuilder-elementor-widget-form-editor-frontend-css';

	const FRONTEND_WIDGET_SCRIPT_LOCALIZATION_OBJECT_NAME = 'toolsetPageBuilderElementorWidgetFormStrings';

	const FORM_WIDGET_ASSETS_RELATIVE_URL = '/inc/autoloaded/interop/handler/elementor/widgets/form/assets';

	private $forms_preview_renderer;

	public function __construct(
		array $data = array(),
		array $args = null,
		\Toolset_Common_Bootstrap $tc_bootstrap = null,
		\Toolset_Constants $constants = null,
		\Toolset_Renderer $toolset_renderer = null,
		\OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer $forms_preview_renderer = null
	) {
		parent::__construct(
			$data,
			$args,
			$tc_bootstrap,
			$constants,
			$toolset_renderer
		);

		$this->forms_preview_renderer = $forms_preview_renderer
			? $forms_preview_renderer
			: new \OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer();
	}

	/**
	 * Initiliazes the Toolset Form ELementor widget.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for the Toolset Form ELementor widget.
	 */
	private function init_hooks() {
		parent::init_common_hooks();

		add_action( 'elementor/frontend/after_register_scripts/backend_preview', array( $this, 'register_editor_preview_widget_scripts' ) );

		add_action( 'elementor/frontend/after_enqueue_scripts/backend_preview', array( $this, 'enqueue_editor_preview_widget_scripts' ) );

		add_action( 'elementor/frontend/after_register_styles/backend_preview', array( $this, 'register_editor_preview_widget_styles' ) );

		add_action( 'elementor/frontend/after_enqueue_styles/backend_preview', array( $this, 'enqueue_editor_preview_widget_styles' ) );
	}

	/**
	 * The name of the Toolset Form Elementor widget.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'toolset-form';
	}

	/**
	 * The title of the Toolset Form Elementor widget.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Toolset Form', 'wpv-views' );
	}

	/**
	 * The icon of the Toolset Form Elementor widget.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'icon-cred-logo';
	}

	/**
	 * The category of the Toolset Form Elementor widget.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'toolset-modules' );
	}

	/**
	 * Registers the Toolset Form Elementor widget controls.
	 */
	protected function register_controls() {
		$widget_controls = new FormWidgetControls( $this );
		$widget_controls->register_controls();

	}

	/**
	 * Renders the Toolset Form Elementor widget output on the frontend.
	 */
	public function render() {
		if ( $this->tc_bootstrap->get_request_mode() === $this->constants->constant( 'Toolset_Common_Bootstrap::MODE_FRONTEND' ) ) {
			$output = $this->render_frontend_widget();
		} else {
			$output = $this->render_backend_widget_preview();
		}

		echo $output;
	}

	/**
	 * Renders the Toolset Form Elementor widget output for the widget preview on the editor.
	 *
	 * @return bool|string The markup for the editor widget rendering echo-ed or returned.
	 */
	private function render_backend_widget_preview() {
		$settings = $this->get_settings_for_display();
		$form_id = toolset_getarr( $settings, 'form', '0' );
		$form = get_post( $form_id );

		if (
			null !== $form &&
			(int) $form_id > 0 &&
			(int) $form_id === $form->ID
		) {
			$html = $this->forms_preview_renderer->render_preview( $form );
			$html .= $this->render_module_overlay( $form->post_title );
		} else {
			$html = $this->render_no_form_selected_message();
		}

		return $html;
	}

	/**
	 * Creates the Form shortcode according to the selected values on the widget options.
	 *
	 * @param null|array    $settings      The widget settings.
	 * @param null|\WP_Post $view_instance The Form instance
	 *
	 * @return string
	 */
	protected function create_widget_shortcode( $settings = null, $form = null ) {
		$settings = null !== $settings ? $settings : $this->get_settings_for_display();
		$form_id = toolset_getarr( $settings, 'form', '0' );
		$form = null !== $form ? $form : get_post( $form_id );

		if (
			null === $form ||
			(int) $form_id <= 0 ||
			(int) $form_id !== $form->ID
		) {
			return '';
		}

		$shortcode_start = '[';
		$shortcode_end = ']';

		$form_type_attribute = str_replace( '-', '_', $form->post_type );
		$form_slug_attribute = ' form="' . $form->post_name . '"';

		$resource_to_edit_attribute = '';
		$resource_to_edit = toolset_getarr( $settings, 'resource_to_edit', '' );
		$another_resource_error = '';

		switch ( $resource_to_edit ) {
			case 'post':
				$post_to_edit = toolset_getarr( $settings, 'post_to_edit', '' );
				if (
					'' !== $post_to_edit &&
					'current_post' !== $post_to_edit
				) {
					$another_post = toolset_getarr( $settings, 'another_post_select_control', '' );
					$resource_to_edit_attribute = '' !== $another_post ? ' ' . $resource_to_edit . '="' . $another_post . '"' : '';
					$another_resource_error = '' === $another_post ? __( 'The Toolset Form widget displays a "Post edit" form, but no post to edit was selected.', 'wpv-views' ) : '';
				}
				break;
			case 'user':
				$user_to_edit = toolset_getarr( $settings, 'user_to_edit', '' );
				if (
					'' !== $user_to_edit &&
					'current_user' !== $user_to_edit
				) {
					$another_user = toolset_getarr( $settings, 'another_user_select_control', '' );
					$resource_to_edit_attribute = '' !== $another_user ? ' ' . $resource_to_edit . '="' . $another_user . '"' : '';
					$another_resource_error = '' === $another_user ? __( 'The Toolset Form widget displays a "User edit" form, but no user to edit was selected.', 'wpv-views' ) : '';
				}
				break;
		}

		$shortcode = '' === $another_resource_error ? $shortcode_start . $form_type_attribute . $form_slug_attribute . $resource_to_edit_attribute . $shortcode_end : $another_resource_error;

		return $shortcode;
	}

	/**
	 * Renders the Toolset Form Elementor widget output for the widget preview on the editor when no Form is selected.
	 *
	 *
	 * @return bool|string The markup for the editor widget rendering, when no Form is selected.
	 */
	private function render_no_form_selected_message() {
		$template_repository = \Toolset_Output_Template_Repository::get_instance();
		$context = array(
			'element_type' => __( 'Form', 'wpv-views' ),
		);
		$html = $this->toolset_renderer->render(
			$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_ELEMENTOR_NOTHING_SELECTED' ) ),
			$context,
			false
		);

		return $html;
	}

	/**
	 * Renders the Toolset Form Elementor widget overlay for the widget preview on the editor.
	 *
	 * @param string $form_title The title of the selected Form.
	 *
	 * @return bool|string The markup for the editor widget overlay rendering.
	 */
	private function render_module_overlay( $form_title ) {
		$renderer = $this->toolset_renderer;
		$template_repository = \Toolset_Output_Template_Repository::get_instance();
		$context = array(
			'module_title' => $form_title,
			'module_type' => __( 'Form', 'wpv-views' ),
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
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . self::FORM_WIDGET_ASSETS_RELATIVE_URL . '/js/' . $this->get_name() . '.editor.js',
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		wp_localize_script(
			self::FRONTEND_WIDGET_SCRIPT,
			self::FRONTEND_WIDGET_SCRIPT_LOCALIZATION_OBJECT_NAME,
			array(
				'editFormURL' => admin_url( 'post.php?action=edit&post=' ),
				'selectFormFirstMessage' => __( 'Please, select a Form first!', 'wpv-views' ),
				'resourceToEditControlKey' => $this->constants->constant( '\OTGS\Toolset\Common\Interop\Handler\Elementor\FormWidgetControls::RESOURCE_TO_EDIT_CONTROL_KEY' ),
				'allForms' => $this->get_all_forms(),
				'isPreviewMode' => \Elementor\Plugin::$instance->preview->is_preview_mode(),
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
	 * Registers the styles required for the editor preview.
	 */
	public function register_editor_preview_widget_styles() {
		wp_register_style(
			self::FRONTEND_WIDGET_STYLE,
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . self::FORM_WIDGET_ASSETS_RELATIVE_URL . '/css/' . $this->get_name() . '.editor.frontend.css',
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);
	}

	/**
	 * Enqueues the styles required for the editor preview.
	 */
	public function enqueue_editor_preview_widget_styles() {
		wp_enqueue_style( self::FRONTEND_WIDGET_STYLE );
	}

	/**
	 * Returns an array containing all the available forms along with information about the form type (post/user) and the form action (new/edit).
	 *
	 * @return array
	 */
	private function get_all_forms() {
		$forms = array();

		$published_forms['post'] = apply_filters( 'cred_get_available_forms', array(), \CRED_Form_Domain::POSTS );
		$published_forms['user'] = apply_filters( 'cred_get_available_forms', array(), \CRED_Form_Domain::USERS );

		foreach ( $published_forms as $form_type => $form_actions ) {
			foreach ( $form_actions as $form_action_type => $form_action ) {
				foreach ( $form_action as $form ) {
					$forms[ $form->ID ] = array(
						'form_id' => $form->ID,
						'form_type' => $form_type,
						'form_action' => $form_action_type,
						'form_title' => $form->post_title,
					);
				}
			}
		}

		return $forms;
	}
}
