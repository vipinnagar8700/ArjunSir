<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package PPMWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( '\PPMWP\RestrictLogins' ) ) {
	add_action( 'ppm_settings_timed_logins_settings', array( '\PPMWP\RestrictLogins', 'settings_markup' ), 100, 2 );
	add_action( 'show_user_profile', array( '\PPMWP\RestrictLogins', 'add_user_profile_field' ) );
	add_action( 'edit_user_profile', array( '\PPMWP\RestrictLogins', 'add_user_profile_field' ) );
	add_action( 'personal_options_update', array( '\PPMWP\RestrictLogins', 'save_user_profile_field' ) );
	add_action( 'edit_user_profile_update', array( '\PPMWP\RestrictLogins', 'save_user_profile_field' ) );
	add_action( 'authenticate', array( '\PPMWP\RestrictLogins', 'pre_login_check' ), 30, 3 );
}