<?php

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Error\LoaderError;
use OTGS\Toolset\Twig_Environment;
use OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride;
use OTGS\Toolset\Types\Page\Extension\RelatedContent\DirectEditStatusRepository;
use OTGS\Toolset\Types\User\Access;

/**
 * Handle action with related content in edit posts page.
 *
 * @since 2.3
 * @refactoring Split this into smaller classes, one per action.
 */
class Types_Ajax_Handler_Related_Content_Action extends Toolset_Ajax_Handler_Abstract {


	const ACTION_INSERT = 'insert';

	const ACTION_CONNECT = 'connect';

	const ACTION_UPDATE = 'update';

	const ACTION_ENABLE_FIELDS = 'enable_fields';

	const ACTION_DISCONNECT = 'disconnect';

	const ACTION_DELETE = 'delete';

	const ACTION_RESTORE = 'restore';

	const ACTION_SEARCH_RELATED_CONTENT = 'search_related_content';

	const ACTION_LOAD = 'load';

	const ACTION_GET_TRANSLATABLE_CONTENT = 'get_translatable_content';

	const ACTION_UPDATE_FIELDS_DISPLAYED = 'update_fields_displayed';


	const SEARCH_RESULTS_PER_PAGE = 25;


	/**
	 * Unsafe actions list
	 *
	 * @var array
	 */
	private $unsafe_actions = array(
		self::ACTION_CONNECT,
		self::ACTION_DISCONNECT,
		self::ACTION_SEARCH_RELATED_CONTENT,
		self::ACTION_LOAD,
	);


	/**
	 * Twig class
	 *
	 * @var Twig_Environment Twig Enviroment.
	 * @since m2m
	 */
	private $twig;


	/** @var null|Toolset_Gui_Base */
	private $_gui_base;


	/**
	 * Definition repository injection for testing purposes
	 *
	 * @var Toolset_Relationship_Definition_Repository Definition repository.
	 * @since m2m
	 */
	private $definition_repository;


	/**
	 * Fields Edit Container injection for testing purposes
	 *
	 * @var Types_Viewmodel_Fields_Edit_Container|null Fields Edit Container Viewmodel.
	 * @since m2m
	 */
	private $fields_edit_container;


	/**
	 * Post type repository
	 *
	 * @var Toolset_Post_Type_Repository
	 * @since m2m
	 */
	private $post_type_repository;

	/**
	 * Association
	 *
	 * @var IToolset_Association|null
	 * @since m2m
	 */
	private $association;

	/**
	 * Relationship definition
	 *
	 * @var IToolset_Relationship_Definition|null
	 * @since m2m
	 */
	private $definition;

	/**
	 * Checks if it can connect to another related content
	 *
	 * For testing purposes
	 *
	 * @var boolean
	 * @since m2m
	 */
	private $can_connect;

	/** @var Toolset_Element_Factory|null */
	private $element_factory;

	/** @var DirectEditStatusRepository */
	private $direct_edit_status_factory;

	/** @var WpmlService */
	private $wpml_service;

	/** @var Factory */
	private $relationships_factory;

	/** @var WpmlTridAutodraftOverride */
	private $wpml_trid_autodraft_override;

	/** @var Types_Viewmodel_Related_Content_Factory */
	private $related_content_factory;


	/**
	 * Constructor
	 *
	 * @param Types_Ajax $ajax_manager Ajax manager.
	 * @param Toolset_Relationship_Definition_Repository $definition_repository Test injection purposes.
	 * @param Factory $relationships_factory
	 * @param Toolset_Post_Type_Repository $post_type_repository
	 * @param Toolset_Element_Factory $element_factory
	 * @param DirectEditStatusRepository $direct_edit_status_factory
	 * @param WpmlService $wpml_service
	 * @param WpmlTridAutodraftOverride $wpml_trid_autodraft_override
	 * @param Types_Viewmodel_Related_Content_Factory $related_content_factory
	 * @param boolean $can_connect_di Test injection purposes.
	 * @param Toolset_Gui_Base $gui_base_di Test injection purposes.
	 * @param Types_Viewmodel_Fields_Edit_Container $fields_edit_container Test injection purposes.
	 *
	 * @since m2m
	 */
	public function __construct(
		Types_Ajax $ajax_manager,
		Toolset_Relationship_Definition_Repository $definition_repository,
		Factory $relationships_factory,
		Toolset_Post_Type_Repository $post_type_repository,
		Toolset_Element_Factory $element_factory,
		DirectEditStatusRepository $direct_edit_status_factory,
		WpmlService $wpml_service,
		WpmlTridAutodraftOverride $wpml_trid_autodraft_override,
		Types_Viewmodel_Related_Content_Factory $related_content_factory,
		$can_connect_di = null,
		$gui_base_di = null,
		Types_Viewmodel_Fields_Edit_Container $fields_edit_container = null
	) {
		parent::__construct( $ajax_manager );
		$this->_gui_base = $gui_base_di;
		$this->definition_repository = $definition_repository;
		$this->relationships_factory = $relationships_factory;
		$this->fields_edit_container = $fields_edit_container;
		$this->can_connect = $can_connect_di;
		$this->post_type_repository = $post_type_repository;
		$this->element_factory = $element_factory;
		$this->direct_edit_status_factory = $direct_edit_status_factory;
		$this->wpml_service = $wpml_service;
		$this->wpml_trid_autodraft_override = $wpml_trid_autodraft_override;
		$this->related_content_factory = $related_content_factory;
	}


	/**
	 * Returns GUI Base
	 *
	 * @return Toolset_Gui_Base
	 * @since m2m
	 */
	protected function get_gui_base() {
		if ( null === $this->_gui_base ) {
			$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
			$toolset_common_bootstrap->register_gui_base();
			$this->_gui_base = Toolset_Gui_Base::get_instance();
			$this->_gui_base->init();
		}

		return $this->_gui_base;
	}


