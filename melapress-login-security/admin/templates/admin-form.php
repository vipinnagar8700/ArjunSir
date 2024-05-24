<?php
/**
 * Handles policies admin area.
 *
 * @package WordPress
 * @subpackage wpassword
 */

// Get wp all roles.
global $wp_roles;
$roles = $wp_roles->get_names();
// current tab.
$current_tab         = isset( $_REQUEST['role'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['role'] ) ) : '';
$master_switch_title = ! empty( $current_tab ) ? __( 'Inherit login security policies', 'ppm-wp' ) : __( 'Enable login security policies', 'ppm-wp' );
$sidebar_required    = false;
/* @free:start */
// Override in free edition.
$sidebar_required    = true;
/* @free:end */
$form_class = ( $sidebar_required ) ? 'sidebar-present' : 'sidebar-present';
?>
<div class="wrap ppm-wrap">

	<div class="page-head">
		<h2><?php esc_html_e( 'Login Security Policies', 'ppm-wp' ); ?></h2>
	</div>

	<form method="post" id="ppm-wp-settings" class="<?php echo esc_attr( $form_class ); ?>">
		<input type="hidden" id="ppm-exempted-role" value="<?php echo $current_tab ? esc_attr( $current_tab ) : ''; ?>" name="_ppm_options[ppm-user-role]">

		<div class="action mls-reset-all-wrapper">
			<?php
			if ( 0 === $this->get_global_reset_timestamp() ) {
				$reset_string = __( 'Reset All Passwords was never used', 'ppm-wp' );
			} else {
				$reset_string = __( 'Last reset on', 'ppm-wp' ) . ' ' . get_date_from_gmt( date( 'Y-m-d H:i:s', $this->get_global_reset_timestamp() ), get_site_option( 'date_format', get_option( 'date_format' ) ) . ' ' . get_site_option( 'time_format', get_option( 'time_format' ) ) ); // phpcs:ignore.
			}
			?>
			<div id="reset-container">
				<input id="_ppm_reset" type="submit"
					   name="_ppm_reset"
					   class="button-secondary"
					   value="<?php esc_attr_e( "Reset All Users' Passwords", 'ppm-wp' ); ?>"/>
				<p class="description"><?php echo esc_html( $reset_string ); ?></p>
			</div>
		</div>

		<p class="short-message"><?php esc_html_e( 'The password policies configured in the All tab apply to all roles. To override the default policies and configure policies for a specific role disable the option Inherit policies in the role\'s tab.', 'ppm-wp' ); ?></p>

		<div class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg( 'page', 'ppm_wp_settings', network_admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php echo empty( $current_tab ) && ! isset( $_REQUEST['tab'] ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Site-wide policies', 'ppm-wp' ); ?></a>
			<div id="ppmwp-role_tab_link_wrapper">
				<div id="ppmwp_links-inner-wrapper">
					<?php
					$title_active = isset( $roles[ $current_tab ] ) ? 'nav-tab-active' : '';
					?>
					<span class="nav-tab <?php esc_attr_e( $title_active ); ?> dummy"><span style="opacity: 0.2" class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'Role-based policies', 'ppm-wp' ); ?></span>
					<?php
					if ( isset( $roles[ $current_tab ] ) ) {
						$first_item = array(
							$current_tab => $roles[ $current_tab ],
						);
						unset( $roles[ $current_tab ] );

						$roles = $first_item + $roles;
					}

					foreach ( $roles as $key => $value ) {
						$url = add_query_arg(
							array(
								'page' => 'ppm_wp_settings',
								'role' => $key,
							),
							network_admin_url( 'admin.php' )
						);
						// Active tab.
						$active       = ( $current_tab === $key ) ? ' nav-tab-active' : '';
						$settings_tab = get_site_option( PPMWP_PREFIX . '_' . $key . '_options' );
						$icon         = empty( $settings_tab ) || 1 === $settings_tab['master_switch'] ? '<span style="opacity: 0.2" class="dashicons dashicons-admin-settings"></span> ' : '<span class="dashicons dashicons-admin-settings"></span> ';
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses( $icon . $value, $this->allowed_kses_args() ); ?></a>
						<?php
					}
					?>
				</div>
				<span class="dashicons dashicons-arrow-down"></span>
			</div>
		</div>
		<?php if ( ! isset( $_REQUEST['tab'] ) ) : ?>
		<div>
			<table class="form-table" data-id="<?php echo esc_attr( $current_tab ); ?>">
				<tbody>
				<?php if ( ! empty( $current_tab ) ) : ?>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Do not enforce password & login policies for this role', 'ppm-wp' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="ppm_enforce_password">
									<input type="checkbox" id="ppm_enforce_password" name="_ppm_options[enforce_password]"
										   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->enforce_password ) ); ?>>
								</label>
							</fieldset>
						</td>
					</tr>
					<?php endif; ?>
					<tr valign="top" class="master-switch">
						<th scope="row">
							<?php echo esc_html( $master_switch_title ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e( 'Password Length', 'ppm-wp' ); ?>
									</span>
								</legend>
								<label for="ppm-min-length">
									<?php
									if ( isset( $_GET['role'] ) ) {
										$master_key = $this->setting_tab->inherit_policies;
									} else {
										$master_key = $this->setting_tab->master_switch;
									}
									?>
									<input type="checkbox" id="ppm_master_switch" name="_ppm_options[master_switch]"
										   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $master_key ) ); ?>>
									<?php if ( isset( $_GET['role'] ) ) : ?>
									<input type="hidden" name="_ppm_options[inherit_policies]" value="<?php echo esc_attr( $this->setting_tab->inherit_policies ); ?>" id="inherit_policies">
									<?php endif; ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		<div class="clear">&nbsp;</div>

		<?php wp_nonce_field( PPMWP_PREFIX . '_nonce_form', PPMWP_PREFIX . '_nonce' ); ?>
		<div class="ppm-settings">
			<table class="form-table">
				<tbody>
					<?php require_once PPM_WP_PATH . 'admin/templates/form-table.php'; ?>
				</tbody>
			</table>
		</div>
		<?php
		// we DON'T want this submit button on the inactive users page.
		if ( ! isset( $_REQUEST['tab'] ) || ( isset( $_REQUEST['tab'] ) && 'inactive-users' !== $_REQUEST['tab'] ) ) {
			?>
			<p class="submit">
				<input type="submit" name="_ppm_save" class="button-primary"
					value="<?php echo esc_attr( __( 'Save Changes', 'ppm-wp' ) ); ?>" />
			</p>
			<?php
		}
		?>
	</form>

	<?php
	global $wp_roles;
	$roles = $wp_roles->get_names();
	?>

	<div class="mls-modal-main-wrapper" id="reset-all-modal">
		<div class="mls-modal-content">
			<div class="mls-modal-content-wrapper">
				<h3><?php esc_attr_e( 'Which users would you like to reset?', 'ppm-wp' ); ?></h3>
				<p class="description"><?php esc_attr_e( 'Here you can choose if you want to reset the passwords for ALL users or just a specific sub set of users based on your desired critera. Simply choose from the available options below and hit proceed when ready.', 'ppm-wp' ); ?></p>
				<br>

				<fieldset>
					<p class="description" style="display: inline;"><?php esc_attr_e( 'Choose user group: ', 'ppm-wp' ); ?></p>
					<span style="display: inline-table; margin-left: 10px">
						<input type="radio" id="reset-all" name="reset_type" value="reset-all" checked>
						<label for="reset-all" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset all users', 'ppm-wp' ); ?></label><br>

						<input type="radio" id="reset-role" name="reset_type" value="reset-role"" data-active-shows-setting=".reset-role-panel">
						<label for="reset-role" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset by role', 'ppm-wp' ); ?> </label><br>

						<div class="reset-role-panel hidden">
							<select id="reset-role-select">
								<?php
									foreach ( $roles as $key => $value ) {
										if ( 'subscriber' == strtolower( $value ) ) {
											echo '<option selected value="' . strtolower( $value ) . '">' . $value . '</option>';
										} else {

										echo '<option value="' . strtolower( $value ). '">' . $value . '</option>';
										}
									}
								?>
							</select>
							<br>
							<br>
						</div>

						<input type="radio" id="reset-users" name="reset_type" value="reset-users" data-active-shows-setting=".reset-users-panel">
						<label for="reset-users" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset specific users', 'ppm-wp' ); ?> </label><br>
						
						<div class="reset-users-panel hidden">
							<fieldset>
								<input type="text" id="ppm-exempted" style="float: left; display: block; width: 250px;">
								<input type="hidden" id="ppm-exempted-users" name="_ppm_options[exempted][users]" value="<?php echo ! empty( $this->options->ppm_setting->exempted['users'] ) ? esc_attr( htmlentities( wp_json_encode( $this->options->ppm_setting->exempted['users'] ), ENT_QUOTES, 'UTF-8' ) ) : ''; ?>">
								<p class="description" style="clear:both;">
									<?php
									esc_html_e( 'Users in this list will reset.', 'ppm-wp' );
									?>
								</p>
								<ul id="ppm-exempted-list"></ul>
							</fieldset>
						</div>

						<input type="radio" id="reset-csv" name="reset_type" value="reset-csv" data-active-shows-setting=".reset-users-file">
						<label for="reset-csv" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Upload CSV of User IDs (.csv or .txt only)', 'ppm-wp' ); ?> </label><br>
						
						<div class="reset-users-file hidden">
							<input type="file" id="users-reset-file" name="filename"><br>
						</div>
					</span>
				</fieldset>
								
				<br>
				<fieldset>
					<input type="checkbox" id="send_reset_email" name="send_email" value="send-email" checked>
					<label for="send_reset_email"><?php esc_attr_e( 'Send email to users when resetting.', 'ppm-wp' ); ?></label>
				</fieldset>

				<br>
				<fieldset>
					<input type="checkbox" id="include_reset_self" name="reset_self" value="reset-self">
					<label for="include_reset_self"><?php esc_attr_e( 'Include yourself in password reset', 'ppm-wp' ); ?></label>
				</fieldset>

				<br>
				<fieldset>
					<input type="checkbox" id="terminate_sessions_on_reset" name="reset_self" value="reset-self" checked>
					<label for="terminate_sessions_on_reset"><?php esc_attr_e( 'Terminate sessions for reset users', 'ppm-wp' ); ?></label>
				</fieldset>

				<br>	
			</div>
			<div>
				<a href="#modal-cancel" data-modal-close-target="#reset-all-modal" class="button button-secondary"><?php esc_attr_e( 'Cancel', 'ppm-wp' ); ?></a>  <a href="#modal-proceed" data-reset-nonce="<?php echo wp_create_nonce( 'mls_mass_reset' ); ?>" class="button button-primary"><?php esc_attr_e( 'Proceed', 'ppm-wp' ); ?></a> 
			</div>
		</div>
	</div>

	<?php
	/* @free:start */
	require_once PPM_WP_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

	?>
</div>
