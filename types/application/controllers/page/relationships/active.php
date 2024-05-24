<?php

use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Error\LoaderError;
use OTGS\Toolset\Twig\Error\RuntimeError;
use OTGS\Toolset\Twig\Error\SyntaxError;

/**
 * Relationship page controller (if m2m is enabled).
 *
 * Controls the page with relationship listing and (later also) editing and creating.
 * Using the Toolset_Gui_Base.
 *
 * @since m2m
 */
class Types_Page_Relationships extends Types_Page_Persistent {


	const MAIN_ASSET_HANDLE = 'types-page-relationships-main';

	const WIZARD_ASSET_HANDLE = 'types-page-relationships-wizard';

	const JQUERY_UI_ASSET_HANDLE = 'types-page-relationships-jquery-ui';

	/** @var Toolset_Common_Bootstrap */
	private $toolset_common_bootstrap;

	/** @var null|Toolset_Gui_Base Use get_gui_base() instead of accessing this directly. */
	private $_gui_base;

	/** @var null|Environment */
	private $_twig;

	/** @var null|Types_Viewmodel_Relationship_Definition_Factory */
	private $_relationship_definition_viewmodel_factory;

	/** @var null|array */
	private $post_type_map;

	/** @var Toolset_Field_Type_Definition_Factory */
	private $field_type_definition_factory;

	/** @var Toolset_Twig_Dialog_Box_Factory|null */
	private $_dialog_box_factory;

	/** @var Toolset_Post_Type_Query_Factory */
	private $post_type_query_factory;

	/** @var Toolset_Asset_Manager */
	private $toolset_asset_manager;

	/**
	 * Constansts handler
	 *
	 * @var Toolset_Constants
	 * @since m2m
	 */
	private $constants;


	/**
	 * Types_Page_Relationships constructor.
	 *
	 * @inheritdoc
	 *
	 * @param array $args Page information that needed to be determined before instantiating the controller.
	 *     - string $title Page title (and heading).
	 *     - string $page_name Slug of the page.
	 *     - string $required_capability User capability needed to display this page.
	 *
	 * @param null|Toolset_Common_Bootstrap $toolset_common_bootstrap_di PHPUnit dependency injection.
	 * @param null|Toolset_Gui_Base $gui_base_di PHPUnit dependency injection.
	 * @param null|Types_Viewmodel_Relationship_Definition_Factory $relationship_definition_vm_factory_di PHPUnit
	 *     dependency      *     injection.
	 */
	public function __construct(
		array $args,
		Toolset_Common_Bootstrap $toolset_common_bootstrap_di = null,
		Toolset_Gui_Base $gui_base_di = null,
		Types_Viewmodel_Relationship_Definition_Factory $relationship_definition_vm_factory_di = null,
		Toolset_Field_Type_Definition_Factory $field_type_definition_factory_di = null,
		Toolset_Twig_Dialog_Box_Factory $dialog_box_factory_di = null,
		Toolset_Post_Type_Query_Factory $post_type_query_factory_di = null,
		Toolset_Constants $constants_di = null,
		Toolset_Asset_Manager $toolset_asset_manager_di = null
	) {
		parent::__construct( $args );

		// If we don't get anything injected here, we need to wait with the instantiation until we actually need it
		$this->_gui_base = $gui_base_di;
		$this->_relationship_definition_viewmodel_factory = $relationship_definition_vm_factory_di;
		$this->_dialog_box_factory = $dialog_box_factory_di;

		$this->toolset_common_bootstrap = $toolset_common_bootstrap_di ? : Toolset_Common_Bootstrap::get_instance();
		$this->field_type_definition_factory = $field_type_definition_factory_di
			? : Toolset_Field_Type_Definition_Factory::get_instance();
		$this->post_type_query_factory = $post_type_query_factory_di ? : new Toolset_Post_Type_Query_Factory();
		$this->constants = $constants_di ? : new Toolset_Constants();
		$this->toolset_asset_manager = $toolset_asset_manager_di ? : Toolset_Asset_Manager::get_instance();
	}


