<?php
/*
 * Post relationship class.
 *
 *
 */

/**
 * Post relationship class
 *
 * @since Types 1.2
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category Relationship
 * @author srdjan <srdjan@icanlocalize.com>
 *
 */
class WPCF_Relationship
{
    /**
     * Custom field
     *
     * @var type
     */
    var $cf = array();
    var $data = array();

    /**
     * Settings
     *
     * @var type
     */
    var $settings = array();
    var $items_per_page = 5;
    var $items_per_page_option_name = '_wpcf_relationship_items_per_page';
    var $child_form = null;

    /**
     * Construct function.
     */
    function __construct()
    {
        $this->cf = new WPCF_Field;
        $this->settings = get_option( 'wpcf_post_relationship', array() );
        add_action( 'wp_ajax_add-types_reltax_add',
                array($this, 'ajaxAddTax') );
    }

    /**
     * Sets current data.
     *
     * @param type $parent
     * @param type $child
     * @param type $field
     * @param type $data
     */
    function set( $parent, $child, $data = array() )
    {
        return $this->_set( $parent, $child, $data );
    }

    /**
     * Sets current data.
     *
     * @param type $parent
     * @param type $child
     * @param type $field
     * @param type $data
     */
    function _set( $parent, $child, $data = array() )
    {
        $this->parent = $parent;
        $this->child = $child;
        $this->cf = new WPCF_Field;
        // TODO Revise usage
        $this->data = $data;
    }

    /**
     * Meta box form on post edit page.
     *
     * @param type $parent Parent post
     * @param type $post_type Child post type
     * @return type string HTML formatted form table
     */
    function child_meta_form($parent, $post_type)
    {
        if ( is_integer( $parent ) ) {
            $parent = get_post( $parent );
        }
        $output = '';
        require_once dirname( __FILE__ ) . '/relationship/form-child.php';
        $this->child_form = new WPCF_Relationship_Child_Form(
                        $parent,
                        $post_type,
                        $this->settings( $parent->post_type, $post_type )
        );
        $output .= $this->child_form->render();

        return $output;
    }

    /**
     * Child row rendered on AJAX 'Add New Child' call.
     *
     * @param type $parent_id
     * @param type $child_id
     * @return type
     */
    function child_row($parent_id, $child_id)
    {
        $parent = get_post( intval( $parent_id ) );
        $child = get_post( intval( $child_id ) );
        if ( empty( $parent ) || empty( $child ) ) {
            return new WP_Error( 'wpcf-relationship-save-child', 'no parent/child post' );
        }
        $output = '';
        $this->child_form = $this->_get_child_form( $parent, $child );
        $output .= $this->child_form->child_row( $child );

        return $output;
    }

    /**
     * Returns HTML formatted form.
     *
     * @param type $parent
     * @param type $child
     * @return \WPCF_Relationship_Child_Form
     */
    function _get_child_form($parent, $child)
    {
        require_once dirname( __FILE__ ) . '/relationship/form-child.php';
        return new WPCF_Relationship_Child_Form(
                        $parent,
                        $child->post_type,
                        $this->settings( $parent->post_type, $child->post_type )
        );
    }

    function get_child()
    {
        $r = $this->child;
        $r->parent = $this->parent;
        $r->form = $this->_get_child_form( $r->parent, $this->child );
        return $r;
    }

    /**
     * Save items_per_page settings.
     *
     * @param type $parent
     * @param type $child
     * @param int $num
     */
    function save_items_per_page($parent, $child, $num)
    {
        if ( post_type_exists( $parent ) && post_type_exists( $child ) ) {
            $option_name = $this->items_per_page_option_name . '_' . $parent . '_' . $child;
            if ( $num == 'all' ) {
                $num = 9999999999999999;
            }
            update_option( $option_name, intval( $num ) );
        }
    }

    /**
     * Return items_per_page settings
     *
     * @param type $parent
     * @param type $child
     * @return type
     */
    function get_items_per_page($parent, $child)
    {
        $per_page = get_option( $this->items_per_page_option_name . '_' . $parent . '_' . $child,
                $this->items_per_page );
        return empty( $per_page ) ? $this->items_per_page : $per_page;
    }

    /**
     * Adjusts post name when saving.
     *
     * @todo Revise (not used?)
     * @param type $post
     * @return type
     */
    function get_insert_post_name($post)
    {
        if ( empty( $post->post_title ) ) {
            return $post->post_type . '-' . $post->ID;
        }
        return $post->post_title;
    }

