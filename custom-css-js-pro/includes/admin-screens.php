<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_Admin
 */
class CustomCSSandJS_AdminPro {

	/**
	 * Array with the options for a specific custom-css-js post
	 */
	private $options = array();

	/**
	 * The settings array
	 */
	private $settings = array();

	/**
	 * Constructor
	 */
	public function __construct() {

        require dirname( CCJ_PLUGIN_FILE_PRO ) . '/includes/vendor/autoload.php';

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$free_version = 'custom-css-js/custom-css-js.php';
		if ( is_plugin_active( $free_version ) ) {
			deactivate_plugins( $free_version );
			return false;
		}

		$this->add_functions();
		$this->edd_updater();
	}

	/**
	 * Add actions and filters
	 */
	function add_functions() {

		// Add filters
		$filters = array(
			'manage_custom-css-js_posts_columns' => 'manage_custom_posts_columns',
		);
		foreach ( $filters as $_key => $_value ) {
			add_filter( $_key, array( $this, $_value ) );
		}

		// Add actions
		$actions = array(
			'admin_menu'                 => 'admin_menu',
			'admin_enqueue_scripts'      => 'admin_enqueue_scripts',
			'current_screen'             => 'current_screen',
			'admin_notices'              => 'create_uploads_directory',
			'edit_form_after_title'      => 'codemirror_editor',
			'add_meta_boxes'             => 'add_meta_boxes',
			'save_post'                  => 'options_save_meta_box_data',
			'trashed_post'               => 'trash_post',
			'untrashed_post'             => 'trash_post',
			'admin_post_ccj-autosave'    => 'ajax_autosave',
			'wp_loaded'                  => 'compatibility_shortcoder',
			'wp_ajax_ccj_active_code'    => 'wp_ajax_ccj_active_code',
			'post_submitbox_start'       => 'post_submitbox_start',
			'restrict_manage_posts'      => 'restrict_manage_posts',
			'load-post.php'              => 'contextual_help',
			'load-post-new.php'          => 'contextual_help',
			'edit_form_before_permalink' => 'edit_form_before_permalink',
			'wp_ajax_ccj_permalink'      => 'wp_ajax_ccj_permalink',
			'before_delete_post'         => 'before_delete_post',
		);
		foreach ( $actions as $_key => $_value ) {
			add_action( $_key, array( $this, $_value ) );
		}

		$this->settings = get_option( 'ccj_settings', array() );

		// Set the linting option
		if ( is_array( $this->settings ) && count( $this->settings ) > 0 ) {
			$update = false;
			if ( ! isset( $this->settings['lint'] ) ) {
				$update         = true;
				$this->settings = $this->settings + array( 'lint' => true );
			}

			if ( ! isset( $this->settings['autocomplete'] ) ) {
				$update         = true;
				$this->settings = $this->settings + array( 'autocomplete' => true );
			}

			if ( ! isset( $this->settings['ccj_editor_theme'] ) ) {
				$update         = true;
				$this->settings = $this->settings + array( 'ccj_editor_theme' => 'default' );
			}
			if ( $update ) {
				update_option( 'ccj_settings', $this->settings );
			}
		}

		// Add some custom actions
		add_action( 'manage_custom-css-js_posts_custom_column', array( $this, 'manage_posts_columns' ), 10, 2 );
		add_filter( 'manage_edit-custom-css-js_sortable_columns', array( $this, 'manage_edit_posts_sortable_columns' ) );
		add_action( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
		add_action( 'posts_join_paged', array( $this, 'posts_join_paged' ), 10, 2 );
		add_action( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10, 2 );
		add_action( 'save_post', array( $this, 'revision_save_meta_box_data' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
		add_filter( 'parse_query', array( $this, 'parse_query' ), 10 );
		add_filter( 'frou_ignored_extensions', array( $this, 'file_renaming_extensions' ), 20 );
		add_filter( 'wp_statuses_get_supported_post_types', array( $this, 'wp_statuses_get_supported_post_types' ), 20 );

		add_action( 'current_screen', array( $this, 'current_screen_2' ), 100 );
	}


	function ajax_autosave() {

		check_admin_referer( 'ccj-nonce' );

		if ( ! isset( $_POST['content'] ) || ! isset( $_POST['title'] ) ) {
			$ajax = new WP_AJAX_Response( array( 'data' => -1 ) );
			$ajax->send();
			return;
		}

		if ( isset( $_POST['data'] ) ) {
			wp_autosave( $_POST );
			$ajax = new WP_AJAX_Response( array( 'data' => $_POST['data'] . ' => ' . $_POST['data2'] ) );
			$ajax->send();
			return;
		}
	}


	/**
	 * Add submenu pages
	 */
	function admin_menu() {
		$menu_slug    = 'edit.php?post_type=custom-css-js';
		$submenu_slug = 'post-new.php?post_type=custom-css-js';

		remove_submenu_page( $menu_slug, $submenu_slug );

		$title = __( 'Add Custom CSS', 'custom-css-js-pro' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_custom_csss', $submenu_slug . '&#038;language=css' );

		$title = __( 'Add Custom JS', 'custom-css-js-pro' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_custom_csss', $submenu_slug . '&#038;language=js' );

		$title = __( 'Add Custom HTML', 'custom-css-js-pro' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_custom_csss', $submenu_slug . '&#038;language=html' );
	}


	/**
	 * Enqueue the scripts and styles
	 */
	public function admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();

		// Only for custom-css-js post type
		if ( $screen->post_type != 'custom-css-js' ) {
			return false;
		}

		// Some handy variables
		$a  = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets';
		$cm = $a . '/codemirror';
		$v  = CCJ_VERSION_PRO;

		// If Toolset Types plugin is enabled, remove the codemirror script
		global $wp_scripts;
		if ( is_plugin_active( 'types/wpcf.php' ) ) {
			if ( isset( $wp_scripts->registered['toolset-codemirror-script'] ) ) {
				unset( $wp_scripts->registered['toolset-codemirror-script'] );
			}
		}

		// Enqueue scripts and styles
		wp_enqueue_script( 'ccj-tipsy', $a . '/jquery.tipsy.js', array( 'jquery' ), $v, false );
		wp_enqueue_style( 'ccj-tipsy', $a . '/tipsy.css', array(), $v );
		wp_enqueue_script( 'ccj-cookie', $a . '/js.cookie.js', array( 'jquery' ), $v, false );
		wp_register_script( 'ccj-admin', $a . '/ccj_admin.js', array( 'jquery', 'ccj-tipsy', 'jquery-ui-resizable' ), $v, false );
		wp_localize_script( 'ccj-admin', 'CCJ', $this->cm_localize() );
		wp_enqueue_script( 'ccj-admin' );
		wp_enqueue_style( 'ccj-admin', $a . '/ccj_admin.css', array(), $v );

		if ( $hook == 'custom-css-js_page_custom-css-js-config' ) {
			wp_enqueue_script( 'ccj-bootstrap', $a . '/bootstrap.min.js', array( 'jquery' ), $v, true );
			wp_enqueue_style( 'ccj-bootstrap', $a . '/bootstrap.min.css', array(), $v );
			wp_enqueue_style( 'ccj-admin_settings', $a . '/ccj_admin_settings.css', array(), $v );
		}

		// Only for the new/edit Code's page
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			wp_deregister_script( 'wp-codemirror' );

			// wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css', array(), $v );
			wp_enqueue_script( 'ccj-codemirror', $cm . '/lib/codemirror.js', array( 'jquery' ), $v, false );
			wp_enqueue_script( 'ccj-addon_merge_match_patch', $cm . '/lib/diff_match_patch.js', array( 'ccj-codemirror' ), $v, false );

			wp_enqueue_style( 'ccj-codemirror', $cm . '/lib/codemirror.css', array(), $v );
			wp_enqueue_script( 'ccj-admin_url_rules', $a . '/ccj_admin-url_rules.js', array( 'jquery' ), $v, false );

			// Add the language modes
			$cmm = $cm . '/mode/';
			wp_enqueue_script( 'ccj-xml', $cmm . 'xml/xml.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-js', $cmm . 'javascript/javascript.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-css', $cmm . 'css/css.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-htmlmixed', $cmm . 'htmlmixed/htmlmixed.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-clike', $cmm . 'clike/clike.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-php', $cmm . 'php/php.js', array( 'ccj-codemirror' ), $v, false );

			// Other addons
			$cma = $cm . '/addon/';
			wp_enqueue_script( 'ccj-dialog', $cma . 'dialog/dialog.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-search', $cma . 'search/search.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-searchcursor', $cma . 'search/searchcursor.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-jump-to-line', $cma . 'search/jump-to-line.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fullscreen', $cma . 'display/fullscreen.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-merge', $cma . 'merge/merge.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_style( 'ccj-dialog', $cma . 'dialog/dialog.css', array(), $v );
			wp_enqueue_style( 'ccj-merge', $cma . 'merge/merge.css', array(), $v );
			wp_enqueue_script( 'ccj-formatting', $cm . '/lib/util/formatting.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-comment', $cma . 'comment/comment.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-continuecomment', $cma . 'comment/continuecomment.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-active-line', $cma . 'selection/active-line.js', array( 'ccj-codemirror' ), $v, false );

			// Hint Addons
			wp_enqueue_script( 'ccj-hint', $cma . 'hint/show-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-hint-js', $cma . 'hint/javascript-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-hint-xml', $cma . 'hint/xml-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-hint-html', $cma . 'hint/html-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-hint-css', $cma . 'hint/css-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-hint-anyword', $cma . 'hint/anyword-hint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_style( 'ccj-hint', $cma . 'hint/show-hint.css', array(), $v );
			wp_enqueue_script( 'ccj-closebrackets', $cma . 'edit/closebrackets.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-matchbrackets', $cma . 'edit/matchbrackets.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-matchtags', $cma . 'edit/matchtags.js', array( 'ccj-codemirror' ), $v, false );

			// Fold Addons
			wp_enqueue_script( 'ccj-fold-brace', $cma . 'fold/brace-fold.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-comment', $cma . 'fold/comment-fold.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-code', $cma . 'fold/foldcode.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-gutter', $cma . 'fold/foldgutter.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-indent', $cma . 'fold/indent-fold.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-markdown', $cma . 'fold/markdown-fold.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-fold-xml', $cma . 'fold/xml-fold.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_style( 'ccj-fold-gutter', $cma . 'fold/foldgutter.css', array(), $v );

			// Lint Addons
			wp_enqueue_style( 'ccj-lint', $cma . 'lint/lint.css', array(), $v );
			wp_enqueue_script( 'ccj-lint', $cma . 'lint/lint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lints-js', $cma . 'lint-vendors/jshint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lints-css', $cma . 'lint-vendors/csslint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lints-html', $cma . 'lint-vendors/htmlhint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lints-scss', $cma . 'lint-vendors/scsslint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lint-js', $cma . 'lint/javascript-lint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lint-css', $cma . 'lint/css-lint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lint-html', $cma . 'lint/html-lint.js', array( 'ccj-codemirror' ), $v, false );
			wp_enqueue_script( 'ccj-lint-scss', $cma . 'lint/scss-lint.js', array( 'ccj-codemirror' ), $v, false );

			// remove the assets from other plugins so it doesn't interfere with CodeMirror
			global $wp_scripts;
			if ( is_array( $wp_scripts->registered ) && count( $wp_scripts->registered ) != 0 ) {
				foreach ( $wp_scripts->registered as $_key => $_value ) {
					if ( ! isset( $_value->src ) ) {
						continue;
					}

					if ( strstr( $_value->src, 'wp-content/plugins' ) !== false
					&& strstr( $_value->src, 'plugins/custom-css-js-pro/assets' ) === false
					&& strstr( $_value->src, 'plugins/advanced-custom-fields/' ) === false
					&& strstr( $_value->src, 'plugins/wp-jquery-update-test/' ) === false
					&& strstr( $_value->src, 'plugins/enable-jquery-migrate-helper/' ) === false
					&& strstr( $_value->src, 'plugins/tablepress/' ) === false
					&& strstr( $_value->src, 'plugins/advanced-custom-fields-pro/' ) === false ) {
						unset( $wp_scripts->registered[ $_key ] );
					}
				}
			}
		}

		// remove the CodeMirror library added by the Product Slider for WooCommerce plugin by ShapedPlugin
		wp_enqueue_style( 'spwps-codemirror', $a . '/empty.css', '1.0' );
		wp_enqueue_script( 'spwps-codemirror', $a . '/empty.js', array(), '1.0', true );

		// Load the editor's theme
		if ( isset( $this->settings['ccj_editor_theme'] ) && ! empty( $this->settings['ccj_editor_theme'] ) && $this->settings['ccj_editor_theme'] !== 'default' ) {
			$theme = $this->settings['ccj_editor_theme'];
			wp_enqueue_style( 'cmt-' . $theme, $cm . '/theme/' . $theme . '.css', array(), $v );
		}
	}


	/**
	 * Send variables to the ccj_admin.js script
	 */
	public function cm_localize() {

		$vars = array(
			'theme'          => isset( $this->settings['ccj_editor_theme'] ) ? $this->settings['ccj_editor_theme'] : 'default',
			'lint'           => isset( $this->settings['lint'] ) ? $this->settings['lint'] : true,
			'autocomplete'   => isset( $this->settings['autocomplete'] ) ? $this->settings['autocomplete'] : true,
			'active'         => __( 'Active', 'custom-css-js-pro' ),
			'inactive'       => __( 'Inactive', 'custom-css-js-pro' ),
			'activate'       => __( 'Activate', 'custom-css-js-pro' ),
			'deactivate'     => __( 'Deactivate', 'custom-css-js-pro' ),
			'active_title'   => __( 'The code is active. Click to deactivate it', 'custom-css-js-pro' ),
			'deactive_title' => __( 'The code is inactive. Click to activate it', 'custom-css-js-pro' ),

			/* Translations for the "Apply on these Pages" rules */
			'rules_i18n'     => array(
				'all'            => __( 'All Website', 'custom-css-js-pro' ),
				'first-page'     => __( 'Homepage', 'custom-css-js-pro' ),
				'contains'       => __( 'URL contains ..', 'custom-css-js-pro' ),
				'not-contains'   => __( 'URL not contains ..', 'custom-css-js-pro' ),
				'equal-to'       => __( 'URL is equal to ..', 'custom-css-js-pro' ),
				'not-equal-to'   => __( 'URL not equal to ..', 'custom-css-js-pro' ),
				'begins-with'    => __( 'URL starts with ..', 'custom-css-js-pro' ),
				'ends-by'        => __( 'URL ends by ..', 'custom-css-js-pro' ),
				'wp-conditional' => __( 'WP Conditional Tag ..', 'custom-css-js-pro' ),
			),

			/* CodeMirror options */
			'codemirror' => array(
				'indentUnit'       => 4,
				'indentWithTabs'   => true,
				'inputStyle'       => 'contenteditable',
				'lineNumbers'      => true,
				'lineWrapping'     => true,
				'styleActiveLine'  => true,
				'continueComments' => true,
				'extraKeys'        => array(
					'Ctrl-Space' => 'autocomplete',
					'Cmd-Space'  => 'autocomplete',
					'Ctrl-/'     => 'toggleComment',
					'Cmd-/'      => 'toggleComment',
					'Alt-F'      => 'findPersistent',
					'Ctrl-F'     => 'findPersistent',
					'Cmd-F'      => 'findPersistent',
					'Ctrl-J'     =>  'toMatchingTag',
				),
				'direction'        => 'ltr', // Code is shown in LTR even in RTL languages.
				'gutters'          => array( 'CodeMirror-lint-markers' ),
				'matchBrackets'    => true,
				'matchTags'        => array( 'bothTags' => true ),
				'autoCloseBrackets' => true,
				'autoCloseTags'    => true,
				'lint'             => isset( $this->settings['lint'] ) ? $this->settings['lint'] : true,
			),

			/* CSS linting options */
			'csslint'    => array(
				'errors'                    => true, // Parsing errors.
				'box-model'                 => true,
				'display-property-grouping' => true,
				'duplicate-properties'      => true,
				'known-properties'          => true,
				'outline-none'              => true,
			),

			/* SASS linting options */
			'sasslint' => array(
				'variable-for-property' => 0,
				'no-ids'                => 0,
				'indentation'           => 0,
				'no-important'          => 0,
			),

			/* JS linting options */
			'jshint'     => array(
				'boss'     => true,
				'curly'    => true,
				'eqeqeq'   => true,
				'eqnull'   => true,
				'expr'     => true,
				'immed'    => true,
				'noarg'    => true,
				'nonbsp'   => true,
				'onevar'   => true,
				'quotmark' => 'single',
				'trailing' => true,
				'undef'    => true,
				'unused'   => true,
				'browser'  => true,
				'globals'  => array(
					'_'        => false,
					'Backbone' => false,
					'jQuery'   => false,
					'JSON'     => false,
					'wp'       => false,
				),
			),

			/* HTML linting options */
			'htmlhint'   => array(
				'tagname-lowercase'        => true,
				'attr-lowercase'           => true,
				'attr-value-double-quotes' => false,
				'doctype-first'            => false,
				'tag-pair'                 => true,
				'spec-char-escape'         => true,
				'id-unique'                => true,
				'src-not-empty'            => true,
				'attr-no-duplication'      => true,
				'alt-require'              => true,
				'space-tab-mixed-disabled' => 'tab',
				'attr-unsafe-chars'        => true,
			),

		);

		return apply_filters( 'ccj_code_editor_settings', $vars);
	}


	public function add_meta_boxes() {
		$post_id  = isset( $_GET['post'] ) ? esc_attr( $_GET['post'] ) : false;
		$language = $this->get_language( $post_id );

		if ( 'html' === $language ) {
			add_meta_box( 'custom-code-options', __( 'Options', 'custom-css-js-pro' ) . ccj_a_doc( 'https://www.silkypress.com/simple-custom-css-js-pro-documentation/#doc-options' ), array( $this, 'custom_code_options_meta_box_callback_html' ), 'custom-css-js', 'side', 'low' );
		} else {
			add_meta_box( 'custom-code-options', __( 'Options', 'custom-css-js-pro' ), array( $this, 'custom_code_options_meta_box_callback' ), 'custom-css-js', 'side', 'low' );
		}

		remove_meta_box( 'slugdiv', 'custom-css-js', 'normal' );
	}


	/**
	 * Reformat the `edit` or the `post` screens
	 */
	function current_screen( $current_screen ) {

		if ( $current_screen->post_type != 'custom-css-js' ) {
			return false;
		}

		if ( $current_screen->base == 'post' ) {
			add_action( 'admin_head', array( $this, 'current_screen_post' ) );
		}

		if ( $current_screen->base == 'edit' ) {
			add_action( 'admin_head', array( $this, 'current_screen_edit' ) );
		}
	}



	/**
	 * Add the buttons in the `edit` screen
	 */
	function add_new_buttons() {
		$current_screen = get_current_screen();

		if ( ( isset( $current_screen->action ) && $current_screen->action == 'add' ) || $current_screen->post_type != 'custom-css-js' ) {
			return false;
		}
		?>
	<div class="updated buttons">
	<a href="post-new.php?post_type=custom-css-js&language=css" class="custom-btn custom-css-btn"><?php _e( 'Add CSS code', 'custom-css-js-pro' ); ?></a>
	<a href="post-new.php?post_type=custom-css-js&language=js" class="custom-btn custom-js-btn"><?php _e( 'Add JS code', 'custom-css-js-pro' ); ?></a>
	<a href="post-new.php?post_type=custom-css-js&language=html" class="custom-btn custom-js-btn"><?php _e( 'Add HTML code', 'custom-css-js-pro' ); ?></a>
	</div>
		<?php
	}



	/**
	 * Add new columns in the `edit` screen
	 */
	function manage_custom_posts_columns( $columns ) {
		return array(
			'cb'        => '<input type="checkbox" />',
			'active'    => '<span class="ccj-dashicons dashicons dashicons-star-empty" title="' . __( 'Active', 'custom-css-js-pro' ) . '"></span>',
			'type'      => __( 'Type', 'custom-css-js-pro' ),
			'title'     => __( 'Title', 'custom-css-js-pro' ),
			'author'    => __( 'Author', 'custom-css-js-pro' ),
			'published' => __( 'Published', 'custom-css-js-pro' ),
			'modified'  => __( 'Modified', 'custom-css-js-pro' ),
			'options'   => __( 'Options', 'custom-css-js-pro' ),
		);
	}


	/**
	 * Fill the data for the new added columns in the `edit` screen
	 */
	function manage_posts_columns( $column, $post_id ) {

		$options = ccj_get_options( $post_id );

		if ( 'type' === $column ) {
			echo '<span class="language language-' . $options['language'] . '">' . $options['language'] . '</span>';
		}

		if ( 'modified' === $column || 'published' === $column ) {
			$post = get_post( $post_id );

			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$t_time    = __( 'Unpublished' );
				$h_time    = $t_time;
				$time_diff = 0;
			} else {
				$time      = ( 'published' === $column ) ? get_post_time( 'U', false, $post ) : get_post_modified_time( 'U', false, $post );
				$time_diff = time() - $time;

				if ( $time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					/* translators: %s: Human-readable time difference. */
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
				} else {
					$h_time = ( 'published' === $column ) ? get_the_time( __( 'Y/m/d' ), $post ) : get_the_modified_time( __( 'Y/m/d' ), $post );
				}
			}

			 echo $h_time;
		}

		if ( 'active' === $column ) {
			$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=ccj_active_code&code_id=' . $post_id ), 'ccj-active-code-' . $post_id );
			if ( $this->is_active( $post_id ) ) {
				$active_title = __( 'The code is active. Click to deactivate it', 'custom-css-js-pro' );
				$active_icon  = 'dashicons-star-filled';
			} else {
				$active_title = __( 'The code is inactive. Click to activate it', 'custom-css-js-pro' );
				$active_icon  = 'dashicons-star-empty ccj_row';
			}
			echo '<a href="' . esc_url( $url ) . '" class="ccj_activate_deactivate" data-code-id="' . $post_id . '" title="' . $active_title . '">' .
				'<span class="dashicons ' . $active_icon . '"></span>' .
				'</a>';
		}

		if ( 'options' === $column ) {
			echo $this->options_overview( $post_id, $options );
		}
	}


	/**
	 * Make the 'Modified' column sortable
	 */
	function manage_edit_posts_sortable_columns( $columns ) {
		$columns['active'] = 'active';
		$columns['type'] = 'type';
		$columns['modified']  = 'modified';
		$columns['published'] = 'published';
		return $columns;

	}


	/**
	 * List table: Change the query in order to filter by code type
	 */
	function parse_query( $query ) {
		global $wpdb;
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $query;
		}

		if ( ! isset( $query->query['post_type'] ) ) {
			return $query;
		}

		if ( 'custom-css-js' !== $query->query['post_type'] ) {
			return $query;
		}

		$filter = filter_input( INPUT_GET, 'language_filter' );
		if ( ! is_string( $filter ) || strlen( $filter ) == 0 ) {
			return $query;
		}
		$filter = '%' . $wpdb->esc_like( $filter ) . '%';

		$post_id_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s";
		$post_ids      = $wpdb->get_col( $wpdb->prepare( $post_id_query, 'options', $filter ) );
		if ( ! is_array( $post_ids ) || count( $post_ids ) == 0 ) {
			$post_ids = array( -1 );
		}
		$query->query_vars['post__in'] = $post_ids;

		return $query;
	}


	/**
	 * If the "File Renaming on Upload" plugin is installed,
	 * then don't rename the files with the 'css', 'js', 'html' extensions
	 */
	function file_renaming_extensions( $extensions ) {
		return array_merge( $extensions, array( 'css', 'js', 'html' ) );
	}


	/**
	 * The "Publish"/"Update" button is missing if the "LH Archived Post Status" plugins is installed.
	 */
	function wp_statuses_get_supported_post_types( $post_types ) {
		unset( $post_types['custom-css-js'] );
		return $post_types;
	}


	/**
	 * List table: add a filter by code type
	 */
	function restrict_manage_posts( $post_type ) {
		if ( 'custom-css-js' !== $post_type ) {
			return;
		}

		$languages = array(
			'css'  => __( 'CSS Codes', 'custom-cs-js' ),
			'js'   => __( 'JS Codes', 'custom-css-js' ),
			'html' => __( 'HTML Codes', 'custom-css-js' ),
		);

		echo '<label class="screen-reader-text" for="custom-css-js-filter">' . esc_html__( 'Filter Code Type', 'custom-css-js' ) . '</label>';
		echo '<select name="language_filter" id="custom-css-js-filter">';
		echo '<option  value="">' . __( 'All Custom Codes', 'custom-css-js' ) . '</option>';
		foreach ( $languages as $_lang => $_label ) {
			$selected = selected( filter_input( INPUT_GET, 'language_filter' ), $_lang, false );
			echo '<option ' . $selected . ' value="' . $_lang . '">' . $_label . '</option>';
		}
		echo '</select>';
	}


	/**
	 * Show the active options as small icons with tooltips
	 */
	function options_overview( $post_id, $options ) {

		if ( $options['language'] == 'html' ) {
			$options_array = ccj_get_options_meta_html();
		} else {
			$options_array = ccj_get_options_meta();
		}


		// Remove custom JS codes from login page on subsites
		if ( $options['language'] == 'js' && is_multisite() && ! is_main_site() && ! get_site_option( 'ccj_multisite_js_loginpage', false ) ) {
			unset( $options_array['side']['values']['login'] );
		}


		$output = '';

		if ( $options['language'] == 'html' && $options['type'] == 'shortcode' ) {
			$output .= '<span class="dashicons dashicons-paperclip" rel="tipsy" title="' . __( 'Where on the page: As shortcode', 'custom-css-js-pro' ) . '"></span> ';
			if ( ! isset( $options['name'] ) || empty( $options['name'] ) ) {
				$output .= ' ' . __( 'Shortcode without id', 'custom-css-js-pro' );
			} else {
				$output .= ' [ccj id="' . $options['name'] . '"]';
			}
			if ( isset( $options['multisite'] ) && $options['multisite'] == true ) {
				$title   = $options_array['multisite']['title'];
				$output .= '<span class="dashicons dashicons-admin-multisite" rel="tipsy" original-title="' . $title . '"></span>';
			}
			return $output;
		}

		foreach ( $options_array as $_key => $meta ) {
			if ( ! isset( $options[ $_key ] ) ) {
				continue;
			}
			$_value = $options[ $_key ];

			if ( $_key == 'priority' ) {
				echo '<span rel="tipsy" title="' . sprintf( __( 'Priority %d', 'custom-css-js-pro' ), $_value ) . '">pr' . $_value . '</span>';
				continue;
			}

			// For radio values with a dashicon
			if ( isset( $meta['values'][ $_value ] ) && isset( $meta['values'][ $_value ]['dashicon'] ) ) {
				$icon = 'dashicons ';
				if ( isset( $meta['values'][ $_value ]['dashicon'] ) ) {
					$icon .= 'dashicons-' . $meta['values'][ $_value ]['dashicon'];
				}

				$title = $meta['title'];
				if ( isset( $meta['values'][ $_value ]['title'] ) ) {
					$title .= ' : ' . $meta['values'][ $_value ]['title'];
				}

				$output .= '<span class="' . $icon . '" rel="tipsy" title="' . htmlentities( $title ) . '"></span> ';
			}
			// For radio values without a dashicon
			elseif ( isset( $meta['values'][ $_value ] ) ) {
				if ( $_value == 'none' ) {
					continue;
				}
				if ( isset( $meta['values'][ $_value ]['title'] ) ) {
					$text    = $meta['values'][ $_value ]['title'];
					$title   = $meta['title'] . ' : ' . $text;
					$output .= ' <span rel="tipsy" title="' . $title . '">' . $text . '</span> ';
				} else {
					$text    = $meta['values'][ $_value ];
					$output .= '<span class="dashicons dashicons-' . $meta['dashicon'] . '" rel="tipsy" title="' . $text . '"></span> ';
				}
			}
			// For checkboxes
			elseif ( $_value == true ) {
				$title = $meta['title'];
				$icon  = '';
				if ( isset( $meta['dashicon'] ) ) {
					$icon = 'dashicons dashicons-' . $meta['dashicon'];
				}
				$output .= '<span class="' . $icon . '" rel="tipsy" title="' . $title . '"></span> ';
			}
		}

		return $output;
	}


	/**
	 * Order table by Type and Active columns
	 *
	 */
	function posts_orderby( $orderby, $query ) {
		if ( ! is_admin() ) {
			return $orderby;
		}
		global $wpdb;

		if ( 'custom-css-js' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$orderby = "REGEXP_SUBSTR( {$wpdb->prefix}postmeta.meta_value, 'js|html|css') " . $query->get( 'order' );
		}
		if ( 'custom-css-js' === $query->get( 'post_type' ) && 'active' === $query->get( 'orderby' ) ) {
			$orderby = "coalesce( postmeta1.meta_value, 'p' ) " . $query->get( 'order' );
		}
		return $orderby;
	}


	/**
	 * Order table by Type and Active columns
	 */
	function posts_join_paged( $join, $query ) {
		if ( ! is_admin() ) {
			return $join;
		}
		global $wpdb;

		if ( 'custom-css-js' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$join = "LEFT JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id";
		}

		if ( 'custom-css-js' === $query->get( 'post_type' ) && 'active' === $query->get( 'orderby' ) ) {
			$join = "LEFT JOIN (SELECT post_id AS ID, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_active' ) as postmeta1 USING( ID )";
		}
		return $join;
	}


	/**
	 * Order table by Type and Active columns
	 */
	function posts_where_paged( $where, $query ) {
		if ( ! is_admin() ) {
			return $where;
		}
		global $wpdb;

		if ( 'custom-css-js' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$where .= " AND {$wpdb->prefix}postmeta.meta_key = 'options'";
		}
		return $where;
	}


	/**
	 * Activate/deactivate a code
	 *
	 * @return void
	 */
	function wp_ajax_ccj_active_code() {
		if ( ! isset( $_GET['code_id'] ) ) {
			die();
		}

		$code_id = absint( $_GET['code_id'] );

		$response = 'error';
		if ( check_admin_referer( 'ccj-active-code-' . $code_id ) ) {

			if ( 'custom-css-js' === get_post_type( $code_id ) ) {
				$active = get_post_meta( $code_id, '_active', true );
				$active = ( $active !== 'no' ) ? $active = 'yes' : 'no';

				update_post_meta( $code_id, '_active', $active === 'yes' ? 'no' : 'yes' );
				ccj_build_search_tree();
			}
		}
		echo $active;

		die();
	}


	/**
	 * Check if a code is active
	 *
	 * @return bool
	 */
	function is_active( $post_id ) {
		return get_post_meta( $post_id, '_active', true ) !== 'no';
	}


	/**
	 * Reformat the `edit` screen
	 */
	function current_screen_edit() {
		?>
		<script type="text/javascript">
			 /* <![CDATA[ */
			jQuery(window).ready(function($){
				var h1 = '<?php _e( 'Custom Code', 'custom-css-js-pro' ); ?> ';
				h1 += '<a href="post-new.php?post_type=custom-css-js&language=css" class="page-title-action"><?php _e( 'Add CSS Code', 'custom-css-js-pro' ); ?></a>';
				h1 += '<a href="post-new.php?post_type=custom-css-js&language=js" class="page-title-action"><?php _e( 'Add JS Code', 'custom-css-js-pro' ); ?></a>';
				h1 += '<a href="post-new.php?post_type=custom-css-js&language=html" class="page-title-action"><?php _e( 'Add HTML Code', 'custom-css-js-pro' ); ?></a>';
				$("#wpbody-content h1").html(h1);
			});

		</script>
		<?php
	}


	/**
	 * Reformat the `post` screen
	 */
	function current_screen_post() {

		$this->remove_unallowed_metaboxes();

		if ( isset( $_GET['post'] ) ) {
			$action  = __( 'Edit %s code', 'custom-css-js-pro' );
			$post_id = esc_attr( $_GET['post'] );
		} else {
			$action  = __( 'Add %s code', 'custom-css-js-pro' );
			$post_id = false;
		}
		$language = $this->get_language( $post_id );

		$title = sprintf( $action, strtoupper( $language ) );

		if ( $post_id != false ) {
			$title .= ' <a href="post-new.php?post_type=custom-css-js&language=css" class="page-title-action">' . __( 'Add CSS Code', 'custom-css-js-pro' ) . '</a> ';
			$title .= '<a href="post-new.php?post_type=custom-css-js&language=js" class="page-title-action">' . __( 'Add JS Code', 'custom-css-js-pro' ) . '</a>';
			$title .= '<a href="post-new.php?post_type=custom-css-js&language=html" class="page-title-action">' . __( 'Add HTML Code', 'custom-css-js-pro' ) . '</a>';
		}

		$parsing_error = get_site_transient( 'ccje_' . $post_id );
		if ( $parsing_error ) {
			$parsing_error = preg_replace("/\r|\n/", "", $parsing_error);
			delete_site_transient( 'ccje_' . $post_id );
		}

		?>
		<script type="text/javascript">
			 /* <![CDATA[ */
			jQuery(window).ready(function($){
				$("#wpbody-content h1").html('<?php echo $title; ?>');
				$("#message.updated.notice").html('<p><?php _e( 'Code updated', 'custom-css-js-pro' ); ?></p>');
				<?php if ( $parsing_error ) : ?>
					$("#message.updated.notice").html('<p><?php _e($parsing_error); ?></p>').removeClass('updated notice-success').addClass('notice-error');
				<?php endif; ?>
			});
			/* ]]> */
		</script>
		<?php
	}


	/**
	 * Remove unallowed metaboxes from custom-css-js edit page
	 *
	 * Use the custom-css-js-meta-boxes filter to add/remove allowed metaboxdes on the page
	 */
	function remove_unallowed_metaboxes() {
		global $wp_meta_boxes;

		// Side boxes
		$allowed = array( 'submitdiv', 'custom-code-options' );

		$allowed = apply_filters( 'custom-css-js-meta-boxes', $allowed );

		foreach ( $wp_meta_boxes['custom-css-js']['side'] as $_priority => $_boxes ) {
			foreach ( $_boxes as $_key => $_value ) {
				if ( ! in_array( $_key, $allowed ) ) {
					unset( $wp_meta_boxes['custom-css-js']['side'][ $_priority ][ $_key ] );
				}
			}
		}

		// Normal boxes
		$allowed = array( 'slugdiv', 'previewdiv', 'url-rules', 'revisionsdiv', 'activatelicensediv' );

		$allowed = apply_filters( 'custom-css-js-meta-boxes-normal', $allowed );

		foreach ( $wp_meta_boxes['custom-css-js']['normal'] as $_priority => $_boxes ) {
			foreach ( $_boxes as $_key => $_value ) {
				if ( ! in_array( $_key, $allowed ) ) {
					unset( $wp_meta_boxes['custom-css-js']['normal'][ $_priority ][ $_key ] );
				}
			}
		}

		unset( $wp_meta_boxes['custom-css-js']['advanced'] );
	}




	/**
	 * Add the codemirror editor in the `post` screen
	 */
	public function codemirror_editor( $post ) {
		$current_screen = get_current_screen();

		if ( $current_screen->post_type != 'custom-css-js' ) {
			return false;
		}

		$settings     = $this->settings;
		$editor_theme = isset( $settings['ccj_editor_theme'] ) ? $settings['ccj_editor_theme'] : 'default';

		if ( empty( $post->post_title ) && empty( $post->post_content ) ) {
			$new_post = true;
			$post_id  = false;
		} else {
			$new_post = false;
			if ( ! isset( $_GET['post'] ) ) {
				$_GET['post'] = $post->id;
			}
			$post_id = esc_attr( $_GET['post'] );
		}
		$language = $this->get_language( $post_id );

		// Replace the htmlentities (https://wordpress.org/support/topic/annoying-bug-in-text-editor/), but only selectively
		if ( isset( $settings['ccj_htmlentities'] ) && $settings['ccj_htmlentities'] == 1 && strstr( $post->post_content, '&' ) ) {

			// First the ampresands
			$post->post_content = str_replace( '&amp', htmlentities( '&amp' ), $post->post_content );

			// Then the rest of the entities
			$entities = get_html_translation_table( HTML_ENTITIES, ENT_QUOTES | ENT_HTML5 );
			unset( $entities[ array_search( '&amp;', $entities ) ] );
			$regular_expression = str_replace( ';', '', '/(' . implode( '|', $entities ) . ')/i' );
			preg_match_all( $regular_expression, $post->post_content, $matches );
			if ( isset( $matches[0] ) && count( $matches[0] ) > 0 ) {
				foreach ( $matches[0] as $_entity ) {
					$post->post_content = str_replace( $_entity, htmlentities( $_entity ), $post->post_content );
				}
			}
		}

		if ( isset( $settings['ccj_htmlentities2'] ) && $settings['ccj_htmlentities2'] == 1 ) {
			$post->post_content = htmlentities( $post->post_content );
		}

		switch ( $language ) {
			case 'js':
				if ( $new_post ) {
					$post->post_content = __(
						'/* Add your JavaScript code here.

If you are using the jQuery library, then don\'t forget to wrap your code inside jQuery.ready() as follows:

jQuery(document).ready(function( $ ){
    // Your code in here
});

--

If you want to link a JavaScript file that resides on another server (similar to
<script src="https://example.com/your-js-file.js"></script>), then please use
the "Add HTML Code" page, as this is a HTML code that links a JavaScript file.

End of comment */ ',
						'custom-css-js-pro'
					) . PHP_EOL . PHP_EOL;
				}
				$code_mirror_mode   = 'text/javascript';
				$code_mirror_before = '<script type="text/javascript">';
				$code_mirror_after  = '</script>';
				break;
			case 'html':
				if ( $new_post ) {
					$post->post_content = __(
						'<!-- Add HTML code in the header, the footer or in the content as a shortcode.

## In the header
	For example, you can add the following code to the header for loading the jQuery library from Google CDN:
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

	or the following one for loading the Bootstrap library from jsDelivr:
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

## As shortcode
	You can use it in a post/page as [ccj id="shortcode_id"]. ("ccj" stands for "Custom CSS and JS").

## Shortcode variables:
	For the shortcode: [ccj id="shortcode_id" variable="Ana"]

	and the shortcode content: Good morning, {$variable}!
    OR, equivalently, the content: Good morning, <?php echo $variable; ?>

	this will be output on the website: Good morning, Ana!

-- End of the comment --> ',
						'custom-css-js-pro'
					) . PHP_EOL . PHP_EOL;

				}
				$code_mirror_mode   = 'application/x-httpd-php';
				$code_mirror_before = '';
				$code_mirror_after  = '';
				break;

			case 'php':
				if ( $new_post ) {
					$post->post_content = __( '/* The following will be executed as if it were written in functions.php. */', 'custom-css-js-pro' ) . PHP_EOL . PHP_EOL;
				}
				$code_mirror_mode   = 'php';
				$code_mirror_before = '<?php';
				$code_mirror_after  = '?>';

				break;
			default:
				if ( $new_post ) {
					$post->post_content = __(
						'/* Add your CSS code here.

For example:
.example {
    color: red;
}

For brushing up on your CSS knowledge, check out http://www.w3schools.com/css/css_syntax.asp

End of comment */ ',
						'custom-css-js-pro'
					) . PHP_EOL . PHP_EOL;

				}
				$code_mirror_mode   = 'text/css';
				$code_mirror_before = '<style type="text/css">';
				$code_mirror_after  = '</style>';

		}

		?>
			  <form style="position: relative; margin-top: .5em;">
				<div id="codemirror_all">

				<div class="code-mirror-buttons">
				<div class="button-left"><span rel="tipsy" original-title="<?php _e( 'Beautify Code', 'custom-css-js-pro' ); ?>"><button type="button" tabindex="-1" id="ccj-beautifier"><i class="ccj-i-beautifier"></i></button></span></div>
				<!--div class="button-left"><span rel="tipsy" original-title="<?php _e( 'Editor Settings', 'custom-css-js-pro' ); ?>"><button type="button" tabindex="-1" id="ccj-settings"><i class="ccj-i-settings"></i></button></span></div -->
				<div class="button-right" id="ccj-fullscreen-button" alt="<?php _e( 'Distraction-free writing mode', 'custom-css-js-pro' ); ?>"><span rel="tipsy" original-title="<?php _e( 'Fullscreen', 'custom-css-js-pro' ); ?>"><button role="presentation" type="button" tabindex="-1"><i class="ccj-i-fullscreen"></i></button></span></div>
<input type="hidden" name="fullscreen" id="ccj-fullscreen-hidden" value="false" />
<!-- div class="button-right" id="ccj-search-button" alt="Search"><button role="presentation" type="button" tabindex="-1"><i class="ccj-i-find"></i></button></div -->

				</div>

				<div class="code-mirror-before 
				<?php
				if ( ! empty( $editor_theme ) ) {
					echo 'cm-before-' . $editor_theme;}
				?>
				"><div><?php echo htmlentities( $code_mirror_before ); ?></div></div>
				<textarea class="wp-editor-area" id="ccj_content" mode="<?php echo htmlentities( $code_mirror_mode ); ?>" name="content" autofocus><?php echo $post->post_content; ?></textarea>
				<div class="code-mirror-after 
				<?php
				if ( ! empty( $editor_theme ) ) {
					echo 'cm-after-' . $editor_theme;}
				?>
				"><div><?php echo htmlentities( $code_mirror_after ); ?></div></div>

				<table id="post-status-info"><tbody><tr>
					<td class="autosave-info">
					<span class="autosave-message">&nbsp;</span>
				<?php
				if ( 'auto-draft' != $post->post_status ) {
					echo '<span id="last-edit">';
					if ( $last_user = get_userdata( get_post_meta( $post->ID, '_edit_last', true ) ) ) {
						printf( __( 'Last edited by %1$s on %2$s at %3$s', 'custom-css-js-pro' ), esc_html( $last_user->display_name ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
					} else {
						printf( __( 'Last edited on %1$s at %2$s', 'custom-css-js-pro' ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
					}
					echo '</span>';
				}
				?>
					</td>
				</tr></tbody></table>

				<input type="hidden" id="update-post_<?php echo $post->ID; ?>" value="<?php echo wp_create_nonce( 'update-post_' . $post->ID ); ?>" />
				<input type="hidden" id="editor_theme" name="editor_theme" value="<?php echo $editor_theme; ?>" />

				</div>

			  </form>

			<?php
					/*
			if ( get_post_meta($post->ID, 'urls_error') ) {
				$error = sprintf( __('There is something wrong with the %s value for the WP Conditional Tag. Please check the <a href="%s" target="_blank">WordPress documentation</a> regarding the allowed Conditional Tags.', 'custom-css-js-pro'),
					'<code>'.get_post_meta($post->ID, 'urls_error', true). '</code>',
					'https://codex.wordpress.org/Conditional_Tags');

				echo '<div id="wp_tag_error" style="display: none;">'.$error. '</div>';
			}
				*/

	}


	/**
	 * Show the options form in the `post` screen
	 */
	function custom_code_options_meta_box_callback( $post ) {

			$options = ccj_get_options( $post->ID );

			$meta = ccj_get_options_meta();

			if ( isset( $_GET['language'] ) ) {
				$options['language'] = $this->get_language();
			}

			// Remove custom JS codes from login page on subsites
			if ( $options['language'] == 'js' && is_multisite() && ! is_main_site() && ! get_site_option( 'ccj_multisite_js_loginpage', false ) ) {
				unset( $meta['side']['values']['login'] );
			}

			wp_nonce_field( 'options_save_meta_box_data', 'custom-css-js_meta_box_nonce' );

		?>
			<div class="options_meta_box">
			<?php

			$output = '';

			foreach ( $meta as $_key => $a ) {

				// Don't show Pre-processors for JavaScript Codes
				if ( $options['language'] == 'js' && $_key == 'preprocessor' ) {
					continue;
				}

				$output .= '<h3>' . $a['title'] . '</h3>' . PHP_EOL;

				$output .= $this->render_input( $_key, $a, $options );

			}

			echo $output;

			?>

			<input type="hidden" name="custom_code_language" value="<?php echo $options['language']; ?>" />

			<div style="clear: both;"></div>

			</div>


			<?php
	}


	/**
	 * Save the post and the metadata
	 */
	function options_save_meta_box_data( $post_id ) {

		// The usual checks
		if ( ! isset( $_POST['custom-css-js_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['custom-css-js_meta_box_nonce'], 'options_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'custom-css-js' != $_POST['post_type'] ) {
			return;
		}

		$defaults = ccj_get_options();
		if ( $_POST['custom_code_language'] == 'html' ) {
			$defaults = ccj_get_options( $post_id, 'html' );
		}
		$options = array();

		foreach ( $defaults as $_field => $_default ) {
			$options[ $_field ] = isset( $_POST[ 'custom_code_' . $_field ] ) ? esc_attr( strtolower( $_POST[ 'custom_code_' . $_field ] ) ) : $_default;
		}

		$options['side'] = [];
		foreach ( ['frontend', 'admin', 'login'] as $_side ) {
			if ( isset( $_POST[ 'custom_code_side-' . $_side ] ) && $_POST[ 'custom_code_side-' . $_side ] == '1' ) {
				$options['side'][] = $_side;
			}
		}
		if ( count( $options['side'] ) === 0 ) {
			$options['side'] = ['frontend'];
		}
		$options['side'] = implode(',', $options['side'] );


		$options['language'] = in_array( $options['language'], array( 'html', 'css', 'js' ), true ) ? $options['language'] : $defaults['language'];

		if ( $options['language'] === 'html' && $options['type'] === 'shortcode' ) {
			$options['name'] = sanitize_file_name( $_POST['custom_code_name'] );
		}

		$is_preprocessor = ( $options['language'] === 'css' && ( $options['preprocessor'] === 'less' || $options['preprocessor'] === 'sass' ) ) ? true : false;

		if ( $is_preprocessor ) {
			$options['preid'] = sanitize_file_name( $_POST['custom_code_preid'] );

		}

		if ( is_multisite() && is_super_admin() && is_main_site() ) {
			if ( ! isset( $_POST['custom_code_multisite'] ) ) {
				$options['multisite'] = false;
			}
		}

		update_post_meta( $post_id, 'options', $options );

		$options['post_id'] = $post_id;

		// Save the Custom Code in a file in `wp-content/uploads/custom-css-js`
		if ( $options['language'] != 'html' ) {
			$filename = $post_id . '.' . $options['language'];
			if ( $is_preprocessor && ! empty( $options['preid'] ) ) {
				$filename = $options['preid'];
			}
			ccj_save_code_file( $_POST['content'], $options, $filename, $post_id );
		}

		ccj_build_search_tree();

	}



	/**
	 * Show the options form in the `post` screen for HTML language
	 */
	function custom_code_options_meta_box_callback_html( $post ) {

		$options = ccj_get_options( $post->ID, 'html' );

		$meta = ccj_get_options_meta_html();

		if ( isset( $_GET['language'] ) ) {
			$options['language'] = $this->get_language();
		}

		wp_nonce_field( 'options_save_meta_box_data', 'custom-css-js_meta_box_nonce' );

		?>
		<div class="options_meta_box">
		<?php

		$output = '';

		foreach ( $meta as $_key => $a ) {

			$output .= '<h3>' . $a['title'] . '</h3>' . PHP_EOL;

			$output .= $this->render_input( $_key, $a, $options );

		}

		echo $output;

		?>

		<input type="hidden" name="custom_code_language" value="<?php echo $options['language']; ?>" />

		<div style="clear: both;"></div>

		</div>


		<?php
	}



	/**
	 * Create the custom-css-js dir in uploads directory
	 *
	 * Show a message if the directory is not writable
	 *
	 * Create an empty index.php file inside
	 */
	function create_uploads_directory() {
		$current_screen = get_current_screen();

		// Check if we are editing a custom-css-js post
		if ( $current_screen->base != 'post' || $current_screen->post_type != 'custom-css-js' ) {
			return false;
		}

		$dir = CCJ_UPLOAD_DIR;

		// Create the dir if it doesn't exist
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Show a message if it couldn't create the dir
		if ( ! file_exists( $dir ) ) :
			?>
			 <div class="notice notice-error is-dismissible">
			 <p><?php printf( __( 'The %s directory could not be created', 'custom-css-js-pro' ), '<b>custom-css-js</b>' ); ?></p>
			 <p><?php _e( 'Please run the following commands in order to make the directory', 'custom-css-js-pro' ); ?>: <br /><strong>mkdir <?php echo $dir; ?>; </strong><br /><strong>chmod 777 <?php echo $dir; ?>;</strong></p>
			</div>
			<?php
			return;
endif;

		// Show a message if the dir is not writable
		if ( ! wp_is_writable( $dir ) ) :
			?>
			 <div class="notice notice-error is-dismissible">
			 <p><?php printf( __( 'The <b>%s</b> directory is not writable, therefore the CSS and JS files cannot be saved.', 'custom-css-js-pro' ), $dir ); ?></p>
			 <p><?php _e( 'Please run the following command to make the directory writable', 'custom-css-js-pro' ); ?>:<br /><strong>chmod 777 <?php echo $dir; ?> </strong></p>
			</div>
			<?php
			return;
endif;

		// Write a blank index.php
		if ( ! file_exists( $dir . '/index.php' ) ) {
			$content = '<?php' . PHP_EOL . '// Silence is golden.';
			@file_put_contents( $dir . '/index.php', $content );
		}
	}

	function revision_save_meta_box_data( $post_id, $post ) {

		$parent_id = wp_is_post_revision( $post_id );
		if ( ! $parent_id ) {
			return;
		}

		$parent = get_post( $parent_id );
		if ( $parent->post_type != 'custom-css-js' ) {
			return;
		}

		$options = get_post_meta( $parent->ID, 'options', true );

		if ( false !== $options ) {
			add_metadata( 'post', $post_id, 'options', $options );
		}

	}

	/**
	 * Rebuilt the tree when you trash or restore a custom code
	 */
	function trash_post( $post_id ) {
		ccj_build_search_tree();
	}


	/**
	 * Compatibility with `shortcoder` plugin
	 */
	function compatibility_shortcoder() {
		ob_start( array( $this, 'compatibility_shortcoder_html' ) );
	}
	function compatibility_shortcoder_html( $html ) {
		if ( strpos( $html, 'QTags.addButton' ) === false ) {
			return $html;
		}
		if ( strpos( $html, 'codemirror/codemirror-compressed.js' ) === false ) {
			return $html;
		}

		return str_replace( 'QTags.addButton', '// QTags.addButton', $html );
	}


	/**
	 * Initiate the EDD_SL_Plugin_Updater class
	 */
	public function edd_updater() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater_CCS' ) ) {
			include 'edd/EDD_SL_Plugin_Updater.php';
		}

		$a                      = ccj_plugin_updater_data();
		$a['data']['license']   = trim( get_option( 'ccj_license_key' ) );
		$a['data']['url']       = home_url();
		$a['data']['item_name'] = $a['data']['plugin_name'];

		$edd_updater = new EDD_SL_Plugin_Updater_CCS( $a['plugin_server'], $a['file'], $a['data'] );

	}


	/**
	 * Render the checkboxes, radios, selects and inputs
	 */
	function render_input( $_key, $a, $options ) {
		$name   = 'custom_code_' . $_key;
		$output = '';

		// Show radio type options
		if ( $a['type'] === 'radio' ) {
			$selected = '';
			$output  .= '<div class="radio-group">' . PHP_EOL;
			foreach ( $a['values'] as $__key => $__value ) {
				$id        = $name . '-' . $__key;
				$dashicons = isset( $__value['dashicon'] ) ? 'dashicons-before dashicons-' . $__value['dashicon'] : '';
				$selected  = ( $__key == $options[ $_key ] ) ? ' checked="checked" ' : '';
				$output   .= '<input type="radio" ' . $selected . 'value="' . $__key . '" name="' . $name . '" id="' . $id . '">' . PHP_EOL;
				$output   .= '<label class="' . $dashicons . '" for="' . $id . '"> ' . esc_attr( $__value['title'] ) . '</label><br />' . PHP_EOL;
			}

			if ( $_key === 'type' && $options['type'] === 'shortcode' ) {
				$output .= '<div id="custom_code_name_div"><label for="custom_code_name">' . __( 'Shortcode id', 'custom-css-js-pro' ) . ': </label> <input type="text" name="custom_code_name" id="custom_code_name" value="' . $options['name'] . '" /></div>';
			}

			if ( $_key === 'preprocessor' && ( $options['preprocessor'] === 'less' || $options['preprocessor'] === 'sass' ) ) {
				$options['preid'] = isset( $options['preid'] ) ? $options['preid'] : '';
				$preid_info_text  = __( 'This ID can be used in a Sass/Less import statement from another custom CSS code. Example: if this ID is \'vars.scss\', then you can write <b>@import \'vars.scss\';</b> in another custom Sass/Less code in order to import it. Leave the ID empty if it will not be imported from another custom code.' );
				$preid_info       = '<span class="dashicons dashicons-editor-help" rel="tipsy" original-title="' . $preid_info_text . '"></span>';
				$output          .= '<div id="custom_code_preid_div">' . $preid_info . ' <label for="custom_code_preid">' . __( 'ID', 'custom-css-js-pro' ) . ': </label> <input type="text" name="custom_code_preid" id="custom_code_preid" value="' . $options['preid'] . '" /></div>';
			}

			$output .= '</div>' . PHP_EOL;
		}

		// Show checkbox type options
		if ( $a['type'] == 'checkbox' ) {
			$output .= '<div class="radio-group">' . PHP_EOL;
			if ( isset( $a['values'] ) && count( $a['values'] ) > 0 ) {
				$current_values = explode(',', $options[ $_key ] );
				foreach ( $a['values'] as $__key => $__value ) {
					$id        = $name . '-' . $__key;
					$dashicons = isset( $__value['dashicon'] ) ? 'dashicons-before dashicons-' . $__value['dashicon'] : '';
					$selected  = ( isset( $a['disabled'] ) && $a['disabled'] ) ? ' disabled="disabled"' : '';
					$selected .= ( in_array( $__key, $current_values ) ) ? ' checked="checked" ' : '';
					$output   .= '<input type="checkbox" ' . $selected . ' value="1" name="' . $id . '" id="' . $id . '">' . PHP_EOL;
					$output   .= '<label class="' . $dashicons . '" for="' . $id . '"> ' . esc_attr( $__value['title'] ) . '</label><br />' . PHP_EOL;
				}
			} else {
				$dashicons = isset( $a['dashicon'] ) ? 'dashicons-before dashicons-' . $a['dashicon'] : '';
				$selected  = ( isset( $options[ $_key ] ) && $options[ $_key ] == '1' ) ? ' checked="checked" ' : '';
				$selected .= ( isset( $a['disabled'] ) && $a['disabled'] ) ? ' disabled="disabled"' : '';
				$output   .= '<input type="checkbox" ' . $selected . ' value="1" name="' . $name . '" id="' . $name . '">' . PHP_EOL;
				$output   .= '<label class="' . $dashicons . '" for="' . $name . '"> ' . esc_attr( $a['title'] ) . '</label>' . PHP_EOL;
			}
			$output   .= '</div>' . PHP_EOL;
		}

		// Show text type options
		if ( $a['type'] == 'text' ) {
			$output .= '<div class="radio-group">' . PHP_EOL;
			$output .= '<input type="text" value="' . $options[ $_key ] . '" name="' . $name . '" id="' . $name . '">' . PHP_EOL;
			$output .= '</div>' . PHP_EOL;

		}

		// Show select type options
		if ( $a['type'] == 'select' ) {
			$output .= '<div class="radio-group">' . PHP_EOL;
			$output .= '<select name="' . $name . '" id="' . $name . '">' . PHP_EOL;
			foreach ( $a['values'] as $__key => $__value ) {
				$selected = ( isset( $options[ $_key ] ) && $options[ $_key ] == $__key ) ? ' selected="selected"' : '';
				$output  .= '<option value="' . $__key . '"' . $selected . '>' . $__value . '</option>' . PHP_EOL;
			}
			$output .= '</select>' . PHP_EOL;
			$output .= '</div>' . PHP_EOL;
		}

		return $output;

	}


	/**
	 * Get the language for the current post
	 */
	function get_language( $post_id = false ) {
		if ( $post_id !== false ) {
			$options  = ccj_get_options( $post_id );
			$language = $options['language'];
		} else {
			$language = isset( $_GET['language'] ) ? esc_attr( strtolower( $_GET['language'] ) ) : 'css';
		}
		if ( ! in_array( $language, array( 'css', 'js', 'html' ) ) ) {
			$language = 'css';
		}

		return $language;
	}


	/**
	 * Show the activate/deactivate link in the row's action area
	 */
	function post_row_actions( $actions, $post ) {
		if ( 'custom-css-js' !== $post->post_type ) {
			return $actions;
		}

		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=ccj_active_code&code_id=' . $post->ID ), 'ccj-active-code-' . $post->ID );
		if ( $this->is_active( $post->ID ) ) {
			$active_title = __( 'The code is active. Click to deactivate it', 'custom-css-js-pro' );
			$active_text  = __( 'Deactivate', 'custom-css-js-pro' );
		} else {
			$active_title = __( 'The code is inactive. Click to activate it', 'custom-css-js-pro' );
			$active_text  = __( 'Activate', 'custom-css-js-pro' );
		}
		$actions['activate'] = '<a href="' . esc_url( $url ) . '" title="' . $active_title . '" class="ccj_activate_deactivate" data-code-id="' . $post->ID . '">' . $active_text . '</a>';

		return $actions;
	}


	/**
	 * Show the activate/deactivate link in admin.
	 */
	public function post_submitbox_start() {
		global $post;

		if ( ! is_object( $post ) ) {
			return;
		}

		if ( 'custom-css-js' !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=ccj_active_code&code_id=' . $post->ID ), 'ccj-active-code-' . $post->ID );

		if ( $this->is_active( $post->ID ) ) {
			$text   = __( 'Active', 'custom-css-js-pro' );
			$action = __( 'Deactivate', 'custom-css-js-pro' );
		} else {
			$text   = __( 'Inactive', 'custom-css-js-pro' );
			$action = __( 'Activate', 'custom-css-js-pro' );
		}
		?>
		<div id="activate-action"><span style="font-weight: bold;"><?php echo $text; ?></span>
		(<a class="ccj_activate_deactivate" data-code-id="<?php echo $post->ID; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo $action; ?></a>)
		</div>
		<?php
	}


	/**
	 * Show the Permalink edit form
	 */
	function edit_form_before_permalink( $filename = '', $permalink = '', $filetype = 'css' ) {
		if ( isset( $_GET['language'] ) ) {
			$filetype = strtolower(trim($_GET['language']));
		}

		if ( ! in_array( $filetype, array( 'css', 'js' ) ) ) {
			return;
		}

		if ( ! is_string( $filename ) ) {
			global $post;
			if ( ! is_object( $post ) ) {
				return;
			}
			if ( 'custom-css-js' !== $post->post_type ) {
				return;
			}

			$post    = $filename;
			$slug    = get_post_meta( $post->ID, '_slug', true );
			$options = get_post_meta( $post->ID, 'options', true );
			if ( is_array( $options ) && isset( $options['language'] ) ) {
				$filetype = $options['language'];
			}
			if ( $filetype === 'html' ) {
				return;
			}
			if ( ! @file_exists( CCJ_UPLOAD_DIR . '/' . $slug . '.' . $filetype ) ) {
				$slug = false;
			}
			$filename = ( $slug ) ? $slug : $post->ID;
		}

		if ( empty( $permalink ) ) {
			$permalink = CCJ_UPLOAD_URL . '/' . $filename . '.' . $filetype;
		}

		?>
		<div class="inside">
			<div id="edit-slug-box" class="hide-if-no-js">
				<strong>Permalink:</strong>
				<span id="sample-permalink"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( CCJ_UPLOAD_URL ) . '/'; ?><span id="editable-post-name"><?php echo esc_html( $filename ); ?></span>.<?php echo esc_html( $filetype ); ?></a></span>
				&lrm;<span id="ccj-edit-slug-buttons"><button type="button" class="ccj-edit-slug button button-small hide-if-no-js" aria-label="Edit permalink">Edit</button></span>
				<span id="editable-post-name-full" data-filetype="<?php echo $filetype; ?>"><?php echo esc_html( $filename ); ?></span>
			</div>
			<?php wp_nonce_field( 'ccj-permalink', 'ccj-permalink-nonce' ); ?>
		</div>
		<?php
	}


	/**
	 * AJAX save the Permalink slug
	 */
	function wp_ajax_ccj_permalink() {

		if ( ! isset( $_POST['ccj_permalink_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['ccj_permalink_nonce'], 'ccj-permalink' ) ) {
			return;
		}

		$code_id   = isset( $_POST['code_id'] ) ? intval( $_POST['code_id'] ) : 0;
		$permalink = isset( $_POST['permalink'] ) ? $_POST['permalink'] : null;
		$slug      = isset( $_POST['new_slug'] ) ? trim( sanitize_file_name( $_POST['new_slug'] ) ) : null;
		$filetype  = isset( $_POST['filetype'] ) ? $_POST['filetype'] : 'css';
		if ( empty( $slug ) ) {
			$slug = (string) $code_id;
		} else {
			update_post_meta( $code_id, '_slug', $slug );
		}
		$this->edit_form_before_permalink( $slug, $permalink, $filetype );

		wp_die();
	}


	/**
	 * Show contextual help for the Custom Code edit page
	 */
	public function contextual_help() {
		$screen = get_current_screen();

		if ( $screen->id != 'custom-css-js' ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'ccj-editor_shortcuts',
				'title'   => __( 'Editor Shortcuts', 'custom-css-js-pro' ),
				'content' =>
							  '<p><table>
            <tr><td><strong>Auto Complete</strong></td><td> <code>Ctrl</code> + <code>Space</code></td></tr>
            <tr><td><strong>Find</strong></td><td> <code>Ctrl</code> + <code>F</code></td></tr>
            <tr><td><strong>Replace</strong></td><td> <code>Shift</code> + <code>Ctrl</code> + <code>F</code></td></tr>
            <tr><td><strong>Save</strong></td><td> <code>Ctrl</code> + <code>S</code></td></tr>
            <tr><td><strong>Comment line/block</strong></td><td> <code>Ctrl</code> + <code>/</code></td></tr>
            <tr><td><strong>Code folding</strong></td><td> <code>Ctrl</code> + <code>Q</code></td></tr>
            </table></p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'ccj-editor_jshint',
				'title'   => __( 'JS Linting Options', 'custom-css-js-pro' ),
				'content' =>
				'<p>The linting options can be changed by adding the /* jshint option: value */ line in the editor at the beginning of the custom code.<table>
			<tr><td><strong>For example:</strong></td><td> <code>/* jshint esversion: 6 */</code></td></tr></table></p>
			<p>On the <a href="https://jshint.com/docs/options/" target="_blank">JSHint options</a> page you can find additional jshint options.</p>',
			)
		);

	}


	/**
	 * Remove the JS/CSS file from the disk when deleting the post
	 */
	function before_delete_post( $postid ) {
		global $post;
		if ( ! is_object( $post ) ) {
			return;
		}
		if ( 'custom-css-js' !== $post->post_type ) {
			return;
		}
		if ( ! wp_is_writable( CCJ_UPLOAD_DIR ) ) {
			return;
		}

		$options = get_post_meta( $postid, 'options', true );
		if ( ! is_array( $options ) ) {
			return;
		}

		$options['language'] = ( isset( $options['language'] ) ) ? strtolower( $options['language'] ) : 'css';
		$options['language'] = in_array( $options['language'], array( 'html', 'js', 'css' ), true ) ? $options['language'] : 'css';

		$slug = get_post_meta( $postid, '_slug', true );
		$slug = sanitize_file_name( $slug );

		$file_name = $postid . '.' . $options['language'];

		@unlink( CCJ_UPLOAD_DIR . '/' . $file_name );

		if ( ! empty( $slug ) ) {
			@unlink( CCJ_UPLOAD_DIR . '/' . $slug . '.' . $options['language'] );
		}
	}


	/**
	 * Fix for bug: white page Edit Custom Code for WordPress 5.0 with Classic Editor
	 */
	function current_screen_2() {
		$screen = get_current_screen();

		if ( $screen->post_type != 'custom-css-js' ) {
			return false;
		}

		remove_filter( 'use_block_editor_for_post', array( 'Classic_Editor', 'choose_editor' ), 100, 2 );
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	}
}

return new CustomCSSandJS_AdminPro();
