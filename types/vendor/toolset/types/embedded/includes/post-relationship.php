<?php
/*
 * Post relationship code.
 *
 *
 */
add_action( 'wpcf_admin_post_init', 'wpcf_pr_admin_post_init_action', 10, 4 );

add_action( 'save_post', 'wpcf_pr_admin_save_post_hook', 20, 2 ); // Trigger afer main hook

/*
 * Temporary fix for https://core.trac.wordpress.org/ticket/17817
 *
 * WordPress 4.7 fixes this issue and the code below is not needed anymore.
 *
 * Supported by WPML 3.6.0 and above.
 */
$wp_version = get_bloginfo( 'version' );
if ( version_compare( $wp_version, '4.7' ) == - 1 ) {

	global $sitepress;
	$is_wpml_active = (
		defined( 'ICL_SITEPRESS_VERSION' )
		&& ! ICL_PLUGIN_INACTIVE
		&& ! is_null( $sitepress )
		&& class_exists( 'SitePress' )
	);

	$is_wpml_required_version = ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.6.0' ) >= 0 );

	if ( $is_wpml_active && $is_wpml_required_version ) {
		// WPML *guarantees* that the wpml_after_save_post action will be fired for each post.
		remove_action( 'save_post', 'wpcf_pr_admin_save_post_hook', 20 );
		add_action( 'wpml_after_save_post', 'wpcf_pr_admin_save_post_hook', 10, 2 );
	}
}


if ( is_admin() ) {
	add_action( 'wp_ajax_wpcf_relationship_search', 'wpcf_pr_admin_wpcf_relationship_search' );
	// Deprecated since the introduction of select v.4
	add_action( 'wp_ajax_wpcf_relationship_entry', 'wpcf_pr_admin_wpcf_relationship_entry' );
	// Deprecated since the introduction of select v.4
	add_action( 'wp_ajax_wpcf_relationship_delete', 'wpcf_pr_admin_wpcf_relationship_delete' );
	// Deprecated since the introduction of select v.4
	add_action( 'wp_ajax_wpcf_relationship_save', 'wpcf_pr_admin_wpcf_relationship_save' );
	// Used since the introduction of select2 v.4
	add_action( 'wp_ajax_wpcf_relationship_update', 'wpcf_pr_admin_wpcf_relationship_update' );

	add_filter( 'wpcf_pr_belongs_post_numberposts', 'wpcf_pr_belongs_post_numberposts_minimum', PHP_INT_MAX, 1 );
}

/**
 * Init function.
 *
 * Enqueues styles and scripts on post edit page.
 *
 * @param $post_type
 * @param $post
 * @param $groups
 * @param $wpcf_active
 */
function wpcf_pr_admin_post_init_action( $post_type, $post, $groups, $wpcf_active ) {
	if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
		return;
	}
	// See if any data
	$has = wpcf_pr_admin_get_has( $post_type );
	$belongs = wpcf_pr_admin_get_belongs( $post_type );

	/*
	 * Enqueue styles and scripts
	 */
	if ( ! empty( $has ) || ! empty( $belongs ) ) {

		$output = wpcf_pr_admin_post_meta_box_output( $post, array(
			'post_type' => $post_type,
			'has' => $has,
			'belongs' => $belongs,
		) );
		add_meta_box(
			'wpcf-post-relationship',
			__( 'Post Relationship', 'wpcf' ),
			'wpcf_pr_admin_post_meta_box',
			$post_type,
			'normal',
			'default',
			array( 'output' => $output )
		);
		if ( ! empty( $output ) ) {
			wp_register_script(
				'wpcf-post-relationship',
				WPCF_EMBEDDED_RELPATH . '/resources/js/post-relationship.js',
				array( 'jquery', 'toolset_select2' ),
				WPCF_VERSION
			);
			wp_localize_script(
				'wpcf-post-relationship',
				'wpcf_post_relationship_messages',
				array(
					'parent_saving' => __( 'Saving post parent.', 'wpcf' ),
					'parent_saving_success' => __( 'Saved.', 'wpcf' ),
					'parent_per_page' => apply_filters( 'wpcf_pr_belongs_post_numberposts', 10 ),
				)
			);
			wp_enqueue_script( 'wpcf-post-relationship' );

			wp_enqueue_style( 'wpcf-post-relationship',
				WPCF_EMBEDDED_RELPATH . '/resources/css/post-relationship.css',
				array(), WPCF_VERSION );
			if ( ! $wpcf_active ) {
				wpcf_enqueue_scripts();
				wp_enqueue_style( 'wpcf-pr-post',
					WPCF_EMBEDDED_RES_RELPATH . '/css/fields-post.css',
					array(), WPCF_VERSION );

				$asset_manager = Types_Asset_Manager::get_instance();
				$asset_manager->enqueue_scripts(
					array(
						Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION,
						Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,
					)
				);

			}
			wpcf_admin_add_js_settings( 'wpcf_pr_del_warning',
				'\'' . __( 'Are you sure about deleting this post?', 'wpcf' ) . '\'' );
			wpcf_admin_add_js_settings( 'wpcf_pr_pagination_warning',
				'\'' . __( 'If you continue without saving your changes, they might get lost.', 'wpcf' ) . '\'' );
		}
	}
}


