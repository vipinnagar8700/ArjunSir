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
class CustomCSSandJS_Revisions {

	private $default_options = array(
		'type'     => 'header',
		'linking'  => 'internal',
		'side'     => 'frontend',
		'priority' => 5,
		'language' => 'css',
	);


	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'admin_post_ccj-revisions-compare', array( $this, 'ajax_revisions_compare' ) );
		add_action( 'admin_post_ccj-revisions-delete', array( $this, 'ajax_revisions_delete' ) );

		add_action( 'wp_restore_post_revision', array( $this, 'restore_revision_meta' ), 10, 2 );

		add_filter( 'wp_save_post_revision_post_has_changed', array( $this, 'wp_save_post_revision_post_has_changed' ), 10, 3 );
	}


	function ajax_revisions_delete() {

		check_admin_referer( 'ccj-revisions' );

		if ( empty( $_POST['revision_ids'] ) ) {
			$ajax = new WP_Ajax_Response( array( 'data' => -1 ) );
			$ajax->send();
			return;
		}

		$revisions = explode( ',', stripslashes( $_POST['revision_ids'] ) );
		$revisions = array_map( 'intval', $revisions );

		$deleted = array();

		foreach ( $revisions as $_id ) {
			$_id = intval( $_id );

			if ( ! wp_is_post_revision( $_id ) ) {
				continue;
			}
			if ( wp_is_post_autosave( $_id ) ) {
				continue;
			}
			if ( ! current_user_can( 'delete_post', get_post( $_id )->post_parent ) ) {
				continue;
			}

			if ( wp_delete_post_revision( $_id ) ) {
				$deleted[] = $_id;
			}
		}

		$ajax = new WP_Ajax_Response(
			array(
				'data'         => 1,
				'supplemental' => array(
					'ids' => implode(
						',',
						$deleted
					),
				),
			)
		);
		$ajax->send();
		return;
	}

	function ajax_revisions_compare_enqueue() {
		$cm      = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets/codemirror/';
		$version = '1.0';

		wp_enqueue_script( 'cm-codemirror', $cm . 'lib/codemirror.js', array( 'jquery' ) );
		wp_enqueue_style( 'cm-codemirror', $cm . 'lib/codemirror.css', array(), $version );
		wp_enqueue_script( 'cm-addon_merge_match_patch', $cm . 'lib/diff_match_patch.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_script( 'cm-merge', $cm . 'addon/merge/merge.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_style( 'cm-merge', $cm . 'addon/merge/merge.css', array(), $version );

		$cmm = $cm . 'mode/';
		wp_enqueue_script( 'cm-xml', $cmm . 'xml/xml.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_script( 'cm-js', $cmm . 'javascript/javascript.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_script( 'cm-css', $cmm . 'css/css.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_script( 'cm-htmlmixed', $cmm . 'htmlmixed/htmlmixed.js', array( 'cm-codemirror' ), $version, false );
		wp_enqueue_script( 'cm-php', $cmm . 'php/php.js', array( 'cm-codemirror' ), $version, false );

		set_current_screen( 'revision-edit' );
		$GLOBALS['hook_suffix'] = 'revision-control';

		// remove the assets from other plugins so it doesn't interfere with CodeMirror
		global $wp_scripts;
		if ( is_array( $wp_scripts->registered ) && count( $wp_scripts->registered ) != 0 ) {
			foreach ( $wp_scripts->registered as $_key => $_value ) {
				if ( ! isset( $_value->src ) ) {
					continue;
				}

				if ( strstr( $_value->src, 'wp-content/plugins' ) !== false
					&& strstr( $_value->src, 'plugins/custom-css-js-pro/assets' ) === false
					&& strstr( $_value->src, 'plugins/advanced-custom-fields/' ) === false
					&& strstr( $_value->src, 'plugins/wp-jquery-update-test/' ) === false
					&& strstr( $_value->src, 'plugins/enable-jquery-migrate-helper/' ) === false
					&& strstr( $_value->src, 'plugins/advanced-custom-fields-pro/' ) === false ) {
						unset( $wp_scripts->registered[ $_key ] );
				}
			}
		}

	}

	function ajax_revisions_compare() {
		$this->ajax_revisions_compare_enqueue();
		iframe_header();
		?>
		<style type="text/css">
			html.wp-toolbar {
				padding-top: 0px;
			}
			.error {
				color: #ff0000;
				padding: 15px !important;
				margin-bottom: 20px;
				border: 1px solid transparent;
				border-radius: 4px;
				color: #a94442;
				background-color: #f2dede;
				border-color: #ebccd1;
			 }
			textarea {
				display: none;
			}
			table.revision-info {
				width: 100%;
			}
			table.revision-info tr.different {
				background-color: #f2dede;
			}
			table.revision-info td {
				width: 50%;
				padding: 6px;
				font-size: 18px;
				text-align: center;
			}
			table.revision-info .bold {
				font-weight: bold;
			}
			table.revision-info td.left {
				text-align: right;
				font-size: 14px;
				width: 45%;
			}
			table.revision-info td.right {
				text-align: left;
				font-size: 14px;
				width: 45%;
			}
			table.revision-info td.middle {
				text-align: center;
				font-size: 14px;
				width: 10%;
				font-weight: bold;
			}
		</style>
		<?php

		if ( ! isset( $_GET['left'] ) || ! isset( $_GET['right'] ) || ! isset( $_GET['post_id'] ) ) {
			echo '<div class="error">' . __( 'Error: Please choose two revisions to compare', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		$left  = get_post( absint( $_GET['left'] ) );
		$right = get_post( absint( $_GET['right'] ) );
		$post  = get_post( absint( $_GET['post_id'] ) );

		if ( ! $left || ! $right ) {
			echo '<div class="error">' . __( 'Error: One of the revisions is missing', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		if ( ! current_user_can( 'read_post', $left->ID ) || ! current_user_can( 'read_post', $right->ID ) ) {
			echo '<div class="error">' . __( 'Error: You do not have enough permissions to read the revisions', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		if ( $left->post_parent != $right->post_parent && $left->post_parent != $right->ID && $left->ID != $right->post_parent ) {
			echo '<div class="error">' . __( 'Error: The revisions are unrelated. You cannot compare them', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		if ( $left->ID == $right->ID ) {
			echo '<div class="error">' . __( 'Error: You are trying to compare the same revision to itself', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		if ( ! wp_get_post_revision( $left->ID ) && ! wp_get_post_revision( $right->ID ) ) {
			echo '<div class="error">' . __( 'Error: You are trying to compare two posts, not two revisions', 'custom-css-js-pro' ) . '</div>';
			return false;
		}

		$options = false;
		$options = get_post_meta( $post->ID, 'options', true );
		if ( ! is_array( $options ) || count( $options ) === 0 ) {
			$options = $this->default_options;
		}

		switch ( $options['language'] ) {
			case 'js':
				$code_mirror_mode = 'text/javascript';
				break;
			case 'html':
				$code_mirror_mode = 'application/x-httpd-php';
				break;
			default:
				$code_mirror_mode = 'text/css';
				break;
		}

		$title       = __( "<span class='bold'>%1\$s</span> by <span class='bold'>%2\$s</span>", 'custom-css-js-pro' );
		$title_left  = sprintf(
			$title,
			wp_post_revision_title( $left->ID, false ),
			get_the_author_meta( 'display_name', $left->post_author )
		);
		$title_right = sprintf(
			$title,
			wp_post_revision_title( $right->ID, false ),
			get_the_author_meta( 'display_name', $right->post_author )
		);

		?>
		<script type="text/javascript">
			window.onload = function() {
				var left_code = document.getElementById("revision-left-cm").innerText;
				var right_code = document.getElementById("revision-right-cm").innerText;
				var highlight = true, connect = null, collapse = false;
				var target = document.getElementById("view");
				target.innerHTML = "";
				var editors = CodeMirror.MergeView( target, {
					value: left_code,
					orig: right_code,
					lineNumbers: true,
					mode: "<?php echo $code_mirror_mode; ?>",
					highlightDifferences: highlight,
					connect: connect,
					collapseIdentical: collapse
				});
			};
		</script>
		<div class="wrap">
		<table class="revision-info">
		<tr><td><?php echo $title_left; ?></td>
		<td><?php echo $title_right; ?></td></tr>
		</table>
		<div id="view"></div>
		<?php $this->revisions_meta_table( $left, $right, $post ); ?>
		<textarea id="revision-left-cm" name="revision-left-cm"><?php echo $left->post_content; ?></textarea>
		<textarea id="revision-right-cm" name="revision-right-cm"><?php echo $right->post_content; ?></textarea>
		</div>

		<!-- follows the code from iframe_footer(), only without the `admin_print_footer_scripts` hook 
		otherwise there is an incompatibility with the `HTML Editor Syntax Highlighter` plugin -->
		<div class="hidden">
		<?php
			/** This action is documented in wp-admin/admin-footer.php */
			do_action( 'admin_footer', $hook_suffix );

			/** This action is documented in wp-admin/admin-footer.php */
			do_action( "admin_print_footer_scripts-$hook_suffix" );

			/** This action is documented in wp-admin/admin-footer.php */
		// do_action( 'admin_print_footer_scripts' );
		?>
			</div>
		<script type="text/javascript">if(typeof wpOnload=="function")wpOnload();</script>
		</body>
		</html>
		<?php

	}

	function revisions_meta_table( $left, $right, $post ) {
		$language = 'css';

		$options_left = false;
		if ( $left->post_type == 'revision' ) {
			$options_left = get_metadata( 'post', $left->ID, 'options', true );
		} elseif ( $left->post_type == 'custom-css-js' ) {
			$options_left = get_post_meta( $left->ID, 'options', true );
		}
		if ( isset( $options_left['language'] ) ) {
			$language = $options_left['language'];
		}

		$options_right = false;
		if ( $right->post_type == 'revision' ) {
			$options_right = get_metadata( 'post', $right->ID, 'options', true );
		} elseif ( $right->post_type == 'custom-css-js' ) {
			$options_right = get_post_meta( $right->ID, 'options', true );
		}

		$options_meta = ccj_get_options_meta();
		if ( $language == 'html' ) {
			$options_meta = ccj_get_options_meta_html();
		}

		$differences = array();
		foreach ( $options_meta as $_key => $_option ) {
			$differences[ $_key ] = '';
			if ( ! isset( $options_right[ $_key ] ) ) {
				$options_right[ $_key ] = '';
			}
			if ( ! isset( $options_left[ $_key ] ) ) {
				$options_right[ $_key ] = '';
			}
			if ( $options_left[ $_key ] != $options_right[ $_key ] ) {
				$differences[ $_key ] = ' class="different"';
			}
		}

		?>
		<table class="revision-info">
		<?php foreach ( $options_meta as $_key => $_option ) : ?>
			<?php $left_title = $this->get_option_title( $_key, $_option, $options_left[ $_key ] ); ?>
			<?php $right_title = $this->get_option_title( $_key, $_option, $options_right[ $_key ] ); ?>
		<tr<?php echo $differences[ $_key ]; ?>>
			<td class="left"><?php echo $left_title; ?></td>
			<td class="middle"><?php echo $_option['title']; ?></td>
			<td class="right"><?php echo $right_title; ?></td>
		</tr>
		<?php endforeach; ?>
		</table>
		<?php
	}

	function get_option_title( $_key, $meta, $_value ) {

		$output = '';
		$title  = '';

		// For radio values with a dashicon
		if ( isset( $meta['values'][ $_value ] ) && isset( $meta['values'][ $_value ]['dashicon'] ) ) {
			$icon = 'dashicons ';
			if ( isset( $meta['values'][ $_value ]['dashicon'] ) ) {
				$icon .= 'dashicons-' . $meta['values'][ $_value ]['dashicon'];
			}

			if ( isset( $meta['values'][ $_value ]['title'] ) ) {
				$title = $meta['values'][ $_value ]['title'];
			}

			$output .= '<span class="' . $icon . '"></span> ' . $title;
		}
		// For radio values without a dashicon
		elseif ( isset( $meta['values'][ $_value ] ) ) {
			if ( $_value != 'none' ) {
				$title = $meta['values'][ $_value ];
				if ( isset( $meta['values'][ $_value ]['title'] ) ) {
					$title = $meta['values'][ $_value ]['title'];
				}
				$output .= ' <span>' . $title . '</span> ';
			}
		}
		// For checkboxes
		elseif ( $_value == true ) {
			$meta['dashicon'] = isset( $meta['dashicon'] ) ? $meta['dashicon'] : '';
			$title   = $meta['title'];
			$icon    = 'dashicons dashicons-' . $meta['dashicon'];
			$output .= '<span class="' . $icon . '"></span> ' . $title;
		}

		return $output;

	}


	function add_meta_boxes() {
		remove_meta_box( 'revisionsdiv', 'custom-css-js', 'normal' );

		add_meta_box( 'revisionsdiv', __( 'Code Revisions', 'custom-css-js-pro' ) . ccj_a_doc( 'https://www.silkypress.com/simple-custom-css-js-pro-documentation/#doc-revision' ), array( $this, 'revisions_meta_box_callback' ), 'custom-css-js', 'normal' );

	}

	function admin_enqueue_scripts( $hook ) {
		if ( $hook != 'post-new.php' && $hook != 'post.php' ) {
			return false;
		}

		$screen = get_current_screen();

		if ( $screen->post_type != 'custom-css-js' ) {
			return false;
		}

		$cm      = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets/';
		$version = '1.0';

		wp_enqueue_script( 'ccj-revisions', $cm . 'revisions.js', array( 'jquery', 'wp-ajax-response' ), $version );

		add_thickbox();

	}

	function revisions_meta_box_callback( $post_id = null ) {

		if ( ! $post_id ) {
			$post_id = $GLOBALS['post_ID'];
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$revisions = $this->get_revisions( $post );

		if ( ! $revisions ) {
			_e( 'There are no revisions yet', 'custom-css-js-pro' );
			return false;
		};

		$this->output_revisions( $revisions, $post );
	}


	/**
	 * Retrieve and arrange the revisions
	 */
	function get_revisions( $post = null ) {

		$revisions = wp_get_post_revisions( $post->ID );

		if ( ! $revisions ) {
			$revision = array();
		}

		foreach ( $revisions as $_key => $_revision ) {
			if ( ! current_user_can( 'read_post', $_revision->ID ) ) {
				unset( $revisions[ $_key ] );
			}
		}

		if ( ! is_array( $revisions ) || count( $revisions ) == 0 ) {
			return false;
		}

		usort( $revisions, array( $this, 'usort_revisions' ) );

		// array_unshift( $revisions, $post );

		return $revisions;
	}


	/**
	 * Sort the revisions before showing them in the table
	 */
	function usort_revisions( $a, $b ) {
		$a_time = strtotime( $a->post_modified_gmt );
		$b_time = strtotime( $b->post_modified_gmt );

		if ( $a_time == $b_time ) {
			return 0;
		}

		return ( $a > $b ) ? -1 : +1;
	}


	/**
	 * Output the revisions
	 */
	function output_revisions( $revisions, $post ) {
		?>
	<input type="hidden" id="revisions-nonce" value="<?php echo wp_create_nonce( 'ccj-revisions' ); ?>" />
	<table class="revisions">
		<thead><tr>
		<th class="revisions-compare"><?php _e( 'Compare', 'custom-css-js-pro' ); ?></th>
		<th><?php _e( 'Revision', 'custom-css-js-pro' ); ?></th>
		<th><?php _e( 'Author', 'custom-css-js-pro' ); ?></th>
		<th><input type="checkbox" name="delete[]" value="all" id="ccj-delete-checkbox" /> <?php _e( 'Delete', 'custom-css-js-pro' ); ?></th>
		<th><?php _e( 'Restore', 'custom-css-js-pro' ); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $revisions as $revision ) : ?>
			<?php

			$can_edit_post = ( $post->ID == 0 ) ? true : current_user_can( 'edit_post', $post->ID );

			$is_current_revision = false;
			if ( $post->ID == $revision->ID ) {
				$is_current_revision = true;
			}

			$title = wp_post_revision_title( $revision, false );

			$restore_url = wp_nonce_url(
				add_query_arg(
					array(
						'revision' => $revision->ID,
						'diff'     => false,
						'action'   => 'restore',
					),
					'revision.php'
				),
				'restore-post_' . $revision->ID
			);

			$delete_disabled = '';
			$delete_tooltip  = '';
			$class           = '';
			if ( $is_current_revision ) {
				$delete_disabled = 'disabled="disabled"';
				$delete_tooltip  = ' title="' . __( 'This is the current version. You cannot delete it', 'custom-css-js-pro' ) . '"';
				$class           = 'current-revision';
			}
			if ( wp_is_post_autosave( $revision ) ) {
				$delete_disabled = 'disabled="disabled"';
				$delete_tooltip  = ' title="' . __( 'This is an autosave revision. You cannot delete it', 'custom-css-js-pro' ) . '"';
			}
			if ( ! $can_edit_post ) {
				$delete_disabled = 'disabled="disabled"';
				$delete_tooltip  = ' title="' . __( 'You do not have the right to edit this Custom Code.', 'custom-css-js-pro' ) . '"';
			}
			?>
		<tr class="<?php echo $class; ?>" id="<?php echo 'revision-row-' . $revision->ID; ?>">
			<td class="revisions-compare">
			<input type="radio" name="compare_left" value="<?php echo $revision->ID; ?>" />
			<input type="radio" name="compare_right" value="<?php echo $revision->ID; ?>" />
			</td>
			<td><?php echo $title; ?></td>
			<td><?php echo get_the_author_meta( 'display_name', $revision->post_author ); ?></td>
			<td class="revisions-delete">
			<input type="checkbox" name="delete[]" value="<?php echo $revision->ID; ?>" <?php echo $delete_disabled . $delete_tooltip; ?>/>
			</td>
			<td class="revisions-restore">
				<a href="<?php echo $restore_url; ?>"><?php _e( 'Restore', 'custom-css-js-pro' ); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
		<tr>
			<td>
				<input type="button" class="button-secondary" value="<?php esc_attr_e( 'Compare', 'custom-css-js-pro' ); ?>" id="revisions-compare-button" />
			</td>
			<td colspan="2"> &nbsp;</td>
			<td>
				<input type="button" class="button-secondary" value="<?php esc_attr_e( 'Delete', 'custom-css-js-pro' ); ?>" id="revisions-delete-button" />
			</td>
			<td> &nbsp; </td>
		</tr>
		</tbody>
	</table>
		<?php
	}


	/**
	 * Restore the meta upon restoring the revision
	 */
	function restore_revision_meta( $post_id, $revision_id ) {

		$post     = get_post( $post_id );
		$revision = get_post( $revision_id );
		$options  = get_metadata( 'post', $revision->ID, 'options', true );

		if ( false !== $options ) {
			update_post_meta( $post_id, 'options', $options );
		} else {
			delete_post_meta( $post_id, 'options' );
		}

	}


	/**
	 * Let WordPress core know to save the revisions of codes even when the code didn't change, but the options did
	 *
	 * return true - if the revision should be saved
	 * return false - if the revision should not be saved
	 */
	function wp_save_post_revision_post_has_changed( $post_has_changed, $last_revision, $post ) {
		if ( $post_has_changed == true ) {
			return true;
		}

		$old_options = get_post_meta( $post->ID, 'options', true );
		if ( ! is_array( $old_options ) || count( $old_options ) === 0 ) {
			$old_options = false;
		}
		$new_options = get_post_meta( $last_revision->ID, 'options', true );
		if ( ! is_array( $new_options ) || count( $new_options ) === 0 ) {
			$new_options = false;
		}

		if ( $old_options == $new_options ) {
			return false;
		}

		return true;
	}

}

return new CustomCSSandJS_Revisions();
