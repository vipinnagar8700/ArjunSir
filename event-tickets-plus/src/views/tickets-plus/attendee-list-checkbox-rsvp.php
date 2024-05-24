<?php
/**
 * Renders the attendee list checkbox for rsvp's
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets-plus/attendee-list-checkbox-rsvp.php
 *
 * @since 4.12.0 Added filter to turn on/off the optout checkbox.
 *
 * @version 4.12.0
 *
 */
$view = Tribe__Tickets__Tickets_View::instance();

/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.5.2
 * @since 4.12.0 Added $post_id parameter.
 *
 * @param bool $hide_attendee_list_optout Whether to hide attendees list opt-out.
 * @param int  $post_id                   The post ID this ticket belongs to.
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false, $post_id );

if ( $hide_attendee_list_optout ) :
	?>
	<input name="attendee[<?php echo esc_attr( $first_attendee['order_id'] ); ?>][optout]" value="1" type="hidden" />
	<?php
	return;
endif;
?>
<div class="tribe-tickets-attendees-list-optout">
	<input <?php echo $view->get_restriction_attr( $post_id, esc_attr( $first_attendee['product_id'] ) ); ?> type="checkbox" name="attendee[<?php echo esc_attr( $first_attendee['order_id'] ); ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>" <?php checked( true, esc_attr( $first_attendee['optout'] ) ) ?>>
	<label for="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets-plus' ); ?></label>
</div>
