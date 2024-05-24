<?php

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Error\LoaderError;
use OTGS\Toolset\Types\Field\Group\PostGroupViewmodel;
use OTGS\Toolset\Types\Field\Group\TermGroupViewmodel;
use OTGS\Toolset\Types\Field\Group\UserGroupViewmodel;
use OTGS\Toolset\Types\Field\Group\ViewmodelFactory;
use OTGS\Toolset\Types\Field\Group\ViewmodelInterface;

/**
 * "Custom Fields" page controller.
 *
 * Custom Fields groups Post fields, User Fields and Term Fields in one page
 *
 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/types-890
 * @since 2.3
 */
class Types_Page_Custom_Fields extends Types_Page_Persistent {


	/**
	 * Domain parameter
	 *
	 * @since 2.3
	 * @var string Name of the URL parameter for the field domain.
	 */
	const PARAM_DOMAIN = 'domain';


	/**
	 * Screen options 'Per page' name
	 *
	 * @since 2.3
	 * @var string The name of the parameter used for "per page" screen options
	 */
	const SCREEN_OPTION_PER_PAGE_NAME = 'toolset_fields_groups_per_page';


	/**
	 * Screen options 'Per page' default value
	 *
	 * @since 2.3
	 * @var integer Default value of the parameter used for "per page" screen options
	 */
	const SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE = 10;


	/**
	 * Current domain
	 *
	 * @since 2.3
	 * @var string Current field domain. Will be populated during self::prepare(). Never access directly.
	 */
	private $current_domain;


	/**
	 * Twig class
	 *
	 * @since 2.3
	 * @var Environment Twig Enviroment.
	 */
	private $twig;


	/**
	 * @var Toolset_Twig_Dialog_Box_Factory|null
	 *
	 * Note: The class may not be available at the time of __construct().
	 */
	private $dialog_factory;


	/** @var Types_Asset_Manager */
	private $asset_manager;


	/**
	 * Class instance
	 *
	 * @since 2.3
	 * @var Types_Page_Custom_Fields Class instance.
	 */
	private static $instance;


	/**
	 * Types_Page_Custom_Fields constructor
	 *
	 * @param array $args List of arguments.
	 * @param Toolset_Twig_Dialog_Box_Factory|null $dialog_factory Twig dialogs factory.
	 * @param Types_Asset_Manager|null $asset_manager_di
	 *
	 * @since 2.3
	 *
	 */
	public function __construct(
		$args, Toolset_Twig_Dialog_Box_Factory $dialog_factory = null, Types_Asset_Manager $asset_manager_di = null
	) {
		parent::__construct( $args );

		self::$instance = $this;

		$this->dialog_factory = $dialog_factory;
		$this->asset_manager = $asset_manager_di ? : Types_Asset_Manager::get_instance();
	}


	/**
	 * Gets class instance
	 *
	 * @return Types_Page_Custom_Fields
	 * @throws RuntimeException If instance is null, constructor should be call before.
	 * @since 2.3
	 */
	public static function get_existing_instance() {
		if ( null === self::$instance ) {
			throw new RuntimeException( 'Constructor neeeds to be called before' );
		}

		return self::$instance;
	}


	/**
	 * Validate field domain, which must be part of the GET request.
	 *
	 * @inheritdoc
	 *
	 * @throws InvalidArgumentException When the domain is invalid.
	 * @since 2.3
	 */
	public function prepare() {
		parent::prepare();
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		$this->add_screen_options();
		$this->prepare_dialogs();
	}


	/**
	 * Renders callback
	 *
	 * @inheritdoc
	 * @return callable
	 * @since 2.3
	 */
	public function get_render_callback() {
		return array( $this, 'render_page' );
	}


	/**
	 * Page name slug
	 *
	 * @inheritdoc
	 * @return string
	 * @since 2.3
	 */
	public function get_page_name() {
		return Types_Admin_Menu::PAGE_NAME_CUSTOM_FIELDS;
	}


	/**
	 * User required capability.
	 *
	 * @inheritdoc
	 * @return string
	 * @since 2.3
	 */
	public function get_required_capability() {
		return 'manage_options'; // TODO better role/cap handling.
	}


	/**
	 * Current field domain.
	 *
	 * Gets the current domain: posts, users, meta.
	 *
	 * @return string
	 * @since 2.3
	 */
	private function get_current_domain() {
		if ( null === $this->current_domain ) {
			$this->current_domain = toolset_getget( self::PARAM_DOMAIN, Toolset_Element_Domain::POSTS, Toolset_Element_Domain::all() );
		}

		return $this->current_domain;
	}


