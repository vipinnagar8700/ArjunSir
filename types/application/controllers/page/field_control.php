<?php

use OTGS\Toolset\Twig\Environment;

/**
 * "Field Control" page controller.
 *
 * @since 2.0
 */
final class Types_Page_Field_Control extends Types_Page_Abstract {


	/** Name of the URL parameter for the field domain. */
	const PARAM_DOMAIN = 'domain';


	// Screen options...
	const SCREEN_OPTION_PER_PAGE_NAME = 'toolset_fields_per_page';

	const SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE = 20;


	/** @var string|null Current field domain. Will be populated during self::prepare(). Never access directly. */
	private $current_domain;

	/** @var array */
	private $field_definitions;

	/** @var string[] */
	private $_fields_without_sanitized_slug;

	private $twig;

	private static $instance;


	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private function __construct() {
	}


	private function __clone() {
	}


	/**
	 * Generate full URL to the Field Control page for given field domain.
	 *
	 * @param string $domain
	 *
	 * @return null|string The URL or null if the domain was invalid.
	 * @since 2.0
	 */
	public static function get_page_url( $domain ) {

		if ( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
			return null;
		}

		return esc_url_raw(
			add_query_arg(
				array(
					'page' => Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL,
					'domain' => $domain,
				),
				admin_url( 'admin.php' )
			)
		);
	}


