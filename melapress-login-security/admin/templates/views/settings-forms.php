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
 $ppm = ppm_wp();
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php esc_attr_e( $form_class ); ?>">
		<div class="ppm-settings">

			<!-- getting started -->
			<div class="page-head" style="padding-right: 0">
				<h2><?php esc_html_e( 'Forms & Placement', 'ppm-wp' ); ?></h2>
				<p class="description" style="max-width: none"><?php esc_html_e( 'By default, the login and password security policies configured in this plugin can only be enforced on the native WordPress forms. However, the plugin also has out of the box support for third party popular plugins such as WooCommerce and BuddyPress. Use the checkboxes below to select on which forms you\'d like to also enforce the configured policies.', 'ppm-wp' ); ?></p>
				<br>
			</div>

			<div class="ppm-general-settings">
				<table class="form-table">
					<tbody>
						<tr class="setting-heading" valign="top">
							<th scope="row">
								<h3><?php esc_html_e( 'Standard forms', 'ppm-wp' ); ?></h3>							
							</th>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_attr_e( 'Wordpress forms', 'ppm-wp' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm-enable_wp_reset_form">
										<input name="_ppm_options[enable_wp_reset_form]" type="checkbox" id="ppm-enable_wp_reset_form"
												value="yes" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->enable_wp_reset_form ) ); ?>/>
												<?php esc_attr_e( 'This website\'s password reset page', 'ppm-wp' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label for="ppm-enable_wp_profile_form">
										<input name="_ppm_options[enable_wp_profile_form]" type="checkbox" id="ppm-enable_wp_profile_form"
												value="yes" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->enable_wp_profile_form ) ); ?>/>
												<?php esc_attr_e( 'User profile page', 'ppm-wp' ); ?>
									</label>
								</fieldset>
							</td>
								</tr>

					</tbody>
				</table>
			</div>

			<?php
				$scripts_required = false;
				$additonal_tabs   = apply_filters( 'ppmwp_forms_settings_page_content_tabs', '' );
			?>

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