/**
 * Determine if a post type can take a part in a post relationship.
 *
 * @param string $post_type_slug
 *
 * @return bool
 * @since 2.3
 */
function wpcf_pr_is_post_type_available_for_relationships( $post_type_slug ) {
	$is_active = ( null != get_post_type_object( $post_type_slug ) );
	$is_excluded_from_relationships = ( 'attachment' == $post_type_slug );

	return ( $is_active && ! $is_excluded_from_relationships );
}


/**
 * Gets post types that belong to current post type.
 *
 * @param string $parent_post_type_slug
 *
 * @return array|false
 * @deprecated Since 2.3. Use toolset_get_related_post_types() instead.
 */
function wpcf_pr_admin_get_has( $parent_post_type_slug ) {
    if( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
		do_action( 'toolset_do_m2m_full_init' );
		$related_post_types = toolset_get_related_post_types( Toolset_Relationship_Role::CHILD, $parent_post_type_slug );
		$results = array();
		foreach( array_keys( $related_post_types ) as $related_post_type ) {
			$results[ $related_post_type ] = array();
		}
		return $results;
	}static $cache = array();
    if ( isset( $cache[$parent_post_type_slug] ) ) {
        return $cache[$parent_post_type_slug];
    }
    $relationships = get_option( 'wpcf_post_relationship', array() );
    if ( empty( $relationships[$parent_post_type_slug] ) ) {
        return false;
    }
    // See if enabled
    foreach ( $relationships[ $parent_post_type_slug ] as $child_post_type_slug => $ignored ) {
        if ( ! wpcf_pr_is_post_type_available_for_relationships( $child_post_type_slug ) ) {
            unset( $relationships[ $parent_post_type_slug ][ $child_post_type_slug ] );
        }
    }
    $cache[$parent_post_type_slug] = !empty( $relationships[$parent_post_type_slug] ) ? $relationships[$parent_post_type_slug] : false;
    return $cache[$parent_post_type_slug];
}

/**
 * Gets post types that current post type belongs to.
 *
 * @param string $post_type
 *
 * @return array|false
 * @deprecated Since 2.3. Use toolset_get_related_post_types() instead.
 */
function wpcf_pr_admin_get_belongs( $post_type ) {
    if( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
	    do_action( 'toolset_do_m2m_full_init' );
        $related_post_types = toolset_get_related_post_types( Toolset_Relationship_Role::PARENT, $post_type );
        $results = array();
        foreach( array_keys( $related_post_types ) as $related_post_type ) {
            $results[ $related_post_type ] = array();
        }
        return $results;
    }static $cache = array();
    if ( isset( $cache[$post_type] ) ) {
        return $cache[$post_type];
    }
    $relationships = get_option( 'wpcf_post_relationship', array() );
    $results = array();
    if ( is_array( $relationships ) ) {
        foreach ( $relationships as $has => $belongs ) {

			if ( ! wpcf_pr_is_post_type_available_for_relationships( $has ) ) {
				continue;
			}

			if ( array_key_exists( $post_type, $belongs ) ) {
				$results[ $has ] = $belongs[ $post_type ];
			}
		}
	}
	$cache[ $post_type ] = ! empty( $results ) ? $results : false;

	return $cache[ $post_type ];
}


/**
 * Gets post types related to current post type.
 *
 * Gets:
 *        children post types (one-to-many / one-to-one)
 *            one-to-one is a one-to-many realationship where user only sets one children
 *        parent post types (one-to-many-from-child) - post type belong to another post type
 *        parent posts of childrem posts (many-to-many)
 *
 * @since m2m
 *
 * @param string $post_type
 * @param string $field If the related post type has not the field, it will be disabled
 *
 * @return array|false The array is grouped in different types
 *                                                [one-to-one]   List of PT with this relationship
 *                                                [one-to-many]  List of PT with this relationship
 *                                                [one-to-many-from-child]  List of PT with this relationship
 *                                                [many-to-many] List of PT with this relationship
 *                                                [post_types]   List of PT having this field
 */
