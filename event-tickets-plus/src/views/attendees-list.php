<?php
/**
 * Renders the attendee list for an event.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/attendees-list.php
 *
 * @version 4.11.2
 */
?>
<div class='tribe-attendees-list-container'>
	<h2 class="tribe-attendees-list-title"><?php esc_html_e( 'Who\'s Attending', 'event-tickets-plus' ) ?></h2>
	<p>
		<?php
		echo esc_html(
			sprintf(
				_n(
					'One person is attending %2$s',
					'%d people are attending %s',
					$attendees_total,
					'event-tickets-plus'
				),
				$attendees_total,
				get_the_title( $event->ID )
			)
		);
		?>
	</p>

	<ul class='tribe-attendees-list'>
		<?php foreach ( $attendees_list as $attendee_id => $avatar_html ) { ?>
			<li class='tribe-attendees-list-item'><?php echo $avatar_html; ?></li>
		<?php } ?>
	</ul>
</div>