	/**
	 * Enqueue all assets needed by the page.
	 *
	 * (Notice the dependencies on Toolset GUI base assets.)
	 *
	 * @since 2.3
	 */
	public function on_admin_enqueue_scripts() {

		$main_handle = 'types-page-custom-fields-main';

		// Enqueuing with the wp-admin dependency because we need to override something !important.
		$this->asset_manager->enqueue_styles(
			array(
				'wp-admin',
				'common',
				'font-awesome',
				'wpcf-css-embedded',
				'wp-jquery-ui-dialog',
			)
		);

		wp_enqueue_style(
			$main_handle,
			TYPES_RELPATH . '/public/page/custom_fields/style.css',
			array(
				Toolset_Gui_Base::STYLE_GUI_BASE,
				Toolset_Assets_Manager::STYLE_TOOLSET_COMMON,
			)
		);

		wp_enqueue_script(
			$main_handle,
			TYPES_RELPATH . '/public/page/custom_fields/main.js',
			array(
				'jquery',
				'backbone',
				'underscore',
				Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
				Types_Asset_Manager::SCRIPT_HEADJS,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_ADJUST_MENU_LINK,
				Types_Asset_Manager::SCRIPT_UTILS,
				Types_Asset_Manager::SCRIPT_POINTER,
			),
			TYPES_VERSION
		);

	}


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Environment
	 * @throws LoaderError
	 * @since 2.3
	 */
	private function get_twig() {
		if ( null === $this->twig ) {

			$gui_base = Toolset_Gui_Base::get_instance();

			$this->twig = $gui_base->create_twig_environment(
				array(
					'custom_fields' => TYPES_ABSPATH . '/application/views/page/custom_fields',
				)
			);
		}

		return $this->twig;
	}


	/**
	 * Renders the page.
	 *
	 * Gets the page context: strings, items... and echoes the result.
	 *
	 * @since 2.3
	 */
	public function render_page() {

		$context = $this->build_page_context();

		try {
			$twig = $this->get_twig();
			echo $twig->render( '@custom_fields/main.twig', $context );
		} catch ( \OTGS\Toolset\Twig\Error\Error $e ) {
			echo 'Error during rendering the page. Please contact the Toolset user support.';
		}
	}


	/**
	 * Build the context for main poge template.
	 *
	 * That includes variables for the template as well as data to be passed to JavaScript.
	 *
	 * @return array Page context. See the main page template for details.
	 * @since 2.3
	 */
	private function build_page_context() {

		$gui_base = Toolset_Gui_Base::get_instance();

		// Basics for the listing page which we'll merge with specific data later on.
		$base_context = $gui_base->get_twig_context_base( Toolset_Gui_Base::TEMPLATE_LISTING, $this->build_js_data() );

		$specific_context = array(
			'strings' => $this->build_strings_for_twig(),
			'tabs' => $this->get_tabs(),
		);

		return toolset_array_merge_recursive_distinct( $base_context, $specific_context );
	}


	/**
	 * Adds typical header on admin pages.
	 *
	 * @return string
	 * @since 2.3
	 */
	public function add_new_label_for_twig() {
		// Checks user can?
		$add_button = false;
		switch ( $this->get_current_domain() ) {
			case Toolset_Element_Domain::POSTS:
				$add_button = WPCF_Roles::user_can_create( 'custom-field' );
				break;
			case Toolset_Element_Domain::USERS:
				$add_button = WPCF_Roles::user_can_create( 'user-meta-field' );
				break;
			case Toolset_Element_Domain::TERMS:
				$add_button = WPCF_Roles::user_can_create( 'term-field' );
				break;
		}

		if ( $add_button ) {
			$add_new_title = __( 'Add New', 'wpcf' );
		} else {
			$add_new_title = '';
		}

		$current_page = sanitize_text_field( toolset_getget( 'page' ) );
		// Legacy actions.
		do_action( 'wpcf_admin_header' );
		do_action( 'wpcf_admin_header_' . $current_page );
		// Legacy menu options.
		$legacy_page = '';
		switch ( $this->get_current_domain() ) {
			case Toolset_Element_Domain::POSTS:
				$legacy_page = 'wpcf-cf';
				break;
			case Toolset_Element_Domain::USERS:
				$legacy_page = 'wpcf-um';
				break;
			case Toolset_Element_Domain::TERMS:
				$legacy_page = 'wpcf_termmeta_listing';
				break;
		}
		do_action( 'wpcf_admin_header_' . $legacy_page );

		return $add_new_title;
	}


	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 * @since 2.3
	 */
	private function build_js_data() {

		$ajax_controller = Types_Ajax::get_instance();

		$field_action_name = $ajax_controller->get_action_js_name( Types_Ajax::CALLBACK_CUSTOM_FIELDS_ACTION );

		return array(
			'jsIncludePath' => TYPES_RELPATH . '/public/page/custom_fields',
			'typesVersion' => TYPES_VERSION,
			'customFields' => $this->build_custom_fields(),
			'strings' => $this->build_strings_for_js(),
			'ajaxInfo' => array(
				'fieldGroupAction' => array(
					'name' => $field_action_name,
					'nonce' => wp_create_nonce( $field_action_name ),
				),
			),
			'currentDomain' => $this->get_current_domain(),
			'itemsPerPage' => $this->get_items_per_page_setting(),
			'tabs' => $this->get_tabs(),
		);

	}


