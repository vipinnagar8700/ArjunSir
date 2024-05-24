<?php
/**
 * Messaging about collecting Attendee Information (i.e. Meta) from ticket purchasers.
 *
 * @since 4.10
 *
 * @var Tribe__Tickets_Plus__Main $etp_main
 */
$etp_main = tribe( 'tickets-plus.main' );

$meta_object = $etp_main->meta(); ?>

<h4 class="accordion-label"><?php esc_html_e( 'Attendee Information:', 'event-tickets-plus' ); ?></h4>
<p class="tribe-intro">
	<?php
	echo esc_html( sprintf(
		_x(
			'Need to collect information from your %s buyers? Configure your own registration form with the options below.',
			'Attendee Information',
			'event-tickets-plus'
		),
		tribe_get_ticket_label_singular_lowercase( 'attendee_info' )
	) );
	?>
	<a href="<?php echo esc_url( 'https://theeventscalendar.com/knowledgebase/collecting-attendee-information/?utm_source=tec&utm_medium=eventticketsplusapp&utm_term=adminnotice&utm_campaign=evergreen&cid=tec_eventticketsplusapp_adminnotice_evergreen' ) ?>">
		<?php esc_html_e( 'Learn more', 'event-tickets-plus' ) ?>
	</a>.
</p>

<?php include $etp_main->plugin_path . 'src/admin-views/meta.php';