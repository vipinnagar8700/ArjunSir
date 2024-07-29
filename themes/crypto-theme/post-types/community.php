<?php
function customposttypecommunity() { 
    register_post_type( 'community',
    // community CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Community' ),
            'singular_name'  => __( 'community' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'community'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-buddicons-community',
			));
	register_taxonomy('community-category','community',
        array(
            'label' 		=> __( 'Community Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'community-category' ),
            'hierarchical'  => true,
        ));
        register_taxonomy(
            'community-tag',  // Custom taxonomy name
            'community',      // Associated post type
            array(
                'label'        => __( 'Community Tags' ),
                'rewrite'      => array( 'slug' => 'community-tag' ),
                'hierarchical' => false,
                'show_in_rest' => true,  // Enable for Gutenberg editor
            )
        );
		}
// Hooking up our function to category for community
	add_action( 'init', 'customposttypecommunity' );