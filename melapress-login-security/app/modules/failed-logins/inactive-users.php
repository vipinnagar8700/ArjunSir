<?php
/**
 * Page partial template that displays the inactive-users list view.
 *
 * @since  2.1.0
 *
 * @package WordPress
 */

// list table should be defined by here but just incase check first.
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once PPM_WP_PATH . 'app/modules/failed-logins/InactiveUsersTable.php';

$sidebar_required = false;

/* @free:start */

// Override in free edition.
$sidebar_required = true;
/* @free:end */
$form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
?>
<div id="inactive_users_page" class="<?php esc_attr_e( $form_class ); ?>">
	<?php
	// display the table view + message if the feature is enabled, otherwise
	// show an error message to tell user what is required to turn this on.
	$inactive_feature_enabled = \PPMWP\Helpers\OptionsHelper::should_inactive_users_feature_be_active();
	if ( ! class_exists( '\PPMWP\InactiveUsers' ) ) {
		?>
		<p>
			<?php
			printf(
				esc_html__( 'In this section you can see a list of locked users. Users can be locked if they have had too many failed login attempts. More information on the %1$s', 'ppm-wp' ),
				sprintf(
					'<a target="_blank" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Failed logins policy', 'ppm-wp' )
				)
			);
			?>
		</p>
		<form method="post">
			<?php
			$table = new \PPMWP\Views\Tables\InactiveUsersTable();
			$table->display();
			?>
		</form>
		<?php
	} else if ( $inactive_feature_enabled ) {
		?>
		<p>
			<?php
			printf(
				esc_html__( 'In this section you can see a list of locked users. Users can be locked if they have been inactive for a long time, or they have had too many failed login attempts. More information on the %1$s', 'ppm-wp' ),
				sprintf(
					'<a target="_blank" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Inactive users policy', 'ppm-wp' )
				)
			);
			?>
		</p>
		<form method="post">
			<?php
			$table = new \PPMWP\Views\Tables\InactiveUsersTable();
			$table->display();
			?>
		</form>
		<?php
	} else {
		?>
		<p>
			<?php
			printf(
				esc_html__( 'In this section you can see a list of inactive WordPress users on your website if you enable the %1$s, or the block failed logins policy', 'ppm-wp' ),
				sprintf(
					'<a target="_blank"  rel="nofollow" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Inactive users policy', 'ppm-wp' )
				)
			);
			?>
		</p>
		<?php
	}
	?>
</div>

<?php
	/* @free:start */
	require_once PPM_WP_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

?>
