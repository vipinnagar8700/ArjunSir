<?php
/**
 * Contact us wrapper.
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
		<h2><?php esc_html_e( 'Contact Us', 'ppm-wp' ); ?></h2>
	</div>
	<style type="text/css">
		.fs-secure-notice {
			position: relative !important;
			top: 0 !important;
			left: 0 !important;
		}
		.fs-full-size-wrapper {
			margin: 10px 20px 0 2px !important;
		}
	</style>
	<?php
	$freemius_id = ppm_freemius()->get_id();
	$vars        = array( 'id' => $freemius_id );
	echo fs_get_template( 'contact.php', $vars ); // phpcs:ignore
	?>
</div>
