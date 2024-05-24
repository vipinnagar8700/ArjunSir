<?php
/**
 * Help area sidebar.
 *
 * @package WordPress
 * @subpackage wpassword
 */

?>

<div class="our-wordpress-plugins side-bar">
	<h3><?php esc_html_e( 'Our WordPress Plugins', 'ppm-wp' ); ?></h3>
	<ul>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/wp-security-audit-log-img.jpeg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Keep a log of users and under the hood site activity.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins', 
									'utm_medium'   => 'link', 
									'utm_campaign' => 'mls',
								),
								'https://www.melapress.com/wordpress-activity-log/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/wp-2fa.jpeg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Add an extra layer of security to your login pages with 2FA & require your users to use it.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins', 
									'utm_medium'   => 'link', 
									'utm_campaign' => 'mls',
								),
								'https://www.melapress.com/wordpress-2fa/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/c4wp.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Protect website forms & login pages from spambots & automated attacks.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins', 
									'utm_medium'   => 'link', 
									'utm_campaign' => 'mls',
								),
								'https://www.melapress.com/wordpress-captcha/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
	</ul>
</div>