	/**
	 * @inheritdoc
	 */
	public function prepare() {
		$this->get_gui_base()->init();

		$this->toolset_common_bootstrap->initialize_m2m();

		$this->add_metabox_support();
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->prepare_dialogs();

		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
	}


	/**
	 * @return Toolset_Gui_Base
	 * @since m2m
	 */
	private function get_gui_base() {
		if ( null === $this->_gui_base ) {
			$this->toolset_common_bootstrap->register_gui_base();

			$this->_gui_base = Toolset_Gui_Base::get_instance();
		}

		return $this->_gui_base;
	}


	/**
	 *  Add support for native WordPress metaboxes.
	 *
	 * On the Edit Relationship screen, native metaboxes are used for displaying the
	 * individual sections.
	 *
	 * @link https://code.tutsplus.com/articles/integrating-with-wordpress-ui-meta-boxes-on-custom-pages--wp-26843
	 * @since m2m
	 */
	private function add_metabox_support() {
		// User can choose between 1 or 2 columns (default 2)
		add_screen_option( 'layout_columns', array(
			'max' => 2,
			'default' => 2,
		) );

		$this->add_meta_boxes();
	}


	/**
	 * Register all metaboxes to be rendered on the Edit Relationship page.
	 *
	 * @since m2m
	 */
	private function add_meta_boxes() {

		$metaboxes = array(
			array(
				'title' => __( 'Name and description', 'wpcf' ),
				'template' => 'name_and_description',
			),
			array(
				'title' => __( 'Settings', 'wpcf' ),
				'template' => 'settings',
				'context' => array(
					'cardinalityClasses' => array(
						array(
							'value' => 'one-to-one',
							'title' => __( 'One-to-one', 'wpcf' ),
						),
						array(
							'value' => 'one-to-many',
							'title' => __( 'One-to-many', 'wpcf' ),
						),
						array(
							'value' => 'many-to-many',
							'title' => __( 'Many-to-many', 'wpcf' ),
						),
					),
					'postTypes' => $this->build_post_type_map(),
					'imageUrl' => $this->constants->constant( 'TYPES_RELPATH' ) . '/public/page/relationships/images',
				),
			),
			array(
				'title' => __( 'Save', 'wpcf' ),
				'template' => 'management',
				'priority' => 'high',
				'id' => 'submitdiv',
				'metabox_context' => 'side',
			),
			array(
				'title' => __( 'Intermediary Post Type and Custom Fields', 'wpcf' ),
				'template' => 'association_fields',
			),
		);

		foreach ( $metaboxes as $metabox ) {

			$metabox_template = $metabox['template'];
			$metabox_priority = toolset_getarr( $metabox, 'priority', 'default' );
			$metabox_context = toolset_getarr( $metabox, 'metabox_context', 'normal' );
			$metabox_id = toolset_getarr( $metabox, 'id', 'types_relationship_' . $metabox_template );

			add_meta_box(
				$metabox_id,
				$metabox['title'],
				array( $this, 'render_metabox' ),
				null,
				$metabox_context,
				$metabox_priority,
				$metabox
			);
		}

	}


