<?php
/**
 * Custom CSS and JS PRO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_ShowCodesPro
 */
class CustomCSSandJS_ShowCodesPro {

	var $first_page = '';
	var $search_tree;
	var $search_tree_html;
	var $shortcodes = array();
	var $upload_dir;
	var $upload_url;
	var $preview_id;
	var $main_site_id         = null;
	var $this_site_id         = null;
	var $main_site_https      = false;
	var $remove_comments      = false;
	var $wp_conditional_error = array();


	function set_value( $var, $value ) {
		$this->$var = $value;
	}

	/**
	 * Check WordPress conditional tags
	 */
	function wp_conditional_tags( $allowed_codes = array(), $rules = array() ) {
		if ( ! isset( $rules['wp-conditional'] ) ) {
			return $allowed_codes;
		}

		if ( defined( 'CCJ_WP_CONDITIONALS' ) && CCJ_WP_CONDITIONALS == false ) {
			return $allowed_codes;
		}

		$add_rules    = array();
		$remove_rules = array();
		foreach ( $rules['wp-conditional'] as $_rule => $_codes ) {
			$original_rule = $_rule;
			$_rule         = str_replace( ';', '', stripslashes( trim( $_rule ) ) );
			if ( empty( $_rule ) ) {
				continue;
			}
			if ( stripos( $_rule, 'return' ) === false ) {
				$_rule = 'return(' . $_rule . ');';
			}

			try {
				if ( is_admin() && eval( $_rule ) || @eval( $_rule) ) {
					$add_rules = array_merge( $add_rules, $_codes );
				} else {
					$remove_rules = array_merge( $remove_rules, $_codes );
				}
			} catch ( Throwable $e ) {
				$remove_rules = array_merge( $remove_rules, $_codes );
				if ( is_admin() ) {
					$this->wp_conditional_error[] = '<b>Simple Custom CSS & JS Pro</b> - the <code>' . $original_rule . '</code> WP Conditional Tag from the <b>' . get_the_title( $_codes[0] ) . '</b> custom code throws the following error: <b>' . $e->getMessage() . '</b>';
				}
			}
		}
		$add_rules = array_diff( $add_rules, $remove_rules );

		$allowed_codes = array_merge( $allowed_codes, $add_rules );
		$allowed_codes = array_diff( $allowed_codes, $remove_rules );

		return $allowed_codes;
	}


	/**
	 * Check all the url filters and get the allowed codes for this specific page
	 */
	function url_rules( $uri, $rules = array() ) {

		if ( $rules === false ) {
			return false;
		}

		if ( ! $rules || count( $rules ) == 0 ) {
			return array();
		}

		$allowed_codes = array();
		$filters       = array(
			'all'          => 'array_merge',
			'first-page'   => 'array_merge',
			'contains'     => 'array_merge',
			'equal-to'     => 'array_merge',
			'begins-with'  => 'array_merge',
			'ends-by'      => 'array_merge',
			'not-contains' => 'array_diff',
			'not-equal-to' => 'array_diff',
		);

		foreach ( $filters as $_type => $_action ) {
			if ( isset( $rules[ $_type ] ) ) {
				if ( $_action == 'array_merge' ) {
					$allowed_codes = array_merge( $allowed_codes, $this->check_url_rules( $_type, $rules[ $_type ], $uri ) );
				} else {
					$allowed_codes = array_diff( $allowed_codes, $this->check_url_rules( $_type, $rules[ $_type ], $uri ) );
				}
			}
		}

		return array_unique( $allowed_codes );
	}


