<?php
function customposttypeleaders() { 
    register_post_type( 'leaders',
    // leaders CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Industry Leaders' ),
            'singular_name'  => __( 'leaders' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'leaders'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-search',
			));
	register_taxonomy('leaders-category','leaders',
        array(
            'label' 		=> __( 'Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'leaders-category' ),
            'hierarchical'  => true,
        ));
 
		}
// Hooking up our function to category for leaders
	add_action( 'init', 'customposttypeleaders' );