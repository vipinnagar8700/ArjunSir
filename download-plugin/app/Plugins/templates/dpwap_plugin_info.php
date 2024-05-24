<div class="dp-admin-nav-tabs">    
<h2 class="nav-tab-wrapper" id="dp-admin-tabs">
    <a href="javascript:void(0)" id="dp-tab1" class="nav-tab nav-tab-active"> <?php esc_html_e( 'Download Plugin', 'download-plugin' );?> </a>
    <a href="javascript:void(0)" id="dp-tab2" class="nav-tab"> <?php esc_html_e( 'Upload Plugin', 'download-plugin' );?> </a>
    </h2>
</div>
<div class="dp-admin-nav-container" id="dp-tab1C">
    <div class="dp-tabs-content-wrap">
        <div class="dp-tabs-step">           
            <h3>1. <?php esc_html_e( 'Single Plugin', 'download-plugin' );?></h3>
            <p>
                <?php esc_html_e( 'Click on the', 'download-plugin' );?> 
                <strong class=""> <?php esc_html_e( 'Download', 'download-plugin' );?></strong> 
                <?php esc_html_e( 'link under plugin name on Plugins page.', 'download-plugin' );?>
            </p> 
            <div class="dp-tabs-img"><img src="<?php echo esc_url(DPWAP_URL . '/assets/images/download-plugin.png'); ?>" width="" height=""></div>
        </div>
        <div class="dp-tabs-step">           
            <h3>2. <?php esc_html_e( 'Multiple Plugins', 'download-plugin' );?></h3>
            <p><?php esc_html_e( 'On Plugins page, choose multiple plugins using checkboxes and select Download option in bulk action dropdown.', 'download-plugin' );?></p>
            <div class="dp-tabs-img"><img src="<?php echo esc_url(DPWAP_URL . '/assets/images/bulk-download-plugin.png'); ?>" width="" height=""></div>
        </div>
    </div>
</div>
<div class="dp-admin-nav-container" id="dp-tab2C">
    <div class="dp-tabs-content-wrap">
        <div class="dp-tabs-step">           
            <h3>1. <?php esc_html_e( 'Multiple Plugins', 'download-plugin' );?></h3>
            <p>
                <?php esc_html_e( 'Click on the', 'download-plugin' );?>
                <span class="dp-upload">
                    <?php esc_html_e( 'Upload Multiple Plugins', 'download-plugin' );?> 
                </span> 
                <?php esc_html_e( 'on Plugins → Add New page.', 'download-plugin');?>
            </p>
            <div class="dp-tabs-img"><img src="<?php echo esc_url(DPWAP_URL . '/assets/images/multi-upload.png'); ?>" width="" height=""></div>
        </div>
    </div>
</div>