<?php
/**
 * Inactive Users List Table.
 *
 * @since 1.0.0
 *
 * @package wordpress
 */

?>
<div class="wrap ppm-wrap">
	<div class="page-head">
		<h2><?php esc_html_e( 'Locked Users', 'ppm-wp' ); ?></h2>
	</div>

	<?php include_once PPM_WP_PATH . 'app/modules/failed-logins/inactive-users.php'; ?>
</div>
