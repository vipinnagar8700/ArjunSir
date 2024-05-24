<?php
/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * @since     3.0.0
 * @author    WooCommerce / SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 *
 * Modified by WooCommerce on 19 December 2021.
 */

namespace WooCommerce\Square\Framework\PaymentGateway\ApplePay;

use WooCommerce\Square\Framework\Square_Helper;

defined( 'ABSPATH' ) or exit;

/**
 * Sets up the Apple Pay settings screen.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Apple_Pay_Admin {


	/** @var Payment_Gateway_Apple_Pay the Apple Pay handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Apple_Pay $handler main Apple Pay handler instance
	 */
	public function __construct( $handler ) {

		$this->handler = $handler;

		// add Apple Pay to the checkout settings sections
		add_filter( 'woocommerce_get_sections_checkout', array( $this, 'add_settings_section' ), 99 );

		// output the settings
		add_action( 'woocommerce_settings_checkout', array( $this, 'add_settings' ) );

		// render the special "static" gateway select
		add_action( 'woocommerce_admin_field_static', array( $this, 'render_static_setting' ) );

		// save the settings
		add_action( 'woocommerce_settings_save_checkout', array( $this, 'save_settings' ) );

		// add admin notices for configuration options that need attention
		add_action( 'admin_footer', array( $this, 'add_admin_notices' ), 10 );
	}


	/**
	 * Adds Apple Pay to the checkout settings sections.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param array $sections the existing sections
	 * @return array
	 */
	public function add_settings_section( $sections ) {

		$sections['apple-pay'] = __( 'Apple Pay', 'woocommerce-square' );

		return $sections;
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array $settings The combined settings.
	 */
	public function get_settings() {

		$settings = array(

			array(
				'title' => __( 'Apple Pay', 'woocommerce-square' ),
				'type'  => 'title',
			),

			array(
				'id'              => 'sv_wc_apple_pay_enabled',
				'title'           => __( 'Enable / Disable', 'woocommerce-square' ),
				'desc'            => __( 'Accept Apple Pay', 'woocommerce-square' ),
				'type'            => 'checkbox',
				'default'         => 'no',
			),

			array(
				'id'      => 'sv_wc_apple_pay_display_locations',
				'title'   => __( 'Allow Apple Pay on', 'woocommerce-square' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'css'     => 'width: 350px;',
				'options' => $this->get_display_location_options(),
				'default' => array_keys( $this->get_display_location_options() ),
			),

			array(
				'id'      => 'sv_wc_apple_pay_button_style',
				'title'   => __( 'Button Style', 'woocommerce-square' ),
				'type'    => 'select',
				'options' => array(
					'black'           => __( 'Black', 'woocommerce-square' ),
					'white'           => __( 'White', 'woocommerce-square' ),
					'white-with-line' => __( 'White with outline', 'woocommerce-square' ),
				),
				'default' => 'black',
			),

			array(
				'type' => 'sectionend',
			),
		);

		$connection_settings = array(
			array(
				'title' => __( 'Connection Settings', 'woocommerce-square' ),
				'type'  => 'title',
			),

			array(
				'id'      => 'sv_wc_apple_pay_merchant_id',
				'title'   => __( 'Apple Merchant ID', 'woocommerce-square' ),
				'type'    => 'text',
				'desc'  => sprintf(
					/** translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					__( 'This is found in your %1$sApple developer account%2$s', 'woocommerce-square' ),
					'<a href="https://developer.apple.com" target="_blank">', '</a>'
				),
			),

			array(
				'id'       => 'sv_wc_apple_pay_cert_path',
				'title'    => __( 'Certificate Path', 'woocommerce-square' ),
				'type'     => 'text',
				'desc_tip' => __( 'The full system path to your certificate file from Apple. For security reasons you should store this outside of your web root.', 'woocommerce-square' ),
				'desc'     => sprintf(
					/* translators: Placeholders: %s - the server's web root path */
					__( 'For reference, your current web root path is: %s', 'woocommerce-square' ),
					'<code>' . ABSPATH . '</code>'
				),
			),
		);

		$gateway_setting_id = 'sv_wc_apple_pay_payment_gateway';
		$gateway_options    = $this->get_gateway_options();

		if ( 1 === count( $gateway_options ) ) {

			$connection_settings[] = array(
				'id'    => $gateway_setting_id,
				'title' => __( 'Processing Gateway', 'woocommerce-square' ),
				'type'  => 'static',
				'value' => key( $gateway_options ),
				'label' => current( $gateway_options ),
			);

		} else {

			$connection_settings[] = array(
				'id'      => $gateway_setting_id,
				'title'   => __( 'Processing Gateway', 'woocommerce-square' ),
				'type'    => 'select',
				'options' => $this->get_gateway_options(),
			);
		}

		$connection_settings[] = array(
			'id'      => 'sv_wc_apple_pay_test_mode',
			'title'   => __( 'Test Mode', 'woocommerce-square' ),
			'desc'    => __( 'Enable to test Apple Pay functionality throughout your sites without processing real payments.', 'woocommerce-square' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		$connection_settings[] = array(
			'type' => 'sectionend',
		);

		$settings = array_merge( $settings, $connection_settings );

		/**
		 * Filter the combined settings.
		 *
		 * @since 3.0.0
		 * @param array $settings The combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_apple_pay', $settings );
	}


	/**
	 * Outputs the settings fields.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function add_settings() {
		global $current_section;

		if ( 'apple-pay' === $current_section ) {
			\WC_Admin_Settings::output_fields( $this->get_settings() );
		}
	}


	/**
	 * Saves the settings.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @global string $current_section The current settings section.
	 */
	public function save_settings() {
		global $current_section;

		// Output the general settings
		if ( 'apple-pay' == $current_section ) {

			\WC_Admin_Settings::save_fields( $this->get_settings() );
		}
	}


	/**
	 * Renders a static setting.
	 *
	 * This "setting" just displays simple text instead of a <select> with only
	 * one option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting
	 */
	public function render_static_setting( $setting ) {

		?>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $setting['id'] ); ?>"><?php echo esc_html( $setting['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $setting['type'] ) ); ?>">
				<?php echo esc_html( $setting['label'] ); ?>
				<input
					name="<?php echo esc_attr( $setting['id'] ); ?>"
					id="<?php echo esc_attr( $setting['id'] ); ?>"
					value="<?php echo esc_html( $setting['value'] ); ?>"
					type="hidden"
					>
			</td>
		</tr><?php
	}


	/**
	 * Adds admin notices for configuration options that need attention.
	 *
	 * @since 3.0.0
	 */
	public function add_admin_notices() {

		// if the feature is not enabled, bail
		if ( ! $this->handler->is_enabled() ) {
			return;
		}

		// if not on the settings screen, bail
		if ( ! $this->is_settings_screen() ) {
			return;
		}

		$errors = array();

		// HTTPS notice
		if ( ! wc_site_is_https() ) {
			$errors[] = __( 'Your site must be served over HTTPS with a valid SSL certificate.', 'woocommerce-square' );
		}

		// Currency notice
		if ( ! in_array( get_woocommerce_currency(), $this->handler->get_accepted_currencies(), true ) ) {

			$accepted_currencies = $this->handler->get_accepted_currencies();

			$errors[] = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a currency/comma-separated list of currencies, %3$s - <a> tag, %4$s - </a> tag */
				_n(
					'Accepts payment in %1$s only. %2$sConfigure%3$s WooCommerce to accept %1$s to enable Apple Pay.',
					'Accepts payment in one of %1$s only. %2$sConfigure%3$s WooCommerce to accept one of %1$s to enable Apple Pay.',
					count( $accepted_currencies ),
					'woocommerce-square'
				),
				'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>',
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">',
				'</a>'
			);
		}

		// bad cert config notice
		// this first checks if the option has been set so the notice is not
		// displayed without the user having the chance to set it.
		if ( false !== $this->handler->get_cert_path() && ! $this->handler->is_cert_configured() ) {

			$errors[] = sprintf(
				/** translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
				__( 'Your %1$sMerchant Identity Certificate%2$s cannot be found. Please check your path configuration.', 'woocommerce-square' ),
				'<strong>', '</strong>'
			);
		}

		if ( ! empty( $errors ) ) {

			$message = '<strong>' . __( 'Apple Pay is disabled.', 'woocommerce-square' ) . '</strong>';

			if ( 1 === count( $errors ) ) {
				$message .= ' ' . current( $errors );
			} else {
				$message .= '<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
			}

			$this->handler->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, 'apple-pay-https-required', array(
				'notice_class' => 'error',
				'dismissible'  => false,
			) );
		}
	}


	/**
	 * Determines if the user is currently on the settings screen.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function is_settings_screen() {

		return 'wc-settings' === Square_Helper::get_request( 'page' ) && 'apple-pay' === Square_Helper::get_request( 'section' );
	}


	/**
	 * Gets the available display location options.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_display_location_options() {

		return array(
			'product'  => __( 'Single products', 'woocommerce-square' ),
			'cart'     => __( 'Cart', 'woocommerce-square' ),
			'checkout' => __( 'Checkout', 'woocommerce-square' ),
		);
	}


	/**
	 * Gets the available gateway options.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_gateway_options() {

		$gateways = $this->handler->get_supporting_gateways();

		foreach ( $gateways as $id => $gateway ) {
			$gateways[ $id ] = $gateway->get_method_title();
		}

		return $gateways;
	}
}