	/**
	 * Check for one type of rules if this page follows it or now
	 */
	function check_url_rules( $type, $rules, $uri ) {

		if ( $type == 'all' ) {
			return $rules;
		}

		if ( $type == 'first-page' ) {
			if ( is_front_page() ) {
				return $rules;
			}
			return array();
		}

		if ( ! is_array( $rules ) || count( $rules ) == 0 ) {
			return array();
		}

		$all_codes = array();
		$uri = strtolower( $uri );
		$urid = urldecode( $uri );

		foreach ( $rules as $_key => $_codes ) {
			$accept        = false;
			$_key = strtolower($_key);
			$_key_relative = (string) parse_url( $_key, PHP_URL_PATH );
			$_key          = (string) $_key;
			switch ( $type ) {
				case 'contains':
				case 'not-contains':
					if ( ! empty( $_key ) && ( strpos( $uri, $_key ) !== false || strpos( $urid , $_key ) !== false ) ) {
						$accept = true;
					}
					if ( ! empty( $_key_relative ) && ( strpos( $uri, $_key_relative ) !== false || strpos( $urid , $_key ) !== false ) ) {
						$accept = true;
					}
					break;
				case 'equal-to':
				case 'not-equal-to':
					if ( $uri == $_key || $uri == $_key_relative || $urid  == $_key || $urid == $_key_relative ) {
						$accept = true;
					}
					break;
				case 'begins-with':
					if ( ! empty( $_key ) && ( strpos( $uri, $_key ) === 0 || strpos( $urid, $_key ) === 0 ) ) {
						$accept = true;
					}
					if ( ! empty( $_key_relative ) && ( strpos( $uri, $_key_relative ) === 0 || strpos( $urid, $_key_relative ) === 0 ) ) {
						$accept = true;
					}
					break;
				case 'ends-by':
					if ( ! empty( $_key ) && ( strpos( strrev( $uri ), strrev( $_key ) ) === 0 || strpos( strrev( $urid ), strrev( $_key ) ) === 0 ) ) {
						$accept = true;
					}
					if ( ! empty( $_key_relative ) && ( strpos( strrev( $uri ), strrev( $_key_relative ) ) === 0 || strpos( strrev( $urid ), strrev( $_key_relative ) ) === 0 ) ) {
						$accept = true;
					}
					break;
			}
			if ( $accept ) {
				$all_codes = array_merge( $all_codes, $_codes );
			}
		}
		return $all_codes;
	}

	/**
	 * Filter the codes in the search tree and allow only the ones accepted for this page
	 */
	function filter_search_tree( $search_tree = array(), $allowed_codes = array() ) {

		if ( ! is_array( $search_tree ) || count( $search_tree ) == 0 ) {
			return array();
		}

		if ( ! is_int( key( $search_tree ) ) ) {
			$search_tree[5] = $search_tree;
		}
		ksort( $search_tree );

		// there are no $allowed_codes defined, probably just upgraded from the free to the pro version
		if ( $allowed_codes === false ) {
			return $search_tree;
		}

		// on this particular page there shouldn't any codes be shown, therefore the $allowed_codes is empty
		if ( ! is_array( $allowed_codes ) || count( $allowed_codes ) == 0 ) {
			return array();
		}

		foreach ( $search_tree as $_priority => $_sub ) {
			if ( ! is_array($_sub) || count($_sub) == 0 ) continue;
			foreach ( $_sub as $_action => $_codes ) {
				if ( 'jquery' === $_action ) continue;
				if ( strpos( $_action, 'external' ) !== false ) {
					foreach ( $_codes as $__key => $__code ) {
						if ( ! in_array( $this->short_filename( $__code ), $allowed_codes ) ) {
							unset( $_codes[ $__key ] );
						}
					}
				} else {
					if ( ! is_array( $_codes ) ) {
						$_codes = array();
					}
					if ( ! is_array( $allowed_codes ) ) {
						$allowed_codes = array();
					}
					$_codes = array_intersect( $_codes, $allowed_codes );
				}
				if ( is_array( $_codes ) && count( $_codes ) > 0 ) {
					$search_tree[ $_priority ][ $_action ] = $_codes;
				} else {
					unset( $search_tree[ $_priority ][ $_action ] );
				}
			}
		}

		return $search_tree;
	}


	/**
	 * Replace the $preview_id code with the preview, or add the $preview_id code preview
	 */
	function search_tree_for_preview( $search_tree = array(), $preview_id = '' ) {

		if ( ! is_array( $search_tree ) || count( $search_tree ) == 0 ) {
			$search_tree = array();
		}

		if ( empty( $preview_id ) ) {
			return $search_tree;
		}

		$transient = get_transient( CCJ_PREVIEW_PREFIX . $preview_id );

		if ( false == $transient ) {
			add_action( 'wp_head', array( $this, 'alert_preview_expired' ) );
		}

		// Remove the non-preview entry of the code from the search_tree
		$post_meta = get_post_meta( $transient['post_ID'], 'options', true );

		if ( $post_meta ) {
			$tree_branch = $post_meta['side'] . '-' . $post_meta['language'] . '-' . $post_meta['type'] . '-' . $post_meta['linking'];

			foreach ( $search_tree as $priority => $_sub ) {
				if ( ! isset( $_sub[ $tree_branch ] ) ) {
					continue;
				}
				foreach ( $_sub[ $tree_branch ] as $_key => $_codes ) {
					if ( strpos( $_codes, $transient['post_ID'] . '.' ) === 0 || $_codes == $transient['post_ID'] ) {
						unset( $search_tree[ $priority ][ $tree_branch ][ $_key ] );
					}
				}
				if ( count( $_sub[ $tree_branch ] ) == 0 ) {
					unset( $search_tree[ $priority ][ $tree_branch ] );
				}
			}
		}

		// Add the preview entry of the code in the search_tree
		$new_tree_branch = $transient['side'] . '-' . $transient['language'] . '-' . $transient['type'] . '-' . $transient['linking'];

		$priority = $transient['priority'];

		$filename = $transient['post_ID'] . '-preview.' . $transient['language'];

		if ( $transient['linking'] == 'external' ) {
			$filename .= '?v=' . rand( 1, 10000 );
		}

		$search_tree[ $priority ][ $new_tree_branch ][] = $filename;

		return $search_tree;
	}


