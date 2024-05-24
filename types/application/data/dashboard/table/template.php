<?php
/**
 * This describes the "Template" column of Toolset Dashboard.
 *
 * Every element of the top-level array (let's call it a case) is evaluated according to specified
 * conditions (which may be either subclasses of \Types_Helper_Condition or implementations
 * of \Toolset_Condition_Interface) and if ALL conditions match, the "description" element is selected
 * (used to render the output of a particular table cell).
 *
 * The output of all selected cases will then be concatenated in the order in which they're defined here.
 *
 * Explore Types_Page_Dashboard::get_dashboard_types_table() for further context.
 */

return array(
	/* Layouts, integrated, template missing*/
	'layouts-integrated-template-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'\OTGS\Toolset\Common\Condition\Views\IsClassicFlavourOrInactive',
			'Types_Helper_Condition_Layouts_Compatible',
			'Types_Helper_Condition_Layouts_Template_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no template layout for %POST-LABEL-SINGULAR% items.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create template', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-TEMPLATE%',
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Layouts, template */
	'layouts-template' => array(
		'type' => 'template',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'\OTGS\Toolset\Common\Condition\Views\IsClassicFlavourOrInactive',
			'Types_Helper_Condition_Layouts_Template_Exists'
		),

		'description' => array(
			array(
				'type' => 'link',
				'label' => '%POST-LAYOUT-TEMPLATE%',
				'target'  => '%POST-EDIT-LAYOUT-TEMPLATE%'
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Views, template */
	'views-template' => array(
		'type' => 'template',

		'conditions'=> array(
			'\OTGS\Toolset\Common\Condition\Layouts\IsMissingOrToolsetBlocksActive',
			'Types_Helper_Condition_Views_Template_Exists'
		),

		'description' => array(
			array(
				'type' => 'link',
				'label' => '%POST-CONTENT-TEMPLATE-NAME%',
				'target'  => '%POST-EDIT-CONTENT-TEMPLATE%'
			),
			array(
				'type' => 'paragraph',
				'content'  => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* For posts and pages we always show template file if it exists */
	'single-exists-for-posts-pages--post-template-file' => array(
		'type' => 'template',
		'conditions'=> array(
			'Types_Helper_Condition_Type_Post_Or_Page',
			'Types_Helper_Condition_Single_Exists',
			'Types_Helper_Condition_Single_Has_Fields'
		),
		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-TEMPLATE-FILE%', 'wpcf' )
			),
		),
	),
	'single-exists-for-posts-pages--post-content-template-conditions' => array(
		'type' => 'template',
		'conditions'=> array(
			'Types_Helper_Condition_Views_Active',
			'Types_Helper_Condition_Type_Post_Or_Page',
			'Types_Helper_Condition_Single_Exists',
			'Types_Helper_Condition_Single_Has_Fields',
		),
		'description' => array(
			array(
				'type' => 'paragraph',
				'content'  => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
		),
	),
	'single-exists-for-posts-pages--custom-errors-elements-list' => array(
		'type' => 'template',
		'conditions'=> array(
			'Types_Helper_Condition_Type_Post_Or_Page',
			'Types_Helper_Condition_Single_Exists',
			'Types_Helper_Condition_Single_Has_Fields'
		),
		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Layouts, has template with missing fields. */
	'layouts-single-fields-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'\OTGS\Toolset\Common\Condition\Views\IsClassicFlavourOrInactive',
			'Types_Helper_Condition_Layouts_Template_Missing',
			'Types_Helper_Condition_Single_No_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Your theme’s template file %POST-TEMPLATE-FILE% for displaying %POST-LABEL-SINGULAR% items is missing custom fields.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create template', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-TEMPLATE%',
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
		),
	),

	/* Layouts, single.php exists, but layouts missing */
	'layouts-php-template-exists-layouts-template-missing' => array(
		'type' => 'template',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'\OTGS\Toolset\Common\Condition\Views\IsClassicFlavourOrInactive',
			'Types_Helper_Condition_Layouts_Template_Missing',
			'Types_Helper_Condition_Single_Exists'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-TEMPLATE-FILE%', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create template', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-TEMPLATE%',
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),

	),

	/* Layouts, template missing*/
	'layouts-template-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'\OTGS\Toolset\Common\Condition\Views\IsClassicFlavourOrInactive',
			'Types_Helper_Condition_Layouts_Template_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no template layout for %POST-LABEL-SINGULAR% items.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'label'  => __( 'Create template', 'wpcf' ),
				'target' => '%POST-CREATE-LAYOUT-TEMPLATE%',
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* No Views, No Layouts, Single Missing */
	'single-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Single_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Your theme doesn’t have a template to display %POST-LABEL-PLURAL%.', 'wpcf' )
			),
			array(
				'type'   => 'dialog',
				'class'  => 'button',
				'label'  => __( 'Create template', 'wpcf' ),
				'dialog' => array(
					'id' => 'resolve-single-no-template',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'Toolset plugins let you design templates for single items (%POST-LABEL-SINGULAR% pages) without
                        writing PHP. Your templates will include all the fields that you need and your design.', 'wpcf' )
						),
						array(
							'type'   => 'link',
							'class'  => 'button-primary types-button',
							'external' => true,
							'label'  => __( 'Learn about creating templates with Toolset', 'wpcf' ),
							'target' => Types_Helper_Url::get_url( 'creating-templates-with-toolset', 'popup', false, 'gui' ),
						),
					)
				)
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* No Views, No Layouts, Single, without Fields */
	'single-fields-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Single_No_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Your theme’s template file for displaying %POST-LABEL-SINGULAR% items is missing custom fields.', 'wpcf' )
			),
			array(
				'type'   => 'dialog',
				'class'  => 'button-primary types-button',
				'label'  => __( 'Resolve', 'wpcf' ),
				'dialog' => array(
					'id' => 'resolve-single-no-fields',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'To design templates, you need to activate Toolset Views plugin.', 'wpcf' )
						),
						array(
							'type'   => 'link',
							'class'  => 'button-primary types-button',
							'external' => true,
							'label'  => __('Download Toolset Views from your Toolset account', 'wpcf' ),
							'target' => Types_Helper_Url::get_url( 'toolset-account-downloads', 'popup', false, 'gui' ),
						),
					)
				)
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* No Views, No Layouts, Single with Fields */
	'single-fields' => array(
		'type' => 'template',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Missing',
			'Types_Helper_Condition_Single_Has_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-TEMPLATE-FILE%', 'wpcf' )
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Views, has template with missing fields. */
	'views-single-fields-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'\OTGS\Toolset\Common\Condition\Layouts\IsMissingOrToolsetBlocksActive',
			'Types_Helper_Condition_Views_Template_Missing',
			'Types_Helper_Condition_Single_No_Fields',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'Your theme’s template file %POST-TEMPLATE-FILE% for displaying %POST-LABEL-SINGULAR% items is missing custom fields.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'target' => '%POST-CREATE-CONTENT-TEMPLATE%',
				'label'  => __( 'Create Content Template', 'wpcf' )
			),
			array(
				'type' => 'paragraph',
				'content'  => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Views, single.php exists, but views missing */
	'views-php-template-exists-views-template-missing' => array(
		'type' => 'template',

		'conditions'=> array(
			'\OTGS\Toolset\Common\Condition\Layouts\IsMissingOrToolsetBlocksActive',
			'Types_Helper_Condition_Views_Template_Missing',
			'Types_Helper_Condition_Single_Exists'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( '%POST-TEMPLATE-FILE%', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'target' => '%POST-CREATE-CONTENT-TEMPLATE%',
				'label'  => __( 'Create Content Template', 'wpcf' )
			),
			array(
				'type' => 'paragraph',
				'content'  => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),
	),

	/* Views, template missing */
	'views-template-missing' => array(
		'type' => 'template',

		'priority' => 'important',

		'conditions'=> array(
			'\OTGS\Toolset\Common\Condition\Layouts\IsMissingOrToolsetBlocksActive',
			'Types_Helper_Condition_Views_Template_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __( 'There is no Content Template for %POST-LABEL-SINGULAR% items.', 'wpcf' )
			),
			array(
				'type'   => 'link',
				'class'  => 'button',
				'target' => '%POST-CREATE-CONTENT-TEMPLATE%',
				'label'  => __( 'Create template', 'wpcf' )
			),
			array(
				'type' => 'paragraph',
				'content'  => '%POST-CONTENT-TEMPLATE-CONDITIONS-LIST%'
			),
			array(
				'type' => 'paragraph',
				'content' => '%POST-CUSTOM-ERRORS-ELEMENTS-LIST%',
			),
		),

	),
);
