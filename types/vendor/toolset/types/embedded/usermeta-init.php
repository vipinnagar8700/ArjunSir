<?php

// Add usermeta and post fileds groups to access.
$usermeta_access = new Usermeta_Access;
$fields_access = new Post_Fields_Access;


/**
 * Add User Fields menus hook
 *
 * @since 1.3
 */
function wpcf_admin_menu_edit_user_fields_hook() {
	do_action( 'wpcf_admin_page_init' );

	// Group filter
	wp_enqueue_script( 'wpcf-filter-js',
		WPCF_EMBEDDED_RES_RELPATH
		. '/js/custom-fields-form-filter.js', array( 'jquery' ), WPCF_VERSION );

	// Form
	$asset_manager = Types_Asset_Manager::get_instance();
	$asset_manager->enqueue_scripts(
		array(
			Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,

			// These scripts are needed only for the Styling editor
			Types_Asset_Manager::SCRIPT_CODEMIRROR,
			Types_Asset_Manager::SCRIPT_CODEMIRROR_CSS,
			Types_Asset_Manager::SCRIPT_CODEMIRROR_XML,
			Types_Asset_Manager::SCRIPT_CODEMIRROR_HTMLMIXED,
			Types_Asset_Manager::SCRIPT_JSCROLLPANE,
			Types_Asset_Manager::SCRIPT_MOUSEWHEEL
		)
	);

	wp_enqueue_script( 'wpcf-form-codemirror-editor-resize',
		WPCF_RELPATH . '/resources/js/jquery_ui/jquery.ui.resizable.min.js',
		array( 'wpcf-js' ) );


	wp_enqueue_style( 'wpcf-usermeta',
		WPCF_EMBEDDED_RES_RELPATH . '/css/usermeta.css' );

	// Toolset GUI Base dependencies
	Toolset_Common_Bootstrap::get_instance()->register_gui_base();
	Toolset_Gui_Base::get_instance()->init();
	wp_enqueue_style( Toolset_Gui_Base::STYLE_GUI_BASE );
	wp_enqueue_script( Toolset_Gui_Base::SCRIPT_GUI_JQUERY_COLLAPSIBLE );

	// MAIN
	wp_enqueue_script( 'wpcf-fields-form',
		WPCF_EMBEDDED_RES_RELPATH
		. '/js/fields-form.js', array( 'wpcf-js' ) );

	/**
	 * fields form to manipulate fields
	 */
	wp_enqueue_script(
		'wpcf-admin-fields-form',
		WPCF_RES_RELPATH . '/js/fields-form.js',
		array( Toolset_Assets_Manager::SCRIPT_UTILS, 'wp-pointer' ),
		WPCF_VERSION
	);

	// Enqueue styles

	$asset_manager->enqueue_styles(
		array(
			// These styles are needed only for the Styling editor
			Types_Asset_Manager::STYLE_CODEMIRROR,
			Types_Asset_Manager::STYLE_EDITOR_ADDON_MENU_SCROLL
		)
	);


	wp_enqueue_style( 'font-awesome' );

	add_action( 'admin_footer', 'wpcf_admin_fields_form_js_validation' );
	require_once WPCF_INC_ABSPATH . '/fields.php';
	require_once WPCF_INC_ABSPATH . '/usermeta.php';
	require_once WPCF_INC_ABSPATH . '/fields-form.php';
	require_once WPCF_INC_ABSPATH . '/usermeta-form.php';

	require_once WPCF_INC_ABSPATH . '/classes/class.types.admin.edit.meta.fields.group.php';
	$wpcf_admin = new Types_Admin_Edit_Meta_Fields_Group();
	$wpcf_admin->init_admin();
	$form = $wpcf_admin->form();
	wpcf_form( 'wpcf_form_fields', $form );
}

