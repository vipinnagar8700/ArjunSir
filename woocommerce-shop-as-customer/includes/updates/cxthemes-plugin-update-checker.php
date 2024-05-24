<?php

// Include the 3rd party - Plugin Update Checker
require 'plugin-update-checker/plugin-update-checker.php';

if ( class_exists( 'CX_Shop_As_Customer_Plugin_Update_Checker' ) ) return;

class CX_Shop_As_Customer_Plugin_Update_Checker {
	
	public $metadataUrl;
	public $pluginFile;
	public $slug;
	public $plugin_update_checker;
	public $cx_updater_version;
	
	public function __construct( $pluginFile, $slug ) {
		
		$this->pluginFile         = $pluginFile;
		$this->slug               = $slug;
		$this->cx_updater_version = '1'; // Used to increment the Enqueue Scripts to avoid caching.
		
		$this->metadataUrl = 'http://updates.cxthemes.com/'; // Live
		// $this->metadataUrl = 'http://localhost/cxthemes-updates/'; // Local. testing.
		
		// Merge in nthe query_vars.
		$this->metadataUrl = add_query_arg(
			array(
				'action'      => 'get_metadata',
				'slug'        => $slug,
				'license_key' => get_option( "cx_{$this->slug}_envato_purchase_code" ),
				'wc_version'  => get_option( 'woocommerce_db_version' ),
			),
			$this->metadataUrl
		);
		
		// Make sure there is never mailformed multiple slashed url check that will fail on some setups.
		// e.g. `http://updates.cxthemes.com//?test=test`
		$this->metadataUrl = preg_replace('/([^:])(\/{2,})/', '$1/', $this->metadataUrl);
		
		// s( $this->metadataUrl );
		
		/**
		 * Main: do the initial plugin update check.
		 */
		$this->plugin_update_checker = Puc_v4_Factory::buildUpdateChecker(
			$this->metadataUrl,
			$pluginFile,
			$slug
		);
		
		// Enqueue Scripts/Styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Render purchase-code interfaces.
		add_action( 'admin_notices', array( $this, 'show_purchase_code_interface' ), 10 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 5, 2 );
	}
	
	/**
	 * Enqueue CSS and Scripts
	 *
	 * @date	20-08-2014
	 * @since	1.0
	 */
	public function enqueue_scripts( $hook_suffix ) {
		global $pagenow;
		
		if ( 'plugins.php' == $pagenow || 'plugin-install.php' == $pagenow ) {
			
			wp_enqueue_style(
				'cx-plugin-update-css',
				plugins_url( 'assets/updates.css', __FILE__ ),
				array(),
				$this->cx_updater_version
			);
			
			wp_enqueue_script(
				'cx-plugin-update-js',
				plugins_url( 'assets/updates.js', __FILE__ ),
				array( 'jquery' ),
				$this->cx_updater_version
			);
			
			if ( isset( $_GET['plugin'] ) && $this->slug == $_GET['plugin'] ) {
				wp_localize_script(
					'cx-plugin-update-js',
					'cx_plugin_update_object',
					array(
						'current_plugin' => $this->slug,
						'current_plugin_is_licenced' => ( ( bool ) get_option( "cx_{$this->slug}_envato_purchase_code" ) ),
						'update_button_text' => __( 'Please register on the Plugins page to get this update', 'shop-as-customer' ),
						'plugin_register_url' => esc_url( admin_url( "plugins.php?{$this->slug}-purchase-code" ) ),
					)
				);
			}
		}
	}
	
