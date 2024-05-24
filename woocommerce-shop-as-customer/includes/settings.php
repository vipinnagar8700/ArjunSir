<?php

/**
 * Add a submenu item to the WooCommerce menu
 */
add_action( 'admin_menu', 'cxsac_admin_menu' );
function cxsac_admin_menu() {

	add_submenu_page(
		'options-general.php',
		// 'woocommerce',
		__( 'Shop as Customer', 'shop-as-customer' ),
		__( 'Shop as Customer', 'shop-as-customer' ),
		'manage_options', // 'manage_networks',
		'shop_as_customer_settings',
		'cxsac_admin_page'
	);
}

function cxsac_admin_page() {
	
	// Save settings if data has been posted
	if ( ! empty( $_POST ) )
		cxsac_save_settings();

	// Add any posted messages
	if ( ! empty( $_GET['wc_error'] ) )
		//self::add_error( stripslashes( $_GET['wc_error'] ) );

	 if ( ! empty( $_GET['wc_message'] ) )
		//self::add_message( stripslashes( $_GET['wc_message'] ) );

	//self::show_messages();
	?>
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<div class="cxsac-wrap-settings woocommerce">
			
			<h1><?php _e( 'Shop as Customer', 'shop-as-customer' ); ?><span class="dashicons dashicons-arrow-right"></span><?php _e( 'Settings', 'shop-as-customer' ); ?></h1>
			
			<?php
			$settings = cxsac_get_settings();
			WC_Admin_Settings::output_fields( $settings );
			?>
			
			<p class="submit">
				<input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'shop-as-customer' ); ?>" />
				<?php wp_nonce_field( 'woocommerce-settings' ); ?>
				
				<a class="button button-alternate sac-settings-shop-as"><?php _e( 'Shop as Customer', 'shop-as-customer' ); ?></a>
			</p>
			
		</div>
	</form>
	
	<?php
}

/**
 * Get settings array
 *
 * @return array
 */
