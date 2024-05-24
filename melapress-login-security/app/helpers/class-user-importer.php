<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Helper class to hide other admin notices.
 *
 * @since 1.2.0
 *
 * @package WordPress
 */

namespace PPMWP\Helpers;

/**
 *  Helper class to hide other admin notices.
 *
 * @since 1.2.0
 */
class UserImporter {

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 */
	public function init() {
		add_filter( 'ppmwp_settings_page_nav_tabs', array( $this, 'settings_tab_link' ), 50, 1 );
		add_filter( 'ppmwp_settings_page_content_tabs', array( $this, 'settings_tab' ), 50, 1 );
		add_filter( 'wp_ajax_mls_export_users', array( $this, 'export_users' ), 10, 1 );
		add_filter( 'wp_ajax_mls_process_user_import', array( $this, 'process_import' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'selectively_enqueue_admin_script' ) );
	}

	/**
	 * Add scripts when needed.
	 *
	 * @param string $hook - Current hook.
	 * @return void
	 */
	public function selectively_enqueue_admin_script( $hook ) {
		if ( 'login-security_page_ppm-settings' !== $hook ) {
			return;
		}

		$ppm = ppm_wp();

		wp_enqueue_script( 'mls_users_importexport', PPM_WP_URL . 'admin/assets/js/users-importexport.js', array( 'ppm-wp-settings' ), PPMWP_VERSION );

		wp_localize_script(
			'mls_users_importexport',
			'wpws_import_data',
			array(
				'wp_import_nonce'       => wp_create_nonce( 'mls-import-settings' ),
				'checkingMessage'       => esc_html__( 'Checking import contents', 'ppm-wp' ),
				'checksPassedMessage'   => esc_html__( 'Ready to import', 'ppm-wp' ),
				'checksFailedMessage'   => esc_html__( 'Issues found', 'ppm-wp' ),
				'importingMessage'      => esc_html__( 'Importing settings', 'ppm-wp' ),
				'importedMessage'       => esc_html__( 'Settings imported', 'ppm-wp' ),
				'helpMessage'           => esc_html__( 'Help', 'ppm-wp' ),
				'notFoundMessage'       => esc_html__( 'The role, user or post type contained in your settings are not currently found in this website. Importing such settings could lead to abnormal behavour. For more information and / or if you require assistance, please', 'ppm-wp' ),
				'notSupportedMessage'   => esc_html__( 'Currently this data is not supported by our export/import wizard.', 'ppm-wp' ),
				'restrictAccessMessage' => esc_html__( 'To avoid accidental lock-out, this setting is not imported.', 'ppm-wp' ),
				'wrongFormat'           => esc_html__( 'Please upload a valid JSON file.', 'ppm-wp' ),
				'cancelMessage'         => esc_html__( 'Cancel', 'ppm-wp' ),
				'readyMessage'          => esc_html__( 'The settings file has been tested and the configuration is ready to be imported. Would you like to proceed?', 'ppm-wp' ),
				'proceedMessage'        => esc_html__( 'The configuration has been successfully imported. Click OK to close this window', 'ppm-wp' ),
				'proceed'               => esc_html__( 'Proceed', 'ppm-wp' ),
				'ok'                    => esc_html__( 'OK', 'ppm-wp' ),
				'helpPage'              => '',
				'helpLinkText'          => esc_html__( 'Contact Us', 'ppm-wp' ),
				'isUsingCustomEmail'    => ( $ppm->options->ppm_setting->from_email && ! empty( $ppm->options->ppm_setting->from_email ) ) ? $ppm->options->ppm_setting->from_email : false,
			)
		);
	}

	/**
	 * Add link to tabbed area within settings.
	 *
	 * @param  string $markup - Currently added content.
	 * @return string $markup - Appended content.
	 */
	public function settings_tab_link( $markup ) {
		return $markup . '<a href="#users-export" class="nav-tab" data-tab-target=".ppm-users-export">' . esc_attr__( 'User Import/Export', 'ppm-wp' ) . '</a>';
	}

	/**
	 * Add settings tab content to settings area
	 *
	 * @param  string $markup - Currently added content.
	 * @return string $markup - Appended content.
	 */
	public function settings_tab( $markup ) {
		ob_start(); ?>
			<div class="settings-tab ppm-users-export">
				<table class="form-table">
					<tbody>
						<?php
						self::render_settings();
						?>
					</tbody>
				</table>
			</div>
			<?php
			return $markup . ob_get_clean();
	}