function wpcf_pr_admin_get_related( $post_type, $field ) {
	static $cache = array();
	$cache_id = 'related_' . $post_type . '_' . $field['slug'];
	if ( isset( $cache[ $cache_id ] ) ) {
		return $cache[ $cache_id ];
	}
	do_action( 'toolset_do_m2m_full_init' );
	$query = new Toolset_Relationship_Query(
		array(
			Toolset_Relationship_Query::QUERY_HAS_TYPE => array(
				'domain' => Toolset_Relationship_Element_Type::DOMAIN_POSTS,
				'type' => $post_type,
			),
		)
	);

	/** @var IToolset_Relationship_Definition[] $results */
	$relationships = $query->get_results();

	$results = array();
	if ( is_array( $relationships ) ) {
		// Post types stores the posts types used for checking if it has the current field.
		$results['post_types'] = array();
		$results['one-to-one'] = array();
		$results['one-to-many'] = array();
		$results['one-to-many-from-child'] = array();
		$results['many-to-many'] = array();
		foreach ( $relationships as $relationship ) {
			$cardinality = $relationship->get_cardinality();
			$parent_type = $relationship->get_parent_type();
			$child_type = $relationship->get_child_type();

			// Depends of it the post type is a parent or a child, some checks are needed.
			$is_parent = in_array( $post_type, $parent_type->get_types() );
			$is_child = in_array( $post_type, $child_type->get_types() );
			$relationship_slug = $relationship->get_slug();
			$child_role_name = $relationship->get_role_name( Toolset_Relationship_Role::CHILD );
			$parent_role_name = $relationship->get_role_name( Toolset_Relationship_Role::PARENT );

			// Adds the value to the post type
			$new_value_to_post_type = new StdClass();
			$new_value_to_post_type->post_type = $is_parent
				? $child_type->get_types()
				: $parent_type->get_types();
			$new_value_to_post_type->post_type = $new_value_to_post_type->post_type[0];
			$new_value_to_post_type->value = '@' . $relationship_slug . '.' . (
				$is_parent
					? $child_role_name
					: $parent_role_name
				);

			// One-to-one.
			if ( ( $is_parent || $is_child ) && $cardinality->is_one_to_one() ) {
				$results['one-to-one'][] = $new_value_to_post_type;
			}

			// One-to-many.
			// In this case, is_many_to_many doesn't fit our needs because
			// we make difference between one-to-many and one-to-many-from-child
			if ( ( $is_parent && $cardinality->is_one_to_many() )
				// Checks inverse Relationship. It shouldn't be allow by the wizard.
				|| ( $is_child && $cardinality->is_many_to_one() )
			) {

				$results['one-to-many'][] = $new_value_to_post_type;
			}

			// one-to-many-from-child.
			if ( ( $is_parent && $cardinality->is_many_to_one() )
				// Checks inverse Relationship. It shouldn't be allow by the wizard.
				|| ( $is_child && $cardinality->is_one_to_many() )
			) {
				$results['one-to-many-from-child'][] = $new_value_to_post_type;
			}

			// Many-to-many
			if ( ( $is_parent || $is_child ) && $cardinality->is_many_to_many() ) {
				$results['many-to-many'][] = $new_value_to_post_type;
			}
		}

		// Get which post types has this field
		$definition_factory = Toolset_Field_Definition_Factory_Post::get_factory_by_domain( 'posts' );
		$definitions = $definition_factory->query_definitions( array(
			'filter' => 'all',
			'orderby' => 'name',
			'order' => 'asc',
			'search' => 'artist',
		) );

		$group_factory = Toolset_Field_Group_Post_Factory::get_instance();
		$groups = $group_factory->get_groups_by_post_types();

		// Remove unavailable and gets post types with field.
		foreach ( $results as $relationship_type => $related_post_types ) {
			foreach ( $related_post_types as $i => $related_post_type ) {
				if ( ! wpcf_pr_is_post_type_available_for_relationships( $related_post_type->post_type ) ) {
					unset( $results[ $relationship_type ][ $i ] );
				}
				// Checks if specific post type has current field.
				if ( ! in_array( $related_post_type->post_type, $results['post_types'] ) ) {
					if ( ! empty( $groups[ $related_post_type->post_type ] ) && in_array( $field['slug'], $groups[ $related_post_type->post_type ][0]->get_field_slugs() ) ) {
						$results['post_types'][] = $related_post_type->post_type;
					}
				}
			}
		}
	} // End if().

	$cache[ $cache_id ] = ! empty( $results ) ? $results : false;

	return $cache[ $cache_id ];
}


/**
 * Gets post types working as an intermediate in a relationship.
 *
 *
 * @since m2m
 *
 * @param string $post_type
 * @param string $field If the related post type has not the field, it will be disabled
 *
 * @return array|false The array is grouped in different types
 *                                                [one-to-one]   List of PT with this relationship
 *                                                [one-to-many]  List of PT with this relationship
 *                                                [one-to-many-from-child]  List of PT with this relationship
 *                                                [many-to-many] List of PT with this relationship
 *                                                [post_types]   List of PT having this field
 */
