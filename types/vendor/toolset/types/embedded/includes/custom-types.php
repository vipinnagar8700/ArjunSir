<?php
/**
 *
 * Post Types embedded code.
 *
 *
 */

use OTGS\Toolset\Common\PostType\EditorMode;

add_action( 'wpcf_type', 'wpcf_filter_type', 10, 2 );

/**
 * Returns default custom type structure.
 *
 * @return array
 */
function wpcf_custom_types_default() {
    return array(
        'labels' => array(
            'name' => '',
            'singular_name' => '',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New %s',
//          'edit' => 'Edit',
            'edit_item' => 'Edit %s',
            'new_item' => 'New %s',
//          'view' => 'View',
            'view_item' => 'View %s',
            'search_items' => 'Search %s',
            'not_found' => 'No %s found',
            'not_found_in_trash' => 'No %s found in Trash',
            'parent_item_colon' => 'Parent %s',
            'menu_name' => '%s',
            'all_items' => '%s',
        ),
        'slug' => '',
        'description' => '',
        'public' => true,
        'capabilities' => false,
        'menu_position' => null,
        'menu_icon' => '',
        'taxonomies' => array(
            'category' => false,
            'post_tag' => false,
        ),
        'supports' => array(
            'title' => true,
            'editor' => true,
            'trackbacks' => false,
            'comments' => false,
            'revisions' => false,
            'author' => false,
            'excerpt' => false,
            'thumbnail' => false,
            'custom-fields' => false,
            'page-attributes' => false,
            'post-formats' => false,
        ),
        'rewrite' => array(
            'enabled' => true,
            'slug' => '',
            'with_front' => true,
            'feeds' => true,
            'pages' => true,
        ),
        'has_archive' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_menu_page' => '',
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'hierarchical' => false,
        'query_var_enabled' => true,
        'query_var' => '',
        'can_export' => true,
        'show_rest' => false,
        'rest_base' => '',
        'show_in_nav_menus' => true,
        'register_meta_box_cb' => '',
        'permalink_epmask' => 'EP_PERMALINK',
        'update' => false,
    );
}

/**
 * Inits custom types.
 */
function wpcf_custom_types_init() {
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    $needs_flush_rewrite_rules = false;
    if ( !empty( $custom_types ) ) {
        foreach ( $custom_types as $post_type => $data ) {
            if ( empty($data) ) {
                continue;
            }
            if (
                ( isset($data['_builtin']) && $data['_builtin'] )
                || wpcf_is_builtin_post_types($post_type)
            ) {
                continue;
            }
            wpcf_custom_types_register( $post_type, $data );

            if(
            	array_key_exists( '_needs_flush_rewrite_rules', $data )
                && $data['_needs_flush_rewrite_rules'] === true
            ) {
            	$needs_flush_rewrite_rules = true;
            	// Unset the flag, we need it only once.
            	unset( $custom_types[ $post_type ]['_needs_flush_rewrite_rules'] );
            	$post_type_option->update_post_types( $custom_types );
            }
        }
    }

    if( $needs_flush_rewrite_rules ) {
    	flush_rewrite_rules();
    }

    /** This filter is documented in wp-admin/wp-admin/edit-form-advanced.php */
    add_filter('enter_title_here', 'wpcf_filter_enter_title_here', 10, 2);
}

/**
 * Enhanced custom menu order
 *
 * "Enhanced" because this is on top of the normal "menu_position", which is an CPT attribute on registration.
 * Using only "menu_position" can end in unexpected results, because WordPress will search the next free menu spot.
 *
 * @see types-969
 *
 * @param $menu_order
 *
 * @return array
 */
function wpcf_custom_menu_order( $menu_order ){
	global $menu, $types_cpt_menu_position;

	$types_admin_menu_order = new \OTGS\Toolset\Types\Site\AdminMenuOrder( $menu, $types_cpt_menu_position );

	if( $menu_ordered = $types_admin_menu_order->get_ordered_menu_for_wp_filter_menu_order() ) {
		return $menu_ordered;
	}

	return $menu_order;
}

/**
 * Run enhanced menu ordering
 * Filter 'types_enhanced_menu_order' added by 3.2
 */
if( apply_filters( 'types_enhanced_menu_order', '__return_true' ) ) {
	// enable custom order
	add_filter( 'custom_menu_order', '__return_true' );
	// apply our menu order
	add_filter( 'menu_order', 'wpcf_custom_menu_order', -1, 1 );
}

/**
 * Registers post type.
 *
 * @param string $post_type
 * @param array $data
 *
 * @return void
 */
