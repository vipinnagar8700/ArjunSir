<tr class="event-wide-settings">
	<td colspan="2">
		<table class="eventtable ticket_list eventForm">
			<tr>
				<td>
					<label for="tribe-tickets-hide-attendees-list">
						<?php esc_html_e( 'Display attendees list', 'event-tickets-plus' ); ?>
					</label>
				</td>
				<td>
					<input type="checkbox" name="tribe-tickets-hide-attendees-list" id="tribe-tickets-hide-attendees-list" value="1" <?php checked( ! $is_attendees_list_hidden ); // inverted purposefully for backwards-compat @since 4.5.1 ?> />
				</td>
			</tr>
		</table>
	</td>
</tr>