	/**
	 * Show an alert when the preview transient is expired
	 */
	function alert_preview_expired() {
		$message = __( 'The preview id you are using is already expired. Please generate the preview again.', 'custom-css-js-pro' );
		echo '<script type="text/javascript"> alert("' . $message . '"); </script>' . PHP_EOL;
	}


	/**
	 * Add the appropriate wp actions
	 */
	function print_code_actions( $search_tree = array() ) {

		if ( ! is_array( $search_tree ) || count( $search_tree ) == 0 ) {
			return;
		}

		if ( is_multisite() ) {
			$this->main_site_id    = get_main_site_id();
			$this->this_site_id    = get_current_blog_id();
			$this->main_site_https = get_site_option( 'ccj_main_site_https', false );
		}

		foreach ( array( 'wp_head', 'wp_body_open', 'wp_footer', 'admin_head', 'admin_footer', 'login_head', 'login_footer' ) as $action ) {
			add_action( $action, array( $this, 'print_' . $action ), 30 );
		}

		add_action( 'admin_notices', array( $this, 'show_wp_conditional_errors' ) );
	}


	/**
	 * Print_wp_head, print_wp_footer, print_admin_head, print_admin_footer
	 */
	public function __call( $function, $args ) {
		if ( strstr( $function, 'print_' ) == false ) {
			return false;
		}

		$action = str_replace( 'print_', '', $function );

		foreach ( $this->search_tree as $_priority => $_sub ) {
			if ( ! is_array($_sub) || count($_sub) == 0 ) continue;
			foreach ( $_sub as $_where => $_codes ) {
				$is_frontend  = ( strpos( $_where, 'frontend' ) !== false ) ? true : false;
				$is_admin     = ( strpos( $_where, 'admin' ) !== false ) ? true : false;
				$is_login     = ( strpos( $_where, 'login' ) !== false ) ? true : false;
				$is_header    = ( strpos( $_where, 'header' ) !== false ) ? true : false;
				$is_footer    = ( strpos( $_where, 'footer' ) !== false ) ? true : false;
				$show_subtree = false;

				switch ( $action ) {
					case 'wp_head':
						if ( $is_frontend && $is_header ) {
							$show_subtree = true;
						}
						break;

					case 'wp_body_open':
						if ( strpos( $_where, 'body_open' ) !== false ) {
							$show_subtree = true;
						}
						break;

					case 'wp_footer':
						if ( $is_frontend && $is_footer ) {
							$show_subtree = true;
						}
						break;

					case 'admin_head':
						if ( $is_admin && $is_header ) {
							$show_subtree = true;
						}
						break;

					case 'admin_footer':
						if ( $is_admin && $is_footer ) {
							$show_subtree = true;
						}
						break;

					case 'login_head':
						if ( $is_login && $is_header ) {
							$show_subtree = true;
						}
						break;

					case 'login_footer':
						if ( $is_login && $is_footer ) {
							$show_subtree = true;
						}
						break;

				}
				if ( $show_subtree ) {
					$this->show_this_subtree( $_where, $_codes );
				}
			}
		}
	}


