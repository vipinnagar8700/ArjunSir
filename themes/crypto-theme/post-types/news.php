<?php
function customposttypenews() { 
    register_post_type( 'news',
    // news CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'News' ),
            'singular_name'  => __( 'news' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'news'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-rss',
			));
	register_taxonomy('news-category','news',
        array(
            'label' 		=> __( 'News Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'news-category' ),
            'hierarchical'  => true,
        ));
        register_taxonomy(
            'news-tag',  // Custom taxonomy name
            'news',      // Associated post type
            array(
                'label'        => __( 'News Tags' ),
                'rewrite'      => array( 'slug' => 'news-tag' ),
                'hierarchical' => false,
                'show_in_rest' => true,  // Enable for Gutenberg editor
            )
        );
		}
// Hooking up our function to category for news
	add_action( 'init', 'customposttypenews' );