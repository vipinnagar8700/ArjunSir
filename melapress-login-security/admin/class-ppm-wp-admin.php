<?php
/**
 * WPassword Admin Class.
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP\Admin;

use \PPMWP\Utilities\ValidatorFactory as ValidatorFactory; // phpcs:ignore
use \PPMWP\Helpers\OptionsHelper as OptionsHelper; // phpcs:ignore

if ( ! class_exists( '\PPMWP\Admin\PPM_WP_Admin' ) ) {

	/**
	 * Declare PPM_WP_Admin class
	 */
	class PPM_WP_Admin {

		/**
		 * WPassword Options.
		 *
		 * @var array|object
		 */
		public $options;

		/**
		 * Password Polciy Manager Settings.
		 *
		 * @var array|object settings
		 */
		public $settings;

		/**
		 * WPassword Setting Tab.
		 *
		 * @var array $setting_tab
		 */
		public $setting_tab = array();

		/**
		 * WPassword additonal notice content.
		 *
		 * @var array $extra_notice_details
		 */
		private $extra_notice_details = array();

		/**
		 * Class construct.
		 *
		 * @param array|object $options PPM options.
		 * @param array|object $settings PPM setting options.
		 * @param array|object $setting_options Get current role option.
		 */
		public function __construct( $options, $settings, $setting_options ) {
			$this->options     = $options;
			$this->settings    = $settings;
			$this->setting_tab = $setting_options;

			add_filter( 'plugin_action_links_' . PPM_WP_BASENAME, array( $this, 'plugin_action_links' ), 100, 1 );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			// Ajax.
			add_action( 'wp_ajax_get_users_roles', array( $this, 'search_users_roles' ) );
			add_action( 'wp_ajax_ppm_wp_send_test_email', array( $this, 'send_test_email' ) );
			add_action( 'wp_ajax_mls_process_reset', array( '\PPMWP\PPM_WP_Reset', 'process_global_password_reset' ) );

			// Bulk actions.
			add_filter( 'bulk_actions-users', array( '\PPMWP\PPM_WP_Reset', 'add_bulk_action_link' ), 10, 1 );
			add_filter( 'handle_bulk_actions-users', array( '\PPMWP\PPM_WP_Reset', 'handle_bulk_action_link' ), 10, 3 );
			add_filter( 'admin_notices', array( '\PPMWP\PPM_WP_Reset', 'bulk_action_admin_notice' ), 10, 3 );

			// Add dialog box.
			add_action( 'admin_footer', array( $this, 'admin_footer_session_expired_dialog' ) );
			add_action( 'admin_footer', array( $this, 'popup_notices' ) );

			$options_master_switch    = OptionsHelper::string_to_bool( $this->options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( $this->settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( $this->settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {
				if ( OptionsHelper::string_to_bool( $this->settings->enforce_password ) ) {
					return;
				}
				add_action( 'admin_enqueue_scripts', array( $this, 'global_admin_enqueue_scripts' ) );
			}
		}

		/**
		 * Handles injecting content for a thickbox popup for special notice
		 * messages that people need to see when working with the settings of
		 * the plugin.
		 *
		 * Renders a hidden modal to be triggered when the page loads.
		 *
		 * NOTE: this code can be used to trigger it:
		 * if( jQuery('#notice_modal' ).length > 0 ) {
		 *     tb_show( jQuery('#notice_modal' ).data( 'windowTitle' ) , '#TB_inline?height=155&width=400&inlineId=notice_modal');
		 * }
		 *
		 * @method popup_notices
		 * @since  2.1.0
		 */
		public function popup_notices() {

			if ( is_array( $this->extra_notice_details ) && ! empty( $this->extra_notice_details ) ) {
				foreach ( $this->extra_notice_details as $notice ) {
					if ( ! isset( $notice['message'] ) ) {
						// no message to send, skip itteration.
						continue;
					}
					?>
					<div id="notice_modal" class="hidden"
						data-windowtitle="<?php echo ( isset( $notice['title'] ) ) ? esc_attr( $notice['title'] ) : ''; ?>"
						data-redirect="<?php echo ( isset( $notice['redirect'] ) ) ? esc_attr( $notice['redirect'] ) : ''; ?>"
						>
						<div class="notice_modal_wrapper">
							<p><?php echo wp_kses_post( $notice['message'] ); ?></p>
							<?php
							if ( isset( $notice['buttons'] ) && ! empty( $notice['buttons'] ) ) {
								?>
								<div class="notice_modal_footer">
									<?php
									foreach ( $notice['buttons'] as $key => $button ) {
										?>
										<button type="button"
											class="<?php echo ( isset( $button['class'] ) ) ? esc_attr( $button['class'] ) : ''; ?>"
											onClick="<?php echo ( isset( $button['onClick'] ) ) ? esc_attr( $button['onClick'] ) : ''; ?>"
											>
												<?php echo esc_html( $button['text'] ); ?>
											</button>
										<?php
									}
									?>
								</div>
								<?php
							}
							?>

						</div>
					</div>
					<?php
				}
			}

			?>
				<div id="mls_admin_lockout_notice_modal" class="hidden">
					<div class="notice_modal_wrapper">
						<p><?php esc_html_e( 'To ensure you dont lock yourself out of your own dashboard, be sure to exclude your own admin account from password policies when enabling this feature.', 'ppm-wp' ); ?></p>
						<div class="notice_modal_footer">
							<button type="button" class="button-primary" onclick="ppmwp_close_thickbox()"><?php esc_html_e( 'Acknowledge', 'ppm-wp' ); ?></button>
						</div>
					</div>
				</div>
			<?php
		}

		/**
		 * Adds further links to the plugins action items.
		 *
		 * @param array $old_links - Original action links.
		 * @return array
		 */
		public function plugin_action_links( $old_links ) {
			$new_links = array();

			if ( function_exists( 'ppm_freemius' ) ) {
				if ( ppm_freemius()->can_use_premium_code() && isset( $old_links['upgrade'] ) ) {
					unset( $old_links['upgrade'] );
				} elseif ( ppm_freemius()->is_free_plan() ) {
					unset( $old_links['upgrade'] );
					$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . __( 'Get the Premium!', 'ppm-wp' ) . '</a>';
					array_push( $new_links, $upgrade_link );
				}
			} else {
				$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . __( 'Get the Premium!', 'ppm-wp' ) . '</a>';
				array_push( $new_links, $upgrade_link );
			}

			$config_link = '<a href="' . add_query_arg( 'page', PPMWP_MENU_SLUG, network_admin_url( 'admin.php' ) ) . '">' . __( 'Configure policies', 'ppm-wp' ) . '</a>';
			array_push( $new_links, $config_link );

			$docs_link = '<a target="_blank" href="' . add_query_arg(
				array(
					'utm_source'   => 'plugins',
					'utm_medium'   => 'link',
					'utm_campaign' => 'mls',
				),
				'https://www.melapress.com/support/kb/'
			) . '">' . __( 'Docs', 'ppm-wp' ) . '</a>';
			array_push( $new_links, $docs_link );

			return array_merge( $new_links, $old_links );
		}

		/**
		 * Register admin menu
		 */
		public function admin_menu() {
			// Add admin menu page.
			$hook_name = add_menu_page( __( 'Login Security Policies', 'ppm-wp' ), __( 'Login Security', 'ppm-wp' ), 'manage_options', PPMWP_MENU_SLUG, array( $this, 'screen' ), ppm_wp()->icon, 99 );
			add_action( "load-$hook_name", array( $this, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$hook_name", array( $this, 'process' ) );

			add_submenu_page( PPMWP_MENU_SLUG, __( 'Login Security Policies', 'ppm-wp' ), __( 'Login Security Policies', 'ppm-wp' ), 'manage_options', PPMWP_MENU_SLUG, array( $this, 'screen' ) );

			// Add admin submenu page.
			$hook_submenu = add_submenu_page(
				PPMWP_MENU_SLUG,
				__( 'Help & Contact Us', 'ppm-wp' ),
				__( 'Help & Contact Us', 'ppm-wp' ),
				'manage_options',
				'ppm-help',
				array(
					$this,
					'ppm_display_help_page',
				)
			);

			add_action( "load-$hook_submenu", array( $this, 'help_page_enqueue_scripts' ) );

			// Add admin submenu page for settings.
			$settings_hook_submenu = add_submenu_page(
				PPMWP_MENU_SLUG,
				__( 'Settings', 'ppm-wp' ),
				__( 'Settings', 'ppm-wp' ),
				'manage_options',
				'ppm-settings',
				array(
					$this,
					'ppm_display_settings_page',
				)
			);

			add_action( "load-$settings_hook_submenu", array( $this, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$settings_hook_submenu", array( $this, 'process' ) );

			// Add admin submenu page for form placement.
			$forms_hook_submenu = add_submenu_page(
				PPMWP_MENU_SLUG,
				__( 'Forms & Placement', 'ppm-wp' ),
				__( 'Forms & Placement', 'ppm-wp' ),
				'manage_options',
				'ppm-forms',
				array(
					$this,
					'ppm_display_forms_page',
				),
				1
			);

			add_action( "load-$forms_hook_submenu", array( $this, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$forms_hook_submenu", array( $this, 'process_forms' ) );

			// Add admin submenu page for form placement.
			$hide_login_submenu = add_submenu_page(
				PPMWP_MENU_SLUG,
				__( 'Login page hardening', 'ppm-wp' ),
				__( 'Login page hardening', 'ppm-wp' ),
				'manage_options',
				'ppm-hide-login',
				array(
					$this,
					'ppm_display_hide_login_page',
				),
				2
			);

			add_action( "load-$hide_login_submenu", array( $this, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$hide_login_submenu", array( $this, 'process_hide_login' ) );

			if ( class_exists( '\PPMWP\Reports' ) ) {
				// Add admin submenu page for form placement.
				$reports_submenu = add_submenu_page(
					PPMWP_MENU_SLUG,
					__( 'Reports', 'ppm-wp' ),
					__( 'Reports', 'ppm-wp' ),
					'manage_options',
					'ppm-reports',
					array(
						'\PPMWP\Reports',
						'admin_page',
					),
					2
				);

				add_action( "load-$reports_submenu", array( $this, 'admin_enqueue_scripts' ) );
				add_action( "admin_head-$reports_submenu", array( $this, 'process_hide_login' ) );
			}

			/* @free:start */
			$hook_upgrade_submenu = add_submenu_page( PPMWP_MENU_SLUG, esc_html__( 'Premium Features ➤', 'ppm-wp' ), esc_html__( 'Premium Features ➤', 'ppm-wp' ), 'manage_options', 'ppm-upgrade', array( $this, 'ppm_display_upgrade_page' ), 3 );
			add_action( "load-$hook_upgrade_submenu", array( $this, 'help_page_enqueue_scripts' ) );
			/* @free:end */
		}

		/**
		 * Display help page.
		 */
		public function ppm_display_help_page() {
			require_once 'templates/help/index.php';
		}

		/**
		 * Display settings page.
		 */
		public function ppm_display_settings_page() {
			require_once 'templates/views/settings.php';
		}

		/**
		 * Display forms and placement settings page.
		 */
		public function ppm_display_forms_page() {
			require_once 'templates/views/settings-forms.php';
		}

		/**
		 * Display forms and placement settings page.
		 */
		public function ppm_display_hide_login_page() {
			require_once 'templates/views/settings-hide-login.php';
		}

		/* @free:start */
		/**
		 * Display help page.
		 */
		public function ppm_display_upgrade_page() {
			require_once PPM_WP_PATH . 'admin/templates/help/upgrade.php';
		}
		/* @free:end */

		/**
		 * PPM onload process
		 */
		public function process() {
			// nonce checked later before processing happens.
			$is_user_action = isset( $_POST[ PPMWP_PREFIX . '_nonce' ] ) ? true : false; // phpcs:ignore

			if ( $is_user_action ) {
				$this->save();
			}
		}

		/**
		 * Process forms settings.
		 *
		 * @return void
		 */
		public function process_forms() {
			// nonce checked later before processing happens.
			$is_user_action = isset( $_POST[ PPMWP_PREFIX . '_nonce' ] ) ? true : false; // phpcs:ignore

			if ( $is_user_action ) {
				$this->save( 'forms_and_placement' );
			}
		}

		/**
		 * Process hide login settings.
		 *
		 * @return void
		 */
		public function process_hide_login() {
			// nonce checked later before processing happens.
			$is_user_action = isset( $_POST[ PPMWP_PREFIX . '_nonce' ] ) ? true : false; // phpcs:ignore

			if ( $is_user_action ) {
				$this->save( 'hide_login' );
			}
		}

		/**
		 * Render PPM dashboard screen
		 */
		public function screen() {
			include_once PPM_WP_PATH . 'admin/templates/admin-form.php';
		}

		/**
		 * PPM verify wp nonce
		 *
		 * @return bool return
		 */
		public function validate() {
			return wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ PPMWP_PREFIX . '_nonce' ] ) ), PPMWP_PREFIX . '_nonce_form' ); // phpcs:ignore
		}

		/**
		 * Process global resets.
		 *
		 * @return type
		 */
		public function process_reset() {
			if ( ! isset( $_POST[ '_ppm_reset' ] ) ) { // phpcs:ignore
				return;
			}

			if ( ! $this->validate() ) {
				$this->notice( 'admin_reset_error_notice' );
			}
			// Reset all users.
			$reset = new \PPMWP\PPM_WP_Reset();
			$reset->reset_all();
			// Update global reset timestamp.
			$this->update_global_reset_timestamp();
			// Success notice.
			$this->notice( 'admin_reset_success_notice' );
		}

		/**
		 * Save settings values.
		 *
		 * @param string $settings_type - Thing to save.
		 * @return void
		 */
		public function save( $settings_type = '' ) {
			// PPM Object.
			$ppm = ppm_wp();

			if ( ! isset( $_POST[ '_ppm_save' ] ) ) { // phpcs:ignore
				return;
			}

			// Validate the nonce.
			if ( ! $this->validate() ) {
				$this->notice( 'admin_save_error_notice' );
			}

			// If check policies inherit or not.
			if ( isset( $_POST['_ppm_options']['inherit_policies'] ) && sanitize_text_field( wp_unslash( $_POST['_ppm_options']['inherit_policies'] ) ) == 1 ) { // phpcs:ignore 
				// Get user role.
				$setting_option = ( isset( $_POST['_ppm_options']['ppm-user-role'] ) && ! empty( $_POST['_ppm_options']['ppm-user-role'] ) ) ? '_' . sanitize_text_field( wp_unslash( $_POST['_ppm_options']['ppm-user-role'] ) ) : ''; // phpcs:ignore 
				// Delete site option.
				delete_site_option( PPMWP_PREFIX . $setting_option . '_options' );
				// unset settings.
				unset( $_POST[ '_ppm_options' ] ); // phpcs:ignore 
				// Reassign setting open.
				$this->setting_tab = (object) $ppm->options->inherit;
				// Success notice.
				$this->notice( 'admin_save_success_notice' );
			}

			$post_array = filter_input_array( INPUT_POST );

			if ( 'forms_and_placement' === $settings_type ) {
				// Allow empty.
				$settings = isset( $post_array['_ppm_options'] ) ? $post_array['_ppm_options'] : array();

				$settings['enable_wp_reset_form']     = isset( $settings['enable_wp_reset_form'] );
				$settings['enable_wp_profile_form']   = isset( $settings['enable_wp_profile_form'] );
				$settings['enable_wc_pw_reset']       = isset( $settings['enable_wc_pw_reset'] );
				$settings['enable_wc_checkout_reg']   = isset( $settings['enable_wc_checkout_reg'] );
				$settings['enable_bp_register']       = isset( $settings['enable_bp_register'] );
				$settings['enable_bp_pw_update']      = isset( $settings['enable_bp_pw_update'] );
				$settings['enable_ld_register']       = isset( $settings['enable_ld_register'] );
				$settings['enable_um_register']       = isset( $settings['enable_um_register'] );
				$settings['enable_um_pw_update']      = isset( $settings['enable_um_pw_update'] );
				$settings['enable_bbpress_pw_update'] = isset( $settings['enable_bbpress_pw_update'] );
				$settings['enable_mepr_register']     = isset( $settings['enable_mepr_register'] );
				$settings['enable_mepr_pw_update']    = isset( $settings['enable_mepr_pw_update'] );

				$other_settings = (array) $ppm->options->ppm_setting;

				$ppm_setting = OptionsHelper::recursive_parse_args( $settings, $ppm->options->ppm_setting );

				if ( $this->options->_ppm_setting_save( $ppm_setting ) ) {
					$this->notice( 'admin_save_success_notice' );
				}

				return;
			}

			if ( ! isset( $_POST['_ppm_options'] ) ) {  // phpcs:ignore 
				return;
			}

			$settings = $post_array['_ppm_options'];

			// Save plugin settings.
			if ( isset( $settings['exempted'] ) ) {
				$settings['exempted']['users']          = $this->decode_js_var( $settings['exempted']['users'] );
				$settings['terminate_session_password'] = isset( $settings['terminate_session_password'] );
				$settings['send_summary_email']         = isset( $settings['send_summary_email'] );
				$settings['users_have_multiple_roles']  = isset( $settings['users_have_multiple_roles'] );
				$settings['multiple_role_order']        = explode( ',', $settings['multiple_role_order'] );
				$settings['send_user_unlocked_email']   = isset( $settings['send_user_unlocked_email'] );
				$settings['send_user_unblocked_email']  = isset( $settings['send_user_unblocked_email'] );
				$settings['send_user_pw_reset_email']   = isset( $settings['send_user_pw_reset_email'] );
				$settings['send_user_pw_expired_email'] = isset( $settings['send_user_pw_expired_email'] );


				if ( ! isset( $settings['clear_history'] ) ) {
					$settings['clear_history'] = 0;
				}

				$ok_to_save = true;

				/**
				 * Validates the input based on the rules defined in the @see PPM_WP_Options::$settings_options_validation_rules
				 */
				foreach ( \PPMWP\PPM_WP_Options::$settings_options_validation_rules as $key => $valid_rules ) {

					if ( is_array( $valid_rules ) && ! isset( $valid_rules['typeRule'] ) ) {
						foreach ( $valid_rules as $field_name => $rule ) {
							if ( isset( $_POST['_ppm_options'][ $key ][ $field_name ] ) ) { // phpcs:ignore 
								if ( ! ValidatorFactory::validate( sanitize_text_field( wp_unslash( $_POST['_ppm_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore 
									$this->notice( 'admin_save_error_notice' );
									$ok_to_save = false;
								}
							}
						}
					} else {
						if ( isset( $_POST['_ppm_options'][ $key ] ) ) { // phpcs:ignore 
							$rule = $valid_rules;
							if ( ! ValidatorFactory::validate( sanitize_text_field( wp_unslash( $_POST['_ppm_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore 
								$this->notice( 'admin_save_error_notice' );
								$ok_to_save = false;
							}
						}
					}
				}

				if ( $ok_to_save ) {
					$ppm_setting = OptionsHelper::recursive_parse_args( $settings, $ppm->options->ppm_setting );

					if ( $this->options->_ppm_setting_save( $ppm_setting ) ) {
						$this->notice( 'admin_save_success_notice' );
					}
				}
				return;
			}

			if ( 'hide_login' === $settings_type ) {
				$settings['custom_login_url']      = isset( $settings['custom_login_url'] ) ? preg_replace( '/[^-\w,]/', '', $settings['custom_login_url'] ) : $ppm->options->ppm_setting->custom_login_url;
				$settings['custom_login_redirect'] = isset( $settings['custom_login_redirect'] ) ? preg_replace( '/[^-\w,]/', '', $settings['custom_login_redirect'] ) : $ppm->options->ppm_setting->custom_login_redirect;

				$other_settings = (array) $ppm->options->ppm_setting;

				$ppm_setting = OptionsHelper::recursive_parse_args( $settings, $ppm->options->ppm_setting );

				if ( $this->options->_ppm_setting_save( $ppm_setting ) ) {
					$this->notice( 'admin_save_success_notice' );
				}
				return;
			}

			/**
			 * Save Tab options
			 */
			if ( ! isset( $_POST['_ppm_options']['master_switch'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['master_switch'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['enforce_password'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['enforce_password'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['change_initial_password'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['change_initial_password'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['timed_logins'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['timed_logins'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['restrict_login_ip'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['restrict_login_ip'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['disable_self_reset'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['disable_self_reset'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['locked_user_disable_self_reset'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['locked_user_disable_self_reset'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['disable_self_reset_message'] ) || empty( $_POST['_ppm_options']['disable_self_reset_message'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'ppm-wp' );
			}

			if ( ! isset( $_POST['_ppm_options']['locked_user_disable_self_reset_message'] ) || empty( $_POST['_ppm_options']['locked_user_disable_self_reset_message'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['locked_user_disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'ppm-wp' );
			}

			if ( ! isset( $_POST['_ppm_options']['user_unlocked_email_title'] ) || empty( $_POST['_ppm_options']['user_unlocked_email_title'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'ppm-wp' );
			}

			if ( ! isset( $_POST['_ppm_options']['failed_login_policies_enabled'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['failed_login_policies_enabled'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['failed_login_reset_on_unblock'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['failed_login_reset_on_unblock'] = 0;
			}

			if ( ! isset( $_POST['_ppm_options']['inactive_users_enabled'] ) ) {// phpcs:ignore 
				$_POST['_ppm_options']['inactive_users_enabled'] = 0;
			} else {
				$_POST['_ppm_options']['inactive_users_enabled'] = 1;
				// add the current user to the inactive exempt list if that list
				// is empty.
				$added = OptionsHelper::add_initial_user_to_inactive_exempt_list( wp_get_current_user() );
				if ( $added ) {
					// add details to output for the modal popup.
					$this->extra_notice_details[] = array(
						'title'    => __( 'User Added to Inactive Exempt List', 'ppm-wp' ),
						'message'  => __( 'Your user has been exempted from the Inactive Users policy since there must be at least one excluded user to avoid all users being locked out. You can change this from the plugin\'s settings.', 'ppm-wp' ),
						'redirect' => add_query_arg(
							array(
								'page' => 'ppm-settings',
								'tab'  => 'setting',
							),
							network_admin_url( 'admin.php' )
						),
						'buttons'  => array(
							array(
								'text'    => __( 'View settings', 'ppm-wp' ),
								'class'   => 'button-primary',
								'onClick' => 'tb_remove()',
							),
						),
					);
				}

				if ( empty( $_POST['_ppm_options']['inactive_users_expiry']['value'] ) ) { // phpcs:ignore 
					$this->notice( 'admin_save_error_required_field_notice' );
					$ok_to_save = false;
				} else {
					$_POST['_ppm_options']['inactive_users_expiry']['value'] = sanitize_text_field( wp_unslash( $_POST['_ppm_options']['inactive_users_expiry']['value'] ) ); // phpcs:ignore 
				}
			}

			if ( ! isset( $_POST['_ppm_options']['inactive_users_reset_on_unlock'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['inactive_users_reset_on_unlock'] = 0;
			} else {
				$_POST['_ppm_options']['inactive_users_reset_on_unlock'] = 1;
			}

			// Exclude special characters.
			if ( ! isset( $_POST['_ppm_options']['ui_rules']['exclude_special_chars'] ) ) { // phpcs:ignore 
				$_POST['_ppm_options']['ui_rules']['exclude_special_chars'] = 0;
			}

			// Check inputs for emptyness.
			$ok_to_save = true;
			if ( isset( $_POST['_ppm_options']['min_length'] ) && empty( $_POST['_ppm_options']['min_length'] ) || isset( $_POST['_ppm_options']['password_expiry'] ) && empty( $_POST['_ppm_options']['password_expiry']['value'] ) && intval( $_POST['_ppm_options']['password_expiry']['value'] ) !== 0 || isset( $_POST['_ppm_options']['password_history'] ) && empty( $_POST['_ppm_options']['password_history'] ) ) { // phpcs:ignore 
				$this->notice( 'admin_save_error_required_field_notice' ); // phpcs:ignore 
				$ok_to_save = false;
			}

			if ( isset( $_POST['_ppm_options']['ui_rules']['exclude_special_chars'] ) && intval( $_POST['_ppm_options']['ui_rules']['exclude_special_chars'] ) !== 0 && empty( $_POST['_ppm_options']['excluded_special_chars'] ) ) { // phpcs:ignore 
				$this->notice( 'admin_save_error_required_field_notice' ); // phpcs:ignore 
				$ok_to_save = false;
			}

			/**
			 * Validates the input based on the rules defined in the @see PPM_WP_Options::$default_options_validation_rules
			 */
			foreach ( \PPMWP\PPM_WP_Options::$default_options_validation_rules as $key => $valid_rules ) {

				if ( is_array( $valid_rules ) && ! isset( $valid_rules['typeRule'] ) ) {
					foreach ( $valid_rules as $field_name => $rule ) {
						if ( isset( $_POST['_ppm_options'][ $key ][ $field_name ] ) ) { // phpcs:ignore 
							if ( ! ValidatorFactory::validate( sanitize_text_field( wp_unslash( $_POST['_ppm_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore 
								$this->notice( 'admin_save_error_notice' );
								$ok_to_save = false;
							}
						}
					}
				} else {
					if ( isset( $_POST['_ppm_options'][ $key ] ) ) { // phpcs:ignore 
						$rule = $valid_rules;
						if ( ! ValidatorFactory::validate( sanitize_text_field( wp_unslash( $_POST['_ppm_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore 
							$this->notice( 'admin_save_error_notice' );
							$ok_to_save = false;
						}
					}
				}
			}

			$bool_rules = array(
				'special_chars',
				'mix_case',
				'numeric',
				'inactive_users_enabled',
				'exclude_special_chars',
			);

			$post_array  = filter_input_array( INPUT_POST );
			$ppm_options = $post_array ['_ppm_options'];

			// Ensure slashes (which can be added when a " is excluded) are removed prior to saving.
			if ( isset( $ppm_options['excluded_special_chars'] ) ) {
				$ppm_options['excluded_special_chars'] = stripslashes( $ppm_options['excluded_special_chars'] );
			}

			foreach ( $bool_rules as $rule ) {
				$ppm_options['ui_rules'][ $rule ] = isset( $ppm_options['ui_rules'][ $rule ] ) && ! in_array( $ppm_options['ui_rules'][ $rule ], array( 0, '0', false, '' ), true );
			}

			$main_bool_options     = array( 'master_switch', 'enforce_password', 'inherit_policies', 'change_initial_password', 'timed_logins', 'restrict_login_ip', 'disable_self_reset', 'locked_user_disable_self_reset', 'inactive_users_enabled', 'inactive_users_reset_on_unlock', 'failed_login_policies_enabled', 'failed_login_reset_on_unblock' );
			$ui_rules_bool_options = array( 'history', 'username', 'length', 'numeric', 'mix_case', 'special_chars', 'exclude_special_chars' );
			$pw_rules_bool_options = array( 'length', 'numeric', 'upper_case', 'lower_case', 'special_chars', 'exclude_special_chars' );

			// Turn bools into yes/no.
			$ppm_options_updated = array();
			// Process main options.
			foreach ( $main_bool_options as $main_bool ) {
				$bool_to_check                     = ( isset( $ppm_options[ $main_bool ] ) ) ? $ppm_options[ $main_bool ] : false;
				$ppm_options_updated[ $main_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
			}
			// Process UI options.
			foreach ( $ui_rules_bool_options as $ui_bool ) {
				$bool_to_check                               = ( isset( $ppm_options['ui_rules'][ $ui_bool ] ) ) ? $ppm_options['ui_rules'][ $ui_bool ] : false;
				$ppm_options_updated['ui_rules'][ $ui_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
			}
			// Process PW options.
			foreach ( $pw_rules_bool_options as $pw_rules_bool ) {
				$bool_to_check                                  = ( isset( $ppm_options['rules'][ $pw_rules_bool ] ) ) ? $ppm_options['rules'][ $pw_rules_bool ] : false;
				$ppm_options_updated['rules'][ $pw_rules_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
			}

			// Process reset blocked message.
			$ppm_options_updated['disable_self_reset_message']             = ( ! empty( $ppm_options['disable_self_reset_message'] ) ) ? sanitize_textarea_field( $ppm_options['disable_self_reset_message'] ) : false;
			$ppm_options_updated['locked_user_disable_self_reset_message'] = ( ! empty( $ppm_options['locked_user_disable_self_reset_message'] ) ) ? sanitize_textarea_field( $ppm_options['locked_user_disable_self_reset_message'] ) : false;
			$ppm_options_updated['deactivated_account_message']            = ( isset( $ppm_options['deactivated_account_message'] ) && ! empty( $ppm_options['deactivated_account_message'] ) ) ? wp_kses_post( $ppm_options['deactivated_account_message'] ) : trim( \PPMWP\PPM_WP_Options::get_default_account_deactivated_message() );
			$ppm_options_updated['timed_login_message']                    = ( ! empty( $ppm_options['timed_login_message'] ) ) ? sanitize_textarea_field( $ppm_options['timed_login_message'] ) : false;

			$processed_ppm_options = apply_filters( 'mls_pre_option_save_validation', array_merge( $ppm_options, $ppm_options_updated ) );

			if ( $ok_to_save ) {
				if ( $this->options->_save( $processed_ppm_options ) ) {

					$this->setting_tab = (object) $this->options->setting_options;

					$this->notice( 'admin_save_success_notice' );
				}
			}
		}

		/**
		 * Validate a list of site users for the inactive exempted list.
		 *
		 * Accepts a CSV string of usernames, checks they exist and returns
		 * only those that are real users.
		 *
		 * @method validate_inactive_exempted
		 * @since  2.1.0
		 * @param  string $users_string CSV string of usernames.
		 * @return string
		 */
		public function validate_inactive_exempted( $users_string ) {
			$users_array  = array();
			$users_string = (string) $users_string;
			$users        = explode( ',', $users_string );
			foreach ( $users as $username ) {
				$user = get_user_by( 'login', trim( $username ) );
				if ( is_a( $user, '\WP_User' ) ) {
					$users_array[ $user->ID ] = $user->data->user_login;
				}
			}
			return $users_array;
		}

		/**
		 * Admin notice.
		 *
		 * @param  string $function Callback fuction.
		 */
		public function notice( $function ) {
			add_action( 'admin_notices', array( $this, $function ) );
		}

		/**
		 * Enqueue script for help page.
		 */
		public function help_page_enqueue_scripts() {
			wp_enqueue_style( 'ppm-help', PPM_WP_URL . 'admin/assets/css/help.css', array(), PPMWP_VERSION );
		}

		/**
		 * Add scripts for admin pages.
		 *
		 * @param type $hook - Current admin page.
		 */
		public static function admin_enqueue_scripts( $hook ) {
			$ppm = ppm_wp();
			add_thickbox();
			// enqueue these scripts and styles before admin_head.
			wp_enqueue_script( 'jquery-ui-dialog' );
			// jquery and jquery-ui should be dependencies, didn't check though.
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			// enqueue plugin JS.
			wp_enqueue_style( 'ppm-wp-settings-css', PPM_WP_URL . 'admin/assets/css/settings.css', array(), PPMWP_VERSION ); // phpcs:ignore 
			wp_enqueue_script( 'ppm-wp-settings', PPM_WP_URL . 'admin/assets/js/settings.js', array( 'jquery-ui-autocomplete', 'jquery-ui-sortable' ), PPMWP_VERSION ); // phpcs:ignore 
			$session_setting = isset( $ppm->options->ppm_setting->terminate_session_password ) ? $ppm->options->ppm_setting->terminate_session_password : $ppm->options->default_setting->terminate_session_password;
			wp_localize_script(
				'ppm-wp-settings',
				'ppm_ajax',
				array(
					'ajax_url'                   => admin_url( 'admin-ajax.php' ),
					'test_email_nonce'           => wp_create_nonce( 'send_test_email' ),
					'settings_nonce'             => wp_create_nonce( 'ppm_wp_settings' ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'special_chars_regex'        => ppm_wp()->get_special_chars( true ),
					'reset_done_title'           => __( 'Reset process complete', 'ppm-wp' ),
					'csv_error'                  => __( 'CSV contains invalid data, provide user IDs only.', 'ppm-wp' ),
					'csv_error_length'           => __( 'Please ensure more than 1 ID is provided.', 'ppm-wp' ),
					'reset_done_text'            => __( 'You may now close this window.', 'ppm-wp' ),
				)
			);
			do_action( 'ppmwp_enqueue_admin_scripts' );
			wp_localize_script(
				'ppm-wp-settings',
				'ppmwpSettingsStrings',
				array(
					'resetPasswordsDelayedMessage'   => __( 'This will reset the passwords of all users on this site. Users have to change their password once they logout and log back in. Are you sure?', 'ppm-wp' ),
					'resetPasswordsInstantlyMessage' => __( 'This will reset the passwords of all users on this site and terminate their sessions instantly. Are you sure?', 'ppm-wp' ),
					'resetOwnPasswordMessage'        => __( 'Should the plugin reset your password as well?', 'ppm-wp' ),
				)
			);
		}

		/**
		 * Global admin enqueue scripts.
		 */
		public function global_admin_enqueue_scripts() {
			if ( ppm_is_user_exempted( get_current_user_id() ) ) {
				return;
			}

			// enqueue these scripts and styles before admin_head
			// jquery and jquery-ui should be dependencies, didn't check though.
			if ( ! wp_script_is( 'jquery-ui-dialog', 'queue' ) ) {
				wp_enqueue_script( 'jquery-ui-dialog' );
			}

			if ( ! wp_style_is( 'wp-jquery-ui-dialog', 'queue' ) ) {
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}

			// Global JS.
			wp_enqueue_script( 'ppm-wp-global', PPM_WP_URL . 'admin/assets/js/global.js', array( 'jquery' ), PPMWP_VERSION ); // phpcs:ignore 
			wp_localize_script(
				'ppm-wp-global',
				'ppmwpGlobalStrings',
				array(
					'emailResetInstructions' => __( 'Please check your email for instructions on how to reset your password.', 'ppm-wp' ),
					'shortPasswordMessage'   => __( 'By setting the minimum number of characters in passwords to less than 6 you\'re encouraging weak passwords and polices cannot be enforced. Would you like to proceed?', 'ppm-wp' ),
					'submitOK'               => __( 'OK', 'ppm-wp' ),
					'submitNo'               => __( 'No', 'ppm-wp' ),
				)
			);
			// Check password expired.
			$should_password_expire = \PPMWP\PPM_WP_Expire::should_password_expire( get_current_user_id() );
			$session_setting        = isset( $this->options->ppm_setting->terminate_session_password ) ? $this->options->ppm_setting->terminate_session_password : $this->options->default_setting->terminate_session_password;
			// localize options.
			wp_localize_script(
				'ppm-wp-global',
				'options',
				array(
					'global_ajax_url'            => admin_url( 'admin-ajax.php' ),
					'wp_admin'                   => wp_logout_url( network_admin_url() ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'should_password_expire'     => OptionsHelper::string_to_bool( $should_password_expire ),
				)
			);
		}

		/**
		 * Session expired dialog box.
		 */
		public function admin_footer_session_expired_dialog() {
			?>
			<div id="ppm-wp-dialog" class="hidden" style="max-width:800px">
				<p><?php esc_html_e( 'Your password has expired hence your session is being terminated. Click the button below to receive an email with the reset password link.', 'ppm-wp' ); ?></p>
				<p><?php esc_html_e( 'For more information please contact the WordPress admin on ', 'ppm-wp' ); ?><?php echo esc_url( get_option( 'admin_email' ) ); ?></p>
				<a href="javascript:;" class="button-primary reset"><?php esc_html_e( 'Reset password', 'ppm-wp' ); ?></a>
			</div>
			<div id="reset-all-dialog" class="hidden" style="max-width:800px">
			</div>
			<style>
				a[href="admin.php?page=ppm-upgrade"] {
					color: #ff8977 !important;
				}
			</style>
			<?php
		}

		/**
		 * Get list of all roles.
		 */
		public function search_users_roles() {

			check_admin_referer( 'ppm_wp_settings' );

			$get_array  = filter_input_array( INPUT_GET );
			$search_str = $get_array['search_str'];

			if ( isset( $get_array['action'] ) && 'get_users_roles' !== $get_array['action'] ) {
				die();
			}

			$exclude_users = empty( $get_array['exclude_users'] ) ? false : $this->decode_js_var( $get_array['exclude_users'] );

			$users = $this->search_users( $search_str, $exclude_users );

			echo wp_json_encode( $users );

			die();
		}

		/**
		 * Turns json into usable string.
		 *
		 * @param type $var - Item to decode.
		 * @return type
		 */
		public function decode_js_var( $var ) {
			$var = json_decode( html_entity_decode( stripslashes( $var ), ENT_QUOTES, 'UTF-8' ), true );

			if ( ! is_array( $var ) && ! empty( $var ) ) {
				$var = $this->decode_js_var( $var );
			}

			return $var;
		}

		/**
		 * Seach Users
		 *
		 * @param string $search_str Search string.
		 * @param array  $exclude_users Exclude user array.
		 * @return array
		 */
		public function search_users( $search_str, $exclude_users ) {
			// Search by user fields.
			$args = array(
				'exclude'        => $exclude_users,
				'search'         => '*' . $search_str . '*',
				'search_columns' => array(
					'user_login',
					'user_email',
					'user_nicename',
					'user_url',
					'display_name',
				),
				'fields'         => array(
					'ID',
					'user_login',
				),
			);

			// Search by user meta.
			$meta_args = array(
				'exclude'    => $exclude_users,
				'meta_query' => array( // phpcs:ignore 
					'relation' => 'OR',
					array(
						'key'     => 'first_name',
						'value'   => ".*$search_str",
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => ".*$search_str",
						'compare' => 'LIKE',
					),
				),
				'fields'     => array(
					'ID',
					'user_login',
				),
			);
			// Get users by search keyword.
			$user_query = new \WP_User_Query( $args );
			// Get user by search user meta value.
			$user_query_by_meta = new \WP_User_Query( $meta_args );
			// Merge users.
			$users = $user_query->results + $user_query_by_meta->results;
			// Return found users.
			return $this->format_users( $users );
		}

		/**
		 * User format.
		 *
		 * @param array $users User Object.
		 * @return array
		 */
		public function format_users( $users ) {
			$formatted_users = array();
			foreach ( $users as $user ) {
				$formatted_users[] = array(
					'id'    => $user->ID,
					'value' => $user->user_login,
				);
			}

			return $formatted_users;
		}

		/**
		 * Display custom admin notice.
		 */
		public function admin_save_success_notice() {
			?>

				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Policies updated successfully.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 */
		public function admin_save_error_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Policies update failed. Please try again.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice on form settings.
		 */
		public function admin_at_least_one_form_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Policies update failed, at least one notice should be enabled.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 */
		public function admin_save_error_required_field_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'This setting is mandatory. Please specify a value.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 */
		public function admin_reset_success_notice() {
			?>

				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All passwords were reset.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 */
		public function admin_reset_error_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Resetting passswords failed. Please try again.', 'ppm-wp' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Sends a test email to the logged in user.
		 *
		 * @param array $s - Posted data.
		 */
		public function send_test_email( $s ) {

			// Check if its a valid request.
			check_admin_referer( 'send_test_email' );

			// Checking if request is made by a logged in user or not.
			$current_user = wp_get_current_user();
			if ( ! is_user_logged_in() || ! ( $current_user instanceof \WP_User ) ) {
				wp_send_json_error( array( 'message' => __( 'No user logged in.', 'ppm-wp' ) ) );
			}
			if ( ! isset( $current_user->user_email ) || empty( $current_user->user_email ) ) {
				wp_send_json_error( array( 'message' => __( 'Current user has no email address defined', 'ppm-wp' ) ) );
			}

			// Populating data for email.
			$to      = $current_user->user_email;
			$subject = __( 'Melapress Login Security plugin email test', 'ppm-wp' );
			$message = sprintf(
				__(
					'Hooray!

<p>You received the test email. Now you can <a href="https://www.melapress.com/support/kb/getting-started-wpassword/?utm_source=plugins&utm_medium=link&utm_campaign=mls">enable the password policies</a>.</p>

<p>Thank you for using the Melapress Login Security plugin for WordPress.</p>

',
					'ppm-wp'
				)
			);

			$from_email = $this->options->ppm_setting->from_email ? $this->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );

			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;
			$headers[]  = 'Content-Type: text/html; charset=UTF-8';

			// Errors might be thrown in wp_mail, so handling them beforehand.
			add_action( 'wp_mail_failed', array( $this, 'log_ajax_mail_error' ) );

			// Sending email and returning the status to the ajax request.
			$status = wp_mail( $to, $subject, $message, $headers );
			if ( true === $status ) {
				/* translators: %s: Users email address. */
				wp_send_json_success( array( 'message' => sprintf( __( 'An email was sent successfully to your account email address: %s. Please check your email address to confirm receipt.', 'ppm-wp' ), $to ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'An error occurred while trying to send email, please check if the server is configured to send emails before saving settings', 'ppm-wp' ) ) );
			}
			exit;
		}

		/**
		 * Logging of test mail function errors.
		 *
		 * @param object $error WP_Error Object.
		 */
		public function log_ajax_mail_error( $error ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( is_wp_error( $error ) ) {
					wp_send_json_error( array( 'message' => $error->get_error_message() ) );
				} else {
					wp_send_json_error( array( 'message' => __( 'Mail was not sent due to some unknown error', 'ppm-wp' ) ) );
				}
				exit;
			}
		}

		/**
		 * Update global reset timestamp.
		 */
		public function update_global_reset_timestamp() {
			update_site_option( PPMWP_PREFIX . '_reset_timestamp', current_time( 'timestamp' ) );
		}

		/**
		 * Get global timestamp.
		 *
		 * @return string|int Timestamp.
		 */
		public function get_global_reset_timestamp() {
			return get_site_option( PPMWP_PREFIX . '_reset_timestamp', 0 );
		}

		/**
		 * An easy to use array of allowed HTML for use with sanitzation of our admin areas etc.
		 *
		 * @return array $wp_kses_args - Our args.
		 */
		public function allowed_kses_args() {
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
			return $wp_kses_args;
		}

	}
}
