<?php

// Google Analytics
// ?utm_source=typesplugin&utm_campaign=types&utm_medium=%CURRENT-SCREEN%&utm_term=EMPTY&utm_content=EMPTY

$urls = array(
	'learn-how-template' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	'learn-how-archive' => 'https://toolset.com/course-lesson/creating-a-custom-archive-page/',
	'learn-how-views' => 'https://toolset.com/course-lesson/creating-a-view/',
	'learn-how-forms' => 'https://toolset.com/home/cred/',
	'learn-how-post-types' => 'https://toolset.com/course-lesson/creating-a-custom-post-type/',
	'learn-how-fields' => 'https://toolset.com/course-lesson/creating-custom-fields/',
	'learn-how-taxonomies' => 'https://toolset.com/course-lesson/creating-a-custom-taxonomy/',
	'creating-templates-with-toolset' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	'creating-templates-with-php' => 'https://toolset.com/documentation/customizing-sites-using-php/creating-templates-single-custom-posts/',
	// TODELETE nNot used anywhere as on Types 3.4
	'creating-archives-with-toolset' => 'https://toolset.com/course-lesson/creating-a-custom-archive-page/',
	// TODELETE nNot used anywhere as on Types 3.4
	'creating-archives-with-php' => 'https://toolset.com/documentation/customizing-sites-using-php/creating-templates-custom-post-type-archives/',
	'how-views-work' => 'https://toolset.com/course-lesson/creating-a-view/',
	// TODELETE nNot used anywhere as on Types 3.4
	'how-to-add-views-to-layouts' => 'https://toolset.com/documentation/getting-started-with-toolset/adding-lists-of-contents/',
	'learn-views' => 'https://toolset.com/course-lesson/creating-a-view/',
	'how-cred-work' => 'https://toolset.com/course-lesson/front-end-forms-for-adding-content/',
	// TODELETE nNot used anywhere as on Types 3.4
	'how-to-add-forms-to-layouts' => 'https://toolset.com/documentation/getting-started-with-toolset/publish-content-from-the-front-end/forms-for-creating-content/',
	// TODELETE nNot used anywhere as on Types 3.4
	'learn-cred' => 'https://toolset.com/course-lesson/front-end-forms-for-adding-content/',
	'adding-custom-fields-with-php' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	// TODELETE nNot used anywhere as on Types 3.4
	'themes-compatible-with-layouts' => 'https://toolset.com/documentation/user-guides/layouts-theme-integration/',
	// TODELETE nNot used anywhere as on Types 3.4
	'layouts-integration-instructions' => 'https://toolset.com/documentation/user-guides/layouts-theme-integration/',
	// TODELETE nNot used anywhere as on Types 3.4
	'adding-views-to-layouts' => 'https://toolset.com/documentation/getting-started-with-toolset/create-and-display-custom-lists-of-content/adding-views-to-designs-with-toolset-layouts/',
	'adding-forms-to-layouts' => 'https://toolset.com/course-lesson/front-end-forms-for-adding-content/',
	'using-post-fields' => 'https://toolset.com/course-lesson/creating-custom-fields/',
	'adding-fields' => 'https://toolset.com/course-lesson/creating-custom-fields/',
	'displaying-fields' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	'adding-user-fields' => 'https://toolset.com/documentation/',
	'displaying-user-fields' => 'https://toolset.com/documentation/user-guides/views/displaying-wordpress-user-fields/',
	'adding-term-fields' => 'https://toolset.com/documentation/user-guides/views/term-fields/',
	'displaying-term-fields' => 'https://toolset.com/documentation/user-guides/views/displaying-wordpress-term-fields/',
	'custom-post-types' => 'https://toolset.com/course-lesson/creating-a-custom-post-type/',
	'custom-taxonomy' => 'https://toolset.com/course-lesson/creating-a-custom-taxonomy/',
	'post-relationship' => 'https://toolset.com/course-lesson/many-to-many-post-relationships/',
	'compare-toolset-php' => 'https://toolset.com/landing/toolset-vs-php/',
	'types-fields-api' => 'https://toolset.com/documentation/customizing-sites-using-php/functions/',
	'parent-child' => 'https://toolset.com/course-lesson/many-to-many-post-relationships/',
	'custom-post-archives' => 'https://toolset.com/course-lesson/creating-a-custom-archive-page/',
	'using-taxonomy' => 'https://toolset.com/course-lesson/creating-a-custom-taxonomy/',
	'custom-taxonomy-archives' => 'https://toolset.com/course-lesson/creating-a-custom-taxonomy/',
	'repeating-fields-group' => 'https://toolset.com/course-lesson/creating-and-displaying-repeatable-field-groups/',
	'single-pages' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	'content-templates' => 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/',
	'views-user-guide' => 'https://toolset.com/course-lesson/creating-a-view/',
	'wp-types' => 'https://toolset.com/',
	'date-filters' => 'https://toolset.com/documentation/user-guides/views/date-filters/',
	'getting-started-types' => 'https://toolset.com/course-chapter/setting-up-custom-post-types-fields-and-taxonomy-directory/',
	'displaying-post-reference-fields' => 'https://toolset.com/course-lesson/displaying-related-posts/',
	'displaying-repeating-fields-groups' => 'https://toolset.com/documentation/getting-started-with-toolset/creating-and-displaying-repeatable-field-groups/',
	'toolset-account-downloads' => 'https://toolset.com/account/downloads/',
	'rest-api-integration' => 'https://toolset.com/documentation/programmer-reference/toolset-integration-with-the-rest-api/',
);

// Visual Composer
if ( defined( 'WPB_VC_VERSION' ) ) {
	$urls['learn-how-template'] = 'https://toolset.com/course-lesson/using-toolset-with-wpbakery-page-builder/';
	$urls['creating-templates-with-toolset'] = 'https://toolset.com/course-lesson/using-toolset-with-wpbakery-page-builder/';
} // Beaver Builder
elseif ( class_exists( 'FLBuilderLoader' ) ) {
	$urls['learn-how-template'] = 'https://toolset.com/course-lesson/using-toolset-with-beaver-builder/';
	$urls['creating-templates-with-toolset'] = 'https://toolset.com/course-lesson/using-toolset-with-beaver-builder/';
} // Layouts
elseif ( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) ) {
	$urls['learn-how-template'] = 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/';
	$urls['creating-templates-with-toolset'] = 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/';
}

return $urls;
