<?php
function customposttypediscover() { 
    register_post_type( 'discover',
    // discover CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Discover' ),
            'singular_name'  => __( 'discover' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'discover'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-search',
			));
	register_taxonomy('discover-category','discover',
        array(
            'label' 		=> __( 'Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'discover-category' ),
            'hierarchical'  => true,
        ));
        register_taxonomy(
            'discover-tag',  // Custom taxonomy name
            'discover',      // Associated post type
            array(
                'label'        => __( 'Discover Tags' ),
                'rewrite'      => array( 'slug' => 'discover-tag' ),
                'hierarchical' => false,
                'show_in_rest' => true,  // Enable for Gutenberg editor
            )
        );
		}
// Hooking up our function to category for discover
	add_action( 'init', 'customposttypediscover' );