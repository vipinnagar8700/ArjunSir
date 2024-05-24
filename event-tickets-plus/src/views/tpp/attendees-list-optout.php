<?php
/**
 * Renders the PayPal tickets attendee list optout inputs.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tpp/attendees-list-optout.php
 *
 * @version 4.7
 *
 * @var \Tribe__Tickets__Ticket_Object $ticket
 */

/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.5.2
 *
 * @param bool $hide_attendee_list_optout Whether to hide attendees list opt-out.
 * @param int  $post_id                   The post ID this ticket belongs to.
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false, $post_id );

if ( $hide_attendee_list_optout ) {
	return;
}
?>
	<tr class="tribe-tickets-attendees-list-optout"
		<?php
		if ( $hide_attendee_list_optout ) :
			echo 'style="display:none;"';
		endif;
		?>
	>
		<td colspan="4">
			<?php
			if ( $hide_attendee_list_optout ) :
				?>
				<input
					name="tpp_optout[]"
					value="<?php echo esc_attr( $ticket->ID ); ?>"
					type="hidden"
				>
				<?php
			else :
				?>
				<input
					type="checkbox"
					name="tpp_optout[]"
					id="tribe-tickets-attendees-list-optout-tpp"
					value="<?php echo esc_attr( $ticket->ID ); ?>"
				>
				<label for="tribe-tickets-attendees-list-optout-tpp">
					<?php esc_html_e( "Don't list me on the public attendee list", 'event-tickets-plus' ); ?>
				</label>
				<?php
			endif;
			?>
		</td>
	</tr>