function wpcf_custom_types_register( $post_type, $data ) {
	global $types_cpt_menu_position;
	if( $types_cpt_menu_position === null ) {
		$types_cpt_menu_position = array();
	}

    if ( !empty( $data['disabled'] ) ) {
        return;
    }
    $data = apply_filters( 'types_post_type', $data, $post_type );
    // Set labels
    if ( !empty( $data['labels'] ) ) {
        if ( !isset( $data['labels']['name'] ) ) {
            $data['labels']['name'] = $post_type;
        }
        if ( !isset( $data['labels']['singular_name'] ) ) {
            $data['labels']['singular_name'] = $data['labels']['name'];
        }
        foreach ( $data['labels'] as $label_key => $label ) {
            $data['labels'][$label_key] = $label = stripslashes( $label );

            // Allows several instances of %s in the label.
            $label = str_replace( '%s', '%1$s', $label );

            switch ( $label_key ) {
                case 'add_new_item':
                case 'edit_item':
                case 'new_item':
                case 'view_item':
                case 'parent_item_colon':
                    $data['labels'][$label_key] = sprintf( $label,
                            $data['labels']['singular_name'] );
                    break;

                case 'search_items':
                case 'all_items':
                case 'not_found':
                case 'not_found_in_trash':
                case 'menu_name':
                    $data['labels'][$label_key] = sprintf( $label,
                            $data['labels']['name'] );
                    break;
            }
        }
    }
    $data['description'] = !empty( $data['description'] ) ? htmlspecialchars( stripslashes( $data['description'] ),
                    ENT_QUOTES ) : '';
    $data['public'] = (empty( $data['public'] ) || strval( $data['public'] ) == 'hidden') ? false : true;
    $data['publicly_queryable'] = !empty( $data['publicly_queryable'] );
    $data['exclude_from_search'] = !empty( $data['exclude_from_search'] );
    $data['show_ui'] = (empty( $data['show_ui'] ) || !$data['public']) ? false : true;
    if ( empty( $data['menu_position'] ) ) {
        unset( $data['menu_position'] );
    } else {
    	$menu_position_parts = explode( '--wpcf-add-menu-after--', $data['menu_position'] );
        $data['menu_position'] = intval( $data['menu_position'] );

        if( isset( $menu_position_parts[1] ) && isset( $data['labels']['name'] ) ){
	        $types_cpt_menu_position[ $menu_position_parts[1] ][] = $data['labels']['name'];
        }
    }
    $data['hierarchical'] = !empty( $data['hierarchical'] );
    $data['supports'] = !empty( $data['supports'] ) && is_array( $data['supports'] ) ? array_keys( $data['supports'] ) : array();
    $data['taxonomies'] = !empty( $data['taxonomies'] ) && is_array( $data['taxonomies'] ) ? array_keys( $data['taxonomies'] ) : array();
    $data['has_archive'] = !empty( $data['has_archive'] );
    $data['can_export'] = !empty( $data['can_export'] );
    $data['show_in_rest'] = !empty( $data['show_in_rest'] );
    $data['rest_base'] = !empty( $data['rest_base'] ) ? $data['rest_base'] : $post_type;
    $data['show_in_nav_menus'] = !empty( $data['show_in_nav_menus'] );
    $data['show_in_menu'] = !empty( $data['show_in_menu'] );
    if ( empty( $data['query_var_enabled'] ) ) {
        $data['query_var'] = false;
    } else if ( empty( $data['query_var'] ) ) {
        $data['query_var'] = true;
    }
    if ( !empty( $data['show_in_menu_page'] ) ) {
        $data['show_in_menu'] = $data['show_in_menu_page'];
        $data['labels']['all_items'] = $data['labels']['name'];
    }
    /**
     * menu_icon
     */
    if ( empty( $data['menu_icon'] ) ) {
        unset( $data['menu_icon'] );
    } else {
        $data['menu_icon'] = stripslashes( $data['menu_icon'] );
        if ( strpos( $data['menu_icon'], '[theme]' ) !== false ) {
            $data['menu_icon'] = str_replace( '[theme]',
                    get_stylesheet_directory_uri(), $data['menu_icon'] );
        }
    }
    if ( empty($data['menu_icon'] ) && !empty( $data['icon'] ) ) {
        $data['menu_icon'] = sprintf( 'dashicons-%s', $data['icon'] );
    }
    /**
     * rewrite
     */
    if ( !empty( $data['rewrite']['enabled'] ) ) {
        $data['rewrite']['with_front'] = !empty( $data['rewrite']['with_front'] );
        $data['rewrite']['feeds'] = !empty( $data['rewrite']['feeds'] );
        $data['rewrite']['pages'] = !empty( $data['rewrite']['pages'] );
        if ( !empty( $data['rewrite']['custom'] ) && $data['rewrite']['custom'] != 'custom' ) {
            unset( $data['rewrite']['slug'] );
        }
        unset( $data['rewrite']['custom'] );
    } else {
        $data['rewrite'] = false;
    }

    // Set permalink_epmask
    if ( !empty( $data['permalink_epmask'] ) ) {
        $data['permalink_epmask'] = constant( $data['permalink_epmask'] );
    }

    /**
     * set default support options
     */
    $support_fields = array(
        'editor' => false,
        'author' => false,
        'thumbnail' => false,
        'excerpt' => false,
        'trackbacks' => false,
        'custom-fields' => false,
        'comments' => false,
        'revisions' => false,
        'page-attributes' => false,
        'post-formats' => false,
    );
    $data['supports'] = array_merge_recursive( $data['supports'], $support_fields );

    /**
     * custom slug for has_archive
     */
    if (
        isset($data['has_archive'])
        && $data['has_archive']
        && isset($data['has_archive_slug'])
        && $data['has_archive_slug']
    ) {
        $data['has_archive'] = $data['has_archive_slug'];
    }

    /**
     * check menu icon
     */
    if ( isset($data['menu_icon']) && empty($data['menu_icon']) ) {
        unset($data['menu_icon']);
    }

	// do not handle taxonomy assignments if we have customised taxonomies stored
	$stored_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
	if( isset( $data['taxonomies'] ) && !empty( $stored_taxonomies ) ) {

		// Because of the types-676 bug, we're going to make an extra check and fix the problem.
		// It costs us some performance but we can consider this a temporary fix. This bug has affected
		// mainly users who update regularly, so we can afford to remove it after few versions.

		// Important note here is that $data['taxonomies'] keeps being updated, so we're not risking any data loss.
		$legacy_taxonomy_list = $data['taxonomies'];

		// Compile a list of taxonomy slugs from the new data source (taxonomy definitions)
		$current_taxonomy_list = array();
		foreach( $stored_taxonomies as $stored_taxonomy_slug => $stored_taxonomy ) {
			if( isset( $stored_taxonomy['supports'] )
				&& is_array( $stored_taxonomy['supports'] )
				&& in_array( $post_type, array_keys( $stored_taxonomy['supports'] ) )
			) {
				$current_taxonomy_list[] = $stored_taxonomy['slug'];
			}
		}

		// If $current_taxonomy_list is a subset of $legacy_taxonomy_list, it is an indication of buggy update
		// routine in Types 1.9 or 1.9.1.
		$is_failed_update = ( array_intersect( $current_taxonomy_list, $legacy_taxonomy_list ) == $current_taxonomy_list );
		if( $is_failed_update ) {

			// Mirror assignments from the "legacy" source
			foreach( $legacy_taxonomy_list as $missed_taxonomy_slug ) {
				if( isset( $stored_taxonomies[ $missed_taxonomy_slug ] ) ) {
					$stored_taxonomies[ $missed_taxonomy_slug ][ 'supports' ][ $post_type ] = 1;;
				}
			}

			update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $stored_taxonomies );
		}

		// Still unset the legacy data to avoid double assignment
		unset( $data['taxonomies'] );
	}

	// This is required for pre 3.0.6 created RFGs
	// The problem was that we only set 'public' to false, but as our cpt registration always define
	// all options, the 'public' value is meaningless and each option must be set explicit
	if( isset( $data['is_repeating_field_group'] ) && $data['is_repeating_field_group'] ) {
		$data['exclude_from_search'] = true;
		$data['publicly_queryable'] = false;
		$data['show_in_nav_menus'] = false;
		$data[ 'show_ui'] = false;
	}

	$editor_mode = toolset_getarr( $data, 'editor', EditorMode::CLASSIC );
	if( EditorMode::BLOCK === $editor_mode ) {
		// Block editor chosen. Make sure the post type shows in the REST API,
		// which is a requirement for Gutenberg.
		$data['show_in_rest'] = true;
	} elseif( EditorMode::PER_POST === $editor_mode ) {
		// Block editor might be used for some posts. Make sure the post type shows in the REST API,
		// which is a requirement for Gutenberg.
		$data['show_in_rest'] = true;

		// Additionally, the PerPostEditorMode will do its job when editing a particular post.
	}
	// Otherwise, the classic editor was chosen or the post type was created pre gutenberg.
	// Enforcing the classic editor mode is now being handled in \OTGS\Toolset\Types\TypeRegistration\Controller,
	// because we also need to handle built-in post types which aren't being processed here.

    $args = register_post_type( $post_type, apply_filters( 'wpcf_type', $data, $post_type ) );

    do_action( 'wpcf_type_registered', $args );
}