    /**
     * Bulk saving children.
     *
     * @param int $parent_id
     * @param array $children Array $child_id => $fields. For details about $fields see save_child().
     */
    function save_children($parent_id, $children)
    {
        foreach ( $children as $child_id => $fields ) {
            $this->save_child( $parent_id, $child_id, $fields );
        }
    }

    /**
     * Unified save child function.
     *
     * @param int $parent_id
     * @param int $child_id
     * @param array $save_fields
     * @return bool|WP_Error
     */
    function save_child( $parent_id, $child_id, $save_fields = array() )
    {
    	// this function modifies $_POST
	    // we need to make sure to revoke all changes to $_POST at the end (types-1644)
    	$POST_backup = $_POST;

        $parent = get_post( intval( $parent_id ) );
        $child = get_post( intval( $child_id ) );
        $post_data = array();

        if ( empty( $parent ) || empty( $child ) ) {
            return new WP_Error( 'wpcf-relationship-save-child', 'no parent/child post' );
        }

        // Save relationship
        update_post_meta( $child->ID,
                '_wpcf_belongs_' . $parent->post_type . '_id', $parent->ID );

        // Check if added via AJAX
        $check = get_post_meta( $child->ID, '_wpcf_relationship_new', true );
        $new = !empty( $check );
        delete_post_meta( $child->ID, '_wpcf_relationship_new' );

        // Set post data
        $post_data['ID'] = $child->ID;

        // Title needs to be checked if submitted at all
        if ( !isset( $save_fields['_wp_title'] ) ) {
            // If not submitted that means it is not offered to be edited
            if ( !empty( $child->post_title ) ) {
                $post_title = $child->post_title;
            } else {
                // DO NOT LET IT BE EMPTY
                $post_title = $child->post_type . ' ' . $child->ID;
            }
        } else {
            $post_title = $save_fields['_wp_title'];
        }


        $post_data['post_title'] = $post_title;
        $post_data['post_content'] = isset( $save_fields['_wp_body'] ) ? $save_fields['_wp_body'] : $child->post_content;
        $post_data['post_excerpt'] = isset( $save_fields['_wp_excerpt'] ) ? $save_fields['_wp_excerpt'] : $child->post_excerpt;
        $post_data['post_type'] = $child->post_type;

        // Check post status - if new, convert to 'publish' else keep remaining
        if ( $new ) {
            $post_data['post_status'] =  'publish';
        } else {
            $post_data['post_status'] =  get_post_status( $child->ID );
        }

        /*
         *
         *
         *
         *
         *
         *
         * UPDATE POST
         */

        $cf = new WPCF_Field;
        if (
            isset( $_POST['wpcf_post_relationship'][$parent_id])
            && isset( $_POST['wpcf_post_relationship'][$parent_id][$child_id] )
        ) {
            $_POST['wpcf'] = array();
            foreach( $_POST['wpcf_post_relationship'][$parent_id][$child_id] as $slug => $value ) {
                $_POST['wpcf'][$cf->get_slug_no_prefix( $slug )] = $value;
                $_POST['wpcf'][$slug]                            = $value;
            }
        }
        unset($cf);

        /**
         * avoid send data to children
         */
        if ( isset( $_POST['post_ID'] ) ) {
            $temp_post_data = $_POST;
            $_POST = array();
            foreach( array('wpcf_post_relationship', 'post_ID', '_wptoolset_checkbox', 'wpcf', '_wpnonce') as $key ) {
                if ( isset($temp_post_data[$key]) ) {
                    $_POST[$key] = $temp_post_data[$key];
                }
            }
        }

        // Workaround for types-876, see wpcf_admin_post_save_post_hook().
	    add_filter( 'types_updating_child_post', '__return_true' );

        $updated_id = wp_update_post( $post_data );

        if ( isset($temp_post_data) ) {
            $_POST = $temp_post_data;
            unset($temp_post_data);
        }

        if ( empty( $updated_id ) ) {
	        remove_filter( 'types_updating_child_post', '__return_true' );
            return new WP_Error( 'relationship-update-post-failed', 'Updating post failed' );
        }

        // Save parents
        if ( !empty( $save_fields['parents'] ) ) {
            foreach ( $save_fields['parents'] as $parent_post_type => $parent_post_id ) {
                update_post_meta( $child->ID,
                        '_wpcf_belongs_' . $parent_post_type . '_id',
                        $parent_post_id );
            }
        }

        // Update taxonomies
        if ( !empty( $save_fields['taxonomies'] ) && is_array( $save_fields['taxonomies'] ) ) {
            $_save_data = array();
            foreach ( $save_fields['taxonomies'] as $taxonomy => $t ) {
                if ( !is_taxonomy_hierarchical( $taxonomy ) ) {
                    $_save_data[$taxonomy] = strval( $t );
                    continue;
                }
                foreach ( $t as $term_id ) {
                    if ( $term_id != '-1' ) {
                        $term = get_term( $term_id, $taxonomy );
                        if ( empty( $term ) ) {
                            continue;
                        }
                        $_save_data[$taxonomy][] = $term_id;
                    }
                }
            }
            wp_delete_object_term_relationships( $child->ID,
                    array_keys( $save_fields['taxonomies'] ) );
            foreach ( $_save_data as $_taxonomy => $_terms ) {
                wp_set_post_terms( $child->ID, $_terms, $_taxonomy,
                        $append = false );
            }
        }

        // Unset non-types
        unset( $save_fields['_wp_title'], $save_fields['_wp_body'],
            $save_fields['parents'], $save_fields['taxonomies'] );

        /**
         * add filter to remove field name from error message
         */
        /** This filter is toolset-common/toolset-forms/classes/class.validation.php */
        add_filter('toolset_common_validation_add_field_name_to_error', '__return_false', 1234, 1);

        /**
         * UPDATE Loop over fields
         */
        foreach ( $save_fields as $slug => $value ) {
            if ( defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
                // Get field by slug
                $field = wpcf_fields_get_field_by_slug( str_replace( WPCF_META_PREFIX, '', $slug ) );
                if ( empty( $field ) ) {
                    continue;
                }
                // Set config
                $config = wptoolset_form_filter_types_field( $field, $child->ID );
                // Check if valid
                $valid = wptoolset_form_validate_field( 'post', $config, $value );
                if ( is_wp_error( $valid ) ) {
                    $errors = $valid->get_error_data();
                    $msg = sprintf(
                        __( 'Child post "%s" field "%s" not updated:', 'wpcf' ),
                        $child->post_title,
                        $field['name']
                    );
                    wpcf_admin_message_store( $msg . ' ' . implode( ', ', $errors ), 'error' );
                    continue;
                }
            }
            $this->cf->set( $child, $field );
            $this->cf->context = 'post_relationship';
            $this->cf->save( $value );
        }

        /**
         * save feature image
         */
        if ( isset( $save_fields['_wp_featured_image']) ) {
            if ( $save_fields['_wp_featured_image'] ) {
                set_post_thumbnail( $updated_id, $save_fields['_wp_featured_image']);
            } else {
                delete_post_thumbnail($updated_id);
            }
        }


        remove_filter('toolset_common_validation_add_field_name_to_error', '__return_false', 1234, 1);

        do_action( 'wpcf_relationship_save_child', $child, $parent );

        clean_post_cache( $parent->ID );
        clean_post_cache( $child->ID );
        // Added because of caching meta 1.5.4
        wp_cache_flush();

	    remove_filter( 'types_updating_child_post', '__return_true' );

	    // re-apply original $_POST data
		$_POST = $POST_backup;

        return true;
    }