	/**
	 * Display settings markup for email tempplates.
	 *
	 * @return void
	 */
	public static function render_settings() {
		$ppm   = ppm_wp();
		$nonce = wp_create_nonce( 'mls-export-settings' );
		// Get wp all roles.
		global $wp_roles;
		$roles = $wp_roles->get_names();
		?>
				
				<tr>
					<th><label><?php esc_html_e( 'Export users', 'ppm-wp' ); ?></label></th>
					<td>
						<fieldset>
							<input type="button" id="export-users" class="button-primary"
									value="<?php esc_html_e( 'Export', 'ppm-wp' ); ?>"
									data-export-wpws-users data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once the users are exported a download will automatically start. The users are exported to a CSV text file.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th><label><?php esc_html_e( 'Import users to WordPress', 'ppm-wp' ); ?></label></th>
					<td>
						<fieldset>
							<p class="description">
							<?php esc_html_e( 'Here you can import users by providing a CSV of usernames and email addresses.', 'ppm-wp' ); ?>
							</p>

							<br>
							<p class="description">
								<details>
									<summary><?php esc_html_e( 'Click here to view an example txt file.', 'ppm-wp' ); ?></summary>
									<div id="csv-examples">
										<pre>
										"username","email"
										"j11ohnewf24gdose","johgwegn23r.do32re@example.com"
										"j22ohndsoe","john.dasdoe@examasd23rple.com"
										"jo22h2f3d23rnddoe","john.do32re332r2r@example.com"
										</pre>
										<pre>
										"username",
										"johgwegn23r.do32re@example.com"
										"john.dasdoe@examasd23rple.com"
										"john.do32re332r2r@example.com"
										</pre>
									</div>
								</details>
							</p>
							<br>

							<input type="file" id="wpws-users-file" name="filename"><br>
							<input style="margin-top: 7px;" type="submit" id="import-users" class="button-primary" data-import-wpws-users data-nonce="<?php echo esc_attr( $nonce ); ?>" value="<?php esc_html_e( 'Validate & Import', 'ppm-wp' ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once you choose a CSV text users file, it will be checked prior to being imported to alert you of any issues, if there are any.', 'ppm-wp' ); ?>
							</p>
							<div id="import-users-modal">
								<div class="modal-content">
									<h3 id="wpws-modal-title"></h3>
									<span class="import-users-modal-close">&times;</span>
									<h3><?php esc_html_e( 'Import users to WordPress', 'ppm-wp' ); ?></h3>
									<p class="description">
									<?php esc_html_e( "Below are the usernames and (if applicable) email addresses detected from your CSV file. If you wish to enable a forced password reset on users' first login, tick the checkbox below.", 'ppm-wp' ); ?>
									</p>
									<br>
									<div id="file-import-settings">
										<fieldset>
											<legend class="screen-reader-text">
												<span>
												<?php esc_html_e( 'Numbers', 'ppm-wp' ); ?>
												</span>
											</legend>
											<label for="force-reser">
												<input id="force-reset" type="checkbox" value="1" checked />
												<?php esc_html_e( "Force newly created users to reset their password on their first login.", 'ppm-wp' ); ?>
											</label>
											<p><strong><?php esc_html_e( 'IMPORTANT:', 'ppm-wp' ); ?></strong> <?php esc_html_e( "If you don't tick the above option but this setting is enforced through the plugin's settings, the users will still need to change their password on their first login.", 'ppm-wp' ); ?></p>										
											<br>
										</fieldset>

										<fieldset>
											<p><?php esc_html_e( "By default, imported users are assigned the Subscriber role. However, you can choose a different role from the drop-down menu. Once you have made your selections, you can click Proceed to begin importing the users. The window will update once the process is completed.", 'ppm-wp' ); ?></p>
											<label>
											<?php esc_html_e( 'Assign the following role to imported users', 'ppm-wp' ); ?>
												<select id="import-role">
											<?php
											foreach ( $roles as $key => $value ) {
												if ( 'subscriber' === strtolower( $value ) ) {
													echo '<option selected value="' . esc_attr( $value ) . '">' . esc_attr( $value ) . '</option>';
												} else {

													echo '<option value="' . esc_attr( $value ) . '">' . esc_attr( $value ) . '</option>';
												}
											}
											?>
												</select>
											</label>
										 

										</fieldset>
									</div>

									<span><ul id="wpws-users-file-output"></ul></span>
									
									<div id="wpws-users-actions"></div>
								</div>
							</div>
						</fieldset>
					</td>
				</tr>

				<style>
					li[data-wpws-option-name] span {
						width: auto;
						margin-left: 10px;
						display: inline-block;
					}

					#wpws-users-file-output li, li[data-wpws-option-name] [data-help] {
						font-size: 14px;
						font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
						line-height: 25px;
					}