	/**
	 * Adds some scripts and styles
	 *
	 * @since m2m
	 */
	public function on_admin_enqueue_scripts() {

		$types_version = $this->constants->constant( 'TYPES_VERSION' );

		wp_enqueue_script(
			self::MAIN_ASSET_HANDLE,
			$this->constants->constant( 'TYPES_RELPATH' ) . '/public/page/relationships/main.js',
			array(
				'jquery',
				'jquery-ui-slider',
				'backbone',
				'underscore',
				'postbox',
				'wp-pointer',
				Toolset_Assets_Manager::SCRIPT_HEADJS,
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
				Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
				Toolset_Gui_Base::SCRIPT_GUI_JQUERY_COLLAPSIBLE,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_BATCH_PROCESS_DIALOG,
				Toolset_Gui_Base::SCRIPT_GUI_MIXIN_ADVANCED_ITEM_VIEWMODEL,
				Types_Asset_Manager::SCRIPT_SLUG_CONFLICT_CHECKER,
			),
			$types_version
		);

		wp_enqueue_style(
			self::MAIN_ASSET_HANDLE,
			$this->constants->constant( 'TYPES_RELPATH' ) . '/public/page/relationships/style.css',
			array(
				Toolset_Gui_Base::STYLE_GUI_BASE,
				// For field type icons
				Toolset_Assets_Manager::STYLE_FONT_AWESOME,
				Types_Asset_Manager::STYLE_BASIC_CSS,
				Toolset_Gui_Base::STYLE_GUI_MIXIN_BATCH_PROCESS_DIALOG,
			),
			$types_version
		);

		wp_enqueue_style(
			self::WIZARD_ASSET_HANDLE,
			$this->constants->constant( 'TYPES_RELPATH' ) . '/public/page/relationships/css/wizard.css',
			array(
				self::MAIN_ASSET_HANDLE,
				Toolset_Assets_Manager::STYLE_FONT_AWESOME,
				'toolset-notifications-css',
			),
			$types_version
		);

		// Toolset Common icons used inside of CSS must be included inline.
		$icon_path = $this->toolset_asset_manager->get_image_url( Toolset_Asset_Manager::IMAGE_TOOLBOT_SVG, false );

		$custom_css = "
			.information::before {
				background-image: url($icon_path);
				background-size: contain;
				background-repeat: no-repeat;
				background-position: center center;
			}
		";

		wp_add_inline_style( self::WIZARD_ASSET_HANDLE, $custom_css );

		wp_enqueue_style(
			self::JQUERY_UI_ASSET_HANDLE,
			$this->constants->constant( 'TYPES_RELPATH' ) . '/public/page/relationships/css/jquery-ui-slider.css',
			array(),
			$types_version
		);

		// Needed for wpcf_slugize.
		wp_enqueue_script(
			'types',
			$this->constants->constant( 'WPCF_EMBEDDED_RES_RELPATH' ) . '/js/basic.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'toolset_select2' ),
			$types_version,
			true
		);

		// Required for /js/fields-form.js.
		wp_register_script(
			'wpcf-js',
			$this->constants->constant( 'WPCF_RES_RELPATH' ) . '/js/basic.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tabs', 'toolset-colorbox' ),
			$types_version
		);

		wp_localize_script(
			'wpcf-js',
			'wpcf_js',
			array(
				'close' => __( 'Close', 'wpcf' ),
			)
		);

		wp_enqueue_script( 'wpcf-js' );

		// Required for including fields.
		wp_enqueue_script(
			'wpcf-fields-form',
			$this->constants->constant( 'WPCF_EMBEDDED_RES_RELPATH' ) . '/js/fields-form.js',
			array( 'wpcf-js' ),
			$types_version
		);

		// Required for opening the fields list popup.
		wp_enqueue_script(
			'wpcf-admin-fields-form',
			$this->constants->constant( 'WPCF_RES_RELPATH' ) . '/js/fields-form.js',
			array(),
			$types_version
		);

		wp_enqueue_style(
			'wpcf-css-embedded',
			$this->constants->constant( 'WPCF_EMBEDDED_RES_RELPATH' ) . '/css/basic.css',
			array(),
			$types_version
		);

