<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_Shortcodes
 */
class CustomCSSandJS_Shortcodes {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
	}


	/**
	 * Register a tinyMCE plugin
	 */
	function init() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_buttons_1' ) );
		}
	}


	/**
	 * Register the ccj_shortcodes plugin
	 */
	function mce_external_plugins( $plugins ) {
		$plugins['ccj_shortcodes'] = plugins_url( 'assets/tinyMCE-button.js', CCJ_PLUGIN_FILE_PRO );
		return $plugins;
	}


	/**
	 * Register the ccj_shortcodes button
	 */
	function mce_buttons_1( $buttons ) {
		array_push( $buttons, 'ccj_shortcodes' );
		return $buttons;
	}


	/**
	 * Define the tinyMCE button
	 */
	function admin_print_scripts() {
		$search_tree = get_option( 'custom-css-js-tree' );

		$shortcodes = array();
		if ( is_array( $search_tree ) && count( $search_tree ) > 0 && isset( $search_tree[10] ) && isset( $search_tree[10]['shortcode'] ) ) {
			foreach ( $search_tree[10]['shortcode'] as $shortcode_id ) {
				$shortcode = explode( '-', $shortcode_id, 2 );

				if ( ! is_array( $shortcode ) || count( $shortcode ) !== 2 ) {
					continue;
				}

				$shortcode_post = get_post( $shortcode[0] );

				$shortcodes[] = array(
					'id'    => $shortcode[1],
					'title' => $shortcode_post->post_title,
				);

			}
		}

		global $wp_version;
		if ( is_multisite() && version_compare( $wp_version, '4.9.0', '>=' ) && ! is_main_site() ) {
			$search_tree_multisite = get_site_option( 'custom-css-js-multisite' );
			if ( is_array( $search_tree_multisite )
				&& count( $search_tree_multisite ) > 0
				&& isset( $search_tree_multisite[10] )
				&& isset( $search_tree_multisite[10]['shortcode-multisite'] ) ) {

				$main_site = get_main_site_id();

				foreach ( $search_tree_multisite[10]['shortcode-multisite'] as $shortcode_id ) {
					$shortcode = explode( '-', $shortcode_id, 2 );

					if ( ! is_array( $shortcode ) || count( $shortcode ) !== 2 ) {
						continue;
					}

					$shortcode_post = get_blog_post( $main_site, $shortcode[0] );

					$shortcodes[] = array(
						'id'    => $shortcode[1],
						'title' => $shortcode_post->post_title . __( ' (network wide)', 'custom-css-js-pro' ),
					);
				}
			}
		}

		?>
<style type="text/css">.ccj_shortcodes-icon:before { content: "\f502"; display: inline-block; -webkit-font-smoothing: antialiased; font: normal 19px/1 "dashicons"; vertical-align: top; }</style>

<script type="text/javascript">/* <![CDATA[ */
var ccj_shortcodes = <?php echo json_encode( $shortcodes ); ?>;
/* ]]> */
</script>
		<?php
	}


}

return new CustomCSSandJS_Shortcodes();
