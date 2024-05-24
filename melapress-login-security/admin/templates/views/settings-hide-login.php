<?php
/**
 * Handles policies admin area.
 *
 * @package WordPress
 * @subpackage wpassword
 */

 $sidebar_required    = false;
 /* @free:start */
 // Override in free edition.
 $sidebar_required    = true;
 /* @free:end */
 $form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
 $login_control = new \PPMWP\MLS_Login_Page_Control();
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php esc_attr_e( $form_class ); ?>">
		<div class="ppm-settings">

			<!-- getting started -->
			<div class="page-head" style="padding-right: 0">
				<h2><?php esc_html_e( 'Change the login page URL', 'ppm-wp' ); ?></h2>
				<p class="description" style="max-width: none;">
					<?php esc_html_e( 'The default WordPress login page URL is /wp-admin/ or /wp-login.php. Improve the security of your website by changing the URL of the WordPress login page to anything you want, thus preventing easy access to bots and attackers. To change the URL just specify the new path in the placeholder below. Do not include the trailing slash', 'ppm-wp' ); ?>
				</p>
			</div>

            <div class="settings-tab ppm-login-page-settings">
				<table class="form-table">
					<tbody>
                    	<?php echo $login_control::render_login_page_url_settings(); ?>
                    </tbody>
                </table>
				<?php
				?>
            </div>
		</div>

		<?php wp_nonce_field( PPMWP_PREFIX . '_nonce_form', PPMWP_PREFIX . '_nonce' ); ?>
		
		<div class="submit">
			<input type="submit" name="_ppm_save" class="button-primary"
		value="<?php echo esc_attr( __( 'Save Changes', 'ppm-wp' ) ); ?>" />
		</div>
	</form>

	<?php
	/* @free:start */
	require_once PPM_WP_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

	?>

</div> 
