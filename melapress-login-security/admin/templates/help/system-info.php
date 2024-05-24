<?php
/**
 * System info
 *
 * @package WordPress
 * @subpackage wpassword
 */

// Plugin adverts sidebar.
require_once 'sidebar.php';
?>
<div class="ppm-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'System information', 'ppm-wp' ); ?></h2>
	</div>
	<?php $ppm = ppm_wp(); ?>
	<form method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wsal-sysinfo"><?php echo esc_html( $ppm->get_sysinfo() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="ppmwp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'ppmwp-download-sysinfo', false ); ?>
		</p>
	</form>
</div>
