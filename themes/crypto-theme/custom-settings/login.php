<?php
add_action('admin_post_nopriv_custom_user_login', 'custom_user_login');
add_action('admin_post_custom_user_login', 'custom_user_login');

function custom_user_login() {
    // Check if the form data is submitted
    if( isset($_POST['username_or_email']) && isset($_POST['password']) ) {
        $username_or_email = $_POST['username_or_email'];
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);

        // Attempt to log the user in
        $user = wp_signon(array(
            'user_login' => $username_or_email,
            'user_password' => $password,
            'remember' => $remember_me
        ), false);

        // Check if login was successful
        if( is_wp_error($user) ) {
            // Handle login errors
            wp_redirect( home_url('/publish/?login=failed') );
            exit;
        } else {
            // Redirect user after successful login
            wp_redirect( home_url('/my-account?login=Success') );
            exit;
        }
    } else {
        // Redirect back to login page if form data is not submitted
        wp_redirect( home_url('/my-account') );
        exit;
    }
}
