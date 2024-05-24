<?php
/**
 * Policy settings table.
 *
 * @package WordPress
 * @subpackage wpassword
 */

?>
	<tr class="setting-heading" valign="top">
		<th scope="row">
			<h3><?php esc_html_e( 'Password policies', 'ppm-wp' ); ?></h3>
		</th>
	</tr>
	<tr valign="top">
		<th scope="row" style="vertical-align: middle;">
			<?php esc_html_e( 'Password Policies', 'ppm-wp' ); ?>
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
					ob_start();
					?>
					<input type="number" id="ppm-min-length" name="_ppm_options[min_length]"
						   value="<?php echo esc_attr( $this->setting_tab->min_length ); ?>" size="4" class="tiny-text ltr" min="1" required>
							<?php
							$input_length = ob_get_clean();
								 /* translators: %s: Configured miniumum password length. */
							printf( esc_html__( 'Passwords must be %s characters minimum.', 'ppm-wp' ), wp_kses( $input_length, $this->allowed_kses_args() ) );
							?>
				</label>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Mixed Case', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-mix-case">
					<input name="_ppm_options[ui_rules][mix_case]" type="checkbox" id="ppm-mix-case"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules['mix_case'] ) ); ?>/>
						   <?php esc_html_e( 'Password must contain at least one uppercase and one lowercase character.', 'ppm-wp' ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Numbers', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-numeric">
					<input name="_ppm_options[ui_rules][numeric]" type="checkbox" id="ppm-numeric"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules['numeric'] ) ); ?>/>
						   <?php
							printf(
								/* translators: 1 - example of numeral */
								esc_html__( 'Password must contain at least one numeric character (%1$s).', 'ppm-wp' ),
								'<code>0-9</code>'
							);
							?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">

		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Special Characters', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-special">
					<input name="_ppm_options[ui_rules][special_chars]" type="checkbox" id="ppm-special"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules['special_chars'] ) ); ?>/>
						<?php
						printf(
							/* translators: 1 - a list of special characters wrapped in a code block */
							esc_html__( 'Password must contain at least one special character (eg: %1$s).', 'ppm-wp' ),
							'<code>' . esc_html( ppm_wp()->get_special_chars() ) . '</code>'
						);
						?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">

		</th>
		<td class="col-indented">
			<fieldset>
				<input name="_ppm_options[ui_rules][exclude_special_chars]" type="checkbox" id="ppm-exclude-special"
					value="1" <?php ( isset( $this->setting_tab->ui_rules['exclude_special_chars'] ) ) ? checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules['exclude_special_chars'] ) ) : ''; ?>/>
				<label for="ppm-excluded-special-chars">
					<?php esc_html_e( 'Do not allow these special characters in passwords:', 'ppm-wp' ); ?>
				</label>
				<input
					type="text"
					name="_ppm_options[excluded_special_chars]"
					id="ppm-excluded-special-chars"
					class="small-input"
					value="<?php echo esc_attr( ( isset( $this->setting_tab->excluded_special_chars ) ) ? $this->setting_tab->excluded_special_chars : $this->options->default_setting['excluded_special_chars'] ); ?>"
					pattern="<?php echo esc_attr( ppm_wp()->get_special_chars( true) ); ?>*?"
					onkeypress="accept_only_special_chars_input( event )"
				/>
				<p class="description" style="clear:both;max-width:570px">
					<?php esc_html_e( 'To enter multiple special characters simply type them in one next to the other.', 'ppm-wp' ); ?>
				</p>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Password Expiration Policy', 'ppm-wp' ); ?>
		</th>
		<td>

			<?php
			ob_start();
			$test_mode = apply_filters( 'ppmwp_enable_testing_mode', false );
			$units     = array(
				'hours'  => __( 'hours', 'ppm-wp' ),
				'days'   => __( 'days', 'ppm-wp' ),
				'months' => __( 'months', 'ppm-wp' ),
			);
			if ( $test_mode ) {
				$units['seconds'] = __( 'seconds', 'ppm-wp' );
			}
			?>
			<input type="number" id="ppm-expiry-value" name="_ppm_options[password_expiry][value]"
				   value="<?php echo esc_attr( $this->setting_tab->password_expiry['value'] ); ?>" size="4" class="small-text ltr" min="0" required>
			<select id="ppm-expiry-unit" name="_ppm_options[password_expiry][unit]">
				<?php
				foreach ( $units as $key => $unit ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( $key ), $this->setting_tab->password_expiry['unit'], true ); ?>><?php echo esc_html( $unit ); ?></option>
					<?php
				}
				?>
			</select>
			<?php
			$input_expiry = ob_get_clean();
			/* translators: %s: Configured password expiry period. */
			printf( esc_html__( 'Passwords should automatically expire in %s', 'ppm-wp' ), wp_kses( $input_expiry, $this->allowed_kses_args() ) );
			?>
			<p class="description">
				<?php esc_html_e( 'Set to 0 to disable automatic expiration.', 'ppm-wp' ); ?>
			</p>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Disallow old passwords on reset', 'ppm-wp' ); ?>
		</th>
		<td>
			<fieldset>
				<label for="ppm-history">
					<?php
					ob_start();
					?>
					<input name="_ppm_options[password_history]" type="number" id="ppm-history"
						   value="<?php echo esc_attr( $this->setting_tab->password_history ); ?>" min="1" max="100" size="4" class="tiny-text ltr" required/>
						   <?php
							$input_history = ob_get_clean();
								 /* translators: %s: Configured number of old password to check for duplication. */
							printf( esc_html__( "Don't allow users to use the last %s passwords when they reset their password.", 'ppm-wp' ), wp_kses( $input_history, $this->allowed_kses_args() ) );
							?>
					<p class="description">
						<?php esc_html_e( 'You can configure the plugin to remember up to 100 previously used passwords that users cannot use. It will remember the last 1 password by default (minimum value: 1).', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Reset password on first login', 'ppm-wp' ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Delete database data upon uninstall', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-initial-password">
					<input name="_ppm_options[change_initial_password]" type="checkbox" id="ppm-initial-password"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->change_initial_password ) ); ?> />
						   <?php esc_html_e( 'Reset password on first login', 'ppm-wp' ); ?>
					<p class="description">
						<?php esc_html_e( 'Enable this setting to force new users to reset their password the first time they login.', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Disable sending of password reset links', 'ppm-wp' ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Disable sending of password reset links', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="disable-self-reset">
					<input name="_ppm_options[disable_self_reset]" type="checkbox" id="disable-self-reset" onclick="admin_lockout_check( event )" 
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->disable_self_reset ) ); ?> />
						   <?php esc_html_e( 'Do not send password reset links', 'ppm-wp' ); ?>
					<p class="description">
						<?php esc_html_e( 'By default users who forget their password can request a password reset link that is sent to their email address. Enable this setting to stop WordPress sending these links, so users have to contact the website administrator if they forgot their password and need to reset it.', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
			<div class="disabled-reset-message-wrapper disabled" style="margin-top: 30px;">
				<p class="description" style="margin-bottom: 10px; display: block;">
					<?php esc_html_e( 'Display the following message when a user requests a password reset.', 'ppm-wp' ); ?>
				</p>
				<textarea id="disable_self_reset_message" name="_ppm_options[disable_self_reset_message]" rows="2" cols="60"><?php echo esc_attr( $this->setting_tab->disable_self_reset_message ); ?></textarea>
			</div>
		</td>
	</tr>
	
	<?php
		$inactive_users_settings = apply_filters( 'ppm_settings_add_inactive_users_settings', '', $this->setting_tab );
		echo wp_kses( $inactive_users_settings, $this->allowed_kses_args() );
		
		$failed_login_settings = apply_filters( 'ppm_settings_add_failed_login_settings', '', $this->setting_tab );
		echo wp_kses( $failed_login_settings, $this->allowed_kses_args() );

		$timed_logins_settings = apply_filters( 'ppm_settings_timed_logins_settings', '', $this->setting_tab );
		echo wp_kses( $timed_logins_settings, $this->allowed_kses_args() );
		
		$additional = apply_filters( 'ppm_settings_additional_settings', '', $this->setting_tab );
		echo wp_kses( $additional, $this->allowed_kses_args() );
	?>
