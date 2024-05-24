<?php
/**
 * PPM_WP Class
 *
 * @package WordPress
 * @subpackage wpassword
 */

if ( ! class_exists( 'PPM_WP' ) ) {

	/**
	 * The core class that loads all the functionality.
	 *
	 * @since 0.1
	 */
	class PPM_WP {

		/**
		 * Password Policy Options.
		 *
		 * @var object instance of PPM_WP_Options
		 */
		public $options;

		/**
		 * Password Policy regex.
		 *
		 * @var object instance of PPM_WP_Regex
		 */
		public $regex;

		/**
		 * Policy Policy Message.
		 *
		 * @var object instance of PPM_WP_Msgs
		 */
		public $msgs;

		/**
		 * Store the single instance.
		 *
		 * @var object instance of PPM_WP
		 * @since 0.1
		 */
		private static $_instance = null; // phpcs:ignore

		/**
		 * Password policy menu icon.
		 *
		 * @var string Icon encode string
		 */
		public $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMzAwIDMwMCI+CiAgICA8aW1hZ2Ugd2lkdGg9IjMwMCIgaGVpZ2h0PSIzMDAiIHhsaW5rOmhyZWY9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBSUFBQUFDQUNBWUFBQUREUG1ITEFBQUFBWE5TUjBJQXJzNGM2UUFBQjU5SlJFRlVlRjd0bmN1clYxVVV4ejg3TGMwb2UyaDJOWVBLeWhkYVlKSDRLZzNUUURUU2FRUkJvQVVOb2tHUFFRV1Y0S0NnbVRRUXlrbU5tbVVONnA4b1p3MGxHam9JUld2RmtuWHJ4KzNlKzl2bm5OOTUvcjVuY2dkM24zUDJYdXZ6VzJ2dHRmZFpPNkZycWlXUXBucjBHandDWU1vaEVBQUNZSmdTTUxNN2dPZUJLOEFQS2FXcnd4eHB0VkVOemdLWTJTM0F5OEFwWUJOd0JqaWRVdnF6bXFpR2VYZnZBVEF6SDhNS1lBMXdISGdUV0R1aXJvK0JUd1RBL0FEM0dnQXpjMFZ2Qmc0Qko0RDE4TC9BVmdBc1lyeDZCNENaTFFFZUFYWUIrNEY5d0xwRnhpZ0FoZ0JBK1BhdHdGRmdML0FFc0RMRE13dUF2Z05nWnY1cmZ4WFlBOHlFejgrMVhnS2dqd0NZMlZMZ09lQmRZQ2ZncGo5WDZhTkRGZ0I5QWNETWxnUDN4Uy85SkxBRHVEbkR6Qy9XUkFCMEdZQ1l4dDBOUEJZQm5VL2x0c2N2dnFMdWI5d3VBTG9JZ0puZEZMLzIzUkhOZTBUdjBmMmtMd0hRTlFETTdQNUkyaHdFdGdVSTd1UHJ1QVJBVndBd3M0MFJ6UjhEVmdPZXJ5OFQyQlVCUlFDMENVQWtianduL3hid0VuQjdFZTFOb0swQWFCb0FNMXNHcklvMDdXdkFZZUMyQ1NpenpDTUVRRk1BbUprcjJhTjVuN2Q3eHM0RHU3cDhleTRNQXFCdUFPelNwUlhNekhoNjlrQk01VHhsZTJ1dWhtcHVKd0RxQXNETVBCZC9KQ0w2TGNBRGdLL0hkK2tTQURVQzhBeHdGdGpRUURSZkZpb0JVQ01BdnVYcVhNemp5eXFvN3ZzRWdBRFFqcUNGR0tpVWhERXpXWUM2N1ZmTnp4Y0FOUXU0NjQ4WEFGM1hVTTM5RXdBTENEaTJvSGtHODRVQ092Z2J1RmFnZmRXbWw0SHpLYVdMWlI4a0FCWUd3TGVhKzI2azk4b0t0NEg3ZmdkZVNTbGRLUHN1QVNBQXlySURRNTRGbUprc3dEZzBCTUE0Q2RYK2Y3bUFEQkdYeWdUS0FtUklWaFlnUTBqMU5wRUZ5SkN2TElEV0FvcXZCY2dGWlB5MDVBSXloRlJ2RTdtQURQbktCY2dGeUFWb09iaGdpUmpGQUJtMlZURkFocERxYmFJWUlFTytpZ0VVQXlnR1VBelFmQXp3VndNZnhjZ0ZkTmdGZkFTOFhmTUhNZ0tnd3dENDExSDNBdC9WK0VHc0FPZzRBTDlHdWRvdmdJY0FMNG94eVVzQVpFaXpyVm5BMXBUU0wyYm1OWTU4Ky95SEV5NTk0ME1YQUYwSHdQc1hHMHo5UytrUGdDY25hQWtFUUI4QUNBaTg3TjFUd0dmeE42UHJZNXNJZ0xFaUtsa2xiQUtwNEJzdVlMUi9VUmpyUWVCcjRPbU12bzlySWdER1NhaHNtYmc2QUpqdHE1bmRCWHdiQlRTcTdNd1dBSDBFSUZ5Q1d3SS95OEEvUENsYlRFTUE5QldBZ01ETDZid1RCVGJLMUZBU0FIMEdJQ0I0T0Nxb2VUSHNvdFZWZWd1QUFiOEIzMlFvc0dxVG40Q2ZVMHFGdnRtTCtidFhRSG0yWkFjK1R5bjlrWE92bVhrRmREL3B4RlBIUlpKRnZRYmdSK0RGSEFGVmJIT3RxUEpIZ2pWUDRwUXRWbjBscGVRZmkyWmRVV0h0OVFoYWZjcVljL1VhZ085VFNrVyt2TTBSU0svYlJIM0ZOeUpybUJNVENJQmVhM3llenB1WlYxTDE0cHBlV2RWTDV5ODJUUlFBUXdNZ0FrT3ZvZXhIMzNsTTRLWDNGcm9Fd0JBQkNBamNCWGh0NVUvbkhJTTNPbVFCTUZRQUFnS2ZGdm9zNUV2QVMrelB2UVRBa0FFSUNIeGE2T3NHWDhXZUFsbUFvU3Q5ZEh5eGdDUUFwa25wczJPTmZRUnlBVk9xZkFXQjA2aDRUUU9uVmVzeGJpV0NwaGlBYVVzRmF6Rm9CUFpwWEF6U2N2Qi9abi9xbG9PYk5QU3RmaGN3YnFCbU5wVWJRc2JKWlpMLzd5d0FacVl0WVpQVTlBTFA2aVFBWnFaTm9RMG8zMS9ST1FDMExid2h6Y2RyT2dPQVBneHBWdkd6YitzRUFHYW1UOFBhMFgvN0xrQWZoN2FrK1M2NEFIMGUzcTd5V3cwQ0FSV0lhRi8vN2JrQWxZanBnUFpibkFhcVNGUTM5TithQlZDWnVDa0hvSW5oOTNaWGNCUENhVHNQME1RWUJVQ0dsTnRLQkdWMHJYSVRBWkFoUWdHd2lKQ3ExS2ZSd1pFWjlOWGNSQllnUThDeUFMSUFLaGUvRUFOeUFRdElaZ0psNGpLTVUrVW1jZ0VaSXBRTGtBdVFDNUFMYVA3RWtBempWTG1KWEVDR0NPVUM1QUxrQXVRQzVBTG1aVURUUUUwRE03em93a0x5bzFET1JUMjc4ZytxOTg2eU1ZQVhhRG9jMWJ6cjZxSHZHYmhlNGVHWGdmTXBwWXRsbnlFTFVGWnlBN2xQQUF4RWtXV0hJUURLU200Zzl3bUFnU2l5N0RBRVFGbkpEZVMrcWdENGdRcG5nUTFqcWxxM0thNVNzNEEyTzl6a3U2c0NzQkk0RW1mZWJJbksxa1dQUGFsN3ZBS2dybFR3N0hQdDBxVVZ6TXpzQlE0QSt3QS9PTG5zU1ZpVEJrSUExQTNBdnlDWWVYVkxMM3V5RXpnYTUrSXRtYlJHQ3o1UEFEUUZ3QWdJeTRCVndPWTQvY0l6YWpsSG9CVFViVlp6QWRBMEFLUHZNek8zQUp2aUNCUS9BTUdQUkdueUVnQnRBakFIaG8yQW40OTNERmdOK05Fb2xRTFJESklFUUZjQUdIRVJmdnJGY2VBZ3NDMFdrK3FLRlFSQTF3RHcva1RCSkQ4VmEzY2NpN0lmZURUakYxMjBpUURvSWdCelhNTTlNWHZ3S2FSYmh1M0FwQ3lDQU9nNkFDT3VZWG00Z3ozQVNXQkhoWk03Wng4ckFQb0N3Qnlyc0JTdTc0ZWw3MGRld1MxQ21ZQlJBUFFSZ0RrdzdJclpnMXNHcjZ5OW9nQU1BcUR2QUVUUTZHc01ubUwyREtPbm5SOEg3c3lJQ0FYQUVBQVlpUlBjRmZqcW8xdUYyYldIZFl1TVVRQU1DWUE1cm1GdHBKc1BBU2VBOWZPNEJnRXdWQURDTlhoZzZESEJtcGhDK3ZIckRvSm1BUm4rc1V4VW5mSFk5cHBFWFY0L2VmdFVyRUdjQVU2bmdoK0d0RGVDWnQ4OE9BQkdZZ1ZmWjNEWGNBVzRrRks2MnF4bysvRzJ3UUxRRC9HMzMwc0IwTDRPV3UyQkFHaFYvTzIvWEFDMHI0TldlL0FQbUczOXZVRk9EMlFBQUFBQVNVVk9SSzVDWUlJPSIvPgogIDwvc3ZnPg==';

		/**
		 * Holds instances of the cron classes in this plugin.
		 *
		 * @var array
		 */
		public $crons;

		/**
		 * Holds an insteance of the InactiveUsers class.
		 *
		 * @var PPMWP\InactiveUsers
		 */
		public $inactive;

		/**
		 * Holds an insteance of the InactiveUsersAjax class.
		 */
		public $ajax;

		/**
		 * Holds an insteance of the PPM_PW_History class.
		 */
		public $history;

		/**
		 * Holds an insteance of the NewUser class.
		 */
		public $new_user;

		/**
		 * Instantiate
		 */
		private function __construct() {

			new \PPMWP\PPM_Apply_Timestamp_For_Users_Process();
			new \PPMWP\PPM_Reset_User_PW_Process();

			$this->register_dependencies();

			$can_continue = true;


			// Check if a user is on a trial or has an activated license that enables premium features.
			if ( $can_continue || $free_plan ) {
				// initialise options.
				$this->options = new \PPMWP\PPM_WP_Options();

				// initialise rule regexes.
				$this->regex = new \PPMWP\PPM_WP_Regex();
				// initialise strings.
				$this->msgs = new \PPMWP\PPM_WP_Msgs();

				// Load plugin's text language files.
				add_action( 'init', array( $this, 'localise' ) );
				// Init.
				add_action( 'init', array( $this, 'init' ) );
				// Admin init.
				add_action( 'admin_init', array( $this, 'ppm_overwrite_admin_menu' ) );

			}


			// Ensure user is sent to reset if needed.
			add_action( 'wp_loaded', array( $this, 'redirect_user_to_forced_pw_reset' ) );
			add_action( 'admin_init', array( $this, 'redirect_user_to_forced_pw_reset' ) );

			// Update user's last activity.
			add_action( 'wp_login', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_logout', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_login_failed', array( $this, 'update_user_last_activity' ) );
			add_action( 'wp_loaded', array( $this, 'register_summary_email_cron' ) );

			$login_control = new \PPMWP\MLS_Login_Page_Control();
			add_action( 'plugins_loaded', array( $login_control, 'is_login_check' ), 9999 );
			add_action( 'wp_loaded', array( $login_control, 'redirect_user' ) );

			if ( class_exists( '\PPMWP\PPM_Failed_Logins' ) ) {
				$failed_logins = new \PPMWP\PPM_Failed_Logins();
				add_action( 'init', array( $failed_logins, 'init' ) );
				add_action( 'wp_login_failed', array( $failed_logins, 'failed_login_check' ), 1, 2 );
				add_action( 'authenticate', array( $failed_logins, 'pre_login_check' ), 20, 3 );
				if ( ! class_exists( '\PPMWP\InactiveUsers' ) ) {
					add_action( 'admin_menu', array( $failed_logins, 'add_locked_users_admin_menu' ), 20, 3 );
				}				
			}

		}


		/**
		 * Registers some dependency classes and files for the plugin.
		 *
		 * @method register_dependencies
		 * @since  2.1.0
		 */
		public function register_dependencies() {
			require_once PPM_WP_PATH . 'app/crons/class-croninterface.php';
			require_once PPM_WP_PATH . 'app/ajax/class-ajaxinterface.php';
			require_once PPM_WP_PATH . 'app/helpers/class-optionshelper.php';
			require_once PPM_WP_PATH . 'app/helpers/class-ppm-emailstrings.php';
			$this->hooks();
		}

		/**
		 * Register the inactive users check crons.
		 *
		 * @method register_cron
		 * @since
		 * @return void
		 */
		public function register_summary_email_cron() {
			require_once PPM_WP_PATH . 'app/crons/class-summaryemail.php';
			// setup the cron for this.
			$this->crons['summary_email'] = new PPMWP\Crons\SummaryEmail( $this );
			$this->crons['summary_email']->register();
		}

		/**
		 * Adds various hooks that are used for the plugin.
		 *
		 * @method hooks
		 * @since  2.1.0
		 */
		public function hooks() {
			// filters allowed special characters, this is run with a late
			// priority so that users can add new characters.
			add_filter( 'ppwmp_filter_allowed_special_chars', array( $this, 'remove_excluded_special_chars_from_allowed' ), 15, 1 );

			$this->history = new \PPMWP\PPM_WP_History();
			add_action( 'user_register', array( $this->history, 'user_register' ) );
			add_action( 'ppmwp_apply_forced_reset_usermeta', array( $this->history, 'ppm_apply_forced_reset_usermeta' ) );

			$this->new_user = new \PPMWP\PPM_New_User_Register();
			add_action( 'wp_login', array( $this->new_user, 'ppm_first_time_login' ), 10, 2 );

			if ( is_admin() ) {
				// Hide all unrelated to the plugin notices on the plugin admin pages.
				add_action( 'admin_print_scripts', array( '\PPMWP\Helpers\HideAdminNotices', 'hide_unrelated_notices' ) );
			}
		}

		/**
		 * Get a list of all default supported special characters.
		 *
		 * @since  2.1.0
		 * @return string
		 */
		public function get_special_chars( $return_escaped = false ) {
			return ( $return_escaped ) ? '[!@#$%^&*()_?£"\\-\\+=~;:€<>\\[\\]]' : '[!@#$%^&*()_?£"-+=~;:€<>]';
		}

		/**
		 * Gets the list of allowed special characters passed through a filter
		 * to remove any characters that are dissallowed via options.
		 *
		 * @method get_allowed_special_chars
		 * @since  2.1.0
		 * @return string
		 */
		public function get_allowed_special_chars() {
			// get list of removed characters from option.
			$allowed_chars = $this->get_special_chars();
			// run characters string through filter where chars can be added/removed.
			$special_chars_string = apply_filters( 'ppwmp_filter_allowed_special_chars', $allowed_chars );
			return $special_chars_string;
		}

		/**
		 * Filter that removes special characters from the allowed list.
		 *
		 * @since  2.1.0
		 * @param  string $chars of allowed characters.
		 * @return string
		 */
		public function remove_excluded_special_chars_from_allowed( $chars ) {
			// get disallowed characters from options. First check user options,
			// then global options and fallback to default.
			$remove_chars = ( isset( $this->options->users_options->rules['exclude_special_chars'] ) && isset( $this->options->users_options->excluded_special_chars ) ) ? $this->options->users_options->excluded_special_chars : '';
			// split the remove string into an array of individual characters.

			if ( $remove_chars ) {
				// Decode $remove_chars so we are stripping out the things we need,
				// not looping through the HTML entity chars.
				$remove_chars_array = str_split( html_entity_decode( $remove_chars ) );
				foreach ( $remove_chars_array as $char ) {
					// remove any chars from the allowed list.
					$chars = str_replace( $char, '', $chars );
				}
			}

			// return a maybe updated list of special chars.
			return $chars;
		}


		/**
		 * Overwrite admin menu URL.
		 */
		public function ppm_overwrite_admin_menu() {
			global $submenu;
			
			if ( isset( $submenu['ppm_wp_settings'] ) ) {
				$menu_index = array_search( 'ppm_wp_settings-pricing', array_column( $submenu['ppm_wp_settings'], 2 ) );
				if ( $menu_index ) {
					$upgrade_menu                              = $submenu['ppm_wp_settings'][ $menu_index ];
					$submenu['ppm_wp_settings'][ $menu_index ] = array_replace( // phpcs:ignore
						$upgrade_menu,
						array_fill_keys(
							array_keys( $upgrade_menu, 'ppm_wp_settings-pricing' ),
							esc_url( 'https://www.melapress.com/wordpress-login-security/pricing/' )
						)
					);
				}

				$help = array_search( 'Help & Contact Us', array_column( $submenu['ppm_wp_settings'], 0 ) );

				/**
				 * Help menu move to last.
				 *
				 * @var $submenu
				 */
				if ( $help ) {
					if ( ! is_multisite() ) {
						$help_menu = $submenu['ppm_wp_settings'][ $help ];

						if ( isset( $submenu['ppm_wp_settings'][9] ) && isset( $submenu['ppm_wp_settings'][10] ) ) {
							$clone                          = $submenu['ppm_wp_settings'];
							$submenu['ppm_wp_settings'][9] = $clone[10]; // phpcs:ignore
							$submenu['ppm_wp_settings'][10] = $clone[9]; // phpcs:ignore
						} else {
							$help_menu = $submenu['ppm_wp_settings'][ $help ];
							unset( $submenu['ppm_wp_settings'][ $help ] );
							$submenu['ppm_wp_settings'][] = $help_menu; // phpcs:ignore	
						}
					} else {
						if ( isset( $submenu['ppm_wp_settings'][5] ) && isset( $submenu['ppm_wp_settings'][6] ) ) {
							$help_menu = $submenu['ppm_wp_settings'][ $help ];
							unset( $submenu['ppm_wp_settings'][ $help ] );
							$submenu['ppm_wp_settings'][] = $help_menu; // phpcs:ignore								
						}
					}
				}
			}
		}

		/**
		 * Initialise
		 */
		public function init() {

			$ppm = ppm_wp();

			$this->options->init();

			$user_settings = $ppm->options->users_options;

			$role_setting = $ppm->options->setting_options;

			if ( null != $user_settings ) {
				$this->msgs->init();
			}

			$this->regex->init();
			// Call password history class.
			$history = new \PPMWP\PPM_WP_History();
			$history->ppm_after_password_reset();

			// Call password expire class.
			$expire = new \PPMWP\PPM_WP_Expire();
			$expire->ppm_authenticate_user();

			// Check change initial password setting is enabled OR not.
			$new_user = new \PPMWP\PPM_New_User_Register();
			$new_user->init();

			$new_user = new \PPMWP\PPM_User_Profile();
			$new_user->init();

			$shortcodes = new \PPMWP\PPM_Shortcodes();
			$shortcodes->init();

			$login_control = new \PPMWP\MLS_Login_Page_Control();
			$login_control->init();

			$settings_import_export = new PPMWP\Helpers\SettingsImporter();
			$settings_import_export->init();


			do_action( 'mls_extension_init' );

			// call ppm history all hook.
			$history->hook();

			$options_master_switch    = \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->options->master_switch );
			$settings_master_switch   = \PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = \PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			// Enable all features only if policy switch is enabled.
			if ( $is_needed ) {

				if ( ! \PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->enforce_password ) ) {

					$pwd_check = new \PPMWP\PPM_WP_Password_Check();

					$pwd_check->hook();

					$pwd_gen = new \PPMWP\PPM_WP_Password_Gen();

					$pwd_gen->hook();

					$forms = new \PPMWP\PPM_WP_Forms();

					$forms->hook();

					// call ppm expire all hook.
					$expire->hook();

					$reset = new \PPMWP\PPM_WP_Reset();

					$reset->hook();
				}
			}

			if ( ! is_multisite() ) {
				$admin = new PPMWP\Admin\PPM_WP_Admin( $this->options, $user_settings, $role_setting );
			} else {
				$admin = new PPMWP\Admin\PPM_WP_MS_Admin( $this->options, $user_settings, $role_setting );
			}
		}

		/**
		 * Standard singleton pattern.
		 *
		 * @return PPM_WP Returns the current plugin instance.
		 */
		public static function _instance() {
			if ( is_null( self::$_instance ) || ! ( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		/**
		 * Checks if a user is exempted from the policies.
		 *
		 * @param integer $user_id User ID.
		 * @return boolean
		 */
		public static function is_user_exempted( $user_id = false ) {

			$ppm = ppm_wp();

			// if no user is supplied, assume they are not exempted.
			if ( false === $user_id ) {
				return false;
			}

			if ( isset( $ppm->options->ppm_setting->exempted['users'] ) && ! empty( $ppm->options->ppm_setting->exempted['users'] ) ) {

				// check if this particular user is exempted.
				if ( in_array( $user_id, $ppm->options->ppm_setting->exempted['users'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Load plugin textdomain.
		 */
		public function localise() {
			load_plugin_textdomain( 'ppm-wp', false, dirname( PPM_WP_BASENAME ) . '/languages/' );
		}

		/**
		 * Create activation timestamp
		 */
		public static function activation_timestamp() {
			update_site_option( PPMWP_PREFIX . '_activation', current_time( 'timestamp' ) );
			self::ppm_multisite_install_plugin();
		}

		/**
		 * Deactivate plugin.
		 */
		public static function ppm_deactivation() {
			self::cleanup();
		}

		/**
		 * Clean up data
		 */
		public static function cleanup() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			if ( $ppm_setting ) {
				$clear_up_needed = isset( $ppm_setting['clear_history'] ) && ( 'yes' === $ppm_setting['clear_history'] || 1 === $ppm_setting['clear_history'] );

				if ( $clear_up_needed ) {
					self::clear_options();
					self::clear_history();
					self::clear_usermeta();
				}
			}
		}

		/**
		 * Delete both options
		 */
		public static function clear_options() {
			global $wp_roles;
			$roles = $wp_roles->get_names();

			// delete all role options.
			foreach ( $roles as $key => $role ) {
				delete_site_option( PPMWP_PREFIX . '_' . $key . '_options' );
			}

			delete_site_option( PPMWP_PREFIX . '_options' );
			delete_site_option( PPMWP_PREFIX . '_setting' );
			delete_site_option( PPMWP_PREFIX . '_activation' );
			delete_site_option( PPMWP_PREFIX . '_reset_timestamp' );
			delete_site_option( 'ppmwp_plugin_version' );
			delete_site_option( 'ppmwp_prefixes_updated' );
			delete_site_option( 'ppmwp_inactive_users' );
		}

		/**
		 * Clear history
		 */
		public static function clear_history() {
			$args = array(
				'fields' => array( 'ID' ),
			);

			if ( ! is_multisite() ) {
				self::clear_user_history( $args );
			} else {
				self::clear_ms_history( $args );
			}

		}

		/**
		 * Clear User meta
		 */
		public static function clear_usermeta() {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", array( 'ppmwp_%' ) ) );
		}

		/**
		 * Clear History in multisite.
		 *
		 * @param string|array $args History clear arguments.
		 */
		public static function clear_ms_history( $args ) {
			// Specify a large number so we get more than 100 sites.
			$sites_args = array(
				'number' => 10000,
			);
			$sites      = get_sites( $sites_args );

			foreach ( $sites as $site ) {

				switch_to_blog( $site->blog_id );

				$args['blog_id'] = $site->blog_id;

				self::clear_user_history( $args );

				restore_current_blog();
			}
		}

		/**
		 * Clear user history for one site
		 *
		 * @param array $args User query.
		 */
		public static function clear_user_history( $args ) {

			$users = get_users( $args );

			foreach ( $users as $user ) {
				delete_user_meta( $user->ID, PPM_WP_META_KEY );
			}
		}

		/**
		 * Destroy user session.
		 *
		 * @param  int $user_id User ID.
		 */
		public function ppm_user_session_destroy( $user_id ) {
			// get all sessions for user with ID $user_id.
			$sessions = WP_Session_Tokens::get_instance( $user_id );
			// we have got the sessions, destroy them all!
			$sessions->destroy_all();
		}

		/**
		 * Get user by blog ID.
		 *
		 * @param  integer $blog_id     WordPress site ID.
		 * @param  array   $extra_query User query.
		 * @return object|array
		 */
		public function ppm_mu_user_by_blog_id( $blog_id = 0, $extra_query = array() ) {
			// Default query for get blog users.
			$user_query = array(
				'blog_id' => $blog_id,
			);
			// Merge custom query.
			$user_query = array_merge( $user_query, $extra_query );
			// Return user object.
			return get_users( $user_query );
		}

		/**
		 * Get user blog by user ID.
		 *
		 * @param  integer $user_id The id of user to work with.
		 * @return Object|bool Defalut 0
		 */
		public function ppm_mu_get_blog_by_user_id( $user_id = 0 ) {
			$blog_info = get_active_blog_for_user( $user_id );
			// If check user blog object.
			if ( $blog_info ) {
				return (int) $blog_info->blog_id;
			}
			return 0;
		}

		/**
		 * Multisite installation.
		 */
		public static function ppm_multisite_install_plugin() {
			$installation_errors = false;
			// If check multisite and network admin.
			if ( is_multisite() && is_super_admin() && ! is_network_admin() ) {
				$installation_errors  = esc_html__( 'The Melapress Login Security plugin is a multisite network tool. Please activate it from the network dashboard.', 'ppm-wp' );
				$installation_errors .= '<br />';
				$installation_errors .= '<a href="javascript:;" onclick="window.top.location.href=\'' . esc_url( network_admin_url( 'plugins.php' ) ) . '\'">' . esc_html__( 'Redirect me to the network dashboard', 'ppm-wp' ) . '</a> ';
			}
			if ( $installation_errors ) {
				?>
				 <html>
				 <head><style>body{margin:0;}.warn-icon-tri{top:7px;left:5px;position:absolute;border-left:16px solid #FFF;border-right:16px solid #FFF;border-bottom:28px solid #C33;height:3px;width:4px}.warn-icon-chr{top:10px;left:18px;position:absolute;color:#FFF;font:26px Georgia}.warn-icon-cir{top:4px;left:0;position:absolute;overflow:hidden;border:6px solid #FFF;border-radius:32px;width:34px;height:34px}.warn-wrap{position:relative;font-size:13px;font-family:sans-serif;padding:6px 48px;line-height:1.4;}</style></head>
 				<body><div class="warn-wrap"><div class="warn-icon-tri"></div><div class="warn-icon-chr">!</div><div class="warn-icon-cir"></div><span><?php echo $installation_errors; // @codingStandardsIgnoreLine ?></span></div></body>
				 </html>
				<?php
				die( 1 );
			}
		}

		/**
		 * Applies activation timestamp to user meta.
		 *
		 * @return void
		 */
		public static function ppm_apply_timestammp_for_users() {

			// Send users for bg processing later.
			$total_users = count_users();
			$batch_size  = 100;
			$slices      = ceil( $total_users['total_users'] / $batch_size );
			$users       = array();

			for ( $count = 0; $count < $slices; $count++ ) {
				$args  = array(
					'number' => $batch_size,
					'offset' => $count * $batch_size,
					'fields' => array( 'ID' ),
				);
				$users = get_users( $args );

				if ( ! empty( $users ) ) {
					$background_process = new \PPMWP\PPM_Apply_Timestamp_For_Users_Process();
					$background_process->push_to_queue( $users );
				}

				$background_process->save()->dispatch();
			}

		}

		/**
		 * Return users who are required to reset their password, back to the reset form.
		 */
		public function redirect_user_to_forced_pw_reset() {
			/* Ensure we dont redirect ajax requests for generating passwords. */
			if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'generate-password' === $_REQUEST['action'] || isset( $_REQUEST['action'] ) && 'rp' === $_REQUEST['action'] || isset( $_POST['rp_key'] ) ) {
				return;
			}

			$user = wp_get_current_user();

			// Get user reset key.
			$reset            = new \PPMWP\PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'reset-on-login' );

			// If check reset key exists OR not.
			if ( $verify_reset_key ) {
				$this->handle_user_redirection( $verify_reset_key );
			}
		}


		/**
		 * Simple handler to perform redirection where needed.
		 *
		 * @param Object  $verify_reset_key - Users reset key.
		 * @param boolean $send_json_after - Send json when done.
		 * @param boolean $exit_on_over - Exit or die.
		 * @return void
		 */
		public function handle_user_redirection( $verify_reset_key, $send_json_after = false, $exit_on_over = false ) {

			if ( $verify_reset_key ) {
				$redirect_to = add_query_arg(
					array(
						'action' => 'rp',
						'key'    => $verify_reset_key->reset_key,
						'login'  => rawurlencode( $verify_reset_key->user_login ),
					),
					network_site_url( 'wp-login.php' )
				);
				if ( $send_json_after ) {
					wp_send_json_success(
						array(
							'success'  => true,
							'redirect' => $redirect_to,
						)
					);
				} else {
					wp_safe_redirect( $redirect_to );
					if ( $exit_on_over ) {
						exit;
					} else {
						die;
					}
				}
			}

		}

		/**
		 * Update the users last activity
		 *
		 * @param  int|string $user - User for which to update.
		 * @return void
		 */
		public function update_user_last_activity( $user ) {

			if ( is_int( $user ) ) {
				$user = get_user_by( 'id', $user );
			} elseif ( is_string( $user ) ) {
				// If user is using an email, act accordingly.
				if ( filter_var( $user, FILTER_VALIDATE_EMAIL ) ) {
					$user = get_user_by( 'email', $user );
				} else {
					$user = get_user_by( 'login', $user );
				}
			} else {
				$user = wp_get_current_user();
			}

			if ( isset( $user->ID ) ) {
				if ( method_exists( 'PPMWP\Helpers\OptionsHelper', 'is_user_inactive' ) ) {
					// Check if user is already handled by our inactivity feature.
					$is_user_inactive = \PPMWP\Helpers\OptionsHelper::is_user_inactive( $user->ID );
					if ( ! $is_user_inactive ) {
						// Apply last active time.
						update_user_meta( $user->ID, 'ppmwp_last_activity', current_time( 'timestamp' ) );
					}
				} else {
					update_user_meta( $user->ID, 'ppmwp_last_activity', current_time( 'timestamp' ) );
				}
			}
		}

		/**
		 * Generates system info panel.
		 */
		public function get_sysinfo() {
			// System info.
			global $wpdb;

			$sysinfo = '### System Info → Begin ###' . "\n\n";

			// Start with the basics...
			$sysinfo .= '-- Site Info --' . "\n\n";
			$sysinfo .= 'Site URL (WP Address):    ' . site_url() . "\n";
			$sysinfo .= 'Home URL (Site Address):  ' . home_url() . "\n";
			$sysinfo .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

			// Get theme info.
			$theme_data   = wp_get_theme();
			$theme        = $theme_data->Name . ' ' . $theme_data->Version; // phpcs:ignore
			$parent_theme = $theme_data->Template; // phpcs:ignore
			if ( ! empty( $parent_theme ) ) {
				$parent_theme_data = wp_get_theme( $parent_theme );
				$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version; // phpcs:ignore
			}

			// Language information.
			$locale = get_locale();

			// WordPress configuration.
			$sysinfo .= "\n" . '-- WordPress Configuration --' . "\n\n";
			$sysinfo .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
			$sysinfo .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
			$sysinfo .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
			$sysinfo .= 'Active Theme:             ' . $theme . "\n";
			if ( $parent_theme !== $theme ) {
				$sysinfo .= 'Parent Theme:             ' . $parent_theme . "\n";
			}
			$sysinfo .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

			// Only show page specs if frontpage is set to 'page'.
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$front_page_id = (int) get_option( 'page_on_front' );
				$blog_page_id  = (int) get_option( 'page_for_posts' );

				$sysinfo .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
				$sysinfo .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
			}

			$sysinfo .= 'ABSPATH:                  ' . ABSPATH . "\n";
			$sysinfo .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$sysinfo .= 'WP Memory Limit:          ' . WP_MEMORY_LIMIT . "\n";

			// Get plugins that have an update.
			$updates = get_plugin_updates();

			// Must-use plugins.
			// NOTE: MU plugins can't show updates!
			$muplugins = get_mu_plugins();
			if ( count( $muplugins ) > 0 ) {
				$sysinfo .= "\n" . '-- Must-Use Plugins --' . "\n\n";

				foreach ( $muplugins as $plugin => $plugin_data ) {
					$sysinfo .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
				}
			}

			// WordPress active plugins.
			$sysinfo .= "\n" . '-- WordPress Active Plugins --' . "\n\n";

			$plugins        = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( ! in_array( $plugin_path, $active_plugins ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			// WordPress inactive plugins.
			$sysinfo .= "\n" . '-- WordPress Inactive Plugins --' . "\n\n";

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array( $plugin_path, $active_plugins ) ) {
					continue;
				}

				$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			if ( is_multisite() ) {
				// WordPress Multisite active plugins.
				$sysinfo .= "\n" . '-- Network Active Plugins --' . "\n\n";

				$plugins        = wp_get_active_network_plugins();
				$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

				foreach ( $plugins as $plugin_path ) {
					$plugin_base = plugin_basename( $plugin_path );

					if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
						continue;
					}

					$update   = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
					$plugin   = get_plugin_data( $plugin_path );
					$sysinfo .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
				}
			}

			// Server configuration.
			$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			$sysinfo        .= "\n" . '-- Webserver Configuration --' . "\n\n";
			$sysinfo        .= 'PHP Version:              ' . PHP_VERSION . "\n";
			$sysinfo        .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";

			if ( isset( $server_software ) ) {
				$sysinfo .= 'Webserver Info:           ' . $server_software . "\n";
			} else {
				$sysinfo .= 'Webserver Info:           Global $_SERVER array is not set.' . "\n";
			}

			// PHP configs.
			$sysinfo .= "\n" . '-- PHP Configuration --' . "\n\n";
			$sysinfo .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
			$sysinfo .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
			$sysinfo .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
			$sysinfo .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
			$sysinfo .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
			$sysinfo .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

			$sysinfo .= "\n" . '-- PPMWP Settings  --' . "\n\n";

			$ppm_options = $this->options->ppm_setting;

			if ( ! empty( $ppm_options ) ) {
				foreach ( $ppm_options as $option => $value ) {
					$sysinfo .= 'Option: ' . $option . "\n";
					$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n"; // phpcs:ignore 
				}
			}

			$sysinfo .= "\n" . '-- PPMWP Role Options  --' . "\n\n";

			$roles_obj = wp_roles();

			foreach ( $roles_obj->role_names as $role ) {
				$role_options = \PPMWP\Helpers\OptionsHelper::get_role_options( $role );
				$sysinfo     .= "\n" . '-- ' . $role . '  --' . "\n\n";
				if ( ! empty( $role_options ) ) {
					foreach ( $role_options as $option => $value ) {
						$sysinfo .= 'Option: ' . $option . "\n";
						$sysinfo .= 'Value: ' . print_r( $value, true ) . "\n\n"; // phpcs:ignore
					}
				}
			}

			$sysinfo .= "\n" . '### System Info → End ###' . "\n\n";

			return $sysinfo;
		}

	}
}
