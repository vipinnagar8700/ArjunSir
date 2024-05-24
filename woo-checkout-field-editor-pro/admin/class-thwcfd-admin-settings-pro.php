<?php
/**
 * The admin advanced settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.4.4
 *
 * @package    woo-checkout-field-editor-pro
 * @subpackage woo-checkout-field-editor-pro/admin
 */

if(!defined('WPINC')){	die; }

if(!class_exists('THWCFD_Admin_Settings_Pro')):

class THWCFD_Admin_Settings_Pro extends THWCFD_Admin_Settings{
	protected static $_instance = null;
	protected $tabs = '';

	private $settings_fields = NULL;
	private $cell_props = array();
	private $cell_props_CB = array();

	public function __construct() {
		parent::__construct();
		$this->page_id = 'pro';
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function render_page(){
		$this->render_tabs();
		$this->render_content();
	}
	
	private function render_content(){
		$thwcfd_since = get_option('thwcfd_since');
		$now = time();
		$render_time  = apply_filters('thwcfd_get_pro_button_offer', 6 * MONTH_IN_SECONDS);
		$render_time = $thwcfd_since + $render_time;

		if($now > $render_time){
			$url = "https://www.themehigh.com/?edd_action=add_to_cart&download_id=12&cp=lyCDSy&utm_source=free&utm_medium=premium_tab&utm_campaign=wcfe_upgrade_link";
		}else{
			$url = "https://www.themehigh.com/product/woocommerce-checkout-field-editor-pro/?utm_source=free&utm_medium=premium_tab&utm_campaign=wcfe_upgrade_link";
		}

		?>
		<div class="th-nice-box">
			<div class="th-ad-banner">
				<div class="th-ad-content">
					<div class="th-ad-content-container">
						<div class="th-ad-content-desc">
							<p>Unlock away yourself into a dimension of feature loaded checkout experience with Checkout Field Editor pro version.</p>	
						</div>
						<div class="upgrade-pro-btn-div">
							<a class="btn-upgrade-pro" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')">Upgrade to Pro</a>
						</div>	
					</div>
				</div>
				<div class="th-ad-terms">
					<div class="th-ad-guarantee">
						<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/guarantee.svg'); ?>">
					</div>
					<p class="th-ad-term-head"> 30 DAYS RISK-FREE MONEY BACK GUARANTEE
					<span class="th-ad-term-desc">100% refund if you are not satisfied</span></p>
				</div>
			</div>
			<div class="th-wrapper-main">
				<div class="th-try-demo">
					<h3 class="trydemo-heading">The Golden Perks that Come with the Checkout Field Editor Pro</h3>
					<p class="try-demo-desc">The numerous advanced features that accompany the Checkout Field Editor Pro for WooCommerce plugin lets you create an organized and flawless checkout page. Why wait? Go pro and advance your checkout page to the next level now.</p>
					<div class="th-pro-btn"><a class="btn-get-pro" onclick="this.classList.add('clicked')" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" >Get Pro</a><a class="btn-try-demo" href="https://flydemos.com/wcfe/?utm_source=free&utm_medium=banner&utm_campaign=trydemo"
						target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" >Try Demo</a></div>
					<!-- <img class="vedio" src="" alt="no img">  ADD vedio tutorial-->
				</div>
				<section class="th-cfe-key-feature">
					
					<h3 class="th-feautre-head">Key Features of WooCommerce Checkout Field Editor Pro</h3>
				
					<p class="th-feautre-desc">Checkout Field Editor For WooCommerce plugin comes with several advanced features that let you create an organized checkout page. With these premium features, bring your checkout page to its next level</p>
					<div class="th-cfe-feature-list-ul">
						<ul class="th-cfe-feature-list">
							<li>24 custom checkout field types</li>
					        <li>Custom section which can be placed at 15 different positions on the checkout page</li>
					        <li>Display fields conditionally</li>
					        <li>Address autofill suggestion</li>
					        <li>Display sections conditionally</li>
					        <li>Price fields with a set of price types</li>
					        <li>Custom validations</li>
					        <li>Change address display format</li>
					        <li>Display fields based on shipping & payment methods</li>
					        <li class="column-break">Compatibility with other plugins</li>
					        <li>Instant validation of fields</li>
					        <li>Zapier & Integromat support</li>
					        <li>WPML compatibility</li>
					        <li>Repeat fields conditionally</li>
					        <li>Manage field display in emails and order details pages</li>
					        <li>Display custom fields optionally at My Account page</li>
					        <li>Customise, disable or delete default WooCommerce fields</li>
					        <li>Developer friendly with custom hooks</li>
					        <li>Rearrange all fields and sections as per convenience</li>
					        <li>Create your own custom classes for styling the field</li>
					        
				    	</ul>	
					</div>
					<div class="th-get-pro">
						<div class="th-get-pro-img">
							<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/rocket.png'); ?>">
						</div>
						<div class='th-wrapper-get-pro'>
							<div class="th-get-pro-desc">
								<p class="th-get-pro-desc-head">Switch to Pro version and be a part of our limitless features
									<span class="th-get-pro-desc-contnt">Switch to a world of seamless checkout with an ocean of possibilities to customize.</span>
								</p>
							</div>
							<div class="th-get-pro-btn">
								<a class="btn-upgrade-pro" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')" >Upgrade to Pro</a>
							</div>
						</div>
					</div>
				</section>
				<div class="th-star-support">
					<div class="th-user-star">
						<p class="th-user-star-desc">2 Million+ Users & 1000+</p>
						<div class="th-user-star-img">
							<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/star.svg'); ?>">
						</div>
					</div>
					<div class="th-pro-support">
						<div class="th-pro-support-img">
							<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/support.svg'); ?>">
						</div>
						<p class="th-pro-support-desc">Enjoy the <em>Premium Support</em> experience with our dedicated support team.</p>
					</div>
					<div class="th-hor-line"></div>
				</div>
				
				<section class="th-field-types">
					<h3 class="th-field-types-head">Available Field Types</h3>
					<p class="th-field-types-desc">Following are the custom field types available in the Checkout Field Editor plugin.</p>
					<div class="th-cfe-field-type-img">
						<div class="th-fields">
							<ul class="th-cfe-field-list">
								<li>Text</li>
								<li>Hidden</li>
								<li>Password</li>
								<li>Telephone</li>
								<li>Email</li>
								<li>Number</li>
								<li>Textarea</li>
								<li>Select</li>
								<li>Multiselect</li>
								<li>Radio</li>
								<li>Checkbox</li>
								<li>Checkbox Group</li>
								<li>Date Picker <span class="th-crown"><img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
								<li>Datetime local</li>
								<li>Date</li>
								<li>Time Picker <span class="th-crown"><img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
								<li>Time</li>
								<li>Month</li>
								<li>Week <span class="th-new-rec"><p>NEW</p></span></li>
								<li>File Upload <span class="th-new-rec"><p>NEW</p></span><span class="th-crown"><img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
								<li>Heading</li>
								<li>Paragraph</li>
								<li>Label <span class="th-crown"><img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/crown.svg'); ?>"></span></li>
								<li>URL</li>
							</ul>
						</div>
						<div class="th-fields-img">
							<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/fields.png'); ?>">
						</div>
					</div>
				</section>
				<div class="th-fields-section-function">
					<div class="th-section-function">	
						<section class="th-display-rule-section">
							<div class="th-cfe-pro">
								<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/pro.svg'); ?>">
							</div>
							<div class="th-display-rule-section-head">Display Sections Conditionally</div>
							<p class="th-display-rule-section-desc">Display the custom sections on your checkout page based on the condition you set. Following are the positions where these checkout sections can be displayed:</p>
							<ul class="th-display-section-list">
								<li>Before customer details</li>
								<li>After customer details</li>
								<li>Before billing form</li>
								<li>After billing form</li>
								<li>Before shipping form</li>
								<li>After shipping form</li>
								<li>Before registration form</li>
								<li>After registration form</li>
								<li>Before order notes</li>
								<li>After order notes</li>
								<li>Before terms and conditions</li>
								<li>After terms and conditions</li>
								<li>Before submit button</li>
								<li>After submit button</li>
								<li>Inside a custom step created using WooCommerce Multistep Checkout</li>
							</ul>
							<div class="display-section-img">
								<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/display-section.png'); ?>">
							</div>
						</section>
					</div>
					<div class="th-fields-function">
						<section class="th-display-rule-fields">
							<div class="th-cfe-pro">
								<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/pro.svg'); ?>">
							</div>
							<h3 class="th-display-rule-fields-head">Display Fields Conditionally</h3>
							<p class="th-display-rule-fields-desc">Display the custom and default checkout fields based on the conditions you provide. Conditions on which field can be displayed are:</p>
							<div class="th-dispaly-rule-list">
								<ul class="th-display-field-list">
									<li>Cart Content</li>
									<li>Cart Subtotal</li>
									<li>Cart Total</li>
									<li>User Roles</li>
									<li>Product</li>
									<li>Product Variation</li>
									<li>Product Type</li>
									<li>Product Category & Tag</li>
									<li>Shipping Class</li>
									<li>Shipping Weight</li>
									<li>Based on the other field values</li>
									<li>Based on Shipping & Payment Methods</li>
								</ul>
							</div>
						</section>

						<section class="th-price-fields">
							<div class="th-cfe-pro">
								<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/pro.svg'); ?>">
							</div>
							<h3 class="th-price-fields-head">Add price fields and choose the price type </h3>
							<p class="th-price-fields-desc">With the premium version of the Checkout Page Editor Plugin, add an extra price value to the total price by creating a field with price into the checkout form. The available price types that can be added to WooCommerce checkout fields are:</p>
							<div class="th-price-field-list">
								<ul class="th-price-list">
									<li>Fixed Price</li>
									<li>Custom Price</li>
									<li>Percentage of Cart Total</li>
									<li>Percentage of Subtotal</li>
									<li>Percentage of Subtotal excluding tax</li>
									<li>Dynamic Price</li>
								</ul>
							</div>
						</section>
					</div>
				</div>
				<div class="th-review-section">
					<div class="review-image-section">
						<div class="review-quote-img">
							<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/reviewquotes.png'); ?>">
						</div>
					</div>
					<div id="indicator" class="th-review-navigator" style="text-align:center">
						<a class="prev" onclick='plusSlides(-1)'></a>
	  					<a class="next" onclick='plusSlides(1)'></a>
						<span class="dot th-review-nav-btn" onclick="currentSlide(1)"></span>
						<span class="dot th-review-nav-btn" onclick="currentSlide(2)"></span>
						<span class="dot th-review-nav-btn" onclick="currentSlide(3)"></span>
						<span class="dot th-review-nav-btn" onclick="currentSlide(4)"></span>
						<span class="dot th-review-nav-btn" onclick="currentSlide(5)"></span>
					</div>
					<div class="th-user-review-section">
						<div class="th-review-quote">
						<img src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/quotes.svg'); ?>">
						</div>
						<div class="th-user-review">
							<h3 class="th-review-heading">Great plugin, even better support (free & pro versions)</h3>
							<p class="th-review-content">I used the free version of this plugin for a while until I needed some of the pro features. It was great as a free plugin and even better as a paid/pro version. On top of that, the support for the pro version is out-of-this-world good! Anuram on the support team went above and beyond. I heartily recommend upgrading to the pro version if it has features you’d like to use, as it is very well worth the price paid!</p>
							<p class="th-review-user-name">Eric Kuznacic</p>
						</div>
					</div>
				</div>
				<section class="th-faq-tab">
					<div class="th-faq-desc">
						<h3>FAQ's</h3>
						<p class="th-faq-para">Don't worry! Here are the answer to your frequent doubt and questions. If you feel you haven't been answered relevantly, feel free to contact our efficient support team.</p>
	                
					</div>
					<div class="th-faq-qstns" >
		                <button class="accordion" onclick="thwcfdAccordionexpand(this)">
		                	<div class="accordion-qstn">
		                		<p>How to upgrade to the premium version of the plugin and how can I apply the license key to activate the pro plugin?</p>
		                		<img class="accordion-img" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion.svg'); ?>">
		                		<img class="accordion-img-opn" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion-open.svg'); ?>">
		                	</div>
		                	<div class="panel">
		                		<p>Please follow the steps given in the below links to purchase the plugin and activate the license.</p>
		                		<p>
		                			<a href="https://www.themehigh.com/docs/download-and-install-your-plugin/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/download-and-install-your-plugin/</a><br>
		                		</p>
		                		<p class="th-faq-links">
		                			<a href="https://www.themehigh.com/docs/manage-license/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/docs/manage-license/</a><br>
		                		</p>
		                		<p class="th-faq-notes">
		                			Note: Please confirm whether all the fields that you had created in the free version have been migrated to the premium version after upgrading. If so you can safely deactivate and delete the free version from your site.
		                		</p>
			                </div>
		                </button>	                
		                <button class="accordion" onclick="thwcfdAccordionexpand(this)">
		                	<div class="accordion-qstn">
		                		<p>Do I have to keep both the free version and the pro version after buying the pro version?</p>
		                		<img class="accordion-img" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion.svg'); ?>">
		                		<img class="accordion-img-opn" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion-open.svg'); ?>">
		                	</div>
		                	<div class="panel">
			                	<p class="th-faq-answer">Please note that free and premium versions are different plugins entirely. So, you can deactivate and remove the free version of the plugin from your website, if you start using the premium version.</p>
			                </div>
		                </button>
		                
		                <button class="accordion" onclick="thwcfdAccordionexpand(this)">
		                	<div class="accordion-qstn">
		                		<p>How to migrate our configuration from the free version to the pro version?</p>
		                		<img class="accordion-img" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion.svg'); ?>">
		                		<img class="accordion-img-opn" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion-open.svg'); ?>">
		                	</div>
		                	<div class="panel">
			                	<p class="th-faq-answer">At the time when you upgrade the plugin from the free to the premium version, the free plugin settings will get automatically migrated to the premium version.
	 
								Please confirm whether all the fields that you created in the free version have been migrated to the premium version after upgrading. If so you can safely deactivate and delete the free version from your site.</p>
			                </div>
		                </button>
		                <button class="accordion" onclick="thwcfdAccordionexpand(this)">
		                	<div class="accordion-qstn">
		                		<p>Will I get a refund if the pro plugin doesn't meet my requirements?</p>
		                		<img class="accordion-img" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion.svg'); ?>">
		                		<img class="accordion-img-opn" src="<?php echo esc_url(THWCFD_URL .'admin/assets/css/accordion-open.svg'); ?>">
		                	</div>
		                	
		                	<div class="panel">
			                	<p>Please note that as per our refund policy, we will provide a refund within one month from the date of purchase, if you are not satisfied with the product. Please refer to the below link for more details:
			                	</p>
			                	<p class="th-faq-answer">
			                		<a href="https://www.themehigh.com/refund-policy/" target="_blank" rel="noopener noreferrer">https://www.themehigh.com/refund-policy/</a><br>
			                	</p>
			                </div>
		                </button>
		                
		            </div>

		    	</section>
				<section class="switch-to-pro-tab">
					<div class="th-switch-to-pro">
						<h3 class="switch-to-pro-heading">Switch to Pro version and be a part of our limitless features</h3>
						<p>Switch to pro and unlock access to few of the most sought after features in the checkout page and experience one of a kind convenience like never before.</p>
						<!-- <div class="th-button-get-pro-link"> -->
							<a class="button-get-pro" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" onclick="this.classList.add('clicked')">Get Pro</a>	
						<!-- </div> -->
						
					</div>
				</section>
			</div>
		</div>
		<?php
	}

}

endif;