function cxsac_get_settings() {
	global $wp_roles;
	
	// Get roles if not set.
	if ( ! isset( $wp_roles ) ) {
		$wp_roles = wp_roles();
	}
	
	// Get all the roles that have 'edit_shop_order' role - they are the ones that could possibly use Shop as Customer.
	$available_users = array();
	foreach ( $wp_roles->roles as $role_key => $role_value ) {
		
		if ( isset( $role_value['capabilities']['edit_shop_order'] ) )
			$available_users[$role_key] = $role_value['name'];
	}
			
	$settings = array(
		
		// --------------------
		
		array(
			'id'   => 'cxsac_settings',
			'name' => __( 'General Settings', 'shop-as-customer' ),
			'type' => 'title',
			'desc' => '',
		),
		array(
			'id'      => 'shop_as_user_role', // Named like this due to legacy - 'cxsac_user_can_create_customers',
			'name'    => __( 'Minimum User Role', 'shop-as-customer' ),
			'desc'    => __( 'Which users can shop as other users.', 'shop-as-customer' ),
			'type'    => 'select',
			'options' => array(
				'super_admin' => 'Super Admin',
				'administrator' => 'Administrator',
				'shop_manager' => 'Shop Manager',
			),
			'options' => $available_users,
			'default' => '0',
		),
		array(
			'id'      => 'cxsac_user_role_heirarchy',
			'name'    => __( 'User Role Hierarchy', 'shop-as-customer' ),
			'desc'    => __( 'To prevent less privileged users switching to more privileged users, we need to know the hierarchy of your user roles. If you are not using any custom user roles then you can leave this as is. If you are using custom roles then please make sure all your role types are represented here in order from most privileged to least privileged. Roles on the same row, separated using pipe "|", have equal privileges. e.g. "custom_role" role has the same capabilities as the "shop_manager".', 'shop-as-customer' ),
			'type'    => 'textarea',
			'css'     => 'height: 160px;',
			'default' => "super_admin
administrator
shop_manager | custom_role
customer
subscriber",
			'placeholder' => "super_admin
administrator
shop_manager | custom_role
customer
subscriber",
		),
		array(
			'id'       => 'cxsac_autofocus_customer_search',
			'name'     => __( 'Autofocus Customer Search', 'create-customer-order' ),
			'label'    => '',
			'desc'     => __( '', 'create-customer-order' ),
			'desc_tip' => __( "Autofocus the Customer Search field as the Switch To Customer modal opens (can speed up usability).", 'create-customer-order' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		),
		array(
			'id'       => 'cxsac_show_switch_back_bar',
			'name'     => __( "Show 'Switch Back' Bar", 'create-customer-order' ),
			'label'    => '',
			'desc'     => __( '', 'create-customer-order' ),
			'desc_tip' => __( "The 'Switch Back' bar shows by default. If it interferes with your theme display then uncheck this to switch it off and use the 'Switch Back' link in the Admin Bar.", 'create-customer-order' ),
			'type'     => 'checkbox',
			'default'  => 'yes',
		),
		array(
			'id'   => 'cxsac_settings',
			'type' => 'sectionend',
		),
		
	);

	return $settings;
}

/**
 * Save Settings.
 *
 * Loops though the woocommerce options array and outputs each field.
 *
 * @access public
 * @return bool
 */
function cxsac_save_settings() {
	
	if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) )
		die( __( 'Action failed. Please refresh the page and retry.', 'shop-as-customer' ) );
	
	$settings = cxsac_get_settings();
	
	if ( empty( $_POST ) )
		return false;
	
	// Options to update will be stored here
	$update_options = array();

	// Loop options and get values to save
	foreach ( $settings as $value ) {

		if ( ! isset( $value['id'] ) )
			continue;

		$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

		// Get the option name
		$option_value = null;

		switch ( $type ) {

			// Standard types
			case "checkbox" :

				if ( isset( $_POST[ $value['id'] ] ) ) {
					$option_value = 'yes';
				} else {
					$option_value = 'no';
				}

			break;

			case "textarea" :

				if ( isset( $_POST[$value['id']] ) ) {
					$option_value = wp_kses_post( trim( stripslashes( $_POST[ $value['id'] ] ) ) );
				} else {
					$option_value = '';
				}

			break;

			case "text" :
			case 'email':
			case 'number':
			case "select" :
			case "color" :
			case 'password' :
			case "single_select_page" :
			case "single_select_country" :
			case 'radio' :

				if ( $value['id'] == 'woocommerce_price_thousand_sep' || $value['id'] == 'woocommerce_price_decimal_sep' ) {

					// price separators get a special treatment as they should allow a spaces (don't trim)
					if ( isset( $_POST[ $value['id'] ] )  ) {
						$option_value = wp_kses_post( stripslashes( $_POST[ $value['id'] ] ) );
					} else {
						$option_value = '';
					}

				} elseif ( $value['id'] == 'woocommerce_price_num_decimals' ) {

					// price separators get a special treatment as they should allow a spaces (don't trim)
					if ( isset( $_POST[ $value['id'] ] )  ) {
						$option_value = absint( $_POST[ $value['id'] ] );
					} else {
					   $option_value = 2;
					}

				} elseif ( $value['id'] == 'woocommerce_hold_stock_minutes' ) {

					// Allow > 0 or set to ''
					if ( ! empty( $_POST[ $value['id'] ] )  ) {
						$option_value = absint( $_POST[ $value['id'] ] );
					} else {
						$option_value = '';
					}

					wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

					if ( $option_value != '' )
						wp_schedule_single_event( time() + ( absint( $option_value ) * 60 ), 'woocommerce_cancel_unpaid_orders' );

				} else {

				   if ( isset( $_POST[$value['id']] ) ) {
						$option_value = woocommerce_clean( stripslashes( $_POST[ $value['id'] ] ) );
					} else {
						$option_value = '';
					}

				}

			break;

			// Special types
			case "multiselect" :
			case "multi_select_countries" :

				// Get countries array
				if ( isset( $_POST[ $value['id'] ] ) )
					$selected_countries = array_map( 'wc_clean', array_map( 'stripslashes', (array) $_POST[ $value['id'] ] ) );
				else
					$selected_countries = array();

				$option_value = $selected_countries;

			break;

			case "image_width" :

				if ( isset( $_POST[$value['id'] ]['width'] ) ) {

					$update_options[ $value['id'] ]['width']  = woocommerce_clean( stripslashes( $_POST[ $value['id'] ]['width'] ) );
					$update_options[ $value['id'] ]['height'] = woocommerce_clean( stripslashes( $_POST[ $value['id'] ]['height'] ) );

					if ( isset( $_POST[ $value['id'] ]['crop'] ) )
						$update_options[ $value['id'] ]['crop'] = 1;
					else
						$update_options[ $value['id'] ]['crop'] = 0;

				} else {
					$update_options[ $value['id'] ]['width'] 	= $value['default']['width'];
					$update_options[ $value['id'] ]['height'] 	= $value['default']['height'];
					$update_options[ $value['id'] ]['crop'] 	= $value['default']['crop'];
				}

			break;

			// Custom handling
			default :

				do_action( 'woocommerce_update_option_' . $type, $value );

			break;

		}

		if ( ! is_null( $option_value ) ) {
			// Check if option is an array
			if ( strstr( $value['id'], '[' ) ) {

				parse_str( $value['id'], $option_array );

				// Option name is first key
				$option_name = current( array_keys( $option_array ) );

				// Get old option value
				if ( ! isset( $update_options[ $option_name ] ) )
					 $update_options[ $option_name ] = get_option( $option_name, array() );

				if ( ! is_array( $update_options[ $option_name ] ) )
					$update_options[ $option_name ] = array();

				// Set keys and value
				$key = key( $option_array[ $option_name ] );

				$update_options[ $option_name ][ $key ] = $option_value;

			// Single value
			} else {
				$update_options[ $value['id'] ] = $option_value;
			}
		}

		// Custom handling
		do_action( 'woocommerce_update_option', $value );
	}

	// Now save the options
	foreach( $update_options as $name => $value ) {
		
		$current_option = get_option( $name );
		$current_default = cxsac_get_default( $name );
		
		if ( $value === $current_default ) {
			delete_option( $name );
		}
		else if ( $value !== $current_option ) {
			update_option( $name, $value );
		}
	}

	return true;
}

/**
 * Get one of our options.
 *
 * Automatically mixes in our defaults if nothing is saved yet.
 *
 * @param  string $key key name of the option.
 * @return mixed       the value stored with the option, or the default if nothing stored yet.
 */
function cxsac_get_option( $key ) {
	return get_option( $key, cxsac_get_default( $key ) );
}

/**
 * Get one of defaults options.
 *
 * @param  string $key key name of the option.
 * @return mixed       the default set for that option, or FALSE if none has been set.
 */
function cxsac_get_default( $key ) {
	
	$settings = cxsac_get_settings();
	
	$default = FALSE;
	
	foreach ( $settings as $setting ) {
		if ( isset( $setting['id'] ) && $key == $setting['id'] && isset( $setting['default'] ) ) {
			$default = $setting['default'];
		}
	}
	
	return $default;
}


?>