function wpcf_pr_admin_get_intermediate( $post_type, $field ) {
	static $cache = array();
	$cache_id = 'intermediate_' . $post_type . '_' . $field['slug'];
	if ( isset( $cache[ $cache_id ] ) ) {
		return $cache[ $cache_id ];
	}

	do_action( 'toolset_do_m2m_full_init' );
	$query = new Toolset_Relationship_Query(
		array(
			Toolset_Relationship_Query::QUERY_HAS_TYPE => array(
				'domain' => Toolset_Relationship_Element_Type::DOMAIN_POSTS,
				'type' => $post_type,
			),
		)
	);

	/** @var IToolset_Relationship_Definition[] $results */
	$relationships = $query->get_results();


	$results = array();
	if ( is_array( $relationships ) ) {
		foreach ( $relationships as $relationship ) {

			if ( ! $relationship->get_driver()->get_intermediary_post_type() ) {
				continue;
			}

			$parent_type = $relationship->get_parent_type();
			$child_type = $relationship->get_child_type();
			$is_parent = in_array( $post_type, $parent_type->get_types() );

			$relationship_slug = $relationship->get_slug();
			$parents = $is_parent
				? $child_type->get_types()
				: $parent_type->get_types();

			$child_role_name = $relationship->get_role_name( Toolset_Relationship_Role::CHILD );
			$parent_role_name = $relationship->get_role_name( Toolset_Relationship_Role::PARENT );

			if ( is_array( $parents ) ) {
				foreach ( $parents as $parent ) {
					// Get which post types has this field
					$definition_factory = Toolset_Field_Definition_Factory_Post::get_factory_by_domain( 'posts' );
					$definitions = $definition_factory->query_definitions( array(
						'filter' => 'all',
						'orderby' => 'name',
						'order' => 'asc',
						'search' => 'artist',
					) );

					$group_factory = Toolset_Field_Group_Post_Factory::get_instance();
					$groups = $group_factory->get_groups_by_post_types();

					// Checks if specific post type has current field.
					$enabled = false;
					if ( ! empty( $groups[ $parent ] ) && in_array( $field['slug'], $groups[ $parent ][0]->get_field_slugs() ) ) {
						$enabled = true;
					}

					$results[] = array(
						'name' => $relationship->get_display_name(),
						'value' => '@' . $relationship_slug . '.' . (
							$is_parent
								? $child_role_name
								: $parent_role_name
							),
						'id' => $relationship_slug . '-' . $parent,
						'enabled' => $enabled,
					);
				}
			}
		}
	}

	$cache[ $cache_id ] = ! empty( $results ) ? $results : false;

	return $cache[ $cache_id ];
}


/**
 * Meta boxes contents.
 *
 * @param type $post
 * @param type $args
 */
function wpcf_pr_admin_post_meta_box( $post, $args ) {
	if ( ! empty( $args['args']['output'] ) ) {
		echo $args['args']['output'];
	} else {
		$wpcf_pr_admin_belongs = wpcf_pr_admin_get_belongs( $post->post_type );
		if ( empty( $wpcf_pr_admin_belongs ) ) {
			_e( 'You will be able to manage child posts after saving this post.', 'wpcf' );
		} else {
			_e( 'You will be able to add parent posts after saving this post.', 'wpcf' );
		}
	}
}

function wpcf_admin_notice_post_locked_no_parent() {
	if ( ! $post = get_post() ) {
		return;
	}
	$parent_type = wpcf_pr_admin_get_belongs( $post->post_type );
	if ( is_array( $parent_type ) && count( $parent_type ) ) {
		$parent_type = array_shift( array_keys( $parent_type ) );
		$parent_type = get_post_type_object( $parent_type );
	} else {
		return;
	}

	if ( ( $sendback = wp_get_referer() ) && false === strpos( $sendback, 'post.php' ) && false === strpos( $sendback, 'post-new.php' ) ) {
		$sendback_text = __( 'Go back', 'wpcf' );
	} else {
		$sendback = admin_url( 'edit.php' );
		if ( 'post' != $post->post_type ) {
			$sendback = esc_url( add_query_arg( 'post_type', $post->post_type, $sendback ) );
		}
		$sendback_text = get_post_type_object( $post->post_type )->labels->all_items;
	}
	?>
	<div id="post-lock-dialog" class="notification-dialog-wrap">
		<div class="notification-dialog-background"></div>
		<div class="notification-dialog">
			<div class="post-locked-message">
				<p>
					<?php
					if ( 'auto-draft' == $post->post_status ) {
						printf(
							__( 'You will be able to add child posts after saving at least one <b>%s</b>.', 'wpcf' ),
							$parent_type->labels->singular_name
						);
					} else {
						printf(
							__( 'You will be able to edit child posts after saving at least one <b>%s</b>.', 'wpcf' ),
							$parent_type->labels->singular_name
						);
					}
					?>
				</p>
				<p><a class="button button-primary wp-tab-last"
						href="<?php echo $sendback; ?>"><?php echo $sendback_text; ?></a></p>
			</div>
		</div>
	</div>
	</div>
	<?php
}

/**
 * Meta boxes contents output.
 *
 * @param WP_Post $post
 * @param array $args
 *
 * @return string
 */
