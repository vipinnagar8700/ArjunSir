<?php

use OTGS\Toolset\Common\Wordpress\Version;
use OTGS\Toolset\Common\Wordpress\WpSafeRedirect;

/**
 * Handles rendering of the form content on the Edit Term Fields Group page.
 *
 * Based on legacy code, it is basically just a modified version of Types_Admin_Edit_Custom_Fields_Group.
 * I still struggle to understand what it does exactly - consider it a temporary solution.
 *
 * @since 1.9
 */
final class WPCF_Page_Edit_Termmeta_Form extends Types_Admin_Edit_Fields {

	private $valid_meta_boxes_regexps = array(
			'/^wpcf.*/',
			'/^Types.*/',
			'/add_meta_boxes$/',
	);

	/** @var null|Toolset_Field_Group_Term Currently edited field group. */
	private $field_group = null;


	public function __construct() {
		parent::__construct();

		$this->get_id = 'group_id';
		$this->type = Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION;

		add_action('wp_ajax_wpcf_ajax_filter', array($this, 'ajax_filter_dialog'));
	}


	public function init_admin()
	{
		$this->post_type = Toolset_Field_Group_Term::POST_TYPE;

		$this->init_hooks();

		$this->boxes = array(
			'submitdiv' => array(
				'callback' => array($this, 'box_submitdiv'),
				'title' => __('Save', 'wpcf'),
				'default' => 'side',
				'priority' => 'high',
			),
			/*
			'types_where' => array(
				'callback' => array($this, 'box_where'),
				'title' => __('Where to include this Field Group', 'wpcf'),
				'default' => 'side',
			),
			*/
		);
		$this->boxes = apply_filters('wpcf_meta_box_order_defaults', $this->boxes, $this->post_type);
		$this->boxes = apply_filters('wpcf_meta_box_custom_field', $this->boxes, $this->post_type);

		// This should have been defined as a dependency somewhere.
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style('wp-jquery-ui-dialog');

		// Toolset GUI Base dependencies
		Toolset_Common_Bootstrap::get_instance()->register_gui_base();
		Toolset_Gui_Base::get_instance()->init();
		wp_enqueue_style( Toolset_Gui_Base::STYLE_GUI_BASE );
		wp_enqueue_script( Toolset_Gui_Base::SCRIPT_GUI_JQUERY_COLLAPSIBLE );
	}


	/**
	 * Get the purpose of the page that is being displayed, depending on provided data and user capabilities.
	 *
	 * @return string 'add'|'edit'|'view'. Note that 'edit' is also returned when the new group is about to be created,
	 * but it doesn't exist yet (has no ID).
	 */
	public function get_page_purpose() {

		$role_type = 'term-field';
		$group_id = (int) toolset_getget( 'group_id' );
		$is_group_specified = ( 0 !=  $group_id );

		if( $is_group_specified ) {
			if( WPCF_Roles::user_can_edit( $role_type, array( 'id' => $group_id ) ) ) {
				$purpose = 'edit';
			} else {
				$purpose = 'view';
			}
		} else {
			if( $this->is_there_something_to_save() ) {
				if( WPCF_Roles::user_can_create( $role_type ) ) {
					// We're creating a group now, the page will be used for editing it.
					$purpose = 'edit';
				} else {
					$purpose = 'view';
				}
			} else if( WPCF_Roles::user_can_create( $role_type ) ) {
				$purpose = 'add';
			} else {
				$purpose = 'view'; // Invalid state
			}
		}

		return $purpose;
	}


	/**
	 * Obtain ID of current field group by any means necessary.
	 *
	 * Tries to grab the ID from (a) cache, (b) _POST argument during AJAX call, (c) generally used _REQUEST argument with ID.
	 *
	 * @return int Current field group ID or zero if not found.
	 */
	private function get_field_group_id() {
		if( null != $this->field_group ) {
			return $this->field_group->get_id();
		} elseif( toolset_getpost( 'action' ) == 'wpcf_ajax_filter' ) {
			return (int) toolset_getpost( 'id' );
		} elseif( isset( $_REQUEST[ $this->get_id ] ) ) {
			return (int) $_REQUEST[ $this->get_id ];
		} else {
			return 0;
		}
	}


