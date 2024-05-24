<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_Revisions
 */
class CustomCSSandJS_Preview {

	var $default = 3600;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Add actions
		$actions = array(
			'add_meta_boxes'           => 'add_meta_boxes',
			'wp_ajax_ccj_preview_save' => 'wp_ajax_ccj_preview_save',
			'admin_enqueue_scripts'    => 'admin_enqueue_scripts',
		);
		foreach ( $actions as $_key => $_value ) {
			add_action( $_key, array( $this, $_value ) );
		}

		add_action( 'ccj_settings_form', array( $this, 'ccj_settings_form' ), 11 );
		add_filter( 'ccj_settings_default', array( $this, 'ccj_settings_default' ) );
		add_filter( 'ccj_settings_save', array( $this, 'ccj_settings_save' ) );
	}


	/**
	 * Add the URL Preview meta box
	 */
	function add_meta_boxes() {

		add_meta_box( 'previewdiv', __( 'Preview', 'custom-css-js-pro' ) . ccj_a_doc( 'https://www.silkypress.com/simple-custom-css-js-pro-documentation/#doc-preview' ), array( $this, 'previews_meta_box_callback' ), 'custom-css-js', 'normal' );
	}


	/**
	 * The meta box content
	 */
	function previews_meta_box_callback( $post ) {

		$preview_url = get_post_meta( $post->ID, 'preview_url' );
		$preview_url = isset( $preview_url[0] ) ? $preview_url[0] : '';

		$preview_id = get_post_meta( $post->ID, 'preview_id' );
		$preview_id = isset( $preview_id[0] ) ? $preview_id[0] : $this->random_str( 8 ) . $post->ID;

		wp_nonce_field( 'previews_meta_box_callback', 'ccj-preview-nonce' );
		?>
<div id="preview-action">
	<div>
	<input type="text" name="preview_url" id="ccj-preview_url" value="<?php echo $preview_url; ?>" placeholder="<?php _e( 'Full URL on which to preview the changes ...', 'custom-css-js-pro' ); ?>" />
	<a class="preview button button-primary button-large" id="ccj-preview"><?php _e( 'Preview Changes', 'custom-css-js-pro' ); ?></a>
	</div>
	<input type="hidden" name="ccj_preview-id" id="ccj_preview-id" value="<?php echo $preview_id; ?>" />
</div>
		<?php

	}

	private function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
		$str = '';
		$max = mb_strlen( $keyspace, '8bit' ) - 1;
		for ( $i = 0; $i < $length; ++$i ) {
			$str .= $keyspace[ rand( 0, $max ) ];
		}
		return $str;
	}


	/**
	 * Save the code content and variables for preview
	 */
	function wp_ajax_ccj_preview_save() {

		// Verify the transient
		if ( ! wp_verify_nonce( $_POST['ccj-preview-nonce'], 'previews_meta_box_callback' ) ) {
			_e( 'Error: _nonce is not correct. Refresh your browser and try again', 'custom-css-js-pro' );
			die();
		}

		// Build and set the transient data
		$post_id      = $_POST['post_ID'];
		$transient_id = CCJ_PREVIEW_PREFIX . $_POST['preview_id'];

		foreach ( array( 'post_ID', 'linking', 'type', 'side', 'language', 'preprocessor', 'minify', 'priority' ) as $_type ) {
			$transient_data[ $_type ] = $_POST[ $_type ];
		}
		$transient_data['minify'] = ( $transient_data['minify'] == 'true' ) ? '1' : '0';

		$settings         = get_option( 'ccj_settings' );
		$preview_duration = isset( $settings['ccj_duration_preview'] ) ? $settings['ccj_duration_preview'] : 3600;

		set_transient( $transient_id, $transient_data, $preview_duration );

		// Save the code in a file
		$filename = $post_id . '-preview.' . $_POST['language'];
		$result   = ccj_save_code_file( $_POST['content'], $transient_data, $filename, $post_id );

		// Save the preview URL in the database
		update_post_meta( $post_id, 'preview_url', $_POST['preview_url'] );

		update_post_meta( $post_id, 'preview_id', $_POST['preview_id'] );

		// Return
		echo 'success';
		die();
	}


	/**
	 * Enqueue the scripts and styles
	 */
	public function admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();

		// Only for custom-css-js post type
		if ( $screen->post_type != 'custom-css-js' ) {
			return false;
		}

		// Only for the new/edit Code's page
		if ( $hook != 'post-new.php' && $hook != 'post.php' ) {
			return;
		}

		// Some handy variables
		$a = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets';
		$v = CCJ_VERSION_PRO;

		wp_enqueue_script( 'ccj_admin-preview', $a . '/ccj_preview.js', array( 'jquery', 'ccj-admin' ), $v, false );
	}




	/**
	 * Add the "Duration of Preview" default in the Settings page
	 */
	function ccj_settings_default( $defaults ) {
		$defaults['ccj_duration_preview'] = $this->default;
		return $defaults;
	}

	/**
	 * Form for "Duration of Preview" field in the Settings page
	 */
	function ccj_settings_form() {

		// Get the setting
		$settings = get_option( 'ccj_settings' );
		$value    = isset( $settings['ccj_duration_preview'] ) ? $settings['ccj_duration_preview'] : $this->default;

		$title = __( 'How long will last a preview generated for a code.', 'custom-css-js-pro' );
		$help  = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $title . '"></span>';
		?>
		<tr>
		<th scope="row"><label for="ccj_duration_preview"><?php _e( 'Duration of Preview', 'custom-css-js-pro' ); ?> <?php echo $help; ?></label></th>
		<td><input name="ccj_duration_preview" type="text" id="ccj_duration_preview" value="<?php echo $value; ?>" class="regular-text" /> <?php _e( 'seconds', 'custom-css-js-pro' ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Add the 'ccj_duration_preview' value to the $_POST for the Settings page
	 */
	function ccj_settings_save( $data ) {
		$duration = isset( $_POST['ccj_duration_preview'] ) ? $_POST['ccj_duration_preview'] : $this->default;
		$duration = (int) $duration;
		if ( $duration <= 0 ) {
			$duration = $this->default;
		}

		return array_merge( $data, array( 'ccj_duration_preview' => $duration ) );
	}



}

return new CustomCSSandJS_Preview();