function wpcf_pr_admin_post_meta_box_output( $post, $args ) {
	if ( empty( $post ) || empty( $post->ID ) ) {
		return array();
	}

	global $wpcf;

	$output = '';
	$relationships = $args;
	$post_id = ! empty( $post->ID ) ? $post->ID : - 1;
	$current_post_type = wpcf_admin_get_edited_post_type( $post );

	/*
	 * Render has form (child form)
	 */
	if ( ! empty( $relationships['has'] ) ) {
		foreach ( $relationships['has'] as $post_type => $data ) {
			if ( isset( $data['fields_setting'] ) && 'only_list' == $data['fields_setting'] ) {
				$output .= $wpcf->relationship->child_list( $post, $post_type, $data );
			} else {
				$output .= $wpcf->relationship->child_meta_form( $post, $post_type, $data );
			}
		}
	}
	/*
	 * Render belongs form (parent form)
	 */
	if ( ! empty( $relationships['belongs'] ) ) {
		$meta = get_post_custom( $post_id );
		$belongs = array( 'belongs' => array(), 'posts' => array() );
		foreach ( $meta as $meta_key => $meta_value ) {
			if ( strpos( $meta_key, '_wpcf_belongs_' ) === 0 ) {
				$temp_post = get_post( $meta_value[0] );
				if ( ! empty( $temp_post ) ) {
					$belongs['posts'][ $temp_post->ID ] = $temp_post;
					$belongs['belongs'][ $temp_post->post_type ] = $temp_post->ID;
				}
			}
		}
		foreach ( $relationships['belongs'] as $post_type => $data ) {
			$parent_post_type_object = get_post_type_object( $post_type );
			$output .= '<div class="belongs">';
			$form = wpcf_pr_admin_post_meta_box_belongs_form( $post, $post_type, $belongs );
			if ( isset( $form[ $post_type ] ) ) {
				$form[ $post_type ]['#before'] =
					'<p>'
					. sprintf(
						__( 'This <em>%s</em> belongs to <em>%s</em>', 'wpcf' ),
						get_post_type_object( $current_post_type )->labels->singular_name,
						$parent_post_type_object->labels->singular_name
					);
				$button_classname = ( $form[ $post_type ]['#default_value'] > 0 ) ? 'button wpcf-pr-parent-edit js-wpcf-pr-parent-edit' : 'button wpcf-pr-parent-edit js-wpcf-pr-parent-edit disabled';
				$button_style = ( $form[ $post_type ]['#default_value'] > 0 ) ? '' : 'display:none';
				$form[ $post_type ]['#after'] =
					'<a'
					. ' href="' . get_edit_post_link( $form[ $post_type ]['#default_value'] ) . '"'
					. ' style="' . $button_style . '"'
					. ' class="' . $button_classname . '"'
					. ' target="_blank"'
					. '>'
					. $parent_post_type_object->labels->edit_item
					. '</a>'
					. '</p>';
			}
			if ( $x = wpcf_form_simple( $form ) ) {
				$output .= $x;
			} else {
				$output .= $parent_post_type_object->labels->not_found;
			}
			$output .= '</div>';
			unset( $parent_post_type_object );
		}
	}

	return $output;
}

/**
 * AJAX delete child item call.
 *
 * @param int $post_id
 *
 * @return string
 */
function wpcf_pr_admin_delete_child_item( $post_id ) {
	wp_delete_post( $post_id, true );

	return __( 'Post deleted', 'wpcf' );
}

/**
 *
 * Belongs form helper to build correct SQL string to prepare.
 *
 * Belongs form helper to build correct SQL string to $wpdb->prepare - replace
 * any item by digital placeholder.
 *
 * @param any $item
 *
 * @return string
 *
 */
function wpcf_pr_admin_post_meta_box_belongs_form_items_helper( $item ) {
	return '%d';
}

/**
 * Belongs form.
 *
 * @param $post
 * @param $type
 * @param $belongs
 *
 * @return array
 */
function wpcf_pr_admin_post_meta_box_belongs_form( $post, $type, $belongs ) {
	global $wpdb;
	$temp_type = get_post_type_object( $type );
	if ( empty( $temp_type ) ) {
		return array();
	}

	$form = array();
	$id = esc_attr( sprintf( 'wpcf_pr_belongs_%d_%s', $post->ID, $type ) );
	$belongs_id = isset( $belongs['belongs'][ $type ] ) ? $belongs['belongs'][ $type ] : 0;

	$options_array = array();

	$values_to_prepare = array();

	$post_status = array( 'publish', 'private' );

	$wpml_join = $wpml_where = "";
	$is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', false, $type );

	if ( $is_translated_post_type ) {
		$wpml_current_language = apply_filters( 'wpml_current_language', '' );
		$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
		$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type = concat( 'post_', %s ) ";
		$values_to_prepare[] = $wpml_current_language;
		$values_to_prepare[] = sanitize_text_field( $type );

		// This is covered by the element_type condition in $wpml_where
		$where_post_type = '';
	} else {
		// No WPML tables, we just query the post type directly.
		$where_post_type = ' AND p.post_type = %s ';
		$values_to_prepare[] = sanitize_text_field( $type );
	}

	$not_in_selected = '';
	if ( $belongs_id ) {
		$not_in_selected = ' AND p.ID != %d';
		$values_to_prepare[] = (int) $belongs_id;
		$options_array[ $belongs_id ] = array(
			'#title' => get_the_title( $belongs_id ),
			'#value' => $belongs_id,
		);
	} else {
		$options_array[''] = array(
			'#title' => '',
			'#value' => '',
		);
	}

	$sql_query = $wpdb->prepare(
		"SELECT p.ID, p.post_title
			FROM {$wpdb->posts} p {$wpml_join}
			WHERE
			    p.post_status IN ('" . implode( "','", $post_status ) . "')
			    {$wpml_where}
			    {$where_post_type}
			    {$not_in_selected}
			ORDER BY p.post_date DESC
			LIMIT 15",
		$values_to_prepare
	);

	$parents_available = $wpdb->get_results( $sql_query );

	foreach ( $parents_available as $parent_option ) {
		$options_array[ $parent_option->ID ] = array(
			'#title' => $parent_option->post_title,
			'#value' => $parent_option->ID,
		);
	}


	$form[ $type ] = array(
		'#type' => 'select',
		'#name' => 'wpcf_pr_belongs[' . $post->ID . '][' . $type . ']',
		'#default_value' => $belongs_id,
		'#id' => $id,
		'#options' => $options_array,
		'#attributes' => array(
			'class' => 'wpcf-pr-belongs',
			'data-loading' => esc_attr__( 'Please Wait, Loading…', 'wpcf' ),
			'data-nounce' => wp_create_nonce( $id ),
			'data-placeholder' => esc_attr( sprintf( __( 'Search for %s', 'wpcf' ), $temp_type->labels->name ) ),
			'data-post-id' => $post->ID,
			'data-post-type' => esc_attr( $type ),
			'autocomplete' => 'off',
		),
	);

	return $form;
}

