<?php
/**
 * Handles policies admin area.
 *
 * @package WordPress
 * @subpackage wpassword
 */

$sidebar_required    = false;
/* @free:start */
// Override in free edition.
$sidebar_required    = true;
/* @free:end */
$form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php esc_attr_e( $form_class ); ?>">
		<div class="ppm-settings">

			<!-- getting started -->
			<div class="page-head">
				<h2><?php esc_html_e( 'Plugin settings', 'ppm-wp' ); ?></h2>
			</div>

			<?php
				$tab_links = apply_filters( 'ppmwp_settings_page_nav_tabs', '' );

			if ( ! empty( $tab_links ) ) {
				?>
					<div class="nav-tab-wrapper">
						<a href="#general-settings" class="nav-tab nav-tab-active" data-tab-target=".ppm-general-settings">General settings</a>
						<?php echo wp_kses( $tab_links, $this->allowed_kses_args() ); ?>
					</div>
				<?php
			}
			?>

			<div class="settings-tab ppm-general-settings">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Weekly Summary', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Send me a weekly summary of newly inactive and blocked users, and those whom have reset their password in the last week.', 'ppm-wp' ); ?>
										</span>
									</legend>
									<label for="ppm-send-summary-email">
										<input name="_ppm_options[send_summary_email]" type="checkbox" id="ppm-send-summary-email"
												value="yes" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->ppm_setting->send_summary_email ) ); ?>/>
												<?php esc_html_e( 'Enable weekly summary emails.', 'ppm-wp' ); ?>
												<p class="description">
													<?php esc_html_e( 'Send me a weekly summary of newly inactive and blocked users, and those whom have reset their password in the last week. Uses from/default address set below.', 'ppm-wp' ); ?>
												</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th>
								<?php esc_html_e( 'Users exempted from password policies', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<input type="text" id="ppm-exempted" style="float: left; display: block; width: 250px;">
									<input type="hidden" id="ppm-exempted-users" name="_ppm_options[exempted][users]" value="<?php echo ! empty( $this->options->ppm_setting->exempted['users'] ) ? esc_attr( htmlentities( wp_json_encode( $this->options->ppm_setting->exempted['users'] ), ENT_QUOTES, 'UTF-8' ) ) : ''; ?>">
									<p class="description" style="clear:both;">
										<?php
										esc_html_e( 'Users in this list will be exempted from all the policies.', 'ppm-wp' );
										?>
									</p>
									<ul id="ppm-exempted-list">
										<?php
										if ( is_array( $this->options->ppm_setting->exempted['users'] ) ) {
											foreach ( $this->options->ppm_setting->exempted['users'] as $user_id ) {
												$user = get_userdata( $user_id );
												if ( $user ) :
													?>
													<li class="ppm-exempted-list-item ppm-exempted-users user-btn button button-secondary" data-id="<?php echo esc_attr( $user_id ); ?>">
														<?php echo esc_html( $user->user_login ); ?>
														<a href="#" class="remove remove-item"></a>
													</li>
													<?php
												endif;
											}
										}
										?>
									</ul>
								</fieldset>
							</td>
						</tr>

						<?php
						?>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Instantly terminate session on password expire or reset', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Instantly terminate session on password expire or reset', 'ppm-wp' ); ?>
										</span>
									</legend>
									<label for="ppm-terminate-session-password">
										<input name="_ppm_options[terminate_session_password]" type="checkbox" id="ppm-terminate-session-password"
											value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->ppm_setting->terminate_session_password ) ); ?>/>
											<?php esc_html_e( 'Terminate session on password expire', 'ppm-wp' ); ?>
										<p class="description">
											<?php esc_html_e( "By default when a user's password expires or is reset, their current session is not terminated, and they are asked to reset their password once they log out and log back in. Enable this option to instantly terminate the users' sessions once the password expires or is reset.", 'ppm-wp' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Reset key expiry time', 'ppm-wp' ); ?>
							</th>
							<td>

								<?php
								ob_start();
								$units = array(
									'days'  => __( 'days', 'ppm-wp' ),
									'hours' => __( 'hours', 'ppm-wp' ),
								);
								?>
								<input type="number" id="ppm-reset-key-expiry-value" name="_ppm_options[password_reset_key_expiry][value]"
											value="<?php echo esc_attr( $this->options->ppm_setting->password_reset_key_expiry['value'] ); ?>" size="4" class="small-text ltr" min="1" required>
								<select id="ppm-reset-key-expiry-unit" name="_ppm_options[password_reset_key_expiry][unit]">
									<?php
									foreach ( $units as $key => $unit ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $this->options->ppm_setting->password_reset_key_expiry['unit'] ); ?>><?php echo esc_html( $unit ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$input_expiry = ob_get_clean();
								/* translators: %s: Configured password expiry period. */
								printf( esc_html__( 'Passwords reset keys should automatically expire in %s', 'ppm-wp' ), wp_kses( $input_expiry, $this->allowed_kses_args() ) );
								?>
								<p class="description">
									<?php esc_html_e( 'By default when a user requests a password reset, the reset key will expire with 24 hours. Use this option to control this expiry time.', 'ppm-wp' ); ?>
								</p>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Policy priority for users with multiple roles', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm-multiple-roles-preference">
										<input name="_ppm_options[users_have_multiple_roles]" type="checkbox" id="ppm-users-have-multiple-roles"
												value="yes" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->ppm_setting->users_have_multiple_roles ) ); ?>/>
										<?php esc_html_e( 'Configure the priority of each user role\'s password policies', 'ppm-wp' ); ?>
										<p class="description">
										<?php esc_html_e( 'By default our plugin will apply the policy based on the 1st role found for a user, if your users are able to have multiple roles the correct policies may not be applied. To control this, sort the roles below into order priority (the higher the role means policies for this role will override subsequent policies which may also be applicable to a user).', 'ppm-wp' ); ?>
										</p>
									</label>

									<?php
									$roles_obj  = wp_roles();
									$role_names = $roles_obj->get_names();

									$saved_order = ( isset( $this->options->ppm_setting->multiple_role_order ) && ! empty( $this->options->ppm_setting->multiple_role_order ) ) ? $this->options->ppm_setting->multiple_role_order : array();

									// Newly added roles.
									$new_roles = array_diff( array_values( $role_names ), $saved_order );
									if ( ! empty( $new_roles ) ) {
										$saved_order = $saved_order + $new_roles;
									}

									// Removed roles.
									$obselete_roles = array_diff( $saved_order, array_keys( $role_names ) );
									if ( ! empty( $obselete_roles ) ) {
										foreach ( $obselete_roles as $index => $role_to_remove ) {
											$key = array_search( $role_to_remove, $saved_order );
											if ( false !== $key ) {
												unset( $saved_order[ $key ] );
											}
										}
									}

									$roles_names_array = ( ! empty( $saved_order ) && is_array( $saved_order ) ) ? $saved_order : $roles_obj->get_names();
									$roles_list_items  = '';

									foreach ( $roles_names_array as $key => $label ) {
										$roles_list_items .= '<li class="ui-state-default" data-role-key="' . strtolower( str_replace( ' ', '_', $label ) ) . '"><span class="dashicons dashicons-leftright"></span>' . ucwords( str_replace( '_', ' ', $label ) ) . '</li>';
									}

									$value_string = implode( ',', $roles_names_array );
									?>

									<div id="sortable_roles_holder" class="disabled">
										<ul id="roles_sortable"> 
											<?php echo wp_kses( $roles_list_items, $this->allowed_kses_args() ); ?>
										</ul>

										<p class="description">
											<?php esc_html_e( 'Higher roles will superceed lower roles, meaning if a user has the role "subscriber" and also "author", to ensure "author" policies apply place it above "subscriber" to give these policies priority.', 'ppm-wp' ); ?>
										</p>
									</div>

									<input type="hidden" id="multiple-role-order" name="_ppm_options[multiple_role_order]" value='<?php echo esc_html( $value_string ); ?>' />
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'From email address', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<input type="text" name="_ppm_options[from_email]" value="<?php echo esc_attr( $this->options->ppm_setting->from_email ? $this->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) ) ); ?>" id="ppm-from-email" style="float: left; display: block; width: 250px;" />
									<p class="description" style="clear:both;max-width:570px">
										<?php esc_html_e( 'Specify the from email address the plugin should use to send emails. If you do not specify an email address, the pre-defined default will be used.', 'ppm-wp' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label>
									<?php esc_html_e( 'Email Test', 'ppm-wp' ); ?>
							</th>
							<td>
								<button type="button" class="button-secondary" id="ppm-wp-test-email"><?php esc_html_e( 'Send Test Email', 'ppm-wp' ); ?></button>
								<span id="ppm-wp-test-email-loading" class="spinner" style="float:none"></span>
								<p class="description" style="clear:both;max-width:570px">
									<?php
									esc_html_e(
										'The plugin uses emails to alert users that their password has expired.
									Use the test button below to send a test email to my email address and confirm email functionality.',
										'ppm-wp'
									);
									?>
								</p>
							</td>
						</tr>

						<tr valign="top" style="border: 1px solid red;">
							<th scope="row" style="padding-left: 15px;">
								<?php esc_html_e( 'Delete database data upon uninstall', 'ppm-wp' ); ?>
							</th>
							<td style="padding-right: 15px;">
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Delete database data upon uninstall', 'ppm-wp' ); ?>
										</span>
									</legend>
									<label for="ppm-clear-history">
										<input name="_ppm_options[clear_history]" type="checkbox" id="ppm-clear-history"
											value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->ppm_setting->clear_history ) ); ?>/>
											<?php esc_html_e( 'Delete database data upon uninstall', 'ppm-wp' ); ?>
										<p class="description">
											<?php esc_html_e( 'Enable this setting to delete the plugin\'s data from the database upon uninstall.', 'ppm-wp' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

					</tbody>
				</table>
			</div>

			<?php
				$scripts_required = false;
				$additonal_tabs   = apply_filters( 'ppmwp_settings_page_content_tabs', '' );
				if ( ! empty( $additonal_tabs ) ) {
					$scripts_required = true;
					echo $additonal_tabs; // phpcs:ignore
				}
			?>

		</div>

		<?php wp_nonce_field( PPMWP_PREFIX . '_nonce_form', PPMWP_PREFIX . '_nonce' ); ?>
		
		<div class="submit">
			<input type="submit" name="_ppm_save" class="button-primary"
		value="<?php echo esc_attr( __( 'Save Changes', 'ppm-wp' ) ); ?>" />
		</div>
	</form>

	<?php
	/* @free:start */
	require_once PPM_WP_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

	?>
</div> 

<?php
if ( $scripts_required ) {
	?>
<script type="text/javascript">
	function showTab( ) {
		var activeTab = jQuery( '.nav-tab-wrapper .nav-tab-active' ).attr( 'data-tab-target' );
		jQuery( '.settings-tab' ).hide();
		jQuery('body').find( '' + activeTab + '' ).show();
	}

	// Needs improvement.
	if (window.location.href.indexOf( "#email-settings" ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#email-settings"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	if (window.location.href.indexOf( "#forms-and-placement-settings" ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#forms-and-placement-settings"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	if (window.location.href.indexOf( "#login-page-settings" ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#login-page-settings"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	if (window.location.href.indexOf( '#users-export' ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#users-export"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	if (window.location.href.indexOf( '#integrations' ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#integrations"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	if (window.location.href.indexOf( '#settings-export' ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#settings-export"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}

	jQuery( document ).ready( function( $ ) {
		showTab();	

		$( "body" ).on( 'click', 'a[data-tab-target]', function( event ) {
			$( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
			$(this).addClass( 'nav-tab-active' );
			showTab();
		});
	} );

</script>
	<?php
}
?>