		wp_enqueue_style( 'wp-jquery-ui-dialog' );

	}


	protected function prepare_dialogs() {

		if ( null === $this->_dialog_box_factory ) {
			$this->_dialog_box_factory = new Toolset_Twig_Dialog_Box_Factory();
		}

		$this->_dialog_box_factory->create(
			'types-confirm-relationship-deleting',
			$this->get_twig(),
			array(),
			'@relationships/dialogs/confirm_relationship_deleting.twig'
		);

		$this->_dialog_box_factory->create(
			'types-confirm-post-type-deleting',
			$this->get_twig(),
			array(),
			'@relationships/dialogs/confirm_intermediary_post_type_deleting.twig'
		);

		$this->_dialog_box_factory->create(
			'types-confirm-cardinality-change',
			$this->get_twig(),
			array(),
			'@relationships/dialogs/confirm_cardinality_change.twig'
		);

		$this->_dialog_box_factory->create(
			'types-merge-relationships',
			$this->get_twig(),
			array(),
			'@relationships/dialogs/merge_relationships.twig'
		);

	}

	/** @noinspection PhpDocMissingThrowsInspection */
	/**
	 * @return Environment
	 */
	private function get_twig() {
		if ( null === $this->_twig ) {

			/** @noinspection PhpUnhandledExceptionInspection */
			$this->_twig = $this->get_gui_base()->create_twig_environment(
				array(
					'relationships' => $this->constants->constant( 'TYPES_ABSPATH' )
						. '/application/views/page/relationships',
				)
			);
		}

		return $this->_twig;
	}


	public function render_page() {

		/** @noinspection PhpUnhandledExceptionInspection */
		$twig = $this->get_twig();

		$context = $this->build_page_context();

		/** @noinspection PhpUnhandledExceptionInspection */
		echo $twig->render( '@relationships/main.twig', $context );
	}


	private function build_page_context() {

		// Basics for the listing page which we'll merge with specific data later on.
		$base_context = $this->get_gui_base()->get_twig_context_base(
			Toolset_Gui_Base::TEMPLATE_LISTING, $this->build_js_data()
		);

		$specific_context = array(
			'strings' => $this->build_strings_for_twig(),
			'wordpress' => array(
				'postboxescollapsenonce' => wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false, false ),
				'postboxesordernonce' => wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false, false ),
			),
		);

		return toolset_array_merge_recursive_distinct( $base_context, $specific_context );
	}


	private function build_strings_for_twig() {
		$external_icon = '<span class="dashicons dashicons-external"></span>';

		/** @noinspection HtmlUnknownTarget */
		return array(
			'misc' => array(
				'pageTitle' => _x( 'Relationships', 'relationship page title', 'wpcf' ),
				'noItemsFound' => _x( 'No relationships have been created yet.', 'shows instead of relationships table', 'wpcf' ),
				'wizardFieldsNonce' => wp_create_nonce( 'wpcf-edit-0' ),
				'slugConflictNonce' => wp_create_nonce( Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS ),
				'helpTexts' => array(
					// translators: 1: an URL 2: the title of this URL.
					'chooseHowToConnectPosts' => sprintf( __( 'Choose how to connect between posts. Need help? Read <a href="%1$s" title="%2$s" target="_blank">what different post relationships mean</a>%3$s.', 'wpcf' ), 'https://toolset.com/course-lesson/what-are-post-relationships-and-how-they-work/?utm_source=plugin&utm_medium=gui&utm_campaign=types', '', $external_icon ),
					// TODO get urls ToolsetDoc-627.
					'whichPostTypes' => __( 'Which post types do you want to connect?', 'wpcf' ),
					'selfJoinIsPossible' => __( 'Since you\'ve chosen an one-to-one or many-to-many relationship, you can select the same post type on both sides.', 'wpcf' ),
					'limitTheNumberOfPosts' => __( 'If you need to limit the maximum number of posts that can be connected to another post at once, you can do it here. You can change these limits later.', 'wpcf' ),
					// translators: 1: an URL 2: the title of this URL.
					'addFields' => sprintf( __( 'You can add fields to the relationship itself. Need help? Read about <a href="%1$s" title="%2$s" target="_blank">using fields in relationships</a>%3$s.', 'wpcf' ), 'https://toolset.com/course-lesson/how-to-set-up-post-relationships-in-wordpress/?utm_source=plugin&utm_medium=gui&utm_campaign=types', '', $external_icon ),
					// TODO get urls ToolsetDoc-628.
					'relationshipNames' => __( 'Please choose a name for the relationship. This name identifies the relationship and allows you to use it in different parts of the site. You will be able to later edit the "plural" and "singular" labels easily, but renaming the slug may be more problematic if it is already used in any existing shortcode attributes.', 'wpcf' ),
				),
			),
			'column' => array(
				'name' => _x( 'Name', 'relationship name column header', 'wpcf' ),
				'is_active' => _x( 'Active', 'column header - is relationship active', 'wpcf' ),
				'description' => _x( 'Description', 'column header - relationship description', 'wpcf' ),
			),
			'rowAction' => array(
				'edit' => _x( 'Edit', 'edit a relationship', 'wpcf' ),
				'delete' => _x( 'Delete', 'delete a relationship', 'wpcf' ),
			),
		);
	}


	private function build_js_data() {

		$relationship_action_name = Types_Ajax::get_instance()
			->get_action_js_name( Types_Ajax::CALLBACK_RELATIONSHIPS_ACTION );
		$delete_intermediary_post_type_action_name = Types_Ajax::get_instance()
			->get_action_js_name( Types_Ajax::CALLBACK_DELETE_INTERMEDIARY_POST_TYPE_ACTION );
		$merge_relationships_action_name = Types_Ajax::get_instance()
			->get_action_js_name( Types_Ajax::CALLBACK_MERGE_RELATIONSHIPS );

		$page_url = admin_url() . 'admin.php?page=' . Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS;

		/** @noinspection HtmlUnknownTarget */
		return array(
			'jsIncludePath' => TYPES_RELPATH . '/public/page/relationships',
			'typesVersion' => TYPES_VERSION,
			'itemsPerPage' => 10, // todo.
			'relationships' => $this->build_relationship_data(),
			'postTypes' => $this->build_post_type_map(),
			'potentialIntermediaryPostTypes' => $this->build_potential_intermediary_post_type_map(),
			'fieldTypeIcons' => $this->build_field_type_icon_map(),
			'nonce' => wp_create_nonce( $relationship_action_name ),
			'delete_intermediary_post_type_nonce' => wp_create_nonce( $delete_intermediary_post_type_action_name ),
			'delete_intermediary_post_type_action' => $delete_intermediary_post_type_action_name,
			'templates' => $this->build_templates(),
			'strings' => array(
				'yes' => __( 'Yes', 'wpcf' ),
				'no' => __( 'No', 'wpcf' ),
				'title' => array(
					'listing' => $this->get_title(),
					'edit' => __( 'Edit Relationship', 'wpcf' ),
					'wizard' => __( 'Relationship Wizard', 'wpcf' ),
				),
				'pageTitle' => array(
					'sep' => ' ' . apply_filters( 'document_title_separator', '-' ) . ' ',
					// translators: The name of a relationship.
					'edit' => _x( 'Edit %s', 'Edit a relationship', 'wpcf' ),
					'add' => _x( 'Add new', 'Add new page title', 'wpcf' ),
				),
				'bulkAction' => array(
					'select' => __( 'Select', 'wpcf' ),
					'merge' => __( 'Merge', 'wpcf' ),
				),
				'noPostTypesPlaceholder' => _x( 'posts', 'generic name when there are no post types selected', 'wpcf' ),
				'or' => _x( 'or', 'in the enumeration of post types', 'wpcf' ),
				'infinite' => _x( 'Infinite', 'as in: infinite posts', 'wpcf' ),
				'confirmUnload' => __( 'You have one or more unsaved relationships.', 'wpcf' ),
				'errorSavingRelationship' => __( 'There was an error when saving the relationship.', 'wpcf' ),
				'wizard' => array(
					'limits' => __( 'You can set maximum number of %PARENT% possible to assign to one %CHILD%', 'wpcf' ),
					'noLimit' => __( 'No limit', 'wpcf' ),
					'infinite' => __( 'Infinite', 'wpcf' ),
					'summaryDescription' => __( '<strong>%NUMBER% %PARENT%</strong> can be assigned to one <strong>%CHILD%</strong>', 'wpcf' ),
					'summaryDescriptionOneToOne' => __( '<strong>One %PARENT%</strong> can be assigned to one <strong>%CHILD%</strong>', 'wpcf' ),
					// translators: documentation link.
					'translatableWarning' => sprintf( __( 'Your site is multilingual and we recommend that you learn <a href="%s" target="_blank">how to translate related content</a>.', 'wpcf' ), 'https://toolset.com/course-lesson/translating-related-content/?utm_source=plugin&utm_medium=gui&utm_campaign=types' ),
					'slugConflictPlaceholder' => __( 'Checking for slug conflicts...', 'wpcf' ),
				),
				'postReferenceNotAllowedInRFG' => __( 'Post Reference Field can not be placed into a Repeatable Group.', 'wpcf' ),
				'postReferenceFieldOnlyAllowedWithOneAssignedPostType' => __( 'Post Reference field is only available for field groups, which are assigned to a single post type.', 'wpcf' ),
				'noIntermediaryPostType' => __( 'No post type exists yet.', 'wpcf' ),
				'deleteRelationshipDialog' => array(
					'title' => __( 'Delete relationship: ', 'wpcf' ),
					'delete' => _x( 'Delete permanently', 'delete a relationship', 'wpcf' ),
					'deactivate' => _x( 'Safely deactivate', 'deactivate a relationship', 'wpcf' ),
					'cancel' => __( 'Cancel', 'wpcf' ),
				),
				'deleteIntermediaryPostTypeDialog' => array(
					'title' => __( 'Delete Intermediary Post Type: ', 'wpcf' ),
					'delete' => _x( 'Delete permanently', 'delete a intermediary post type', 'wpcf' ),
					'cancel' => __( 'Cancel', 'wpcf' ),
					'finish' => __( 'Finish', 'wpcf' ),
				),
				'changeCardinalityDialog' => array(
					'title' => __( 'Clean up many to many data ', 'wpcf' ),
					'apply_and_save' => _x( 'Apply & Save', 'change cardinality', 'wpcf' ),
					'cancel' => __( 'Cancel', 'wpcf' ),
				),
				'mergeRelationshipsDialog' => array(
					'title' => __( 'Merge relationships', 'wpcf' ),
					'cancel' => __( 'Cancel', 'wpcf' ),
					'close' => __( 'Done', 'wpcf' ),
					'merge' => __( 'Merge', 'wpcf' ),
					'newRelationship' => __( 'new-relationship', 'wpcf' ),
					'actionName' => $merge_relationships_action_name,
					'nonce' => wp_create_nonce( Types_Ajax::CALLBACK_MERGE_RELATIONSHIPS ),
					'phaseLabels' => array(
						Types_Ajax_Handler_Merge_Relationships::PHASE_SETUP => __( 'Configuring the new many-to-many relationship.', 'wpcf' ),
						Types_Ajax_Handler_Merge_Relationships::PHASE_MERGE_ASSOCIATIONS => __( 'Transforming associations.', 'wpcf' ),
						Types_Ajax_Handler_Merge_Relationships::PHASE_CLEANUP => __( 'Removing previous relationships and performing clean-up.', 'wpcf' ),
					),
					'resultMessage' => array(
						'warning' => __( 'The relationship merging has finished with some warnings. Please check technical details for more information.', 'wpcf' ),
						'error' => __( 'An error has occurred during the relationship merging. Please contact the Toolset support forum with the copy of the technical details you will find below.', 'wpcf' ),
						'success' => __( 'Relationships have been successfully merged.', 'wpcf' ),
					),
				),
				// translators: post type name.
				'disabledPostTypesSingular' => __( '<strong>%s</strong> post type does not exist or is currently inactive', 'wpcf' ),
				// translators: post type names.
				'disabledPostTypesPlural' => __( '<strong>%s</strong> post types do not exist or are currently inactive', 'wpcf' ),
				'rolesSlugMustBeDifferent' => __( 'Role slugs must be different.', 'wpcf' ),
			),
			'urls' => array(
				'listing' => $page_url,
				'addNew' => $page_url . '&action=add_new',
				'edit' => $page_url . '&action=edit&slug=',
			),
		);

	}


	private function build_relationship_data() {

		$viewmodels = $this->get_relationship_definition_viewmodel_factory()->get_viewmodels();

		$results = array();
		foreach ( $viewmodels as $viewmodel ) {
			$results[] = $viewmodel->to_array();
		}

		return $results;
	}


	private function build_post_type_map() {

		if ( null === $this->post_type_map ) {

			$post_type_query = $this->post_type_query_factory->create(
				array(
					Toolset_Post_Type_Query::HAS_SPECIAL_PURPOSE => false,
					Toolset_Post_Type_Query::IS_PUBLIC => true,
				)
			);

			/** @var IToolset_Post_Type[] $post_types */
			$post_types = $post_type_query->get_results();

			$results = array();

			foreach ( $post_types as $post_type ) {
				$post_type_slug = $post_type->get_slug();
				$results[ $post_type_slug ] = array(
					'plural' => $post_type->get_label( Toolset_Post_Type_Labels::NAME ),
					'singular' => $post_type->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME ),
					'can_be_used_in_relationship' => $this->format_can_be_used( $post_type->can_be_used_in_relationship()
						->to_array() ),
					'isTranslatable' => WpmlService::get_instance()
						->is_post_type_translatable( $post_type_slug ),
				);
			}

			$this->post_type_map = $results;
		}

		return $this->post_type_map;

	}


	/**
	 * @return Types_Viewmodel_Relationship_Definition_Factory
	 * @since m2m
	 */
	private function get_relationship_definition_viewmodel_factory() {
		if ( null === $this->_relationship_definition_viewmodel_factory ) {
			$this->_relationship_definition_viewmodel_factory = new Types_Viewmodel_Relationship_Definition_Factory();
		}

		return $this->_relationship_definition_viewmodel_factory;
	}


	/**
	 * Get the list of post types that theoretically could act as intermediary ones.
	 *
	 * @return array
	 */
	private function build_potential_intermediary_post_type_map() {
		$post_type_query = $this->post_type_query_factory->create(
			array(
				Toolset_Post_Type_Query::FROM_TYPES => true,
				Toolset_Post_Type_Query::HAS_SPECIAL_PURPOSE => false,
				Toolset_Post_Type_Query::IS_INVOLVED_IN_RELATIONSHIP => false,
				Toolset_Post_Type_Query::IS_REGISTERED => true,
			)
		);

		$post_types = $post_type_query->get_results();

		$results = array();
		foreach ( $post_types as $post_type ) {
			$results[] = array(
				'slug' => $post_type->get_slug(),
				'label' => $post_type->get_label(),
			);
		}

		return $results;
	}


	public function build_field_type_icon_map() {
		$type_definitions = $this->field_type_definition_factory->get_all_definitions();
		$results = array();
		foreach ( $type_definitions as $type_definition ) {
			$results[ $type_definition->get_slug() ] = $type_definition->get_icon_classes();
		}

		return $results;
	}


	/**
	 * Build array of templates that will be passed to JavaScript.
	 *
	 * If the template file does not exist or is not readable, it will be silently omitted.
	 *
	 * @return array
	 * @since m2m
	 */
	private function build_templates() {

		$template_sources = array(
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


	/**
	 * Render a single metabox from a dedicated Twig template.
	 *
	 * @param mixed $object Ignored.
	 * @param array $args Metabox arguments. One of the elements is 'args' passed
	 *     from the add_meta_box() call.
	 *
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @since m2m
	 */
	public function render_metabox(
		/** @noinspection PhpUnusedParameterInspection */
		$object, $args
	) {
		$template_name = sprintf(
			'@relationships/metaboxes/%s.twig',
			toolset_getnest( $args, array( 'args', 'template' ) )
		);

		$context = toolset_ensarr(
			toolset_getnest( $args, array( 'args', 'context' ) )
		);

		$twig = $this->get_twig();

		/** @noinspection PhpUnhandledExceptionInspection */
		echo $twig->render( $template_name, $context );
	}


	/**
	 * Format can_be_used_in_relationship data adding tooltip header title
	 *
	 * @param array $data Data received from Toolset_Post_Type_Abstract::can_be_used_in_relationship()
	 *
	 * @return array
	 * @see Toolset_Post_Type_Abstract::can_be_used_in_relationship()
	 * @since m2m
	 */
	private function format_can_be_used( $data ) {
		$data['title'] = isset( $data['message'] ) && false !== strpos( $data['message'], 'WPML' )
			? 'Translatable Post Type'
			: 'Wrong post type';

		return $data;
	}
}
