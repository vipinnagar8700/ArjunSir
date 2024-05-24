<?php
/*
 * Module Manager
 *
 * Since Types 1.2
 *
 *
 */

define( '_TYPES_MODULE_MANAGER_KEY_', 'types' );
define( '_POSTS_MODULE_MANAGER_KEY_', 'posts' );
define( '_GROUPS_MODULE_MANAGER_KEY_', 'groups' );
define( '_FIELDS_MODULE_MANAGER_KEY_', 'fields' );
define( '_TAX_MODULE_MANAGER_KEY_', 'taxonomies' );
define( '_RELATIONSHIPS_MODULE_MANAGER_KEY_', 'm2m_relationships' );

/**
 * Fields table.
 */
function wpcf_module_inline_table_fields()
{
    // dont add module manager meta box on new post type form
    if ( !defined( 'MODMAN_PLUGIN_NAME' ) ) {
        _e('There is a problem with Module Manager', 'wpcf');
        return;
    }
    if ( !isset( $_GET['group_id'] ) ) {
        _e('There is a problem with Module Manager', 'wpcf');
        return;
    }
    $group = wpcf_admin_fields_get_group( (int) $_GET['group_id'] );
    if ( empty($group) ) {
        _e('Wrong group id.', 'wpcf');
        return;
    }
    do_action(
        'wpmodules_inline_element_gui',
        array(
            'id' => '12' . _GROUPS_MODULE_MANAGER_KEY_ . '21' . $group['id'],
            'title' => $group['name'],
            'section' => _GROUPS_MODULE_MANAGER_KEY_,
        )
    );
}

/**
 * Post Types table.
 */
add_filter('wpcf_meta_box_order_defaults', 'wpcf_module_post_add_meta_box', 10, 2);

function wpcf_module_post_add_meta_box($meta_box_order_defaults, $type)
{
    if ( !defined( 'MODMAN_PLUGIN_NAME' )) {
        return $meta_box_order_defaults;
    }
    switch($type)
    {
    case 'post_type':
        if ( isset( $_GET['wpcf-post-type'] ) ) {
            $meta_box_order_defaults['module_manager_post'] = array(
                'callback' => 'wpcf_admin_metabox_module_manager_post',
                'title' => __('Module Manager', 'wpcf'),
                'default' => 'side',
                'priority' => 'low',
            );
        }
        break;
    case 'taxonomy':
        if (  isset( $_GET['wpcf-tax'] ) ) {
            $meta_box_order_defaults['module_manager_post'] = array(
                'callback' => 'wpcf_admin_metabox_module_manager_taxonomy',
                'title' => __('Module Manager', 'wpcf'),
                'default' => 'side',
                'priority' => 'low',
            );
        }
        break;
    case 'wp-types-group':
        if (  isset( $_GET['group_id'] ) ) {
            $meta_box_order_defaults['module_manager_post'] = array(
                'callback' => 'wpcf_module_inline_table_fields',
                'title' => __('Module Manager', 'wpcf'),
                'default' => 'side',
                'priority' => 'low',
            );
        }
        break;

    }
    return $meta_box_order_defaults;
}

function wpcf_admin_metabox_module_manager_post()
{
    return wpcf_admin_metabox_module_manager('post');
}

function wpcf_admin_metabox_module_manager_taxonomy()
{
    return wpcf_admin_metabox_module_manager('taxonomy');
}

function wpcf_admin_metabox_module_manager($type)
{
    $form = array();
    /**
     * box content
     */
    ob_start();
    switch($type) {
    case 'post':
        wpcf_module_inline_table_post_types();
        break;
    case 'taxonomy':
        wpcf_module_inline_table_post_taxonomies();
        break;
    default:
        _e('Wrong type!', 'wpcf');
        break;
    }
    $markup = ob_get_contents();
    ob_end_clean();
    $form['table-mm'] = array(
        '#type' => 'markup',
        '#markup' => $markup,
    );
    /**
     * render form
     */
    $form = wpcf_form(__FUNCTION__, $form);
    echo $form->renderForm();
}

function wpcf_module_inline_table_post_types() {
    // dont add module manager meta box on new post type form
    if ( defined( 'MODMAN_PLUGIN_NAME' ) && isset( $_GET['wpcf-post-type'] ) ) {
	    $post_type_option = new Types_Utils_Post_Type_Option();
        $_custom_types = $post_type_option->get_post_types();
        if ( isset( $_custom_types[$_GET['wpcf-post-type']] ) ) {
            $_post_type = $_custom_types[$_GET['wpcf-post-type']];
            // add module manager meta box to post type form
            $element = array('id' => '12' . _TYPES_MODULE_MANAGER_KEY_ . '21' . $_post_type['slug'], 'title' => $_post_type['labels']['singular_name'], 'section' => _TYPES_MODULE_MANAGER_KEY_);
            do_action( 'wpmodules_inline_element_gui', $element );
        }
    }
}

/**
 * Taxonomies table.
 */
function wpcf_module_inline_table_post_taxonomies() {
    // dont add module manager meta box on new post type form
    if ( defined( 'MODMAN_PLUGIN_NAME' ) && isset( $_GET['wpcf-tax'] ) ) {
        $_custom_taxes = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
        if ( isset( $_custom_taxes[$_GET['wpcf-tax']] ) ) {
            $_tax = $_custom_taxes[$_GET['wpcf-tax']];
            // add module manager meta box to post type form
            $element = array('id' => '12' . _TAX_MODULE_MANAGER_KEY_ . '21' . $_tax['slug'],
                'title' => $_tax['labels']['singular_name'], 'section' => _TAX_MODULE_MANAGER_KEY_);
            do_action( 'wpmodules_inline_element_gui', $element );
        }
    }
}