	/**
	 * Print all the codes for this particular subtree
	 */
	function show_this_subtree( $_where, $_codes ) {

		// show the code from the multisite folder
		$upload_dir = $this->upload_dir;
		$upload_url = $this->upload_url;
		if ( is_multisite() ) {
			if ( strstr( $_where, 'multisite' ) ) {
				$upload_dir = str_replace( '/sites/' . $this->this_site_id, '', $upload_dir );
				$upload_url = str_replace( '/sites/' . $this->this_site_id, '', $upload_url );
				if ( $this->main_site_https ) {
					$upload_url = str_replace( 'http://', 'https://', $upload_url );
				} else {
					$upload_url = str_replace( 'https://', 'http://', $upload_url );
				}
			}

			if ( is_main_site() && $this->main_site_https ) {
				$upload_url = str_replace( 'http://', 'https://', $upload_url );
			}
		} else {
			$upload_url = str_replace( array( 'https://', 'http://' ), '//', $upload_url );
		}

		$output = '';

		$type = strpos( $_where, 'css' ) !== false ? 'css' : '';
		$type = strpos( $_where, 'js' ) !== false ? 'js' : $type;
		$type = strpos( $_where, 'html' ) !== false ? 'html' : $type;
		$tag  = array(
			'css' => 'style',
			'js'  => 'script',
		);

		$type_attr = ( $type === 'js' && ! current_theme_supports( 'html5', 'script' ) ) ? ' type="text/javascript"' : '';
		$type_attr = ( $type === 'css' && ! current_theme_supports( 'html5', 'style' ) ) ? ' type="text/css"' : $type_attr;

		$is_mobile = $this->is_mobile();

		if ( strstr( $_where, 'internal' ) ) {

			$before = $this->remove_comments ? '' : '<!-- start Simple Custom CSS and JS -->' . PHP_EOL;
			$after  = $this->remove_comments ? '' : '<!-- end Simple Custom CSS and JS -->' . PHP_EOL;

			if ( $type === 'css' || $type === 'js' ) {
				$before .= '<' . $tag[ $type ] . $type_attr . '>' . PHP_EOL;
				$after   = '</' . $tag[ $type ] . '>' . PHP_EOL . $after;
			}
		}

		foreach ( $_codes as $_filename ) {

			if ( strpos( $_where, 'internal' ) !== false && ( $type === 'css' || $type === 'js' ) ) {
				if ( $this->remove_comments || empty( $type_attr ) ) {
					$custom_code = @file_get_contents( $upload_dir . '/' . $_filename );
					if ( $this->remove_comments ) {
						$custom_code = str_replace( array( '<!-- start Simple Custom CSS and JS -->' . PHP_EOL, '<!-- end Simple Custom CSS and JS -->' . PHP_EOL ), '', $custom_code );
					}
					if ( empty( $type_attr ) ) {
						$custom_code = str_replace( array( ' type="text/javascript"', ' type="text/css"' ), '', $custom_code );
					}
					$output .= $custom_code;
				} else {
					$output .= @file_get_contents( $upload_dir . '/' . $_filename );
				}
			}

			if ( strpos( $_where, 'internal' ) !== false && ( ! $type === 'css' || ! $type === 'js' ) ) {
				$post    = ( is_multisite() && strpos( $_where, 'multisite' ) !== false ) ? get_blog_post( $this->main_site_id, $_filename ) : get_post( $_filename );
				$output .= $before . $post->post_content . $after;
			}

			if ( strpos( $_where, 'external' ) !== false && $type === 'js' ) {
				$output .= PHP_EOL . "<script{$type_attr} src='{$upload_url}/{$_filename}'></script>" . PHP_EOL;
			}

			if ( strpos( $_where, 'external' ) !== false && $type === 'css' ) {
				$id      = $this->short_filename( $_filename ) . '-css';
				$href    = $upload_url . '/' . $_filename;
				$output .= PHP_EOL . "<link rel='stylesheet' id='{$id}'  href='{$href}'{$type_attr} media='all' />" . PHP_EOL;
			}

			if ( $type === 'html' ) {
				if ( ( $is_mobile && strpos( $_where, 'desktop' ) !== false ) || ( ! $is_mobile && strpos( $_where, 'mobile' ) !== false ) ) {
					continue;
				}
				if ( strpos( $_filename, '-preview' ) ) {
					$output .= file_get_contents( $upload_dir . '/' . $_filename );
				} else {
					if ( is_multisite() && strpos( $_where, 'multisite' ) !== false ) {
						$post    = get_blog_post( $this->main_site_id, $_filename );
						$output .= do_shortcode( $post->post_content );
					} else {
						$post    = get_post( $_filename );
						$output .= do_shortcode( $post->post_content );
					}
				}
			}
		}

		echo $output;
	}