	private function load_field_group( $field_group_id ) {
		return Toolset_Field_Group_Term_Factory::load( $field_group_id );
	}


	private function get_field_group() {
		if( null == $this->field_group ) {
			$this->field_group = $this->load_field_group( $this->get_field_group_id() );
		}
		return $this->field_group;
	}


	/**
	 * Initialize and render the form.
	 *
	 * Determine if existing field group is being edited or if we're creating a new one.
	 * If we're reloading the edit page after clicking Save button, save changes to database.
	 * Generate an array with form field definitions (setup the form).
	 * Fill $this->update with field group data.
	 *
	 * @return array
	 */
	public function form()
	{
		$this->save();

		$this->current_user_can_edit = WPCF_Roles::user_can_create('term-field');

		$field_group_id = (int) toolset_getarr( $_REQUEST, $this->get_id, 0 );

		// If it's update, get data
		if ( 0 != $field_group_id ) {

			$this->update = wpcf_admin_fields_get_group( $field_group_id, Toolset_Field_Group_Term::POST_TYPE );

			if ( null == $this->get_field_group() ) {

				$this->update = false;
				wpcf_admin_message( sprintf( __( "Group with ID %d do not exist", 'wpcf' ), $field_group_id ) );

			} else {
				$this->current_user_can_edit = WPCF_Roles::user_can_edit( 'custom-field', $this->update );

				$this->update['fields'] = wpcf_admin_fields_get_fields_by_group(
					$field_group_id, 'slug', false, true, false,
					Toolset_Field_Group_Term::POST_TYPE,
					Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
				);
			}
		}

		// sanitize id
		$this->update['id'] = $this->get_field_group_id();

		// copy update to ct... dafuq is "ct"?
		$this->ct = $this->update;

		$form = $this->prepare_screen();

		$form['_wpnonce_wpcf'] = array(
			'#type' => 'markup',
			'#markup' => wp_nonce_field('wpcf_form_fields', '_wpnonce_wpcf', true, false),
		);


		// nonce depend on group id
		$nonce_name = $this->get_nonce_action($this->update['id']);
		$form['_wpnonce_'.$this->post_type] = array(
			'#type' => 'markup',
			'#markup' => wp_nonce_field(
				$nonce_name,
				'wpcf_save_group_nonce',
				true,
				false
			),
		);

		$form['form-open'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<div id="post-body-content" class="%s">',
				$this->current_user_can_edit? '':'wpcf-types-read-only'
			),
		);

		$form[ $this->get_id ]  = array(
			'#type' => 'hidden',
			'#name' => 'wpcf[group][id]',
			'#value' => $this->update['id'],
		);

		$view_helper = new \OTGS\Toolset\Types\Field\Group\View\Group( $this->update, get_post( $this->update['id'] ) );
		$field_settings_collapsed_class = $view_helper->are_settings_collapsed()
			? ' toolset-collapsible-closed'
			: '';

		$settings_title = isset( $this->update['name'] )
			? sprintf( __( 'Settings for %s', 'wpcf' ), $this->update['name'] )
			: __( 'Settings for the fields group', 'wpcf' );