/**
 * Add/Edit usermeta fields group
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function wpcf_admin_menu_edit_user_fields()
{
    $add_new = false;
    $post_type = current_filter();
    $title = __('View User Field Group', 'wpcf');
    if ( isset( $_GET['group_id'] ) ) {
        $item = wpcf_admin_get_user_field_group_by_id( (int) $_GET['group_id'] );
        if ( WPCF_Roles::user_can_edit('user-meta-field', $item) ) {
            $title = __( 'Edit User Field Group', 'wpcf' );
            $add_new = array(
                'page' => 'wpcf-edit-usermeta',
            );
        }
    } else if ( WPCF_Roles::user_can_create('user-meta-field')) {
        $title = __( 'Add New User Field Group', 'wpcf' );
    }
    wpcf_add_admin_header( $title, $add_new);
    $form = wpcf_form( 'wpcf_form_fields' );
    echo '<form method="post" action="" class="wpcf-fields-form wpcf-form-validate js-types-show-modal">';
    wpcf_admin_screen($post_type, $form->renderForm());
    echo '</form>';
    wpcf_add_admin_footer();

    return;

    $form = wpcf_form( 'wpcf_form_fields' );
    echo '<br /><form method="post" action="" class="wpcf-fields-form '
    . 'wpcf-form-validate" onsubmit="';
    echo 'if (jQuery(\'#wpcf-group-name\').val() == \'' . __( 'Enter group title', 'wpcf' ) . '\') { jQuery(\'#wpcf-group-name\').val(\'\'); }';
    echo 'if (jQuery(\'#wpcf-group-description\').val() == \'' . __( 'Enter a description for this group', 'wpcf' ) . '\') { jQuery(\'#wpcf-group-description\').val(\'\'); }';
    echo 'jQuery(\'.wpcf-forms-set-legend\').each(function(){
        if (jQuery(this).val() == \'' . __( 'Enter field name', 'wpcf' ) . '\') {
            jQuery(this).val(\'\');
        }
        if (jQuery(this).next().val() == \'' . __( 'Enter field slug', 'wpcf' ) . '\') {
            jQuery(this).next().val(\'\');
        }
        if (jQuery(this).next().next().val() == \'' . __( 'Describe this field', 'wpcf' ) . '\') {
            jQuery(this).next().next().val(\'\');
        }
	});';
    echo '">';
    echo $form->renderForm();
    echo '</form>';
    wpcf_add_admin_footer();
}


/**
 * Usermeta groups listing
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function wpcf_usermeta_summary()
{
    wpcf_add_admin_header(
        __( 'User Field Groups', 'wpcf' ),
        array('page' => 'wpcf-edit-usermeta'),
        __('Add New', 'wpcf')
    );
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/usermeta.php';
    require_once WPCF_INC_ABSPATH . '/usermeta-list.php';
    $to_display = wpcf_admin_fields_get_fields();
    if ( !empty( $to_display ) ) {
        add_action( 'wpcf_groups_list_table_after', 'wpcf_admin_promotional_text' );
    }
    wpcf_admin_usermeta_list();
    wpcf_add_admin_footer();
}

//Add usermeta hook when user profile loaded
add_action( 'show_user_profile', 'wpcf_admin_user_profile_load_hook' );
add_action( 'edit_user_profile', 'wpcf_admin_user_profile_load_hook' );

//Save usermeta hook
add_action( 'personal_options_update', 'wpcf_admin_user_profile_save_hook' );
add_action( 'edit_user_profile_update', 'wpcf_admin_user_profile_save_hook' );

/**
 * Get current logged user ID
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */
function wpcf_usermeta_get_user( $method = '' ){
    if ( empty( $method ) ) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
    }

    return $user_id;
}

/**
 * Calls view function for specific field type.
 *
 * @param $field_id
 * @param $params
 * @param null $content
 * @param string $code
 *
 * @return string
 *
 * @deprecated Use types_render_usermeta() instead.
 */
function types_render_usermeta_field( $field_id, $params, $content = null, $code = '' ) {
	return types_render_usermeta( $field_id, $params, $content, $code );
}

/**
 * Add fields to user profile
 */
function wpcf_admin_user_profile_load_hook( $user )
{
    if ( !current_user_can( 'edit_user', $user->ID ) ) {
        return false;
    }
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    wpcf_admin_userprofile_init( $user );
}

/**
 * Add styles to admin fields groups
 */

add_action('admin_head-profile.php', 'wpcf_admin_fields_usermeta_styles' );
add_action('admin_head-user-edit.php', 'wpcf_admin_fields_usermeta_styles' );
add_action('admin_head-user-new.php', 'wpcf_admin_fields_usermeta_styles' );