// setup module manager hooks and actions
if ( defined( 'MODMAN_PLUGIN_NAME' ) ) {
    add_filter( 'wpmodules_register_sections', 'wpcf_register_modules_sections',
            10, 1 );

    // Post Types
    add_filter( 'wpmodules_register_items_' . _TYPES_MODULE_MANAGER_KEY_,
            'wpcf_register_modules_items_types', 10, 1 );
    add_filter( 'wpmodules_export_items_' . _TYPES_MODULE_MANAGER_KEY_,
            'wpcf_export_modules_items_types', 10, 2 );
    add_filter( 'wpmodules_import_items_' . _TYPES_MODULE_MANAGER_KEY_,
            'wpcf_import_modules_items_types', 10, 2 );

    // Relationships
    add_filter( 'wpmodules_register_items_' . _RELATIONSHIPS_MODULE_MANAGER_KEY_,
            'wpcf_register_modules_items_relationships', 10, 1 );
    add_filter( 'wpmodules_export_items_' . _RELATIONSHIPS_MODULE_MANAGER_KEY_,
            'wpcf_export_modules_items_relationships', 10, 2 );
    add_filter( 'wpmodules_import_items_' . _RELATIONSHIPS_MODULE_MANAGER_KEY_,
            'wpcf_import_modules_items_relationships', 10, 2 );

    // Groups
    add_filter( 'wpmodules_register_items_' . _GROUPS_MODULE_MANAGER_KEY_,
            'wpcf_register_modules_items_groups', 10, 1 );
    add_filter( 'wpmodules_export_items_' . _GROUPS_MODULE_MANAGER_KEY_,
            'wpcf_export_modules_items_groups', 10, 3 );
    add_filter( 'wpmodules_import_items_' . _GROUPS_MODULE_MANAGER_KEY_,
            'wpcf_import_modules_items_groups', 10, 2 );

    // Taxonomies
    add_filter( 'wpmodules_register_items_' . _TAX_MODULE_MANAGER_KEY_,
            'wpcf_register_modules_items_taxonomies', 10, 1 );
    add_filter( 'wpmodules_export_items_' . _TAX_MODULE_MANAGER_KEY_,
            'wpcf_export_modules_items_taxonomies', 10, 2 );
    add_filter( 'wpmodules_import_items_' . _TAX_MODULE_MANAGER_KEY_,
            'wpcf_import_modules_items_taxonomies', 10, 2 );

    // Check items
		add_filter( 'wpmodules_items_check_' . _TYPES_MODULE_MANAGER_KEY_,
            'wpcf_modman_items_check_custom_post_types', 10, 2 );
    add_filter( 'wpmodules_items_check_' . _GROUPS_MODULE_MANAGER_KEY_,
            'wpcf_modman_items_check_groups', 10, 2 );
    add_filter( 'wpmodules_items_check_' . _TAX_MODULE_MANAGER_KEY_,
            'wpcf_modman_items_check_taxonomies', 10, 2 );
	add_filter( 'wpmodules_items_check_' . _RELATIONSHIPS_MODULE_MANAGER_KEY_,
		'wpcf_modman_items_check_relationships', 10, 2 );

		// Filter modules result.
		add_filter( 'wpmodules_saved_items', 'wpcf_modman_wpmodules_saved_items' );

	//Module manager: Hooks for adding plugin version

	/*Export*/
    add_filter('wpmodules_export_pluginversions_'._GROUPS_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');
    add_filter('wpmodules_export_pluginversions_'._TYPES_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');
    add_filter('wpmodules_export_pluginversions_'._TAX_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');

    /*Import*/
    add_filter('wpmodules_import_pluginversions_'._GROUPS_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');
    add_filter('wpmodules_import_pluginversions_'._TYPES_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');
    add_filter('wpmodules_import_pluginversions_'._TAX_MODULE_MANAGER_KEY_,'wpcf_modman_get_plugin_version');

    /*
     * Module Manager Functions
     */

    function wpcf_modman_get_plugin_version() {

    	if (defined( 'WPCF_VERSION' )) {

    		return WPCF_VERSION;

    	}

    }

    function wpcf_register_modules_sections( $sections ) {
        $sections[_TYPES_MODULE_MANAGER_KEY_] = array(
            'title' => __( 'Post Types', 'wpcf' ),
            'icon' => WPCF_EMBEDDED_RES_RELPATH . '/images/types-icon-color_12X12.png',
            'icon_css' => 'icon-types-logo ont-icon-16 ont-color-orange'
        );

	    $sections[_RELATIONSHIPS_MODULE_MANAGER_KEY_] = array(
		    'title' => __( 'Relationships', 'wpcf' ),
		    'icon' => WPCF_EMBEDDED_RES_RELPATH . '/images/types-icon-color_12X12.png',
		    'icon_css' => 'icon-types-logo ont-icon-16 ont-color-orange'
	    );

	    $sections[_GROUPS_MODULE_MANAGER_KEY_] = array(
            'title' => __( 'Field Groups', 'wpcf' ),
            'icon' => WPCF_EMBEDDED_RES_RELPATH . '/images/types-icon-color_12X12.png',
            'icon_css' => 'icon-types-logo ont-icon-16 ont-color-orange'
        );
        // no individual fields are exported
        /* $sections[_FIELDS_MODULE_MANAGER_KEY_]=array(
          'title'=>__('Fields','wpcf'),
          'icon'=>WPCF_EMBEDDED_RES_RELPATH.'/images/types-icon-color_12X12.png'
          ); */
        $sections[_TAX_MODULE_MANAGER_KEY_] = array(
            'title' => __( 'Taxonomies', 'wpcf' ),
            'icon' => WPCF_EMBEDDED_RES_RELPATH . '/images/types-icon-color_12X12.png',
            'icon_css' => 'icon-types-logo ont-icon-16 ont-color-orange'
        );

        return $sections;
    }

	/**
	 * Returns post types for module manager
	 *
	 * @param array $items Previous items handled by the filter.
	 * @param array[]
	 *
	 * @return array
	 */
    function wpcf_register_modules_items_types( $items, $custom_types = null ) {
	    $post_type_option = new Types_Utils_Post_Type_Option();
        $custom_types = ! $custom_types
            ? $post_type_option->get_post_types()
            : $custom_types;

        $custom_types = array_map( function( $post_type ) {
	        if( $post_type instanceof WP_Post_Type ) {
        		// Transform the post type object to an array, for the purpose of the function below.
        		$post_type = (array) $post_type;
        		$post_type['slug'] = $post_type['name'];
	        } elseif( $post_type instanceof Toolset_Post_Type_From_Types ) {
	        	$post_type = $post_type->get_definition();
	        } elseif( $post_type instanceof IToolset_Post_Type ) {
	        	$post_type = (array) $post_type->get_wp_object();
		        $post_type['slug'] = $post_type['name'];
	        }

	        // This should be a post type definition array as expected below.
	        return $post_type;
        }, $custom_types );

        foreach ( $custom_types as $type ) {
	        if (isset($type['_builtin']) && $type['_builtin']) {
		        // skip builtin post type
		        continue;
	        }

        	if( isset( $type['labels'] ) && is_object( $type['labels'] ) ) {
	        	// convert labels to array
        		$type['labels'] = (array) $type['labels'];
	        }

        	if( ! is_array( $type )
	            || empty($type)
	            || ! isset( $type['public'] )
	            || ! isset( $type['slug'] )
		        || ! isset( $type['labels'] )
		        || ! isset( $type['description'] )
	            || ! isset( $type['labels']['name'] ) || ! isset( $type['labels']['singular_name'] )
	        ) {
				// we proof all required fields here, if one is missing skip the cpt
		        continue;
	        }

            $_details = sprintf( __( '%s post type: %s', 'wpcf' ), ucfirst( $type['public'] ), $type['labels']['name'] );
            $details = !empty( $type['description'] ) ? $type['description'] : $_details;
            $items[] = array(
                'id' => '12' . _TYPES_MODULE_MANAGER_KEY_ . '21' . $type['slug'],
                'title' => $type['labels']['singular_name'],
                'details' => '<p style="padding:5px;">' . $details . '</p>',
                '__types_id' => $type['slug'],
                '__types_title' => $type['labels']['name'],
            );
        }

        return $items;
    }

    function wpcf_export_modules_items_types( $res, $items ) {
        $existing_types = array();
        foreach ( $items as $ii => $item ) {
            if ( isset( $item['id'] ) && ! in_array( $item['id'], $existing_types ) ) {
                $items[$ii] = str_replace( '12' . _TYPES_MODULE_MANAGER_KEY_ . '21',
                        '', $item['id'] );
            }
            $existing_types[] = $item['id'];
        }
        $xmlstring = wpcf_admin_export_selected_data( $items, 'types',
                'module_manager' );
        return $xmlstring;
    }

    function wpcf_import_modules_items_types( $result, $xmlstring ) {
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
        $result2 = wpcf_admin_import_data_from_xmlstring( $xmlstring, 'types',
                'modman' );
        if ( false === $result2 || is_wp_error( $result2 ) )
            return (false === $result2) ? __( 'Error during Post Types import', 'wpcf' ) : $result2->get_error_message( $result2->get_error_code() );

        return $result2;
    }

	/**
	 * Register m2m Relationships
	 * @param array    $items
	 * @param string[] $selected_relationships List of existing relationships.
	 *
	 * @return array
	 *
	 * @since 3.0
	 */
	function wpcf_register_modules_items_relationships( $items, $selected_relationships = null ) {
		do_action( 'toolset_do_m2m_full_init' );

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return array();
		}

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		$relationship_definitions = $relationship_repository->get_definitions();

		foreach( $relationship_definitions as $relationship_definition ) {
			if ( null !== $selected_relationships ) {
				if ( ! in_array( $relationship_definition->get_slug(), $selected_relationships ) ) {
					continue;
				}
			}
			$parent_types_slugs = $relationship_definition->get_parent_type()->get_types();
			$child_types_slugs = $relationship_definition->get_child_type()->get_types();

			$parent_types_labels = $child_types_labels = array( 'singular' => array(), 'plural' => array() );

			$post_types_list = array();

			foreach( $parent_types_slugs as $slug ) {
				if( $post_type = $post_type_repository->get( $slug ) ) {
					$parent_types_labels['plural'][] = $post_type->get_label( );
					$parent_types_labels['singular'][] = $post_type->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME );
					$post_types_list[ $slug ] = $post_type;
				}
			}

			foreach( $child_types_slugs as $slug ) {
				if( $post_type = $post_type_repository->get( $slug ) ) {
					$child_types_labels['plural'][] = $post_type->get_label( );
					$child_types_labels['singular'][] = $post_type->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME );
					$post_types_list[ $slug ] = $post_type;
				}
			}

			// repeatable field group
			if( $relationship_definition->get_origin()->get_origin_keyword()
			    == Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ) {

				$details = sprintf( 'Repeatable Field Group used on %s',
					implode( ' / ', $parent_types_labels['plural'] )
				);
			// post reference field
			} else if( $relationship_definition->get_origin()->get_origin_keyword()
			           == Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ) {
				$details = sprintf( 'Post Reference Field for %s used on %s',
					implode( ' / ', $parent_types_labels['plural'] ),
					implode( ' / ', $child_types_labels['plural'] )
				);
			// wizard created relationship (or any other origin, which does not exist while writing this)
			} else {
				$details = sprintf( 'A %s (%s) relationship between post types %s and %s',
					$relationship_definition->get_cardinality()->get_type(),
					$relationship_definition->get_cardinality()->to_string(),
					implode( ' / ', $parent_types_labels['singular'] ),
					implode( ' / ', $child_types_labels['singular'] )
				);
			}

			ModMan_Loader::tpl('modules', array(
					'onlyfunctions' => true
				)
			);
			$post_type_modules = modman_list_items(
				wpcf_register_modules_items_types( array(), $post_types_list ),
				'types',
				null,
				'icon-types-logo ont-icon-16 ont-color-gray',
				false,
				false
			);

			$items[] = array(
				'id' => '12' . _RELATIONSHIPS_MODULE_MANAGER_KEY_ . '21' . $relationship_definition->get_slug(),
				'title' => $relationship_definition->get_display_name(),
				'details' => '<p style="padding:5px;">' . $details . $post_type_modules . '</p>',
				'__types_id' => $relationship_definition->get_slug(),
				'__types_title' => $relationship_definition->get_display_name()
			);
		}

		return $items;
	}

	/**
	 * Export m2m relationships
	 * @param $res
	 * @param $items
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	function wpcf_export_modules_items_relationships( $res, $items ) {
		foreach ( $items as $ii => $item ) {
			if ( isset( $item['id'] ) ) {
				$items[$ii] = str_replace( '12' . _RELATIONSHIPS_MODULE_MANAGER_KEY_ . '21',
					'', $item['id'] );
			}
		}
		$xmlstring = wpcf_admin_export_selected_data( $items, 'relationships',
			'module_manager' );
		return $xmlstring;
	}

	/**
	 * Import m2m relationships
	 *
	 * @param $result
	 * @param $xmlstring
	 *
	 * @return bool|string|void|WP_Error
	 *
	 * @since 3.0
	 */
	function wpcf_import_modules_items_relationships( $result, $xmlstring ) {
		require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
		$result2 = wpcf_admin_import_data_from_xmlstring( $xmlstring, 'm2m_relationships', 'modman' );
		if ( false === $result2 || is_wp_error( $result2 ) )
			return (false === $result2) ? __( 'Error during Post Types import', 'wpcf' ) : $result2->get_error_message( $result2->get_error_code() );

		return $result2;
	}

    function wpcf_register_modules_items_groups( $items ) {
        $groups = wpcf_admin_fields_get_groups();
        foreach ( $groups as $group ) {
            $_details = sprintf( __( 'Fields group: %s', 'wpcf' ),
                    $group['name'] );
            $details = !empty( $group['description'] ) ? $group['description'] : $_details;

            $relationship_slugs = wpcf_get_relationships_included_in_field_groups( $group['slug'] );

            ModMan_Loader::tpl('modules', array(
                    'onlyfunctions' => true
                )
            );
            $relationship_modules = modman_list_items(
                wpcf_register_modules_items_relationships( array(), $relationship_slugs ),
                'm2m_relationships',
                null,
                'icon-types-logo ont-icon-16 ont-color-gray',
                false,
                false
            );

            $items[] = array(
                'id' => '12' . _GROUPS_MODULE_MANAGER_KEY_ . '21' . $group['id'],
                'title' => $group['name'],
                'details' => '<p style="padding:5px;">' . $details . $relationship_modules . '</p>',
                '__types_id' => $group['slug'],
                '__types_title' => $group['name'],
            );
        }
        return $items;
    }

    function wpcf_export_modules_items_groups( $res, $items, $use_cache = false ) {
        foreach ( $items as $ii => $item ) {
            $items[$ii] = intval( str_replace( '12' . _GROUPS_MODULE_MANAGER_KEY_ . '21',
                            '', $item['id'] ) );
        }
        $xmlstring = wpcf_admin_export_selected_data( $items, 'groups',
                'module_manager', $use_cache );
        return $xmlstring;
    }

    function wpcf_import_modules_items_groups( $result, $xmlstring ) {
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
        $result2 = wpcf_admin_import_data_from_xmlstring( $xmlstring, 'groups',
                'modman' );
        if ( false === $result2 || is_wp_error( $result2 ) )
            return (false === $result2) ? __( 'Error during Field Groups import', 'wpcf' ) : $result2->get_error_message( $result2->get_error_code() );

        return $result2;
    }

    function wpcf_register_modules_items_taxonomies( $items ) {
        $custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );

        foreach ( $custom_taxonomies as $tax ) {
            $_details = sprintf( __( 'Fields group: %s', 'wpcf' ),
                    $tax['labels']['name'] );
            $details = !empty( $tax['description'] ) ? $tax['description'] : $_details;
            $items[] = array(
                'id' => '12' . _TAX_MODULE_MANAGER_KEY_ . '21' . $tax['slug'],
                'title' => $tax['labels']['singular_name'],
                'details' => '<p style="padding:5px;">' . $details . '</p>',
                '__types_id' => $tax['slug'],
                '__types_title' => $tax['labels']['name'],
            );
        }
        return $items;
    }

    function wpcf_export_modules_items_taxonomies( $res, $items ) {
        foreach ( $items as $ii => $item ) {
            if ( isset( $item['id'] ) ) {
                $items[$ii] = str_replace( '12' . _TAX_MODULE_MANAGER_KEY_ . '21',
                        '', $item['id'] );
            }
        }
        $xmlstring = wpcf_admin_export_selected_data( $items, 'taxonomies',
                'module_manager' );
        return $xmlstring;
    }

    function wpcf_import_modules_items_taxonomies( $result, $xmlstring ) {
        require_once WPCF_EMBEDDED_INC_ABSPATH . '/import-export.php';
        $result2 = wpcf_admin_import_data_from_xmlstring( $xmlstring,
                'taxonomies', 'modman' );
        if ( false === $result2 || is_wp_error( $result2 ) )
            return (false === $result2) ? __( 'Error during Taxonomies import', 'wpcf' ) : $result2->get_error_message( $result2->get_error_code() );

        return $result2;
    }

}

/**
 * Custom Export function for Module Manager.
 *
 * Exports selected items (by ID) and of specified type (eg views, view-templates).
 * Returns xml string.
 *
 * @param array $items
 * @param string $_type
 * @param string $return mixed array|xml|download
 * @return string|array
 */
function wpcf_admin_export_selected_data( array $items, $_type = 'all', $return = 'download', $use_cache = false)
{
    global $wpcf;

    $xml = new ICL_Array2XML();
    $data = array();
    $data['settings'] = wpcf_get_settings();

	// m2m relationships...
    if( apply_filters( 'toolset_is_m2m_enabled', false ) === true ) {
	    // ...on all data export
	    if ( $_type === 'all' ) {
		    do_action( 'toolset_do_m2m_full_init' );
		    $relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		    $relationship_repository->load_definitions();
		    $relationships = $relationship_repository->get_definitions();

		    if( ! empty( $relationships ) ) {
			    $relationship_definition_translator = new Toolset_Relationship_Definition_Translator();

			    // let's make an array of our relationships
			    foreach( $relationships as $relationship ) {
				    $relationship_array = array( 'id' => $relationship->get_row_id() );
				    $relationship_array = $relationship_array + $relationship_definition_translator->to_database_row( $relationship );

				    // list parent types
				    $relationship_array['parent_types'] = array();
				    foreach( $relationship->get_parent_type()->get_types() as $type ) {
					    $relationship_array['parent_types'][$type] = true;
				    }

				    // list child types
				    $relationship_array['child_types'] = array();
				    foreach( $relationship->get_child_type()->get_types() as $type ) {
					    $relationship_array['child_types'][$type] = true;
				    }
				    $data['m2m_relationships'][$relationship->get_slug()] = $relationship_array;
			    }
		    }
	    }

	    // ...on relationships export only (module manager)
	    else if ( 'relationships' === $_type ) {
		    do_action( 'toolset_do_m2m_full_init' );
		    $relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		    $relationship_repository->load_definitions();

		    $relationship_definition_translator = new Toolset_Relationship_Definition_Translator();
		    foreach ( $items as $relationship_slug ) {

			    $relationship = $relationship_repository->get_definition( $relationship_slug );
			    if ( $relationship ) {
				    $relationship_array = array( 'id' => $relationship->get_row_id() );
				    $relationship_array = $relationship_array + $relationship_definition_translator->to_database_row( $relationship );

				    // list parent types
				    $relationship_array['parent_types'] = array();
				    foreach( $relationship->get_parent_type()->get_types() as $type ) {
					    $relationship_array['parent_types'][$type] = true;
				    }

				    // list child types
				    $relationship_array['child_types'] = array();
				    foreach( $relationship->get_child_type()->get_types() as $type ) {
					    $relationship_array['child_types'][$type] = true;
				    }
				    $relationship_array['__types_id'] = $relationship->get_slug();
				    $relationship_array['__types_title'] = $relationship->get_display_name();
				    $relationship_array['hash'] = $relationship_array['checksum'] = $wpcf->export->generate_checksum( 'relationship', $relationship->get_slug() );
				    $data['m2m_relationships'][$relationship->get_slug()] = $relationship_array;
			    }
		    }

		    if ( 'module_manager' === $return ) {
			    $items = array();
			    foreach ( $data['m2m_relationships'] as $relationship_slug => $relationship_data ) {
				    $_item = array();
				    $_item['id'] = $relationship_data['__types_id'];
				    $_item['title'] = $relationship_data['__types_title'];
				    $_item['hash'] = $_item['checksum'] = $relationship_data['hash'];
				    $items[$relationship_data['__types_id']] = $_item;
			    }
			    return array(
				    'xml' => $xml->array2xml( $data, 'm2m_relationships' ),
				    'items' => $items,
			    );
		    }
	    }
    }


    if ( 'user_groups' === $_type || 'all' === $_type ) {
        // Get groups
        if ( empty( $items ) ) {
            $groups = get_posts(
                array(
                    'post_type' => TYPES_USER_META_FIELD_GROUP_CPT_NAME,
                    'post_status' => null,
                    'numberposts' => '-1',
                )
            );
        } else {
            /*
             * This fails
             * $items are in form of:
             * 0 => array('id' => 'pt', ...)
             */
            foreach ( $items as $k => $item ) {
                if ( isset( $item['id'] ) ) {
                    $items[ $k ] = (int) wpcf_modman_get_submitted_id( 'groups', $item['id'] );
                }
            }
            $args = array(
                'post__in' => $items,
                'post_type' => TYPES_USER_META_FIELD_GROUP_CPT_NAME,
                'post_status' => 'all',
                'posts_per_page' => -1
            );
            $groups = get_posts( $args );
        }
        if ( !empty( $groups ) ) {
            $data['user_groups'] = array('__key' => 'group');
            foreach ( $groups as $key => $post ) {
                $post = (array) $post;
                $post_data = array();
                $copy_data = array('ID', 'post_content', 'post_title',
                    'post_excerpt', 'post_type', 'post_status');
                foreach ( $copy_data as $copy ) {
                    if ( isset( $post[$copy] ) ) {
                        $post_data[$copy] = $post[$copy];
                    }
                }
                $_data = $post_data;
                $meta = get_post_custom( $post['ID'] );
                if ( !empty( $meta ) ) {
                    $_meta = array();
                    foreach ( $meta as $meta_key => $meta_value ) {
                        if ( in_array( $meta_key, array(
							'_wp_types_group_showfor',
							'_wp_types_group_fields',
							'_wp_types_group_admin_styles',
							Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
						), true )
                        ) {
                            $_meta[$meta_key] = $meta_value[0];
                        }
                    }
                    if ( !empty( $_meta ) ) {
                        $_data['meta'] = $_meta;
                    }
                }
                $_data['checksum'] = $_data['hash'] = $wpcf->export->generate_checksum( 'group',
                    $post['ID'] );
                $_data['__types_id'] = $post['post_name'];
                $_data['__types_title'] = $post['post_title'];
                $data['user_groups']['group-' . $post['ID']] = $_data;
            }
        }

        if ( !empty( $items ) ) {
            // Get fields by group
            // TODO Document why we use by_group
            $fields = array();
            foreach ( $groups as $key => $post ) {
                $fields = array_merge( $fields,
                    wpcf_admin_fields_get_fields_by_group( $post->ID,
                    'slug', false, false, false,
                    TYPES_USER_META_FIELD_GROUP_CPT_NAME, 'wpcf-usermeta',
                    $use_cache ) );
            }
        } else {
            // Get fields
            $fields = wpcf_admin_fields_get_fields( false, false, false,
                'wpcf-usermeta' );
        }
        if ( !empty( $fields ) ) {

            // Add checksums before WPML
            foreach ( $fields as $field_id => $field ) {
                // TODO WPML and others should use hook
                $fields[$field_id] = apply_filters( 'wpcf_export_field',
                    $fields[$field_id] );
                $fields[$field_id]['__types_id'] = $field_id;
                $fields[$field_id]['__types_title'] = $field['name'];
                $fields[$field_id]['checksum'] = $fields[$field_id]['hash'] = $wpcf->export->generate_checksum(
                    'field', $field_id
                );
            }

            // WPML
            // todo remove WPML dependency, see https://onthegosystems.myjetbrains.com/youtrack/issue/types-749#comment=102-105900
            global $iclTranslationManagement;
            if ( !empty( $iclTranslationManagement ) ) {
                foreach ( $fields as $field_id => $field ) {
                    // TODO Check this for all fields
                    if ( isset( $iclTranslationManagement->settings['custom_fields_translation'][wpcf_types_get_meta_prefix( $field ) . $field_id] ) ) {
                        $fields[$field_id]['wpml_action'] = $iclTranslationManagement->settings['custom_fields_translation'][wpcf_types_get_meta_prefix( $field ) . $field_id];
                    }
                }
            }

            $data['user_fields'] = $fields;
            $data['user_fields']['__key'] = 'field';
        }
    }


	// Export term field groups and term field definitions.
	if( in_array( $_type, array( 'term_groups', 'all' ) ) ) {
		$ie_controller = Types_Import_Export::get_instance();

		$data['term_groups'] = $ie_controller->export_field_groups_for_domain( Toolset_Field_Utils::DOMAIN_TERMS );
		$data['term_fields'] = $ie_controller->export_field_definitions_for_domain( Toolset_Field_Utils::DOMAIN_TERMS );
	}


    if ( 'groups' === $_type || 'all' === $_type ) {
        // Get groups
        if ( empty( $items ) ) {
            $groups = get_posts( 'post_type=wp-types-group&post_status=null&numberposts=-1' );
        } else {
            /*
             *
             * This fails
             * $items are in form of:
             * 0 => array('id' => 'pt', ...)
             */
            foreach ( $items as $k => $item ) {
                if ( isset( $item['id'] ) ) {
                    $items[$k] = intval( wpcf_modman_get_submitted_id( 'groups',
                        $item['id'] ) );
                }
            }
            $args = array(
                'post__in' => $items,
                'post_type' => TYPES_CUSTOM_FIELD_GROUP_CPT_NAME,
                'post_status' => 'all',
                'posts_per_page' => -1
            );
            $groups = get_posts( $args );
        }

        if ( !empty( $groups ) ) {
        	$rfg_service = new Types_Field_Group_Repeatable_Service();

        	// collect all nested rfgs first.
        	foreach( $groups as $key => $post ) {
        	    $groups = apply_rfgs_by_group( $groups, $post->ID, $rfg_service );
	        }

            $data['groups'] = array('__key' => 'group');
            foreach ( $groups as $key => $post ) {
                $post = (array) $post;
                $post_data = array();
                $copy_data = array('ID', 'post_content', 'post_title',
                    'post_excerpt', 'post_type', 'post_status');
                foreach ( $copy_data as $copy ) {
                    if ( isset( $post[$copy] ) ) {
                        $post_data[$copy] = $post[$copy];
                    }
                }
                $_data = $post_data;
                $meta = get_post_custom( $post['ID'] );
                if ( !empty( $meta ) ) {
                    $_meta = array();
                    foreach ( $meta as $meta_key => $meta_value ) {
                        if ( in_array( $meta_key,
                            array(
                                '_wp_types_group_terms',
                                '_wp_types_group_post_types',
                                '_wp_types_group_fields',
                                '_wp_types_group_templates',
                                '_wpcf_conditional_display',
                                '_wp_types_group_filters_association',
                                '_wp_types_group_admin_styles',
	                            '_types_repeatable_field_group_post_type',
								Toolset_Field_Group::POSTMETA_GROUP_PURPOSE,
                            )
                        )
                        ) {
                            $_meta[$meta_key] = $meta_value[0];
                            $_meta[$meta_key] = maybe_unserialize($_meta[$meta_key]);
                        }

	                    // for fields list: rename rfg ids to slug
	                    if( $meta_key == '_wp_types_group_fields' ) {
		                    $_meta[$meta_key] = $rfg_service->on_export_fields_string( $_meta[$meta_key] );
                        }
                    }
                    if ( !empty( $_meta ) ) {
                        $_data['meta'] = $_meta;
                    }
                }
                $_data['checksum'] = $_data['hash'] = $wpcf->export->generate_checksum( 'group',
                    $post['ID'] );
                $_data['__types_id'] = $post['post_name'];
                $_data['__types_title'] = $post['post_title'];
                $data['groups']['group-' . $post['ID']] = $_data;
            }
        }

        if ( !empty( $items ) ) {
            // Get fields by group
            // TODO Document why we use by_group
            $fields = array();
            foreach ( $groups as $key => $post ) {
                $fields = array_merge( $fields,
                    wpcf_admin_fields_get_fields_by_group( $post->ID,
                    'slug', false, false, false, TYPES_CUSTOM_FIELD_GROUP_CPT_NAME,
                    'wpcf-fields', $use_cache ) );
            }
        } else {
            // Get fields
            $fields = wpcf_admin_fields_get_fields();
        }
        if ( !empty( $fields ) ) {

            // Add checksums before WPML
            foreach ( $fields as $field_id => $field ) {
                // TODO WPML and others should use hook
                $fields[$field_id] = apply_filters( 'wpcf_export_field',
                    $fields[$field_id] );
                // RFG.
                if ( $field_id === $fields[$field_id] ) {
                    $post_id = str_replace( Types_Field_Group_Repeatable::PREFIX, '', $field_id );
                    $post = get_post( $post_id );
                    $fields[$field_id] = Types_Field_Group_Repeatable::PREFIX . $post->post_name;
                } else {
                    $fields[$field_id]['__types_id'] = $field_id;
                    $fields[$field_id]['__types_title'] = $field['name'];
                    $fields[$field_id]['checksum'] = $fields[$field_id]['hash'] = $wpcf->export->generate_checksum(
                        'field', $field_id
                    );
                }
            }

            // WPML
	        // todo remove WPML dependency, see https://onthegosystems.myjetbrains.com/youtrack/issue/types-749#comment=102-105900
            global $iclTranslationManagement;
            if ( !empty( $iclTranslationManagement ) ) {
                foreach ( $fields as $field_id => $field ) {
                    // TODO Check this for all fields
                    if ( isset( $iclTranslationManagement->settings['custom_fields_translation'][wpcf_types_get_meta_prefix( $field ) . $field_id] ) ) {
                        $fields[$field_id]['wpml_action'] = $iclTranslationManagement->settings['custom_fields_translation'][wpcf_types_get_meta_prefix( $field ) . $field_id];
                    }
                }
            }

            $data['fields'] = $fields;
            $data['fields']['__key'] = 'field';
        }
    }

    // Get custom types
    if ( 'types' == $_type || 'all' == $_type ) {
	    $post_type_option = new Types_Utils_Post_Type_Option();
        $custom_types = $post_type_option->get_post_types();
        // Get custom types
        // TODO Document $items
        if ( !empty( $items ) ) {
            /*
             * This fails
             * $items are in form of:
             * 0 => array('id' => 'pt', ...)
             */
            $_items = array();
            foreach ( $items as $k => $item ) {
                if ( is_array( $item ) && isset( $item['id'] ) ) {
                    $_items[$item['id']] = true;
                } else {
                    $_items[$item] = true;
                }
            }
            $custom_types = array_intersect_key( $custom_types, $_items );
        }
        // Get custom types
        if ( !empty( $custom_types ) ) {
            foreach ( $custom_types as $key => $type ) {
                if( isset( $type['custom-field-group'] )
                    && is_array( $type['custom-field-group'] )
                    && !empty( $type['custom-field-group'] ) ) {

                    foreach( $type['custom-field-group'] as $custom_field_group_id => $senseless_as_it_is_always_one ) {
                        $custom_field_group = get_post( $custom_field_group_id );

                        // unset custom field USING ID AS KEY AND "1" AS VALUE from custom post type
                        unset( $custom_types[$key]['custom-field-group'][$custom_field_group_id] );

                        // continue with next if this custom field group no longer exists
                        if( !is_object( $custom_field_group ) )
                            continue;

                        // set custom field, generating an unique key (but without a particular meaning) AND ID AS VALUE to custom post type
                        $custom_types[ $key ]['custom-field-group'][ 'group_' . $custom_field_group_id ] = $custom_field_group_id;
                    }
                }

                $custom_types[$key]['id'] = $key;
                $custom_types[$key] = apply_filters( 'wpcf_export_custom_post_type',
                    $custom_types[$key] );

                // fix RFGs created in beta
	            // for these we stored supports by not using arrays keys: array('post_title', 'author'),
	            // which results in an xml error.
                if( isset( $custom_types[$key]['is_repeating_field_group'] )
                    && $custom_types[$key]['is_repeating_field_group']
                    && isset( $custom_types[$key]['supports'] )
                ) {
                	$supports_fixed = array();
                	foreach( $custom_types[$key]['supports'] as $supports_key => $supports_value ) {
                		if( is_int( $supports_key ) && is_string( $supports_value ) ) {
			                $supports_fixed[$supports_value] = 1;
		                } else {
			                $supports_fixed[$supports_key] = $supports_value;
		                }
	                }
	                $custom_types[$key]['supports'] = $supports_fixed;
	            }

                $custom_types[$key]['__types_id'] = $key;
                $custom_types[$key]['__types_title'] = $type['labels']['name'];
                $custom_types[$key]['checksum'] = $custom_types[$key]['hash'] = $wpcf->export->generate_checksum(
                    'custom_post_type', $key, $type
                );
            }
            $data['types'] = $custom_types;
            $data['types']['__key'] = 'type';
        }

        if ( !empty( $items ) ) {
            // Get post relationships only for items
            $relationships_all = get_option( 'wpcf_post_relationship', array() );
            $relationships = array();
            foreach ( $relationships_all as $parent => $children ) {
                if ( in_array( $parent, $items ) ) {
                    foreach ( $children as $child => $childdata ) {
                        if ( in_array( $child, $items ) ) {
                            if ( !isset( $relationships[$parent] ) )
                                $relationships[$parent] = array();
                            $relationships[$parent][$child] = $childdata;
                        }
                    }
                }
            }
        } else {
            // Get post relationships
            $relationships = get_option( 'wpcf_post_relationship', array() );
        }
        if ( !empty( $relationships ) ) {
            $data['post_relationships']['data'] = json_encode( $relationships );
        }

    }

    // Get custom tax
    if ( 'taxonomies' == $_type || 'all' == $_type ) {
        if ( !empty( $items ) ) {
            /*
             *
             * This fails
             * $items are in form of:
             * 0 => array('id' => 'pt', ...)
             */
            //            $custom_taxonomies = array_intersect_key( get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES,
            //                            array() ), array_flip( $items ) );
            $_items = array();
            foreach ( $items as $k => $item ) {
                if ( is_array( $item ) && isset( $item['id'] ) ) {
                    $_items[$item['id']] = true;
                } else {
                    $_items[$item] = true;
                }
            }
            $custom_taxonomies = array_intersect_key( get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES,
                array() ), $_items );
        } else {
            // Get custom tax
            $custom_taxonomies = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
        }
        if ( !empty( $custom_taxonomies ) ) {
            foreach ( $custom_taxonomies as $key => $tax ) {
	            $custom_taxonomies[$key]['id'] = $key;
                $custom_taxonomies[$key] = apply_filters( 'wpcf_filter_export_custom_taxonomy', $custom_taxonomies[$key] );
                $custom_taxonomies[$key]['__types_id'] = $key;
                $custom_taxonomies[$key]['__types_title'] = $tax['labels']['name'];
                $custom_taxonomies[$key]['checksum'] = $wpcf->export->generate_checksum(
                    'custom_taxonomy', $key, $tax
                );
            }
            $data['taxonomies'] = $custom_taxonomies;
            $data['taxonomies']['__key'] = 'taxonomy';
        }
    }

	// Export Toolset Common settings.
	//
	//
	if( in_array( $_type, array( Types_Import_Export::XML_KEY_TOOLSET_COMMON_SETTINGS, 'all' ), true ) ) {
		$ie_controller = Types_Import_Export::get_instance();
		$exported_settings = $ie_controller->export_toolset_common_settings();
		if ( null !== $exported_settings ) {
			$data[ Types_Import_Export::XML_KEY_TOOLSET_COMMON_SETTINGS ] = $exported_settings;
		}
	}


	//
	//
	// Done collecting data, now produce the requested output.
	if ( $return === 'array' ) {
        return $data;
    }

	if ( $return === 'xml' ) {
		return $xml->array2xml( $data, 'types' );
	}

	if ( $return === 'module_manager' ) {
		$items = array();
		// Re-arrange fields
		if ( !empty( $data['fields'] ) ) {
			foreach ( $data['fields'] as $_data ) {
				if ( isset( $_data['__types_id'], $_data['checksum'] ) && is_array( $_data ) ) {
					$_item = array();
					$_item['hash'] = $_item['checksum'] = $_data['checksum'];
					$_item['id'] = $_data['__types_id'];
					$_item['title'] = $_data['__types_title'];
					$items['__fields'][ $_data['__types_id'] ] = $_item;
				}
			}
		}
		// Add checksums to items
		foreach ( $data as $_t => $type ) {
			foreach ( $type as $_data ) {
				// Skip fields
				if ( $_t === 'fields' ) {
					continue;
				}
				if ( is_array( $_data ) && isset( $_data['__types_id'] )
					&& isset( $_data['checksum'] ) ) {
						$_item = array();
						$_item['hash'] = $_item['checksum'] = $_data['checksum'];
						$_item['id'] = $_data['__types_id'];
						$_item['title'] = $_data['__types_title'];
						$items[$_data['__types_id']] = $_item;
					}
			}
		}
		return array(
			'xml' => $xml->array2xml( $data, 'types' ),
			'items' => $items,
		);
	}

	// Offer for download
    $data = $xml->array2xml( $data, 'types' );

    $sitename = sanitize_title( get_bloginfo( 'name' ) );
    if ( empty( $sitename ) ) {
        $sitename = 'wp';
    }
    $sitename .= '.';
    $filename = $sitename . 'types.' . date( 'Y-m-d' ) . '.xml';
    $code = "<?php\r\n";
    $code .= '$timestamp = ' . time() . ';' . "\r\n";
    $code .= "\r\n?".">";

    if ( class_exists( 'ZipArchive' ) ) {
        $zipname = $sitename . 'types.' . date( 'Y-m-d' ) . '.zip';
        $temp_dir = wpcf_get_temporary_directory();
        if ( empty( $temp_dir ) ) {
            die(__('There is a problem with temporary directory.', 'wpcf'));
        }
        $file = tempnam( $temp_dir, "zip" );
        $zip = new ZipArchive();
        $zip->open( $file, ZipArchive::OVERWRITE );

        // if sys_get_temp_dir fail in case of open_basedir restriction,
		// try use wp_upload_dir instead. if this fail too, send pure
        // xml file to user
        if ( empty( $zip->filename ) ) {
            $temp_dir = wp_upload_dir();
            $temp_dir = $temp_dir['basedir'];
            $file = tempnam( $temp_dir, "zip" );
            $zip = new ZipArchive();
            $zip->open( $file, ZipArchive::OVERWRITE );
        }

        // send a zip file
        if ( !empty($zip->filename ) ) {
            $zip->addFromString( 'settings.xml', $data );
            $zip->addFromString( 'settings.php', $code );
            $zip->close();
            $data = file_get_contents( $file );
            header( 'Content-Description: File Transfer' );
            header( 'Content-Disposition: attachment; filename=' . $zipname );
            header( 'Content-Type: application/zip' );
            header( 'Content-length: ' . strlen( $data ) . "\n\n" );
            header( 'Content-Transfer-Encoding: binary' );
            echo $data;
            unlink( $file );
            die();
        }
    }

    // download the xml if fail downloading zip
    header( 'Content-Description: File Transfer' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Content-Type: application/xml' );
    header( 'Content-length: ' . strlen( $data ) . "\n\n" );
    echo $data;
    die();
}

/**
 * This function is needed to get nested rfgs
 * The id of each rfg will be added to $groups
 *
 * @param array $groups
 * @param int $group_post_id
 * @param Types_Field_Group_Repeatable_Service $rfg_service
 *
 * @return array
 */
function apply_rfgs_by_group( $groups, $group_post_id, $rfg_service ) {
	$meta_fields = get_post_meta( $group_post_id, '_wp_types_group_fields', true );

	if( $rfgs = $rfg_service->get_rfgs_by_fields_string( $meta_fields ) ) {
		foreach( $rfgs as $rfg ) {
			$groups[] = $rfg->get_wp_post();
			$groups = apply_rfgs_by_group( $groups, $rfg->get_id(), $rfg_service );
		}
	}

	return $groups;
}

/**
 * Custom Import function for Module Manager.
 *
 * Import selected items given by xmlstring.
 *
 * @global object $wpdb
 * @global type $iclTranslationManagement
 * @param type $data
 * @param type $_type
 * @return \WP_Error|boolean
 */
function wpcf_admin_import_data_from_xmlstring( $data = '', $_type = 'types',
        $context = 'types' ) {

    global $wpdb, $wpcf;

    /*
     *
     * TODO Types 1.3
     * Merge with wpcf_admin_import_data()
     */

    $result = array(
        'updated' => 0,
        'new' => 0,
        'failed' => 0,
        'errors' => array(),
    );

    libxml_use_internal_errors( true );
    $data = simplexml_load_string( $data );
    if ( !$data ) {
        echo '<div class="message error"><p>' . __( 'Error parsing XML', 'wpcf' ) . '</p></div>';
        foreach ( libxml_get_errors() as $error ) {
            return new WP_Error( 'error_parsing_xml', __( 'Error parsing XML', 'wpcf' ) . ' ' . $error->message );
        }
        libxml_clear_errors();
        return false;
    }
    $errors = array();
    $imported = false;
    // Process groups

    $groups_with_rfgs = array();
    $rfgs = array();

    if ( !empty( $data->groups ) && 'groups' == $_type ) {
        $imported = true;

        $groups = array();

        // Set Groups insert data from XML
        foreach ( $data->groups->group as $group ) {
            $group = (array) $group;
            // TODO 1.2.1 Remove
//            $_id = wpcf_modman_set_submitted_id( _GROUPS_MODULE_MANAGER_KEY_,
//                    $group['ID'] );
            $_id = $group['__types_id'];

            // If Types check if exists in $_POST
            if ( $context == 'types' || $context == 'modman' ) {
                if ( !isset( $_POST['items']['groups'][$_id] ) ) {
                    continue;
                }
            }

            $group = wpcf_admin_import_export_simplexml2array( $group );
            $group['add'] = true;
            $group['update'] = false;

            $groups[$_id] = $group;
        }

        // Insert groups
        foreach ( $groups as $group ) {
            $post = array(
                'post_status' => $group['post_status'],
                'post_type' => TYPES_CUSTOM_FIELD_GROUP_CPT_NAME,
                'post_title' => $group['post_title'],
                'post_content' => !empty( $group['post_content'] ) ? $group['post_content'] : '',
				'post_name' => $group['__types_id']
            );
            if ( (isset( $group['add'] ) && $group['add'] ) ) {
                $post_to_update = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s",
                        $group['post_title'],
                        TYPES_CUSTOM_FIELD_GROUP_CPT_NAME
                    )
                );
                // Update (may be forced by bulk action)
                if ( $group['update'] || (!empty( $post_to_update )) ) {
                    if ( !empty( $post_to_update ) ) {
                        $post['ID'] = $post_to_update;

                        /*
                         *
                         * Compare checksum to see if updated
                         */
                        $_checksum = $wpcf->import->checksum( 'group',
                                $post_to_update, $group['checksum'] );

                        $group_wp_id = wp_update_post( $post );
                        if ( !$group_wp_id ) {
                            $errors[] = new WP_Error( 'group_update_failed', sprintf( __( 'Group "%s" update failed', 'wpcf' ),
                                                    $group['post_title'] ) );
                            $result['errors'][] = sprintf( __( 'Group %s update failed', 'wpcf' ), $group['post_title'] );
                            $result['failed'] += 1;
                        } else {
                            if ( !$_checksum ) {
                                $result['updated'] += 1;
                            } else {

                            }
                        }
                    } else {
                        $errors[] = new WP_Error( 'group_update_failed', sprintf( __( 'Group "%s" update failed', 'wpcf' ),
                                                $group['post_title'] ) );
                    }
                } else { // Insert
                    $group_wp_id = wp_insert_post( $post, true );
                    if ( is_wp_error( $group_wp_id ) ) {
                        $errors[] = new WP_Error( 'group_insert_failed', sprintf( __( 'Group "%s" insert failed', 'wpcf' ),
                                                $group['post_title'] ) );
                        $result['errors'][] = sprintf( __( 'Group %s insert failed', 'wpcf' ), $group['post_title'] );
                        $result['failed'] += 1;
                    } else {
                        $result['new'] += 1;
                    }
                }
                // Update meta
				// Collecting all field groups IDs to update assignments when the import is finished.
                if ( !empty( $group['meta'] ) ) {
                    foreach ( $group['meta'] as $meta_key => $meta_value ) {
	                    if ( ! is_array( $meta_value ) && preg_match_all( '/(' . Types_Field_Group_Repeatable::PREFIX . '[a-z0-9_-]+)/', $meta_value, $m ) ) {
		                    if ( isset( $m[1] ) ) {
			                    foreach( $m[1] as $group_name ) {
				                    if ( ! isset( $groups_with_rfgs[ $group_name ] ) ) {
					                    $groups_with_rfgs[ $group_name ] = array();
				                    }
				                    $groups_with_rfgs[ $group_name ][] = $group_wp_id;
			                    }
		                    }
	                    }
                        update_post_meta( $group_wp_id, $meta_key,
                                maybe_unserialize( $meta_value ) );
                    }
                }
                $group_check[] = $group_wp_id;
                if ( !empty( $post_to_update ) ) {
                    $group_check[] = $post_to_update;
                }
            }
        }

        // Process fields
        if ( !empty( $data->fields ) ) {
            $fields_existing = wpcf_admin_fields_get_fields();
            $fields = array();
            $fields_check = array();
            // Set insert data from XML
            foreach ( $data->fields->field as $field ) {
                $field = wpcf_admin_import_export_simplexml2array( $field );
                if ( isset( $field['id'] ) ) {
                    $fields[$field['id']] = $field;
                } else {
                    $rfgs[] = $field[0];
                }
            }
            // Insert fields
            foreach ( $fields as $field_id => $field ) {

                // If Types check if exists in $_POST
                // TODO Regular import do not have structure like this
                if ( $context == 'types' || $context == 'modman' ) {
                    if ( !isset( $_POST['items']['groups']['__fields__' . $field['slug']] ) ) {
                        continue;
                    }
                }

                if ( (isset( $field['add'] ) && !$field['add']) && !$overwrite_fields ) {
                    continue;
                }
                if ( empty( $field['id'] ) || empty( $field['name'] ) || empty( $field['slug'] ) ) {
                    continue;
                }

                $_new_field = !isset( $fields_existing[$field_id] );

                if ( $_new_field ) {
                    $result['new'] += 1;
                } else {
                    $_checksum = $wpcf->import->checksum( 'field',
                            $fields_existing[$field_id]['slug'],
                            $field['checksum'] );
                    if ( !$_checksum ) {
                        $result['updated'] += 1;
                    }
                }

                $field_data = array();
                $field_data['description'] = isset( $field['description'] ) ? $field['description'] : '';
                $field_data['data'] = (isset( $field['data'] ) && is_array( $field['data'] )) ? $field['data'] : array();

                foreach( array( 'id', 'name', 'type', 'slug', 'meta_key', 'meta_type' ) as $key ) {
                    if ( array_key_exists( $key, $field ) ) {
                        $field_data[$key] = $field[$key];
                    }
                }

                $fields_existing[$field_id] = $field_data;
                $fields_check[] = $field_id;

                // WPML
                global $iclTranslationManagement;
                if ( !empty( $iclTranslationManagement ) && isset( $field['wpml_action'] ) ) {
                    $iclTranslationManagement->settings['custom_fields_translation'][wpcf_types_get_meta_prefix( $field ) . $field_id] = $field['wpml_action'];
                    $iclTranslationManagement->save_settings();
                }
            }

            // RFGs.
	        $field_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
	        $all_rfgs = $field_group_factory->query_groups(
		        array(
			        'purpose' => '*',
			        'post_status' => 'hidden'
		        )
	        );

	        $all_rfgs_slug_id = array();

	        foreach( $all_rfgs as $rfg ) {
				$all_rfgs_slug_id[$rfg->get_slug()] = $rfg->get_id();
	        }

	        foreach ( $rfgs as $rfg ) {
                $relationship_slug = str_replace( Types_Field_Group_Repeatable::PREFIX, '', $rfg );

                if( ! isset( $all_rfgs_slug_id[ $relationship_slug ] ) ) {
                	// rfg could not be found
                	continue;
                }

                $post_id = $all_rfgs_slug_id[ $relationship_slug ];
	            if ( isset( $groups_with_rfgs[ $rfg ] ) ) {
                    foreach ( $groups_with_rfgs[ $rfg ] as $group_id ) {
                        $fields_meta = get_post_meta( $group_id, Types_Field_Group_Service::OPTION_FIELDS, true );
                        $fields_meta = str_replace( $rfg, Types_Field_Group_Repeatable::PREFIX . $post_id, $fields_meta );
                        update_post_meta( $group_id, Types_Field_Group_Service::OPTION_FIELDS, $fields_meta );
                    }
                }
            }

            update_option( 'wpcf-fields', $fields_existing );
        }
    }


    // Process types

    if ( !empty( $data->types ) && 'types' == $_type ) {
        $imported = true;

	    $post_type_option = new Types_Utils_Post_Type_Option();
        $types_existing = $post_type_option->get_post_types();
        $types = array();
        $types_check = array();
        // Set insert data from XML
        foreach ( $data->types->type as $type ) {
            $type = (array) $type;
            $type = wpcf_admin_import_export_simplexml2array( $type );
            $_id = strval( $type['__types_id'] );

            // If Types check if exists in $_POST
            if ( $context == 'types' || $context == 'modman' ) {
                if ( !isset( $_POST['items']['types'][$_id] ) ) {
                    continue;
                }
            }

            $types[$_id] = $type;
        }
        // Insert types
        foreach ( $types as $type_id => $type ) {
            if ( (isset( $type['add'] ) && !$type['add'] ) ) {
                continue;
            }

            if ( isset( $types_existing[$type_id] ) ) {
                /*
                 *
                 * Compare checksum to see if updated
                 */
                $_checksum = $wpcf->import->checksum( 'custom_post_type',
                        $type_id, $type['checksum'] );

                if ( !$_checksum ) {
                    $result['updated'] += 1;
                }
            } else {
                $result['new'] += 1;
            }

            /*
             * Set type
             */
            unset( $type['add'], $type['update'], $type['checksum'] );
            $types_existing[$type_id] = $type;
            $types_check[] = $type_id;
        }
        update_option( WPCF_OPTION_NAME_CUSTOM_TYPES, $types_existing );

        // Add relationships
        /** EMERSON: Restore Types relationships when importing modules */
        if ( !empty( $data->post_relationships )) {
        	$relationship_existing = get_option( 'wpcf_post_relationship', array() );
        	/**
        	 * be sure, $relationship_existing is a array!
        	*/
        	if ( !is_array( $relationship_existing ) ) {
        		$relationship_existing = array();
        	}
        	$relationship = json_decode( $data->post_relationships->data, true );
        	if ( is_array( $relationship ) ) {
        		$relationship = array_merge( $relationship_existing, $relationship );
        		update_option( 'wpcf_post_relationship', $relationship );
        	}
        }
    }

    // Process taxonomies

    if ( !empty( $data->taxonomies ) && 'taxonomies' == $_type ) {
        $imported = true;

        $taxonomies_existing = get_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array() );
        $taxonomies = array();
        $taxonomies_check = array();
        // Set insert data from XML
        foreach ( $data->taxonomies->taxonomy as $taxonomy ) {
            // TODO 1.2.1 Remove
//            $_id = wpcf_modman_get_submitted_id( _TAX_MODULE_MANAGER_KEY_,
//                    $taxonomy['__types_id'] );
            $_id = strval( $taxonomy->__types_id );

            // If Types check if exists in $_POST
            if ( $context == 'types' || $context == 'modman' ) {
                if ( !isset( $_POST['items']['taxonomies'][$_id] ) ) {
                    continue;
                }
            }

            $taxonomy = wpcf_admin_import_export_simplexml2array( $taxonomy );
            $taxonomy = apply_filters( 'wpcf_filter_import_custom_taxonomy', $taxonomy );
            $taxonomies[$_id] = $taxonomy;
        }
        // Insert taxonomies
        foreach ( $taxonomies as $taxonomy_id => $taxonomy ) {
            if ( (isset( $taxonomy['add'] ) && !$taxonomy['add']) && !$overwrite_tax ) {
                continue;
            }

            if ( isset( $taxonomies_existing[$taxonomy_id] ) ) {
                /*
                 *
                 * Compare checksum to see if updated
                 */
                $_checksum = $wpcf->import->checksum( 'custom_taxonomy',
                        $taxonomy_id, $taxonomy['checksum'] );
                if ( !$_checksum ) {
                    $result['updated'] += 1;
                }
            } else {
                $result['new'] += 1;
            }

            // Set tax
            unset( $taxonomy['add'], $taxonomy['update'], $taxonomy['checksum'] );
            $taxonomies_existing[$taxonomy_id] = $taxonomy;
            $taxonomies_check[] = $taxonomy_id;
        }
        update_option( WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, $taxonomies_existing );
    }

	// Add m2m relationships
	if( ! empty( $data->m2m_relationships ) && $_type == 'm2m_relationships' ) {
		do_action( 'toolset_do_m2m_full_init' );
		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		// all required keys - trust is good, control is better
		$dependency_keys = array( 'slug', 'parent_types', 'child_types',
			'intermediary_type', 'display_name_plural', 'display_name_singular',
			'cardinality_parent_max', 'cardinality_child_max', 'needs_legacy_support' );

		foreach ( $data->m2m_relationships as $relationships ) {
			$relationships = (array) $relationships;
			$intermediary_post_type = null;

			foreach ( $relationships as $relationship ) {
				$relationship = (array) $relationship;

				try {
					// check required values are available
					foreach( $dependency_keys as $key ) {
						if( ! isset( $relationship[$key] ) ) {
							throw new Exception( __( 'Incompatible module', 'wpcf' ) );
						}
					}

					// get parent type (throws Exception if not available)
					foreach( $relationship['parent_types'] as $post_type_slug => $isset ) {
						// currently we only support one type, so there is only one item to loop
						$parent_type = Toolset_Relationship_Element_Type::build_for_post_type(
							sanitize_title( $post_type_slug ) );
					}
					// for the case of an empty $relationship['parent_types'] array
					if( ! isset( $parent_type ) ) {
						throw new Exception( __( 'Parent Post Type missing.', 'wpcf' ) );
					}

					// get child type (throws Exception if not available)
					foreach( $relationship['child_types'] as $post_type_slug => $isset ) {
						// currently we only support one type, so there is only one item to loop
						$child_type = Toolset_Relationship_Element_Type::build_for_post_type(
							sanitize_title( $post_type_slug ) );
					}
					// for the case of an empty $relationship['child_types'] array
					if( ! isset( $child_type ) ) {
						throw new Exception( __( 'Child Post Type missing.', 'wpcf' ) );
					}

					// check for intermediary type (m2m)
					if ( ! empty( $relationship['intermediary_type'] ) ) {
						/** @var $intermediary_post_type Toolset_Post_Type_From_Types */
						if( ! $intermediary_post_type = $post_type_repository->get( $relationship['intermediary_type'] ) ) {
							throw new Exception( __( 'Intermediary Post Type missing.', 'wpcf' ) );
						};
					}


					/** @var Toolset_Relationship_Definition $definition */
					$definition = $relationship_repository->create_definition( $relationship['slug'], $parent_type, $child_type, false );
					$definition->set_display_name( $relationship['display_name_plural'] );
					$definition->set_display_name_singular( $relationship['display_name_singular'] );

					$cardinality = new Toolset_Relationship_Cardinality(
						$relationship['cardinality_parent_max'],
						$relationship['cardinality_child_max']
					);
					$definition->set_cardinality( $cardinality );
					$definition->is_distinct( true );
					$definition->set_origin( $relationship['origin'] );
					$legacy_support = $relationship['needs_legacy_support'] ? true : false;
					$definition->set_legacy_support_requirement( $legacy_support );
					if ( isset( $intermediary_post_type ) ) {
						$definition->get_driver()->set_intermediary_post_type(
							$intermediary_post_type, $intermediary_post_type->has_show_in_menu() );
					}

					$relationship_repository->persist_definition( $definition );

					$result['new']++;

				} catch( Exception $e ) {
					$result['failed']++;
				}
			}
		}
	}

    if ( $imported ) {
        // WPML bulk registration
        // TODO WPML move
        wpcf_admin_bulk_string_translation();

        // Flush rewrite rules
        wpcf_init_custom_types_taxonomies();
        flush_rewrite_rules();
    }

    return $result;
}

