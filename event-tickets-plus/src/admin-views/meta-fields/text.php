<?php
/**
 * @see \Tribe__Tickets_Plus__Meta__Field__Text
 */

$multiline = isset( $extra['multiline'] ) ? $extra['multiline'] : '';
?>
<div class="tribe-tickets-input tribe-tickets-input-checkbox tribe-tickets-required">
	<label class="prompt">
		<input
			type="checkbox"
			name="tribe-tickets-input[<?php echo esc_attr( $field_id ); ?>][extra][multiline]"
			class="ticket_field"
			value="yes"
			<?php checked( 'yes', $multiline ); ?>
		>
		<?php echo esc_html_x( 'Multi-line text field?', 'Attendee information fields', 'event-tickets-plus' ); ?>
	</label>
</div>
