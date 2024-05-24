<?php
	use Tribe\Tickets\Plus\Meta\Field_Types_Collection;
?>
<div id="tribe-tickets-attendee-info-form" class="eventtable tribe-tickets-attendee-info-form ticket_advanced ticket_advanced_meta">
	<table class="eventtable">
		<tr class="tribe-attendee-fields-box">
			<td class='tribe-attendee-fields-new'>
				<h5><?php esc_html_e( 'Add New Field:', 'event-tickets-plus' ); ?></h5>
				<ul class="tribe-tickets-attendee-info-options">
					<?php
					$field_types = tribe( Field_Types_Collection::class )->get();

					foreach ( $field_types as $type => $name ) :
					?>
					<li id='tribe-tickets-add-<?php echo esc_attr( $type ); ?>' class='tribe-tickets-attendee-info-option'>
						<a href='#' class='add-attendee-field' data-type='<?php echo esc_attr( $type ); ?>'><?php echo esc_html( $name ); ?> <span class='dashicons dashicons-plus-alt'></span></a>
					</li>
					<?php endforeach; ?>
				</ul>
			</td>
			<td class='tribe-attendee-fields-existing'>
				<h5><?php esc_html_e( 'Active Fields:', 'event-tickets-plus' ); ?></h5>
				<div class="tribe-tickets-attendee-saved-fields">
					<div class="tribe-tickets-saved-fields-select">
						<p><?php esc_html_e( 'No active fields.', 'event-tickets-plus' ); ?> <span class="tribe-tickets-add-new-fields"><?php esc_html_e( 'Add new fields or', 'event-tickets-plus' ); ?></span></p>
						<select class="chosen ticket-attendee-info-dropdown" name="ticket-attendee-info[MetaID]" id="saved_ticket-attendee-info" title="Start with a saved fieldset..." >

							<option selected value="0"><?php esc_html_e( 'Start with a saved fieldset...', 'event-tickets-plus' ); ?></option>
								<?php foreach ( $templates as $template ) : ?>
									<option data-attendee-group="<?php echo esc_attr( $template->post_title ); ?>"
									        value="<?php echo esc_attr( $template->ID ); ?>"><?php echo esc_attr( $template->post_title ); ?></option>
								<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div id="tribe-tickets-attendee-sortables" class="meta-box-sortables ui-sortable">
					<?php
					foreach ( $active_meta as $meta ) {
						$field = $meta_object->generate_field( $ticket_id, $meta->type, (array) $meta );
						// outputs HTML input field - no escaping
						echo $field->render_admin_field();
					}
					?>
				</div>

				<p class="tribe_soft_note">
					<?php
					echo esc_html( sprintf(
						_x(
							'The name and contact info of the person acquiring %s is collected by default.',
							'Attendee Info',
							'event-tickets-plus'
						),
						tribe_get_ticket_label_plural_lowercase( 'attendee_info' )
					) );
					?>
				</p>

				<?php if ( empty( $fieldset_form ) ) : ?>
					<div class="tribe-tickets-input tribe-tickets-attendee-save-fieldset">
						<label>
							<input type="checkbox" name="tribe-tickets-save-fieldset" id="save_attendee_fieldset" value="on" class="ticket_field save_attendee_fieldset" data-tribe-toggle="tribe-tickets-attendee-saved-fieldset-name">
							<?php
							esc_html( sprintf(
								__( 'Save this fieldset for use on other %s?', 'event-tickets-plus' ),
								tribe_get_ticket_label_plural_lowercase( 'fieldset' )
							) );
							?>
						</label>
					</div>
					<div id="tribe-tickets-attendee-saved-fieldset-name" class="tribe-tickets-input tribe-tickets-attendee-saved-fieldset-name">
						<label for="tribe-tickets-saved-fieldset-name"><?php esc_html_e( 'Name this fieldset:', 'event-tickets-plus' ); ?></label>
						<input type="text" class="ticket_field" name="tribe-tickets-saved-fieldset-name" value="">
					</div>
				<?php endif; ?>

				<input type="hidden" name="tribe-tickets-input[0]" value="">
			</td>
		</tr>
	</table>
</div>
