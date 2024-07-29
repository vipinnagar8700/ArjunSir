<?php
add_action('admin_post_nopriv_custom_user_register', 'custom_user_register');

function custom_user_register() {
    if( isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_POST['consentCheck']) ) {
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $consent_check = $_POST['consentCheck'];

        if( empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($consent_check) ) {
            // Handle empty fields
            wp_redirect( home_url('/my-account/?register=empty') );
            exit;
        }
 

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if( is_wp_error($user_id) ) {
            // Handle user creation error
            wp_redirect( home_url('/my-account/?register=failed') );
            exit;
        } else {
            
            $user = wp_signon(array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => true
            ));
            // Redirect user after successful registration
            wp_redirect( home_url('/my-account/?register=success') );
            exit;
        }
    } else {
        // Redirect back to registration page if form data is not submitted
        wp_redirect( home_url('/my-account') );
        exit;
    }
}

 
add_action('wp_ajax_check_user_exists', 'check_user_exists');
add_action('wp_ajax_nopriv_check_user_exists', 'check_user_exists');

function check_user_exists() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'check_user_exists_nonce' ) ) {
        echo json_encode(array('error' => 'Nonce verification failed.'));
        wp_die();
    }

    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    $response = array();

    if (!empty($username)) {
        $user_id = username_exists($username);
        $field = 'username';
        if ($user_id) {
            // User exists
            $response['exists'] = true;
            $response['field'] = $field;
            echo json_encode($response);
            wp_die();
        }
        
    }
     if (!empty($email)) {
        $user_id = email_exists($email);
        $field = 'email';
        if ($user_id) {
            // User exists
            $response['exists'] = true;
            $response['field'] = $field;
            echo json_encode($response);
            wp_die();
        }
    }
 
        $response['exists'] = false;
 

    echo json_encode($response);
    wp_die();
}