/**
 * Checks hash.
 *
 * @param type $items
 */
function wpcf_modman_items_check_custom_post_types( $items ) {
    global $wpcf;

    foreach ( $items as $k => $item ) {
        $item['exists'] = $wpcf->import->item_exists( 'custom_post_type',
                $item['id'] );
        if ( $item['exists'] && isset( $item['hash'] ) ) {
            $item['is_different'] = $wpcf->import->checksum( 'custom_post_type',
                            $item['id'], $item['hash'] ) ? false : true;
        }
        $items[$k] = $item;
    }

    return $items;
}

/**
 * Checks hash.
 *
 * @param type $items
 */
function wpcf_modman_items_check_groups( $items ) {

    global $wpcf;

    $_items = array();
    $_fields = array();

    // Process fields if any
    if ( !empty( $items['__fields'] ) ) {
        foreach ( $items['__fields'] as $k => $item ) {
            $_item = array();
            $_item['id'] = '__fields__' . $item['id'] . '';
            $_item['title'] = sprintf( __( 'Field: %s', 'wpcf' ), $item['title'] );
            $_item['exists'] = $wpcf->import->item_exists( 'field', $item['id'] );
            if ( $_item['exists'] && isset( $item['hash'] ) ) {
                $_item['is_different'] = $wpcf->import->checksum( 'field',
                                $item['id'], $item['hash'] ) ? false : true;
            }
            $_fields[] = $_item;
        }
        unset( $items['__fields'] );
    }

    foreach ( $items as $k => $item ) {
        $_item = array();
        $_item['id'] = $item['id'];
        $_item['title'] = $item['title'];
        $_item['exists'] = $wpcf->import->item_exists( 'group', $item['id'] );
        if ( $_item['exists'] && isset( $item['hash'] ) ) {
            $_item['is_different'] = $wpcf->import->checksum( 'group',
                            $item['id'], $item['hash'] ) ? false : true;
        }
        $_items[] = $_item;
    }

    return array_merge( $_items, $_fields );
}

