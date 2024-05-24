<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_AdminConfig
 */
class CustomCSSandJS_AdminConfigPro {

	var $settings_default;

	var $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Get the "default settings"
		$settings_default = apply_filters( 'ccj_settings_default', array() );

		// Get the saved settings
		$settings = get_option( 'ccj_settings', array() );
		if ( ! is_array( $settings ) || count( $settings ) === 0 ) {
			$settings = $settings_default;
		} else {
			foreach ( $settings_default as $_key => $_value ) {
				if ( ! isset( $settings[ $_key ] ) ) {
					$settings[ $_key ] = $_value;
				}
			}
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->settings         = $settings;
		$this->settings_default = $settings_default;

		// Add actions and filters
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'ccj_settings_form', array( $this, 'general_extra_form' ), 11 );
		add_filter( 'ccj_settings_default', array( $this, 'general_extra_default' ) );
		add_filter( 'ccj_settings_save', array( $this, 'general_extra_save' ) );
		add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
	}


	/**
	 * Add submenu pages
	 */
	function admin_menu() {
		$menu_slug = 'edit.php?post_type=custom-css-js';

		add_submenu_page( $menu_slug, __( 'Settings', 'custom-css-js-pro' ), __( 'Settings', 'custom-css-js-pro' ), 'manage_options', 'custom-css-js-config', array( $this, 'config_page' ) );

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

		if ( $hook != 'custom-css-js_page_custom-css-js-config' ) {
			return false;
		}

		// Some handy variables
		$a = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets';
		$v = CCJ_VERSION_PRO;

		wp_enqueue_script( 'tipsy', $a . '/jquery.tipsy.js', array( 'jquery' ), $v, false );
		wp_enqueue_style( 'tipsy', $a . '/tipsy.css', array(), $v );
	}



	/**
	 * Template for the config page
	 */
	function config_page() {

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'license' ) {
			$this->license_tab();
			return;
		}

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'debug' ) {
			$this->debug_tab();
			return;
		}

		if ( isset( $_POST['ccj_settings-nonce'] ) ) {
			check_admin_referer( 'ccj_settings', 'ccj_settings-nonce' );

			$data = apply_filters( 'ccj_settings_save', array() );

			$settings = get_option( 'ccj_settings', array() );
			if ( ! isset( $settings['add_role'] ) ) {
				$settings['add_role'] = false;
			}
			if ( ! isset( $settings['remove_comments'] ) ) {
				$settings['remove_comments'] = false;
			}

			// If the "add role" option changed
			if ( $data['add_role'] !== $settings['add_role'] && current_user_can( 'update_plugins' ) ) {
				// Add the 'css_js_designer' role
				if ( $data['add_role'] ) {
					CustomCSSandJS_InstallPro::create_roles();
				}

				// Remove the 'css_js_designer' role
				if ( ! $data['add_role'] ) {
					remove_role( 'css_js_designer' );
				}
				flush_rewrite_rules();
			}

			update_option( 'ccj_settings', $data );

			if ( is_multisite() && is_main_site() ) {
				update_site_option( 'ccj_main_site_https', isset( $_POST['main_site_https'] ) && $_POST['main_site_https'] == 1 );
				if ( current_user_can( 'manage_network' ) ) {
					update_site_option( 'ccj_multisite_js_loginpage', isset( $_POST['multisite_js_loginpage'] ) && $_POST['multisite_js_loginpage'] == 1 );
				}
			}
		} else {
			$data = $this->settings;
		}

		?>

		<?php $this->config_page_header( 'general' ); ?>

		<div class="panel panel-default">
		<div class="panel-body">
		<div class="row">
		<form action="<?php echo admin_url( 'edit.php' ); ?>?post_type=custom-css-js&page=custom-css-js-config" id="ccj_settings" method="post" class="form-horizontal">
		<table class="form-table">
				<?php do_action( 'ccj_settings_form' ); ?>

			<tr>
			<th>&nbsp;</th>
			<td>
			<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save' ); ?>" />
			<?php wp_nonce_field( 'ccj_settings', 'ccj_settings-nonce', false ); ?>
			</td>
			</tr>

		</table>
		</form>
		</div>
		</div>
		</div>
		</div>
		<?php
	}


	/**
	 * Template for config page header
	 */
	function config_page_header( $tab = 'general' ) {

		$url = '?post_type=custom-css-js&page=custom-css-js-config';

		$active         = array(
			'general' => '',
			'license' => '',
			'debug'   => '',
		);
		$active[ $tab ] = 'nav-tab-active';

		$logo       = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets/images/silkypress_logo.png';
		$silkypress = '<img src="' . $logo . '"> <a href="https://www.silkypress.com/?utm_source=wordpress&amp;utm_campaign=iz_free&amp;utm_medium=banner" target="_blank">SilkyPress.com</a>';

		?>
		<h2><?php printf( __( 'Custom CSS & JS Pro by %s' ), $silkypress ); ?></h2>

		<div class="wrap">

		<h3 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<a href="<?php echo $url; ?>" class="nav-tab <?php echo $active['general']; ?>"><?php echo __( 'General Settings', 'custom-css-js-pro' ); ?></a>
			<a href="<?php echo $url; ?>&tab=license" class="nav-tab <?php echo $active['license']; ?>"><?php echo __( 'License Key', 'custom-css-js-pro' ); ?></a>
			<a href="<?php echo $url; ?>&tab=debug" class="nav-tab <?php echo $active['debug']; ?>"><?php echo __( 'Debug', 'custom-css-js-pro' ); ?></a>
		</h3>

		<?php
	}


	/**
	 * Template for the License tab in the config page
	 */
	function license_tab() {

		require_once dirname( __FILE__ ) . '/edd/edd-plugin.php';

		$a = ccj_plugin_updater_data();

		$license_data = array(
			'store_url'      => $a['plugin_server'],
			'item_name'      => $a['data']['plugin_name'],
			'author'         => $a['data']['author'],
			'version'        => $a['data']['version'],
			'main_file'      => $a['file'],
			'prefix'         => 'ccj_',
			'license'        => 'ccj_license',
			'license_key'    => 'ccj_license_key',
			'license_status' => 'ccj_license_status',
		);

		$edd = new CustomCSSandJS_LicenseForm( $license_data );

		$admin_notice = false;
		if ( ! empty( $_POST ) ) {
			$admin_notice = $edd->activate_deactivate_license( $_POST );
		}

		?>

		<?php $this->config_page_header( 'license' ); ?>

		<div class="panel panel-default">
		<div class="panel-body">
		<div class="row" style="margin: 0;">
		<?php $edd->license_page( $admin_notice ); ?>
		</div>
		</div>
		</div>

		</div>

		<?php
	}


	/**
	 * Template for the Debug tab in the config page
	 */
	function debug_tab() {

		$ccj_for_url = '';
		if ( isset( $_POST['ccj_for_url'] ) ) {
			$ccj_for_url = $_POST['ccj_for_url'];
		}

		include_once 'class-show-codes.php';

		$show_codes = new CustomCSSandJS_ShowCodesPro();
		$show_codes->set_value( 'first_page', get_option( 'home' ) );
		$show_codes->set_value( 'upload_dir', CCJ_UPLOAD_DIR );
		$show_codes->set_value( 'upload_url', CCJ_UPLOAD_URL );

		// Get the allowed codes for this specific page
		$uri = $ccj_for_url;

		$ccj_urls      = get_option( 'custom-css-js-urls', array() );
		$allowed_codes = $show_codes->url_rules( $uri, $ccj_urls );

		$search_tree          = get_option( 'custom-css-js-tree', array() );
		$filtered_search_tree = $show_codes->filter_search_tree( $search_tree, $allowed_codes );

		// WordPress environment
		$report  = '';
		$report .= 'Home URL: ' . get_option( 'home' ) . PHP_EOL;
		$report .= 'Site URL: ' . get_option( 'siteurl' ) . PHP_EOL;
		$report .= 'WP Version: ' . get_bloginfo( 'version' ) . PHP_EOL;
		$report .= 'CCJ Version: ' . CCJ_VERSION_PRO . PHP_EOL;
		$report .= 'WP Multisite: ' . var_export( is_multisite(), true ) . PHP_EOL;
		$report .= 'WP Memory Limit: ' . @ini_get( 'memory_limit' ) . PHP_EOL;

		ob_start();
		echo '### WordPress Environment ###' . PHP_EOL;
		echo $report . PHP_EOL;
		if ( ! empty( $ccj_for_url ) ) {
			echo '### Report generated for: ' . PHP_EOL;
			echo '   ' . $ccj_for_url . PHP_EOL;
		}
		echo '### All codes ###' . PHP_EOL;
		print_r( $ccj_urls );
		if ( ! empty( $ccj_for_url ) ) {
			echo '### Allowed codes ###' . PHP_EOL;
			print_r( $allowed_codes );
		}
		echo '### Search tree ###' . PHP_EOL;
		print_r( $search_tree );
		if ( ! empty( $ccj_for_url ) ) {
			echo '### Filtered Search tree ###' . PHP_EOL;
			print_r( $filtered_search_tree );
		}
		$report = ob_get_contents();
		ob_end_clean();

		?>

<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {

		$("#debug-report textarea").focus().select();

	});
		</script>

		<?php $this->config_page_header( 'debug' ); ?>

		<div class="panel panel-default">
		<div class="panel-body">
		<div class="row">
		<div id="debug-report" style="display: block;">
			<div style="margin-bottom: 10px;"><?php _e( 'Please copy and paste this information in your ticket when contacting support', 'custom-css-js-pro' ); ?>:</div>
			<textarea readonly="readonly"><?php echo $report; ?></textarea>


			<form method="post">
			<table class="form-table">
				<tr>
				<td>
				<input type="text" name="ccj_for_url" value="<?php echo $ccj_for_url; ?>" style="width: 100%;" placeholder="<?php _e( 'URL for which you want to generate the report', 'custom-css-js-pro' ); ?>" />
				</td><td>
				<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Regenerate the debug info', 'custom-css-js-pro' ); ?>" />
				</td>
				</tr>
			</table>
			</form>


		</div>
		</div>


		</div>
		</div>
		</div>

		<?php
	}



	/**
	 * Add the defaults for the `General Settings` form
	 */
	function general_extra_default( $defaults ) {
		return array_merge(
			$defaults,
			array(
				'ccj_htmlentities'  => false,
				'ccj_htmlentities2' => false,
				'lint'              => false,
				'autocomplete'      => true,
				'add_role'          => false,
				'remove_comments'   => false,
			)
		);
	}


	/**
	 * Add the `General Settings` form values to the $_POST for the Settings page
	 */
	function general_extra_save( $data ) {
		return array_merge(
			$data,
			array(
				'ccj_htmlentities'  => isset( $_POST['ccj_htmlentities'] ) ? true : false,
				'ccj_htmlentities2' => isset( $_POST['ccj_htmlentities2'] ) ? true : false,
				'lint'              => isset( $_POST['ccj_lint'] ) ? true : false,
				'autocomplete'      => isset( $_POST['ccj_autocomplete'] ) ? true : false,
				'add_role'          => isset( $_POST['add_role'] ) ? true : false,
				'remove_comments'   => isset( $_POST['remove_comments'] ) ? true : false,
			)
		);
	}



	/**
	 * Extra fields for the `General Settings` Form
	 */
	function general_extra_form() {

		// Get the setting
		$settings        = get_option( 'ccj_settings', array() );
		$htmlentities    = ( isset( $settings['ccj_htmlentities'] ) && $settings['ccj_htmlentities'] ) ? true : false;
		$htmlentities2   = ( isset( $settings['ccj_htmlentities2'] ) && $settings['ccj_htmlentities2'] ) ? true : false;
		$lint            = ( isset( $settings['lint'] ) && $settings['lint'] ) ? true : false;
		$autocomplete    = ( ! isset( $settings['autocomplete'] ) ? true : $settings['autocomplete'] ) ? true : false;
		$add_role        = ( isset( $settings['add_role'] ) && $settings['add_role'] ) ? true : false;
		$remove_comments = ( isset( $settings['remove_comments'] ) && $settings['remove_comments'] ) ? true : false;
		if ( is_multisite() && is_main_site() ) {
			$main_site_https = get_site_option( 'ccj_main_site_https', false );
			$multisite_js_loginpage = get_site_option( 'ccj_multisite_js_loginpage', false );
		}

		$title = __( 'If you want to use an HTML entity in your code (for example ' . htmlentities( '&gt; or &quot;' ) . '), but the editor keeps on changing them to its equivalent character (&gt; and &quot; for the previous example), then you might want to enable this option.', 'custom-css-js-pro' );
		$help  = '<span class="dashicons dashicons-editor-help tipsy-no-html" rel="tipsy" title="' . $title . '"></span>';

		$title2 = __( 'If you use HTML tags in your code (for example ' . htmlentities( '<input> or <textarea>' ) . ') and you notice that they disappear and the editor looks weird, then you need to enable this option.', 'custom-css-js-pro' );
		$help2  = '<span class="dashicons dashicons-editor-help tipsy-no-html" rel="tipsy" title="' . $title2 . '"></span>';

		$help_lint = '';
		if ( is_plugin_active( 'html-editor-syntax-highlighter/html-editor-syntax-highlighter.php' ) ) {
			$help_lint = __( 'This feature cannot work if the HTML Editor Syntax Highlighter plugin is active. Sorry for the inconvenience.', 'custom-css-js-pro' );
			$help_lint = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $help_lint . '"></span>';

		}

		$add_role_help = esc_html__( 'By default only the Administrator will be able to publish/edit/delete Custom Codes. By enabling this option there is also a "Web Designer" role created which can be assigned to a non-admin user in order to publish/edit/delete Custom Codes.', 'custom-css-js-pro' );
		$add_role_help = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $add_role_help . '"></span>';

		$remove_comments_help = esc_html__( 'In your page\'s HTML there is a comment added before and after the internal CSS or JS in order to help you locate your custom code. Enable this option in order to remove that comment.', 'custom-css-js-pro' );
		$remove_comments_help = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $remove_comments_help . '"></span>';

		$multisite_js_loginpage_help = esc_html__( 'Adding custom JS codes from subsites to the login page is disabled due to security reasons. Enable this option only if you fully trust the subsite admins.' );
		$multisite_js_loginpage_help = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $multisite_js_loginpage_help . '"></span>';

		?>
		<tr>
		<th scope="row"><label for="ccj_htmlentities"><?php _e( 'Keep the HTML entities, don\'t convert to its character', 'custom-css-js-pro' ); ?> <?php echo $help; ?></label></th>
		<td><input type="checkbox" name="ccj_htmlentities" id = "ccj_htmlentities" value="1" <?php checked( $htmlentities, true ); ?> />
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="ccj_htmlentities2"><?php _e( 'Encode the HTML entities', 'custom-css-js-pro' ); ?> <?php echo $help2; ?></label></th>
		<td><input type="checkbox" name="ccj_htmlentities2" id = "ccj_htmlentities2" value="1" <?php checked( $htmlentities2, true ); ?> />
		</td>
		</tr>
		<tr>
		<th scope="row"><label for="ccj_lint"><?php _e( 'Show code warnings and errors in the editor', 'custom-css-js-pro' ); ?> <?php echo $help_lint; ?></label></th>
		<td><input type="checkbox" name="ccj_lint" id = "ccj_lint" value="1" <?php checked( $lint, true ); ?> />
		</td>
		</tr>
		<tr>
		<tr>
		<th scope="row"><label for="ccj_autocomplete"><?php _e( 'Autocomplete in the editor', 'custom-css-js-pro' ); ?></label></th>
		<td><input type="checkbox" name="ccj_autocomplete" id = "ccj_autocomplete" value="1" <?php checked( $autocomplete, true ); ?> />
		</td>
		</tr>
		<?php if ( current_user_can( 'update_plugins' ) ) : ?> 
		<tr>
		<th scope="row"><label for="add_role"><?php _e( 'Add the "Web Designer" role', 'custom-css-js-pro' ); ?> <?php echo $add_role_help; ?></label></th>
		<td><input type="checkbox" name="add_role" id = "add_role" value="1" <?php checked( $add_role, true ); ?> />
		</td>
		</tr>
		<?php endif; ?>
		<tr>
		<th scope="row"><label for="remove_comments"><?php _e( 'Remove the comments from HTML', 'custom-css-js-pro' ); ?> <?php echo $remove_comments_help; ?></label></th>
		<td><input type="checkbox" name="remove_comments" id = "remove_comments" value="1" <?php checked( $remove_comments, true ); ?> />
		</td>
		</tr>
		<?php if ( is_multisite() && is_main_site() ) : ?>
		<tr>
		<th scope="row"><label for="main_site_https"><?php _e( 'Use HTTPS for the main site', 'custom-css-js-pro' ); ?></label></th>
		<td><input type="checkbox" name="main_site_https" id = "main_site_https" value="1" <?php checked( $main_site_https, true ); ?> />
		</td>
		</tr>
		<?php endif; ?>

		<?php if ( is_multisite() && is_main_site() && current_user_can('manage_network') ) : ?>
		<tr>
		<th scope="row"><label for="multisite_js_loginpage"><?php _e( 'Allow custom JS codes to the login page in subsites', 'custom-css-js-pro' ); ?> <?php echo $multisite_js_loginpage_help; ?></label></th>
		<td><input type="checkbox" name="multisite_js_loginpage" id = "multisite_js_loginpage" value="1" <?php checked( $multisite_js_loginpage, true ); ?> />
		</td>
		</tr>
		<?php endif; ?>


		<?php
	}


	/**
	 * Declare compatibility with the WooCommerce COT (custom order tables) feature.
	 */
	function before_woocommerce_init() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CCJ_PLUGIN_FILE_PRO, true );
		}
	}
}

return new CustomCSSandJS_AdminConfigPro();
