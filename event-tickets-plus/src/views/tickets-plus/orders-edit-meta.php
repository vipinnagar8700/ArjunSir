<?php
/**
 * Renders the meta fields for order editing
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets-plus/orders-edit-meta.php
 *
 * @since   4.4.3
 * @since   4.10.2 Set global for whether or not a ticket has any meta fields to show.
 * @since   4.10.7 Rearranged some variables.
 * @since   4.11.2 Use customizable ticket name functions.
 *
 * @version 4.11.2
 */

global $tribe_my_tickets_have_meta;

/**
 * @see \Tribe__Tickets__Tickets::get_attendee() Each ticket provider implements this method.
 * @var array $attendee
 */
$ticket = get_post( $attendee['product_id'] );

if ( ! $ticket instanceof WP_Post ) {
	?>
	<p>
		<?php
		echo esc_html(
			sprintf(
				__( '%s deleted: attendee info cannot be updated.', 'event-tickets-plus' ),
				tribe_get_ticket_label_singular( 'orders_edit_meta' )
			)
		);
		?>
	</p>
	<?php

	return;
}

/** @var Tribe__Tickets_Plus__Main $tickets_plus_main */
$tickets_plus_main = tribe( 'tickets-plus.main' );

if ( $tickets_plus_main->meta()->meta_enabled( $ticket->ID ) ) {
	$tribe_my_tickets_have_meta = true;
	?>
	<div class="tribe-event-tickets-plus-meta" id="tribe-event-tickets-plus-meta-<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
		<a class="attendee-meta toggle show"><?php esc_html_e( 'Toggle attendee info', 'event-tickets-plus' ); ?></a>
		<div class="attendee-meta-row">
			<?php
			$meta_fields = $tickets_plus_main->meta()->get_meta_fields_by_ticket( $ticket->ID );
			foreach ( $meta_fields as $field ) {
				echo $field->render( $attendee['attendee_id'] );
			}
			?>
		</div>
	</div>
<?php }