<div id="field-<?php echo esc_attr( $field_id ); ?>" class="tribe-tickets-attendee-info-active-field meta-postbox closed">
	<div class="handlediv" title="Click to toggle"><br></div>
	<h3 class="hndle ui-sortable-handle"><span><?php echo esc_html( $type_name ); ?>:</span> <?php echo esc_attr( $label ); ?></h3>

	<div class="inside">
		<input type="hidden" class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id ); ?>][type]" value="<?php echo esc_attr( $type ); ?>">

		<div class="tribe-tickets-input tribe-tickets-input-text">
			<label for="tickets_attendee_info_field"><?php echo esc_html_x( 'Label:', 'Attendee information fields', 'event-tickets-plus' ); ?></label>
			<input type="text" class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id ); ?>][label]" value="<?php echo esc_attr( $label ); ?>">
		</div>

		##FIELD_EXTRA_DATA##

		<div class="tribe-tickets-input tribe-tickets-input-checkbox tribe-tickets-required">
			<label class="prompt"><input type="checkbox" <?php checked( $required, 'on' ); ?> class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id );?>][required]" value="on">
				<?php echo esc_html_x( 'Required?', 'Attendee information fields', 'event-tickets-plus' ); ?>
			</label>
		</div>
		<div class="tribe-tickets-delete-field">
			<a href="#" class="delete-attendee-field" ><?php echo esc_html_x( 'Delete this field', 'Attendee information fields', 'event-tickets-plus' ); ?></a>
		</div>
	</div>
</div>
