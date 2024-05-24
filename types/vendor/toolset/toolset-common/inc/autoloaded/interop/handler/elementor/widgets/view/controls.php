<?php

namespace OTGS\Toolset\Common\Interop\Handler\Elementor;

/**
 * Class that handles the Toolset View Elementor widget controls registration.
 *
 * @since 3.0.7
 */
class ViewWidgetControls extends ToolsetElementorWidgetControlsBase {
	const HAS_CUSTOM_SEARCH_CONTROL_KEY = 'has_custom_search';

	const HAS_SUBMIT_BUTTON_CONTROL_KEY = 'has_submit_button';

	const HAS_BLOCKS_CONTROL_KEY = 'has_blocks';

	const DISMISS_ADDING_SEARCH_RESULTS_TO_PAGE = 'dismiss_adding_search_results_to_page';

	const FORM_ONLY_DISPLAY = 'form_only_display';

	const SAME_PAGE = 'same_page';

	/**
	 * Registers the controls for the Toolset View Elementor widget.
	 */
	public function register_controls() {

		$this->register_view_selection_section();

		$this->register_custom_search_settings_section();

//		$this->register_query_filters_settings_section();

		$this->register_override_basic_settings_section();

		$this->register_secondary_sorting_settings_section();
	}

	/**
	 * Registers the controls for the View selection section of the Toolset View Elementor widget.
	 */
	private function register_view_selection_section() {
		$view_selected_condition = array(
			'view!' => '0',
		);

		$this->widget->start_controls_section(
			'view_selection_section',
			array(
				'label' => __( 'View selection', 'wpv-views' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->widget->add_control(
			'view',
			array(
				'label' => __( 'View', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'groups' => $this->create_view_select_control_options(),
				'default' => '0',
				'description' => __( 'Select a View to render its preview inside the editor.', 'wpv-views' ),
			)
		);

		$this->widget->add_control(
			'hr',
			array(
				'type' => \Elementor\Controls_Manager::DIVIDER,
				'style' => 'thick',
				'condition' => $view_selected_condition,
			)
		);

		$this->widget->add_control(
			'edit_view_btn',
			array(
				'type' => \Elementor\Controls_Manager::BUTTON,
				'separator' => 'default',
				'button_type' => 'default',
				'text' => __( 'Edit View in Toolset', 'wpv-views' ),
				'event' => 'toolset:pageBuilderWidgets:elementor:editor:editView',
				'description' => __( 'Use this button to edit the View in the Views Toolset editor.', 'wpv-views' ),
				'condition' => $view_selected_condition,
			)
		);

		$this->widget->add_control(
			self::HAS_CUSTOM_SEARCH_CONTROL_KEY,
			array(
				'label' => __( 'Has custom search', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => 'false',
			)
		);

		$this->widget->add_control(
			self::HAS_SUBMIT_BUTTON_CONTROL_KEY,
			array(
				'label' => __( 'Has submit button', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => 'false',
			)
		);

		$this->widget->add_control(
			self::HAS_BLOCKS_CONTROL_KEY,
			array(
				'label' => __( 'Has blocks', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => 'false',
			)
		);

		$this->widget->add_control(
			self::DISMISS_ADDING_SEARCH_RESULTS_TO_PAGE,
			array(
				'label' => __( 'Dismiss adding search results to page', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => 'false',
			)
		);

		$this->widget->end_controls_section();
	}

	/**
	 * Registers the controls for the basic View settings overriding section of the Toolset View Elementor widget.
	 */
	private function register_override_basic_settings_section() {
		$this->widget->start_controls_section(
			'override_basic_settings_section',
			array(
				'label' => __( 'Override View basic settings', 'wpv-views' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'view!' => '0',
					'form_display!' => 'form',
					self::HAS_BLOCKS_CONTROL_KEY => 'false'
				),
			)
		);

		$this->widget->add_control(
			'limit',
			array(
				'label' => __( 'Limit', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'description' => __( 'Get only some results. 0 means no limit.', 'wpv-views' ),
				'size_units' => array( 'px' ), // This widget control is meant for sizes. We are using it here by ignoring the unit.
				'range' => array(
					'value' => array(
						'min' => 0,
						'max' => 999,
					),
				),
				'default' => array(
					'unit' => 'px', // This widget control is meant for sizes. We are using it here by ignoring the unit.
					'size' => 0,
				),
			)
		);

		$this->widget->add_control(
			'offset',
			array(
				'label' => __( 'Offset', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'description' => __( 'Skip some results. 0 means skip nothing.', 'wpv-views' ),
				'size_units' => array( 'px' ), // This widget control is meant for sizes. We are using it here by ignoring the unit.
				'range' => array(
					'value' => array(
						'min' => 0,
						'max' => 999,
					),
				),
				'default' => array(
					'unit' => 'px', // This widget control is meant for sizes. We are using it here by ignoring the unit.
					'size' => 0,
				),
			)
		);

		$this->widget->add_control(
			'orderby',
			array(
				'label' => __( 'Order by', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'ID, date, author, title, post_type or field-slug', 'wpv-views' ),
				'description' => __( 'Change how the results will be ordered.', 'wpv-views' ) . ' ' . __( 'You can sort by a custom field simply using the value field-xxx where xxx is the custom field slug.', 'wpv-views' ),
			)
		);

		$this->widget->add_control(
			'order',
			array(
				'label' => __( 'Order', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'default' => __( 'Default setting', 'wpv-views' ),
					'asc' => __( 'Ascending', 'wpv-views' ),
					'desc' => __( 'Descending', 'wpv-views' ),
				),
				'default' => 'default',
				'description' => __( 'Change the order of the results.', 'wpv-views' ),
			)
		);

		$this->widget->end_controls_section();
	}

	/**
	 * Registers the controls for the View secondary sorting settings overriding section of the Toolset View Elementor widget.
	 */
	private function register_secondary_sorting_settings_section() {
		$this->widget->start_controls_section(
			'secondary_sorting_settings_section',
			array(
				'label' => __( 'Secondary sorting', 'wpv-views' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'view!' => '0',
					'form_display!' => 'form',
					self::HAS_BLOCKS_CONTROL_KEY => 'false'
				),
			)
		);

		$this->widget->add_control(
			'secondaryOrderby',
			array(
				'label' => __( 'Secondary order by', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'default' => __( 'No secondary sorting', 'wpv-views' ),
					'post_date' => __( 'Post date', 'wpv-views' ),
					'post_title' => __( 'Post title', 'wpv-views' ),
					'ID' => __( 'ID', 'wpv-views' ),
					'post_author' => __( 'Post author', 'wpv-views' ),
					'post_type' => __( 'Post type', 'wpv-views' ),
				),
				'default' => 'default',
				'description' => __( 'Change how the results that share the same value on the orderby setting will be ordered.', 'wpv-views' ),
			)
		);

		$this->widget->add_control(
			'secondaryOrder',
			array(
				'label' => __( 'Order', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'default' => __( 'Default setting', 'wpv-views' ),
					'asc' => __( 'Ascending', 'wpv-views' ),
					'desc' => __( 'Descending', 'wpv-views' ),
				),
				'default' => 'default',
				'description' => __( 'Change the secondary order of the results.', 'wpv-views' ),
			)
		);

		$this->widget->end_controls_section();
	}

	/**
	 * Registers the controls for the section of the View custom search customization of the Toolset View Elementor widget.
	 */
	private function register_custom_search_settings_section() {
		$this->widget->start_controls_section(
			'custom_search_settings_section',
			array(
				'label' => __( 'Custom Search', 'wpv-views' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					self::HAS_CUSTOM_SEARCH_CONTROL_KEY => 'true',
				),
			)
		);

		$this->widget->add_control(
			'form_display',
			array(
				'label' => __( 'What do you want to include here?', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'full' => __( 'Both the search form and results', 'wpv-views' ),
					'form' => __( 'Only the search form', 'wpv-views' ),
					'results' => __( 'Only the search results', 'wpv-views' ),
				),
				'default' => 'full',
				'description' => __( 'The first option will display the full View.', 'wpv-views' ) . ' ' .
								 __( 'The second option will display just the form, you can then select where to display the results.', 'wpv-views' ) . ' ' .
								 __( 'Finally, the third option will display just the results, you need to add the form elsewhere targeting this page.', 'wpv-views' ),
			)
		);

		$this->widget->add_control(
			self::FORM_ONLY_DISPLAY,
			array(
				'label' => __( 'Where do you want to display the results?', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					self::SAME_PAGE => __( 'In other place on this same page', 'wpv-views' ),
					'other_page' => __( 'On another page', 'wpv-views' ),
				),
				'default' => self::SAME_PAGE,
				'condition' => array(
					'form_display' => 'form',
					self::HAS_SUBMIT_BUTTON_CONTROL_KEY => 'true',
				),
			)
		);

		$form_with_results_on_other_page_condition = array(
			'form_display' => 'form',
			self::FORM_ONLY_DISPLAY => 'other_page',
		);

		$form_with_results_on_other_page_that_is_set_and_not_dismissed_condition = array_merge(
			$form_with_results_on_other_page_condition,
			array(
				'other_page!' => array( '', null ),
				self::DISMISS_ADDING_SEARCH_RESULTS_TO_PAGE => 'false',
			)
		);

		if ( $this->is_elementor_pro_active->is_met() ) {
			$widget_settings = array(
				'label' => __( 'Select the page to display the results', 'wpv-views' ),
				'type' => \ElementorPro\Modules\QueryControl\Module::QUERY_CONTROL_ID,
				'default' => '',
				'options' => array(),
				'condition' => $form_with_results_on_other_page_condition,
			);
			// The way to declare the widget settings changed in Elementor Pro 2.6.0
			// so we need to make some version detecting.
			$elementor_pro_version = $this->is_elementor_pro_active->get_version();
			if (
				$elementor_pro_version
				&& version_compare( $elementor_pro_version, '2.6.0', '>=' )
			) {
				$widget_settings['autocomplete'] = array(
					'object' => \ElementorPro\Modules\QueryControl\Module::QUERY_OBJECT_POST,
					'display' => 'minimal',
				);
			} else {
				$widget_settings['filter_type'] = 'post';
			}
			// Register the widget control
			$this->widget->add_control(
				'other_page',
				$widget_settings
			);
		} else {
			$this->widget->add_control(
				'other_page',
				array(
					'label' => __( 'Select the page to display the results', 'wpv-views' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'description' => __( 'Insert the Post/Page ID where the search result will be displayed.', 'wpv-views' ),
					'condition' => $form_with_results_on_other_page_condition,
				)
			);

			$renderer = $this->toolset_renderer;
			$template_repository = \Toolset_Output_Template_Repository::get_instance();
			$upgrade_to_pro_for_select2 = $renderer->render(
				$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_ELEMENTOR_UPGRADE_TO_PRO_FOR_SELECT2' ) ),
				array(
					'message' => __( 'You can have a Select2 control that supports searching among the available posts/pages, instead of writing the post/page ID on your own.', 'wpv-views' )
				),
				false
			);
			$this->widget->add_control(
				'upgrade_to_pro_for_select2',
				array(
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => $upgrade_to_pro_for_select2,
					'condition' => $form_with_results_on_other_page_condition,
				)
			);
		}

		$this->widget->add_control(
			'add_search_results_to_page_btn',
			array(
				'label' => __( 'Add the search results to this page', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::BUTTON,
				'separator' => 'default',
				'button_type' => 'default elementor-button-success',
				'text' => __( 'Add results', 'wpv-views' ),
				'event' => 'toolset:pageBuilderWidgets:elementor:editor:addSearchResultsToPage',
				'condition' => $form_with_results_on_other_page_that_is_set_and_not_dismissed_condition,
			)
		);

		$this->widget->add_control(
			'add_search_results_to_page_dismiss_btn',
			array(
				'label' => __( 'Not now?', 'wpv-views' ),
				'type' => \Elementor\Controls_Manager::BUTTON,
				'separator' => 'default',
				'button_type' => 'default',
				'text' => __( 'Dismiss', 'wpv-views' ),
				'event' => 'toolset:pageBuilderWidgets:elementor:editor:addSearchResultsToPageDismiss',
				'condition' => $form_with_results_on_other_page_that_is_set_and_not_dismissed_condition,
			)
		);

		$this->widget->add_control(
			'no_submit_button_warning',
			array(
				'label' => __( 'Warning', 'wpv-views' ),
				'show_label' => false,
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __( 'The form in this View does not have a submit button, so you can only display the results on this same page.', 'wpv-views' ),
				'content_classes' => 'elementor-widget-toolset-warning',
				'condition' => array(
					'form_display' => 'form',
					self::HAS_SUBMIT_BUTTON_CONTROL_KEY => 'false',
				),
			)
		);

		$this->widget->add_control(
			'only_search_form_warning',
			array(
				'label' => __( 'Warning', 'wpv-views' ),
				'show_label' => false,
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => sprintf(
					         __( 'You are only displaying the %1s in this widget.', 'wpv-views' ) . ' ',
					         sprintf(
						         '<strong>%1s</strong>',
						         __( 'search form', 'wpv-views' )
					         )
				         ) .
				         sprintf(
					         __( 'A custom search should have the %1s and the %2s.', 'wpv-views' ) . ' ',
					         sprintf(
						         '<strong>%1s</strong>',
						         __( 'search form', 'wpv-views' )
					         ),
					         sprintf(
						         '<strong>%1s</strong>',
						         __( 'search results', 'wpv-views' )
					         )
				         ) .
				         sprintf(
					         __( 'To display the %1s you need to:', 'wpv-views' ) . ' ',
					         sprintf(
						         '<strong>%1s</strong>',
						         __( 'search results', 'wpv-views' )
					         )
				         ) .
				         '<ul>' .
				         '<li>' . __( 'Create a different View widget, either in the current page or in another page, depending on what was chosen above and display this View.', 'wpv-views' ) . '</li>' .
				         '<li>' . sprintf(
					         __( 'Choose to display the %1s.', 'wpv-views' ) . ' ',
					         sprintf(
						         '<strong>%1s</strong>',
						         __( 'search results', 'wpv-views' )
					         )
				         ) . '</li>' .
				         '</ul>',
				'content_classes' => 'elementor-widget-toolset-warning',
				'condition' => array(
					'form_display' => 'form',
				),
			)
		);

		$this->widget->add_control(
			'only_search_results_warning',
			array(
				'label' => __( 'Warning', 'wpv-views' ),
				'show_label' => false,
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => sprintf(
							 __( 'You are only displaying the %1s in this widget.', 'wpv-views' ) . ' ',
							 sprintf(
								 '<strong>%1s</strong>',
								 __( 'search results', 'wpv-views' )
							 )
						 ) .
						 sprintf(
							 __( 'A custom search should have the %1s and the %2s.', 'wpv-views' ) . ' ',
							 sprintf(
								 '<strong>%1s</strong>',
								 __( 'search results', 'wpv-views' )
							 ),
							 sprintf(
								 '<strong>%1s</strong>',
								 __( 'search form', 'wpv-views' )
							 )
						 ) .
						 sprintf(
							 __( 'To display the %1s you need to:', 'wpv-views' ) . ' ',
							 sprintf(
								 '<strong>%1s</strong>',
								 __( 'search form', 'wpv-views' )
							 )
						 ) .
						 '<ul>' .
						 '<li>' . __( 'Create a different View widget and display this View.', 'wpv-views' ) . '</li>' .
						 '<li>' . sprintf(
							 __( 'Choose to display the %1s.', 'wpv-views' ) . ' ',
							 sprintf(
								 '<strong>%1s</strong>',
								 __( 'search form', 'wpv-views' )
							 )
						 ) . '</li>' .
						 '</ul>',
				'content_classes' => 'elementor-widget-toolset-warning',
				'condition' => array(
					'form_display' => 'results',
				),
			)
		);

		$this->widget->end_controls_section();
	}

	/*
	 * Not used yet...
	 */
	private function register_query_filters_settings_section() {
		$this->widget->start_controls_section(
			'query_filters_settings_section',
			array(
				'label' => __( 'Query filters', 'wpv-views' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->widget->end_controls_section();
	}

	/**
	 * Returns the options for the View selection control of the Toolset View Elementor widget. Basically it forms the
	 * list of the available Views accordingly to shape the options of the control.
	 *
	 * @return array
	 *
	 */
	public function create_view_select_control_options() {
		$view_select_control_options = array();

		$published_views = apply_filters( 'wpv_get_available_views', array() );

		$available_view_types = array(
			'posts',
			'taxonomy',
			'users',
		);

		foreach ( $available_view_types as $view_type ) {
			if (
				isset(  $published_views[ $view_type ] ) &&
				count( $published_views[ $view_type ] ) > 0
			) {
				$group = array(
					'label' => __( ucfirst( $view_type ), 'wpv-views' ),
					'options' => array(),
				);

				foreach ( $published_views[ $view_type ] as $view ) {
					$group['options'][ $view->ID ] = $view->post_title;
				}

				$view_select_control_options[ $view_type ] = $group;
			}
		}

		if ( count( $view_select_control_options ) > 0 ) {
			array_unshift( $view_select_control_options, __( 'Select a View', 'wpv-views' ) );
		} else {
			$view_select_control_options[] = __( 'Create a View first', 'wpv-views' );
		}

		return $view_select_control_options;
	}
}