	/**
	 * Prepares custom fields data, grouped by domain, for passing to JavaScript.
	 *
	 * @return array List of custom fields grouped by domain:
	 *      [currentDomain] => actual domain, needed for tab selection
	 *      [data] => array
	 *      [domain1] => array (list of custom fields data)
	 * @since 2.3
	 */
	private function build_custom_fields() {
		$group_data = array();
		$group_data['currentDomain'] = $this->get_current_domain();
		// All the domains will be showed in the same page.
		$group_data['data'] = array();
		foreach ( Toolset_Element_Domain::all() as $domain ) {
			$group_data['data'][ $domain ] = array();
			$query_args = array(
				'orderby' => 'name',
				'order' => 'asc',
				'purpose' => [
					Toolset_Field_Group::PURPOSE_SYSTEM,
					Toolset_Field_Group::PURPOSE_GENERIC,
				],
			);

			$group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );
			$groups = $group_factory->query_groups( $query_args );

			$viewmodel_factory = new ViewmodelFactory();
			/** @var ViewmodelInterface[] $group_viewmodels */
			$group_viewmodels = array_map( static function ( $group ) use ( $viewmodel_factory ) {
				return $viewmodel_factory->create_viewmodel( $group );
			}, $groups );

			foreach ( $group_viewmodels as $group_viewmodel ) {
				$json_data = $group_viewmodel->to_json();
				$group_data['data'][ $domain ][] = $json_data;
			}
		}