/**
 * Revised rewrite.
 *
 * We force slugs now. Submitted and sanitized slug. Set slugs localized (WPML).
 * More solid way to force WP slugs.
 *
 * @see https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/153925180/comments
 * @since 1.1.3.2
 */
function wpcf_filter_type( $data, $post_type ) {
    if ( !empty( $data['rewrite']['enabled'] ) ) {
        $data['rewrite']['with_front'] = !empty( $data['rewrite']['with_front'] );
        $data['rewrite']['feeds'] = !empty( $data['rewrite']['feeds'] );
        $data['rewrite']['pages'] = !empty( $data['rewrite']['pages'] );

        // If slug is not submitted use default slug
        if ( empty( $data['rewrite']['slug'] ) ) {
            $data['rewrite']['slug'] = $data['slug'];
        }

        // Also force default slug if rewrite mode is 'normal'
        if ( !empty( $data['rewrite']['custom'] ) && $data['rewrite']['custom'] != 'normal' ) {
            $data['rewrite']['slug'] = $data['rewrite']['slug'];
        }

        // Register with _x()
        $data['rewrite']['slug'] = _x( $data['rewrite']['slug'], 'URL slug',
                'wpcf' );
        //
        // CHANGED leave it for reference if we need
        // to return handling slugs back to WP.
        //
        // We unset slug settings and leave WP to handle it himself.
        // Let WP decide what slugs should be!
//        if (!empty($data['rewrite']['custom']) && $data['rewrite']['custom'] != 'normal') {
//            unset($data['rewrite']['slug']);
//        }
        // Just discard non-WP property
        unset( $data['rewrite']['custom'] );
    } else {
        $data['rewrite'] = false;
    }

    return $data;
}