					#import-users-modal {
						display: none;
						position: fixed;
						z-index: 9999;
						left: 0;
						top: 0;
						width: 100%;
						height: 100%;
						overflow: auto;
						background-color: rgb(0, 0, 0);
						background-color: rgba(0, 0, 0, 0.4);
					}

					#import-users-modal .modal-content {
						background-color: #fefefe;
						margin: 5% auto;
						padding: 20px;
						border: 1px solid #888;
						width: 80%;
						max-width: 800px;
					}

					.import-users-modal-close {
						color: #aaa;
						float: right;
						font-size: 28px;
						font-weight: bold;
					}

					.import-users-settings-modal-close:hover, .import-users-modal-close:focus {
						color: black;
						text-decoration: none;
						cursor: pointer;
					}

					[data-wpws-option-name] {
						line-height: 25px !important;
					}

					[data-wpws-option-name]>div {
						display: inline-block;
						min-width: 285px;
						font-size: 15px;
						font-weight: 500;
						text-transform: capitalize;
					}

					[data-wpws-option-name]:last-of-type {
						margin-bottom: 30px;
					}

					#wpws-modal-title {
						max-width: 500px;
						display: inline-block;
						margin: 0 15px 1px 0;
						font-size: 24px;
					}

					li[data-wpws-option-name] [data-help] {
						position:relative; /* making the .tooltip span a container for the tooltip text */
						border-bottom:1px dashed #000; /* little indicater to indicate it's hoverable */
					}

					li[data-wpws-option-name] [data-help]:before {
						content: attr(data-help-text); /* here's the magic */
						position:absolute;
						
						/* vertically center */
						top:50%;
						transform:translateY(-50%);
						
						/* move to right */
						left:100%;
						margin-left:15px; /* and add a small left margin */
						
						/* basic styles */
						width:200px;
						padding:10px;
						border-radius:10px;
						background:#000;
						color: #fff;
						text-align:center;
					
						display:none; /* hide by default */
					}

					.button-primary#export-users, .button-primary#import-users {
						min-width: 126px;
					}

					li[data-wpws-option-name] [data-help] .tooltip {
						content: attr(data-help-text); /* here's the magic */
						position:absolute;
						top:50%;
						transform:translateY(-50%);
						left:100%;
						margin-left:15px;
						width:200px;
						padding:10px;
						border-radius:10px;
						background:#000;
						color: #fff;
						text-align:center;
						line-height: 18px;
						font-size: 13px;
					}

					li[data-wpws-option-name] [data-help] .tooltip a {
						font-weight: bold;
						color: #fff;
					}

					#wpws-import-read.disabled {
						opacity: 0.5;
						pointer-events: none;
					}

					#ready-text {
						display: block;
						margin-bottom: 15px;
					}

					#wpws-import-read input {
						float: left;
					}
					.dashicons-info + .dashicons-yes-alt {
						visibility: hidden;
					}
					#wpws-users-file-output li:first-of-type {
						font-weight: bold;
					}
					#wpws-users-file-output li span {
						min-width: 170px;
						display: inline-block;
					}
					#wpws-users-file-output li span:first-of-type {
						min-width: 200px;
					}
					.modal-content h3 {
						margin-top: 0;
						font-size: 15px;
					}
					.modal-content .description {
						max-width: none !important;
					}
				</style>
				<?php

	}

	/**
	 * Creates a JSON file containing settings.
	 */
	public function export_users() {
		// Grab POSTed data.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		// Check nonce.
		if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'ppm-wp' ) );
		}

		$results = array(
			'username,email',
		);

		$blogusers = get_users( array( 'fields' => array( 'user_login', 'user_email' ) ) );
		foreach ( $blogusers as $user ) {
			$user_details = '' . $user->user_login . ',' . $user->user_email . '';
			array_push( $results, $user_details );
		}

		wp_send_json_success( $results ); // phpcs:ignore
	}

	/**
	 * Checks settings before importing.
	 */
	public function process_import() {

		// Grab POSTed data.
		$nonce = null;

		if ( isset( $_POST['nonce'] ) ) {
			$nonce = \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) );
		}

		// Check nonce.
		if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) || empty( $_POST['username'] ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'ppm-wp' ) );
		}

		$new_user_role = isset( $_POST['role'] ) && ! empty( $_POST['role'] ) ? strtolower( \sanitize_text_field( \wp_unslash( $_POST['role'] ) ) ) : 'subscriber';
		$username      = isset( $_POST['username'] ) ? \sanitize_text_field( \wp_unslash( $_POST['username'] ) ) : false;
		$email_address = isset( $_POST['email'] ) ? $_POST['email'] : false;
		$force_reset   = isset( $_POST['force_reset'] ) && ! empty( $_POST['force_reset'] ) && 'false' !== $_POST['force_reset'] ? \sanitize_text_field( \wp_unslash( $_POST['force_reset'] ) ) : false;
		$ppm           = ppm_wp();

		$force_reset = apply_filters( 'mls_override_apply_forced_reset_on_user_import', $force_reset );

		if ( ! username_exists( $username ) ) {
			$password_gen = new \PPMWP\PPM_WP_Password_Gen();
			$password     = $password_gen->_generate( true );

			if ( ! $email_address ) {
				if ( is_email( $username ) ) {
					$email_address = $username;
				} else {
					wp_send_json_error( esc_html__( 'No email address provided for user.', 'ppm-wp' ) );
					exit;
				}
			}

			$user_id = wp_create_user( $username, $password, $email_address );

			if ( is_wp_error( $user_id ) ) {
				$found_user = get_user_by( 'login', $username );
				$message = array(
					'user_exists' => $found_user->ID,
				);
				wp_send_json_error( $message );
				exit;
			} else {
				if ( $force_reset ) {
					do_action( 'ppmwp_apply_forced_reset_usermeta', $user_id );
				}

				$user = new \WP_User( $user_id );
				$user->set_role( $new_user_role );

				if ( $email_address ) {
					$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
					$from_email = sanitize_email( $from_email );
					$headers[]  = 'From: ' . $from_email;
					$title      = esc_html__( 'Welcome', 'ppm-wp' );

					if ( $force_reset ) {
						$key        = get_password_reset_key( $user );
						$login_page = OptionsHelper::get_password_reset_page();

						$message       = __( 'Hello', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'Your user {user_login_name} on the website {home_url} has been created.', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'Please visit the following URL to reset your password: {reset_url}', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'If you have any questions or require assistance contact your website administrator on {admin_email}.', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'Thank you. ', 'ppm-wp' ) . "\n";
						$email_content = \PPMWP\PPM_EmailStrings::replace_email_strings( $message, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) ) ) );

						wp_mail( $email_address, wp_specialchars_decode( $title ), $email_content, $headers );
					} else {
						$message       = __( 'Hello', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'Your user {user_login_name} on the website {home_url} has been created.', 'ppm-wp' ) . "\n\n";
						$message      .= __( 'You may login with the following password', 'ppm-wp' ) . "\n\n";
						$message      .= $password . "\n\n";
						$message      .= __( 'Thank you. ', 'ppm-wp' ) . "\n";
						$email_content = \PPMWP\PPM_EmailStrings::replace_email_strings( $message, $user_id );

						wp_mail( $email_address, wp_specialchars_decode( $title ), $email_content, $headers );
					}
				}

				$user_link = get_edit_user_link( $user_id );
			}
		} else {
			$found_user = get_user_by( 'login', $username );
			$message = array(
				'user_exists' => $found_user->ID,
			);
			wp_send_json_error( $message );
			exit;
		}

		do_action( 'mls_new_user_imported', $user_id );

		$message = array(
			'user_created' => $user_id,
			'username'     => $username,
			'user_link'    => $user_link,
		);
		wp_send_json_success( $message );
		exit;
	}


	/**
	 * Gets value ready for checking when needed.
	 *
	 * @param mixed $value Value.
	 */
	public function trim_and_explode( $value ) {
		if ( is_array( $value ) ) {
			return explode( ',', $value[0] );
		} else {
			$setting_value = trim( $value, '"' );

			return str_replace( '""', '"', explode( ',', $setting_value ) );
		}
	}
}