	/**
	 * Strip the ?v= GET parameter at the end of the filename
	 */
	function short_filename( $filename ) {
		return preg_replace( '@\.(css|js)\?v=.*$@', '.$1', $filename );
	}


	/**
	 * Add the appropriate wp actions
	 *
	function print_html_code_actions($search_tree = array()) {

		if( ! is_array($search_tree) || count( $search_tree ) == 0 ) {
			return;
		}

		foreach( $search_tree as $_key => $_value ) {
			$action = '';
			$_key = str_replace( array('desktop-', 'mobile-', 'both-'), '', $_key );

			$allowed_hooks = array(
				'wp_head', 'wp_footer'
			);

			if ( ! in_array( $_key, $allowed_hooks ) ) {
				continue;
			}

			add_action( $action, array( $this, 'printh_' . $_key ) );
		}
	}
	 */


	/**
	 * Add the shortcodes
	 */
	function add_shortcodes( $search_tree, $multisite = false ) {
		if ( ! is_array( $search_tree ) || count( $search_tree ) == 0 ) {
			return;
		}

		if ( ! isset( $search_tree[10] ) ) {
			return;
		}

		$tree = array();
		if ( $multisite && isset( $search_tree[10]['shortcode-multisite'] ) ) {
			$tree = $search_tree[10]['shortcode-multisite'];
		} elseif ( ! $multisite && isset( $search_tree[10]['shortcode'] ) ) {
			$tree = $search_tree[10]['shortcode'];
		} else {
			return;
		}

		$prefix = ( $multisite ) ? '$$multisite$$-' : '';

		foreach ( $tree as $shortcode_id ) {
			$shortcode = explode( '-', $shortcode_id, 2 );

			if ( ! is_array( $shortcode ) || count( $shortcode ) !== 2 ) {
				continue;
			}

			$this->shortcodes[ $prefix . $shortcode[1] ] = $shortcode[0];
		}

		add_shortcode( 'ccj', array( $this, 'print_shortcode' ) );
	}

	/**
	 * Print the shortcode content
	 */
	function print_shortcode( $atts ) {
		if ( ! isset( $atts['id'] ) || ! ( isset( $this->shortcodes[ $atts['id'] ] ) || isset( $this->shortcodes[ '$$multisite$$-' . $atts['id'] ] ) ) ) {
			return;
		}

		if ( isset( $this->shortcodes[ $atts['id'] ] ) && isset( $this->shortcodes[ '$$multisite$$-' . $atts['id'] ] ) ) {
			unset( $this->shortcodes[ '$$multisite$$-' . $atts['id'] ] );
		}

		if ( is_multisite() && ! is_main_site() && isset( $this->shortcodes[ '$$multisite$$-' . $atts['id'] ] ) ) {
			$post = get_blog_post( $this->main_site_id, $this->shortcodes[ '$$multisite$$-' . $atts['id'] ] );
		} else {
			$post = get_post( $this->shortcodes[ $atts['id'] ] );
		}
		$ccj_content_print = $post->post_content;

		if ( count( $atts ) > 1 ) {
			foreach ( $atts as $_key => $_value ) {
				$ccj_content_print = str_replace( '{$' . $_key . '}', $_value, $ccj_content_print );
			}
		}

		ob_start();
		extract( $atts );
		eval( '?>' . str_ireplace( array( '&lt;?php', '?&gt;' ), array( '<?php', '?>' ), $ccj_content_print ) );
		$ccj_content_print = ob_get_clean();

		$ccj_content_print = do_shortcode( $ccj_content_print );

		return $ccj_content_print;
	}




	/**
	 * As in ABS_PATH . WP_INC . '/vars.php';
	 *
	 * on multi-site installations the vars.php file is loaded later.
	 */
	function is_mobile() {
		static $is_mobile = null;

		if ( isset( $is_mobile ) ) {
			return $is_mobile;
		}

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$is_mobile = false;
		} elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
			|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) {
				$is_mobile = true;
		} else {
			$is_mobile = false;
		}

		return $is_mobile;
	}


	/**
	 * Show an admin notice with the WP Conditional Tag errors, if necessary
	 */
	function show_wp_conditional_errors() {
		if ( ! is_array( $this->wp_conditional_error ) || count( $this->wp_conditional_error ) == 0 ) {
			return;
		}

		$class   = 'notice notice-error';
		$message = implode( '<br />', $this->wp_conditional_error );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}





}