/**
 * Returns active post types.
 *
 * @return array
 * @deprecated Use the Post Type API instead: https://git.onthegosystems.com/toolset/toolset-common/wikis/post-type-api
 * @since unknown
 */
function wpcf_get_active_custom_types() {
	$post_type_option = new Types_Utils_Post_Type_Option();
	$types = $post_type_option->get_post_types();
	foreach ( $types as $type => $data ) {
		if ( ! empty( $data['disabled'] ) ) {
			unset( $types[ $type ] );
		}
	}

	return $types;
}


/** This action is documented in wp-admin/includes/table.php */
add_filter('dashboard_glance_items', 'wpcf_dashboard_glance_items');

/**
 * Add CPT info to "At a Glance"
 *
 * Add to "At a Glance" WordPress admin dashboard widget information
 * about number of posts.
 *
 * @since 1.6.6
 *
 */
function wpcf_dashboard_glance_items($elements)
{
    // remove when https://core.trac.wordpress.org/ticket/27414 is fixed
    wp_register_style( 'wpcf-fix-wordpress-core', WPCF_EMBEDDED_RES_RELPATH . '/css/fix-wordpress-core.css', array(), WPCF_VERSION );
    wp_enqueue_style( 'wpcf-fix-wordpress-core' );

	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();
    if ( empty( $custom_types ) ) {
        return $elements;
    }
    ksort($custom_types);
    foreach ( $custom_types as $post_type => $data ) {
        if ( !isset($data['dashboard_glance']) || !$data['dashboard_glance'] || $post_type == 'post' || $post_type == 'page' ) {
            continue;
        }
        if ( isset($data['disabled']) && $data['disabled'] ) {
            continue;
        }

        if( $post_type == 'attachment' )
            $data['icon'] = 'admin-media';

        $num_posts = wp_count_posts($post_type);

        $num = $post_type == 'attachment'
            ? number_format_i18n($num_posts->inherit)
            : number_format_i18n($num_posts->publish);

        $text = _n( $data['labels']['singular_name'], $data['labels']['name'], intval($num_posts->publish) );
        $elements[] = sprintf(
            '<a href="%s"%s>%s %s</a>',
            esc_url(
                add_query_arg(
                    array(
                        'post_type' => $post_type,
                    ),
                    admin_url('edit.php')
                )
            ),
            isset($data['icon'])? sprintf('class="dashicons-%s"', $data['icon']):'',
            $num,
            $text
        );
    }
    return $elements;
}