	/**
	 * Returns the related content viewmodel
	 *
	 * It is used for testing purposes, if is set during class instance, it will return, other case it will be
	 * generated.
	 *
	 * @param string $role Relationship element role.
	 * @param IToolset_Relationship_Definition $definition The relationship.
	 *
	 * @return Types_Viewmodel_Related_Content
	 * @since m2m
	 */
	private function get_related_content_viewmodel( $role, $definition ) {
		return $this->related_content_factory->get_model_by_relationship( $role, $definition );
	}


	/**
	 * Returns the fields edit container viewmodel
	 *
	 * @param Toolset_Field_Definition[]|Toolset_Field_Instance[] $fields Array of fields.
	 * @param Environment $twig Twig environment.
	 * @param array $context Initial Twig context.
	 * @param string $template Template path.
	 *
	 * @return Types_Viewmodel_Fields_Edit_Container
	 */
	private function get_fields_edit_container( $fields, $twig, $context, $template ) {
		if ( ! $this->fields_edit_container ) {
			return new Types_Viewmodel_Fields_Edit_Container( $fields, $twig, $context, $template );
		}

		return $this->fields_edit_container;
	}


	/**
	 * Retrieve a Twig environment initialized by the Toolset GUI base.
	 *
	 * @return Environment
	 * @throws LoaderError In case of error
	 * @since m2m
	 */
	protected function get_twig() {
		if ( null === $this->twig ) {

			$gui_base = $this->get_gui_base();
			$twig_templates_path = array(
				'related-content' => TYPES_ABSPATH . '/application/views/page/extension/related_content',
			);
			$this->twig = $gui_base->create_twig_environment( $twig_templates_path );
		}

		return $this->twig;
	}


	/**
	 * Process the Ajax call
	 *
	 * @param array $arguments List of POST arguments.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @throws LoaderError
	 * @since m2m
	 */
	public function process_call( $arguments ) {
		do_action( 'toolset_do_m2m_full_init' );

		$action = toolset_getpost( 'related_content_action' );

		$this->get_ajax_manager()->ajax_begin(
			array(
				'nonce' => $this->get_ajax_manager()->get_action_js_name( Types_Ajax::CALLBACK_RELATED_CONTENT_ACTION ),
				'capability_needed' => $this->is_safe_action( $action ) ? 'read' : 'edit_posts',
				'is_public' => toolset_getarr( $_REQUEST, 'skip_capability_check', false ),
			)
		);

		$this->maybe_set_current_language();

		$this->wpml_trid_autodraft_override->initialize(
			(int) toolset_getpost( 'parent_post_id' ),
			(int) toolset_getnest( $_POST, [ 'parent_post_translation_override', 'trid' ] ),
			esc_attr( toolset_getnest( $_POST, [ 'parent_post_translation_override', 'lang_code' ] ) )
		);

		switch ( $action ) {
			case self::ACTION_UPDATE:
				$this->update();
				break;
			case self::ACTION_INSERT:
				$this->insert_connect( self::ACTION_INSERT );
				break;
			case self::ACTION_ENABLE_FIELDS:
				$this->enable_editing_fields();
				break;
			case self::ACTION_DISCONNECT:
				$this->disconnect_association();
				break;
			case self::ACTION_DELETE:
				$this->trash_related_posts();
				break;
			case self::ACTION_RESTORE:
				$this->restore_related_post_from_trash();
				break;
			case self::ACTION_SEARCH_RELATED_CONTENT:
				$this->search_related_content();
				break;
			case self::ACTION_CONNECT:
				$this->insert_connect( self::ACTION_CONNECT );
				break;
			case self::ACTION_LOAD:
				$this->load_related_content();
				break;
			case self::ACTION_GET_TRANSLATABLE_CONTENT:
				$this->get_translatable_content();
				break;
			case self::ACTION_UPDATE_FIELDS_DISPLAYED:
				$this->update_fields_displayed();
				break;
			default:
				$this->fail( __( 'Something was wrong, please try again.', 'wpcf' ) );
		}
	}


	/**
	 * If WPML is active, we will tell it what is the current language - it cannot determine
	 * it on its own in an AJAX call.
	 *
	 * This is especially important when querying items to connect the current post with.
	 *
	 * @since m2m
	 */
	private function maybe_set_current_language() {
		$lang_code = toolset_getpost( 'current_language', '' );
		if ( ! empty( $lang_code ) ) {
			do_action( 'wpml_switch_language', $lang_code );
		}
	}


	/**
	 * Updates related content fields, both post and relationship fields
	 *
	 * @throws LoaderError|Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function update() {
		$association = $this->get_association();
		if ( ! $association ) {
			$this->fail( __( 'Related content not found.', 'wpcf' ) );
		}

		$role = toolset_getpost( 'role' );
		if ( ! in_array( $role, Toolset_Relationship_Role::parent_child_role_names(), true ) ) {
			$this->fail( __( 'Wrong related content role.', 'wpcf' ) );
		}

		$fields = toolset_getpost( 'wpcf' );

		if ( ! is_array( $fields ) || empty( $fields )
			|| ( empty( $fields['post'] )
				&& empty( $fields['relationship'] ) ) ) {
			$this->fail( __( 'The related content has not been updated, as no fields were modified.', 'wpcf' ), 'warning' );
		}

		// Post fields.
		$related_post_id = toolset_getpost( 'post_id' );
		if ( isset( $fields['post'] ) ) {
			$this->update_post_fields( $related_post_id, $association, $fields['post'], $role );
		}
		// Relationship fields.
		if ( isset( $fields['relationship'] ) ) {
			$this->update_relationship_fields( $association, $fields['relationship'] );
		}

		// Handle unchecked checkboxes
		$this->update_unchecked_checkboxes( $fields, $association, $related_post_id );

		$other_role = Toolset_Relationship_Role::other( $role );
		/** @var Types_Viewmodel_Related_Content_Post $related_content_viewmodel (true for now) */
		$related_content_viewmodel = $this->get_related_content_viewmodel( $other_role, $association->get_definition() );
		$results = $related_content_viewmodel->get_related_content_from_uid_array( $association->get_uid() );