/**
 * Updates belongs data.
 *
 * @param int $post_id
 * @param array $data $post_type => $post_id
 *
 * @return string
 */
function wpcf_pr_admin_update_belongs( $post_id, $data ) {

	$errors = array();
	$post = get_post( intval( $post_id ) );
	if ( empty( $post->ID ) ) {
		return new WP_Error(
			'wpcf_update_belongs',
			sprintf(
				__( 'Missing child post ID %d', 'wpcf' ),
				intval( $post_id )
			)
		);
	}

	foreach ( $data as $post_type => $post_owner_id ) {
		// Check if relationship exists
		if ( ! wpcf_relationship_is_parent( $post_type, $post->post_type ) ) {
			$errors[] = sprintf(
				__( 'Relationship do not exist %s -> %s', 'wpcf' ),
				strval( $post_type ),
				strval( $post->post_type )
			);
			continue;
		}
		if ( $post_owner_id == '0' ) {
			delete_post_meta( $post_id, "_wpcf_belongs_{$post_type}_id" );
			continue;
		}
		$post_owner = get_post( intval( $post_owner_id ) );
		// Check if owner post exists
		if ( empty( $post_owner->ID ) ) {
			$errors[] = sprintf( __( 'Missing parent post ID %d', 'wpcf' ), intval( $post_owner_id ) );
			continue;
		}
		// Check if owner post type matches required
		if ( $post_owner->post_type != $post_type ) {
			$errors[] = sprintf(
				__( 'Parent post ID %d is not type of %s', 'wpcf' ),
				intval( $post_owner_id ),
				strval( $post_type )
			);
			continue;
		}
		update_post_meta( $post_id, "_wpcf_belongs_{$post_type}_id", $post_owner->ID );
	}

	if ( ! empty( $errors ) ) {
		return new WP_Error( 'wpcf_update_belongs', implode( '; ', $errors ) );
	}

	return __( 'Post updated', 'wpcf' );
}

/**
 * Pagination link.
 *
 * @param type $post
 * @param type $post_type
 * @param type $page
 * @param type $prev
 * @param type $next
 *
 * @return string
 */
function wpcf_pr_admin_has_pagination(
	$post, $post_type, $page, $prev, $next,
	$per_page = 20, $count = 20
) {

	global $wpcf;

	$link = '';
	$add = '';
	if ( isset( $_GET['sort'] ) ) {
		$add .= '&sort=' . sanitize_text_field( $_GET['sort'] );
	}
	if ( isset( $_GET['field'] ) ) {
		$add .= '&field=' . sanitize_text_field( $_GET['field'] );
	}
	if ( isset( $_GET['post_type_sort_parent'] ) ) {
		$add .= '&post_type_sort_parent=' . sanitize_text_field( $_GET['post_type_sort_parent'] );
	}

	/**
	 * default for next
	 */
	$url_params = array(
		'action' => 'wpcf_ajax',
		'wpcf_action' => 'pr_pagination',
		'page' => $page + 1,
		'dir' => 'next',
		'post_id' => $post->ID,
		'post_type' => $post_type,
		$wpcf->relationship->items_per_page_option_name => $wpcf->relationship->get_items_per_page( $post->post_type, $post_type ),
		'_wpnonce' => wp_create_nonce( 'pr_pagination' ) . $add,
	);
	$url = admin_url( 'admin-ajax.php' );


	if ( $prev ) {
		$url_params['page'] = $page - 1;
		$url_params['dir'] = 'prev';
		$link .= sprintf(
			'<a class="button-secondary wpcf-pr-pagination-link wpcf-pr-prev" href="%s" data-pagination-name="%s">',
			esc_url( add_query_arg( $url_params, $url ) ),
			esc_attr( $wpcf->relationship->items_per_page_option_name )
		);
		$link .= __( 'Prev', 'wpcf' ) . '</a>&nbsp;&nbsp;';
	}
	if ( $per_page < $count ) {
		$total_pages = ceil( $count / $per_page );
		$link .= sprintf(
			'<select class="wpcf-pr-pagination-select" name="wpcf-pr-pagination-select" data-pagination-name="%s">',
			esc_attr( $wpcf->relationship->items_per_page_option_name )
		);
		for ( $index = 1; $index <= $total_pages; $index ++ ) {
			$link .= '<option';
			if ( ( $index ) == $page ) {
				$link .= ' selected="selected"';
			}
			$url_params['page'] = $index;

			$link .= sprintf( ' value="%s"', esc_url( add_query_arg( $url_params, $url ) ) );
			$link .= '">' . $index . '</option>';
		}
		$link .= '</select>';
	}
	if ( $next ) {
		$url_params['page'] = $page + 1;
		$link .= sprintf(
			'<a class="button-secondary wpcf-pr-pagination-link wpcf-pr-next" href="%s" data-pagination-name="%s">',
			esc_url( add_query_arg( $url_params, $url ) ),
			esc_attr( $wpcf->relationship->items_per_page_option_name )
		);
		$link .= __( 'Next', 'wpcf' ) . '</a>';
	}

	return ! empty( $link ) ? '<div class="wpcf-pagination-top">' . $link . '</div>' : '';
}

