<?php
/**
 * This class will take care of loading bootstrap components buttons and custom buttons added by user
 *
 * @since unknown Layouts 1.8
 * @since 2.3.3 Addd the Bootstrap Grid component.
 * @since BS4 Limited to adding the Bootstrap Grid button in Codemirror editors.
 * @refactoring This should eventually become a part of the BootstrapLoader class.
 */
if ( ! class_exists( 'Toolset_CssComponent' ) ) {

	Class Toolset_CssComponent {

		const BOOTSTRAP3_GRID_DOCUMENTATION = 'https://getbootstrap.com/docs/3.4/css/#grid';
		const BOOTSTRAP4_GRID_DOCUMENTATION = 'https://getbootstrap.com/docs/4.3/layout/grid/';


		public function initialize() {
			if ( is_admin() ) {
				add_action( 'admin_print_scripts', array( &$this, 'admin_enqueue_scripts' ) );
			} else {
				add_action( 'wp_print_scripts', array( &$this, 'admin_enqueue_scripts' ) );
			}

			add_filter( 'toolset_add_registered_script', array( &$this, 'add_register_scripts' ) );
			add_filter( 'toolset_add_registered_styles', array( &$this, 'add_register_styles' ) );
		}


		/**
		 * Register the Bootstrap component scripts.
		 *
		 * @param array $scripts
		 *
		 * @return array
		 *
		 * @since unknown
		 * @since 2.3.3 Added the Bootstrap grid component.
		 */
		public function add_register_scripts( $scripts ) {
			$scripts['toolset-css-component-grids'] = new Toolset_Script(
				'toolset-css-component-grids', TOOLSET_COMMON_URL . "/res/js/toolset-bs-component-grids.js",
				array( 'jquery', 'jquery-ui-dialog', 'underscore', 'icl_editor-script', 'toolset-event-manager' ),
				true
			);

			return $scripts;
		}


		public function add_register_styles( $styles ) {
			$styles['toolset-bs-component-style'] = new Toolset_Style( 'toolset-bs-component-style', TOOLSET_COMMON_URL
				. '/res/css/toolset-bs-component.css', array( 'onthego-admin-styles' ), TOOLSET_VERSION );
			$styles['glyphicons'] = new Toolset_Style( 'glyphicons', TOOLSET_COMMON_URL
				. '/res/lib/glyphicons/css/glyphicons.css', array(), '3.3.5', 'screen' );

			return $styles;
		}


		/**
		 * Enqueue the Bootstrap component scripts.
		 *
		 * @since unknown
		 * @since 2.3.3 Added the Bootstrap grid component.
		 */
		public function admin_enqueue_scripts() {

			if ( ! $this->is_allowed_page() ) {
				return;
			}

			do_action( 'toolset_enqueue_styles', array(
				'toolset-bs-component-style',
				'wp-jquery-ui-dialog',
				'ddl-dialogs-css',
				'glyphicons',
			) );

			do_action( 'toolset_enqueue_scripts', array(
				'toolset-css-component-grids',
			) );

			do_action( 'toolset_localize_script', 'toolset-css-component-grids', 'Toolset_CssComponent_Grids', array(
					'button' => array(
						'label' => __( 'Grid', 'wpv-views' ),
					),
					'dialog' => array(
						'title' => __( 'Bootstrap Grid', 'wpv-views' ),
						'content' => $this->get_grid_dialog_content(),
						'insert' => __( 'Insert grid', 'wpv-views' ),
						'cancel' => __( 'Cancel', 'wpv-views' ),
					),
					'bootstrapVersion' => Toolset_Settings::get_instance()->get_bootstrap_version_numeric(),
				)
			);
		}


		/**
		 * Generate the Botstrap grid dialog content.
		 *
		 * @return string Bootstrap dialog content.
		 *
		 * @since 2.3.3
		 */
		public function get_grid_dialog_content() {
			ob_start();
			?>
			<div id="js-toolset-dialog-bootstrap-grid-dialog"
				class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container">
				<div class="wpv-dialog">
					<div class="toolset-bootstrap-grid-types-container">
						<ul class="toolset-bootstrap-grid-types js-toolset-bootstrap-grid-type">
							<li>
								<figure class="grid-type selected">
									<img class="item-preview" data-name="grid-type-two-even"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/two-even.png"
										alt="<?php echo esc_html( __( '2 even columns', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '2 even columns', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-two-even" for="grid-type-two-even"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-two-even" value="two-even"
										checked="checked">
									<?php echo esc_html( __( '2 even columns', 'wpv-views' ) ); ?>
								</label>
							</li>
							<li>
								<figure class="grid-type">
									<img class="item-preview" data-name="grid-type-two-uneven"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/two-uneven-wide-narrow.png"
										alt="<?php echo esc_html( __( '2 columns (wide and narrow)', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '2 columns (wide and narrow)', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-two-uneven" for="grid-type-two-uneven"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-two-uneven" value="two-uneven">
									<?php echo esc_html( __( '2 columns (wide and narrow)', 'wpv-views' ) ); ?>
								</label>
							</li>
							<li>
								<figure class="grid-type">
									<img class="item-preview" data-name="grid-type-three-even"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/three-even.png"
										alt="<?php echo esc_html( __( '3 even columns', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '3 even columns', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-three-even" for="grid-type-three-even"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-three-even" value="three-even">
									<?php echo esc_html( __( '3 even columns', 'wpv-views' ) ); ?>
								</label>
							</li>
							<li>
								<figure class="grid-type">
									<img class="item-preview" data-name="grid-type-three-uneven"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/three-uneven-narrow-wide-narrow.png"
										alt="<?php echo esc_html( __( '3 columns (1 wide and 2 narrow)', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '3 columns (1 wide and 2 narrow)', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-three-uneven" for="grid-type-three-uneven"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-three-uneven"
										value="three-uneven">
									<?php echo esc_html( __( '3 columns (1 wide and 2 narrow)', 'wpv-views' ) ); ?>
								</label>
							</li>
							<li>
								<figure class="grid-type">
									<img class="item-preview" data-name="grid-type-four-even"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/four-even.png"
										alt="<?php echo esc_html( __( '4 even columns', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '4 even columns', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-four-even" for="grid-type-four-even"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-four-even" value="four-even">
									<?php echo esc_html( __( '4 even columns', 'wpv-views' ) ); ?>
								</label>
							</li>
							<li>
								<figure class="grid-type">
									<img class="item-preview" data-name="grid-type-six-even"
										src="<?php echo TOOLSET_COMMON_URL; ?>/res/images/toolset.bs-component/six-even.png"
										alt="<?php echo esc_html( __( '6 even columns', 'wpv-views' ) ); ?>">
									<span><?php echo esc_html( __( '6 even columns', 'wpv-views' ) ); ?></span>
								</figure>
								<label class="radio" data-target="grid-type-six-even" for="grid-type-six-even"
									style="display:none">
									<input type="radio" name="grid_type" id="grid-type-six-even" value="six-even">
									<?php echo esc_html( __( '6 even columns', 'wpv-views' ) ); ?>
								</label>
							</li>
						</ul>
					</div>
					<div class="toolset-bootstrap-grid-types-documentation">
						<?php
						$url = (
							Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4
								? self::BOOTSTRAP4_GRID_DOCUMENTATION
								: self::BOOTSTRAP3_GRID_DOCUMENTATION
						);

						printf(
							'<a href="%s" target="_blank">
								%s<span style="margin-left: 3px;" style="text-decoration: none;"></span><i style="text-decoration: none;" class="icon-external-link fa fa-external-link icon-small"></i>
							</a>',
							$url,
							esc_html( __( 'Bootstrap Grid documentation', 'wpv-views' ) ),
							$url
						);
						?>
					</div>
				</div>
			</div>
			<?php
			$content = ob_get_clean();

			return $content;
		}


		// check is allowed page currently loaded
		public function is_allowed_page() {

			$allowed_screens = array(
				'toolset_page_views-editor',
				'toolset_page_ct-editor',
				'toolset_page_view-archives-editor',
				'toolset_page_dd_layouts_edit',
				'page',
				'post',
			);

			$allowed_pages = array(
				'dd_layouts_edit',
				'views-editor',
				'ct-editor',
				'view-archives-editor',
				'cred_relationship_form',
			);

			$bootstrap_available = false;
			$bootstrap_version = Toolset_Settings::get_instance();

			if ( isset( $bootstrap_version->toolset_bootstrap_version )
				&& $bootstrap_version->toolset_bootstrap_version
				!= "-1" ) {
				$bootstrap_available = true;
			}

			if ( defined( 'LAYOUTS_PLUGIN_NAME' ) ) {
				$bootstrap_available = true;
			}

			if ( is_admin() ) {

				$screen_id = '';
				$screen_base = '';
				$screen = get_current_screen();

				if ( isset( $screen ) ) {
					$screen_id = $screen->id;
					$screen_base = ( isset( $screen->base ) ) ? $screen->base : false;
				}

				if ( in_array( $screen_id, $allowed_screens ) && $bootstrap_available === true ) {
					return true;
				}

				if ( in_array( $screen_base, $allowed_screens ) && $bootstrap_available === true ) {
					return true;
				}

				if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages )
					&& $bootstrap_available
					=== true ) {
					return true;
				}
			} else {
				if ( isset( $_GET['toolset_editor'] ) === true ) {
					return true;
				}
			}

			return false;
		}
	}
}

