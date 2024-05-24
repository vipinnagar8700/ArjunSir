<?php
unset( $settings['ticket-paypal-et-plus-header'] );

$template_options = [
	'default' => esc_html__( 'Default Page Template', 'event-tickets-plus' ),
];

if ( class_exists( 'Tribe__Events__Main' ) ) {
	$template_options['same']  = esc_html__( 'Same as Event Page Template', 'event-tickets-plus' );
}

$templates = get_page_templates();

ksort( $templates );

foreach ( array_keys( $templates ) as $template ) {
	$template_options[ $templates[ $template ] ] = $template;
}

$page_options = [ '' => esc_html__( 'Choose a page or leave blank.', 'event-tickets-plus' ) ];

$pages = get_pages();

if ( $pages ) {
	foreach ( $pages as $page ) {
		$page_options[ $page->ID ] = $page->post_title;
	}
} else {
	//if no pages, let the user know they need one
	$page_options = [ '' => esc_html__( 'You must create a page before using this functionality', 'event-tickets-plus' ) ];
}

$ar_page_description = __( 'Optional: select an existing page to act as your attendee registration page. <strong>Requires</strong> use of the `[tribe_attendee_registration]` shortcode and overrides the above template and URL slug.', 'event-tickets-plus' );

$ar_page = tribe( 'tickets.attendee_registration' )->get_attendee_registration_page();

// this is hooked too early for has_shortcode() to work properly, so regex to the rescue!
if ( ! empty( $ar_page ) && ! preg_match( '/\[tribe_attendee_registration\/?\]/', $ar_page->post_content ) ) {
	$ar_slug_description = __( 'Selected page <strong>must</strong> use the `[tribe_attendee_registration]` shortcode. While the shortcode is missing the default redirect will be used.', 'event-tickets-plus' );
}

$modal_version_check = ! tribe_installed_before( 'Tribe__Tickets__Main', '4.11.0' );

$attendee_options = [
	'ticket-attendee-heading'       => [
		'type' => 'html',
		'html' => '<h3>' . __( 'Attendee Registration', 'event-tickets-plus' ) . '</h3>',
	],
	'ticket-attendee-modal'         => [
		'type'            => 'checkbox_bool',
		'label'           => esc_html__( 'Attendee Registration Modal ', 'event-tickets-plus' ),
		'tooltip' => sprintf(
			esc_html_x(
				'Enabling the Attendee Registration Modal provides a new sales flow for purchasing %1$s that include Attendee Registration. [%2$sLearn more%3$s]',
				'checkbox to enable Attendee Registration Modal',
				'event-tickets-plus'
			),
			esc_html( tribe_get_ticket_label_plural_lowercase( 'modal_notice_tooltip' ) ),
			'<a href="http://m.tri.be/attendee-registration" target="_blank">',
			'</a>'
		),
		'size'            => 'medium',
		'default'         => $modal_version_check,
		'validation_type' => 'boolean',
		'attributes'      => [ 'id' => 'ticket-attendee-enable-modal' ],
	],
	'ticket-attendee-info-slug'     => [
		'type'                => 'text',
		'label'               => esc_html__( 'Attendee Registration URL slug', 'event-tickets-plus' ),
		'tooltip'             => esc_html__( 'The slug used for building the URL for the Attendee Registration Info page.', 'event-tickets-plus' ),
		'size'                => 'medium',
		'default'             => tribe( 'tickets.attendee_registration' )->get_slug(),
		'validation_callback' => 'is_string',
		'validation_type'     => 'slug',
	],
	'ticket-attendee-info-template' => [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Attendee Registration template', 'event-tickets-plus' ),
		'tooltip'         => esc_html__( 'Choose a page template to control the appearance of your attendee registration page.', 'event-tickets-plus' ),
		'validation_type' => 'options',
		'size'            => 'large',
		'default'         => 'default',
		'options'         => $template_options,
	],
	'ticket-attendee-page-id'       => [
		'type'            => 'dropdown',
		'label'           => esc_html__( 'Attendee Registration page', 'event-tickets-plus' ),
		'tooltip'         => $ar_page_description,
		'validation_type' => 'options',
		'size'            => 'large',
		'default'         => 'default',
		'options'         => $page_options,
	],
];

$settings = Tribe__Main::array_insert_after_key( 'ticket-authentication-requirements', $settings, $attendee_options );

return $settings;
