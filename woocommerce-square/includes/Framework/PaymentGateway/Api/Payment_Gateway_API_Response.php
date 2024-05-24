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
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 *
 * Modified by WooCommerce on 15 December 2021.
 */

namespace WooCommerce\Square\Framework\PaymentGateway\Api;
use WooCommerce\Square\Framework\Api\API_Response;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API Response
 */
interface Payment_Gateway_API_Response extends API_Response {

	/**
	 * Checks if the transaction was successful.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if approved, false otherwise
	 */
	public function transaction_approved();


	/**
	 * Returns true if the transaction was held, for instance due to AVS/CSC
	 * Fraud Settings.  This indicates that the transaction was successful, but
	 * did not pass a fraud check and should be reviewed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if the transaction was held, false otherwise
	 */
	public function transaction_held();


	/**
	 * Gets the response status message, or null if there is no status message
	 * associated with this transaction.
	 *
	 * @since 3.0.0
	 *
	 * @return string status message
	 */
	public function get_status_message();


	/**
	 * Gets the response status code, or null if there is no status code
	 * associated with this transaction.
	 *
	 * @since 3.0.0
	 *
	 * @return string status code
	 */
	public function get_status_code();


	/**
	 * Gets the response transaction id, or null if there is no transaction id
	 * associated with this transaction.
	 *
	 * @since 3.0.0
	 *
	 * @return string transaction id
	 */
	public function get_transaction_id();


	/**
	 * Gets the payment type: 'credit-card', 'echeck', etc...
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_payment_type();


	/**
	 * Returns a message appropriate for a frontend user.  This should be used
	 * to provide enough information to a user to allow them to resolve an
	 * issue on their own, but not enough to help nefarious folks fishing for
	 * info.
	 *
	 * @see Payment_Gateway_API_Response_Message_Helper
	 *
	 * @since 3.0.0
	 *
	 * @return string user message, if there is one
	 */
	public function get_user_message();
}
