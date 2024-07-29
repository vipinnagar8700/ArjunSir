<?php
function customposttypepressrelease() { 
    register_post_type( 'press-release',
    // press-release CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Press Release' ),
            'singular_name'  => __( 'press-release' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'press-release'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-megaphone',
			));
	register_taxonomy('press-release-category','press-release',
        array(
            'label' 		=> __( 'Press Release Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'press-release-category' ),
            'hierarchical'  => true,
        ));
		}
// Hooking up our function to category for press-release
	add_action( 'init', 'customposttypepressrelease' );