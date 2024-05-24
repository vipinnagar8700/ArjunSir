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
class SettingsImporter {

	/**
	 * Init settings hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'ppmwp_settings_page_nav_tabs', array( $this, 'settings_tab_link' ), 50, 1 );
		add_filter( 'ppmwp_settings_page_content_tabs', array( $this, 'settings_tab' ), 50, 1 );
		add_filter( 'wp_ajax_mls_export_settings', array( $this, 'export_settings' ), 10, 1 );
		add_filter( 'wp_ajax_mls_check_setting_pre_import', array( $this, 'check_setting_pre_import' ), 10, 1 );
		add_filter( 'wp_ajax_mls_process_import', array( $this, 'process_import' ), 10, 1 );
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

		wp_enqueue_script( 'mls_settings_importexport', PPM_WP_URL . 'admin/assets/js/settings-importexport.js', array( 'ppm-wp-settings' ), PPMWP_VERSION );

		wp_localize_script(
			'mls_settings_importexport',
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
		return $markup . '<a href="#settings-export" class="nav-tab" data-tab-target=".ppm-settings-export">' . esc_attr__( 'Settings Import/Export', 'ppm-wp' ) . '</a>';
	}

	/**
	 * Add settings tab content to settings area
	 *
	 * @param  string $markup - Currently added content.
	 * @return string $markup - Appended content.
	 */
	public function settings_tab( $markup ) {
		ob_start(); ?>
			<div class="settings-tab ppm-settings-export"">
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
		?>
				
				<tr>
					<th><label><?php esc_html_e( 'Export settings', 'ppm-wp' ); ?></label></th>
					<td>
						<fieldset>
							<input type="button" id="export-settings" class="button-primary"
									value="<?php esc_html_e( 'Export', 'ppm-wp' ); ?>"
									data-export-wpws-settings data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once the settings are exported a download will automatically start. The settings are exported to a JSON file.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th><label><?php esc_html_e( 'Import settings', 'ppm-wp' ); ?></label></th>
					<td>
						<fieldset>

							<input type="file" id="wpws-settings-file" name="filename"><br>
							<input style="margin-top: 7px;" type="submit" id="import-settings" class="button-primary" data-import-wpws-settings data-nonce="<?php echo esc_attr( $nonce ); ?>" value="<?php esc_html_e( 'Validate & Import', 'ppm-wp' ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once you choose a JSON settings file, it will be checked prior to being imported to alert you of any issues, if there are any.', 'ppm-wp' ); ?>
							</p>
							<div id="import-settings-modal">
								<div class="modal-content">
									<h3 id="wpws-modal-title"></h3>
									<span class="import-settings-modal-close">&times;</span>
									<span><ul id="wpws-settings-file-output"></ul></span>
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

					li[data-wpws-option-name] span span, li[data-wpws-option-name] [data-help] {
						width: auto;
						font-size: 14px;
						font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
						position: relative;
						margin: 0;
						top: -5px;
					}

					#import-settings-modal {
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

					#import-settings-modal .modal-content {
						background-color: #fefefe;
						margin: 5% auto;
						padding: 20px;
						border: 1px solid #888;
						width: 80%;
						max-width: 800px;
					}

					.import-settings-modal-close {
						color: #aaa;
						float: right;
						font-size: 28px;
						font-weight: bold;
					}

					.import-settings-modal-close:hover, .import-settings-modal-close:focus {
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

					.button-primary#export-settings, .button-primary#import-settings {
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
				</style>
			<?php

	}

	/**
	 * Creates a JSON file containing settings.
	 */
	public function export_settings() {
		// Grab POSTed data.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'ppm-wp' ) );
		}

		$results = array();

		global $wpdb;

		if ( is_multisite() ) {
			$prepared_query = $wpdb->prepare(
				"SELECT `meta_key`, `meta_value` FROM `{$wpdb->sitemeta}` WHERE `meta_key` LIKE %s ORDER BY `meta_key` ASC",
				PPMWP_PREFIX . '%'
			);
		} else {
			$prepared_query = $wpdb->prepare(
				"SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC",
				PPMWP_PREFIX . '%'
			);
		}

		$results = $wpdb->get_results( $prepared_query ); // phpcs:ignore

		wp_send_json_success( json_encode( $results ) ); // phpcs:ignore
	}

	/**
	 * Checks settings before importing.
	 */
	public function check_setting_pre_import() {
		// Grab POSTed data.
		$nonce = null;

		if ( isset( $_POST['nonce'] ) ) {
			$nonce = \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) );
		}

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'ppm-wp' ) );
		}

		$setting_name = null;
		if ( isset( $_POST['setting_name'] ) ) {
			$setting_name = \sanitize_text_field( \wp_unslash( $_POST['setting_name'] ) );
		}
		$process_import = null;
		if ( isset( $_POST['process_import'] ) ) {
			$process_import = \sanitize_text_field( \wp_unslash( $_POST['process_import'] ) );
		}

		$setting_value = filter_input( INPUT_POST, 'setting_value', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$setting_value = $setting_value[0];

		$message = array(
			'setting_checked' => $setting_name,
		);

		$failed = false;

		// Check if relevant data is present for setting to be operable before import.
		if ( ! empty( $setting_value ) ) {

			if ( 'true' !== $process_import && $failed ) {
				wp_send_json_error( $message );
			}
		}

		if ( 'ppmwp_setting' === $setting_name && isset( $_POST['from_email_to_use'] ) && ! empty( maybe_unserialize( $setting_value ) ) ) {
			$setting_arr               = (array) maybe_unserialize( $setting_value );
			$setting_arr['from_email'] = \sanitize_text_field( \wp_unslash( $_POST['from_email_to_use'] ) );
			$setting_value             = $setting_arr;

		}

		// If set to import the data once checked, then do so.
		if ( 'true' === $process_import && ! isset( $message['failure_reason'] ) ) {
			$updated                        = ( ! update_site_option( $setting_name, maybe_unserialize( $setting_value ) ) ) ? esc_html__( 'Setting updated', 'ppm-wp' ) : esc_html__( 'Setting created', 'ppm-wp' );
			$message['import_confirmation'] = $updated;
			wp_send_json_success( $message );
		}

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