/**
 * Checks hash.
 *
 * @param type $items
 */
function wpcf_modman_items_check_taxonomies( $items ) {

    global $wpcf;

    foreach ( $items as $k => $item ) {
        $item['exists'] = $wpcf->import->item_exists( 'custom_taxonomy',
                $item['id'] );
        if ( $item['exists'] && isset( $item['hash'] ) ) {
            $item['is_different'] = $wpcf->import->checksum( 'custom_taxonomy',
                            $item['id'], $item['hash'] ) ? false : true;
        }
        $items[$k] = $item;
    }

    return $items;
}

/**
 * Check if relationships already exists
 *
 * @param $relationships
 *
 * @return mixed
 *
 * @since 3.1
 */
function wpcf_modman_items_check_relationships( $relationships ) {
	if( ! is_array( $relationships ) ) {
		// invalid input
		return $relationships;
	}

	global $wpcf;

	foreach ( $relationships as $slug => $relationship ) {
		if( ! is_array( $relationship ) || ! isset( $relationship['id'] ) ) {
			// invalid entry
			continue;
		}

		// check if the relationship already exists
		$relationship['exists'] = $wpcf->import->item_exists( 'relationship', $relationship['id'] );

		// if relationship exists, proof if it's the same as in the import file
		if ( $relationship['exists'] && isset( $relationship['hash'] ) ) {
			$relationship['is_different'] =
				$wpcf->import->checksum( 'relationship', $relationship['id'], $relationship['hash'] )
					? false
					: true;
		}

		// store updated relationship data
		$relationships[$slug] = $relationship;
	}

	// return updated data array
	return $relationships;
}


