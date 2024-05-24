<?php
if(isset($_GET['page']) && sanitize_text_field($_GET['page']) == "activate-status") { 
	if(isset($_POST['dpid']) && sanitize_text_field($_POST['dpid']) > 0 ){  ?>
		<div id="sm_status" class="wrap pc-wrap dpwap-box-wrap">
	 		<div id="mpiblock">
				<div class="postbox">
					<h3 class="hndle"><span><?php esc_html_e('Activation Status', 'download-plugin'); ?></span></h3>
					<div class="inside">
						<?php 
						$dpwapObj->dpwap_plugin_all_activate(); 
                     	delete_option("dpwap_plugins");
						?>
					</div>
					<span>
						<a href="<?php echo esc_url('plugin-install.php');?>">
							<input type="button" class="button button-primary" value="<?php esc_attr_e('Return to Plugin Installer', 'download-plugin');?>">
						</a>
					</span>
				</div>		
			</div>
		</div><?php
	} 
}