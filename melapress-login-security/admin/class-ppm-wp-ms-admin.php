<?php
/**
 * WPassword Multisite Support class.
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP\Admin;

use \PPMWP\Utilities\ValidatorFactory as ValidatorFactory; // phpcs:ignore
use \PPMWP\Helpers\OptionsHelper as OptionsHelper; // phpcs:ignore

if ( ! class_exists( '\PPMWP\Admin\PPM_WP_MS_Admin' ) ) {

	/**
	 * PPM_WP_MS_Admin extend to PPM_WP_Admin class.
	 */
	class PPM_WP_MS_Admin extends \PPMWP\Admin\PPM_WP_Admin {

		/**
		 * Class construct.
		 *
		 * @param array|object $options PPM options.
		 * @param array|object $settings PPM setting options.
		 * @param array|object $setting_options Get current role option.
		 */
		public function __construct( $options, $settings, $setting_options ) {

			$this->options = $options;

			$this->settings = $settings;

			$this->setting_tab = $setting_options;

			add_filter( 'network_admin_plugin_action_links_' . PPM_WP_BASENAME, array( $this, 'plugin_action_links' ), 10, 1 );

			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'wp_ajax_get_users_roles', array( $this, 'search_users_roles' ) );
			add_action( 'wp_ajax_ppm_wp_send_test_email', array( $this, 'send_test_email' ) );
			add_action( 'wp_ajax_mls_process_reset', array( '\PPMWP\PPM_WP_Reset', 'process_global_password_reset' ) );
			
			// Add dialog box.
			add_action( 'admin_footer', array( $this, 'admin_footer_session_expired_dialog' ) );
			add_action( 'admin_footer', array( $this, 'popup_notices' ) );

			$options_master_switch    = OptionsHelper::string_to_bool( $this->options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( $this->settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( $this->settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {
				// Enqueue admin scripts.
				if ( OptionsHelper::string_to_bool( $this->settings->enforce_password ) ) {
					return;
				}
				add_action( 'admin_enqueue_scripts', array( $this, 'global_admin_enqueue_scripts' ) );
			}
		}

		/**
		 * Register admin menu.
		 */
		public function admin_menu() {
			// Add admin menu page.
			$hook_name = add_menu_page( __( 'Login Security Policies', 'ppm-wp' ), __( 'Login Security', 'ppm-wp' ), 'manage_network_options', PPMWP_MENU_SLUG, array( $this, 'screen' ), 'data:image/svg+xml;base64,' . ppm_wp()->icon, 99 );

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
				),
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
		 * Network admin notice.
		 *
		 * @param string $function Callback function.
		 */
		public function notice( $function ) {
			add_action( 'network_admin_notices', array( $this, $function ) );
		}

		/**
		 * Search User
		 *
		 * @param string $search_str Search string.
		 * @param array  $exclude_users Exclude user array.
		 * @return array
		 */
		public function search_users( $search_str, $exclude_users ) {
			// Search by user fields.
			$args = array(
				'blog_id'        => 0,
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
				'blog_id'    => 0,
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

	}

}