		return $group_data;
	}


	/**
	 * Twig strings
	 *
	 * Prepares I18N strings used in views
	 *
	 * @return array
	 * @since 2.3
	 */
	private function build_strings_for_twig() {

		return array(
			'column' => array(
				'name' => __( 'Field name', 'wpcf' ),
				'description' => __( 'Description', 'wpcf' ),
				'isActive' => __( 'Active', 'wpcf' ),
				'postTypes' => __( 'Post Types', 'wpcf' ),
				'taxonomies' => __( 'Taxonomies', 'wpcf' ),
				'availableFor' => __( 'Available for', 'wpcf' ),
			),
			'rowAction' => array(
				'edit' => __( 'Edit', 'wpcf' ),
				'activate' => __( 'Activate', 'wpcf' ),
				'deactivate' => __( 'Deactivate', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
			'misc' => array(
				'pageTitle' => $this->get_page_title(),
				'addNew' => $this->add_new_label_for_twig(),
			),
			'bulkAction' => array(
				'activate' => __( 'Activate', 'wpcf' ),
				'deactivate' => __( 'Deactivate', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
			'domain' => $this->get_current_domain(),
		);
	}


	/**
	 * Prepares an array of strings used in JavaScript.
	 *
	 * @return array
	 * @since 2.3
	 */
	private function build_strings_for_js() {
		return array(
			'misc' => array(
				'undefinedAjaxError' => __( 'The action was not successful, an unknown error has happened.', 'wpcf' ),
				'genericSuccess' => __( 'The action was completed successfully.', 'wpcf' ),
				'deleteFieldGroup' => __( 'Delete field group', 'wpcf' ),
				'deleteFieldGroups' => __( 'Delete multiple field groups', 'wpcf' ),
				'addNewTitle' => __( 'Choose Custom Field Group type to create', 'wpcf' ),
				// Each tab has its owns text and button.
				'noItemsFound' => array(
					'posts' => sprintf(
						'<p>%s</p><a class="button-primary" href="%s">%s</a>',
						__( 'To use post fields, please create a group to hold them.', 'wpcf' ),
						admin_url() . 'admin.php?page=' . PostGroupViewmodel::EDIT_PAGE_SLUG,
						__( 'Add New Group', 'wpcf' )
					),
					'users' => sprintf(
						'<p>%s</p><a class="button-primary" href="%s">%s</a>',
						__( 'To use user fields, please create a group to hold them.', 'wpcf' ),
						admin_url() . 'admin.php?page=' . UserGroupViewmodel::EDIT_PAGE_SLUG,
						__( 'Add New Group', 'wpcf' )
					),
					'terms' => sprintf(
						'<p>%s</p><a class="button-primary" href="%s">%s</a>',
						__( 'To use term fields, please create a group to hold them.', 'wpcf' ),
						admin_url() . 'admin.php?page=' . TermGroupViewmodel::EDIT_PAGE_SLUG,
						__( 'Add New Group', 'wpcf' )
					),
					'search' => __( 'No items found.', 'wpcf' ),
				),
			),
			'rowAction' => array(
				'edit' => __( 'Edit', 'wpcf' ),
				'activate' => __( 'Activate', 'wpcf' ),
				'deactivate' => __( 'Deactivate', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
			'bulkAction' => array(
				'select' => __( 'Bulk action', 'wpcf' ),
				'activate' => __( 'Activate', 'wpcf' ),
				'deactivate' => __( 'Deactivate', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
			'button' => array(
				'apply' => __( 'Apply', 'wpcf' ),
				'cancel' => __( 'Cancel', 'wpcf' ),
				'delete' => __( 'Delete', 'wpcf' ),
			),
		);
	}


	/**
	 * Display screen options on the page.
	 *
	 * @since 2.3
	 */
	public function add_screen_options() {

		$args = array(
			'label' => __( 'Number of displayed groups', 'wpcf' ),
			'default' => self::SCREEN_OPTION_PER_PAGE_DEFAULT_VALUE,
			'option' => self::SCREEN_OPTION_PER_PAGE_NAME,
		);
		add_screen_option( 'per_page', $args );
	}


	/**
	 * Update the "per page" screen option.
	 *
	 * @param bool|int $original_value Screen option value. Default false to skip.
	 * @param string $option The option name.
	 * @param int $option_value The number of rows to use.
	 *
	 * @return bool|int
	 * @since 2.3
	 *
	 */
	public static function set_screen_option( $original_value, $option, $option_value ) {
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
	 * @since 2.3
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
	 * Prepares assets for all dialogs that are going to be used on the page.
	 *
	 * @since 2.3
	 */
	public function prepare_dialogs() {
		// When this is not injected in unit testing, the class is available only after the
		// Toolset GUI Base is loaded in parent::prepare().
		if ( null === $this->dialog_factory ) {
			$this->dialog_factory = new Toolset_Twig_Dialog_Box_Factory();
		}

		$this->dialog_factory->get_twig_dialog_box(
			'types-add-new-custom-field-dialog',
			$this->get_twig(),
			array(
				'tabs' => $this->get_tabs(),
				'strings' => array(
					'addNewTitle' => __( 'Choose Custom Field Group type to create', 'wpcf' ),
				),
			),
			'@custom_fields/add_new_dialog.twig'
		);

		$this->dialog_factory->get_twig_dialog_box(
			'types-delete-custom-field-dialog',
			$this->get_twig(),
			array(
				'strings' => array(
					'cannotBeUndone' => __( 'This cannot be undone!', 'wpcf' ),
					'doYouReallyWantDelete' => __( 'Do you really want to delete?', 'wpcf' ),
				),
			),
			'@custom_fields/delete_dialog.twig'
		);
	}


	/**
	 * Get help configuration for Types_Asset_Help_Tab_Loader.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_help_config() {
		return array(
			array(
				'id' => Types_Admin_Menu::LEGACY_PAGE_CUSTOM_POST_FIELDS,
				'title' => __( 'Post Fields', 'wpcf' ),
				'template' => '@help/basic.twig',
				'context' => array(
					'introductory_paragraphs' => array(
						__( 'Types plugin organizes post fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.', 'wpcf' ),
						sprintf(
						// translators: a link.
							__( 'You can read more about Post Fields in this tutorial: %s.', 'wpcf' ),
							sprintf(
								'<a href="%s" target="_blank">%s &raquo;</a>',
								Types_Helper_Url::get_url( 'using-post-fields', true, 'using-custom-fields', 'gui' ),
								Types_Helper_Url::get_url( 'using-post-fields', false, false, false, false )
							)
						),
						__( 'On this page you can see your current post field groups, as well as information about which post types and taxonomies they are attached to, and whether they are active or not.', 'wpcf' ),
					),
					'your_options' => __( 'You have the following options:', 'wpcf' ),
					'options' => array(
						array(
							'name' => __( 'Add New', 'wpcf' ),
							'explanation' => __( 'Use this to add a new post fields group which can be attached to a post type', 'wpcf' ),
						),
						array(
							'name' => __( 'Edit', 'wpcf' ),
							'explanation' => __( 'Click to edit the post field group', 'wpcf' ),
						),
						array(
							'name' => __( 'Activate', 'wpcf' ),
							'explanation' => __( 'Click to activate a post field group', 'wpcf' ),
						),
						array(
							'name' => __( 'Deactivate', 'wpcf' ),
							'explanation' => __( 'Click to deactivate a post field group (this can be re-activated at a later date)', 'wpcf' ),
						),
						array(
							'name' => __( 'Delete', 'wpcf' ),
							'explanation' => __( 'Click to delete a post field group.', 'wpcf' ) .
								sprintf( ' <strong>%s</strong>', __( 'Warning: This cannot be undone.', 'wpcf' ) ),
						),
					),
				),
			),
			array(
				'id' => Types_Admin_Menu::LEGACY_PAGE_CUSTOM_USER_FIELDS,
				'title' => __( 'User Fields', 'wpcf' ),
				'template' => '@help/basic.twig',
				'context' => array(
					'introductory_paragraphs' => array(
						__( 'Types plugin organizes User Fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.', 'wpcf' ),
						__( 'On this page you can see your current User Fields groups, as well as information about which user role they are attached to, and whether they are active or not.', 'wpcf' ),
					),
					'your_options' => __( 'You have the following options:', 'wpcf' ),
					'options' => array(
						array(
							'name' => __( 'Add New', 'wpcf' ),
							'explanation' => __( 'Use this to add a new User Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Edit', 'wpcf' ),
							'explanation' => __( 'Click to edit the User Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Activate', 'wpcf' ),
							'explanation' => __( 'Click to activate a User Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Deactivate', 'wpcf' ),
							'explanation' => __( 'Click to deactivate a User Field Group (this can be re-activated at a later date)', 'wpcf' ),
						),
						array(
							'name' => __( 'Delete', 'wpcf' ),
							'explanation' => __( 'Click to delete a User Field Group.', 'wpcf' ) .
								sprintf( ' <strong>%s</strong>', __( 'Warning: This cannot be undone.', 'wpcf' ) ),
						),
					),
				),
			),
			array(
				'id' => Types_Admin_Menu::LEGACY_PAGE_CUSTOM_TERM_FIELDS,
				'title' => __( 'Term Fields', 'wpcf' ),
				'template' => '@help/basic.twig',
				'context' => array(
					'introductory_paragraphs' => array(
						__( 'Types plugin organizes Term Fields in groups. Once you create a group, you can add the fields to it and control to what content it belongs.', 'wpcf' ),
						__( 'On this page you can see your current Term Field groups, as well as information about which taxonomies they are attached to, and whether they are active or not.', 'wpcf' ),
					),
					'your_options' => __( 'You have the following options:', 'wpcf' ),
					'options' => array(
						array(
							'name' => __( 'Add New', 'wpcf' ),
							'explanation' => __( 'Use this to add a new Term Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Edit', 'wpcf' ),
							'explanation' => __( 'Click to edit the Term Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Activate', 'wpcf' ),
							'explanation' => __( 'Click to activate a Term Field Group', 'wpcf' ),
						),
						array(
							'name' => __( 'Deactivate', 'wpcf' ),
							'explanation' => __( 'Click to deactivate a Term Field Group (this can be re-activated at a later date)', 'wpcf' ),
						),
						array(
							'name' => __( 'Delete', 'wpcf' ),
							'explanation' => __( 'Click to delete a Term Field Group.', 'wpcf' ) .
								sprintf( ' <strong>%s</strong>', __( 'Warning: This cannot be undone.', 'wpcf' ) ),
						),
					),
				),
			),
		);
	}


	/**
	 * Gets the page tabs.
	 *
	 * Tabs will be included in @toolset/base.twig if self.tabs is not empty.
	 * Format:
	 *  [slug] =>                   // Slug or DIV content ID.
	 *      [text]  => string   // Tab text.
	 *      [url]   => string   // Tab alternative URL (will be upload using Ajax)
	 *      [class] => string // Additional classes
	 *
	 * @return array
	 * @since 2.3
	 */
	private function get_tabs() {
		/**
		 * Text of each page tab
		 *
		 * @var array Each tab represents a different domain.
		 */
		$tabs_texts = array(
			Toolset_Element_Domain::POSTS => __( 'Post Fields', 'wpcf' ),
			Toolset_Element_Domain::USERS => __( 'User Fields', 'wpcf' ),
			Toolset_Element_Domain::TERMS => __( 'Term Fields', 'wpcf' ),
		);

		/**
		 * Texts for the Field Control box.
		 *
		 * @var array It contains [title, text, button, link]
		 */
		$tabs_field_control_box_texts = array(
			Toolset_Element_Domain::POSTS => array(
				'title' => __( 'Post Field Control', 'wpcf' ),
				'text' => __( 'You can control Post Fields by removing them from the groups, changing type or just deleting.', 'wpcf' ),
				'button' => __( 'Post Field Control', 'wpcf' ),
				'link' => add_query_arg(
					array(
						'page' => Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL,
						'domain' => 'posts',
					),
					admin_url( 'admin.php' )
				),
			),
			Toolset_Element_Domain::USERS => array(
				'title' => __( 'User Field Control', 'wpcf' ),
				'text' => __( 'You can control User Fields by removing them from the groups, changing type or just deleting.', 'wpcf' ),
				'button' => __( 'User Field Control', 'wpcf' ),
				'link' => add_query_arg(
					array(
						'page' => Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL,
						'domain' => 'users',
					),
					admin_url( 'admin.php' )
				),
			),
			Toolset_Element_Domain::TERMS => array(
				'title' => __( 'Term Field Control', 'wpcf' ),
				'text' => __( 'You can control Term Fields by removing them from the groups, changing type or just deleting.', 'wpcf' ),
				'button' => __( 'Term Field Control', 'wpcf' ),
				'link' => add_query_arg(
					array(
						'page' => Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL,
						'domain' => 'terms',
					),
					admin_url( 'admin.php' )
				),
			),
		);

		/**
		 * Texts for the 'Add New' dialog.
		 *
		 * @var array Each tab represents a different domain.
		 */
		$tabs_dialog = array(
			Toolset_Element_Domain::POSTS => array(
				'title' => __( 'Post Fields', 'wpcf' ),
				'icon' => 'fa fa-file',
				'description' => __( 'Fields that belong to pages, posts or custom types', 'wpcf' ),
				'link' => admin_url() . 'admin.php?page=' . PostGroupViewmodel::EDIT_PAGE_SLUG,
			),
			Toolset_Element_Domain::USERS => array(
				'title' => __( 'User Fields', 'wpcf' ),
				'icon' => 'fa fa-user',
				'description' => __( 'Fields that belong to users', 'wpcf' ),
				'link' => admin_url() . 'admin.php?page=' . UserGroupViewmodel::EDIT_PAGE_SLUG,
			),
			Toolset_Element_Domain::TERMS => array(
				'title' => __( 'Term Fields', 'wpcf' ),
				'icon' => 'fa fa-tags',
				'description' => __( 'Fields that belong to taxonomy terms', 'wpcf' ),
				'link' => admin_url() . 'admin.php?page=' . TermGroupViewmodel::EDIT_PAGE_SLUG,
			),
		);

		$tabs = array();
		foreach ( Toolset_Element_Domain::all() as $domain ) {
			$tabs[ $domain ] = array(
				'text' => $tabs_texts[ $domain ],
				'url' => esc_url_raw(
					add_query_arg(
						array(
							'page' => $this->get_page_name(),
							'domain' => $domain,
						),
						admin_url( 'admin.php' )
					)
				),
				'class' => $domain === $this->get_current_domain() ? 'nav-tab-active' : '',
				// Field control box.
				'field_control' => $tabs_field_control_box_texts[ $domain ],
				// 'Add new' dialog.
				'dialog' => $tabs_dialog[ $domain ],
			);
		}

		return $tabs;
	}
}
