<?php

use \OTGS\Toolset\Common\Settings\BootstrapSetting;
use \OTGS\Toolset\Common\Settings\FontAwesomeSetting;

if ( ! class_exists( 'Toolset_Settings_Screen', false ) ) {

	/**
	 * Toolset_Settings_Screen
	 *
	 * Generic class for the shared settings entry for the Toolset family.
	 *
	 * @since 1.9
	 */
	class Toolset_Settings_Screen {

    	const PAGE_SLUG = 'toolset-settings';

		/**
		 * Toolset_Settings_Screen constructor.
		 */
		public function __construct() {
			add_filter( 'toolset_filter_register_common_page_slug', array( $this, 'register_settings_page_slug' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_filter( 'toolset_filter_register_menu_pages', array( $this, 'register_settings_page_in_menu' ), 60 );
			add_action( 'init', array( $this, 'init' ) );
		}

		public function register_settings_page_slug( $slugs ) {
			if ( ! in_array( self::PAGE_SLUG, $slugs ) ) {
				$slugs[] = self::PAGE_SLUG;
			}
			return $slugs;
		}

		public function admin_init() {

		}

		public function register_settings_page_in_menu( $pages ) {
			$pages[] = array(
				'slug'			=> self::PAGE_SLUG,
				'menu_title'	=> __( 'Settings', 'wpv-views' ),
				'page_title'	=> __( 'Settings', 'wpv-views' ),
				'callback'		=> array( $this, 'settings_page' )
			);
			return $pages;
		}

		function init() {
			// Admin bar settings
			add_filter( 'toolset_filter_toolset_register_settings_general_section', array( $this, 'toolset_admin_bar_settings' ), 10, 2 );
			add_action( 'wp_ajax_toolset_update_toolset_admin_bar_options', array( $this, 'toolset_update_toolset_admin_bar_options' ) );
			add_filter( 'toolset_filter_force_unset_shortcode_generator_option', array( $this, 'force_unset_shortcode_generator_option_to_disable' ), 99 );

			add_filter( 'toolset_filter_toolset_register_settings_general_section', array( $this, 'toolset_bootstrap_options' ), 30 );
			add_action( 'wp_ajax_toolset_update_bootstrap_version_status', array( $this, 'toolset_update_bootstrap_version_status' ) );

			add_filter( 'toolset_filter_toolset_register_settings_general_section', array( $this, 'toolset_font_awesome_options' ), 31 );
			add_action( 'wp_ajax_toolset_update_font_awesome_version_status', array( $this, 'toolset_update_font_awesome_version_status' ) );

			// WPML section: on priority 70, to endure that the WPML compatibility tab is added in the right place.
			add_filter( 'toolset_filter_toolset_register_settings_section', array( $this, 'wpml_section' ), 70 );
			// WPML section content: on priority 1 so it can remove any other content added by legacy plugins.
			add_action( 'toolset_filter_toolset_register_settings_wpml_section', array( $this, 'toolset_wpml_integration' ), 1 );
		}


		function settings_page() {

			$settings = Toolset_Settings::get_instance();
			// Which tab is selected?
			// First tab by default: general
			$current_tab = 'general';

			$registered_sections = array(
				'general'			=> array(
					'slug'	=> 'general',
					'title'	=> __( 'General', 'wpv-views' )
				),
			);
			$registered_sections = apply_filters( 'toolset_filter_toolset_register_settings_section', $registered_sections );

			if (
				isset( $_GET['tab'] )
				&& isset( $registered_sections[ $_GET['tab'] ] )
			) {
				$current_tab = sanitize_text_field( $_GET['tab'] );
			}
			?>

			<div class="wrap">
				<h1><?php _e( 'Toolset Settings', 'wpv-views' ) ?></h1>
				<span id="js-toolset-ajax-saving-messages" class="toolset-ajax-saving-messages js-toolset-ajax-saving-messages"></span>
				<?php
				$settings_menu = '';
				$settings_content = '';
				foreach ( $registered_sections as $section_slug => $section_data ) {
					$content_item_items = apply_filters( "toolset_filter_toolset_register_settings_{$section_slug}_section", array(), $settings );
					$menu_item_classname = array( 'js-toolset-nav-tab', 'toolset-nav-tab', 'nav-tab' );
					$content_item_classname = array( 'js-toolset-tabbed-section-item',  'toolset-tabbed-section-item ', 'toolset-tabbed-section-item-' . $section_slug, 'js-toolset-tabbed-section-item-' . $section_slug );
					if ( $section_slug == $current_tab ) {
						$menu_item_classname[] = 'nav-tab-active';
						$content_item_classname[] = 'toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item';
					}
					$settings_menu .= sprintf(
						'<a class="%s" href="%s" title="%s" data-target="%s">%s%s</a>',
						esc_attr( implode( ' ', $menu_item_classname ) ),
						admin_url( 'admin.php?page=toolset-settings&tab=' . $section_slug ),
						esc_attr( $section_data['title'] ),
						$section_slug,
						isset( $section_data['icon'] ) ? $section_data['icon'] : '',
						esc_html( $section_data['title'] )
					);

					$settings_content .= '<div class="' . implode( ' ', $content_item_classname ) . '">';
					ob_start();
					foreach ( $content_item_items as $item_slug => $item_data ) {
						$this->render_setting_section( $item_data );
					}
					$settings_content .= ob_get_clean();
					$settings_content .= '</div>';
				}
				?>
				<p class="toolset-tab-controls">
					<?php echo $settings_menu; ?>
				</p>
				<?php echo $settings_content; ?>
				<div class="toolset-debug-info-helper">
					<p>
					<?php
					echo __( 'Sometimes, our Customer Support personnel ask you to provide debug information. This information helps them give you quicker and better support.', 'wpv-views' );
					?>
					</p>
					<p>
					<?php
					echo sprintf(
						__( 'To get this information, go to %1$sToolset Debug Information and Troubleshooting %2$s.', 'wpv-views' ),
						'<a href="' . admin_url( 'admin.php?page=' . Toolset_Menu::TROUBLESHOOTING_PAGE_SLUG ) . '">',
						'</a>'
					);
					?>
					</p>
				</div>
			</div>
			<?php
		}

		public function render_setting_section( $item_data ) {
			include TOOLSET_COMMON_PATH . '/templates/toolset-setting-section.tpl.php';
		}

		function toolset_admin_bar_settings( $sections, $toolset_options ) {
			$toolset_admin_bar_menu_show = ( isset( $toolset_options['show_admin_bar_shortcut'] ) && $toolset_options['show_admin_bar_shortcut'] == 'off' ) ? false : true;
			$toolset_shortcodes_generator = ( isset( $toolset_options['shortcodes_generator'] ) && in_array( $toolset_options['shortcodes_generator'], array( 'unset', 'disable', 'editor', 'always' ) ) ) ? $toolset_options['shortcodes_generator'] : 'unset';
			ob_start();
			?>
			<h3><a name="shortcodes-settings" href="#"></a><?php echo __( 'Toolset shortcodes menu in the admin bar', 'wpv-views' ); ?></h3>
			<div class="toolset-advanced-setting">
				<p>
					<?php _e( "Toolset can display an admin bar menu in the backend to let you generate Toolset shortcodes in any page that you need them.", 'wpv-views' ); ?>
				</p>
				<ul class="js-shortcode-generator-form">
					<?php
						// Can be 'unset', 'disable', 'editor' or 'always'
						if ( $toolset_shortcodes_generator == 'unset' ) {
							$toolset_shortcodes_generator = apply_filters( 'toolset_filter_force_unset_shortcode_generator_option', $toolset_shortcodes_generator );
						}
						$shortcodes_generator_options = array(
							array(
								'label' =>  __( 'Disable the Toolset shortcodes menu in the admin bar', 'wpv-views' ),
								'value' => 'disable'
							),
							array(
								'label' => __( 'Show the Toolset shortcodes menu in the admin bar only when editing content', 'wpv-views' ),
								'value' => 'editor'
							),
							array(
								'label' => __( 'Show the Toolset shortcodes menu in the admin bar in all the admin pages', 'wpv-views' ),
								'value' => 'always'
							)
						);
						foreach( $shortcodes_generator_options as $option ) {
							printf(
								'<li><label><input type="radio" name="wpv-shortcodes-generator" class="js-toolset-shortcodes-generator js-toolset-admin-bar-options" value="%s" %s autocomplete="off" />%s</label></li>',
								$option['value'],
								checked( $option['value'] == $toolset_shortcodes_generator, true, false ),
								$option['label']
							);

						}

					?>
				</ul>
			</div>
			<h3><a name="design-with-toolset-settings" href="#"></a><?php echo __( 'Design with Toolset', 'wpv-views' ); ?></h3>
			<div class="toolset-advanced-setting">
				<p>
					<?php _e( "Toolset can display an admin bar menu in the frontend to let you create or edit Views and Content Templates related to the current page.", 'wpv-views' ); ?>
				</p>
				<p>
					<label>
						<input type="checkbox" name="wpv-toolset-admin-bar-menu" id="js-toolset-admin-bar-menu" class="js-toolset-admin-bar-menu js-toolset-admin-bar-options" value="1" <?php checked( $toolset_admin_bar_menu_show ); ?> autocomplete="off" />
						<?php _e( "Enable the Design with Toolset menu in the frontend", 'wpv-views' ); ?>
					</label>
				</p>
			</div>
			<?php
			wp_nonce_field( 'toolset_admin_bar_settings_nonce', 'toolset_admin_bar_settings_nonce' );
			?>
			<?php
			$section_content = ob_get_clean();

			$sections['admin-bar-settings'] = array(
				'slug'		=> 'admin-bar-settings',
				'title'		=> __( 'Admin bar options', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}

		function toolset_update_toolset_admin_bar_options() {
			$toolset_options = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			if (
				! isset( $_POST["wpnonce"] )
				|| ! wp_verify_nonce( $_POST["wpnonce"], 'toolset_admin_bar_settings_nonce' )
			) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			$frontend			= ( isset( $_POST['frontend'] ) ) ? sanitize_text_field( $_POST['frontend'] ) : 'true';
			$backend			= ( isset( $_POST['backend'] ) && in_array( $_POST['backend'], array( 'disable', 'editor', 'always' ) ) ) ? sanitize_text_field( $_POST['backend'] ) : null;
			if ( null != $backend ) {
				$toolset_options['shortcodes_generator'] = $backend;
			}
			$toolset_options['show_admin_bar_shortcut'] = ( $frontend == 'true' ) ? 'on' : 'off';
			$toolset_options->save();
			wp_send_json_success();
		}

		public function force_unset_shortcode_generator_option_to_disable( $state ) {
			if ( $state == 'unset' ) {
				$state = 'disable';
			}
			return $state;
		}



		/**
		 * Bootstrap - settings and saving
		 */

		function toolset_bootstrap_options( $sections ) {
			$is_disabled = '';
			$disabled_message = '';

			$settings = Toolset_Settings::get_instance();
			ob_start();
			?>
			<ul class="js-bootstrap-version-form">
				<?php

				$version_options = array(
					array(
						'label' => __( 'The theme or another plugin is already loading Bootstrap 3', 'wpv-views' ),
						'value' => BootstrapSetting::BS3_EXTERNAL,
					),
					array(
						'label' => __( 'The theme or another plugin is already loading Bootstrap 4', 'wpv-views' ),
						'value' => BootstrapSetting::BS4_EXTERNAL,
					),
					array(
						'label' => __( 'Toolset should load Bootstrap 3', 'wpv-views' ),
						'value' => BootstrapSetting::BS3_TOOLSET,
					),
					array(
						'label' => __( 'Toolset should load Bootstrap 4', 'wpv-views' ),
						'value' => BootstrapSetting::BS4_TOOLSET,
					),
					array(
						'label' => __( 'This site is not using Bootstrap CSS', 'wpv-views' ),
						'value' => BootstrapSetting::NO_BOOTSTRAP,
					),
				);

				// Display the Bootstrap 2 option only when it's already selected. Otherwise, we won't support this anymore.
				if( BootstrapSetting::BS2_EXTERNAL === $settings->toolset_bootstrap_version ) {
					array_unshift( $version_options, [
						'label' => __( 'The theme or another plugin is already loading Bootstrap 2 (deprecated)', 'wpv-views' ),
						'value' => BootstrapSetting::BS2_EXTERNAL,
					] );
				}

				foreach( $version_options as $option ) {

					printf(
						'<li>
							<label class="js-tolset-option-%s">
								<input type="radio" name="wpv-bootstrap-version"
									class="js-toolset-bootstrap-version" value="%s" %s %s autocomplete="off" />
								%s
							</label>
						</li>',
						esc_attr( str_replace( '.', '', $option['value'] ) ),
						esc_attr( $option['value'] ),
						checked( $option['value'], $settings->toolset_bootstrap_version, false ),
						disabled( $is_disabled, true, false ),
						$option['label']
					);

				}

				?>
			</ul>

			<?php echo $disabled_message; ?>

			<?php

			wp_nonce_field( 'toolset_bootstrap_version_nonce', 'toolset_bootstrap_version_nonce' );

			$section_content = ob_get_clean();

			$sections['bootstrap-settings'] = array(
				'slug'		=> 'bootstrap-settings',
				'title'		=> __( 'Bootstrap loading', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}

		/**
		 * Update the Views Bootrstap version
		 *
		 * $_POST:
		 * 	wpnonce:	wpv_bootstrap_version_nonce
		 * 	status:		1|2|3|3.toolset|-1
		 */
		function toolset_update_bootstrap_version_status() {
			$settings = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}

			if ( ! wp_verify_nonce( toolset_getpost( 'wpnonce' ), 'toolset_bootstrap_version_nonce' ) ) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}

			$status = toolset_getpost( 'status' );
			// phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
			if ( ! in_array( $status, BootstrapSetting::VALID_VALUES, false ) ) {
				wp_send_json_error();
				return;
			}
			$settings->toolset_bootstrap_version = $status;
			$settings->save();
			wp_send_json_success();
		}

		/**
		 * Font Awesome version setting.
		 *
		 * @param array $sections
		 * @return array
		 */
		public function toolset_font_awesome_options( $sections ) {
			$settings = Toolset_Settings::get_instance();
			ob_start();
			?>
			<ul class="js-toolset-font-awesome-version-form">
				<?php
				$version_options = array(
					array(
						'label' => __( 'Toolset should use Font Awesome 4 (4.7.0)', 'wpv-views' ),
						'value' => FontAwesomeSetting::FA_4,
					),
					array(
						'label' => __( 'Toolset should use Font Awesome 5 (5.13.0)', 'wpv-views' ),
						'value' => FontAwesomeSetting::FA_5,
					),
				);

				foreach ( $version_options as $option ) {
					printf(
						'<li>
							<label class="js-tolset-font-awesome-option-%s">
								<input type="radio" name="toolset-font-awesome-version"
									class="js-toolset-font-awesome-version" value="%s" %s autocomplete="off" />
								%s
							</label>
						</li>',
						esc_attr( $option['value'] ),
						esc_attr( $option['value'] ),
						checked( $option['value'], $settings->toolset_font_awesome_version, false ),
						$option['label']
					);

				}
				?>
			</ul>
			<?php
			wp_nonce_field( 'toolset_font_awesome_version_nonce', 'toolset_font_awesome_version_nonce' );

			$section_content = ob_get_clean();

			$sections['font-awesome-settings'] = array(
				'slug'		=> 'font-awesome-settings',
				'title'		=> __( 'Font Awesome', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}

		/**
		 * Font Awesome save.
		 *
		 * $_POST:
		 * 	wpnonce:	wpv_font_awesome_version_nonce
		 * 	status:		3|4
		 */
		public function toolset_update_font_awesome_version_status() {
			$settings = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}

			if ( ! wp_verify_nonce( toolset_getpost( 'wpnonce' ), 'toolset_font_awesome_version_nonce' ) ) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}

			$status = toolset_getpost( 'status' );
			// phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
			if ( ! in_array( $status, FontAwesomeSetting::VALID_VALUES, false ) ) {
				wp_send_json_error();
				return;
			}
			$settings->toolset_font_awesome_version = $status;
			$settings->save();
			wp_send_json_success();
		}

		/**
		 * Register a WPML compatibility section.
		 *
		 * @param array $sections
		 * @return array
		 */
		public function wpml_section( $sections ) {
			$sections['wpml'] = array(
				'slug'	=> 'wpml',
				'title'	=> __( 'WPML integration', 'wpv-views' )
			);

			return $sections;
		}

		/**
		 * Render the WPML compatibility section.
		 *
		 * Note that legacy sections registered by Types or Views will no longer be printed.
		 *
		 * @param array $sections
		 * @return array
		 */
		public function toolset_wpml_integration( $sections ) {
			// Backwards compatibility: remove all sections addded by individual plugins.
			remove_all_actions( 'toolset_filter_toolset_register_settings_wpml_section' );

			$wpml_compatibility = \Toolset_WPML_Compatibility::get_instance();

			$wpml_installed = $wpml_compatibility->is_wpml_active();
			$wpml_configured = $wpml_compatibility->is_wpml_active_and_configured();
			$wpml_st_installed = $wpml_compatibility->is_wpml_st_active();
			$wpml_tm_installed = $wpml_compatibility->is_wpml_tm_active();

			$string_is_active = __( '%s is installed and active.', 'wpv-views' );
			$string_is_not_configued = __( '%s is active but not configured.', 'wpv-views' );
			$string_is_missing = __( '%s is either not installed or not active.', 'wpv-views' );

			$install_string = __( 'Get %s.', 'wpv-views' );
			$install_link = admin_url( 'plugin-install.php?tab=commercial#wpml' );
			if ( ! class_exists( 'WP_Installer' ) ) {
				$install_link = 'https://wpml.org/';
			}

			ob_start();
			?>
			<ul>
			<?php
			if ( $wpml_installed ) {
				if ( $wpml_configured ) { ?>
				<li>
					<i class="otgs-ico-ok wpml-multilingual-cms" style="color: green;"></i>
					<?php
					echo sprintf( $string_is_active, '<strong>WPML</strong>' );
					?>
				</li>
				<?php } else { ?>
				<li>
					<i class="otgs-ico-warning wpml-multilingual-cms" style="color: orange;"></i>
					<?php echo sprintf( $string_is_not_configued, '<strong>WPML</strong>' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sitepress-multilingual-cms/menu/languages.php' ) ); ?>">
						<?php echo __( 'Configure WPML', 'wpv-views' ); ?>
					</a>
				</li>
				<?php }

				if ( $wpml_st_installed ) { ?>
				<li>
					<i class="otgs-ico-ok wpml-multilingual-cms" style="color: green;"></i>
					<?php echo sprintf( $string_is_active, '<strong>WPML String Translation</strong>' ); ?>
				</li>
				<?php } else { ?>
				<li>
					<i class="otgs-ico-warning wpml-multilingual-cms" style="color: red;"></i>
					<?php echo sprintf( $string_is_missing, '<strong>WPML String Translation</strong>' ); ?>
					<a href="<?php echo esc_url( $install_link ); ?>">
						<?php echo sprintf( $install_string, 'WPML String Translation' ); ?>
					</a>
				</li>
				<?php }

				if ( $wpml_tm_installed ) { ?>
				<li>
					<i class="otgs-ico-ok wpml-multilingual-cms" style="color: green;"></i>
					<?php echo sprintf( $string_is_active, '<strong>WPML Translation Management</strong>' ); ?>
				</li>
				<?php } else { ?>
				<li>
					<i class="otgs-ico-warning wpml-multilingual-cms" style="color: red;"></i>
					<?php echo sprintf( $string_is_missing, '<strong>WPML Translation Management</strong>' ); ?>
					<a href="<?php echo esc_url( $install_link ); ?>">
						<?php echo sprintf( $install_string, 'WPML Translation Management' ); ?>
					</a>
				</li>
				<?php }
			} else { ?>
				<li>
					<i class="otgs-ico-warning wpml-multilingual-cms" style="color: red;"></i>
					<?php echo sprintf( $string_is_missing, '<strong>WPML</strong>' ); ?>
					<a href="<?php echo esc_url( $install_link ); ?>">
						<?php echo sprintf( $install_string, 'WPML' ); ?>
					</a>
				</li>
			<?php } ?>
			</ul>

			<?php
			$views_active_condition = new \Toolset_Condition_Plugin_Views_Active();
			if ( $views_active_condition->is_met() ) { ?>
			<hr style="margin: 30px 0;">
			<p>
				<?php _e( 'Need help?', 'wpv-views' ); ?> <a href="https://toolset.com/course-chapter/translating-directory-and-classifieds-sites/?utm_source=plugin&utm_medium=gui&utm_campaign=toolset" target="_blank"> <?php _e( 'Translating Views and Content Templates with WPML', 'wpv-views' ); ?> &raquo; </a>
			</p>
			<?php } ?>

			<?php
			$section_content = ob_get_clean();

			$sections['toolset-views'] = array(
				'slug'		=> 'toolset-views',
				'title'		=> __( 'Toolset and WPML integration', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}
	}

}
