<?php
$do_ticket_admin_link  = ! empty( $ticket->admin_link );
$do_ticket_report_link = ! empty( $ticket->report_link );
/**
 * Filter the display of eCommerce links for the ticket
 *
 * @since  4.6
 *
 * @param boolean true/false - show/hide
 */
 if ( apply_filters( 'tribe_events_tickets_edd_display_ecommerce_links', true ) && ( $do_ticket_admin_link || $do_ticket_report_link ) ) : ?>
	<div
		id="ecommerce"
		class="<?php $this->tr_class(); ?> input_block tribe-dependent"
		data-depends="#ticket_id"
		data-condition-is-numeric
	>
		<label class="ticket_form_label ticket_form_left"><?php esc_html_e( 'Ecommerce:', 'event-tickets-plus' ); ?></label>
		<div class="ticket_form_right">
			<?php if ( $do_ticket_admin_link ) : ?>
				<a href="<?php echo esc_url( $ticket->admin_link ); ?> "><?php esc_html_e( 'Edit ticket in Easy Digital Downloads', 'event-tickets-plus' ); ?></a>
			<?php endif; ?>
			<?php
			if ( $do_ticket_admin_link && $do_ticket_report_link ) {
				echo ' | '; // need the spacing
			}
			?>
			<?php if ( $do_ticket_report_link ) : ?>
				 <a href="<?php echo esc_url( $ticket->report_link ); ?> "><?php esc_html_e( 'View Sales Report', 'event-tickets-plus' ); ?></a>
			 <?php endif; ?>
		</div>
	</div>
	<?php
endif;