function wpcf_filter_enter_title_here($enter_title_here, $post)
{
    if ( is_object($post) && isset( $post->post_type) ) {
	    $post_type_option = new Types_Utils_Post_Type_Option();
        $custom_types = $post_type_option->get_post_types();
        if (
            true
            && isset($custom_types[$post->post_type])
            && isset($custom_types[$post->post_type]['labels'])
            && isset($custom_types[$post->post_type]['labels']['enter_title_here'])
        ) {
            $enter_title_here = trim($custom_types[$post->post_type]['labels']['enter_title_here']);
        }
    }
    if ( empty($enter_title_here) ) {
        $enter_title_here = __('Enter title here', 'wpcf');
    }
    return $enter_title_here;
}


/**
 * Function to search sub array and returning $array key
 *
 * @since 1.9
 *
 * @param array $array
 * @param string $search
 *
 * @return bool|int|string
 */
function types_get_array_key_search_in_sub( $array, $search ) {
    foreach( $array as $key => $sub_array ) {
        if( in_array( $search, $sub_array ) )
            return $key;
    }

    return false;
}


/**
 * Change labels of build-in post types
 *
 * @since 1.9
 */
function types_rename_build_in_post_types() {
	global $wp_post_types;
	$post_type_option = new Types_Utils_Post_Type_Option();
	$custom_types = $post_type_option->get_post_types();

	if ( ! empty( $custom_types ) ) {
		foreach ( $custom_types as $post_type => $data ) {
			// only for built-in
			if (
				! isset( $data['_builtin'] )
				|| ! $data['_builtin']
				|| ! isset( $data['slug'] )
				|| ! isset( $data['labels']['name'] )
			) {
				continue;
			}

			// check if slug (post/page) exists
			if ( ! isset( $wp_post_types[ $data['slug'] ] ) ) {
				continue;
			}

			// refer $l to post labels
			$labels = &$wp_post_types[ $data['slug'] ]->labels;

			// change name
			$labels->name = isset( $data['labels']['name'] ) ? $data['labels']['name'] : $labels->name;

			// change singular name
			$labels->singular_name = isset( $data['labels']['singular_name'] ) ? $data['labels']['singular_name'] : $labels->singular_name;

			// translators: The "Add New <Item>" label for a built-in post type.
			$labels->add_new_item = sprintf( __( 'Add New %s', 'wpcf' ), $labels->singular_name );
			// translators: The "Add New" label for a built-in post type.
			$labels->add_new = __( 'Add New', 'wpcf' );
			// translators: The "Edit <Item>" label for a built-in post type.
			$labels->edit_item = sprintf( __( 'Edit %s', 'wpcf' ), $labels->singular_name );
			// translators: The "New <Items>" label for a built-in post type.
			$labels->new_item = sprintf( __( 'New %s', 'wpcf' ), $labels->name );
			// translators: The "View <Items>" label for a built-in post type.
			$labels->view_item = sprintf( __( 'View %s', 'wpcf' ), $labels->name );
			// translators: The "Search <Items>" label for a built-in post type.
			$labels->search_items = sprintf( __( 'Search %s', 'wpcf' ), $labels->name );
			// translators: The "No <items> found" label for a built-in post type.
			$labels->not_found = sprintf( __( 'No %s found', 'wpcf' ), strtolower( $labels->name ) );
			// translators: The "No <items> found in Trash" label for a built-in post type.
			$labels->not_found_in_trash = sprintf( __( 'No %s found in Trash', 'wpcf' ), strtolower( $labels->name ) );
			// translators: The "Parent <Items>" label for a built-in post type.
			$labels->parent_item_colon = sprintf( __( 'Parent %s', 'wpcf' ), $labels->name );
			// translators: The "All <Items>" label for a built-in post type.
			$labels->all_items = sprintf( __( 'All %s', 'wpcf' ), $labels->name );
			$labels->menu_name = $labels->name;
			$labels->name_admin_bar = $labels->name;
		}
	}
}

add_action( 'init', 'types_rename_build_in_post_types' );


/**
 * Visibility of inbuild types
 */
function types_visibility_build_in_types() {
	$post_type_option = new Types_Utils_Post_Type_Option();
    $custom_types = $post_type_option->get_post_types();

    // Type: Posts
    if( isset( $custom_types['post']['public'] )
        && $custom_types['post']['public'] == 'hidden' )
        remove_menu_page( 'edit.php' );

    // Type: Pages
    if( isset( $custom_types['page']['public'] )
        && $custom_types['page']['public'] == 'hidden' )
        remove_menu_page( 'edit.php?post_type=page' );

    // Type: Media
    if( isset( $custom_types['attachment']['public'] )
        && $custom_types['attachment']['public'] == 'hidden' )
        remove_menu_page( 'upload.php' );
}

add_action( 'admin_menu', 'types_visibility_build_in_types' );
