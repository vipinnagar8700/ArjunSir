<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * An ajax action to handle resetting inactive users,
 *
 * @since 2.1.0
 *
 * @package WordPress
 */

namespace PPMWP\Ajax;

use \PPMWP\Helpers\OptionsHelper;
use \PPMWP\Helpers\PPM_EmailStrings;

/**
 * An abstract class to be used when creating ajax actions. This ensures a consistent
 * way of using them and invoking them.
 */
class UnlockInactiveUser implements AjaxInterface {

	/**
	 * The string used to generate/check nonces.
	 *
	 * @var string
	 */
	const NONCE_KEY = 'inactive_users_nonce';

	/**
	 * Caller.
	 *
	 * @var string
	 */
	public $caller;

	/**
	 * Sets up the properties for this ajax endpoint.
	 *
	 * @method __construct
	 * @since  2.1.0
	 * @param  {InactiveUsers} $caller Instance of the main InactiveUsers class.
	 */
	public function __construct( $caller ) {
		$this->caller = $caller;
	}


	/**
	 * Register the handler and nonce, this is the entrypoint.
	 *
	 * @method register
	 * @since  2.1.0
	 */
	public function register() {
		add_action( 'wp_ajax_ppmwp_unlock_inactive_user', array( $this, 'reset_inactive_user' ) );
	}

	/**
	 * Calls the method for this ajax action.
	 *
	 * @method reset_inactive_user
	 * @since  2.1.0
	 */
	public function reset_inactive_user() {
		$this->action();
	}

	/**
	 * The action to run, optionally this can just register the hook.
	 *
	 * @method action
	 * @since  2.1.0
	 */
	public function action() {
		check_ajax_referer( self::NONCE_KEY );
		// get the ids list as an array.
		$id = ( isset( $_POST['user'] ) ) ? (int) $_POST['user'] : 0;

		$reset_time = OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $id );

		$userdata = get_user_by( 'id', $id );

		$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

		// Check if we are currently unblocking a user, rather than clearing a users inactivity.
		$currently_unlocking_user_logins = ( isset( $_POST['unblocking_user'] ) && 'true' === $_POST['unblocking_user'] ) ? true : false;

		if ( $currently_unlocking_user_logins ) {
			$failed_logins           = new \PPMWP\PPM_Failed_Logins();
			$clear_failed_login_data = $failed_logins->clear_failed_login_data( $id, false );
			$reset_password          = OptionsHelper::string_to_bool( $role_options->failed_login_reset_on_unblock );
			$failed_logins->send_logins_unblocked_notification_email_to_user( $id, $reset_password );
			// Now we can bailed, firing off a success beacon to anything watching.
			wp_send_json_success(
				array(
					'user_id'    => $id,
					'reset_time' => date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $reset_time ),
				)
			);
		}

		// remove user from inactive list.
		OptionsHelper::clear_inactive_data_about_user( $id );

		// remember this reset time.
		OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $id );

		$reset_password = OptionsHelper::string_to_bool( $role_options->inactive_users_reset_on_unlock );
		$this->send_unlocked_notification_email_to_user( $id, $reset_password );

		wp_send_json_success(
			array(
				'user_id'    => $id,
				'reset_time' => date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $reset_time ),
			)
		);
	}

	/**
	 * Can be used to check the nonce passed for this action.
	 *
	 * @method check_nonce
	 * @since  2.1.0
	 */
	public static function check_nonce() {
		check_ajax_referer( self::NONCE_KEY );
	}

	/**
	 * Send user a notification email once the account has been unlocked, also reset password if required.
	 *
	 * @param  int  $user_id                User ID to notify.
	 * @param  bool $reset_password     Set to true if we are resetting the users PW and emailing them a reset link.
	 */
	public function send_unlocked_notification_email_to_user( $user_id, $reset_password ) {

		// Access plugin instance.
		$ppm = ppm_wp();

		// Grab user data object.
		$user_data = get_userdata( $user_id );

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		// Prepare email details.
		$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
		$from_email = sanitize_email( $from_email );
		$headers[]  = 'From: ' . $from_email;

		$title = \PPMWP\PPM_EmailStrings::replace_email_strings( isset( $ppm->options->ppm_setting->user_unlocked_email_title ) ? $ppm->options->ppm_setting->user_unlocked_email_title : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unlocked_email_title' ), $user_id );

		// Only reset the password if the role has this option enabled.
		if ( $reset_password ) {
			$key = get_password_reset_key( $user_data );
			if ( ! is_wp_error( $key ) ) {
				$update = update_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $key );
			}
		}

		$args = array();

		if ( $reset_password ) {
			$login_page                = OptionsHelper::get_password_reset_page();
			$msg                       = isset( $ppm->options->ppm_setting->user_unlocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unlocked_email_reset_message : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unlocked_email_reset_message' );
			$args['reset_or_continue'] = $msg . ' ' . esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . "\n";
		} else {
			$msg                       = isset( $ppm->options->ppm_setting->user_unlocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unlocked_email_continue_message : \PPMWP\PPM_EmailStrings::get_default_string( 'user_unlocked_email_continue_message' );
			$args['reset_or_continue'] = $msg . "\n";
		}

		$content       = isset( $ppm->options->ppm_setting->user_unlocked_email_body ) ? $ppm->options->ppm_setting->user_unlocked_email_body : \PPMWP\PPM_EmailStrings::default_message_contents( 'user_unlocked' );
		$email_content = \PPMWP\PPM_EmailStrings::replace_email_strings( $content, $user_id, $args );

		// Only send the email if applicable.
		if ( isset( $ppm->options->ppm_setting->send_user_unlocked_email ) && \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->send_user_unlocked_email ) ) {
			// Fire off the mail.
			wp_mail( $user_email, wp_specialchars_decode( $title ), $email_content, $headers );
		}
	}

}
