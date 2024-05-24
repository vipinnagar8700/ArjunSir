<?php
$post_id = get_the_ID();

// Get value from metadata
$show_attendees   = get_post_meta( $post_id, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, true );
// Sets the inverted value
$show_attendees   = ! empty( $show_attendees );

/**
 * Filters the default value for showing attendees on event page if no meta field saved
 *
 * @var boolean $show_attendees value of true|false
 */
if ( ! metadata_exists( 'post', $post_id, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY ) ) {
	$show_attendees = apply_filters( 'tribe_tickets_plus_default_show_attendees_value', $show_attendees );
}

// Add checkbox for attendee display
?>
<p>
	<label>
		<span class="tribe-strong-label"><?php esc_html_e( 'Show attendees list on event page', 'event-tickets-plus' ); ?></span>
		<input
			type="checkbox"
			id="tribe_show_attendees"
			name="tribe-tickets[settings][show_attendees]"
			class="tribe_show_attendees settings_field"
			value="1"
			<?php checked( $show_attendees ); ?>
		>
	</label>
	<p class="description"><?php esc_html_e( 'Show a list of attendees, filtered by those who opt out during purchase.', 'event-tickets-plus' ); ?></p>
</p>
