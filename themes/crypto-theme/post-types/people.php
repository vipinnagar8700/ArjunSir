<?php
function customposttypepeople() { 
    register_post_type( 'people',
    // people CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'People' ),
            'singular_name'  => __( 'people' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'people'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-groups',
			));
	register_taxonomy('people-category','people',
        array(
            'label' 		=> __( 'People Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'people-category' ),
            'hierarchical'  => true,
        ));
        // register_taxonomy(
        //     'people-tag',  // Custom taxonomy name
        //     'people',      // Associated post type
        //     array(
        //         'label'        => __( 'people Tags' ),
        //         'rewrite'      => array( 'slug' => 'people-tag' ),
        //         'hierarchical' => false,
        //         'show_in_rest' => true,  // Enable for Gutenberg editor
        //     )
        // );
		}
// Hooking up our function to category for people
	add_action( 'init', 'customposttypepeople' );