function wpcf_admin_fields_usermeta_styles()
{
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    $groups = wpcf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
    $content = '';

    if ( !empty( $groups ) ) {
        global $user_id;
        $user_role = false;
        if ( !empty( $user_id ) ) {
            $user_info = get_userdata($user_id);
            $user_roles = isset( $user_info->roles ) ? $user_info->roles : array( 'subscriber' );
            unset($user_info);
        }
        foreach ( $groups as $group ) {
            if ( !empty($user_id) ) {
                $for_users = wpcf_admin_get_groups_showfor_by_group($group['id']);
                if ( !empty($for_users) && empty( array_intersect( $user_roles, $for_users ) ) ) {
                    continue;
                }
            }
            if ( empty( $group['is_active'] ) ) {
                continue;
            }
            $content .= str_replace( "}", '}'.PHP_EOL, wpcf_admin_get_groups_admin_styles_by_group( $group['id'] ) );
            $content .= PHP_EOL;
        }
    }
    if ( $content ) {
        printf('<style type="text/css">%s</style>%s', $content, PHP_EOL );
    }
}

/**
 * Add fields to user profile
 */
function wpcf_admin_user_profile_save_hook( $user_id )
{
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields-post.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-post.php';
    wpcf_admin_userprofilesave_init( $user_id );
}

/*
 *  Register Usermeta Groups in Types Access
 */

class Usermeta_Access
{

	/**
	 * Note that this property will be initialized only if Access is active.
	 *
	 * @var array|string
	 */
    public static $user_groups = '';

    /**
     * Initialize plugin enviroment
     */
    public function __construct() {
		if ( ! function_exists( 'wpcf_access_register_caps' ) ) {
			// Nothing to do because Access is not active.
			return;
		}

        // setup custom capabilities
        self::$user_groups = wpcf_admin_fields_get_groups(TYPES_USER_META_FIELD_GROUP_CPT_NAME);

		if ( empty( self::$user_groups ) ) {
			return;
		}

		$access_version = apply_filters( 'toolset_access_version_installed', '1.0' );
		// Since 2.1 we can define a custom tab on Access >= 2.1
		if ( version_compare( $access_version, '2.0' ) > 0 ) {
			// Add Types Fields tab
			add_filter( 'types-access-tab', array( 'Usermeta_Access', 'register_access_types_fields_tab' ) );
			//Add Usermeta Fields area
			add_filter(
				'types-access-area-for-types-fields',
				array( 'Usermeta_Access', 'register_access_usermeta_area' ),
				20, 2
			);
		} else {
			//Add Usermeta Fields area
			add_filter(
				'types-access-area',
				array( 'Usermeta_Access', 'register_access_usermeta_area' ),
				10, 2
			);
		}
		//Add Usermeta Fields groups
		add_filter(
			'types-access-group',
			array( 'Usermeta_Access', 'register_access_usermeta_groups' ),
			10, 2
		);
		//Add Usermeta Fields caps to groups
		add_filter(
			'types-access-cap',
			array( 'Usermeta_Access', 'register_access_usermeta_caps' ),
			10, 3
		);

    }

    // register custom CRED Frontend capabilities specific to each group
    public static function register_access_usermeta_caps( $caps, $area_id,
            $group_id )
    {
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';
        $default_role = 'guest'; //'administrator';
        //List of caps with default permissions
        $usermeta_caps = array(
            array('view_own_in_profile', $default_role, __( 'View own fields in profile', 'wpcf' )),
            array('modify_own', $default_role, __( 'Modify own fields', 'wpcf' )),
        );
        if ( $area_id == $USERMETA_ACCESS_AREA_ID ) {
            $fields_groups = wpcf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
            if ( !empty( $fields_groups ) ) {
                foreach ( $fields_groups as $group ) {
                    $USERMETA_ACCESS_GROUP_ID = '__USERMETA_FIELDS_GROUP_' . $group['slug'];
                    if ( $group_id == $USERMETA_ACCESS_GROUP_ID ) {
                        for ( $i = 0; $i < count( $usermeta_caps ); $i++ ) {
                            $caps[$usermeta_caps[$i][0] . '_' . $group['slug']] = array(
                                'cap_id' => $usermeta_caps[$i][0] . '_' . $group['slug'],
                                'title' => $usermeta_caps[$i][2],
                                'default_role' => $usermeta_caps[$i][1]
                            );
                        }
                    }
                }
            }
        }

        return $caps;
    }