/**
 * Extracts ID.
 *
 * @param type $item
 * @return type
 */
function wpcf_modman_get_submitted_id( $set, $item ) {
    return str_replace( '12' . $set . '21', '', $item );
}

/**
 * Sets ID.
 *
 * @param type $id
 * @return type
 */
function wpcf_modman_set_submitted_id( $set, $id ) {
    return '12' . $set . '21' . $id;
}


add_filter( 'wpcf_filter_export_custom_taxonomy', 'wpcf_fix_exported_taxonomy_assignment_to_cpt' );


/**
 * Filter the data to be exported for custom taxonomies.
 *
 * Ensure the settings of post types associated with the taxonomy is exported correctly, even with support of legacy
 * settings.
 *
 * @param array $taxonomy_data
 * @return array Modified taxonomy data.
 * @since unknown
 */
function wpcf_fix_exported_taxonomy_assignment_to_cpt( $taxonomy_data = array() ) {

	$setting_name_prefix = '__types_cpt_supports_';
	$post_type_support_settings = array();

	// Associated CPTs slugs are stored as XML keys, so they can not start with a number.
    // We force a prefix on all of them on export, and restore them on import.
	$supported_post_types = wpcf_ensarr( wpcf_getarr( $taxonomy_data, 'supports' ) );
	foreach( $supported_post_types as $post_type_slug => $is_supported ) {
		$setting_name = $setting_name_prefix . $post_type_slug;
		$post_type_support_settings[ $setting_name ] = ( $is_supported ? 1 : 0 );
	}

	// Here, we will also process the legacy "object_type" setting, containing supported post type slugs as array items,
	// in the samve way.
	$legacy_supported_post_type_array = wpcf_ensarr( wpcf_getarr( $taxonomy_data, 'object_type' ) );
	foreach( $legacy_supported_post_type_array as $post_type_slug ) {
		$setting_name = $setting_name_prefix . $post_type_slug;
		$post_type_support_settings[ $setting_name ] = 1;
	}

	// Now we need to remove this legacy setting to prevent producing invalid XML.
	unset( $taxonomy_data['object_type'] );

	$taxonomy_data['supports'] = $post_type_support_settings;
	return $taxonomy_data;
}


