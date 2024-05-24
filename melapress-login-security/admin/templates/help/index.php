<?php
/**
 * Help content.
 *
 * @package WordPress
 * @subpackage wpassword
 */

?>

<div class="wrap help-wrap">
	<div class="page-head">
		<h2><?php esc_html_e( 'Help', 'ppm-wp' ); ?></h2>
	</div>
	<div class="nav-tab-wrapper">
		<?php
			// Get current tab.
			$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'help'; // phpcs:ignore.
		?>
		<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab<?php echo 'help' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'ppm-wp' ); ?></a>
		<?php
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'system-info' ) ); ?>" class="nav-tab<?php echo 'system-info' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Info', 'ppm-wp' ); ?></a>
	</div>
	<div class="ppm-help-section nav-tabs">
		<?php
			// Require page content. Default help.php.
			require_once $current_tab . '.php';
		?>
	</div>
</div>