	/**
	 * @inheritdoc
	 *
	 * Validate field domain, which must be part of the GET request.
	 *
	 * @throws InvalidArgumentException when the domain is invalid.
	 * @since 2.0
	 */
	public function prepare() {

		parent::prepare();

		$current_domain = $this->get_current_domain();

		// Fail on invalid domain.
		if ( null === $current_domain ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Invalid field domain provided. Expected one of those values: %s', 'wpcf' ),
					implode( ', ', Toolset_Element_Domain::all() )
				)
			);
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );

		add_action( 'current_screen', array( $this, 'prepare_dialogs' ) );

	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_title() {

		switch ( $this->get_current_domain() ) {
			case Toolset_Element_Domain::POSTS:
				return __( 'Post Field Control', 'wpcf' );
			case Toolset_Element_Domain::USERS:
				return __( 'User Field Control', 'wpcf' );
			case Toolset_Element_Domain::TERMS:
				return __( 'Term Field Control', 'wpcf' );
			default:
				// will never happen
				return '';
		}
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_render_callback() {
		return array( $this, 'render_page' );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_page_name() {
		return Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_required_capability() {
		return 'manage_options'; // todo better role/cap handling
	}


	/**
	 * @inheritdoc
	 * @return callable
	 */
	public function get_load_callback() {
		return array( $this, 'add_screen_options' );
	}


	/**
	 * Current field domain.
	 *
	 * @return string|null
	 * @since 2.0
	 */
	private function get_current_domain() {
		if ( null === $this->current_domain ) {
			$this->current_domain = toolset_getget( self::PARAM_DOMAIN, null, Toolset_Element_Domain::all() );
		}

		return $this->current_domain;
	}


	/**
	 * Enqueue all assets needed by the page.
	 *
	 * (Notice the dependencies on Toolset GUI base assets.)
	 *
	 * @since 2.0
	 */
	public function on_admin_enqueue_scripts() {

		$main_handle = 'types-page-field-control-main';

		// Enqueuing with the wp-admin dependency because we need to override something !important.
		$asset_manager = Types_Asset_Manager::get_instance();
		$asset_manager->enqueue_styles(
			array(
				'wp-admin',
				'common',
				'font-awesome',
				'wpcf-css-embedded',
				'wp-jquery-ui-dialog',
				'toolset-notifications-css',
			)
		);

		wp_enqueue_style(
			$main_handle,
			TYPES_RELPATH . '/public/page/field_control/style.css',
			array( Toolset_Gui_Base::STYLE_GUI_BASE )
		);

		wp_enqueue_script(
			$main_handle,
			TYPES_RELPATH . '/public/page/field_control/main.js',
			array(
				'jquery',
				'backbone',
				'underscore',
				Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
				Types_Asset_Manager::SCRIPT_HEADJS,
				Types_Asset_Manager::SCRIPT_ADJUST_MENU_LINK,
				Types_Asset_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_UTILS,
			),
			TYPES_VERSION
		);

	}


	/** @noinspection PhpDocMissingThrowsInspection */
	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Environment
	 * @since 2.2
	 */
	private function get_twig() {
		if ( null === $this->twig ) {
			$gui_base = Toolset_Gui_Base::get_instance();

			/** @noinspection PhpUnhandledExceptionInspection */
			$this->twig = $gui_base->create_twig_environment(
				array( 'field_control' => TYPES_ABSPATH . '/application/views/page/field_control' )
			);
		}

		return $this->twig;
	}


	/**
	 * @since 2.0
	 */
	public function render_page() {
		$context = $this->build_page_context();

		$twig = $this->get_twig();

		/** @noinspection PhpUnhandledExceptionInspection */
		echo $twig->render( '@field_control/main.twig', $context );
	}


	/**
	 * Build the context for main poge template.
	 *
	 * That includes variables for the template as well as data to be passed to JavaScript.
	 *
	 * @return array Page context. See the main page template for details.
	 * @since 2.0
	 */
	private function build_page_context() {

		$gui_base = Toolset_Gui_Base::get_instance();

		// Basics for the listing page which we'll merge with specific data later on.
		$base_context = $gui_base->get_twig_context_base( Toolset_Gui_Base::TEMPLATE_LISTING, $this->build_js_data() );

		$specific_context = array(
			'strings' => $this->build_strings_for_twig(),
			'fieldsWithoutSanitizedSlug' => $this->get_fields_without_sanitized_slug(),
		);

		return toolset_array_merge_recursive_distinct( $base_context, $specific_context );
	}


	/**
	 * Fields without sanitized slug
	 *
	 * @since 3.1
	 */
	private function get_fields_without_sanitized_slug() {
		if ( $this->_fields_without_sanitized_slug === null ) {
			$this->build_field_definitions();
		}

		return $this->_fields_without_sanitized_slug;
	}


	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_js_data() {

		$ajax_controller = Types_Ajax::get_instance();
		$field_type_definition_factory = Toolset_Field_Type_Definition_Factory::get_instance();
		$field_type_converter = Types_Field_Type_Converter::get_instance();

		$field_action_name = $ajax_controller->get_action_js_name( Types_Ajax::CALLBACK_FIELD_CONTROL_ACTION );

		return array(
			'jsIncludePath' => TYPES_RELPATH . '/public/page/field_control',
			'typesVersion' => TYPES_VERSION,
			'fieldDefinitions' => $this->build_field_definitions(),
			'fieldTypeDefinitions' => $field_type_definition_factory->get_field_type_definitions(),
			'templates' => $this->build_templates(),
			'strings' => $this->build_strings_for_js(),
			'ajaxInfo' => array(
				'fieldAction' => array(
					'name' => $field_action_name,
					'nonce' => wp_create_nonce( $field_action_name ),
				),
			),
			'currentDomain' => $this->get_current_domain(),
			'groups' => $this->build_group_data(),
			'typeConversionMatrix' => $field_type_converter->get_conversion_matrix(),
			'itemsPerPage' => $this->get_items_per_page_setting(),
		);

	}


	/**
	 * Prepare field definition data, depending on current field domain, for passing to JavaScript.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_field_definitions() {
		if ( $this->field_definitions !== null || $this->_fields_without_sanitized_slug !== null ) {
			// already build
			return $this->field_definitions;
		}

		$query_args = array(
			'filter' => 'all',
			'orderby' => 'name',
			'order' => 'asc',
		);

		$definition_factory = Toolset_Field_Definition_Factory_Post::get_factory_by_domain( $this->get_current_domain() );
		$definitions = $definition_factory->query_definitions( $query_args );

		$definition_data = array();
		$fields_without_sanitized_slug = array();

		$blacklisted_fields = $this->get_blacklisted_fields();

		foreach ( $definitions as $definition ) {
			if ( in_array( $definition->get_slug(), $blacklisted_fields, true ) ) {
				// do not included blacklisted fields
				continue;
			}

			if ( sanitize_key( $definition->get_slug() ) !== $definition->get_slug() ) {
				// do not include fields which does not match sanitize_key rules
				$fields_without_sanitized_slug[] = $definition->get_slug();
				continue;
			}

			$definition_data[] = $definition->to_json();
		}

		$this->field_definitions = $definition_data;
		$this->_fields_without_sanitized_slug = $fields_without_sanitized_slug;

		return $this->field_definitions;
	}


	/**
	 * Build array of templates that will be passed to JavaScript.
	 *
	 * If the template file does not exist or is not readable, it will be silently omitted.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_templates() {

		$template_sources = array(
			'messageDefinitionList' => 'page/field_control/message_definition_list.html',
			'messageMultiple' => 'misc/message_multiple.html',
		);

		$templates = array();
		foreach ( $template_sources as $template_name => $template_relpath ) {

			$template_path = TYPES_ABSPATH . '/application/views/' . $template_relpath;

			if ( file_exists( $template_path ) ) {
				$templates[ $template_name ] = file_get_contents( $template_path );
			}
		}

		return $templates;
	}


	private function build_strings_for_twig() {

		return array(
			'column' => array(
				'name' => __( 'Field name', 'wpcf' ),
				'slug' => __( 'Slug', 'wpcf' ),
				'metaKey' => __( 'Meta key', 'wpcf' ),
				'groups' => __( 'Field groups', 'wpcf' ),
				'type' => __( 'Type' ),
			),
			'rowAction' => array(
				'changeAssignment' => __( 'Change assignment', 'wpcf' ),
				'changeType' => __( 'Change type', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
			'misc' => array(
				'noItemsFound' => __( 'No field definitions found.', 'wpcf' ),
				'pageTitle' => $this->get_title(),
				'thisFieldIsRepeating' => __( 'This is a repeating field.', 'wpcf' ),
				'fieldsNotSupportedBecauseOfNotSanitizedSlug' => __( 'Toolset Types only allow fields with names containing lowercase alphanumeric characters, dashes and underscores. The following third party fields are not supported:', 'wpcf' ),
			),
			'bulkAction' => array(
				'delete' => __( 'Delete', 'wpcf' ),
				'manageWithTypes' => __( 'Manage with Types', 'wpcf' ),
				'stopManagingWithTypes' => __( 'Stop managing with Types', 'wpcf' ),
			),
		);
	}


	/**
	 * Prepare an array of strings used in JavaScript.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_strings_for_js() {
		return array(
			'misc' => array(
				'notManagedByTypes' => __( 'Not managed by Types', 'wpcf' ),
				'undefinedAjaxError' => __( 'The action was not successful, an unknown error has happened.', 'wpcf' ),
				'genericSuccess' => __( 'The action was completed successfully.', 'wpcf' ),
				'fieldsAlreadyManaged' => __( 'Some of the fields you selected are already managed by Types.', 'wpcf' ),
				'fieldsAlreadyUnmanaged' => __( 'Some of the fields you selected are already not managed by Types.', 'wpcf' ),
				'unselectAndRetry' => __( 'Please unselect them and try again.', 'wpcf' ),
				'changeAssignmentToGroups' => __( 'Change assignment to field groups for the field', 'wpcf' ),
				'deleteField' => __( 'Delete field', 'wpcf' ),
				'deleteFields' => __( 'Delete multiple fields', 'wpcf' ),
				'cannotDeleteUnmanagedFields' => __( 'Some fields cannot be deleted because they are not managed by Types.', 'wpcf' ),
				'changeFieldType' => __( 'Change field type for the field', 'wpcf' ),
				'startManagingFieldsWithTypes' => __( 'Start managing fields with Types', 'wpcf' ),
				'stopManagingFieldsWithTypes' => __( 'Stop managing fields with Types', 'wpcf' ),
			),
			'rowAction' => array(
				'manageByTypes' => array(
					'yes' => __( 'Manage with Types', 'wpcf' ),
					'no' => __( 'Stop managing with Types', 'wpcf' ),
				),
				'changeCardinality' => array(
					'makeRepetitive' => __( 'Turn into repetitive', 'wpcf' ),
					'makeSingle' => __( 'Turn into single-value', 'wpcf' ),
				),
			),
			'bulkAction' => array(
				'select' => __( 'Bulk action', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
				'manageWithTypes' => __( 'Manage with Types', 'wpcf' ),
				'stopManagingWithTypes' => __( 'Stop managing with Types', 'wpcf' ),
			),
			'button' => array(
				'apply' => __( 'Apply', 'wpcf' ),
				'cancel' => __( 'Cancel', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
		);
	}


	/**
	 * Build an array describing existing field groups within the domain.
	 *
	 * @return array
	 * @since 2.0
	 */
	private function build_group_data() {
		$factory = Toolset_Field_Group_Factory::get_factory_by_domain( $this->get_current_domain() );

		// for post field groups we also need to fetch RFGs
		$group_qry_args = $this->get_current_domain() === Toolset_Element_Domain::POSTS
			? array(
				'purpose' => '*',
				'post_status' => 'any',
			)
			: array();

		$groups = $factory->query_groups( $group_qry_args );

		$group_data = array();
		foreach ( $groups as $group ) {
			$group_slug = $group->get_slug();
			$group_data[ $group_slug ] = array(
				'slug' => $group_slug,
				'displayName' => stripslashes( $group->get_display_name() ),
			);
		}

		return $group_data;
	}


	/**
	 * Display screen options on the page.
	 *
	 * @since 2.0
	 */
	public function add_screen_options() {

		$args = array(
			'label' => __( 'Number of displayed fields', 'wpcf' ),
			'default' => self::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE,
			'option' => self::SCREEN_OPTION_PER_PAGE_NAME,
		);
		add_screen_option( 'per_page', $args );
	}


	/**
	 * Update the "per page" screen option.
	 *
	 * @param mixed $original_value
	 * @param string $option
	 * @param mixed $option_value
	 *
	 * @return mixed
	 * @since 2.0
	 */
	public function set_screen_option( $original_value, $option, $option_value ) {
		if ( self::SCREEN_OPTION_PER_PAGE_NAME === $option ) {
			return $option_value;
		}

		// not our option, return the original value (which is by default "false" = no saving)
		return $original_value;
	}


	/**
	 * Value of the "items per page" setting for current page and current user.
	 *
	 * @return int
	 * @since 2.0
	 */
	private function get_items_per_page_setting() {
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $option, true );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		return (int) $per_page;
	}


	/**
	 * Prepare assets for all dialogs that are going to be used on the page.
	 *
	 * @since 2.0
	 */
	public function prepare_dialogs() {

		new Toolset_Twig_Dialog_Box(
			'types-change-assignment-dialog',
			$this->get_twig(),
			array(
				'groups' => $this->build_group_data(),
				'strings' => array(
					'noFieldGroups' => __( 'No field groups exist yet. You have to create one first.', 'wpcf' ),
				),
			),
			'@field_control/change_assignment_dialog.twig'
		);

		new Toolset_Twig_Dialog_Box(
			'types-delete-field-dialog',
			$this->get_twig(),
			array(
				'strings' => array(
					'deletingWillRemoveDefinitionAndData' => __( 'Deleting fields will remove them from all groups and delete the field data from the database as well.', 'wpcf' ),
					'cannotBeUndone' => __( 'This cannot be undone!', 'wpcf' ),
					'doYouReallyWantDelete' => __( 'Do you really want to delete?', 'wpcf' ),
				),
			),
			'@field_control/delete_dialog.twig'
		);

		$type_definition_factory = Toolset_Field_Type_Definition_Factory::get_instance();

		new Toolset_Twig_Dialog_Box(
			'types-change-field-type-dialog',
			$this->get_twig(),
			array(
				'fieldTypeDefinitions' => $type_definition_factory->get_field_type_definitions(),
				'strings' => array(
					'aboutFieldTypeChanging' => __( 'Select a new type for this field.', 'wpcf' ),
					'someTypesAreDisabled' => __( 'Note: Some of the field types are disabled for conversion because they\'re using a significantly different data format, which is not compatible with the current field type.', 'wpcf' ),
					'potentiallyRiskyOperation' => __( 'Changing field type is a potentially risky operation. Make sure you know what you are doing.', 'wpcf' ),
					'singleOrRepeatingField' => __( 'Single or repeating field', 'wpcf' ),
					'repetitiveField' => __( 'Allow multiple instances of this field', 'wpcf' ),
					'singleField' => __( 'This field can have only one value', 'wpcf' ),
					'targetSupportsSingleOnly' => __( 'Selected field type supports only single fields.', 'wpcf' ),
					'repetitiveToSingleWarning' => __( 'Changing from repeating to single field <strong>will cause partial data loss</strong> if there already are fields with multiple values stored in the database. In such case, only one of those value will be saved on update and some inconsistencies appear when displaying values of this field.', 'wpcf' ),
				),
			),
			'@field_control/change_type_dialog.twig'
		);

		new Toolset_Twig_Dialog_Box(
			'types-bulk-change-management-status-dialog',
			$this->get_twig(),
			array(
				'strings' => array(
					'youAreAboutToManageFields' => __( 'You are about to start managing these fields with Types:', 'wpcf' ),
					'youAreAboutToStopManagingFields' => __( 'You are about to stop managing these fields with Types:', 'wpcf' ),
					'confirmContinue' => __( 'Do you want to continue?', 'wpcf' ),
				),
			),
			'@field_control/bulk_change_management_status_dialog.twig'
		);
	}


	/**
	 * Get help configuration for Types_Asset_Help_Tab_Loader.
	 *
	 * @return array
	 * @since 2.0
	 */
	public function get_help_config() {
		return array(
			'title' => $this->get_title(),
			'template' => '@help/basic.twig',
			'context' => array(
				'introductory_paragraphs' => array(
					__( 'Types plugin provides you with a powerful way to control the Post/User/Term fields.', 'wpcf' ),
					__( 'On this page you can see the list of all the custom fields present in your site. Some of them were created by Types and some were not.', 'wpcf' ),
					__( 'When changing changing properties of existing fields, caution is strongly advised because wrong usage can cause issues with themes, plugins and functionality connected to the applied changes.', 'wpcf' ),
				),
				'your_options' => __( 'You have the following options:', 'wpcf' ),
				'options' => array(
					array(
						'name' => __( 'Change assignment', 'wpcf' ),
						'explanation' => __( 'Change a group that the field belongs to.', 'wpcf' ),
					),
					array(
						'name' => __( 'Change type', 'wpcf' ),
						'explanation' => __( 'Change the type of the field and change whether field can have a single value or multiple values (repetitive field).', 'wpcf' ),
					),
					array(
						'name' => __( 'Manage with Types', 'wpcf' ),
						'explanation' => __( 'Select whether the field is under the control of Types plugin.', 'wpcf' ),
					),
					array(
						'name' => __( 'Delete', 'wpcf' ),
						'explanation' =>
							__( 'Click to delete a field and all of its values from the database.', 'wpcf' )
							. ' <strong>' . __( 'Warning: This cannot be undone.', 'wpcf' ) . '</strong>',
					),
				),
				'ending_paragraphs' => array(
					__( 'Note: An asterisk (*) beside a field type marks a repeating field.', 'wpcf' ),
				),
			),
		);
	}


	/**
	 * Returns list of field slugs, which should not be displayed on the field control page
	 *
	 * @return array
	 */
	private function get_blacklisted_fields() {
		return array(
			'toolset-post-sortorder',
			'types_field_group_purpose',
		);
	}
}
