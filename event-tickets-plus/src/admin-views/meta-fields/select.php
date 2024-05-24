<?php

/**
 * @see \Tribe__Tickets_Plus__Meta__Field__Select
 */

$options = '';

if ( $extra && ! empty( $extra['options'] ) ) {
	$options = implode( "\n", $extra['options'] );
}

?>
<div class="tribe-tickets-input tribe-tickets-input-textarea">
	<label for="tickets_attendee_info_field"><?php echo esc_html_x( 'Options (one per line)', 'Attendee information fields', 'event-tickets-plus' ); ?></label>
	<textarea name="tribe-tickets-input[<?php echo $field_id; ?>][extra][options]" class="ticket_field" value="" rows="5"><?php echo esc_textarea( $options ); ?></textarea>
</div>
