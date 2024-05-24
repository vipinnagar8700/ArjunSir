<?php
echo '<style>.notice { display: none; }</style>';
echo '<style>#dolly { display: none; }</style>';
if ( isset( $_POST['dpid'] ) && sanitize_text_field( $_POST['dpid']) > 0 ) { 
	$dpwapObj->dpwap_plugin_all_activate();
}
if( isset( $_GET ) && sanitize_text_field( $_GET['page'] ) == "mul_upload" ) {
	$max_size_upload = (int)( ini_get( 'upload_max_filesize' ) );?>
	<div class="wrap pc-wrap">
		<div class="mpiicon icon32"></div>
		<div id="mpiblock">
			<div>
				<?php 
				if( $dpwapObj->dpwap_app_DirTesting() ) {} 
				else{?>
					<div class="mpi_error">
						<?php esc_html_e( 'oops!!! Seems like the directory permission are not set right so some functionalities of plugin will not work.', 'download-plugin' );?>
						<br/>
						<?php esc_html_e( 'Please set the directory permission for the folder "uploads" inside "wp-content" directory to 777.', 'download-plugin' );?>
					</div><?php
				} ?>
			</div>
			<div id="dpwap-plugin-box" class="dpwap-meta-box">
				<div class="postbox">
					<div class="handlediv" title="<?php esc_html_e( 'Click to toggle', 'download-plugin' );?>"><br/></div>
					<div id="dpwap-plugin-zipbox">
						<h3 class="hndle">
							<span>
								<?php esc_html_e('You can select and upload multiple Plugins in .zip format','download-plugin'); ?>
							</span>
						</h3>
						<br/>
						<form class="dpwap_multiple_upload_form" onsubmit="return check_valid_zipfile('dpwap_locFiles',<?php echo $max_size_upload; ?>);" name="form_uppcs" method="post" action="" enctype="multipart/form-data">
							<?php wp_nonce_field($dpwapObj->key); ?>
							<div class="upload-section-btn">					
								<input type="file" class="mpi_left middle sm_select_file" name="dpwap_locFiles[]" id="dpwap_locFiles" multiple="multiple"/>
								<input id="install_button" class="button mpi_button sm_btn_hide" type="submit" name="dpwap_locInstall" value="<?php esc_html_e('Install Now','download-plugin'); ?>"  />
								<div class="dpwap_clear"></div>
							</div>
						</form>
					</div>
					<div class="inside">
						<?php
							if (isset($_POST['dpwap_locInstall']) && $_FILES['dpwap_locFiles']['name'][0] != ""){
								echo '<form id="form_alldpwap" name="form_alldpwap" method="post" action="admin.php?page=activate-status">';
								echo "<div class='dpwap_main'>";
								$dpwapObj->dpwap_plugin_locInstall();
								echo "</div>";
								echo '<input class="button button-primary dpwap_allactive" type="submit" name="dpwap_locInstall" onclick="activateAllPLugins()" value="'. esc_attr__('Activate all', 'download-plugin').'">';
								echo '</form>';
							}
						?>
					</div>
				</div>		
			</div>
		</div>
	</div>

	<div class="containerul">
		<h1 style="colour : #fff;"><?php esc_html_e( 'Uploading is in progress...', 'download-plugin' );?></h1>
		<ul class="uploadLoader">
			<li></li><li></li><li></li><li></li><li></li><li></li>
		</ul>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('.sm_btn_hide').attr("disabled", "disabled");
			jQuery('input[type="file"]').change(function(e){
				var fileName = e.target.files[0].name;
				jQuery('.sm_btn_hide').removeAttr("disabled", "disabled");
			});
		});
	</script>
<?php }  ?>