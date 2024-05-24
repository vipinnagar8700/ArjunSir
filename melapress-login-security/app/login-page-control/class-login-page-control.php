<?php
/**
 * PPM Email Settings
 *
 * @package    WordPress
 * @subpackage wpassword
 * @author     Melapress
 */

 namespace PPMWP;

 use \PPMWP\Helpers\OptionsHelper;
 use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( '\PPMWP\MLS_Login_Page_Control' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class MLS_Login_Page_Control {

		/**
		 * Keeps track of login page status.
		 *
		 * @var bool
		 */
		private $is_login_page;

		/**
		 * Keeps check of geo blocking status.
		 *
		 * @var bool
		 */
		private $is_geo_check_required;

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 */
		public function init() {
			$ppm = ppm_wp();
			if ( isset( $ppm->options->ppm_setting->custom_login_url ) && ! empty( $ppm->options->ppm_setting->custom_login_url ) ) {
				add_filter( 'site_url', array( $this, 'login_control_site_url' ), 10, 4 );
				add_filter( 'network_site_url', array( $this, 'login_control_network_site_url' ), 10, 3 );
				add_filter( 'wp_redirect', array( $this, 'login_control_wp_redirect' ), 10, 2 );
				add_filter( 'site_option_welcome_email_content', array( $this, 'welcome_email_content' ) );
				add_filter( 'user_request_action_email_content', array( $this, 'user_request_action_email_content' ), 999, 2 );
				remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
				add_filter( 'login_url', array( $this, 'login_control_login_url' ), 10, 3 );
			}
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab_link( $markup ) {
			return $markup . '<a href="#integrations" class="nav-tab" data-tab-target=".ppm-integrations">' . esc_attr__( 'Integrations', 'ppm-wp' ) . '</a>';
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab( $markup ) {
			ob_start(); ?>
			<div class="settings-tab ppm-integrations">
				<table class="form-table">
					<tbody>
						<?php self::render_integration_settings(); ?>
					</tbody>
				</table>
			</div>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Display settings markup for email tempplates.
		 *
		 * @return void
		 */
		public static function render_integration_settings() {
			$ppm = ppm_wp();
			?>
				
				<tr>
					<th><label><?php esc_html_e( 'IPLocate API Key:', 'ppm-wp' ); ?></label></th>
					<td>
						<p class="description mb-10">IP checking is handled by IPLocate.io, please <a href="https://www.iplocate.io/" target="_blank">click here</a> to get your own key.</p><br>
						<input type="text" id="iplocate_api_key" class="regular regular-text" name="_ppm_options[iplocate_api_key]" placeholder="" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->iplocate_api_key ) ? rtrim( $ppm->options->ppm_setting->iplocate_api_key, '/' ) : '' ); ?>" minlength="32">
					</td>
				</tr>
			   <?php
		}

		/**
		 * Display settings markup for email tempplates.
		 *
		 * @return void
		 */
		public static function render_login_page_url_settings() {
			$ppm = ppm_wp();
			?>
				<br>
				<?php if ( is_multisite() ) { ?>
				<i class="description" style="max-width: none;">
					<?php esc_html_e( 'Please note: this will affect all sites on the network.', 'ppm-wp' ); ?>
				</i>
				<?php } ?>

				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Login page URL', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo trailingslashit( site_url() ); ?></p>
							<input type="text" name="_ppm_options[custom_login_url]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->custom_login_url ) ? rtrim( $ppm->options->ppm_setting->custom_login_url, '/' ) : '' ); ?>" id="ppm-custom_login_url" style="float: left; display: block; width: 250px;" />
							<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Old login page URL redirect', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo trailingslashit( site_url() ); ?></p>
							<input type="text" name="_ppm_options[custom_login_redirect]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->custom_login_redirect ) ? rtrim( $ppm->options->ppm_setting->custom_login_redirect, '/' ) : '' ); ?>" id="ppm-custom_login_redirect" style="float: left; display: block; width: 250px;" />
							<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
							<br>
							<br>
							<p class="description">
								<?php esc_html_e( 'Redirect anyone who tries to access the default WordPress login page URL to the above configured URL.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			<?php
		}

		public static function render_login_geo_settings() {
			$ppm = ppm_wp();
			$iplocate_api_key = isset( $ppm->options->ppm_setting->iplocate_api_key ) ? $ppm->options->ppm_setting->iplocate_api_key : false;

			$inactive_users_url = add_query_arg(
				array(
					'page' => 'ppm-settings#integrations',
				),
				network_admin_url( 'admin.php' )
			);

			?>
				<br>
				<h3><?php esc_html_e( 'Block or allow access to the login page by countries', 'ppm-wp' ); ?></h3>
				<p class="description" style="max-width: none;">
					<?php
						printf(
							esc_html__( 'Use the below setting to either block access to the login page for IP addresses from certain countries, or to restrict access to the login page to IP addresses from certain countries. To add a country enter its respective %1$1s in the field below. To use this feature you will need to provide an API key via %2$2s.', 'ppm-wp' ),
							sprintf(
								'<a target="_blank" href="https://www.iso.org/obp/ui/#search/code/">%s</a>',
								esc_html__( 'ISO country code', 'ppm-wp' )
							),
							sprintf(
								'<a target="_blank" href="%1$s">%2$s</a>',
								esc_url( $inactive_users_url ),
								esc_html__( 'integration settings', 'ppm-wp' )
							)
						);
					?>
				</p>
				<br>

				<tr valign="top" <?php if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) { echo 'class="disabled"'; } ?>>
					<th scope="row">
						<?php esc_html_e( 'Country Codes:', 'ppm-wp' ); ?>
					</th>
					<td>
						<input type="text" id="login_geo_countries_input" placeholder="e.g. MT"><a href="#" class="button button-primary" id="add-login_denied-countries">Add Country</a><div id="login_geo_countries-countries-userfacing"></div>
						<input type="text" id="login_geo_countries" name="_ppm_options[login_geo_countries]" class="hidden" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->login_geo_countries ) ? rtrim( $ppm->options->ppm_setting->login_geo_countries, '/' ) : '' ); ?>" >
					</td>
				</tr>

				<tr valign="top" <?php if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) { echo 'class="disabled"'; } ?>>
					<th scope="row">
						<?php esc_html_e( 'Action:', 'ppm-wp' ); ?>
					</th>
					<td>
						<select id="login_geo_method" class="regular toggleable" name="_ppm_options[login_geo_method]" style="display: inline-block;">
							<option value="default" <?php selected( 'default', $ppm->options->ppm_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Do nothing', 'ppm-wp' ); ?></option>
							<option value="allow_only" <?php selected( 'allow_only', $ppm->options->ppm_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Allow access from the above countries only', 'ppm-wp' ); ?></option>
							<option value="deny_list" <?php selected( 'deny_list', $ppm->options->ppm_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Block access from the above countries', 'ppm-wp' ); ?></option>
						</select>
					</td>
				</tr>

				<h3><?php esc_html_e( 'What should blocked users see?', 'ppm-wp' ); ?></h3>

				<tr valign="top" <?php if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) { echo 'class="disabled"'; } ?>>
					<th scope="row">
						<?php esc_html_e( 'Blocked user handling:', 'ppm-wp' ); ?>
					</th>
					<td>
						<select id="login_geo_action" class="regular toggleable" name="_ppm_options[login_geo_action]" style="display: inline-block;">
							<option value="deny_to_url" <?php selected( 'deny_to_url', $ppm->options->ppm_setting->login_geo_action, true ); ?>><?php esc_html_e( 'Send blocked users to below URL', 'ppm-wp' ); ?></option>
							<option value="deny_to_home" <?php selected( 'deny_to_home', $ppm->options->ppm_setting->login_geo_action, true ); ?>><?php esc_html_e( 'Send blocked users to homepage', 'ppm-wp' ); ?></option>
						</select>
					</td>
				</tr>

				<tr valign="top" <?php if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) { echo 'class="disabled"'; } ?>>
					<th scope="row">
						<?php esc_html_e( 'Login Blocked Redirect URL', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo trailingslashit( site_url() ); ?></p>
							<input type="text" name="_ppm_options[login_geo_redirect_url]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->login_geo_redirect_url ) ? rtrim( $ppm->options->ppm_setting->login_geo_redirect_url, '/' ) : '' ); ?>" id="ppm-custom_login_url" style="float: left; display: block; width: 250px;" />
							<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
						</fieldset>
					</td>
				</tr>
				<?php
		}

		/**
		 * Manually load the login template where it would not typically wish to load.
		 *
		 * @return void
		 */
		private function load_login_template() {
			global $pagenow;
			$pagenow = 'index.php';
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();
			if ( $_SERVER['REQUEST_URI'] === $this->context_trailingslashit( str_repeat( '-/', 10 ) ) ) {
				$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/wp-login-php/' );
			}
			include_once ABSPATH . WPINC . '/template-loader.php';
			die;
		}

		/**
		 * Simple checker function to determine if trailing slashes are needed based on user permalink setup.
		 *
		 * @return bool
		 */
		private function trailing_slashes_needed() {
			return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
		}

		/**
		 * Wraps or unwraps a slash where needed.
		 *
		 * @param  string $string - String to modify.
		 * @return string $string - Modified string.
		 */
		private function context_trailingslashit( $string ) {
			return $this->trailing_slashes_needed() ? trailingslashit( $string ) : untrailingslashit( $string );
		}

		/**
		 * Handles returning the needed slug for login page access.
		 *
		 * @return string $slug
		 */
		private function custom_login_slug() {
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			$slug        = isset( $ppm_setting['custom_login_url'] ) ? $ppm_setting['custom_login_url'] : '';

			if ( is_multisite() && is_plugin_active_for_network( PPM_WP_BASENAME ) ) {
				return $slug;
			} else {
				return $slug;
			}
		}

		/**
		 * Handles returning the needed login url for login page access.
		 *
		 * @return string $slug
		 */
		public function custom_login_url( $scheme = null ) {
			if ( get_option( 'permalink_structure' ) ) {
				return $this->context_trailingslashit( home_url( '/', $scheme ) . $this->custom_login_slug() );
			} else {
				return home_url( '/', $scheme ) . '?' . $this->custom_login_slug();
			}
		}

		/**
		 * Runs early in a page cycle to check and setup local variables to load the login page if needed.
		 *
		 * @return void
		 */
		public function is_login_check() {
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			global $pagenow;

			if ( ! empty( $ppm_setting['custom_login_url'] ) ) {

				$request = wp_parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );
				if ( ! is_multisite() && ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-signup.php' ) !== false || strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-activate.php' ) !== false ) ) {
					wp_die( __( 'This feature is not enabled.', 'ppm-wp' ) );
				}

				if ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-login.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page    = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php';

				} elseif ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->custom_login_slug(), 'relative' ) ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->custom_login_slug() ] ) && empty( $_GET[ $this->custom_login_slug() ] ) ) ) {
					$pagenow = 'wp-login.php';

				} elseif ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-register.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-register', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page    = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php';
				}
			}

			if ( $pagenow == 'wp-login.php' || $this->is_login_page ) {
				$this->is_geo_check_required = false;
				if ( ( isset( $ppm_setting['login_geo_countries'] ) && ! empty( $ppm_setting['login_geo_countries'] ) ) && ( isset( $ppm_setting['login_geo_method'] ) && 'default' !== $ppm_setting['login_geo_method'] ) ) {
					$this->is_geo_check_required = true;
				}
			}
		}

		/**
		 * Handles the user redirection based on results of what occured in plugins_loaded.
		 *
		 * @return void
		 */
		public function redirect_user() {
			global $pagenow;
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			$request     = wp_parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

			if ( $this->is_geo_check_required ) {
				$is_blocked = self::is_blocked_country( true, true, self::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
				if ( $is_blocked ) {
					if ( 'deny_to_url' == $ppm_setting['login_geo_action'] && ! empty( $ppm_setting['login_geo_redirect_url'] ) ) {
						wp_safe_redirect( '/' . rtrim( $ppm_setting['login_geo_redirect_url'], '/' ) );
					} else {
						wp_safe_redirect( '/' );
					}
					die();
				}
			}

			if ( ! empty( $ppm_setting['custom_login_url'] ) ) {
				if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
					if ( empty( $ppm_setting['custom_login_redirect'] ) || ! $ppm_setting['custom_login_redirect'] ) {
						wp_safe_redirect( '/' );
					} else {
						wp_safe_redirect( '/' . rtrim( $ppm_setting['custom_login_redirect'], '/' ) );
					}
					die();
				}

				if ( $pagenow === 'wp-login.php' && $request['path'] !== $this->context_trailingslashit( $request['path'] ) && get_option( 'permalink_structure' ) ) {
					wp_safe_redirect( $this->context_trailingslashit( $this->custom_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
					die;

				} elseif ( $this->is_login_page ) {
					$referer       = wp_get_referer();
					$referer_parse = wp_parse_url( $referer );

					if ( $referer && strpos( $referer, 'wp-activate.php' ) !== false && $referer_parse && ! empty( $referer['query'] ) ) {
						parse_str( $referer['query'], $referer );
						$result = wpmu_activate_signup( $referer['key'] );
						if ( ! empty( $referer['key'] ) && is_wp_error( $result ) && ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' ) ) {
							wp_safe_redirect( $this->custom_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
							die;
						}
					} else {
						if ( empty( $ppm_setting['custom_login_redirect'] ) || ! $ppm_setting['custom_login_redirect'] ) {
							wp_safe_redirect( '/' );
						} else {
							wp_safe_redirect( '/' . rtrim( $ppm_setting['custom_login_redirect'], '/' ) );
						}
						die();
					}

					$this->load_login_template();

				} elseif ( $pagenow === 'wp-login.php' ) {
					global $error, $interim_login, $action, $user_login;
					@include_once ABSPATH . 'wp-login.php';
					die;
				}
			}
		}

		/**
		 * Update site_url to reflect our slug.
		 *
		 * @param  string $url
		 * @param  string $path
		 * @param  string $scheme
		 * @param  int    $blog_id
		 * @return string - Filtred url.
		 */
		public function login_control_site_url( $url, $path, $scheme, $blog_id ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Update networl_site_url to reflect our slug.
		 *
		 * @param  string $url
		 * @param  string $path
		 * @param  string $scheme
		 * @param  int    $blog_id
		 * @return string - Filtred url.
		 */
		public function login_control_network_site_url( $url, $path, $scheme ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Ensure our custom URL is filtered into wp_redirect
		 *
		 * @param  string $location
		 * @param  int    $status
		 * @return string - Filtered location.
		 */
		public function login_control_wp_redirect( $location, $status ) {
			return $this->login_control_login_url_filter( $location );
		}

		/**
		 * Function to take current URL/location and update it based on if user wishes it to be modified or not.
		 *
		 * @param  string      $url
		 * @param  string|null $scheme
		 * @return string - Updated URL.
		 */
		public function login_control_login_url_filter( $url, $scheme = null ) {
			if ( strpos( $url, 'wp-login.php' ) !== false ) {
				if ( is_ssl() ) {
					$scheme = 'https';
				}
				$args = explode( '?', $url );
				if ( isset( $args[1] ) ) {
					parse_str( $args[1], $args );
					$url = add_query_arg( $args, $this->custom_login_url( $scheme ) );
				} else {
					$url = $this->custom_login_url( $scheme );
				}
			}
			return $url;
		}

		/**
		 * Replace login url with modified value.
		 *
		 * @param  string $value - Original string.
		 * @return string $value - Modified string.
		 */
		public function welcome_email_content( $value ) {
			$ppm_setting  = get_site_option( PPMWP_PREFIX . '_setting' );
			return $value = str_replace( 'wp-login.php', trailingslashit( $ppm_setting['custom_login_url'] ), $value );
		}

		/**
		 * Filters text used within user action request emails and replaced the login slug with our value.
		 *
		 * @param  string $email_text
		 * @param  array  $email_data
		 * @return string $email_text - Modified test.
		 */
		public function user_request_action_email_content( $email_text, $email_data ) {
			$ppm = ppm_wp();
			if ( ! empty( $ppm->options->ppm_setting->custom_login_url ) ) {
				$email_text = str_replace( '###CONFIRM_URL###', esc_url_raw( str_replace( rtrim( $ppm->options->ppm_setting->custom_login_url, '/' ) . '/', 'wp-login.php', $email_data['confirm_url'] ) ), $email_text );
			}

			return $email_text;
		}

		/**
		 * Returns an array of slugs which are reserved, for use with validation to ensure no clashes.
		 *
		 * @return void
		 */
		public function protected_slugs() {
			$wp = new WP();
			return array_merge( $wp->public_query_vars, $wp->private_query_vars );
		}

		/**
		 * Ensure we dont give away the correct url in any context.
		 *
		 * @param $login_url
		 * @param $redirect
		 * @param $force_reauth
		 *
		 * @return string
		 */
		public function login_control_login_url( $login_url, $redirect, $force_reauth ) {
			if ( is_404() ) {
				return '#';
			}

			if ( $force_reauth === false ) {
				return $login_url;
			}

			if ( empty( $redirect ) ) {
				return $login_url;
			}

			$redirect = explode( '?', $redirect );

			if ( isset( $redirect[0] ) && $redirect[0] === admin_url( 'options.php' ) ) {
				$login_url = admin_url();
			}

			return $login_url;
		}

		/**
		 * Check if submission is from a country we wish to allow/block.
		 *
		 * @param bool   $currently_allowed
		 * @param bool   $current_verify
		 * @param string $ip
		 * @return boolean
		 */
		public static function is_blocked_country( $currently_allowed, $current_verify, $ip, $context = 'default' ) {
			$is_spam = false;
			$ppm     = ppm_wp();

			$method           = $ppm->options->ppm_setting->login_geo_method;
			$target_countries = $ppm->options->ppm_setting->login_geo_countries;
			$iplocate_api_key = $ppm->options->ppm_setting->iplocate_api_key;

			if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
				return false;
			}

			if ( empty( $method ) || empty( $target_countries ) || 'default' == $method ) {
				return false;
			}

			$denied_countries = ! empty( $target_countries ) ? explode( ',', $target_countries ) : array();

			$response = wp_safe_remote_get(
				esc_url_raw(
					sprintf(
						'https://www.iplocate.io/api/lookup/%s?apikey=%s',
						self::format_incoming_ip( $ip ),
						$iplocate_api_key
					),
					'https'
				)
			);

			if ( is_wp_error( $response ) ) {
				return ( $currently_allowed ) ? false : true;
			}

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return ( $currently_allowed ) ? false : true;
			}

			$body = (string) wp_remote_retrieve_body( $response );

			$json = json_decode( $body, true );

			// If invalid, pass.
			if ( ! is_array( $json ) ) {
				return ( $currently_allowed ) ? false : true;
			}

			// If empty, country not obtained, pass.
			if ( empty( $json['country_code'] ) ) {
				return false;
			}

			// Uppercase should be passed, but just in case.
			$country = strtoupper( $json['country_code'] );

			// Check length.
			if ( empty( $country ) || strlen( $country ) !== 2 ) {
				return ( $currently_allowed ) ? false : true;
			}

			if ( 'deny_list' === $method || 'deny_to_home' === $method ) {
				if ( in_array( $country, $denied_countries, true ) ) {
					$is_spam = true;
				}
			} elseif ( 'allow_only' === $method ) {
				if ( ! in_array( $country, $denied_countries, true ) ) {
					$is_spam = true;
				}
			}

			return $is_spam;
		}

		/**
		 * Prepare ip for check.
		 *
		 * @param string  $ip
		 * @param boolean $cut_end
		 * @return void
		 */
		private static function prepare_ip( $ip, $cut_end = true ) {
			$separator = ( self::check_ip_format( $ip ) ? '.' : ':' );

			return str_replace(
				( $cut_end ? strrchr( $ip, $separator ) : strstr( $ip, $separator ) ),
				'',
				$ip
			);
		}

		/**
		 * Format incoming IP before check.
		 *
		 * @param string $ip
		 * @return void
		 */
		private static function format_incoming_ip( $ip ) {
			if ( self::check_ip_format( $ip ) ) {
				return self::prepare_ip( $ip ) . '.0';
			}

			return self::prepare_ip( $ip, false ) . ':0:0:0:0:0:0:0';
		}

		/**
		 * Validate IP.
		 *
		 * @param string $ip
		 * @return void
		 */
		private static function check_ip_format( $ip ) {
			if ( function_exists( 'filter_var' ) ) {
				return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
			} else {
				return preg_match( '/^\d{1,3}(\.\d{1,3}){3}$/', $ip );
			}
		}

		public static function sanitize_incoming_ip( $raw_ip ) {

			if ( strpos( $raw_ip, ',' ) !== false ) {
				$ips    = explode( ',', $raw_ip );
				$raw_ip = trim( $ips[0] );
			}
			if ( function_exists( 'filter_var' ) ) {
				return (string) filter_var(
					$raw_ip,
					FILTER_VALIDATE_IP
				);
			}

			return (string) preg_replace(
				'/[^0-9a-f:. ]/si',
				'',
				$raw_ip
			);
		}
	}
}