	// Purchase Code interface.
	function show_purchase_code_interface() {
		global $pagenow;
		
		if ( 'plugins.php' == $pagenow && isset( $_GET["{$this->slug}-purchase-code"] ) ) {
			
			// Get submitted purchase code.
			$submitted_code = isset( $_POST['cx-plugin-update-code'] ) ? trim( $_POST['cx-plugin-update-code'] ) : '' ;
			
			// Validation, on form submit.
			
			$notification = FALSE; // Control var.
			
			if ( isset( $_POST['cx-plugin-update-code-action'] ) && 'submit' == $_POST['cx-plugin-update-code-action'] ) {
				
				if ( 36 !== strlen( $submitted_code ) ) {
					
					/**
					 * Submitted code is the wrong format.
					 */
					
					$notification = 'wrong-format';
					delete_option( "cx_{$this->slug}_envato_purchase_code" );
				}
				else {
					
					/**
					 * Submitted code is the correct format.
					 */
					
					// This must be checked with notices-hidden - @ - as it fails with a notice.
					@ $result = $this->plugin_update_checker->requestInfo( array( 'license_key' => $submitted_code ) );
					
					// Debugging:
					// s( $result );
					
					if ( null == $result || ! isset( $result->download_url ) ) {
						
						/**
						 * The update server is down :(
						 */
						
						$notification = 'connection-error';
						delete_option( "cx_{$this->slug}_envato_purchase_code" );
						// s( $notification );
					}
					else {
						
						$parts = parse_url( $result->download_url );
						parse_str( $parts['query'], $query );
						
						if ( ! isset( $query['license_key'] ) ) {
							
							/**
							 * We've checked and this purchase code is not valid- you don't own our product :(
							 */
							
							$notification = 'invalid-purchase-code';
							delete_option( "cx_{$this->slug}_envato_purchase_code" );
							// s( $notification );
						}
						else {
							
							/**
							 * Success, you own our product :)
							 */
							
							$notification = 'success';
							update_option( "cx_{$this->slug}_envato_purchase_code", $submitted_code );
							// s( $notification );
						}
					}
				}
			}
			
			?>
			<div class="cx-plugin-update-enter-code notice notice-success is-dismissibleZZ">
				<form method="post">
					
					<input type="hidden" name="cx-plugin-update-code-action" value="submit">
					
					<p><?php _e( 'To get automatic updates you need to input your valid purchase code, for our product, from Envato. The purchase code looks like: <code>f128a5a1-5a1c-4e4e-82ec-a12b3c4d5e6f</code> and should have been emailed to you when you bought our plugin, or you can retrieve it by logging into <a href="http://codecanyon.net/" target="_blank">CodeCanyon.net</a> go to Downloads > <em>Plugin Name</em> > Download > License certificate & purchase code.', 'shop-as-customer' ); ?></p>
					
					<?php if ( $notification ) { ?>
						
						<!-- Validation Notifications -->
						
						<?php if ( 'wrong-format' == $notification ) { ?>
							
							<div class="cx-plugin-update-notification">
								<?php _e( 'That is not a valid purchase code format. It should look like: <code>f128a5a1-5a1c-4e4e-82ec-a12b3c4d5e6f</code>.', 'shop-as-customer' ); ?>
							</div>
							
						<?php } else if ( 'connection-error' == $notification ) { ?>
							
							<div class="cx-plugin-update-notification">
								<?php _e( 'You are unable to connect to our update server at this time. Please check your connection, or try again later.', 'shop-as-customer' ); ?>
							</div>
						
						<?php } else if ( 'invalid-purchase-code' == $notification ) { ?>
							
							<div class="cx-plugin-update-notification">
								<?php _e( "It doesn't seem like you have purchased our plugin yet. Please <a href='#'>purchase it from CodeCanyon</a> to get access to support and updates.", 'shop-as-customer' ); ?>
							</div>
						
						<?php } else if ( 'success' == $notification ) { ?>
							
							<div class="cx-plugin-update-notification cx-plugin-update-notification-success">
								<?php _e( "Thanks. You're registered and can now receive updates.", 'shop-as-customer' ); ?>
							</div>
							
						<?php }  ?>
						
						<!-- / Validation Notifications -->
						
					<?php } ?>
					
					<div class="ec-plugin-update-row">
						<label><?php _e( 'Purchase Code', 'shop-as-customer' ); ?></label> 
						<input type="text" name="cx-plugin-update-code" value="<?php echo esc_attr( ( $submitted_code ? $submitted_code : get_option( "cx_{$this->slug}_envato_purchase_code" ) ) ); ?>" />&nbsp;
						<input type="submit" class="button" value="<?php _e( 'Save', 'shop-as-customer' ); ?>" >
					</div>
					
				</form>
			</div>
			<?php
		}
	}
	
	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		
		if ( $file == plugin_basename( $this->pluginFile ) ) {
			
			$purchase_code = get_option( "cx_{$this->slug}_envato_purchase_code" );
			$url = esc_url( admin_url( "plugins.php?{$this->slug}-purchase-code" ) );
			
			if ( ! $purchase_code ) {
				
				// It's required that we remove this filter so the word 'Check for Updates' doesn't appear twice.
				remove_filter( 'plugin_row_meta', array( $this->plugin_update_checker, 'addCheckForUpdatesLink'), 10, 2);
				
				$row_meta = array(
					'support' => '<a class="cx-plugin-update-row-meta-register-for-updates" href="' . $url . '">' . __( 'Register for Updates', 'shop-as-customer' ) . '</a>'  .  '<span class="cx-plugin-update-row-meta-register-for-updates-inline"><span class="cx-plugin-update-row-meta-divider"> | </span>' . sprintf( __( '<em><a href="%s">Register here</a> to get this update</em>', 'shop-as-customer' ), $url ) . '</span>',
				);
				$links = array_merge( $links, $row_meta );
			}
			else {
				
				$row_meta = array(
					'support' => '<a class="cx-plugin-update-row-meta-change-purchase-code" href="' . $url . '" title="' . esc_attr( __( '', 'shop-as-customer' ) ) . '">' . __( 'Edit registration', 'shop-as-customer' ) . '</a>',
				);
				$links = array_merge( $links, $row_meta );
			}
		}
		
		return (array) $links;
	}
	
}
