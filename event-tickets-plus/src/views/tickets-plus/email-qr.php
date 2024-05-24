<?php
/**
 * QR code insert in tickets email
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets-plus/email-qr.php
 *
 * @version 4.7.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<table class="content" align="center" width="620" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="margin:15px auto 0; padding:0;">
	<tr>
		<td align="center" valign="top" class="wrapper" width="620">
			<table class="inner-wrapper" border="0" cellpadding="0" cellspacing="0" width="620" bgcolor="#f7f7f7" style="margin:0 auto !important; width:620px; padding:0;">
				<tr>
					<td valign="top" class="ticket-content" align="left" width="140" border="0" cellpadding="20" cellspacing="0" style="padding:20px; background:#f7f7f7;">
						<img src="<?php echo esc_url( $qr ); ?>" width="140" height="140" alt="QR Code Image" style="border:0; outline:none; height:auto; max-width:100%; display:block;"/>
					</td>
					<td valign="top" class="ticket-content" align="left" border="0" cellpadding="20" cellspacing="0" style="padding:20px; background:#f7f7f7;">
						<h3 style="color:#0a0a0e; margin:0 0 10px 0 !important; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-style:normal; font-weight:700; font-size:28px; letter-spacing:normal; text-align:left;line-height: 100%;">
							<span style="color:#0a0a0e !important"><?php esc_html_e( 'Check in for this event', 'event-tickets-plus' ); ?></span>
						</h3>
						<p>
							<?php esc_html_e( 'Scan this QR code at the event to check in.', 'event-tickets-plus' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>