    // register a new Types Access Group within Area for Usermeta Fields Groups Frontend capabilities
    public static function register_access_usermeta_groups( $groups, $id )
    {
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';

        if ( $id == $USERMETA_ACCESS_AREA_ID ) {
            $fields_groups = wpcf_admin_fields_get_groups( TYPES_USER_META_FIELD_GROUP_CPT_NAME );
            if ( !empty( $fields_groups ) ) {
                foreach ( $fields_groups as $group ) {
                    $USERMETA_ACCESS_GROUP_NAME = $group['name'];
                    //. ' User Meta Fields Access Group'
                    $USERMETA_ACCESS_GROUP_ID = '__USERMETA_FIELDS_GROUP_' . $group['slug'];
                    $groups[] = array('id' => $USERMETA_ACCESS_GROUP_ID, 'name' => '' . $USERMETA_ACCESS_GROUP_NAME);
                }
            }
        }
        return $groups;
    }

	/**
	* Register a custom tab on the Access Control admin page, for Types fields.
	*
	* @param $tabs
	* @return $tabs
	*
	* @since 2.1
	*/

	public static function register_access_types_fields_tab( $tabs ) {
		$tabs['types-fields'] = __( 'Types Fields', 'wp-cred' );
		return $tabs;
	}

    // register a new Types Access Area for Usermeta Fields Groups Frontend capabilities
    public static function register_access_usermeta_area( $areas,
            $area_type = 'usermeta' )
    {
        $USERMETA_ACCESS_AREA_NAME = __( 'User Meta Fields Access', 'wpcf' );
        $USERMETA_ACCESS_AREA_ID = '__USERMETA_FIELDS';
        $areas[] = array('id' => $USERMETA_ACCESS_AREA_ID, 'name' => $USERMETA_ACCESS_AREA_NAME);
        return $areas;
    }

}

/*
 *  Register Post Fields Groups in Types Access
 *
 * @author Gen gen.i@icanlocalize.com
 * @since Types 1.3
 */

class Post_Fields_Access
{

	/**
	 * Note that this property will be initialized only if Access is active.
	 *
	 * @var array|string
	 */
	public static $fields_groups = '';


	public function __construct() {
		// setup custom capabilities
		if ( ! function_exists( 'wpcf_access_register_caps' ) ) {
			// Nothing to do because Access is not active.
			return;
		}

		// integrate with Types Access
		// Get list of groups - at this point we already know we need it.
		self::$fields_groups = wpcf_admin_fields_get_groups();

		if ( empty( self::$fields_groups ) ) {
			return;
		}

		$access_version = apply_filters( 'toolset_access_version_installed', '1.0' );
		// Since 2.1 we can define a custom tab on Access >= 2.1
		if ( version_compare( $access_version, '2.0' ) > 0 ) {
			// Add Types Fields tab
			add_filter( 'types-access-tab', array( 'Post_Fields_Access', 'register_access_types_fields_tab' ) );
			// Add Usermeta Fields area
			add_filter(
				'types-access-area-for-types-fields',
				array( 'Post_Fields_Access', 'register_access_fields_area' ),
				10, 2
			);
		} else {
			//Add Usermeta Fields area
			add_filter(
				'types-access-area',
				array( 'Post_Fields_Access', 'register_access_fields_area' ),
				10, 2
			);
		}
		//Add Fields groups
		add_filter(
			'types-access-group',
			array( 'Post_Fields_Access', 'register_access_fields_groups' ),
			10, 2
		);

		//Add Fields caps to groups
		add_filter(
			'types-access-cap',
			array( 'Post_Fields_Access', 'register_access_fields_caps' ),
			10, 3
		);
	}