		if ( isset( $results[0]['fields'] ) && is_array( $results[0]['fields'] ) ) {
			$results[0]['fields'] = $this->format_field_data( $results[0]['fields'], $association->get_uid() );
		}

		if ( $role === Toolset_Relationship_Role::CHILD ) {
			// legacy action 'wpcf_relationship_save_child'
			$child_post = get_post( $association->get_element_id( new Toolset_Relationship_Role_Child() ) );
			$parent_post = get_post( $association->get_element_id( new Toolset_Relationship_Role_Parent() ) );

			do_action( 'wpcf_relationship_save_child', $child_post, $parent_post );
		}

		/*
		 * Action 'toolset_post_update'
		 *
		 * @var WP_Post $affected_post
		 *
		 * @since 3.0
		 */
		$affected_post = $association->get_element( $role )->get_underlying_object();
		do_action( 'toolset_post_update', $affected_post );

		$data = array(
			'results' => $results,
		);
		$this->get_ajax_manager()->ajax_finish( $data, true );

	}


	/**
	 * Gets the role of the relationship from the post type
	 *
	 * @param IToolset_Relationship_Definition $definition Relationship definition.
	 * @param string $post_type The post type.
	 *
	 * @return string
	 * @throws InvalidArgumentException In case of error.
	 * @since m2m
	 */
	private function get_role_from_post_type( $definition, $post_type ) {
		$parent_type = $definition->get_element_type( Toolset_Relationship_Role::PARENT )->get_types();
		$child_type = $definition->get_element_type( Toolset_Relationship_Role::CHILD )->get_types();
		if ( $post_type === $parent_type[0] ) {
			return Toolset_Relationship_Role::PARENT;
		}

		if ( $post_type === $child_type[0] ) {
			return Toolset_Relationship_Role::CHILD;
		}

		throw new InvalidArgumentException( __( 'The post type doesn\'t belong to the relationship', 'wpcf' ) );
	}


	/**
	 * Insert/connect related content fields, both post and relationship fields
	 * 1- Gets and check the relationship
	 * 2- Retreviews the roles (parent/child) for association create_association
	 * 3- If the related post doesn't exist, then create it
	 * 4- If it exists, check if it is not related to the same post if it is not a many to many relationship
	 * 5- Create the association
	 * 6- Update the fields (post and/or relationship)
	 *
	 * @param string $action Insert or connect action key.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @throws LoaderError
	 * @since m2m
	 */
	private function insert_connect( $action ) {
		$current_post_id = toolset_getpost( 'post_id' );
		$post = get_post( $current_post_id );
		if ( ! $post ) {
			$this->fail( __( 'Post not found.', 'wpcf' ) );
		}

		$definition = $this->get_definition();
		if ( ! $definition ) {
			$this->fail( __( 'Relationship not found.', 'wpcf' ) );
		}

		// The role belonging to the current post.
		$role = $this->get_role_from_post_type( $definition, $post->post_type );
		// The role beloinging to the related post.
		$other_role = Toolset_Relationship_Role::other( $role );
		$other_element_type = $definition->get_element_type( $other_role );
		$other_types = $other_element_type->get_types();

		$this->check_can_be_used( $other_types );

		$fields = toolset_getpost( 'wpcf' );

		if ( self::ACTION_INSERT === $action ) {
			$related_post = $this->create_post( $fields, $other_types[0] );
		} else {
			$related_post = $this->get_post_to_connect( $fields, $other_types[0] );
		}

		// Once the related post exists (inserted or retreived), it is neccesary to create the intermediary post.
		list( $parent_post, $child_post ) = Toolset_Relationship_Role::sort_elements( $post, $related_post, $role );
		$association = $definition->create_association( $parent_post, $child_post );

		if ( $association instanceof ResultInterface && $association->is_error() ) {
			$this->fail(
				sprintf(
					__( 'There was a problem creating the related content: %s', 'wpcf' ),
					$association->get_message()
				)
			);
		}

		if ( ! $association instanceof IToolset_Association ) {
			// This should never happen.
			$this->fail( __( 'There was a problem creating the related content.', 'wpcf' ) );
		}

		if ( ! is_array( $fields ) || empty( $fields )
			|| ( empty( $fields['post'] )
				&& empty( $fields['relationship'] ) ) ) {
			$this->fail( __( 'No fields sent.', 'wpcf' ) );
		}

		// Post fields.
		if ( self::ACTION_INSERT === $action && isset( $fields['post'] ) ) {
			$this->update_post_fields( $related_post->ID, $association, $fields['post'], $other_role );
		}
		// Relationship fields.
		if ( isset( $fields['relationship'] ) ) {
			$this->update_relationship_fields( $association, $fields['relationship'] );
		}

		// Handle unchecked checkboxes
		$this->update_unchecked_checkboxes( $fields, $association, $related_post->ID );

		/** @var Types_Viewmodel_Related_Content_Post $related_content_viewmodel (true for now) */
		$related_content_viewmodel = $this->get_related_content_viewmodel( $role, $association->get_definition() );
		$results = $related_content_viewmodel->get_related_content_from_uid_array( $association->get_uid() );

		if ( isset( $results[0]['fields'] ) && is_array( $results[0]['fields'] ) ) {
			$results[0]['fields'] = $this->format_field_data( $results[0]['fields'], $association->get_uid() );
		}

		$current_post_type_object = get_post_type_object( $post->post_type );
		$related_post_type_object = get_post_type_object( $other_types[0] );
		$message = self::ACTION_INSERT === $action
			// translators: both are post types.
			? sprintf( __( 'A new <strong>%1$s</strong> has been connected to this <strong>%2$s</strong>', 'wpcf' ), $related_post_type_object->labels->singular_name, $current_post_type_object->labels->singular_name )
			: sprintf( __( 'An existing <strong>%1$s</strong> has been connected to this <strong>%2$s</strong>', 'wpcf' ), $related_post_type_object->labels->singular_name, $current_post_type_object->labels->singular_name );

		$can_connect_another = $this->can_connect_another();

		$data = array(
			'results' => $results,
			'message' => $message,
			'canConnectAnother' => $can_connect_another,
		);
		$this->get_ajax_manager()->ajax_finish( $data, true );

	}


	/**
	 * Obtain the post to connect with from the input and perform an initial validation.
	 *
	 * @param Toolset_Field_Instance[] $fields List of fields.
	 * @param String $post_type Actual Post type.
	 *
	 * @return WP_Post
	 *
	 * @since m2m
	 */
	private function get_post_to_connect( $fields, $post_type ) {
		if ( empty( $fields['post']['post-id'] ) ) {
			$this->fail( __( 'Related content not found.', 'wpcf' ) );
		}
		$related_post = get_post( $fields['post']['post-id'] );

		if ( ! $related_post ) {
			$this->fail( __( 'Related content not found.', 'wpcf' ) );
		}
		if (
			! $this->wpml_service->has_default_language_translation( $related_post->ID )
			&& $this->relationships_factory->database_operations()->requires_default_language_post()
		) {
			$this->fail( __( 'Related content has to be translated to default language.', 'wpcf' ) );
		}
		if ( $related_post->post_type !== $post_type ) {
			$this->fail( __( 'Wrong related content', 'wpcf' ) );
		}

		return $related_post;
	}


	/**
	 * Create a new post
	 *
	 * @param Toolset_Field_Instance[] $fields List of fields.
	 * @param String $post_type Actual Post type.
	 *
	 * @return WP_Post
	 * @since m2m
	 */
	private function create_post( $fields, $post_type ) {
		$current_language = $this->wpml_service->get_current_language();
		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			// Post creation must be done in default language.
			do_action( 'wpml_switch_language', $this->wpml_service->get_default_language() );
		}

		// Insert
		// Creating new related post type.
		if ( empty( $fields['post']['post-title'] ) ) {
			$this->fail( __( 'Empty post title.', 'wpcf' ) );
		}
		$related_post_id = wp_insert_post(
			array(
				'post_type' => $post_type,
				'post_title' => sanitize_text_field( $fields['post']['post-title'] ),
				'post_status' => 'publish',
			)
		);
		if ( is_wp_error( $related_post_id ) ) {
			$post_type_object = get_post_type_object( $post_type );
			$this->fail(
				sprintf(
				// translators: Post type singular name.
					__( 'Something was wrong while creating a new %s.', 'wpcf' ),
					$post_type_object->labels->singular_name
				)
			);
		}
		$related_post = get_post( $related_post_id );

		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			// Switch back to current language.
			do_action( 'wpml_switch_language', $current_language );
		}

		/*
		 * Action 'toolset_post_update'
		 *
		 * @var WP_Post $related_post
		 *
		 * @since 3.0
		 */
		do_action( 'toolset_post_update', $related_post );

		return $related_post;
	}


	/**
	 * Formats fields data
	 * It receives unformatted fields data for post and relationship and format them into preview and HTML input
	 * elements.
	 *
	 * @param Toolset_Field_Instance[][] $fields An array of fields.
	 * @param int $association_uid Association UID.
	 *
	 * @return array Formatted data
	 * @throws LoaderError
	 * @since m2m
	 */
	private function format_field_data( $fields, $association_uid ) {
		$ajax_controller = Types_Ajax::get_instance();

		$field_action_name = $ajax_controller->get_action_js_name( Types_Ajax::CALLBACK_RELATED_CONTENT_ACTION );
		$nonce = wp_create_nonce( $field_action_name );

		// Fields data is divided into preview fields and html input fields
		// HTML inputs fields are divided into post fields and relationship fields
		// because they are in different sections in the page.
		$fields_data = array(
			'association_uid' => $association_uid,
			'preview' => array(),
			'input' => array(
				'post' => array(),
				'relationship' => array(),
			),
		);

		// Post fields.
		$fields_input = new Types_Viewmodel_Field_Input( $fields['post'] );
		$fields_data['preview']['post'] = $fields_input->get_fields_data();

		// Relationship fields.
		$fields_input = new Types_Viewmodel_Field_Input( $fields['relationship'] );
		$fields_data['preview']['relationship'] = $fields_input->get_fields_data();

		foreach ( array( 'post', 'relationship' ) as $field_type ) {
			$fields_container = $this->get_fields_edit_container(
				$fields[ $field_type ],
				$this->get_twig(),
				array(
					'id' => 'field-input-container-' . $association_uid,
					'nonce' => $nonce,
				),
				'@' . Types_Page_Extension_Meta_Box_Related_Content::ID . '/field_input.twig'
			);
			$fields_data['input'][ $field_type ] = $fields_container->to_html();
		}

		// WYSIWYG extra data. mceInit is needed to set for new tinymce initialization.
		$all_fields = array();
		if ( is_array( $fields['post'] ) ) {
			$all_fields = array_merge( $all_fields, $fields['post'] );
		}
		if ( is_array( $fields['relationship'] ) ) {
			$all_fields = array_merge( $all_fields, $fields['relationship'] );
		}
		$tinymce_helper = new Types_Helper_TinyMCE();
		$fields_data['extra'] = array(
			'wysiwyg' => $tinymce_helper->generate_mceinit_data(
				$all_fields,
				$fields_data['input']['post'] . $fields_data['input']['relationship']
			),
		);

		// Intermediary Title
		$intermediary_title_data = Types_Page_Extension_Meta_Box_Related_Content::get_table_data_for_intermediary_title( $association_uid );
		if ( $intermediary_title_data ) {
			$fields_data['preview']['relationship']['intermediary-title'] = $intermediary_title_data;
		}

		return $fields_data;
	}


	/**
	 * Updates post fields and post title
	 *
	 * @param integer $post_id The post ID to be updated.
	 * @param IToolset_Association $association The related content association.
	 * @param array $fields An array of fields pairs: slug => value.
	 * @param string $role Parent or child.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function update_post_fields( $post_id, $association, $fields, $role ) {
		// I don't know why the fields content is scaped.
		foreach ( $fields as $i => $field ) {
			if ( ! is_array( $field ) ) {
				$fields[ $i ] = stripslashes( $field );
			}
		}

		$post_id = (int) $post_id;
		$post = $association->get_element( Toolset_Relationship_Role::role_from_name( $role ) );

		if ( $post->get_id() !== $post_id ) {
			$this->fail( __( 'Wrong related post.', 'wpcf' ) );
		}
		// Post title.
		$title = isset( $fields['post-title'] ) ? sanitize_text_field( $fields['post-title'] ) : false;
		if ( ! $title ) {
			$this->fail( __( 'Invalid related post title.', 'wpcf' ) );
		}

		// Toolset_Post doesn't have a getter for the post object (WP_Post).
		/** @var WP_Post $wp_post */
		$wp_post = get_post( $post_id );
		$wp_post->post_title = $title;
		wp_update_post( $wp_post );
		// Removing title in order to avoid filter error.
		unset( $fields['post-title'] );

		$this->update_fields( $fields, $post->get_id() );
	}


	/**
	 * Updates relationship fields
	 *
	 * @param IToolset_Association $association The related content association.
	 * @param array $fields An array of fields pairs: slug => value.
	 *
	 * @since m2m
	 */
	private function update_relationship_fields( $association, $fields ) {
		$this->update_fields( $fields, $association->get_intermediary_id() );
	}


	/**
	 * Handle unchecked checkboxes
	 *
	 * @param array $fields
	 * @param IToolset_Association $association
	 * @param int $related_post_id
	 *
	 * @since
	 */
	private function update_unchecked_checkboxes( $fields, IToolset_Association $association, $related_post_id ) {
		$hidden_inputs_for_empty_checkboxes = toolset_ensarr( toolset_getpost( '_wptoolset_checkbox' ) );

		if ( array_key_exists( 'post', $fields ) ) {
			// hidden checkboxes inputs for related post (to uncheck / 0)
			$hidden_inputs_for_empty_checkboxes_post = toolset_ensarr(
				toolset_getarr( $hidden_inputs_for_empty_checkboxes, 'post' )
			);

			foreach ( array_keys( $hidden_inputs_for_empty_checkboxes_post ) as $slug ) {
				wpcf_fields_checkbox_update_one( $related_post_id, $slug, $fields['post'] );
			}
		}

		if ( array_key_exists( 'relationship', $fields ) ) {
			// hidden checkboxes inputs for intermediary post (to uncheck / 0)
			$hidden_inputs_for_empty_checkboxes_relationship = toolset_ensarr(
				toolset_getarr( $hidden_inputs_for_empty_checkboxes, 'relationship' )
			);
			foreach ( array_keys( $hidden_inputs_for_empty_checkboxes_relationship ) as $slug ) {
				wpcf_fields_checkbox_update_one( $association->get_intermediary_id(), $slug, toolset_ensarr( $fields['relationship'] ) );
			}
		}
	}


	/**
	 * Updates fields values
	 *
	 * @param array $fields Array of fields pairs: slug => value.
	 * @param int $post_id The fields' post ID.
	 *
	 * @since m2m
	 */
	private function update_fields( $fields, $post_id ) {
		foreach ( $fields as $slug => $field_value ) {
			// Since Types 3.4.11, if a custom field is hidden conditionally, we're going to reset it upon saving.
			// The field default value (or empty value) will be used.
			if ( $this->is_field_hidden_conditionally( $slug, $post_id, $fields ) ) {
				$field_value = types_is_repetitive( $slug ) ? [ '' ] : '';
			}

			if ( types_is_repetitive( $slug ) ) {
				$my_field = new WPCF_Repeater();
			} else {
				$my_field = new WPCF_Field();
			}
			$my_field->set( $post_id, wpcf_admin_fields_get_field( $slug ) );
			$my_field->save( $field_value );
		}
	}


	private function is_field_hidden_conditionally( $field_slug, $post_id, $form_data ) {
		$field_definition = Toolset_Field_Definition_Factory_Post::get_instance()->load_field_definition( $field_slug );
		if ( ! $field_definition ) {
			return false;
		}

		$field_config = wptoolset_form_filter_types_field( $field_definition->get_definition_array(), $post_id, $form_data );
		return ! wptoolset_form_conditional_check( $field_config );
	}


	/**
	 * Updates the flag for editing post fields
	 * It uses Transient API for store it.
	 *
	 * @since m2m
	 */
	private function enable_editing_fields() {
		$association_is_enabled = $this->direct_edit_status_factory->create( get_current_user_id() );
		$association_is_enabled->set( true );
		$this->get_ajax_manager()->ajax_finish(
			array(
				'message' => 'OK',
			), true
		);
	}


	/**
	 * Do disconnect association
	 *
	 * @return Toolset_Result
	 * @since m2m
	 */
	private function do_disconnect_association() {
		$association = $this->get_association();

		if ( ! $association ) {
			$this->fail( __( 'The association you are trying to disconnect doesn\'t exist, perhaps it has been disconnected before. Please, refresh the page and try again.', 'wpcf' ) );
		}
		$definition = $association->get_definition();
		$driver = $definition->get_driver();

		return $driver->delete_association( $association );
	}


	/**
	 * Disconnect association
	 * It uses the driver to remove the association and intermediary post if exists.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function disconnect_association() {
		$result = $this->do_disconnect_association();

		$can_connect_another = $this->can_connect_another();

		if ( $result->is_success() ) {
			$this->get_ajax_manager()->ajax_finish(
				array(
					'message' => __( 'Association disconnected successfully.', 'wpcf' ),
					'canConnectAnother' => $can_connect_another,
				), true
			);
		} else {
			$this->fail( __( 'Something was wrong, please try again.', 'wpcf' ) );
		}
	}


	/**
	 * Trash the related post and its translations.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function trash_related_posts() {
		$post_id = toolset_getpost( 'post_id' );
		if ( ! $post_id ) {
			$this->fail( __( 'Post not found.', 'wpcf' ) );
		}

		$post_translations = $this->wpml_service->get_post_translations_directly( $post_id );
		// If $post_translations is not empty, it will also include $post_id.
		foreach ( $post_translations as $post_translation_id ) {
			wp_trash_post( $post_translation_id );
		}
		if ( empty( $post_translations ) ) {
			// If there is no WPML or the post type is not translatable, we need to delete at least the
			// one given post.
			wp_trash_post( $post_id );
		}

		$this->get_ajax_manager()->ajax_finish(
			array(
				'message' => __( 'Related post moved to Trash successfully.', 'wpcf' ),
				'canConnectAnother' => $this->can_connect_another(),
			),
			true
		);
	}


	/**
	 * Un-trashes a related post.
	 */
	private function restore_related_post_from_trash() {
		$post_id = toolset_getpost( 'post_id' );
		if ( ! $post_id ) {
			$this->fail( __( 'Post not found.', 'wpcf' ) );
		}

		wp_untrash_post( $post_id );

		$this->get_ajax_manager()->ajax_finish(
			array( 'message' => __( 'Related post restored from Trash.', 'wpcf' ) )
		);

	}


	/**
	 * Get a list of related post types.
	 *
	 * It is used when the user wants to connect a existing post and search for an specific post using a select2 combo
	 *
	 * @since m2m
	 */
	private function search_related_content() {
		$relationship_definition = $this->get_definition();
		$post_type = toolset_getpost( 'post_type' );

		if ( ! $relationship_definition instanceof IToolset_Relationship_Definition ) {
			$this->fail( __( 'Something went wrong when fetching the related content: Couldn\'t load the relationship.', 'wpcf' ) );
		}

		// For now, the assumption about the domain and number of types is safe.
		$parent_post_types = $relationship_definition->get_element_type( Toolset_Relationship_Role::PARENT )
			->get_types();
		$parent_post_type = array_pop( $parent_post_types );

		$target_role = (
		$post_type === $parent_post_type
			? new Toolset_Relationship_Role_Parent()
			: new Toolset_Relationship_Role_Child()
		);

		try {
			$current_post = $this->element_factory->get_post(
				(int) toolset_getpost( 'current_post_id' )
			);
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			$this->get_ajax_manager()->ajax_finish( array( 'items' => array() ), false );

			return;
		}

		$requested_page = (int) toolset_getpost( 'page', 1 );

		$query_args_builder = new Toolset_Potential_Association_Query_Arguments();
		$query_args_builder
			->addFilter( new Toolset_Potential_Association_Query_Filter_Search_String() )
			->addFilter(
				new Types_Potential_Association_Query_Filter_Posts_Author_For_New_Association( $relationship_definition, $target_role )
			)->addFilter(
				new Types_Potential_Association_Query_Filter_Posts_Status( $relationship_definition, $target_role )
			);

		$query_args = $query_args_builder->get();
		$query_args['page'] = $requested_page;
		$query_args['items_per_page'] = self::SEARCH_RESULTS_PER_PAGE;
		$query_args['count_results'] = true;

		$query = $this->relationships_factory->potential_association_query(
			$relationship_definition, $target_role, $current_post, $query_args
		);

		/** @var IToolset_Post[] $posts */
		$posts = $query->get_results();

		$user = wp_get_current_user();
		$user_access = new Access( $user );
		$user_can_edit_any = $user_access->canEditAny( $post_type );
		$user_can_edit_own = $user_access->canEditOwn( $post_type );

		$formatted_posts = array();
		foreach ( $posts as $post ) {
			if ( ! $user_can_edit_any ) {
				// The user has somehow limited permissions when editing posts.
				if ( ! $user_can_edit_own ) {
					// Can't edit even own posts of this post type, so we bail.
					continue;
				}
				if ( (int) $post->get_underlying_object()->post_author !== $user->ID ) {
					// Can't edit this particular post.
					continue;
				}
			}

			$formatted_posts[] = $this->format_potential_association_post( $post );
		}

		$total_returned_items = self::SEARCH_RESULTS_PER_PAGE * $requested_page;
		$has_more_items = ( $query->get_found_elements() > $total_returned_items );

		$this->get_ajax_manager()->ajax_finish(
			array(
				'items' => $formatted_posts,
				'pagination' => array(
					'more' => $has_more_items,
				),
			),
			true
		);
	}


	/**
	 * Prepare data for a select2 option representing a single post.
	 *
	 * Behaviour slightly differs depending on WPML status and relationship database layer version.
	 *
	 * @param IToolset_Post $post
	 *
	 * @return array
	 */
	private function format_potential_association_post(
		IToolset_Post $post
	) {
		$formatted_post = [
			'id' => $post->get_id(),
			'text' => $post->get_title(),
		];

		$is_disabled = false;

		if ( $this->wpml_service->is_wpml_active_and_configured() ) {
			$formatted_post['languageFlagUrl'] = $this->wpml_service->get_language_flag_url( $post );

			if (
				$this->relationships_factory->database_operations()->requires_default_language_post()
				&& ! $this->wpml_service->has_default_language_translation( $post->get_id() )
			) {
				$is_disabled = true;
				$formatted_post['translationLink'] = [
					'url' => esc_attr( apply_filters(
						'wpml_get_link_to_edit_translation', '', $post->get_id(), $this->wpml_service->get_default_language()
					) ),
					'label' => _x( 'Translate', 'related_content', 'wpcf' ),
					'tooltipText' => esc_html__( 'Needs to be translated to default language', 'wpcf' ),
				];
			}
		}

		$formatted_post['disabled'] = $is_disabled;

		return $formatted_post;
	}


	/**
	 * Loads related content depending of the relationship definition and a page number or search string
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist|LoaderError
	 * @since m2m
	 */
	private function load_related_content() {

		$relationship = $this->get_definition();
		if ( ! $relationship ) {
			$this->fail( __( 'Relationship not found.', 'wpcf' ) );
		}

		$related_post_type = toolset_getpost( 'related_post_type' );
		$parent_type = $relationship->get_parent_type()->get_types();
		$child_type = $relationship->get_child_type()->get_types();

		if ( ! in_array( $related_post_type, $parent_type, true )
			&& ! in_array( $related_post_type, $child_type, true )
		) {
			$this->fail( __( 'Invalid related post type.', 'wpcf' ) );
		}

		$role = in_array( $related_post_type, $relationship->get_parent_type()->get_types(), true )
			? Toolset_Relationship_Role::CHILD
			: Toolset_Relationship_Role::PARENT;

		$post_id = toolset_getpost( 'post_id' );
		$post = get_post( $post_id );

		// Many to many relationships where both CPT are the same, the role must be parent.
		if ( $post->post_type === $related_post_type ) {
			$role = Toolset_Relationship_Role::PARENT;
		}

		$items_per_page = toolset_getpost( 'items_per_page' );
		$page_number = toolset_getpost( 'page' );

		// Sorting.
		$sort = toolset_getpost( 'sort', 'ASC' );
		$sort_by = toolset_getpost( 'sort_by', 'displayName' );
		$sort_origin = toolset_getpost( 'sort_origin', 'post_title' );

		$related_content_viewmodel = $this->get_related_content_viewmodel( $role, $relationship );
		$related_content = $related_content_viewmodel->get_related_content_array(
			(int) $post_id, $post->post_type, $page_number, $items_per_page, $role, $sort, $sort_by, $sort_origin
		);
		$related_content['items_found'] = $related_content_viewmodel->get_rows_found();

		foreach ( $related_content['data'] as $i => $item ) {
			// Formats the fields into preview and input render modes.
			$fields = $item['fields'];
			// Modify previous data.
			$related_content['data'][ $i ]['fields'] = $this->format_field_data( $fields, $item['association_uid'] );
		}
		$this->get_ajax_manager()->ajax_finish(
			array(
				'relatedContent' => $related_content,
				'canConnectAnother' => $this->can_connect_another(),
			), true
		);
	}


	/**
	 * Gets translatable content for displaying in the confirmation dialog.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function get_translatable_content() {
		$definition = $this->get_definition();
		$translatable_posts = array();
		if ( $definition->is_translatable() && $this->wpml_service->is_wpml_active_and_configured() ) {
			$association = $this->get_association();
			if ( ! $association ) {
				$this->fail( __( 'The association you are trying to disconnect doesn\'t exist, perhaps it has been disconnected before. Please, refresh the page and try again.', 'wpcf' ) );

				return;
			}
			$elements = array(
				$association->get_element( new Toolset_Relationship_Role_Parent() ),
				$association->get_element( new Toolset_Relationship_Role_Child() ),
			);
			$language_flags = $this->get_language_flags();
			$current_language = $this->wpml_service->get_current_language();
			foreach ( $elements as $element ) {
				if ( ! $element instanceof IToolset_Post ) {
					continue;
				}
				$post_type = $this->post_type_repository->get( $element->get_type() );
				$post_type_name = $post_type
					? $post_type->get_label( Toolset_Post_Type_Labels::NAME )
					: '';
				$id = $element->get_id();
				$titles = array();
				$translated_ids = $this->wpml_service->get_post_translations_directly( $id );
				$current_language_title = array();
				foreach ( $translated_ids as $lang => $tid ) {
					$item = array(
						'title' => get_the_title( $tid ),
						'flag' => array_key_exists( $lang, $language_flags )
							? sprintf(
								'<img src="%s" class="types-language-flag" />',
								esc_attr( $language_flags[ $lang ] )
							)
							: '',
					);
					// Default language is stored in a different var, so it can be placed in first position.
					if ( $lang === $current_language ) {
						$current_language_title[] = $item;
					} else {
						$titles[] = $item;
					}
				}
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$titles = array_merge( $current_language_title, $titles );
				$translatable_posts[ $post_type_name ] = $titles;
			}
		}
		$this->get_ajax_manager()->ajax_finish(
			array(
				'translatablePosts' => $translatable_posts,
			), true
		);
	}


	/**
	 * Gets an array with language flags links
	 *
	 * @return array
	 * @since m2m
	 */
	private function get_language_flags() {
		$flags = array();
		$languages = Toolset_Wpml_Utils::get_active_languages();
		if ( is_array( $languages ) && ! empty( $languages ) ) {
			foreach ( $languages as $lang => $info ) {
				$flags[ $lang ] = $info['country_flag_url'];
			}
		}

		return $flags;
	}


	/**
	 * Gets the relationship definition
	 *
	 * @return IToolset_Relationship_Definition|null
	 * @since m2m
	 */
	private function get_definition() {
		if ( $this->definition ) {
			return $this->definition;
		}
		$relationship_slug = toolset_getpost( 'relationship_slug' );

		if ( $relationship_slug ) {
			$this->definition = $this->definition_repository->get_definition( $relationship_slug );
		} else {
			$association = $this->get_association();
			if ( ! $association ) {
				return null;
			}
			$this->definition = $association->get_definition();
		}

		return $this->definition;
	}


	/**
	 * Gets the association
	 *
	 * @return IToolset_Association|null
	 * @since m2m
	 */
	private function get_association() {
		if ( $this->association ) {
			return $this->association;
		}
		$association_query = $this->relationships_factory->association_query();

		// disable default conditions to make this work without having the post saved
		$association_query->do_not_add_default_conditions();

		$association_uid = toolset_getpost( 'association_uid' );
		$association_query->add( $association_query->association_id( $association_uid ) );
		$association = $association_query->get_results();
		if ( empty( $association ) ) {
			return null;
		}

		$this->association = $association[0];

		return $this->association;
	}


	/**
	 * Handles error messages
	 *
	 * @param string $message The error message.
	 * @param string $type The message type.
	 *
	 * @since m2m
	 */
	private function fail( $message, $type = 'error' ) {
		$this->get_ajax_manager()->ajax_finish( array(
			'message' => $message,
			'messageType' => $type,
		), false );
	}


	/**
	 * Checks if the relationships admits another association
	 *
	 * @return boolean
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	private function can_connect_another() {
		if ( isset( $this->can_connect ) ) {
			return $this->can_connect;
		}
		$definition = $this->get_definition();
		$post_id = toolset_getpost( 'post_id' );
		$post = get_post( $post_id );
		$role = $this->get_role_from_post_type( $definition, $post->post_type );
		// The role beloinging to the related post.
		$other_role = Toolset_Relationship_Role::other( $role );

		$post_element = $this->element_factory->get_post( $post_id );
		$target_role = Toolset_Relationship_Role::PARENT === $other_role
			? new Toolset_Relationship_Role_Parent()
			: new Toolset_Relationship_Role_Child();
		$potential_association_query = $this->relationships_factory->potential_association_query(
			$definition, $target_role, $post_element
		);
		$can_connect_another = $potential_association_query->can_connect_another_element();

		return $can_connect_another->is_success();
	}


	/**
	 * Checks if a list of post types can be used in a relationship
	 * If a post type fails Ajax call will return an error
	 *
	 * @param string[] $post_types List of post type slugs.
	 */
	private function check_can_be_used( $post_types ) {
		foreach ( $post_types as $other_type ) {
			$post_type_object = $this->post_type_repository->get( $other_type );
			if ( $post_type_object && $post_type_object->can_be_used_in_relationship()->is_error() ) {
				$this->fail( sprintf( __( 'Post type %s can not be used in a relationship.', 'wpcf' ), $post_type_object->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME ) ) );
			}
		}
	}


	/**
	 * Updates visible fields in the related content metabox
	 *
	 * @since m2m
	 */
	private function update_fields_displayed() {
		$fields_post = toolset_getpost( 'field-post', array() );
		$fields_relationship = toolset_getpost( 'field-relationship', array() );
		$fields_related_posts = toolset_getpost( 'field-relatedPosts', array() );
		$post_type = toolset_getpost( 'post_type' );
		$definition = $this->get_definition();
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $definition ) {
			$this->fail( __( 'Wrong relationship.', 'wpcf' ) );
		}
		if ( ! $post_type_object ) {
			$this->fail( __( 'Wrong post type.', 'wpcf' ) );
		}
		$ipt = $definition->get_intermediary_post_type();
		$data = array(
			$post_type => array( $fields_post, $fields_related_posts ),
			$ipt => array( $fields_relationship ),
		);
		$classes = array(
			$post_type => array(
				'Types_Post_Type_Relationship_Settings',
				'Types_Post_Type_Relationship_Related_Posts_Settings',
			),
			$ipt => array( 'Types_Post_Type_Relationship_Settings' ),
		);
		foreach ( $data as $_post_type => $fields_list ) {
			foreach ( $fields_list as $i => $fields ) {
				$relationship_settings = new $classes[ $_post_type ][ $i ]( $_post_type, $definition );
				$relationship_settings->set_fields_list_related_content( $fields );
				$relationship_settings->save_data();
			}
		}
		$result = array(
			'post' => $fields_post,
			'relationship' => $fields_relationship,
			'relatedPosts' => $fields_related_posts,
		);
		$this->get_ajax_manager()->ajax_finish( $result, true );
	}


	/**
	 * Checks if it is an unsafe action. For unsage I mean not permissions are required.
	 *
	 * @param string $action Ajax action.
	 *
	 * @return boolean
	 * @since 3.0
	 */
	private function is_safe_action( $action ) {
		return in_array( $action, $this->unsafe_actions, true );
	}
}
