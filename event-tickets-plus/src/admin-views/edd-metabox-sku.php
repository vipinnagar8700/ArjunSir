<?php
/**
 * Filter the display of SKU for the ticket
 *
 * @since  4.6
 *
 * @param boolean true/false - show/hide
 */
?>
<div
	class="<?php $this->tr_class(); ?> input_block tribe-dependent"
	data-depends="#Tribe__Tickets_Plus__Commerce__EDD__Main_radio"
	data-condition-is-checked
>
	<label for="ticket_edd_sku" class="ticket_form_label ticket_form_left"><?php esc_html_e( 'SKU:', 'event-tickets-plus' ); ?></label>
	<input
		type="text"
		id="ticket_edd_sku"
		name="ticket_sku"
		class="ticket_field sku_input ticket_form_right"
		size="14"
		value="<?php echo esc_attr( $sku ); ?>"
	/>
	<p class="description ticket_form_right">
		<?php
		echo esc_html( sprintf(
			_x(
				'A unique identifying code for each %s type you\'re selling',
				'EDD SKU',
				'event-tickets-plus'
			),
			tribe_get_ticket_label_singular_lowercase( 'sku' )
		) );
		?>
	</p>
</div>
<?php
