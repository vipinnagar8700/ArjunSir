<?php
/**
 * Plugin Name: WooCommerce Shop as Customer
 * Plugin URI: https://codecanyon.net/item/shop-as-customer-for-woocommerce/7043722?ref=cxThemes&utm_source=shop%20as%20customer&utm_campaign=commercial%20plugin%20upsell&utm_medium=plugins%20page%20view%20details
 * Description: Shop as Customer allows a store Administrator or Shop Manager to shop the front-end of the store as another User, allowing all functionality such as, order creation, checkout and plugins that only work on the product or cart pages and not the Admin Order page, to function normally as if they were that Customer.
 * Author: cxThemes
 * Author URI: https://codecanyon.net/item/shop-as-customer-for-woocommerce/7043722?ref=cxThemes&utm_source=shop%20as%20customer&utm_campaign=commercial%20plugin%20upsell&utm_medium=plugins%20page%20view%20details
 * Version: 2.16
 *
 * WC requires at least: 3
 * WC tested up to: 3.4.5
 *
 * Text Domain: shop-as-customer
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    cxThemes
 * @category  WooCommerce, WordPress
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define Constants
 */
define( 'WC_SHOP_AS_CUSTOMER_VERSION', '2.16' );
define( 'WC_SHOP_AS_CUSTOMER_REQUIRED_WOOCOMMERCE_VERSION', 3 );
define( 'WC_SHOP_AS_CUSTOMER_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WC_SHOP_AS_CUSTOMER_URI', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WC_SHOP_AS_CUSTOMER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // woocommerce-email-control/ec-email-control.php

/**
 * Update Check
 */
require 'includes/updates/cxthemes-plugin-update-checker.php';
$wc_shop_as_customer_update = new CX_Shop_As_Customer_Plugin_Update_Checker( __FILE__, 'woocommerce-shop-as-customer' );

/**
 * Check if WooCommerce is active, and is required WooCommerce version.
 */
if ( ! WC_Shop_As_Customer::is_woocommerce_active() || version_compare( get_option( 'woocommerce_version' ), WC_SHOP_AS_CUSTOMER_REQUIRED_WOOCOMMERCE_VERSION, '<' ) ){
	add_action( 'admin_notices', array( 'WC_Shop_As_Customer', 'woocommerce_inactive_notice' ) );
	return;
}

/**
 * Includes
 */
include_once( 'includes/settings.php' );
include_once( 'includes/checkout-functions.php' );
include_once( 'includes/helpers.php' );

/**
 * Instantiate plugin.
 */
$wc_shop_as_customer = WC_Shop_As_Customer::get_instance();

/**
 * Main Class.
 */
class WC_Shop_As_Customer {

	private $id = 'woocommerce_shop_as_customer';

	private static $instance;

	/**
	* Get Instance creates a singleton class that's cached to stop duplicate instances
	*/
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	* Construct empty on purpose
	*/
	private function __construct() {}

	/**
	* Init behaves like, and replaces, construct
	*/
	public function init(){
		
		// Translations
		add_action( 'init', array( $this, 'load_translation' ) );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		
		// Add Shop as Customer to WooCommerce submenu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'shop_as_customer_order_notice' ) );
		}
	}

	/**
	 * Add actions and hooks after plugins have been loaded
	 */
	public function plugins_loaded() {
		
		// Define constants.
		if ( ! defined( 'OLD_USER_COOKIE' ) )
			define( 'OLD_USER_COOKIE', 'wordpress_original_user_' . COOKIEHASH );
		if ( ! defined( 'SWITCHED_USERS_COOKIE' ) )
			define( 'SWITCHED_USERS_COOKIE', 'wordpress_switched_users_' . COOKIEHASH );
		
		// Get minimum required user role.
		$shop_as_user_role = get_option( 'shop_as_user_role', 'shop_manager' );
		
		// Only initialize if we're minimum required role, or in a swicthed state.
		if ( $this->is_switched() || self::current_user_is_equal_or_higher_than( $shop_as_user_role ) ) {
			
			add_action( 'wp_ajax_nopriv_woocommerce_checkout', 'cxsac_checkout', 1 );
			add_action( 'wc_ajax_checkout', 'cxsac_checkout', 1 );
			
			// Enqueue switch-ux scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 90 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 90 );

			// Required functionality:
			add_filter( 'user_has_cap', array( $this, 'filter_customer_has_cap' ), 10, 3 );
			add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 10, 4 );
			
			add_filter( 'user_row_actions', array( $this, 'filter_customer_row_actions' ), 10, 2 );
			add_action( 'init', array( $this, 'action_init' ) );
			add_action( 'wp_logout', array( $this, 'clear_original_user_cookie' ) );
			add_action( 'wp_logout', array( $this, 'clear_previous_switched_cookie' ) );
			// add_action( 'wp_login', array( $this, 'clear_original_user_cookie' ) );
			// add_action( 'wp_login', array( $this, 'clear_previous_switched_cookie' ) );

			add_filter( 'ms_user_row_actions', array( $this, 'filter_customer_row_actions' ), 10, 2 );
			add_filter( 'login_message', array( $this, 'filter_login_message' ), 1 );
			add_action( 'personal_options', array( $this, 'action_personal_options' ) );
			add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );
			
			if ( ! $this->is_switched() ) {
				
				/**
				 * Not Switched
				 */
				
				// Output switch modal content.
				add_action( 'in_admin_footer', array( $this, 'output_switch_interface' ) );
				add_action( 'wp_footer', array( $this, 'output_switch_interface' ) );
			}
			else {
				
				/**
				 * Switched
				 */
				
				// Output switched bar.
				add_action( 'wp_footer', array( $this, 'output_switched_bar' ) );
				add_action( 'admin_footer', array( $this, 'output_switched_bar' ) );
			}
		}
	}

	/**
	 * Localization
	 */
	public function load_translation() {

		// Domain ID - used in eg __( 'Text', 'pluginname' )
		$domain = 'shop-as-customer';
		
		// get the languages locale eg 'en_US'
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		
		// Look for languages here: wp-content/languages/pluginname/pluginname-en_US.mo
		load_textdomain( $domain, WP_LANG_DIR . "/{$domain}/{$domain}-{$locale}.mo" ); // Don't mention this location in the docs - but keep it for legacy.
		
		// Look for languages here: wp-content/languages/plugins/pluginname-en_US.mo
		load_textdomain( $domain, WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo" );
		
		// Look for languages here: wp-content/languages/pluginname-en_US.mo
		load_textdomain( $domain, WP_LANG_DIR . "/{$domain}-{$locale}.mo" );
		
		// Look for languages here: wp-content/plugins/pluginname/languages/pluginname-en_US.mo
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . "/languages/" );
	}

	/**
	 * Include admin scripts
	 */
	public function enqueue_scripts() {
		global $screen;
		
			
		/**
		 * Frontend & Backend.
		 */
		
		// SAC.
		wp_enqueue_style(
			'woocommerce-shop-as-customer',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/css/shop-as-customer-styles.css',
			array(),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		wp_enqueue_script(
			'woocommerce-shop-as-customer',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/js/shop-as-customer.js',
			array( 'jquery', 'wc-enhanced-select' ),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		wp_localize_script( 'woocommerce-shop-as-customer', 'woocommerce_shop_as_customer_params', array(
			'shop_as_url'      => add_query_arg(
				array(
					'action' => 'switch_to_customer',
					'_wpnonce' => wp_create_nonce( 'shop_as_customer_nonce' ),
				),
				wp_login_url()
			),
			'ajax_url'         => preg_replace("(^https?:)", '', admin_url('admin-ajax.php') ),
			'nonce'            => wp_create_nonce( 'search-customers' ),
			'autofocus_search' => cxsac_get_option( 'cxsac_autofocus_customer_search' ),
		) );
		
		// Tip-Tip - tooltip plugin.
		wp_enqueue_style(
			'cxsac-tip-tip',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/js/tip-tip/tipTip.css',
			array(),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		wp_enqueue_script(
			'cxsac-tip-tip',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/js/tip-tip/jquery.tipTip.minified.js',
			array( 'jquery' ),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		
		// Fontello.
		wp_enqueue_style(
			'cxsac-icon-font',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/fontello/css/cxsac-icon-font.css',
			array(),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		
		// Select-2
		wp_enqueue_script(
			'woocommerce-shop-as-customer-select-2-script',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/js/select2/select2.min.js',
			array( 'jquery' ),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		wp_enqueue_style(
			'woocommerce-shop-as-customer-select-2-style',
			WC_SHOP_AS_CUSTOMER_URI . '/assets/js/select2/select2.min.css',
			array(),
			WC_SHOP_AS_CUSTOMER_VERSION
		);
		
		
		/**
		 * Checkout Pages (only. Checkout & Thank You Pages).
		 */
		
		if ( is_checkout() ) {
			
			wp_enqueue_script(
				'woocommerce-shop-as-customer-checkout',
				WC_SHOP_AS_CUSTOMER_URI . '/assets/js/shop-as-customer-checkout.js',
				array( 'jquery' ),
				WC_SHOP_AS_CUSTOMER_VERSION
			);
		}
		
		
		/**
		 * Settings Page (only).
		 */
		
		if (
				is_admin() &&
				isset( $screen->id ) &&
				'settings_page_shop_as_customer_settings' == $screen->id
			) {
			
			// Settings Page.
			wp_enqueue_style(
				'woocommerce-shop-as-customer-options',
				WC_SHOP_AS_CUSTOMER_URI . '/assets/css/shop-as-customer-settings.css',
				array(),
				WC_SHOP_AS_CUSTOMER_VERSION
			);
		}
	}
	
	function admin_menu() {
		
		// Get minimum required user role.
		$shop_as_user_role = get_option( 'shop_as_user_role', 'shop_manager' );
		
		if ( self::current_user_is_equal_or_higher_than( $shop_as_user_role ) ) {
			
			add_submenu_page(
				'woocommerce',
				__( 'Shop as Customer', 'shop-as-customer' ),
				__( 'Shop as Customer', 'shop-as-customer' ),
				'manage_woocommerce', // 'manage_networks',
				'shop_as_customer_button',
				'cxsac_admin_page'
			);
		}
	}
	
	// Moved from functions files...
	
	/**
	 * Switches the current logged in user to the specified user.
	 *
	 * @param   int    $switch_to_user_id    The ID of the user to switch to.
	 * @param   bool   $remember             Whether to 'remember' the user in the form of a persistent browser cookie. Optional.
	 * @param   bool   $switch_direction   Whether to set the old user cookie. Optional.
	 * @return  bool                         True on success, false on failure.
	 */
	function switch_to_customer( $switch_to_user_id, $remember = false, $switch_direction = 'switch_to_customer' ) {
		
		// Bail if the destination user does not exsist.
		if ( ! $switch_to_user = get_userdata( $switch_to_user_id ) )
			return false;

		// Bail if the destination user is more powerful than the current user.
		// Only if we are not in a switched state.
		if ( ! $this->is_switched() ) {
			$current_user_id = get_current_user_id();
			if (
					// First make 100% sure we can find the capabilites of the main user.
					self::get_user_role_order( $current_user_id ) >= 4 ||
					
					// Then do the check if the destination user is more powerful than the current user.
					self::get_user_role_order( $current_user_id ) > self::get_user_role_order( $switch_to_user_id )
				) {
				return false;
			}
		}
		
		
		// Debug logging
		// _log( get_current_user_id() );
		// _log( WC()->session->get('cart') );
		// _log( get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' ), TRUE );
		
		
		/*
		WC()->cart->persistent_cart_update(); // Removed - Save session-cart to users persistent-cart so it can be retrieved when switched back.
		WC()->cart->set_session(); // Removed
		WC()->session->set( 'cart', null ); // Removed
		*/
		
		
		// Empty the cart, so that switching automatically laods the cart from `_woocommerce_persistent_cart` user meta.
		// Old Method
		/*if ( isset( WC()->cart ) && method_exists( WC()->cart, 'empty_cart' ) ) {
			WC()->cart->empty_cart( false );
		}*/
		// New Method.
		if ( function_exists( 'wc_empty_cart' ) ) {
			wc_empty_cart();
		}
		
		
		if ( 'switch_to_customer' == $switch_direction && is_user_logged_in() ) {
			
			/**
			 * Switching: To
			 */
			
			$switch_from_user_id = get_current_user_id();
			self::set_original_user_cookie( $switch_from_user_id );
		}
		else {
			
			/**
			 * Switching: Back
			 */
			
			$switch_from_user_id = get_current_user_id();
			self::set_previous_switched_cookie( $switch_from_user_id, $switch_to_user_id );
			self::clear_original_user_cookie();
		}
		
				
		// Log in the new user.
		wp_clear_auth_cookie();
		wp_set_auth_cookie( $switch_to_user_id, $remember );
		wp_set_current_user( $switch_to_user_id, $switch_to_user->user_login );
		do_action( 'wp_login', $switch_to_user->user_login, $switch_to_user ); // WordPress does this so let us do it too (incase plugins do unique actions on user login, then we do too).
		
		
		// Testing new method - not used.
		// Recalc the totals, on the fly, so the JS updates the inline mini-carts etc.
		/*WC()->session->set( 'refresh_totals', true ); // Flag totals for a refresh.
		if ( isset( WC()->cart ) && method_exists( WC()->cart, 'calculate_totals' ) ) {
			// s( WC()->cart );
			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}
			WC()->cart->calculate_totals(); // Recalculate the totals.
		}*/
		
		
		// exit();
		
		
		/*
		WC()->cart->get_cart_from_session(); // Removed (this gets done automatically) - The cart is empty so now get_cart_from_session() will load persistent-cart.
		WC()->cart->persistent_cart_destroy(); // Removed (this should not be done, it should be left there ) - Remove the persistent cart because we've finished using it.
		*/
		
		
		// Debug logging
		// _log( get_current_user_id() );
		// _log( WC()->session->get('cart') );
		// _log( get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' ), TRUE );
		
		
		if ( 'to' == $switch_direction && is_user_logged_in() ) {
			do_action( 'shop_as_customer', $switch_to_user_id, $switch_from_user_id );
		}
		else {
			do_action( 'switch_back_user', $switch_to_user_id, $switch_from_user_id );
		}
		
		
		// Successful.
		return true;
	}
	
	/**
	 * Sets an authorisation cookie containing the originating user, or appends it if there's more than one.
	 *
	 * @param int $original_user_id The ID of the originating user, usually the current logged in user.
	 * @return null
	 */
	function set_original_user_cookie( $original_user_id ) {
		$expiration = time() + 172800; // 48 hours
		$cookie = wp_generate_auth_cookie( $original_user_id, $expiration, 'original_user' );
		setcookie( OLD_USER_COOKIE, json_encode( $cookie ), $expiration, COOKIEPATH, COOKIE_DOMAIN, false );
	}

	/**
	 * Clears the cookie containing the originating user.
	 */
	function clear_original_user_cookie() {
		setcookie( OLD_USER_COOKIE, '', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Gets the value of the cookie containing the list of originating users.
	 *
	 * @return array Array of originating user authentication cookies. @see wp_generate_auth_cookie()
	 */
	public static function get_original_user_cookie() {
		if ( isset( $_COOKIE[OLD_USER_COOKIE] ) )
			return json_decode( stripslashes( $_COOKIE[OLD_USER_COOKIE] ) );
		else
			return FALSE;
	}

	/**
	 * Gets the value of the cookie containing the list of switched users.
	 *
	 * @return array Array of originating user authentication cookies. @see wp_generate_auth_cookie()
	 */
	public static function get_previous_switched_cookie() {
		if ( isset( $_COOKIE[SWITCHED_USERS_COOKIE] ) )
			$cookie = json_decode( stripslashes( $_COOKIE[SWITCHED_USERS_COOKIE] ) );
		if ( ! isset( $cookie ) || ! is_array( $cookie ) )
			$cookie = array();
		return $cookie;
	}

	/**
	 * Sets an authorisation cookie containing the previous user switched, or appends it if there's more than one.
	 *
	 * @param int $original_user_id The ID of the originating user, usually the current logged in user.
	 * @return null
	 */
	public static function set_previous_switched_cookie( $from_user_id, $to_user_id ) {
		
		$expiration = time() + 172800; // 48 hours
		$cookie = self::get_previous_switched_cookie();
		
		if ( ! empty( $cookie ) ) {
			
			$user_cookies = array();
			foreach ( $cookie as $user_cookie ) {
				$user_id = wp_validate_auth_cookie( $user_cookie, 'switched_users' );
				
				if ( ( $user_id != $from_user_id ) && ( $user_id != $to_user_id ) ) {
					$user_cookies[] = $user_cookie;
				}
			}
			if ( count( $user_cookies) > 2) {
				$user_cookies = array_splice( $user_cookies, -2);
			}
			$user_cookies[] = wp_generate_auth_cookie( $from_user_id, $expiration, 'switched_users' );
			
		}
		else {
			
			$user_cookies[] = wp_generate_auth_cookie( $from_user_id, $expiration, 'switched_users' );
		}
		setcookie( SWITCHED_USERS_COOKIE, json_encode( $user_cookies ), $expiration, COOKIEPATH, COOKIE_DOMAIN, false );
	}

	/**
	 * Clears the cookie containing the switched user, or pops the latest item off the end if there's more than one.
	 *
	 * @param bool $clear_all Whether to clear the cookie or just pop the last user information off the end.
	 * @return null
	 */
	function clear_previous_switched_cookie() {
		$cookie = self::get_previous_switched_cookie();
		setcookie( SWITCHED_USERS_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN );
	}
	
	// End Moved from functions files

	/**
	 * Output the 'Shop as' link on the customer editing screen if we have permission to shop as this customer.
	 *
	 * @param WP_User $user User object for this screen
	 * @return null
	 */
	public function action_personal_options( WP_User $user ) {
		
		// Bail if we can't get a link to shop as this user.
		if ( ! $link = self::maybe_shop_as_url( $user->ID ) ) return;
		?>
		<tr>
			<th scope="row">&nbsp;
				
			</th>
			<td>
				<a class="button" href="<?php echo $link; ?>"><?php _e( 'Shop as Customer', 'shop-as-customer' ); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Return whether or not the current logged in user is being remembered in the form of a persistent browser
	 * cookie (ie. they checked the 'Remember Me' check box when they logged in). This is used to persist the
	 * 'remember me' value when the user switches to another user.
	 *
	 * @return bool Whether the current user is being 'remembered' or not.
	 */
	public static function remember( $user_id ) {

		$current     = wp_parse_auth_cookie( '', 'logged_in' );
		// $cookie_life = apply_filters( 'auth_cookie_expiration', 172800, get_current_user_id(), false ); // OLD - caused issue where would not respect the 'Remember Me' checkbox.
		$cookie_life = apply_filters( 'auth_cookie_expiration', 172800, $user_id, false );
		
		// _log( 'main user: ' .  $user_id );
		// _log( 'remember: ' . ( ( $current['expiration'] - time() ) > $cookie_life ) );
		
		// Here we calculate the expiration length of the current auth cookie and compare it to the default expiration.
		// If it's greater than this, then we know the user checked 'Remember Me' when they logged in.
		return ( ( $current['expiration'] - time() ) > $cookie_life );
	}

	/**
	 * Route actions depending on the 'action' query var.
	 *
	 * @return null
	 */
	public function action_init() {
		
		if ( ! isset( $_REQUEST['action'] ) )
			return;

		if ( isset( $_REQUEST['redirect_to'] ) && ! empty( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = self::remove_query_args( $_REQUEST['redirect_to'] );
		}
		else {
			$redirect_to = false;
		}

		switch ( $_REQUEST['action'] ) {
			
			// We're attempting to switch to another user:
			case 'switch_to_customer':
				
				$user_id = absint( $_REQUEST['user_id'] );

				check_admin_referer( 'shop_as_customer_nonce' );

				// Switch user:
				if ( self::switch_to_customer( $user_id, self::remember( get_current_user_id() ), 'switch_to_customer' ) ) {
					
					// Redirect to the dashboard or the home URL depending on capabilities:
					if ( $redirect_to ) {
						
						wp_safe_redirect( esc_url_raw( add_query_arg(
							array(
								'shopping_as_customer' => 'true'
							),
							$redirect_to
						) ) );
					}
					else if ( ! current_user_can( 'read' ) ) {
						
						wp_redirect( esc_url_raw( add_query_arg(
							array(
								'shopping_as_customer' => 'true'
							),
							get_permalink( wc_get_page_id( 'myaccount' ) )
						) ) );
					}
					else {
						
						wp_redirect( esc_url_raw( add_query_arg(
							array(
								'shopping_as_customer' => 'true'
							),
							get_permalink( wc_get_page_id( 'myaccount' ) )
						) ) );
					}
					
					die();

				}
				else {

					$referer_link = '';
					
					if( wp_get_referer() ) {
						$referer_link = ' <a href="' . wp_get_referer() . '">← ' . __('back to previous page','shop-as-customer') . '</a>';
					}

					wp_die( __( "Sorry, you can't shop as this customer.", 'shop-as-customer' ) . '<br />' . __( 'They have higher capabilites so switching would not be secure.', 'shop-as-customer' ) . '<br />' . $referer_link );
				}
				
			break;

			// We're attempting to switch back to the originating user:
			case 'switch_back_to_original_customer':

				check_admin_referer( 'switch_back_to_original_customer_nonce' );

				// Fetch the originating user data:
				if ( ! $this->is_switched() )
					wp_die( __( 'Could not switch back to originating user.', 'shop-as-customer' ) );

				// Get the orginal user.
				$original_user = self::get_original_user();

				// Switch user:
				if ( self::switch_to_customer( $original_user->ID, self::remember( $original_user->ID ), 'switch_back_to_original_customer' ) ) {
					
					if ( isset( $_REQUEST['redirect_to_order'] ) and !empty( $_REQUEST['redirect_to_order'] ) ) {
						
						$redirect_to_order = html_entity_decode( get_edit_post_link( $_REQUEST['redirect_to_order'] ) );
						wp_safe_redirect( $redirect_to_order );
					}
					else {
						
						if ( $redirect_to ) {
							
							wp_safe_redirect( esc_url_raw( add_query_arg(
								array(
									'shopping_as_customer' => 'true',
									'switched_back_user' => 'true',
								),
								$redirect_to
							) ) );
						}
						else {
							
							wp_redirect( esc_url_raw( add_query_arg(
								array(
									'shopping_as_customer' => 'true',
									'switched_back_user' => 'true',
								),
								admin_url( 'users.php' )
							) ) );
						}
					}
					die();
				}
				else {
					wp_die( __( 'Could not switch back to originating user.', 'shop-as-customer' ) );
				}
				
			break;
		}

	}

	/**
	 * Validate the latest item in the original_user cookie and return its user data.
	 *
	 * @return bool|WP_User False if there's no old user cookie or it's invalid, WP_User object if it's present and valid.
	 */
	public static function get_original_user() {
		
		if ( $cookie = self::get_original_user_cookie() ) {
			if ( $original_user_id = wp_validate_auth_cookie( $cookie, 'original_user' ) ) {
				return get_userdata( $original_user_id );
			}
		}
		else {
			return false;
		}
	}

	/**
	 * Validate the all items of the previously switched user cookie and return all user data.
	 *
	 * @return bool|WP_User False if there's no old user cookie or it's invalid, WP_User object if it's present and valid.
	 */
	public static function get_all_previous_switched_users() {
		$cookie = self::get_previous_switched_cookie();
		if ( ! empty( $cookie ) ) {
			
			$collect_users = array();

			foreach ( $cookie as $user ) {
				$user_data = get_userdata( wp_validate_auth_cookie( $user, 'switched_users' ) );
				if ( FALSE !== $user_data ) {
					$collect_users[] = get_userdata( wp_validate_auth_cookie( $user, 'switched_users' ) );
				}
			}
			return $collect_users;
		}
		return false;
	}

	/**
	 * Adds a 'Switch back to {user}' link to the account menu in WordPress' admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object
	 * @return null
	 */
	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( !function_exists( 'is_admin_bar_showing' ) )
			return;

		if ( $this->is_switched() ) {

			$original_user = self::get_original_user();
			self::build_shopping_as_user_menu( $original_user );
		}
		else {

			self::build_user_history_menu();
		}
	}

	/**
	 * Adds a 'Switch back to {user}' link to the WordPress login screen.
	 *
	 * @param string $message The login screen message
	 * @return string The login screen message
	 */
	public function filter_login_message( $message ) {

		if ( $this->is_switched() ) {
			
			$original_user = self::get_original_user();
			$link = sprintf( __( 'Back to %1$s (%2$s)', 'shop-as-customer' ), $original_user->display_name, $original_user->user_login );
			$url = self::switch_back_url();
			if ( isset( $_REQUEST['redirect_to'] ) and !empty( $_REQUEST['redirect_to'] ) ) {
				$url = esc_url_raw( add_query_arg( array(
					'redirect_to' => $_REQUEST['redirect_to']
				), $url ) );
			}
			$message .= '<p class="message"><a href="' . $url . '">' . $link . '</a></p>';
		}

		return $message;

	}

	/**
	 * Adds a 'Switch To' link to each list of user actions on the Users screen.
	 *
	 * @param array   $actions The actions to display for this user row
	 * @param WP_User $user    The user object displayed in this row
	 * @return array The actions to display for this user row
	 */
	public function filter_customer_row_actions( array $actions, WP_User $user ) {

		if ( ! $link = self::maybe_shop_as_url( $user->ID ) )
			return $actions;

		$actions['shop_as_customer'] = '<a href="' . $link . '">' . __( 'Switch To', 'shop-as-customer' ) . '</a>';

		return $actions;
	}

	/**
	 * Helper function. Returns the switch to or switch back URL for a given user ID.
	 *
	 * @param int $user_id The user ID to be switched to.
	 * @return string|bool The required URL, or false if there's no old user or the user doesn't have the required capability.
	 */
	public static function maybe_shop_as_url( $user_id ) {

		$original_user = self::get_original_user();

		if ( $original_user and ( $original_user->ID == $user_id ) )
			return self::switch_back_url();
		else if ( current_user_can( 'shop_as_customer', $user_id ) )
			return self::switch_to_url( $user_id );
		else
			return false;

	}

	/**
	 * Helper function. Returns the nonce-secured URL needed to switch to a given user ID.
	 *
	 * @param int $user_id The user ID to be switched to.
	 * @return string The required URL
	 */
	public static function switch_to_url( $user_id ) {
		return esc_url_raw(
			add_query_arg(
				array(
					'action'  => 'switch_to_customer',
					'user_id' => $user_id,
					 '_wpnonce' => wp_create_nonce( 'shop_as_customer_nonce' ),
				),
				wp_login_url()
			)
		);
	}

	/**
	 * Helper function. Returns the nonce-secured URL needed to switch back to the originating user.
	 *
	 * @return string The required URL
	 */
	public static function switch_back_url() {
		
		return esc_url_raw(
			wp_nonce_url(
				add_query_arg(
					array( 'action' => 'switch_back_to_original_customer' ),
					wp_login_url()
				),
				'switch_back_to_original_customer_nonce'
			)
		);
	}

	/**
	 * Helper function. Returns the current URL.
	 *
	 * @return string The current URL
	 */
	public static function current_url() {
		return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Helper function. Removes a list of common confirmation-style query args from a URL.
	 *
	 * @param string $url A URL
	 * @return string The URL with the listed query args removed
	 */
	public static function remove_query_args( $url ) {
		return esc_url_raw(
					remove_query_arg(
						array(
							'shopping_as_customer',
							'switched_back_user',
							'message',
							'updated',
							'settings-updated',
							'saved',
							'activated',
							'activate',
							'deactivate',
							'locked',
							'skipped',
							'deleted',
							'trashed',
							'untrashed',
							// Cart (new)
							'add-to-cart',
							'remove_item',
							'add-to-cart',
							'added-to-cart',
						),
						$url
					)
				);
	}

	/**
	 * Filter the user's capabilities so they can be added/removed on the fly.
	 *
	 * This is used to grant the 'shop_as_user' capability to a user if they have the ability to edit the user
	 * they're trying to switch to (and that user is not themselves), and to grant the 'switch_off' capability to
	 * a user if they can edit users.
	 *
	 * Important: This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @param array $user_caps     User's capabilities
	 * @param array $required_caps Actual required capabilities for the requested capability
	 * @param array $args          Arguments that accompany the requested capability check:
	 *                             [0] => Requested capability from current_user_can()
	 *                             [1] => Current user ID
	 *                             [2] => Optional second parameter from current_user_can()
	 * @return array User's capabilities
	 */
	public function filter_customer_has_cap( array $user_caps, array $required_caps, array $args ) {
		if ( 'shop_as_customer' == $args[0] )
			$user_caps['shop_as_customer'] = ( user_can( $args[1], 'edit_user', $args[2] ) and ( $args[2] != $args[1] ) );

		return $user_caps;
	}

	/**
	 * Filters the actual required capabilities for a given capability or meta capability.
	 *
	 * This is used to add the 'do_not_allow' capability to the list of required capabilities when a super admin
	 * is trying to switch to themselves. It affects nothing else as super admins can do everything by default.
	 *
	 * @param array  $required_caps Actual required capabilities for the requested action
	 * @param string $cap           Capability or meta capability being checked
	 * @param string $user_id       Current user ID
	 * @param array  $args          Arguments that accompany this capability check
	 * @return array Required capabilities for the requested action
	 */
	public function filter_map_meta_cap( array $required_caps, $cap, $user_id, array $args ) {
		if ( ( 'shop_as_customer' == $cap ) and ( $args[0] == $user_id ) )
			$required_caps[] = 'do_not_allow';
		return $required_caps;
	}

	/**
	 * Remove wordpress my-account admin menu options and build shop as user menu
	 *
	 */
	public function build_shopping_as_user_menu( $original_user ) {
		global $wp_admin_bar;

		$wp_admin_bar->remove_node( "my-account" );
		
		$original_user        = self::get_original_user();
		$original_user_id     = $original_user->ID;
		$original_avatar      = get_avatar( $original_user_id, 26 );
		$original_text        = sprintf( __('Howdy, %1$s', 'shop-as-customer'), $original_user->data->display_name );
		$original_class       = empty( $original_avatar ) ? '' : 'with-avatar';
		$original_profile_url = get_edit_profile_url( $original_user_id );

		// Add shopping as user menu options to admin menu.
		$current_user_id          = get_current_user_id();
		$current_user             = wp_get_current_user();
		$current_user_profile_url = get_edit_profile_url( $current_user_id );
		
		// Bail if no current user.
		if ( ! $current_user_id ) return;

		$current_avatar = get_avatar( $current_user_id, 26 );
		$current_text   = sprintf( __( 'Shopping as %1$s', 'shop-as-customer' ), $current_user->display_name );
		$current_class  = empty( $current_avatar ) ? '' : 'with-avatar';
		
		$collect_html = '';
		// $collect_html .= '<span class="top-howdy top-howdy-main">';
		// $collect_html .= $original_text . $original_avatar;
		// $collect_html .= '</span>';
		$collect_html .= '<span class="top-howdy top-howdy-secondry">';
		$collect_html .= $current_text . $current_avatar;
		$collect_html .= '</span>';
		
		$collect_html .= '<a class="top-switch-back" href="' . self::switch_back_url() . '">';
		$collect_html .= '<span class="dashicons dashicons-randomize"></span>';
		$collect_html .= __( 'Switch Back', 'shop-as-customer' );
		$collect_html .= '</a>';
		
		$wp_admin_bar->add_menu( array(
			'id'        => 'my-account',
			'parent'    => 'top-secondary',
			'title'     => $collect_html,
			'href'      => '',
			'meta'      => array(
				'class'     => "shopping-as-user ".$current_class,
				'title'     => $current_text,
			),
		) );
		
		$wp_admin_bar->remove_node('user-actions');
	}

	/**
	 * Add admin bar menu for the original user.
	 */
	public function build_user_history_menu() {
		global $wp_admin_bar;
		
		$location = is_admin() ? 'backend' : 'frontend' ;
		
		$wp_admin_bar->add_menu( array(
			'parent' => 'user-actions',
			'id'     => "switch-to-customer-{$location}",
			'title'  => '<span class="dashicons dashicons-randomize"></span> ' . __( 'Switch To Customer', 'shop-as-customer' ),
			'href'   => get_admin_url( null, '/users.php#shop-as-customer' ),
		) );
	}
	
	/**
	 * Add admin bar menu for the original user.
	 */
	public function output_switch_interface() {
		
		?>
		<div class="cxsac-switch-form cxsac-modal-form component-modal-hard-hide">
		
			<div class="cxsac-modal-form-title">
				<?php _e( 'Shop as Customer', 'shop-as-customer' ); ?>
			</div>
            
            <div class="cxsac-modal-form-content-inner">
                
                <div class="cxsac-modal-form-row">
                	<?php if ( version_compare( get_option( 'woocommerce_version' ), '3', '<' ) ) { ?>
                    	<input id="cxsac-select2-user-search" type="hidden" class="wc-customer-search" name="customer_user" data-placeholder="Find a Customer" data-allow_clear="true">
                    <?php } else { ?>
                    	<select id="cxsac-select2-user-search" class="wc-customer-search" name="customer_user" data-placeholder="<?php esc_attr_e( 'Find a Customer', 'shop-as-customer' ); ?>" data-allow_clear="true"></select>
                    <?php } ?>
                </div>
                
                <?php
                // Show Recent logins, if there are any
                if ( ! $this->is_switched() && ( $all_switched_users = self::get_all_previous_switched_users() ) ) {
                    
                    // Reverse the order of past user list.
                    $all_switched_users = array_reverse( $all_switched_users, true );
                    ?>
                    <div class="cxsac-modal-form-row cxsac-modal-history-holder">
                        
                        <div class="cxsac-modal-history-heading">
                            <?php _e( 'Recent', 'shop-as-customer' ); ?> 
                            <span class="cxsac-modal-history-helper">
                                <?php _e( 'Customers you have recently switched to', 'shop-as-customer' ) ?>
                            </span>
                        </div>
                        
                        <div class="cxsac-modal-history-items">
                            <?php
                            foreach ( $all_switched_users as $swited_user ) {
                                
                                $link = self::switch_to_url( $swited_user->ID );
                                $orders_link = admin_url( 'edit.php?post_status=all&post_type=shop_order&action=-1&shop_order_status&_customer_user=' . absint( $swited_user->ID ) . '' );
                                $user_link = esc_url_raw( network_admin_url( 'user-edit.php?user_id=' . $swited_user->ID ) );
                                ?>
                                <div class="cxsac-modal-history-item">
                                    <div class="cxsac-modal-history-name">
                                        <?php echo $swited_user->data->display_name; ?>
                                    </div>
                                    <div class="cxsac-modal-history-links">
                                        <a class="cxsac-modal-history-links" href="<?php echo $link; ?>"><?php _e( 'Switch to', 'shop-as-customer' ) ?></a>
                                        <span class="cxsac-modal-history-links-divider">|</span>
                                        <a class="cxsac-modal-history-links" href="<?php echo $orders_link; ?>"><?php _e( 'View Orders', 'shop-as-customer' ) ?></a>
                                        <span class="cxsac-modal-history-links-divider">|</span>
                                        <a class="cxsac-modal-history-links" href="<?php echo $user_link; ?>"><?php _e( 'Edit Profile', 'shop-as-customer' ) ?></a>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    
                    </div>
                    <?php
                }
                ?>
            
            </div>
			
		</div>
		<?php
	}
	
	/**
	 * Add admin bar menu for the original user.
	 */
	public function output_switched_bar() {
		global $wp_admin_bar;
		
		if ( 'no' == cxsac_get_option( 'cxsac_show_switch_back_bar' ) ) {
			return;
		}
		
		$original_user        = self::get_original_user();
		$original_user_id     = $original_user->ID;
		$original_avatar      = get_avatar( $original_user_id, 26 );
		$original_profile_url = get_edit_profile_url( $original_user_id );

		// Add shopping as user menu options to admin menu.
		$current_user_id          = get_current_user_id();
		$current_user             = wp_get_current_user();
		$current_user_profile_url = get_edit_profile_url( $current_user_id );
		
		// Bail if no current user.
		if ( ! $current_user_id ) return;
		
		$current_avatar = get_avatar( $current_user_id, 26 );
		$switch_back_url = add_query_arg(
			array( 'redirect_to' => urlencode( self::current_url() ) ),
			self::switch_back_url()
		);
		?>
		
		<?php if ( is_admin_bar_showing() ) { ?>
			
			<?php if ( is_admin() ) { ?>
				
				<style>
				html { padding-top: 84px !important; }
				
				@media screen and ( max-width: 782px ) {
					html { padding-top: 0px !important; }
					#wpbody { padding-top: 98px !important; }
				}
				</style>
				
			<?php } else { ?>
				
				<style>
				html { margin-top: 84px !important; }
				
				@media screen and ( max-width: 782px ) {
					html { margin-top: 98px !important; }
				}
				</style>
				
			<?php } ?>
			
		<?php } else { ?>
			
			<style>
			html { margin-top: 52px !important; }
			.cxsac-switched-bar-holder { top: 0; }
			</style>
			
		<?php } ?>
		
		<div class="cxsac-switched-bar-holder">
			<span class="cxsac-switched-bar-main-text">
				<?php echo sprintf( __( '<strong>Shopping as</strong> %s %s', 'shop-as-customer' ), $current_avatar, $current_user->display_name ); ?>
				<span class="cxsac-switched-bar-email"><?php echo $current_user->user_email; ?></span>
			</span> 
			<a class="cxsac-switched-bar-button" href="<?php echo esc_url( $switch_back_url ); ?>">
				<?php _e( 'Switch Back', 'shop-as-customer' ); ?> 
				<span class="dashicons dashicons-randomize"></span>
			</a> 
		</div>
		<?php
	}

	public function shop_as_customer_order_notice() {
		global $post;
		$screen = get_current_screen();

		if ( ! isset( $post ) ) return;

		$created_by = get_post_meta( $post->ID, 'create_by', true );

		if ( empty( $created_by ) ) return;

		if ( ! isset( $screen->id ) ) return;

		if ( 'shop_as_customer' === $created_by && 'shop_order' === $screen->id ) {
			
			printf ( '
				<div id="shop-as-customer-message" class="updated notice notice-success below-h2">
					<p>%s</p>
				</div>',
				__( 'Order created using <strong>Shop as Customer</strong>', 'shop-as-customer' )
			);
		}
	}
	
	/**
	 * Check the order-of-power of a user, based on his ID.
	 *
	 * @param string $user_id User id to check.
	 */
	public static function get_user_role_order( $user_id ) {
		
		// super_admin - multisite only.
		if ( is_multisite() && is_super_admin( $user_id ) )
			return 1;
			
		// administrator
		else if ( user_can( $user_id, 'manage_options' ) )
			return 2;
		
		// shop_manager
		else if ( user_can( $user_id, 'manage_woocommerce' ) )
			return 3;
		
		// shop_manager
		else
			return 4;
	}
	
	/**
	 * Check the user role, based on his ID.
	 *
	 * @param   string   $user_id       User id to check.
	 * @param   string   $return_type   Return the role key or name?
	 */
	public static function get_user_role( $user_id, $return_type = 'key' ) {
		
		// super_admin
		if ( user_can( $user_id, 'manage_network' ) )
			return ( 'key' == $return_type ) ? 'super_admin' : 'Super Admin' ;
			
		// administrator
		if ( user_can( $user_id, 'manage_options' ) )
			return ( 'key' == $return_type ) ? 'administrator' : 'Administrator' ;
		
		// shop_manager
		if ( user_can( $user_id, 'manage_woocommerce' ) )
			return ( 'key' == $return_type ) ? 'shop_manager' : 'Shop Manager' ;
	}

	/**
	 * Helper function to check whether the user is in a switched state.
	 *
	 * @return boolean
	 */
	function is_switched() {
		return ( bool ) ( self::get_original_user() );
	}
	
	/**
	 * Returns an array of All the user roles we've saved, in order, with a heirarchical number as a value.
	 *
	 * @param    string   $format   heirarchy|names.
	 * @return   array              Roles in either of the above formats.
	 */
	public static function get_all_user_roles( $format = 'heirarchy' ) {
		
		// Get the saved settings.
		$roles = cxsac_get_option( 'cxsac_user_role_heirarchy' );
		
		if ( '' == trim( $roles ) ) {
			$roles = cxsac_get_default( 'cxsac_user_role_heirarchy' );
		}
		
		$roles = explode( "\r\n", $roles );
		
		// Explode each line in the textarea into an array, noting the heirarchy as a number.
		$index = 0;
		$new_roles = array();
		foreach ( $roles as $role_key => $role_value ) {
			$new_roles[$role_value] = $index;
			$index++;
		}
		$roles = $new_roles;
		
		// Explode the lines that have `|`, allowing the user to have roles that are on the same level.
		$index = 0;
		$new_roles = array();
		foreach ( $roles as $role_key => $role_value ) {
			$new_keys = explode( '|', $role_key );
			foreach ( $new_keys as $new_key_value ) {
				$new_key_value = trim( $new_key_value ); // Trim the value incase the user typed spaces around the `|`
				$new_roles[$new_key_value] = $index;
			}
			$index++;
		}
		$roles = $new_roles;
		
		if ( 'heirarchy' == $format ) {
			
			// Get `role_key => Role Name` format.
			
			return $roles;
		}
		else {
			
			// Get `role_key => role_heirarchy` format.
			
			$role_names = wp_roles()->role_names;
			
			// If multisite then make this option available.
			if ( is_multisite() )
				$role_names = array( 'super_admin' => 'Super Admin' ) + $role_names;
			
			$new_roles = array();
			foreach ( $roles as $role_key => $role_heirarchy ) {
				if ( isset( $role_names[$role_key] ) )
					$new_roles[$role_key] = $role_names[$role_key];
			}
			
			return $new_roles;
		}
	}
	
	/**
	 * Test a users capability
	 */
	public static function current_user_is_equal_or_higher_than( $role_check = 'administrator' ) {
		
		// Get all the user roles.
		$heirarchy = self::get_all_user_roles( 'heirarchy' );
		
		// Get the current user info - so we can get his roles.
		$user_info = wp_get_current_user();
		$user_info->ID;
		$user_info->roles;
		
		$passed = FALSE;
		foreach ( $user_info->roles as $role ) {
			
			// Skip if these role types are not accounted for in our role Setting
			if ( ! isset( $heirarchy[$role] ) || ! isset( $heirarchy[$role_check] ) ) continue;
			
			if ( $heirarchy[$role_check] >= $heirarchy[$role] ) $passed = TRUE;
		}
		
		// Special check for Super Admin.
		if ( 'super_admin' == $role_check && current_user_can( 'manage_network' ) ) {
			$passed = TRUE;
		}
		
		return $passed;
	}
	
	/**
	 * Is WooCommerce active.
	 */
	public static function is_woocommerce_active() {
		
		$active_plugins = (array) get_option( 'active_plugins', array() );
		
		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		
		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}

	/**
	 * Display Notifications on specific criteria.
	 *
	 * @since	2.14
	 */
	public static function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) :
			if ( !class_exists( 'WooCommerce' ) ) :
				?>
				<div id="message" class="error">
					<p>
						<?php
						printf(
							__( '%sShop as Customer for WooCommerce needs WooCommerce%s %sWooCommerce%s must be active for Shop as Customer to work. Please install & activate WooCommerce.', 'shop-as-customer' ),
							'<strong>',
							'</strong><br>',
							'<a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank" >',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			elseif ( version_compare( get_option( 'woocommerce_db_version' ), WC_SHOP_AS_CUSTOMER_REQUIRED_WOOCOMMERCE_VERSION, '<' ) ) :
				?>
				<div id="message" class="error">
					<!--<p style="float: right; color: #9A9A9A; font-size: 13px; font-style: italic;">For more information <a href="http://cxthemes.com/plugins/update-notice.html" target="_blank" style="color: inheret;">click here</a></p>-->
					<p>
						<?php
						printf(
							__( '%sShop as Customer for WooCommerce is inactive%s This version of Shop as Customer requires WooCommerce %s or newer. For more information about our WooCommerce version support %sclick here%s.', 'shop-as-customer' ),
							'<strong>',
							'</strong><br>',
							WC_SHOP_AS_CUSTOMER_REQUIRED_WOOCOMMERCE_VERSION,
							'<a href="https://helpcx.zendesk.com/hc/en-us/articles/202241041/" target="_blank" style="color: inheret;" >',
							'</a>'
						);
						?>
					</p>
					<div style="clear:both;"></div>
				</div>
				<?php
			endif;
		endif;
	}

}
