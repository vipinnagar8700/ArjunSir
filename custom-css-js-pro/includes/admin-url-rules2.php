<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_URLRules
 */
class CustomCSSandJS_URLRules {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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

		// Some handy variables
		$a  = plugins_url( '/', CCJ_PLUGIN_FILE_PRO ) . 'assets';
		$cm = $a . '/codemirror';
		$v  = CCJ_VERSION_PRO;

		wp_enqueue_script( 'ccj_select2', $a . '/select2.full.js', array( 'jquery' ), $v, false );
		wp_enqueue_script( 'ccj_dot', $a . '/doT.min.js', array( 'jquery' ), $v, false );
		wp_enqueue_script( 'ccj_repeatable', $a . '/jquery.repeatable.item.min.js', array( 'jquery' ), $v, false );

		wp_enqueue_style( 'ccj_select2', $a . '/select2.min.css', array(), $v );

	}


	/**
	 * Return the URL Rules filters
	 */
	function get_filters( $a = 'type' ) {

		/*** Apply-equal */
		$apply_equal_url_array = array(
			'contains'     => 'contains',
			'not-contains' => 'not contains',
			'equal-to'     => 'is equal to',
			'not-equal-to' => 'is not equal to',
			'begins-with'  => 'starts with',
			'ends-by'      => 'ends by',

		);
		$apply_equal_url = '';
		foreach ( $apply_equal_url_array as $_key => $_value ) {
			$apply_equal_url .= '<option value="' . $_key . '">' . $_value . '</option>';
		}
		if ( $a == 'apply_equal_url' ) {
			return $apply_equal_url;
		}

		$apply_equal_all = '<option value="equal">is equal to</option><option value="no-equal">is not equal to</option>';
		if ( $a == 'apply_equal_all' ) {
			return $apply_equal_all;
		}

		/*** Apply-type */
		$post_types = $this->get_post_types( 'post_types' );
		$taxonomies = $this->get_post_types( 'taxonomies' );
		$type       = array(
			'url'  => 'URL',
			'view' => 'View',
			array(
				'label'        => 'Posts/Pages',
				'post-special' => 'Special Page',
				'post-type'    => 'Post Type',
			),
			array(
				'label' => 'Categories',
				/*
				'cat-category' => 'Posted in Category',
				'cat-post_tag' => 'Posted in Tag',
				'cat-other' => 'Posted in other cat',
				 */
			),
			array(
				'label'        => 'Archives',
				'tax-special'  => 'Special Archive',
				'tax-category' => 'Category Archive',
				'tax-post_tag' => 'Tag Archive',
			),

			array(
				'label'                 => 'Other',
				'modules-active_theme'  => 'Active Theme',
				'modules-active_plugin' => 'Active Plugin',
				'system-device'         => 'Device',
				'system-browser'        => 'Browser',
			),
		);
		foreach ( $post_types as $_key => $_value ) {
			$type[0][ 'post-' . $_key ] = $_value;
		}
		foreach ( $taxonomies as $_key => $_value ) {
			$type[1][ 'cat-' . $_key ] = 'Posted in ' . $_value;
		}
		if ( $a == 'type' ) {
			return $type;
		}

		$select_data = array(
			'url'          => array(),
			'view'         => array(
				'tax'     => 'Taxonomy (Category, Tag, Custom Post Category)',
				'archive' => 'Archive (Taxonomy, Search, Data Archive)',
				'single'  => 'Single (Page, Post, Custom Post)',
			),
			'post-special' => array(
				'home-page'   => 'Home Page',
				'first-page'  => 'Homepage',
				'search-page' => 'Search Page',
				'404-page'    => '404 Page',
			),
			'post-type'    => $post_types,
		);
		foreach ( $post_types as $_key => $_value ) {
			$select_data[ 'post-' . $_key ] = $this->get_posts( $_key );
		}
		foreach ( $taxonomies as $_key => $_value ) {
			$select_data[ 'cat-' . $_key ] = $this->get_terms( $_key );
		}
		/*
		foreach ($taxonomies as $_key => $_value) {
			$select_data['cat-'.$_key] = $this->get_terms($_key, 'orderby=count&hide_empty=0');
		}
		 */
		if ( $a == 'select_data' ) {
			return $select_data;
		}

		return $filters = array(
			'all'            => __( 'All Website' ),
			'first-page'     => __( 'Homepage' ),
			'contains'       => __( 'URL contains ..' ),
			'not-contains'   => __( 'URL not contains ..' ),
			'equal-to'       => __( 'URL is equal to ..' ),
			'not-equal-to'   => __( 'URL not equal to ..' ),
			'begins-with'    => __( 'URL starts with ..' ),
			'ends-by'        => __( 'URL ends by ..' ),
			'wp-conditional' => __( 'WP Conditional Tag ..' ),
		);
	}

	function get_terms( $type = 'post' ) {
		$terms  = get_terms( $type, 'order_by=count&hide_empty=0' );
		$return = array();
		foreach ( $terms as $_t ) {
			$return[ $_t->slug ] = $_t->name;
		}

		return $return;
	}



	function get_posts( $type = 'post' ) {

		$posts  = get_posts( 'posts_per_page=100&post_type=' . $type );
		$return = array();
		foreach ( $posts as $_post ) {
			$return[ $_post->post_name ] = $_post->post_title;
		}

		return $return;
	}


	function get_post_types( $type = 'post_types' ) {
		if ( $type == 'post_types' ) {
			$post_types_raw = get_post_types( array( 'public' => true ), 'objects' );
		} elseif ( $type = 'taxonomies' ) {
			$post_types_raw = get_taxonomies( array( 'public' => true ), 'objects' );
		}

		if ( ! is_array( $post_types_raw ) || count( $post_types_raw ) == 0 ) {
			return array();
		}

		$post_types = array();
		foreach ( $post_types_raw as $_post_type ) {
			$post_types[ $_post_type->name ] = $_post_type->label;
		}
		return $post_types;
	}

	/**
	 * Add the URL Rules metabox
	 */
	function add_meta_boxes() {

		add_meta_box( 'url-rules', __( 'Apply only on these Pages' ) . ccj_a_doc( 'https://www.silkypress.com/simple-custom-css-js-pro-documentation/#doc-url-rules' ), array( $this, 'url_rules_meta_box_callback' ), 'custom-css-js', 'normal' );
	}

	/**
	 * Show the URL Rules metabox
	 */
	function url_rules_meta_box_callback( $post ) {

		$apply_type_array = $this->get_filters( 'type' );

		$apply_type = '';
		foreach ( $apply_type_array as $_key => $_value ) {
			if ( ! is_array( $_value ) ) {
				$apply_type .= '<option value="' . $_key . '">' . $_value . '</option>' . PHP_EOL;
			} else {
				$apply_type .= '<optgroup label="' . $_value['label'] . '">' . PHP_EOL;
				unset( $_value['label'] );
				foreach ( $_value as $__key => $__value ) {
					$apply_type .= "\t" . '<option value="' . $__key . '">' . $__value . '</option>' . PHP_EOL;
				}
				$apply_type .= '</optgroup>' . PHP_EOL;
			}
		}

		?>
	<ul class="repeatable" data-empty-list-message="item" data-add-button-class="button button-primary" data-add-button-label="Add Rule" data-confirm-remove="yes" data-confirm-remove-message="Are you sure you want to remove this rule?">
		<li data-template="yes" class="list-item">
			<select class="select-basic-single apply-type" style="width: 25%" name="apply[{index}][type]">
				<?php echo $apply_type; ?>
			</select>
		</select>

			<select class="select-basic-single apply-equal" style="width: 20%" name="apply[{index}][equal]">
				<?php echo $this->get_filters( 'apply_equal_url' ); ?>
			</select>

			<select class="select-data-array apply-data" multiple="multiple" style="width: 48%" name="apply[{index}][value][]"></select>

			<span style="width: 3%">
				<a href="#" class="select-remove" title="Remove rule" data-remove="yes">x</a>
			</span>
		</li>
	</ul>
		<?php
	}



	function admin_head() {

		$screen = get_current_screen();

		// Only for custom-css-js post type
		if ( $screen->post_type != 'custom-css-js' ) {
			return false;
		}

		$apply_equal_all = $this->get_filters( 'apply_equal_all' );
		$apply_equal_url = $this->get_filters( 'apply_equal_url' );
		$select_data     = $this->get_filters( 'select_data' );

		$data = array();
		foreach ( $select_data as $_key => $_value ) {
			if ( is_array( $_value ) && count( $_value ) > 0 ) {
				foreach ( $_value as $__key => $__value ) {
					$data[ $_key ][] = array(
						'id'   => $__key,
						'text' => $__value,
					);
				}
			} else {
				$data[ $_key ] = $_value;
			}
		}

		?>

	  <script type="text/javascript">
		jQuery(document).ready(function($) {

			var data = <?php echo json_encode( $data ); ?> 
			var apply_type_options = {minimumResultsForSearch: Infinity};
			var apply_data_options = {
				  tags: true,
				  tokenSeparators: [',', ' '],
				  data: data.url
			};

			$( '.repeatable' ).repeatable_item();
			$(".select-basic-single").select2(apply_type_options);
			$(".select-data-array").select2(apply_data_options);
			change_apply_type();

			$('ul.repeatable').on('repeatable-new-item', function(list, item, index) {
				$(index).find(".select-basic-single").select2(apply_type_options);
				apply_data_options.data = data.url;
				$(index).find(".select-data-array").select2(apply_data_options);
				change_apply_type();
			});

			function change_apply_type() {
				$('.apply-type').on('change', function(){
					var apply_equal_options = '<?php echo $apply_equal_all; ?>';
					if ($(this).val() == 'url') {
						apply_equal_options = '<?php echo $apply_equal_url; ?>';
					}
					$(this).parent().find('.apply-equal').html(apply_equal_options);   

					$(this).parent().find('.apply-data').children().remove();
					apply_data_options.data = data[$(this).val()];
					$(this).parent().find('.apply-data').select2(apply_data_options);
				});
			}

		});
	  </script>

		<?php
	}


	/**
	 * Save the post and the metadata
	 */
	function save_meta_box_data( $post_id ) {

		// The usual checks
		if ( ! isset( $_POST['custom-css-js_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['custom-css-js_meta_box_nonce'], 'options_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'custom-css-js' != $_POST['post_type'] ) {
			return;
		}

		update_post_meta( $post_id, 'urls', $_POST['scan_anchor_filters'] );

		$this->build_url_tree();
	}


	/**
	 * Build a tree where you can quickly find the urls on which the code is active
	 */
	private function build_url_tree() {

		$posts = query_posts( 'post_type=custom-css-js&post_status=publish&nopaging=true' );

		if ( ! is_array( $posts ) || count( $posts ) == 0 ) {
			return false;
		}

		$filters = $this->get_filters();

		$tree = array();
		foreach ( $posts as $_post ) {
			$rules = get_post_meta( $_post->ID, 'urls', true );
			if ( ! $rules ) {
				$rules = '[{"value":"","type":"all","index":1}]';
			}
			$rules = $this->check_array_json( $rules );

			$options = ccj_get_options( $_post->ID );
			if ( ! isset( $options['language'] ) ) {
				continue;
			}
			if ( $options['language'] == 'html' ) {
				$file = $_post->ID;
			} else {
				$file = $_post->ID . '.' . $options['language'];
			}

			foreach ( $rules as $_key => $_rule ) {
				if ( empty( $_rule['value'] ) ) {
					$tree[ $_rule['type'] ][] = $file;
				} else {
					$tree[ $_rule['type'] ][ $_rule['value'] ][] = $file;
				}
			}
		}

		update_option( 'custom-css-js-urls', $tree );
	}

	/**
	 * Check a json array value
	 */
	public function check_array_json( $array, $default = array() ) {
		$value = @json_decode( $array, true );
		return ( empty( $value ) || ! is_array( $value ) ) ? $default : $value;
	}





}

return new CustomCSSandJS_URLRules();
