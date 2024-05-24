<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * PPM Email Settings
 *
 * @package    WordPress
 * @subpackage wpassword
 * @author     Melapress
 */

 namespace PPMWP;

 use \PPMWP\Helpers\OptionsHelper;
 use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( '\PPMWP\RestrictLogins' ) ) {

	/**
	 * Handles login restrictions.
	 */
	class RestrictLogins {

		/**
		 * Add settings markup
		 *
		 * @param  string $markup - Current HTML.
		 * @param  string $settings_tab - Current tabs.
		 * @return string $markup - Appended markup.
		 */
		public static function settings_markup( $markup, $settings_tab ) {
			$ppm          = ppm_wp();
			$wp_kses_args = array(
				'input'    => array(
					'type'      => array(),
					'id'        => array(),
					'name'      => array(),
					'value'     => array(),
					'size'      => array(),
					'class'     => array(),
					'min'       => array(),
					'max'       => array(),
					'required'  => array(),
					'checked'   => array(),
					'onkeydown' => array(),
				),
				'select'   => array(
					'id'    => array(),
					'name'  => array(),
					'class' => array(),
				),
				'option'   => array(
					'id'       => array(),
					'name'     => array(),
					'value'    => array(),
					'selected' => array(),
				),
				'tr'       => array(
					'valign' => array(),
					'class'  => array(),
					'id'     => array(),
				),
				'th'       => array(
					'scope' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'td'       => array(
					'class' => array(),
					'id'    => array(),
				),
				'fieldset' => array(
					'class' => array(),
					'id'    => array(),
				),
				'legend'   => array(
					'class' => array(),
					'id'    => array(),
				),
				'label'    => array(
					'for'   => array(),
					'class' => array(),
					'id'    => array(),
				),
				'p'        => array(
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'span'     => array(
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'li'       => array(
					'class'         => array(),
					'id'            => array(),
					'data-role-key' => array(),
				),
				'a'        => array(
					'class'           => array(),
					'id'              => array(),
					'style'           => array(),
					'data-tab-target' => array(),
					'href'            => array(),
				),
				'h3'       => array(
					'class' => array(),
				),
				'br'       => array(),
				'b'        => array(),
				'i'        => array(),
				'div'      => array(
					'style' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'table'    => array(
					'class' => array(),
					'id'    => array(),
				),
				'tbody'    => array(
					'class' => array(),
					'id'    => array(),
				),
				'textarea' => array(
					'class' => array(),
					'name'  => array(),
					'rows'  => array(),
					'cols'  => array(),
					'id'    => array(),
				),
			);
			ob_start(); ?>
		   
			<tr valign="top" class="timed-logins-tr">
				<th scope="row">
					<?php esc_html_e( 'Limit the IP addresses users can log in from', 'ppm-wp' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span>
								<?php esc_html_e( 'Activate IP addresses restrictions', 'ppm-wp' ); ?>
							</span>
						</legend>
						<label for="mls-restrict-login-ip">
							<input name="_ppm_options[restrict_login_ip]" type="checkbox" id="mls-restrict-login-ip" value="1" <?php checked( OptionsHelper::string_to_bool( $settings_tab->restrict_login_ip ) ); ?> />
								<?php esc_html_e( 'Activate IP addresses restrictions', 'ppm-wp' ); ?>		
						
								<br>   
								<br>    				
								<p class="description restrict-login-option">
									<?php esc_html_e( "Use the below setting to specify the number of different IP addresses a user can log in from. If a user does not have any recorded IP addresses, the plugin will start keeping a log of the different IP addresses the user logs in from. Logins will be limited to the first number of recorded IP addresses based on the below-configured limit. You can always remove or edit IP addresses from the users' profile page.", 'ppm-wp' ); ?>
								</p>
								<br>
								<div class="restrict-login-option">
									<?php
										ob_start();
									?>
									<input name="_ppm_options[restrict_login_ip_count]" type="number" value="<?php echo esc_attr( $settings_tab->restrict_login_ip_count ); ?>" min="1" max="10" size="4" class="tiny-text ltr" required/>
									<?php
										$input_history = ob_get_clean();
										/* translators: %s: Configured number of old password to check for duplication. */
										printf( esc_html__( 'Allow users to log in from %s different IP addresses.', 'ppm-wp' ), wp_kses( $input_history, $wp_kses_args ) );
									?>
								</div>

								<?php
									if ( empty( $settings_tab->restrict_login_message ) ) {
										$message = trim( self::get_default_restrict_login_message() );
									} else {
										$message = trim( $settings_tab->restrict_login_message ); 
									}
								?>

								<div class="restrict-login-option" style="margin-top: 30px;">
									<p class="description" style="margin-bottom: 10px; display: block;">
										<?php esc_html_e( 'Display the following message when a user attempts to login from an unauthorised IP.', 'ppm-wp' ); ?>
									</p>
									<textarea id="restrict_login_message" name="_ppm_options[restrict_login_message]" rows="2" cols="60"><?php echo wp_kses_post( $message ); ?></textarea>
								</div>
						</label>
						<br>                           						
					</fieldset>
				</td>
			</tr>

			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Get default message if none provided.
		 *
		 * @return string
		 */
		public static function get_default_restrict_login_message() {
			$admin_email = get_site_option( 'admin_email' );
			$email_link  = '<a href="mailto:' . sanitize_email( $admin_email ) . '">' . __( 'website administrator', 'ppm-wp' ) . '</a>';
			/* translators: %s: Admin email. */
			return sprintf( __( 'Your are unable to login from this IP. Please contact the %1s for further information.', 'ppm-wp' ), $email_link );
		}

		/**
		 * Handle reset of individual password.
		 *
		 * @param WP_User $user - user to reset.
		 * @return void
		 */
		public static function add_user_profile_field( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			$userdata     = get_user_by( 'id', $user->ID );
			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			if ( ! OptionsHelper::string_to_bool( $role_options->restrict_login_ip ) ) {
				return;
			}

			$ips   = get_user_meta( $user->ID, 'mls_login_ips', true );
			$value = ! empty( $ips ) ? implode( ', ', $ips ) : '';

			if ( current_user_can( 'manage_options' ) ) {
				?>
				<table class="form-table" role="presentation">
					<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="reset_password"><?php esc_html_e( 'User login IP address restrictions', 'ppm-wp' ); ?></label></th>
						<td>
							<label for="reset_password_on_next_login">
								<?php esc_html_e( 'Below is the list of the currently stored IP address(es) for this user. You can delete or edit any of the below IP addresses. Changes will be saed when you click the Update User button to save the user profile changes.', 'ppm-wp' ); ?>
								<?php wp_nonce_field( 'mls_update_users_ips', 'mls_user_ips_nonce' ); ?>
								<br>
								<br>

								<input type="text" name="mls_user_ips" value="<?php echo esc_attr( $value ); ?>" />
							</label>
							<br>
						</td>
						</tr>
					</tbody>
				</table>
				<script>
					jQuery( document ).ready( function() {
						mls_build_ip_list();

						jQuery( document ).on( 'click', '[data-mls-user-ip-item] [data-edit-ip]', function ( event ) {
							var currentValue = jQuery( this ).parent().attr( 'data-mls-user-ip-item' );
							event.preventDefault();
							let person = prompt( "Edit IP below or leave blank to delete this IP", currentValue );

							if (person != null) { 
								var currentInputValue = jQuery( '[name="mls_user_ips"]' ).val();
								var newtext = currentInputValue.replace( currentValue, person ).trim();

								var lastChar = newtext.slice(-1);
								if (lastChar == ',') {
									newtext = newtext.slice(0, -1);
								}
								newtext = newtext.replace(/^,/, '');

								jQuery( '[name="mls_user_ips"]' ).val( newtext.trim() );
								mls_build_ip_list();  
							}

						} );

						jQuery( document ).on( 'click', '[data-mls-user-ip-item] [data-remove-ip]', function ( event ) {
							event.preventDefault();
							var currentValue = jQuery( this ).parent().attr( 'data-mls-user-ip-item' );
							var currentInputValue = jQuery( '[name="mls_user_ips"]' ).val();
							var newtext = currentInputValue.replace( currentValue, '' ).trim();

							var lastChar = newtext.slice(-1);
							if (lastChar == ',') {
								newtext = newtext.slice(0, -1);
							}
							newtext = newtext.replace(/^,/, '');

							jQuery( '[name="mls_user_ips"]' ).val( newtext.trim() );
							mls_build_ip_list();  

						} );
					});

					function mls_build_ip_list() {
						jQuery( '#mls_user_ip_list' ).remove();
						var inputText = jQuery( '[name="mls_user_ips"]' ).val().trim();
						if ( inputText.length > 0 ) {
							var temp = inputText.split(", ");
							var str = '';
							jQuery.each(temp, function(i,v) {
								str += "<div data-mls-user-ip-item="+v+"><span data-edit-ip>"+v+"<span class='dashicons dashicons-edit'></span></span><span data-remove-ip><span class='dashicons dashicons-no'></span><span></div>";
							});
							var div = document.createElement('div');
							div.innerHTML = str.trim();
							jQuery( div ).attr( 'id', 'mls_user_ip_list' );

							jQuery('[name="mls_user_ips"]' ).after( div );
						}
					}
				</script>
				<style>
					[name="mls_user_ips"] {
						display: none;
					}
					div[data-mls-user-ip-item] {
						cursor: pointer;
						display: inline-block;
						border-width: 1px;
						border-style: solid;
						padding: 4px 2px 4px 8px;
						margin: 2px 0 0 2px;
						border-radius: 3px;
						background: #EFE;
						border-color: #5B5;
					}
					div[data-mls-user-ip-item] .dashicons-edit {
						color: #666;
						font-size: 18px;
						margin-left: 3px;
					}
					div[data-mls-user-ip-item] .dashicons-no {
						color: red;
    					font-size: 18px;
						position: relative;
						left: -3px;
					}
				</style>
				<?php
			}
		}

		/**
		 * Handles saving of user profile fields.
		 *
		 * @param  int $user_id - user ID.
		 * @return void
		 */
		public static function save_user_profile_field( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) || isset( $_POST['mls_user_ips_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mls_user_ips_nonce'] ) ), 'mls_update_users_ips' ) ) {
				return;
			}

			if ( isset( $_POST['mls_user_ips'] ) ) {
				$ips = empty( $_POST['mls_user_ips'] ) ? array() : explode( ',', sanitize_text_field( wp_unslash( $_POST['mls_user_ips'] ) ) );
				update_user_meta( $user_id, 'mls_login_ips', $ips );
			}
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
		public static function pre_login_check( $user, $username, $password ) {

			// If WP has already created an error at this point, pass it back and bail.
			if ( is_wp_error( $user ) ) {
				return $user;
			}

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			$failed_logins = new \PPMWP\PPM_Failed_Logins();
			$user_id       = ( isset( $user->ID ) ) ? $user->ID : $failed_logins->get_user_id_from_login_name( $username );

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

			if ( OptionsHelper::string_to_bool( $role_options->restrict_login_ip ) ) {
				$stored_ips   = self::get_user_stored_ips( $user_id );
				$user_addr    = isset( $_SERVER['REMOTE_ADDR'] ) ? \PPMWP\MLS_Login_Page_Control::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : false;
				$error_string = isset( $role_options->restrict_login_message ) && ! empty( $role_options->restrict_login_message ) ? $role_options->restrict_login_message : self::get_default_restrict_login_message();

				if ( ! $user_addr ) {
					return new \WP_Error(
						'login_not_allowed',
						$error_string
					);
				}

				// User has no IP set.
				if ( empty( $stored_ips ) ) {
					$add_ip = self::add_to_user_stored_ips( $user_id, $user_addr );
				} else {
					$add_ip = self::add_to_user_stored_ips( $user_id, $user_addr );
					// Is allowed?
					$is_login_allowed = self::is_ip_allowed( $user_id, $user_addr );
					if ( ! $is_login_allowed || ! $add_ip ) {
						// UM error handling.
						if ( class_exists( '\UM_Functions' ) ) {
							UM()->form()->add_error( 'ppmwp_login_attempts_blocked', $error_string );
						}

						return new \WP_Error(
							'login_not_allowed',
							$error_string
						);
					}
				}
			}

			// We must return the user, regardless.
			return $user;
		}

		/**
		 * Check if IP is ok to logins.
		 *
		 * @param int    $user_id - ID to check.
		 * @param string $user_addr - IP to check.
		 * @return boolean
		 */
		public static function is_ip_allowed( $user_id, $user_addr ) {
			$result   = false;
			$user_ips = self::get_user_stored_ips( $user_id );
			if ( in_array( $user_addr, $user_ips ) ) {
				$result = true;
			}
			return $result;
		}

		/**
		 * Get users stored IPs.
		 *
		 * @param  int $user_id - User ID to get.
		 * @return array $ips - Found IPs.
		 */
		public static function get_user_stored_ips( $user_id ) {
			$ips = get_user_meta( $user_id, 'mls_login_ips', true );

			if ( ! $ips || empty( $ips ) ) {
				$ips = array();
			}

			return $ips;
		}

		/**
		 * Store an IP is enough space is allowed by admin, otherwise if this new IP exceeds the amount allowed, login wont be allowed.
		 *
		 * @param int    $user_id - ID to store.
		 * @param string $incoming - To add.
		 * @return bool   Did update.
		 */
		public static function add_to_user_stored_ips( $user_id, $incoming ) {
			$ips          = self::get_user_stored_ips( $user_id );
			$userdata     = get_user_by( 'id', $user_id );
			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );
			$max_allowed  = (int) str_replace( '0', '', $role_options->restrict_login_ip_count );

			if ( count( $ips ) < $max_allowed ) {
				// IP is stored already.
				if ( in_array( $incoming, $ips ) ) {
					return true;
					// Add new IP.
				} else {
					array_push( $ips, $incoming );
					update_user_meta( $user_id, 'mls_login_ips', $ips );
				}

				return true;
			} else {
				return in_array( $incoming, $ips, true );
			}

			return false;
		}
	}
}
