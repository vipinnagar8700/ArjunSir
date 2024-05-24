<?php
return array(
	/* Views missing */
	'views-missing' => array(
		'type' => 'views',

		'conditions'=> array(
			'Types_Helper_Condition_Views_Missing'
		),

		'description' => array(
			array(
				'type'   => 'dialog',
				'class'  => 'button',
				'label'  => __( 'Create View', 'wpcf' ),
				'dialog' => array(
					'id' => 'create-view',
					'description' => array(
						array(
							'type' => 'paragraph',
							'content' => __( 'To design views, you need to activate Toolset Views plugin.', 'wpcf' )
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
			)
		),

	),

	/* Views, views missing */
	'views-views-missing' => array(
		'type' => 'views',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Views_Missing',
		),

		'description' => array(
			array(
				'type'          => 'link',
				'class'         => 'button js-toolset-dashboard-create-view',
				'target'        => '%POST-CREATE-VIEW%',
				'post_type'     => '%POST-CREATE-VIEW-TYPE%',
				'redirect_url'  => '%POST-CREATE-VIEW-REDIRECT-URL%',
				'label'         => __( 'Create View', 'wpcf' ),
			),
		)
	),

	/* Views, views */
	'views-views' => array(
		'type' => 'views',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Missing',
			'Types_Helper_Condition_Views_Views_Exist',
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => '%POST-VIEWS-LIST%',
			),
			array(
				'type' => 'link',
				'class' => 'button js-toolset-dashboard-create-view',
				'target' => '%POST-CREATE-VIEW%',
				'post_type' => '%POST-CREATE-VIEW-TYPE%',
				'redirect_url' => '%POST-CREATE-VIEW-REDIRECT-URL%',
				'label' => __( 'Create View', 'wpcf' ),
			),
		)
	),

	/* Views Layouts, views missing */
	'views-layouts-views-missing' => array(
		'type' => 'views',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Views_Views_Missing'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => __(
					'Edit any layout and add a View cell to it, to display lists of %POST-LABEL-PLURAL%.', 'wpcf'
				)
			),
			array(
				'type'   => 'link',
				'external' => true,
				'target' => Types_Helper_Url::get_url( 'adding-views-to-layouts', 'table', false, 'gui' ),
				'label'  => __( 'Learn how', 'wpcf' )
			),
		)
	),

	/* Views Layouts, views */
	'views-layouts-views' => array(
		'type' => 'views',

		'conditions'=> array(
			'Types_Helper_Condition_Layouts_Active',
			'Types_Helper_Condition_Views_Views_Exist'
		),

		'description' => array(
			array(
				'type' => 'paragraph',
				'content' => '%POST-VIEWS-LIST%'
			),
			array(
				'type'   => 'link',
				'external' => true,
				'target' => Types_Helper_Url::get_url( 'adding-views-to-layouts', 'table', false, 'gui' ),
				'label'  => __( 'How to add Views to layouts', 'wpcf' )
			),
		)
	),
);
