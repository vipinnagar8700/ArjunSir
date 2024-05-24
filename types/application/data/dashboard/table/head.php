<?php
$question_marks = array(

	'type' => array(
		'id'            => 'type',
		'title'         => __( 'Post Type', 'wpcf' ),
	),

	'fields' => array(
		'id'            => 'fields',
		'title'         => __( 'Custom fields', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'A list of all Custom Post Fields and their attachment to the Post Types.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-fields', 'tooltip', false, 'gui' )
			),
		)
	),

	'taxonomies' => array(
		'id'            => 'taxonomies',
		'title'         => __( 'Taxonomies', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'A list of all Taxonomies and their attachment to the Post Types.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-taxonomies', 'tooltip', false, 'gui' )
			),
		)
	),





	'template' => array(
		'id'            => 'template',
		'title'         => __( 'Template', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'A template displays single-item pages with your design and fields.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-template', 'tooltip', false, 'gui' )
			),
		)
	),

	'archive' => array(
		'id'            => 'archive',
		'title'         => __( 'Archive', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'An archive is the standard list that WordPress produces for content.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-archive', 'tooltip', false, 'gui' )
			),
		)
	),

	'views' => array(
		'id'            => 'views',
		'title'         => __( 'Views', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Views are custom lists of content, which you can display anywhere in the site.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-views', 'tooltip', false, 'gui' )
			),
		)
	),

	'forms' => array(
		'id'            => 'forms',
		'title'         => __( 'Forms', 'wpcf' ),
		'description'   => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Forms allow to create and edit content from the site’s front-end.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'external' => true,
				'label'  => __( 'Learn more', 'wpcf' ),
				'target' => Types_Helper_Url::get_url( 'learn-how-forms', 'tooltip', false, 'gui' )
			),
		)
	)
);

// Visual Composer
if( defined( 'WPB_VC_VERSION' ) ) {
	$question_marks['template']['description'][1]['label'] = __( 'Creating templates with Visual Composer', 'wpcf' );
}
// Beaver Builder
else if( class_exists( 'FLBuilderLoader' ) ) {
	$question_marks['template']['description'][1]['label'] = __( 'Creating templates with Beaver Builder', 'wpcf' );
}
// Layouts
else if( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) ) {
	$question_marks['template']['description'][1]['label'] = __( 'Creating templates with Layouts', 'wpcf' );
}

// Remove Views if Toolset Blocks is active
// TODO Use the Toolset_Condition_Plugin_Toolset_Blocks_Active condition here, after we update it to work properly
$is_toolset_blocks_available = ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) );
if ( $is_toolset_blocks_available ) {
	unset( $question_marks['views'] );
}

return $question_marks;