/**
 * Save post hook.
 *
 * @param type $parent_post_id
 *
 * @return string
 * @refactoring !! Every action involving this code is extremely time-consuming.
 */
function wpcf_pr_admin_save_post_hook( $parent_post_id ) {

	global $wpcf;
	/*
	 * TODO https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/159760120/comments#225005357
	 * Problematic This should be done once per save (on saving main post)
	 * remove_action( 'save_post', 'wpcf_pr_admin_save_post_hook', 11);
	 */
	static $cached = array();
	/*
	 *
	 * TODO Monitor this
	 */
	// Remove main hook?
	// CHECKPOINT We remove temporarily main hook
	if ( ! isset( $cached[ $parent_post_id ] ) ) {
		if ( isset( $_POST['wpcf_post_relationship'][ $parent_post_id ] ) ) {
			$wpcf->relationship->save_children( $parent_post_id,
				(array) $_POST['wpcf_post_relationship'][ $parent_post_id ] );
		}
		// Save belongs if any
		if ( isset( $_POST['wpcf_pr_belongs'][ intval( $parent_post_id ) ] ) ) {
			wpcf_pr_admin_update_belongs( intval( $parent_post_id ),
				$_POST['wpcf_pr_belongs'][ intval( $parent_post_id ) ] );
		}

		// WPML
		wpcf_wpml_relationship_save_post_hook( $parent_post_id );

		/**
		 * Temporary workaround until https://core.trac.wordpress.org/ticket/17817 is fixed.
		 *
		 * Saving child posts cancels all save_post actions for the parent post that would otherwise come
		 * after this one.
		 *
		 * @since 2.2
		 */
		do_action( 'types_finished_saving_child_posts', $parent_post_id );

		$cached[ $parent_post_id ] = true;
	}

}

/**
 * Adds filtering regular evaluation (not wpv_conditional)
 *
 * @global type $wpcf
 *
 * @param type $posted
 * @param type $field
 *
 * @return type
 */
function wpcf_relationship_ajax_data_filter( $posted, $field ) {

	global $wpcf;

	$value = $wpcf->relationship->get_submitted_data(
		$wpcf->relationship->parent->ID,
		$wpcf->relationship->child->ID,
		$field
	);

	return is_null( $value ) ? $posted : $value;
}

/**
 * Checks if post type is parent
 *
 * @param type $parent_post_type
 * @param type $child_post_type
 *
 * @return type
 */
function wpcf_relationship_is_parent( $parent_post_type, $child_post_type ) {
	$has = wpcf_pr_admin_get_has( $parent_post_type );

	return isset( $has[ $child_post_type ] );
}

function wpcf_pr_admin_wpcf_relationship_check( $keys_to_check = array() ) {
	$keys_to_check = array_unique( array_merge( $keys_to_check, array( 'nounce', 'post_id', 'post_type' ) ) );
	foreach ( $keys_to_check as $key ) {
		if ( ! isset( $_REQUEST[ $key ] ) ) {
			die( __( 'Sorry, something went wrong. The requested can not be completed.', 'wpcf' ) );
		}
	}
	$id = esc_attr( sprintf( 'wpcf_pr_belongs_%d_%s', (int) $_REQUEST['post_id'], sanitize_text_field( $_REQUEST['post_type'] ) ) );
	if ( ! wp_verify_nonce( $_REQUEST['nounce'], $id ) ) {
		die( __( 'Sorry, something went wrong. The requested can not be completed.', 'wpcf' ) );
	}
}

function wpcf_pr_admin_wpcf_relationship_search() {
	wpcf_pr_admin_wpcf_relationship_check();

	$posts_per_page = apply_filters( 'wpcf_pr_belongs_post_numberposts', 10 );
	$post_type = sanitize_text_field( $_REQUEST['post_type'] );
	$post_status = apply_filters( 'wpcf_pr_belongs_post_status', array( 'publish', 'private' ) );
	$search = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
	$is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', false, $post_type );
	$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';

	$results = wpcf_pr_admin_wpcf_relationship_search_process(
		$post_type,
		$post_status,
		$search,
		$posts_per_page,
		$page,
		$is_translated_post_type
	);

	wp_send_json( $results );
}

/**
 * Process relationship search
 *
 * (extracted from wpcf_pr_admin_wpcf_relationship_search() since m2m )
 *
 * @param $post_type
 * @param $post_status
 * @param $search
 * @param $posts_per_page
 * @param $page
 * @param $is_translated_post_type
 *
 * @return array
 */