    // register custom CRED Frontend capabilities specific to each group
    public static function register_access_fields_caps( $caps, $area_id, $group_id ) {
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';
        $default_role = 'guest'; //'administrator';
        //List of caps with default permissions
        $fields_caps = array(
            /*array('view_fields_on_site', $default_role, __( 'View Fields On Site', 'wpcf' )),*/
            array('view_fields_in_edit_page', $default_role, __( 'View Fields In Edit Page', 'wpcf' )),
            array('modify_fields_in_edit_page', 'author', __( 'Modify Fields In Edit Page', 'wpcf' )),
        );
        if ( $area_id == $FIELDS_ACCESS_AREA_ID ) {

            if ( !empty( self::$fields_groups ) ) {
                foreach ( self::$fields_groups as $group ) {
                    $FIELDS_ACCESS_GROUP_ID = '__FIELDS_GROUP_' . $group['slug'];
                    if ( $group_id == $FIELDS_ACCESS_GROUP_ID ) {
                        for ( $i = 0; $i < count( $fields_caps ); $i++ ) {
                            $caps[$fields_caps[$i][0] . '_' . $group['slug']] = array(
                                'cap_id' => $fields_caps[$i][0] . '_' . $group['slug'],
                                'title' => $fields_caps[$i][2],
                                'default_role' => $fields_caps[$i][1]
                            );
                        }
                    }
                }
            }
        }

        return $caps;
    }

    // register a new Types Access Group within Area for Post Fields Groups Frontend capabilities
    public static function register_access_fields_groups( $groups, $id )
    {
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';

        if ( $id == $FIELDS_ACCESS_AREA_ID ) {
            if ( !empty( self::$fields_groups ) ) {
                foreach ( self::$fields_groups as $group ) {
                    $FIELDS_ACCESS_GROUP_NAME = $group['name'];
                    //. ' User Meta Fields Access Group'
                    $FIELDS_ACCESS_GROUP_ID = '__FIELDS_GROUP_' . $group['slug'];
                    $groups[] = array('id' => $FIELDS_ACCESS_GROUP_ID, 'name' => '' . $FIELDS_ACCESS_GROUP_NAME);
                }
            }
        }
        return $groups;
    }


	/**
	 * Register a custom tab on the Access Control admin page, for Types fields.
	 *
	 * @param $tabs
	 *
	 * @since 2.1
	 * @return mixed
	 */
	public static function register_access_types_fields_tab( $tabs ) {
		$tabs['types-fields'] = __( 'Types Fields', 'wp-cred' );
		return $tabs;
	}


    // register a new Types Access Area for Post Fields Groups Frontend capabilities
    public static function register_access_fields_area( $areas,
            $area_type = 'usermeta' )
    {
        $FIELDS_ACCESS_AREA_NAME = __( 'Post Meta Fields Access', 'wpcf' );
        $FIELDS_ACCESS_AREA_ID = '__FIELDS';
        $areas[] = array('id' => $FIELDS_ACCESS_AREA_ID, 'name' => $FIELDS_ACCESS_AREA_NAME);
        return $areas;
    }

}

add_action( 'wp_ajax_wpcf_types_suggest_user', 'wpcf_access_wpcf_types_suggest_user_ajax' );

/**
 * Suggest user AJAX.
 *
 * @todo nonce
 * @todo auth
 */
function wpcf_access_wpcf_types_suggest_user_ajax()
{
    global $wpdb;
    $users = '';
    $q = '%'.wptoolset_esc_like(esc_sql( trim( $_GET['q'] ) )).'%';
    $found = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, display_name, user_login 
			FROM {$wpdb->users} 
			WHERE user_nicename LIKE %s 
			OR user_login LIKE %s 
			OR display_name LIKE %s 
			OR user_email LIKE %s 
			LIMIT %d",
            $q,
            $q,
            $q,
            $q,
            10
        )
    );

    if ( !empty( $found ) ) {
        foreach ( $found as $user ) {
            $users .= '<li>' . $user->user_login . '</li>';
        }
    }
    echo $users;
    die();
}

add_action('load-user-new.php', 'wpcf_usermeta_add_user_screen');
function wpcf_usermeta_add_user_screen() {
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta-add-user.php';
    wpcf_usermeta_add_user_screen_init();
}

/**
 * Return very simple data of group
 *
 * @since 1.8.0
 *
 * @param string $group_id Group id
 * @return mixed Array if this is proper $group_id or $group_id
 */
function wpcf_admin_get_user_field_group_by_id($group_id)
{
    $args = array(
        'post__in' => array($group_id),
        'post_type' => 'wp-types-user-group',
    );
    $query = new WP_Query($args);
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $data = array(
                'id' => get_the_ID(),
                WPCF_AUTHOR => get_the_author_meta('ID'),
            );
            wp_reset_postdata();
            return $data;
        }
    }
    return $group_id;
}