    /**
     * Saves new child.
     *
     * @param int $parent_id
     * @param string $post_type
     * @return int|WP_Error
     */
    function add_new_child($parent_id, $post_type)
    {
        global $wpdb;
        $parent = get_post( $parent_id );
        if ( empty( $parent ) ) {
            return new WP_Error( 'wpcf-relationship-no-parent', 'No parent' );
        }
        $new_post = array(
            'post_title' => __('New Child', 'wpcf'). ': '.$post_type,
            'post_type' => $post_type,
            'post_status' => 'draft',
        );
        $id = wp_insert_post( $new_post, true );
        /**
         * return wp_error
         */
        if ( is_wp_error( $id ) ) {
            return $id;
        }
        /**
         * Mark that it is new post
         */
        update_post_meta( $id, '_wpcf_relationship_new', 1 );
        /**
         * Save relationship
         */
        update_post_meta( $id, '_wpcf_belongs_' . $parent->post_type . '_id', $parent->ID );
        /**
         * Fix title
         */
        $wpdb->update(
            $wpdb->posts,
            array('post_title' => $post_type . ' ' . $id),
            array('ID' => $id), array('%s'), array('%d')
        );
        do_action( 'wpcf_relationship_add_child', get_post( $id ), $parent );
        wp_cache_flush();
        return $id;
    }

