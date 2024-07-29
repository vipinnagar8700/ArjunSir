<?php
function customposttypetopics() { 
    register_post_type( 'topics',
    // topics CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Topics' ),
            'singular_name'  => __( 'topics' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'topics'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-buddicons-topics',
			));
	register_taxonomy('topics-category','topics',
        array(
            'label' 		=> __( 'Topics Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'topics-category' ),
            'hierarchical'  => true,
        ));
        register_taxonomy(
            'topics-tag',  // Custom taxonomy name
            'topics',      // Associated post type
            array(
                'label'        => __( 'Topics Tags' ),
                'rewrite'      => array( 'slug' => 'topics-tag' ),
                'hierarchical' => false,
                'show_in_rest' => true,  // Enable for Gutenberg editor
            )
        );
		}
// Hooking up our function to category for topics
	add_action( 'init', 'customposttypetopics' );