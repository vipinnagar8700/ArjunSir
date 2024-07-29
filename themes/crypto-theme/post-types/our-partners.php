<?php
function customposttypeourpartners() { 
    register_post_type( 'partners',
    // partners CPT Options
        array(
            'labels'		 => array(
            'name'			 => __( 'Our Partners' ),
            'singular_name'  => __( 'partners' ),
			 ),
            'public' => true,
            'has_archive' 	 => true,
            'rewrite' 		 => array('slug' => 'partners'),
            'show_in_rest' 	 => true,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),	
            'menu_icon' => 'dashicons-businessman',
			));
	register_taxonomy('partners-category','partners',
        array(
            'label' 		=> __( 'Our Partners Category' ),
            'rewrite' 		=> array( 
			'slug'		    => 'partners-category' ),
            'hierarchical'  => true,
        ));
		}
// Hooking up our function to category for partners
	add_action( 'init', 'customposttypeourpartners' );