    /**
     * Saved relationship settings.
     *
     * @param type $parent
     * @param type $child
     * @return type
     */
    function settings($parent, $child)
    {
        return isset( $this->settings[$parent][$child] ) ? $this->settings[$parent][$child] : array();
    }

    /**
     * Fetches submitted data.
     *
     * @param type $parent_id
     * @param type $child_id
     * @return type
     */
    function get_submitted_data($parent_id, $child_id, $field)
    {
        if ( !is_string( $field ) ) {
            $_field_slug = $field->slug;
        } else {
            $_field_slug = $field;
        }
        return isset( $_POST['wpcf_post_relationship'][$parent_id][$child_id][$_field_slug] ) ? $_POST['wpcf_post_relationship'][$parent_id][$child_id][$_field_slug] : null;
    }

		/**
     * Gets all parents per post type.
     *
     * @param type $child
     * @return type
     */
    public static function get_parents($child)
    {
        $parents = array();
        $item_parents = wpcf_pr_admin_get_belongs( $child->post_type );
        if ( $item_parents ) {
            foreach ( $item_parents as $post_type => $data ) {

                // Get parent ID
                $meta = wpcf_get_post_meta( $child->ID,
                        '_wpcf_belongs_' . $post_type . '_id', true );

                if ( !empty( $meta ) ) {

                    $parent_post = get_post( $meta );

                    if ( !empty( $parent_post ) ) {
                        $parents[$parent_post->post_type] = $parent_post;
                    }
                }
            }
        }
        return $parents;
    }


		/**
		 * Gets all related post types.
		 *
		 * @param type $post
		 * @param string $field To check if the related post types has this field.
		 * @return type
		 * @since m2m
		 */
		public static function get_related( $post, $field ) {
			$related = array();
			$custom_post_data = get_post_type_object( $post->post_type );
			$items_related = wpcf_pr_admin_get_related( $post->post_type, $field );

			if ( ! is_array( $items_related ) ) {
				return array();
			}

			foreach ( $items_related as $cardinality_type => $groups ) {
				foreach ( $groups as $post_type ) {
					if ( 'post_types' !== $cardinality_type ) {
						// Relatioship between post types
						$relationship_type = '';
						switch ( $cardinality_type ) {
							case 'one-to-one':
								$relationship_type = __( '(one-to-one)', 'wpcf' );
								break;
							case 'one-to-many':
							case 'one-to-many-from-child':
								$relationship_type = __( '(one-to-many)', 'wpcf' );
								break;
							case 'many-to-many':
								$relationship_type = __( '(many-to-many)', 'wpcf' );
								break;
						}

						// It will be enabled only if it is one-to-many or one-to-one
						$enabled = true;
						switch ( $cardinality_type ) {
							case 'one-to-many':
							case 'many-to-many':
								$enabled = false;
								break;
						}

						$post_type_data = get_post_type_object( $post_type->post_type );

						// Help message in case it is not visible
						$help = array();
						switch ( $cardinality_type ) {
							case 'one-to-many':
								$help['header'] = __( 'One to many', 'wpcf' );
								$link = 'https://toolset.com/course-lesson/how-to-set-up-post-relationships-in-wordpress/?utm_source=plugin&utm_medium=gui&utm_campaign=types';
								$help['content'] = htmlentities( sprintf( __( 'You cannot select this because: <strong>%s</strong> can be associated with more than one <strong>%s</strong><br /><br /><a href="%s">Learn more</a>', 'wpcf' ), $custom_post_data->labels->singular_name, $post_type_data->label, $link ) );
								break;
							case 'many-to-many':
								$help['header'] = __( 'Many to many', 'wpcf' );
								$link = 'https://toolset.com/course-lesson/many-to-many-post-relationships/?utm_source=plugin&utm_medium=gui&utm_campaign=types';
									$help['content'] = htmlentities( sprintf( __( 'You cannot select this because: <strong>%s</strong> can be associated with more than one <strong>%s</strong><br /><br /><a href="%s">Learn more</a>', 'wpcf' ), $custom_post_data->label, $post_type_data->label, $link ) );
								break;
						}

						// It must have the field.
						if ( $enabled && ! in_array( $post_type->post_type, $items_related['post_types'] ) ) {
							$enabled = false;

							$help['header'] = __( 'Field missing', 'wpcf' );
							$help['content'] = htmlentities( sprintf( __( 'You cannot select this because: <strong>%s</strong> doesn\'t have a field <strong>%s</strong>', 'wpcf' ), $post_type_data->labels->singular_name, $field['name'] ) );
						}

						if ( ! isset( $related[ $post_type->post_type ] ) ) {
							$related[ $post_type->post_type ] = array(
								'name' 				 => $post_type_data->label,
								'relationship' => $relationship_type,
								'enabled'			 => $enabled,
								'help'				 => $help,
								'value'				 => $post_type->value,
							);
						}
					}
				}
			}
			return $related;
		}


