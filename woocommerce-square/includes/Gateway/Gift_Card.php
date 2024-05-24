<?php

namespace WooCommerce\Square\Gateway;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Square\Framework\Square_Helper;
use WooCommerce\Square\Handlers\Product;
use WooCommerce\Square\Plugin;
use WooCommerce\Square\Utilities\Money_Utility;

class Gift_Card {
	/**
	 * @var \WooCommerce\Square\Gateway $gateway
	 */
	public $gateway = null;

	/**
	 * Checks if Gift Card is enabled.
	 *
	 * @since 3.7.0
	 * @return bool
	 */
	public function is_gift_card_enabled() {
		return 'yes' === $this->gateway->get_option( 'enable_gift_cards', 'no' );
	}

	/**
	 * Setup the Gift Card class
	 *
	 * @param \WooCommerce\Square\Gateway $gateway The payment gateway object.
	 * @since 3.7.0
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;

		add_action( 'wp', array( $this, 'init_gift_cards' ) );
		add_action( 'init', array( $this, 'add_gift_card_image_placeholder' ) );
		add_action( 'wp_ajax_wc_square_check_gift_card_balance', array( $this, 'apply_gift_card' ) );
		add_action( 'wp_ajax_nopriv_wc_square_check_gift_card_balance', array( $this, 'apply_gift_card' ) );
		add_action( 'wp_ajax_wc_square_gift_card_remove', array( $this, 'remove_gift_card' ) );
		add_action( 'wp_ajax_nopriv_wc_square_gift_card_remove', array( $this, 'remove_gift_card' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_gift_card_fragments' ) );
		add_filter( 'woocommerce_checkout_order_processed', array( $this, 'delete_sessions' ) );
	}

	/**
	 * Loads resources required for the Gift Card feature.
	 *
	 * @since 3.7.0
	 */
	public function init_gift_cards() {
		// Return if Gateway or Gift Card is not enabled.
		if ( ! ( 'yes' === $this->gateway->get_option( 'enabled', 'no' ) && $this->is_gift_card_enabled() ) ) {
			return;
		}

		if ( ! self::does_checkout_support_gift_card() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Returns true if the Checkout page meets all criteria to
	 * render the Gift Card feature.
	 *
	 * @return boolean
	 */
	public static function does_checkout_support_gift_card() {
		// Return if cart contains subscription product.
		$cart_has_subscription_product = class_exists( '\WC_Subscriptions_Cart' ) && \WC_Subscriptions_Cart::cart_contains_subscription();

		// Return if cart contains a gift card product.
		$cart_has_gift_card      = self::cart_contains_gift_card();
		$does_cart_has_pre_order = self::cart_contains_upon_release_pre_order();

		if ( is_checkout() && ( $cart_has_subscription_product || $cart_has_gift_card || $does_cart_has_pre_order ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Creates an image atatchement for the gift card placeholder.
	 *
	 * @since 4.2.0
	 */
	public function add_gift_card_image_placeholder() {
		$placeholder_image = get_option( 'wc_square_gift_card_placeholder_id', false );

		if ( ! empty( $placeholder_image ) ) {
			if ( ! is_numeric( $placeholder_image ) ) {
				return;
			} elseif ( $placeholder_image && wp_attachment_is_image( $placeholder_image ) ) {
				return;
			}
		}

		$upload_dir = wp_upload_dir();
		$source     = WC_SQUARE_PLUGIN_PATH . '/assets/images/gift-card-featured-image.png';
		$filename   = $upload_dir['basedir'] . '/wc-square-gift-card-placeholder.png';

		if ( ! file_exists( $filename ) ) {
			copy( $source, $filename ); // @codingStandardsIgnoreLine.
		}

		if ( ! file_exists( $filename ) ) {
			update_option( 'wc_square_gift_card_placeholder_id', 0 );
			return;
		}

		$filetype   = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $filename );

		if ( is_wp_error( $attach_id ) ) {
			update_option( 'wc_square_gift_card_placeholder_id', 0 );
			return;
		}

		update_option( 'wc_square_gift_card_placeholder_id', $attach_id );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}

	/**
	 * Returns true if the cart contains a Gift Card.
	 * False otherwise.
	 *
	 * @since 4.2.0
	 *
	 * @return boolean
	 */
	public static function cart_contains_gift_card() {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];

			if ( Product::is_gift_card( $product ) ) {
				return true;
			};
		}

		return false;
	}

	/**
	 * Returns true if the cart contains a Pre Order with Charge Upon Release.
	 *
	 * @return boolean
	 */
	public static function cart_contains_upon_release_pre_order() {
		if ( ! class_exists( '\WC_Pre_Orders_Cart' ) ) {
			return false;
		}

		$does_cart_have_pre_order = \WC_Pre_Orders_Cart::cart_contains_pre_order();
		$is_charged_upon_release  = \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() );

		if ( $does_cart_have_pre_order && $is_charged_upon_release ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue scripts ans=d styles required for Gift Cards.
	 *
	 * @since 3.7.0
	 */
	public function enqueue_scripts() {
		/**
		 * Hook to filter JS args for Gift cards.
		 *
		 * @since 3.7.0
		 * @param array Array of args.
		 */
		$args = apply_filters(
			'wc_square_gift_card_js_args',
			array(
				'applicationId'       => $this->gateway->get_application_id(),
				'locationId'          => wc_square()->get_settings_handler()->get_location_id(),
				'gatewayId'           => $this->gateway->get_id(),
				'gatewayIdDasherized' => $this->gateway->get_id_dasherized(),
				'generalError'        => __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-square' ),
				'ajaxUrl'             => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'applyGiftCardNonce'  => wp_create_nonce( 'wc-square-apply-gift-card' ),
				'logging_enabled'     => $this->gateway->debug_log(),
				'orderId'             => absint( get_query_var( 'order-pay' ) ),
			)
		);

		if ( is_checkout() || is_product() ) {
			wp_enqueue_script(
				'wc-square-gift-card',
				$this->gateway->get_plugin()->get_plugin_url() . '/assets/js/frontend/gift-card.min.js',
				array( 'jquery' ),
				Plugin::VERSION,
				true
			);

			wc_enqueue_js( sprintf( 'window.wc_square_gift_card_handler = new WC_Square_Gift_Card_Handler( %s );', wp_json_encode( $args ) ) );
		}

		if ( is_checkout() || is_product() ) {
			wp_enqueue_style(
				'wc-square-gift-card',
				$this->gateway->get_plugin()->get_plugin_url() . '/assets/css/frontend/wc-square-gift-card.min.css',
				array(),
				Plugin::VERSION
			);
		}
	}

	/**
	 * Filters order review fragments to load Gift Card HTML.
	 *
	 * @since 3.7.0
	 *
	 * @param array $fragments Array of fragments.
	 */
	public function add_gift_card_fragments( $fragments ) {
		if ( ! self::does_checkout_support_gift_card() ) {
			return $fragments;
		}

		$payment_token = WC()->session->woocommerce_square_gift_card_payment_token;
		$is_sandbox    = wc_square()->get_settings_handler()->is_sandbox();
		$cart_total    = WC()->cart->total;
		$gift_card     = null;
		$response      = array(
			'is_error'        => false, // Boolean to indicate error with payment token.
			'has_balance'     => true,  // Boolean to indicate if the Gift card has sufficient funds.
			'current_balance' => 0,     // Gift card balance amount after applying.
			'post_balance'    => 0,     // Gift card balance amount after applying.
			'difference'      => 0,      // The difference amount needed to be charged on the credit card in case the gift card has insufficient funds.
		);

		if ( $is_sandbox ) {
			// The card allowed for testing with the Sandbox account has fund of $1.
			$response['current_balance'] = 1;

			if ( $response['current_balance'] < $cart_total ) {
				$response['has_balance'] = false;
				$response['difference']  = $cart_total - $response['current_balance'];
			} else {
				$response['has_balance']  = true;
				$response['post_balance'] = $response['current_balance'] - $cart_total;
			}
		} else {
			if ( $payment_token ) {
				$api_response   = $this->gateway->get_api()->retrieve_gift_card( $payment_token );
				$gift_card_data = $api_response->get_data();

				if ( $gift_card_data instanceof \Square\Models\RetrieveGiftCardFromNonceResponse ) {
					$gift_card                   = $gift_card_data->getGiftCard();
					$balance_money               = $gift_card->getBalanceMoney();
					$response['current_balance'] = (float) Square_Helper::number_format( Money_Utility::cents_to_float( $balance_money->getAmount() ) );

					if ( $response['current_balance'] < $cart_total ) {
						$response['has_balance'] = false;
						$response['difference']  = $cart_total - $response['current_balance'];
					} else {
						$response['has_balance']  = true;
						$response['post_balance'] = $response['current_balance'] - $cart_total;
					}
				} else {
					$response['is_error'] = true;

					if ( is_array( $gift_card_data ) ) {
						$log = new \WC_Logger();

						foreach ( $gift_card_data as $square_error ) {
							if ( $square_error instanceof \Square\Models\Error ) {
								/** @var \Square\Models\Error $square_error */
								$log->log( 'error', $square_error->getDetail() );
							}
						}
					}
				}
			} else {
				$response['is_error'] = true;
			}
		}

		$charge_type        = $payment_token && ! $response['has_balance'] ? 'PARTIAL' : 'FULL';
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$cash_app_available = isset( $available_gateways[ plugin::CASH_APP_PAY_GATEWAY_ID ] );

		ob_start();
		?>

		<?php if ( $payment_token && ! $response['has_balance'] ) : ?>
		<table id="square-gift-card-split-details">
			<thead>
				<th><?php esc_html_e( 'Payment split', 'woocommerce-square' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'woocommerce-square' ); ?></th>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Gift card', 'woocommerce-square' ); ?></td>
					<td><?php echo wc_price( $cart_total - $response['difference'], array( 'currency' => get_woocommerce_currency() ) ); ?></td>
				</tr>
				<tr>
					<td>
						<?php
						if ( $cash_app_available ) {
							esc_html_e( 'Credit card/Cash App Pay', 'woocommerce-square' );
						} else {
							esc_html_e( 'Credit card', 'woocommerce-square' );
						}
						?>
					</td>
					<td><?php echo wc_price( $response['difference'], array( 'currency', get_woocommerce_currency() ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php endif; ?>
		<div id="square-gift-card-wrapper">
			<div id="square-gift-card-application" <?php echo $payment_token ? 'style="display: none;"' : 'style="display: flex;"'; ?>>
				<div id="square-gift-card-title"><?php esc_html_e( 'Have a Square Gift Card?', 'woocommerce-square' ); ?></div>
				<div id="square-gift-card-fields-input"></div>

				<div id="square-gift-card-apply-button-wrapper">
					<button type="button" id="square-gift-card-apply-btn">
						<?php esc_html_e( 'Apply', 'woocommerce-square' ); ?>
					</button>
				</div>
			</div>

			<div class="square-gift-card-response" style="<?php echo $payment_token ? 'display: block;' : ''; ?>">
				<div class="square-gift-card-response__header square-gift-card-response__header<?php echo ! $response['is_error'] ? '--success' : '--fail'; ?>">
					<?php
					if ( $response['is_error'] ) {
						printf( esc_html__( 'There was an error while applying the gift card.', 'woocommerce-square' ) );
					} else {
						printf(
							wp_kses_post(
								/* translators: %s - amount to be charged on the gift card. */
								__( '%s will be applied from the gift card.', 'woocommerce-square' )
							),
							wc_price( $cart_total - $response['difference'], array( 'currency' => get_woocommerce_currency() ) )
						);
					}
					?>
				</div>
				<div class="square-gift-card-response__content">
					<?php
					if ( ! $response['is_error'] ) {
						if ( $response['has_balance'] ) {
							printf(
								wp_kses_post(
									/* translators: %s - balance amount in the gift card after placing the order. */
									__( 'The remaining gift card balance after placing this order will be <strong>%s</strong>', 'woocommerce-square' )
								),
								wc_price( $response['post_balance'], array( 'currency' => get_woocommerce_currency() ) )
							);
						} else {
							printf(
								wp_kses_post(
									/* translators: %1$s - remaining amount to be paid using the credit card or cash app pay; %2$s - payment method. */
									__( "Your gift card doesn't have enough funds to cover the order total. The remaining amount of <strong>%1\$s</strong> would need to be paid with a %2\$s.", 'woocommerce-square' )
								),
								wc_price( $response['difference'], array( 'currency' => get_woocommerce_currency() ) ),
								$cash_app_available ? __( 'credit card or cash app pay', 'woocommerce-square' ) : __( 'credit card', 'woocommerce-square' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
						}
					}
					?>
					<a id="square-gift-card-remove" href="#">
						<?php esc_html_e( 'Use a different gift card', 'woocommerce-square' ); ?>
					</a>
				</div>
			</div>

			<?php if ( $payment_token ) : ?>
			<div id="square-gift-card-hidden-fields">
				<input name="square-gift-card-payment-nonce" type="hidden" value="<?php echo esc_attr( $payment_token ); ?>" />
				<input name="square-charge-type" type="hidden" value="<?php echo esc_attr( $charge_type ); ?>" />
				<?php if ( ! $response['has_balance'] ) : ?>
					<input name="square-gift-card-charged-amount" type="hidden" value="<?php echo esc_attr( $response['current_balance'] ); ?>" />
					<input name="square-gift-card-difference-amount" type="hidden" value="<?php echo esc_attr( $response['difference'] ); ?>" />
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php

		$fragments['.woocommerce-square-gift-card-html'] = ob_get_clean();

		if ( $payment_token ) {
			ob_start();

			if ( WC()->cart->needs_payment() ) {
				$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
				WC()->payment_gateways()->set_current_gateway( $available_gateways );
			} else {
				$available_gateways = array();
			}

			wc_get_template(
				'Templates/payment.php',
				array(
					'checkout'           => WC()->checkout(),
					'available_gateways' => $available_gateways,
					// PHPCS ignored as it is a Woo core hook.
					'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce-square' ) ), // phpcs:ignore
					'is_error'           => $response['is_error'],
					'has_balance'        => $response['has_balance'],
				),
				'',
				WC_SQUARE_PLUGIN_PATH . 'includes/Gateway/'
			);

			$payment_methods = ob_get_clean();

			$fragments['.woocommerce-checkout-payment'] = $payment_methods;
			$fragments['has-balance']                   = $response['has_balance'];
		}

		return $fragments;
	}

	/**
	 * Ajax callback to apply a Gift Card.
	 *
	 * @since 3.7.0
	 */
	public function apply_gift_card() {
		check_ajax_referer( 'wc-square-apply-gift-card', 'security' );

		$payment_token = isset( $_POST['token'] ) ? wc_clean( wp_unslash( $_POST['token'] ) ) : false;

		if ( ! $payment_token ) {
			wp_send_json_error();
		}

		WC()->session->set( 'woocommerce_square_gift_card_payment_token', $payment_token );

		wp_send_json_success();
	}

	/**
	 * Ajax callback to remove Gift Card.
	 *
	 * @since 3.7.0
	 */
	public function remove_gift_card() {
		WC()->session->set( 'woocommerce_square_gift_card_payment_token', null );

		wp_send_json_success();
	}

	/**
	 * Delete Gift card session after order complete.
	 *
	 * @since 3.7.0
	 */
	public function delete_sessions() {
		WC()->session->set( 'woocommerce_square_gift_card_payment_token', null );
	}

	/**
	 * Returns true if the gift card product added to cart is a new gift card.
	 *
	 * @since 4.2.0
	 *
	 * @return boolean
	 */
	public static function is_new() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['square-gift-card-buying-option'] ) ? 'new' === wc_clean( wp_unslash( $_POST['square-gift-card-buying-option'] ) ) : false;
	}

	/**
	 * Returns true if the gift card product added to cart is to reload an existing gift card.
	 *
	 * @since 4.2.0
	 *
	 * @return boolean
	 */
	public static function is_load() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['square-gift-card-buying-option'] ) ? 'load' === wc_clean( wp_unslash( $_POST['square-gift-card-buying-option'] ) ) : false;
	}
}