function wpcf_pr_admin_wpcf_relationship_search_process( $post_type, $post_status, $search, $posts_per_page, $page, $is_translated_post_type ) {
	global $wpdb;
	$values_to_prepare = array();

	// WPML
	$wpml_join = $wpml_where = "";
	// TODO Almost the same query is in wpcf_pr_admin_post_meta_box_belongs_form(), DRY.
	if ( $is_translated_post_type ) {
		$wpml_current_language = apply_filters( 'wpml_current_language', '' );
		$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
		$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type = concat( 'post_', %s ) ";
		$values_to_prepare[] = $wpml_current_language;
		$values_to_prepare[] = $post_type;

		// This is covered by the element_type condition in $wpml_where
		$where_post_type = '';
	} else {
		// No WPML tables, we just query the post type directly.
		$where_post_type = ' AND p.post_type = %s ';
		$values_to_prepare[] = $post_type;
	}

	// SEARCH
	$search_where = "";
	if ( $search != '' ) {
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			$search_term = '%' . $wpdb->esc_like( $_REQUEST['s'] ) . '%';
		} else {
			$search_term = '%' . like_escape( esc_sql( $_REQUEST['s'] ) ) . '%';
		}
		$search_where = " AND p.post_title LIKE %s ";
		$values_to_prepare[] = $search_term;
		$orderby = ' ORDER BY p.post_title ';
	} else {
		$orderby = ' ORDER BY p.post_date DESC ';
	}

	// PAGE
	if ( preg_match( '/^\d+$/', $page ) ) {
		$values_to_prepare[] = ( (int) $page - 1 ) * $posts_per_page;
	} else {
		$values_to_prepare[] = 0;
	}
	$values_to_prepare[] = $posts_per_page;

	$parents_available = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS p.ID as id, p.post_title as text, p.post_type as type, p.post_status as status
			FROM {$wpdb->posts} p {$wpml_join}
			WHERE p.post_status IN ('" . implode( "','", $post_status ) . "')
			{$wpml_where}
			{$where_post_type}
			{$search_where}
			{$orderby}
			LIMIT %d,%d",
			$values_to_prepare
		)
	);

	$parents_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

	return array(
		'items' => $parents_available,
		'total_count' => $parents_count,
		'incomplete_results' => $parents_count > $posts_per_page,
		'posts_per_page' => $posts_per_page,
	);
}

// Deprecated since the introduction of select v.4
function wpcf_pr_admin_wpcf_relationship_entry() {
	wpcf_pr_admin_wpcf_relationship_check( array( 'p' ) );
	$wpcf_post = get_post( (int) $_REQUEST['p'], ARRAY_A );
	/**
	 * remove unnecessary data and add some necessary
	 */
	$wpcf_post = array(
		'ID' => $wpcf_post['ID'],
		'parent_id' => isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0,
		'edit_link' => html_entity_decode( get_edit_post_link( $wpcf_post['ID'] ) ),
		'post_title' => $wpcf_post['post_title'],
		'post_type' => $wpcf_post['post_type'],
		'save' => 'no-save',
	);
	wp_send_json( $wpcf_post );
}

// Deprecated since the introduction of select v.4
function wpcf_pr_admin_wpcf_relationship_delete() {
	wpcf_pr_admin_wpcf_relationship_check();
	delete_post_meta( (int) $_REQUEST['post_id'], sprintf( '_wpcf_belongs_%s_id', sanitize_text_field( $_REQUEST['post_type'] ) ) );
	wp_send_json(
		array(
			'target' => sprintf( '#wpcf_pr_belongs_%d_%s-wrapper', (int) $_REQUEST['post_id'], sanitize_text_field( $_REQUEST['post_type'] ) ),
		)
	);
}

// Deprecated since the introduction of select v.4
function wpcf_pr_admin_wpcf_relationship_save() {
	wpcf_pr_admin_wpcf_relationship_check( array( 'p' ) );
	update_post_meta( (int) $_REQUEST['post_id'], sprintf( '_wpcf_belongs_%s_id', sanitize_text_field( $_REQUEST['post_type'] ) ), intval( $_REQUEST['p'] ) );
	die;
}

function wpcf_pr_admin_wpcf_relationship_update() {
	wpcf_pr_admin_wpcf_relationship_check();
	$post_id = (int) $_REQUEST['post_id'];
	$parent_post_type = sanitize_text_field( $_REQUEST['post_type'] );
	$data = array();
	if (
		isset( $_REQUEST['p'] )
		&& (int) $_REQUEST['p'] > 0
	) {
		update_post_meta( $post_id, sprintf( '_wpcf_belongs_%s_id', $parent_post_type ), (int) $_REQUEST['p'] );
		$data['edit_link'] = admin_url( 'post.php' );
	} else {
		delete_post_meta( $post_id, sprintf( '_wpcf_belongs_%s_id', $parent_post_type ) );
	}
	wp_send_json_success( $data );
}

function wpcf_pr_belongs_post_numberposts_minimum( $posts_per_page ) {
	if ( $posts_per_page < 6 ) {
		$posts_per_page = 7;
	}

	return $posts_per_page;
}