		/**
		 * Gets all intermediate post types.
		 *
		 * @since m2m
		 * @param type $post
		 * @param string $field To check if the parent intermediate post types has this field.
		 * @return type
		 */
		public static function get_intermediate( $post, $field ) {
			$related = array();
			$related = wpcf_pr_admin_get_intermediate( $post->post_type, $field );
			$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
			if ( is_array( $related ) ) {
				// Adding help, if needed.
				foreach( $related as $i => $type ) {
					$related[ $i ]['help'] = array();
					if ( ! $type['enabled'] ) {
						$definition_slug = preg_replace('#@([^\.]+)\..*#', '$1', $type['value']);
						$definition = $definition_repository->get_definition( $definition_slug );
						if ( $definition ) {
							$intermediary_post_type = get_post_type_object( $definition->get_intermediary_post_type() );
							if ( $intermediary_post_type ) {
								$related[ $i ]['help']['header'] = __( 'Field missing', 'wpcf' );
								$related[ $i ]['help']['content'] = htmlentities( sprintf( __( 'You cannot select this because: <strong>%s</strong> doesn\'t have a field <strong>%s</strong>', 'wpcf' ), $intermediary_post_type->labels->singular_name, $field['name'] ) );
							}
						}
					}
				}
			}
			return $related;
		}


    /**
     * Gets post parent by post type.
     *
     * @param type $post_id
     * @param type $parent_post_type
     * @return type
     */
    public static function get_parent($post_id, $parent_post_type)
    {
        return wpcf_get_post_meta( $post_id,
                        '_wpcf_belongs_' . $parent_post_type . '_id', true );
    }

    /**
     * AJAX adding taxonomies
     */
    public function ajaxAddTax()
    {
        if ( isset( $_POST['types_reltax'] ) ) {
            $data = array_shift( $_POST['types_reltax'] );
            $tax = key( $data );
            $val = array_shift( $data );
            $__nonce = array_shift( $_POST['types_reltax_nonce'] );
            $nonce = array_shift( $__nonce );
            $_POST['action'] = 'add-' . $tax;
            $_POST['post_category'][$tax] = $val;
            $_POST['tax_input'][$tax] = $val;
            $_POST['new'.$tax] = $val;
            $_REQUEST["_ajax_nonce-add-{$tax}"] = $nonce;
            _wp_ajax_add_hierarchical_term();
        }
        die();
    }

    /**
     * Meta box form on post edit page.
     *
     * @param type $parent Parent post
     * @param type $post_type Child post type
     * @return type string HTML formatted list
     */
    function child_list($parent, $post_type)
    {
        if ( is_integer( $parent ) ) {
            $parent = get_post( $parent );
        }
        $output = '';
        require_once dirname( __FILE__ ) . '/relationship/form-child.php';
        $this->child_form = new WPCF_Relationship_Child_Form(
                        $parent,
                        $post_type,
                        $this->settings( $parent->post_type, $post_type )
                    );
        foreach($this->child_form->children as $child) {
            $output .= sprintf(
                '<li>%s</li>',
                apply_filters('post_title', $child->post_title)
            );
        }
        if ( $output ) {
            $output = sprintf(
                '<ul>%s</ul>',
                $output
            );
        } else {
            $output = sprintf(
                '<p class="info">%s</p>',
                $this->child_form->child_post_type_object->labels->not_found
            );
        }

        return $output;
    }


}
