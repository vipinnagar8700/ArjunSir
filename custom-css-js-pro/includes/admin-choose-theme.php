<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_ChooseTheme
 */
class CustomCSSandJS_ChooseTheme {

	var $default = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'ccj_settings_form', array( $this, 'ccj_settings_form' ), 11 );
		add_filter( 'ccj_settings_default', array( $this, 'ccj_settings_default' ) );
		add_filter( 'ccj_settings_save', array( $this, 'ccj_settings_save' ) );
	}

	/**
	 * Add the default for the theme
	 */
	function ccj_settings_default( $defaults ) {
		return array_merge( $defaults, array( 'ccj_editor_theme' => $this->default ) );
	}


	/**
	 * Add the 'ccj_editor_theme' value to the $_POST for the Settings page
	 */
	function ccj_settings_save( $data ) {
		$editor = isset( $_POST['ccj_editor_theme'] ) ? trim( $_POST['ccj_editor_theme'] ) : $this->default;

		if ( ! in_array( $editor, $this->get_themes() ) ) {
			$editor = $this->default;
		}

		return array_merge( $data, array( 'ccj_editor_theme' => $editor ) );
	}



	/**
	 * Form for "Editor Theme" field
	 */
	function ccj_settings_form() {

		// Get the setting
		$settings  = get_option( 'ccj_settings' );
		$the_theme = isset( $settings['ccj_editor_theme'] ) ? $settings['ccj_editor_theme'] : $this->default;

		// Get the themes
		$themes = $this->get_themes();

		$title = __( 'Changing the editor\'s theme will affect all the users, not only you.', 'custom-css-js-pro' );
		$help  = '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $title . '"></span>';

		?>
		<tr>
		<th scope="row"><label for="ccj_editor_theme"><?php _e( 'Editor theme', 'custom-css-js-pro' ); ?> <?php echo $help; ?></label></th>
		<td><select onchange="selectTheme()" name="ccj_editor_theme" id="ccj_editor_theme">
		<?php
		foreach ( $themes as $_theme ) {
			$selected = ( $_theme == $the_theme ) ? ' selected' : '';
			echo "\t\t\t" . '<option value="' . $_theme . '"' . $selected . '>' . $_theme . '</option>' . PHP_EOL;
		}

		?>
		</select> <br />

		<?php $this->theme_demo(); ?>


		</td>
		</tr>
		<?php
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
		$a  = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets';
		$cm = $a . '/codemirror';
		$v  = CCJ_VERSION_PRO;

		wp_enqueue_script( 'ccj-addon_merge_match_patch', $cm . '/lib/diff_match_patch.js', array( 'jquery' ), $v, false );
		wp_enqueue_script( 'ccj-codemirror', $cm . '/lib/codemirror.js', array( 'jquery' ), $v, false );
		wp_enqueue_style( 'ccj-codemirror', $cm . '/lib/codemirror.css', array(), $v );
		wp_enqueue_script( 'ccj-css', $cm . '/mode/css/css.js', array( 'ccj-codemirror' ), $v, false );

		$themes = $this->get_themes();

		foreach ( $themes as $_theme ) {
			if ( $_theme == 'default' ) {
				continue;
			}
			wp_enqueue_style( 'cmt-' . $_theme, $cm . '/theme/' . $_theme . '.css', array(), $v );
		}
	}




	/**
	 * Get the available themes
	 */
	function get_themes() {
		return array(
			'default',
			'3024-day',
			'3024-night',
			'abcdef',
			'ambiance',
			'base16-dark',
			'base16-light',
			'bespin',
			'blackboard',
			'cobalt',
			'colorforth',
			'dracula',
			'eclipse',
			'elegant',
			'erlang-dark',
			'hopscotch',
			'icecoder',
			'isotope',
			'lesser-dark',
			'liquibyte',
			'material',
			'mbo',
			'mdn-like',
			'midnight',
			'monokai',
			'neat',
			'neo',
			'night',
			'paraiso-dark',
			'paraiso-light',
			'pastel-on-dark',
			'railscasts',
			'rubyblue',
			'seti',
			'solarized',
			'the-matrix',
			'tomorrow-night-bright',
			'tomorrow-night-eighties',
			'ttcn',
			'twilight',
			'vibrant-ink',
			'xq-dark',
			'xq-light',
			'yeti',
			'zenburn',
		);

	}


	/**
	 * Show the theme demo
	 */
	function theme_demo() {
		?>
	 
		<div style="margin: 5px 0;"><?php _e( 'Theme preview', 'custom-css-js-pro' ); ?>:</div>
<textarea id="theme_demo" name="theme_demo">
.example {
	color: #eee;
}
h1.example-2 {
	margin-top: 10px !important;
	font-size: 1.3em;
	margin: 1em 0;
	display: block;
	font-weight: 600;
}
</textarea>

		<script>
		  var editor = CodeMirror.fromTextArea(document.getElementById("theme_demo"), {
			lineNumbers: true,
			mode: 'css',
		  });
		  var input = document.getElementById("ccj_editor_theme");
		  function selectTheme() {
			var theme = input.options[input.selectedIndex].textContent;
			editor.setOption("theme", theme);
		  }
		  window.onload = selectTheme();
		</script>

		<?php
	}

}

return new CustomCSSandJS_ChooseTheme();
