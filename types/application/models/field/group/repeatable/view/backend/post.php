<?php

use OTGS\Toolset\Types\Controller\Compatibility\Gutenberg;

/**
 * This class only adds the template scripts, based on the user view choice (vertical or horizontal).
 * The container of the repeatable group fields is added in the legacy code.
 * See: vendor/toolset/types/embedded/includes/fields-post.php (look for: "// repeatable field group container")
 *
 * @since 2.3
 */
class Types_Field_Group_Repeatable_View_Backend_Post {

	const KEY_USER_META_GROUP_VIEW = 'toolset-rg-view';

	const KEY_VERTICAL_VIEW = 'vertical';

	const KEY_HORIZONTAL_VIEW = 'horizontal';

	const KEY_GET_VIEW_SETTING = 'rgview';

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var string (KEY_VERTICAL_VIEW|KEY_HORIZONTAL_VIEW)
	 */
	private $user_view_choice;

	/**
	 * @var string
	 */
	private $post_type;


	/**
	 * Types_Field_Group_Repeatable_View_Backend_Post constructor.
	 *
	 * @param int $user_id
	 * @param string $post_type
	 */
	public function __construct( $user_id, $post_type ) {
		$this->user_id = $user_id;
		$this->post_type = $post_type;
	}


	/**
	 * Scritps and Styles
	 */
	public function on_admin_enqueue_scripts() {
		if ( function_exists( 'wpcf_edit_post_screen_scripts' ) ) {
			wpcf_edit_post_screen_scripts();
		}

		WPToolset_Field_File::file_enqueue_scripts();

		$main_handle = 'types-repeatable-group';

		// rfg css
		wp_enqueue_style(
			$main_handle,
			TYPES_RELPATH . '/public/page/edit_post/rfg.css',
			array(),
			TYPES_VERSION
		);

		// rfg js
		$script_dependencies = array(
			'jquery',
			Types_Asset_Manager::SCRIPT_KNOCKOUT_MAPPING,
			Types_Asset_Manager::SCRIPT_UTILS,
			Types_Asset_Manager::SCRIPT_TINYMCE_COMPATIBILITY,
			Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
			// These scripts are required but won't be included by the legacy code if there are no custom fields
			// outside of a RFG or a related content metabox.
			Toolset_Assets_Manager::SCRIPT_WPTOOLSET_FORM_CONDITIONAL,
			Toolset_Assets_Manager::SCRIPT_WPTOOLSET_FORM_VALIDATION,
		);

		// See https://core.trac.wordpress.org/ticket/45289. */
		$dic = toolset_dic();
		/** @var Gutenberg $gutenberg */
		/** @noinspection PhpUnhandledExceptionInspection */
		$gutenberg = $dic->make( Gutenberg::class );

		if ( $gutenberg->is_active_for_current_post_type() ) {
			$script_dependencies[] = 'wp-editor';
		}
		/* END DELETE */

		wp_enqueue_script(
			$main_handle,
			TYPES_RELPATH . '/public/page/edit_post/rfg.js',
			$script_dependencies,
			TYPES_VERSION
		);

		wp_enqueue_editor();

		// colorpicker js
		// fields are loaded dynamically via ajax, so we have to load colorpicker js
		// for the case rfg has nested colorpicker fields
		wp_enqueue_script(
			'wptoolset-field-colorpicker', // don't change to make sure it's not loaded twice
			WPTOOLSET_FORMS_RELPATH . '/js/colorpicker.js',
			array( 'iris' ),
			WPTOOLSET_FORMS_VERSION,
			true
		);

		// Make sure generic assets related to input forms are loaded even if there are no custom fields
		// on the page (outside of a RFG)
		require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.form_factory.php';
		new FormFactory( 'post' );

		// form conditional js
		WPToolset_Forms_Conditional::load_scripts();
	}


	/**
	 * Print JS
	 */
	public function print_js_data() {
		echo '<script id="types_rfg_model_data" type="text/plain">'
			. base64_encode( wp_json_encode( $this->build_js_data() ) )
			. '</script>';

		// templates for repeatable field group
		switch ( $this->get_user_view_choice() ) {
			case self::KEY_HORIZONTAL_VIEW:
				require_once( TYPES_ABSPATH
					. '/application/views/field/group/repeatable/backend/post-edit/horizontal/templates.phtml' );
				break;
			case self::KEY_VERTICAL_VIEW:
				require_once( TYPES_ABSPATH
					. '/application/views/field/group/repeatable/backend/post-edit/vertical/templates.phtml' );
				break;
			default:
				/** @noinspection ForgottenDebugOutputInspection */
				error_log( 'The view choice "' . $this->get_user_view_choice() . '" is not supported.' );
		}
	}