/**
 * Filters the items of the saved modules
 *
 * In our case we need to remove duplicated post types, the ones that are included in the relationships and the relationships included in the Field Groups.
 *
 * @param array $modules Modules saved.
 * @return array
 */
function wpcf_modman_wpmodules_saved_items( $modules ) {
	if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
		return $modules;
	}
	$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
	foreach ( $modules as $module_name => $module ) {
		if ( isset( $module['m2m_relationships'] ) ) {
			$post_types_in_relationships = array();
			// Getting post types from relationships.
			foreach ( $module['m2m_relationships'] as $relationship_data ) {
				$relationship_slug = str_replace( '12' . _RELATIONSHIPS_MODULE_MANAGER_KEY_ . '21', '', $relationship_data['id'] );

				if( ! $relationship_definition = $relationship_repository->get_definition( $relationship_slug ) ) {
					// no relationship found (happens when the user deletes the relationship after module creation)
					continue;
				};

				$post_types = array_merge(
					$relationship_definition->get_parent_type()->get_types(),
					$relationship_definition->get_child_type()->get_types()
				);

				foreach ( $post_types as $post_type_slug ) {
					$post_types_in_relationships[] = '12' . _TYPES_MODULE_MANAGER_KEY_ . '21' . $post_type_slug;
				}
			}
			// Removing CPT.
			foreach ( $module['types'] as $i => $post_type_data ) {
				if ( in_array( $post_type_data['id'], $post_types_in_relationships ) ) {
					unset( $modules[ $module_name ]['types'][ $i ] );
				}
			}
		}

		if ( isset( $module['groups'] ) && isset( $module['m2m_relationships'] ) ) {
			$relationships_in_groups = array();
			foreach ( $module['groups'] as $group_data ) {
				$group_id = str_replace( '12' . _GROUPS_MODULE_MANAGER_KEY_ . '21', '', $group_data['id'] );
				$relationships_in_groups = array_merge( $relationships_in_groups, wpcf_get_relationships_included_in_field_groups( $group_id ) );
			}
			// Removing CPT.
			foreach ( $module['m2m_relationships'] as $i => $relationship_data ) {
				$relationship_slug = str_replace( '12' . _RELATIONSHIPS_MODULE_MANAGER_KEY_ . '21', '', $relationship_data['id'] );
				if ( in_array( $relationship_slug, $relationships_in_groups ) ) {
					unset( $modules[ $module_name ]['m2m_relationships'][ $i ] );
				}
			}
		}
	}

	return $modules;
}


/**
 * gets a list of relationship slugs belonging to a Field Group: PRF or RFG
 *
 * @param int|string $group Group ID or slug.
 * @return array
 * @since 3.0
 */
function wpcf_get_relationships_included_in_field_groups( $group ) {
	if( ! $group_object = Toolset_Field_Group_Post_Factory::load( $group ) ) {
		// no group = no fields
		return array();
	}

	$service_field_group = new Types_Field_Group_Repeatable_Service();
	$definition_factory = Toolset_Field_Definition_Factory_Post::get_instance();
	$relationship_slugs = array();
	foreach ( $group_object->get_field_slugs() as $field_slug ) {
		$field_definition = $definition_factory->load_field_definition( $field_slug );
		if ( $field_definition ) {
			if ( $field_definition->get_type()->get_slug() === 'post' ) {
				$relationship_slugs[] = $field_slug;
			}
		} else {
			$repeatable_group = $service_field_group->get_object_from_prefixed_string( $field_slug );
			if ( $repeatable_group ) {
				$post_id = $service_field_group->get_id_from_prefixed_string( $field_slug );
				$post = get_post( $post_id );
				$relationship_slugs[] = $post->post_name;
			}
		}
	}
	return $relationship_slugs;
}