		$form['field-group-settings-box-open'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<div class="toolset-field-group-settings toolset-postbox%s"><div data-toolset-collapsible=".toolset-postbox" class="toolset-collapsible-handle" title="%s"><br></div><h3 data-toolset-collapsible=".toolset-postbox" class="toolset-postbox-title">%s</h3><div class="toolset-collapsible-inside">',
				$field_settings_collapsed_class,
				esc_attr__('Click to toggle', 'wpcf'),
				$settings_title
			)
		);

		$form['table-1-open'] = array(
			'#type' => 'markup',
			'#markup' => '<table id="wpcf-types-form-name-table" class="wpcf-types-form-table widefat js-wpcf-slugize-container"><tbody>',
		);
		$table_row = '<tr><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>';
		$form['title'] = array(
			'#title' => sprintf(
				'%s <b>(%s)</b>',
				__( 'Name', 'wpcf' ),
				__( 'required', 'wpcf' )
			),
			'#type' => 'textfield',
			'#name' => 'wpcf[group][name]',
			'#id' => 'wpcf-group-name',
			'#value' => $this->update['id'] ? $this->update['name']:'',
			'#inline' => true,
			'#attributes' => array(
				'class' => 'large-text',
				'placeholder' => __( 'Enter Field Group name', 'wpcf' ),
			),
			'#validate' => array(
				'required' => array(
					'value' => true,
				),
			),
			'#pattern' => $table_row,
		);
		$form['description'] = array(
			'#title' => __( 'Description', 'wpcf' ),
			'#type' => 'textarea',
			'#id' => 'wpcf-group-description',
			'#name' => 'wpcf[group][description]',
			'#value' => $this->update['id'] ? $this->update['description']:'',
			'#attributes' => array(
				'placeholder' =>  __( 'Enter Field Group description', 'wpcf' ),
				'class' => 'hidden js-wpcf-description',
			),
			'#pattern' => $table_row,
			'#after' => sprintf(
				'<a class="js-wpcf-toggle-description hidden" href="#">%s</a>',
				__('Add description', 'wpcf')
			),
			'#inline' => true,
		);

		/**
		 * Where to include these field group
		 */

		$form['table-2-open'] = array(
			'#type'   => 'markup',
			'#markup' => '<tr><td>' . __( 'Appears on', 'wpcf' ) . '</td>',
		);

		$form['table-2-content'] = array(
			'#type'   => 'markup',
			'#markup' => '<td>'.$this->box_where().'</td></tr>',
		);

		$form['table-1-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</tbody></table>',
		);

		$form['field-group-settings-box-close'] = array(
			'#type' => 'markup',
			'#markup' => '</div></div>',
		);

		$form += $this->fields();

		$form['form-close'] = array(
			'#type' => 'markup',
			'#markup' => '</div>',
			'_builtin' => true,
		);

		// setup common setting for forms
		$form = $this->common_form_setup($form);

		if ( $this->current_user_can_edit) {
			return $form;
		}

		return wpcf_admin_common_only_show($form);
	}


	private function get_relevant_taxonomy_slugs() {
		$taxonomy_slugs = apply_filters( 'wpcf_group_form_filter_taxonomy_slugs', get_taxonomies() );
		return array_diff(
			array_unique( toolset_ensarr( $taxonomy_slugs ) ),
			array( 'nav_menu', 'link_category', 'post_format' )
		);
	}


	/**
	 * Render content of a metabox for associating the field group with taxonomies.
	 */
	public function box_where() {

		// Filter taxonomies

		$taxonomy_slugs = $this->get_relevant_taxonomy_slugs();
		$currently_supported_taxonomy_slugs = ( $this->get_field_group_id() != 0 ? $this->field_group->get_associated_taxonomies() : array() );

		$fields_to_clear_class = 'js-wpcf-filter-support-taxonomy';

		$form_tax = array();
		foreach ( $taxonomy_slugs as $taxonomy_slug ) {

			$form_tax[ $taxonomy_slug ] = array(
				'#type' => 'hidden',
				'#name' => sprintf( 'wpcf[group][taxonomies][%s]', esc_attr( $taxonomy_slug ) ),
				'#id' => 'wpcf-form-groups-support-taxonomy-' . $taxonomy_slug,
				'#attributes' => array(
					'class' => $fields_to_clear_class,
					'data-wpcf-label' => Types_Utils::taxonomy_slug_to_label( $taxonomy_slug )
				),
				'#value' => ( in_array( $taxonomy_slug, $currently_supported_taxonomy_slugs ) ) ? $taxonomy_slug : '',
				'#inline' => true
			);
		}

		// Edit Button
		$form_tax['edit-button-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="wpcf-edit-button-container">'
		);

		// generate wrapper and button
		$form_tax += $this->filter_wrap(
			'wpcf-filter-dialog-edit',
			array(
				'data-wpcf-buttons-apply' => esc_attr__( 'Apply', 'wpcf' ),
				'data-wpcf-buttons-cancel' => esc_attr__( 'Cancel', 'wpcf' ),
				'data-wpcf-dialog-title' => esc_attr__( 'Where to use this Field Group', 'wpcf' ),
				'data-wpcf-field-prefix' => esc_attr( 'wpcf-form-groups-support-taxonomy-' ),
				'data-wpcf-field-to-clear-class' => esc_attr( '.' . $fields_to_clear_class ),
				'data-wpcf-id' => esc_attr( $this->update['id'] ),
				'data-wpcf-message-any' => esc_attr__( 'None', 'wpcf' ),
				'data-wpcf-message-loading' => esc_attr__( 'Please wait, Loading…', 'wpcf' ),
			),
			true,
			false
		);

		$form = array();

		// container for better styling
		$form['where-to-include-inner-container'] = array(
			'#type'   => 'markup',
			'#markup' => '<div class="wpcf-where-to-include-inner"><div class="wpcf-conditions-container">'
		);

		// Now starting form
		$form['supports-table-open'] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<p class="wpcf-fields-group-conditions-description js-wpcf-fields-group-conditions-none">%s</p>',
				__( 'By default <b>this group of fields</b> will appear when editing <b>all terms from all Taxonomies.</b><br /><br />Select specific Taxonomies to use these fields with.', 'wpcf' )
			),
		);

		// Description: Terms set
		$form['supports-msg-conditions-taxonomies'] = array(
			'#type'   => 'markup',
			'#markup' => sprintf(
				'<p class="wpcf-fields-group-conditions-description js-wpcf-fields-group-conditions-condition js-wpcf-fields-group-conditions-taxonomies">%s <span></span></p>',
				__( 'Taxonomies:', 'wpcf' )
			),
		);

		$form['conditions-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div>'
		);


		// Terms
		$form = $form + $form_tax;

		$form['where-to-include-inner-container-close'] = array(
			'#type'   => 'markup',
			'#markup' => '</div></div>' // also close for 'edit-button-container'
		);

		// setup common setting for forms
		$form = $this->common_form_setup( $form );

		// render form
		$form = wpcf_form( __FUNCTION__, $form );
		return $form->renderForm();
	}


	private function ajax_filter_default_value($value, $currently_supported = array(), $type = false) {
		if( $type && isset( $_REQUEST['all_fields'] ) && is_array( $_REQUEST['all_fields'] ) ) {
			switch( $type ) {
				case 'taxonomies-for-termmeta':
					$selected_taxonomies = toolset_ensarr( toolset_getnest( $_REQUEST, array( 'all_fields', 'wpcf', 'group', 'taxonomies' ) ) );
					if( in_array( $value, array_keys( $selected_taxonomies ) ) && true == $selected_taxonomies[ $value ] ) {
						return true;
					}
					break;
			}
			// not selected
			return false;
		}

		if( isset( $_REQUEST['current'] ) ) {
			if( is_array( $_REQUEST['current'] ) && in_array( $value, $_REQUEST['current'] ) ) {
				return true;
			}
		} else if( $currently_supported && ! empty( $currently_supported ) && in_array( $value, $currently_supported ) ) {
			return true;
		}

		return false;
	}


	protected function is_there_something_to_save() {
		$wpcf_data = toolset_getpost( 'wpcf', null );
		return ( null != $wpcf_data );
	}


	/**
	 * Save field group data from $_POST to database when the form is submitted.
	 */
	protected function save() {

		if ( ! $this->is_there_something_to_save() ) {
			return;
		}

		$wpcf_data = toolset_getpost( 'wpcf', null );

		// check incoming $_POST data
		$group_id = toolset_getnest( $_POST, array( 'wpcf', 'group', 'id' ), null );
		if ( null === $group_id ) { // probably can be 0, which is valid
			$this->verification_failed_and_die( 1 );
		}

		// nonce verification
		$nonce_name = $this->get_nonce_action( $group_id );
		$nonce = toolset_getpost( 'wpcf_save_group_nonce' );
		if ( ! wp_verify_nonce( $nonce, $nonce_name ) ) {
			$this->verification_failed_and_die( 2 );
		}

		// save group data to the database (sanitizing there)
		$group_id = wpcf_admin_fields_save_group( toolset_getarr( $wpcf_data, 'group', array() ), Toolset_Field_Group_Term::POST_TYPE, 'term' );
		$field_group = $this->load_field_group( $group_id );

		if ( null == $field_group ) {
			return;
		}

		// Why are we doing this?!
		$_REQUEST[ $this->get_id ] = $group_id;

		// save taxonomies; sanitized on a lower level before saving to the database
		$taxonomies_post = toolset_getnest( $wpcf_data, array( 'group', 'taxonomies' ), array() );
		$field_group->update_associated_taxonomies( $taxonomies_post );

		$this->save_filter_fields( $group_id, toolset_getarr( $wpcf_data, 'fields', array() ) );

		do_action( 'types_fields_group_saved', $group_id );
		do_action( 'types_fields_group_term_saved', $group_id );

		// Redirect to edit page so we stay on it even if user reloads it
		// and to present admin notices
		$redirect = new WpSafeRedirect( new Version() );
		if ( $redirect->wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array( 'page' => WPCF_Page_Edit_Termmeta::PAGE_NAME, $this->get_id => $group_id ),
					admin_url( 'admin.php' )
				)
			)
		) ) {
			exit;
		}
	}


	private function save_filter_fields( $group_id, $fields_data )
	{

		if ( empty( $fields_data ) ) {
			delete_post_meta( $group_id, '_wp_types_group_fields' );
			return;
		}

		$fields = array();

		// First check all fields
		foreach ( $fields_data as $field_key => $field ) {

			$field = wpcf_sanitize_field($field);
			$field = apply_filters( 'wpcf_field_pre_save', $field );

			if ( !empty( $field['is_new'] ) ) {

				// Check name and slug
				if ( wpcf_types_cf_under_control(
					'check_exists',
					sanitize_title( $field['name'] ),
					Toolset_Field_Group_Term::POST_TYPE,
					Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
				) ) {
					$this->triggerError();
					wpcf_admin_message( sprintf( __( 'Field with name "%s" already exists', 'wpcf' ), $field['name'] ), 'error' );
					return;
				}

				if ( isset( $field['slug'] )
					&& wpcf_types_cf_under_control(
						'check_exists',
						sanitize_title( $field['slug'] ),
						Toolset_Field_Group_Term::POST_TYPE,
						Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
					)
				) {
					$this->triggerError();
					wpcf_admin_message( sprintf( __( 'Field with slug "%s" already exists', 'wpcf' ), $field['slug'] ), 'error' );
					return;
				}
			}

			$field['submit-key'] = sanitize_text_field( $field_key );

			// Field ID and slug are same thing
			$field_slug = wpcf_admin_fields_save_field(
				$field,
				Toolset_Field_Group_Term::POST_TYPE,
				Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
			);


			if ( is_wp_error( $field_slug ) ) {
				$this->triggerError();
				wpcf_admin_message( $field_slug->get_error_message(), 'error' );
				return;
			}


			if ( !empty( $field_slug ) ) {
				$fields[] = $field_slug;
			}


			// WPML
			if ( defined('ICL_SITEPRESS_VERSION') && version_compare ( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
				if ( function_exists( 'wpml_cf_translation_preferences_store' ) ) {
					$real_custom_field_name = wpcf_types_get_meta_prefix(
						wpcf_admin_fields_get_field( $field_slug, false, false, false, Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION )
					) . $field_slug;
					wpml_cf_translation_preferences_store( $field_key, $real_custom_field_name );
				}
			}
		}

		wpcf_admin_fields_save_group_fields(
			$group_id, $fields, false,
			Toolset_Field_Group_Term::POST_TYPE,
			Toolset_Field_Definition_Factory_Term::FIELD_DEFINITIONS_OPTION
		);
	}


	/**
	 * Update the "form" data for the filter dialog.
	 *
	 * @param string $filter Filter name. Only 'taxonomies-for-meta' is supported here.
	 * @param array $form Form data that will be modified.
	 */
	protected function form_add_filter_dialog( $filter, &$form ) {

		switch( $filter ) {
			case 'taxonomies-for-termmeta':
				include_once WPCF_INC_ABSPATH . '/fields.php'; // Oh dear god, why?

				$taxonomy_slugs = $this->get_relevant_taxonomy_slugs();
				ksort( $taxonomy_slugs );

				$field_group = $this->get_field_group();
				// Can be null when creating new field group
				$currently_supported_taxonomy_slugs = ( null == $field_group ) ? array() : $field_group->get_associated_taxonomies();

				// Setup the form
				$form += $this->add_description(
					__( /** @lang text */ 'Select specific Taxonomies that you want to use with this Field Group:', 'wpcf' )
				);

				$form['ul-begin'] = array(
					'#type' => 'markup',
					'#markup' => '<ul>',
				);

				// Add a checkbox for each taxonomy
				foreach ( $taxonomy_slugs as $taxonomy_slug ) {
					$label = Types_Utils::taxonomy_slug_to_label( $taxonomy_slug );
					$form[ $taxonomy_slug ] = array(
						'#name' => esc_attr( $taxonomy_slug ),
						'#type' => 'checkbox',
						'#value' => 1,
						'#default_value' => $this->ajax_filter_default_value( $taxonomy_slug, $currently_supported_taxonomy_slugs, 'taxonomies-for-termmeta' ),
						'#inline' => true,
						'#before' => '<li>',
						'#after' => '</li>',
						'#title' => $label,
						'#attributes' => array(
							'data-wpcf-value' => esc_attr( $taxonomy_slug ),
							'data-wpcf-name' => $label,
							'data-wpcf-prefix' => 'taxonomy-'
						),
					);
				}

				$form['ul-end'] = array(
					'#type' => 'markup',
					'#markup' => '</ul><br class="clear" />',
				);
				break;
		}

	}


	/**
	 * Get description of tabs that will be displayed on the filter dialog.
	 *
	 * @return array[]
	 */
	protected function get_tabs_for_filter_dialog() {
		$tabs = array(
			'taxonomies-for-termmeta' => array(
				'title' => __( 'Taxonomies', 'wpcf' ),
			)
		);

		return $tabs;

	}

	/**
	 * Filter metaboxes
	 *
	 * It takes the list of metaboxes and use only the permitted ones.
	 *
	 * @since 3.0
	 */
	public function filter_meta_boxes() {
		global $wp_filter;
		foreach ( $wp_filter['add_meta_boxes']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback => $function ) {
				$valid = false;
				foreach ( $this->valid_meta_boxes_regexps as $regexp ) {
					$valid |= preg_match( $regexp, $callback );
				}
				if ( ! $valid ) {
					unset( $wp_filter['add_meta_boxes']->callbacks[ $priority ][ $callback ] );
				}
			}
		}
	}
}