	/**
	 * @return string
	 */
	public function get_user_view_choice() {
		if ( $this->user_view_choice === null ) {
			$this->user_view_choice = $this->fetch_and_store_user_view_choice();
		}

		return $this->user_view_choice;
	}


	/**
	 * @return string
	 */
	private function fetch_and_store_user_view_choice() {
		$user_group_views = $user_group_views_db = get_user_meta( $this->user_id, self::KEY_USER_META_GROUP_VIEW,
			true );

		if ( ! is_array( $user_group_views ) ) {
			// no meta for repeatable views stored yet
			$user_group_views = array();
		}

		if ( ! isset( $user_group_views[ $this->post_type ] ) ) {
			// no view selected for current post type yet, use 'vertical' as default
			$user_group_views[ $this->post_type ] = self::KEY_VERTICAL_VIEW;
			$user_group_views = array( $this->post_type => self::KEY_VERTICAL_VIEW );
		}

		if ( isset( $_REQUEST[ self::KEY_GET_VIEW_SETTING ] )
			&& (
				self::KEY_HORIZONTAL_VIEW === $_REQUEST[ self::KEY_GET_VIEW_SETTING ]
				|| self::KEY_VERTICAL_VIEW === $_REQUEST[ self::KEY_GET_VIEW_SETTING ]
			)
		) {
			$user_group_views[ $this->post_type ] = $_REQUEST[ self::KEY_GET_VIEW_SETTING ];
		}

		if ( $user_group_views !== $user_group_views_db ) {
			// view not stored yet or user made a change
			update_user_meta( $this->user_id, self::KEY_USER_META_GROUP_VIEW, $user_group_views );
		}

		return $user_group_views[ $this->post_type ];
	}


	/**
	 * Build data to be passed to JavaScript.
	 *
	 * @return array
	 */
	private function build_js_data() {

		$types_settings_action = Types_Ajax::get_instance()
			->get_action_js_name( Types_Ajax::CALLBACK_REPEATABLE_GROUP );

		return array(
			'post_id' => isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : 0,
			'action' => array(
				'name' => $types_settings_action,
				'nonce' => wp_create_nonce( $types_settings_action ),
			),
			'yoastActive' => defined( 'TOOLSET_TYPES_YOAST' ) && TOOLSET_TYPES_YOAST,
		);
	}


	/**
	 * This will render the script templates and also initialize the load of scripts
	 *
	 * Should only be called if there really is an repeatable group to prevent loading unnecessary files.
	 */
	public function prepare() {
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_js_data' ) );

		// Fix for GUTENBERG, which has already triggered 'admin_enqueue_scripts' add this point
		// It's a known issue see: https://github.com/WordPress/gutenberg/issues/4929
		if ( did_action( 'admin_enqueue_scripts' ) ) {
			$this->on_admin_enqueue_scripts();
		}

		// also admin_print_scripts is already triggered by GUTENBERG out of order
		if ( did_action( 'admin_print_scripts' ) ) {
			add_action( 'admin_footer', array( $this, 'print_js_data' ) );
		}
	}


	/**
	 * This renders the container for the repeatable group.
	 *
	 * The items of the repeatable group will be loaded via ajax, this way we not slowing down
	 * the initial load of the post edit screen.
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 *
	 * @return string
	 * @noinspection PhpUnusedParameterInspection Used inside the template file!
	 */
	public function render( Types_Field_Group_Repeatable $repeatable_group ) {
		ob_start();
		include( TYPES_ABSPATH . '/application/views/field/group/repeatable/backend/post-edit/container.phtml' );

		return ob_get_clean();
	}


	/**
	 * Gets post type singular name
	 *
	 * @return string
	 * @since m2m
	 * @noinspection PhpUnusedPrivateMethodInspection Used in the template file!
	 */
	private function get_singular_post_type_label() {
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();
		$post_type = $post_type_repository->get( $this->post_type );

		return $post_type->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME );
	}
}
