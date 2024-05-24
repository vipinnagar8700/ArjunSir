<?php
/**
 * PPM New User Register
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 */
if ( ! class_exists( '\PPMWP\PPM_Failed_Logins' ) ) {
	/**
	 * Declare PPM_Failed_Logins Class
	 */
	class PPM_Failed_Logins {

		/**
		 * Init hooks.
		 */
		public function init() {
			add_action( 'ppm_settings_add_failed_login_settings', array( $this, 'failed_login_settings_markup' ), 10, 2 );

			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			add_action( 'wp_login', array( $this, 'clear_failed_login_data' ), 10, 2 );
			// Count Learndash failed logins.
			add_filter( 'learndash_safe_redirect_location', array( $this, 'learndash_login_error_check' ), 10, 3 );
			// Add JS to Memberpress login page.
			add_action( 'mepr-login-form-before-submit', array( $this, 'memberpress_login_form_js' ), 10 );
			add_action( 'admin_init', array( $this, 'register_ajax' ) );
			add_action( 'ppmwp_enqueue_admin_scripts', array( $this, 'register_scripts' ) );
		}

		/**
		 * Add JS into memberpress login form to create front-end error messages in case of login failure
		 */
		public function memberpress_login_form_js() {
			?>
			<script>
				if ( window.location.href.indexOf('mls_errors') > 0 ) {
					setTimeout(() => {
						var errorString = window.location.href.split('errors=')[1];
						var errorArray = errorString.split(',');			

						var lockedLockedMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg" style="min-width: 48px;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>Your account has surpassed the allowed number of login attempts and can no longer log in.</li></ul></div>';		
			
						jQuery.each( errorArray, function ( index, value ) {
							if ( jQuery.trim( value ) == 'mls_login_locked' ) {
								jQuery( '.mepro-login-contents' ).prepend( lockedLockedMarkup );	
							}

							var left = jQuery.trim( value ).split( '=' );

							if ( jQuery.trim( value ).indexOf( 'attempts_remaining' ) >= 0 ) {
								var remainingMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg" style="min-width: 48px;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>You have ' + left[1] + ' attempts remaining.</li></ul></div>';	
								jQuery( '.mepro-login-contents' ).prepend( remainingMarkup );		
							}
						});
						
						var basicErrorMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>Your username or password was incorrect</li></ul></div>';	
			
						jQuery( '.mepro-login-contents' ).prepend( basicErrorMarkup );	

					}, 50);
				}
			</script>
			<?php
		}

		/**
		 * This function runs on Learndash's redirect function which they use to handle login failures.
		 * It passes the usernaem into our logic check so the failure can be counted
		 *
		 * @param  string $location - Current location.
		 * @param  string $status - Error status.
		 * @param  string $context - Error context.
		 * @return string $location - Current location, unmodified by us.
		 */
		public function learndash_login_error_check( $location, $status, $context ) {
			$found = strpos( $location, 'login=failed#login' );
			if ( $found !== false ) {
				$username = isset( $_POST['log'] ) ? $_POST['log'] : '';
				$this->failed_login_check( $username, 'learndash_login_failure_count' );
			}

			return $location;
		}

		/**
		 * Check login to determine if the user is currently blocked
		 *
		 * @param  mixed  $user         WP_User if the user is authenticated. WP_Error or null otherwise.
		 * @param  string $username     Username or email address.
		 * @param  string $password     ser password.
		 *
		 * @return null|WP_User|WP_Error
		 */
		public function pre_login_check( $user, $username, $password ) {

			// If WP has already created an error at this point, pass it back and bail.
			if ( is_wp_error( $user ) ) {
				return $user;
			}

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			$user_id = ( isset( $user->ID ) ) ? $user->ID : $this->get_user_id_from_login_name( $username );

			// If we still have nothing, stop here.
			if ( ! $user_id ) {
				return $user;
			}

			// Return if this user is exempt.
			if ( ppm_is_user_exempted( $user_id ) ) {
				return $user;
			}

			$userdata = get_user_by( 'id', $user_id );

			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) ) {

				if ( 'timed' === $role_options->failed_login_unlock_setting ) {

					$login_attempts_transient = $this->get_users_stored_transient_data( $user_id, true );
					$current_time             = current_time( 'timestamp' );

					// See if enough time has passed since last failed attempt.
					$time_difference = ( ! empty( $login_attempts_transient ) ) ? $current_time - $login_attempts_transient < $role_options->failed_login_reset_hours * 60 : false;

					// Enough time has passed and the user is allowed to reset.
					if ( ! $time_difference ) {
						$this->clear_failed_login_data( $userdata->user_login, $userdata );
					}
				}

				// Check if the user current user has been blocked from further login attemtps.
				$is_user_blocked = get_user_meta( $user_id, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, true );

				if ( 'yes' === $is_user_blocked ) {
					$user = new \WP_Error( 'ppmwp_login_attempts_blocked', __( 'Your account has surpassed the allowed number of login attempts and can no longer log in.', 'ppm-wp' ) );
				}
			}

			// We must return the user, regardless.
			return $user;
		}

		/**
		 * Logs failed attempt in a transient and determine if this failed attempt surpasses the threshold number of allowed attempts.
		 *
		 * @param  Array  $username Currently logging in user name.
		 * @param  Object $error    Current errors object.
		 *
		 * @return $error           Error object with our errors appended to it.
		 */
		public function failed_login_check( $username, $error = false ) {

			// If user is using an email, act accordingly.
			if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
				$userdata = get_user_by( 'email', $username );
			} else {
				$userdata = get_user_by( 'login', $username );
			}

			// If we still have nothing, stop here.
			if ( ! $userdata || ! $error ) {
				return;
			}

			// We dont want to count the error returned when we block the login ourselves.
			if ( isset( $error->errors['login_not_allowed'] ) ) {
				return;
			}

			// Return if this user is exempt.
			if ( ppm_is_user_exempted( $userdata->ID ) ) {
				return;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			// Check if user is already handled by our inactivity feature.
			if ( method_exists( 'OptionsHelper', 'is_user_inactive' ) ) {
				$is_user_inactive = OptionsHelper::is_user_inactive( $userdata->ID );
			} else {
				$is_user_inactive = false;
			}
			
			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) && ! $is_user_inactive ) {
				// Setup needed variables for later.
				$max_login_attempts            = $role_options->failed_login_attempts;
				$login_attempts_transient_name = PPMWP_PREFIX . '_user_' . $userdata->ID . '_failed_login_attempts';

				// Get the user ID by SQL query.
				$user_id = $this->get_user_id_from_login_name( $username );
				// Grab users currently stored attempts.
				$login_attempts_transient = $this->get_users_stored_transient_data( $userdata->ID, false );
				// Check if we have any failed login attempts stored for this user in a transient.
				$current_failed_login_attempts = ( ! empty( $login_attempts_transient ) ) ? $login_attempts_transient : array();
				// Add this failed attempts to what we have so far.
				array_push( $current_failed_login_attempts, current_time( 'timestamp' ) );
				// Save it, but only upto the number of max allowed attempts - we dont want this thing to bloat.
				$attempts_timer  = ( ! isset( $role_options->failed_login_reset_attempts ) ) ? 1440 : $role_options->failed_login_reset_attempts;
				$transient_timer = $attempts_timer * 60;
				set_transient( $login_attempts_transient_name, array_slice( $current_failed_login_attempts, -$max_login_attempts ), $transient_timer );

				// Now check if, including this most recent attempt, the user has surpassed the max number of allowed attempts.
				if ( count( $current_failed_login_attempts ) >= $max_login_attempts ) {
					// This user has exceed what we allow, so there outta here.
					update_user_meta( $userdata->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, 'yes' );
					update_user_meta( $userdata->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP, current_time( 'timestamp' ) );

					if ( is_wp_error( $error ) ) {
						if ( ! isset( $error->errors['ppmwp_login_attempts_blocked'] ) ) {
							$error_string = __( 'Your account has surpassed the allowed number of login attempts and can no longer log in.', 'ppm-wp' );
							$error->add( 'ppmwp_login_attempts_blocked', '<br>' . $error_string );
							if ( function_exists( 'wc_add_notice' ) ) {
								wc_add_notice( $error_string, 'notice' );
							}
						}
						// UM error handling.
						if ( class_exists( '\UM_Functions' ) ) {
							UM()->form()->add_error( 'ppmwp_login_attempts_blocked', $error_string );
						}

						// Mepr handling.
						if ( isset( $_POST['mepr_process_login_form'] ) ) {
							if ( class_exists( '\MeprUtils' ) ) {
								$mepr_options = \MeprOptions::fetch();
								$account_url  = $mepr_options->login_page_url();
								$delim        = \MeprAppCtrl::get_param_delimiter_char( $account_url );
								$url          = add_query_arg( 'mls_errors', 'mls_login_locked', $account_url . $delim );
								\MeprUtils::wp_redirect( $url );
							}
						}
					}
				}
				// This user has a number of attempts remaining, so lets let them know before they lock themselves out.
				else {
					$attempts_left = $max_login_attempts - count( $current_failed_login_attempts );
					$error_string  = sprintf(
						esc_html(
							/* translators: %d: Number of attempts remaining */
							_n(
								'You have %d attempt remaining.',
								'You have %d attempts remaining.',
								$attempts_left,
								'ppm-wp'
							)
						),
						$attempts_left
					);

					if ( is_wp_error( $error ) ) {
						$error->add( 'ppmwp_login_attempts_blocked', '<br>' . $error_string );
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $error_string, 'notice' );
						}
						// UM error handling.
						if ( class_exists( '\UM_Functions' ) ) {
							UM()->form()->add_error( 'ppmwp_login_attempts_blocked', $error_string );
						}

						if ( isset( $_POST['learndash-login-form'] ) && function_exists( 'learndash_validation_registration_form_redirect_to' ) ) {
							$redirect_to = learndash_validation_registration_form_redirect_to();
							if ( $redirect_to ) {
								$redirect_to = add_query_arg( 'login', 'failed', $redirect_to );
								$redirect_to = learndash_add_login_hash( $redirect_to );
								learndash_safe_redirect( $redirect_to );
							}
						}
						// Mepr handling.
						if ( isset( $_POST['mepr_process_login_form'] ) ) {

							if ( class_exists( '\MeprUtils' ) ) {
								$mepr_options = \MeprOptions::fetch();
								$account_url  = $mepr_options->login_page_url();
								$delim        = \MeprAppCtrl::get_param_delimiter_char( $account_url );
								$url          = add_query_arg( 'mls_errors', 'attempts_remaining=' . $attempts_left, $account_url . $delim );
								\MeprUtils::wp_redirect( $url );
							}

							return $errors;
							die;
						}
					}
				}

				return $error;
			}
		}

		/**
		 * Remove the "user blocked" usermeta and any currently held transients upon a succesful login.
		 *
		 * @param  string $username Currently logged in user.
		 * @param  object $user     Currently logged in user object.
		 */
		public function clear_failed_login_data( $username, $user ) {

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			if ( is_numeric( $username ) ) {
				$user_id = $username;
			} else {
				$user_id = ( isset( $user->ID ) ) ? $user->ID : $this->get_user_id_from_login_name( $username );
			}

			if ( $user_id ) {
				$login_attempts_transient_name = PPMWP_PREFIX . '_user_' . $user_id . '_failed_login_attempts';
				$delete_transient              = delete_transient( $login_attempts_transient_name );
				$unblock_user                  = delete_user_meta( $user_id, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY );
				$is_blocked_user               = delete_user_meta( $user_id, PPMWP_PREFIX . 'is_blocked_user' );

				// mark as recently unlocked.
				update_user_meta( $user_id, PPMWP_PREFIX . '_recently_unlocked', true );
				update_user_meta( $user_id, PPMWP_PREFIX . '_recently_unlocked_time', current_time( 'timestamp' ) );
				update_user_meta( $user_id, PPMWP_PREFIX . '_recently_unlocked_reason', 'blocked' );
			}
		}

		/**
		 * Small helper function to return all, or the most recently stored failed login attempts.
		 *
		 * @param  int     $user_id                  User id to lookup.
		 * @param  boolean $return_latest_entry_only Flag to determine if we only want the most recent attempt.
		 *
		 * @return array                             Stored failure attempts.
		 */
		public function get_users_stored_transient_data( $user_id, $return_latest_entry_only = false ) {
			$login_attempts_transient_name = PPMWP_PREFIX . '_user_' . $user_id . '_failed_login_attempts';
			$transient_data                = get_transient( $login_attempts_transient_name );
			$current_time                  = current_time( 'timestamp' );
			$current_time_minus_24_hours   = $current_time - 86400;

			// Remove any attempts older than 24 hours.
			if ( ! empty( $transient_data ) ) {
				foreach ( $transient_data as $key => $login_attempt_timestamp ) {
					if ( $login_attempt_timestamp < $current_time_minus_24_hours ) {
						unset( $transient_data[ $key ] );
					}
				}
			}

			if ( $return_latest_entry_only && ! empty( $transient_data ) ) {
				$transient_data = end( $transient_data );
			}

			return $transient_data;
		}

		/**
		 * Queries the usermeta table to retrieve a users ID. Leaner than using get_user_by as we dont need the whole user object.
		 *
		 * @param  string $username  Users login name.
		 *
		 * @return string            Users ID.
		 */
		public function get_user_id_from_login_name( $username ) {
			global $wpdb;
			$user_data = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login = %s", array( $username ) ) );

			if ( isset( $user_data[0] ) ) {
				$user_id = $user_data[0];
				$user_id = $user_id->ID;
				return $user_id;
			}
		}

		/**
		 * Retreive all IDs for users who are currently blocked.
		 *
		 * @return array Array of user IDs.
		 */
		public function get_all_currently_login_locked_users() {
			global $wpdb;

			$users = $wpdb->get_results(
				$wpdb->prepare(
					"
				SELECT ID FROM $wpdb->users
				INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
				WHERE $wpdb->usermeta.meta_key LIKE %s
				",
					array(
						'ppmwp_is_blocked_%',
					)
				)
			);
			$users = array_map(
				function ( $user ) {
					if ( ! ppm_is_user_exempted( $user->ID ) ) {
						  return (int) $user->ID;
					}
				},
				$users
			);
			$users = ( ! empty( $users ) ) ? $users : array();

			return $users;
		}

		/**
		 * Send user a notification email once the account has been unblocked, also reset password if required.
		 *
		 * @param int  $user_id        -User ID to notify.
		 * @param bool $reset_password - Is PW reset.
		 */
		public function send_logins_unblocked_notification_email_to_user( $user_id, $reset_password ) {

			// Access plugin instance.
			$ppm = ppm_wp();

			// Grab user data object.
			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login = $user_data->user_login;
			$user_email = $user_data->user_email;

			// Only reset the password if the role has this option enabled.
			if ( $reset_password ) {
				$key = get_password_reset_key( $user_data );
				if ( ! is_wp_error( $key ) ) {
					$update = update_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $key );
				}
			}

			// Prepare email details.
			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			$title = \PPMWP\PPM_EmailStrings::replace_email_strings( isset( $ppm->options->ppm_setting->user_unblocked_email_title ) ? $ppm->options->ppm_setting->user_unblocked_email_title : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unblocked_email_title' ), $user_id );

			if ( $reset_password ) {
				$login_page                = OptionsHelper::get_password_reset_page();
				$msg                       = isset( $ppm->options->ppm_setting->user_unblocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unblocked_email_reset_message : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unblocked_email_reset_message' );
				$args['reset_or_continue'] = $msg . ' ' . esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . "\n";
			} else {
				$msg                       = isset( $ppm->options->ppm_setting->user_unblocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unblocked_email_continue_message : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unblocked_email_continue_message' );
				$args['reset_or_continue'] = $msg . "\n";
			}

			$content       = isset( $ppm->options->ppm_setting->user_unblocked_email_body ) ? $ppm->options->ppm_setting->user_unblocked_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'user_unblocked' );
			$email_content = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, $args );

			// Only send the email if applicable.
			if ( isset( $ppm->options->ppm_setting->send_user_unblocked_email ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->send_user_unblocked_email ) ) {
				// Fire off the mail.
				wp_mail( $user_email, wp_specialchars_decode( $title ), $email_content, $headers );
			}
		}

		/**
		 * Add form markup to role policies.
		 *
		 * @param string $markup - Existing markup.
		 * @param object $settings_tab - Current tab.
		 * @return string - Markup.
		 */
		public function failed_login_settings_markup( $markup, $settings_tab ) {
			$ppm = ppm_wp();
			ob_start();
			?>
				<tr valign="top">
					<th scope="row">
						<?php esc_attr_e( 'Failed login policies', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input name="_ppm_options[failed_login_policies_enabled]" type="checkbox" id="ppm-failed-login-policies-enabled" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings_tab->failed_login_policies_enabled ) ); ?>>
							<?php esc_attr_e( 'Activate failed login policies', 'ppm-wp' ); ?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" class="ppmwp-login-block-options">
					<th scope="row">
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span>
									<?php esc_attr_e( 'Number of failed login attempts before locking a user:', 'ppm-wp' ); ?>
								</span>
							</legend>
							<label for="ppm-failed-login-attempts">
								<?php esc_attr_e( 'Number of failed login attempts before locking a user:', 'ppm-wp' ); ?>
								<input type="number" id="ppm-failed-login-attempts" name="_ppm_options[failed_login_attempts]"
											value="<?php echo esc_attr( $settings_tab->failed_login_attempts ); ?>" size="4" class="tiny-text ltr" min="1" required>
							</label>
							<br>
							<label for="ppm-failed-login-reset-attempts">
								<?php esc_attr_e( 'Time period required to reset the failed logins count:', 'ppm-wp' ); ?>
								<input style="width: 54px;" type="text" id="ppm-failed-login-reset-attempts" name="_ppm_options[failed_login_reset_attempts]"
											value="<?php echo esc_attr( $settings_tab->failed_login_reset_attempts ); ?>" size="6" class="tiny-text ltr" min="60" required>
											<?php esc_attr_e( ' minutes', 'ppm-wp' ); ?>
							</label>

							<p class="description">
								<?php esc_attr_e( 'Use this setting to specify how long for should the plugin keep a count of the failed logins. Once this time period passes, the failed logins count is reset to 0.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" class="ppmwp-login-block-options">
					<th scope="row">
					</th>
					<td>
						<fieldset>
							<p class="description" style="display: inline;"><?php esc_attr_e( 'When a user is locked: ', 'ppm-wp' ); ?></p>
							<span style="display: inline-table;">
								<input type="radio" id="unlock-by-admin" name="_ppm_options[failed_login_unlock_setting]" value="unlock-by-admin" <?php checked( $settings_tab->failed_login_unlock_setting, 'unlock-by-admin' ); ?>>
								<label for="unlock-by-admin"><?php esc_attr_e( 'it can be only unlocked by the administrator', 'ppm-wp' ); ?></label><br>
								<input type="radio" id="timed" name="_ppm_options[failed_login_unlock_setting]" value="timed" <?php checked( $settings_tab->failed_login_unlock_setting, 'timed' ); ?>>
								<label for="timed"><?php esc_attr_e( 'unlock it after', 'ppm-wp' ); ?> <input type="number" id="ppm-failed-login-reset-hours" name="_ppm_options[failed_login_reset_hours]" value="<?php echo esc_attr( $settings_tab->failed_login_reset_hours ); ?>" size="4" class="tiny-text ltr" min="5" required> <?php esc_attr_e( 'minutes', 'ppm-wp' ); ?></label>
							</span>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" class="ppmwp-login-block-options" id="ppmwp-reset-pw-on-login-unblock">
					<th scope="row">
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Enable Inactive User Password Reset Feature', 'ppm-wp' ); ?></span>
							</legend>
							<label for="ppm-failed-login-reset-on-unblock">
								<input name="_ppm_options[failed_login_reset_on_unblock]" type="checkbox" id="ppm-failed-login-reset-on-unblock" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings_tab->failed_login_reset_on_unblock ) ); ?>>
								<?php esc_html_e( 'Require blocked users to reset password on unblock.', 'ppm-wp' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'By default, when a previously blocked user has been unblocked by an administrator, they are required to reset their password upon logging in - leave this unchecked to disable this behaviour.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Add admin page.
		 *
		 * @return void
		 */
		public function add_locked_users_admin_menu() {
			// Add admin submenu page for settings.
			$locked_users_hook_submenu = add_submenu_page(
				PPMWP_MENU_SLUG,
				__( 'Locked Users', 'ppm-wp' ),
				__( 'Locked Users', 'ppm-wp' ),
				'manage_options',
				'ppm-locked-users',
				array(
					$this,
					'ppm_display_locked_users_page',
				),
				3
			);

			add_action( "load-$locked_users_hook_submenu", array( '\PPMWP\Admin\PPM_WP_Admin', 'admin_enqueue_scripts' ) );
		}

		/**
		 * Display settings page.
		 */
		public function ppm_display_locked_users_page() {
			?>
			<div class="wrap ppm-wrap">
				<div class="page-head">
					<h2><?php esc_html_e( 'Locked Users', 'ppm-wp' ); ?></h2>
				</div>

				<?php include_once PPM_WP_PATH . 'app/modules/failed-logins/inactive-users.php'; ?>
			</div>
			<?php
		}

		/**
		 * Register the inactive users ajax endpoints.
		 *
		 * @method register_ajax
		 * @since  2.1.0
		 */
		public function register_ajax() {
			$unlock_ajax = new \PPMWP\Ajax\UnlockInactiveUser( $this );
			$unlock_ajax->register();
		}

		/**
		 * Registers scripts used for handling inactive users features.
		 *
		 * NOTE: this class registers scripts but enqueue should happen later, this
		 * is to ensure that they are only there on pages that need them.
		 *
		 * @method register_scripts
		 * @since  2.1.0
		 */
		public function register_scripts() {
			// this script is only registered here so enqueue it at a later point.
			wp_register_script( 'ppmwp-inactive-users', PPM_WP_URL . 'app/modules/failed-logins/inactiveUsers.js', array( 'jquery' ), PPMWP_VERSION, true );
			wp_localize_script(
				'ppmwp-inactive-users',
				'inactiveUsersStrings',
				array(
					'resettingUser'   => esc_html__( 'Resetting...', 'ppm-wp' ),
					'resetDone'       => esc_html__( 'User Reset', 'ppm-wp' ),
					'noUsers'         => esc_html__( 'Currently there are no locked users.', 'ppm-wp' ),
					'buttonReloading' => esc_html__( 'Reloading...', 'ppm-wp' ),
				)
			);
		}
	}
}
