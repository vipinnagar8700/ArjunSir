<?php
/**
 * The admin general settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEC_Admin_Settings_Templates')):

class THWEC_Admin_Settings_Templates extends THWEC_Admin_Settings {
	protected static $_instance = null;
	
	private $cell_props_L = array();
	private $cell_props_R = array();
	private $cell_props_CB = array();
	private $cell_props_CBS = array();
	private $cell_props_CBL = array();
	private $cell_props_CP = array();
	private $cell_props_S  = array();
	private $cell_props_RB = array();
	private $section_props = array();
	private $db_settings = array();
	private $template_list = array();
	private $user_templates = array();
	private $map_msgs = array();
	private $template_status = array();
	private $template_map = array();
	private $wpml_map = array();
	private $subject_placeholders = array();
	private $filtered_templates = array();
	private $link_tabs = array();
	private $admin_email_status = array();
	private $email_descriptions = array();

	public function __construct() {
		parent::__construct('template_settings', '');
		$this->init_constants();
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function init_constants(){
		$this->cell_props = array( 
			'label_cell_props' => 'width="18%"', 
			'input_width' => '350px',  
		);
		$this->cell_props_L = array( 
			'label_cell_props' => 'width="25%"', 
			'input_cell_props' => 'width="34%"', 
			'input_width' => '350px',  
		);
		
		$this->cell_props_R = array( 
			'label_cell_props' => 'width="13%"', 
			'input_cell_props' => 'width="34%"', 
			'input_width' => '250px', 
		);

		$this->cell_props_CB = array( 
			'label_cell_props' => 'width="3%"', 
			'input_cell_props' => 'width="3%"', 
		);

		$this->map_msgs = array(
			true	=> array(
				'msg' 	=> 	array(
					'save'		=>	'Settings Saved',
					'reset'		=>	'Template Settings Successfully Reset',
					'delete'	=>	'Template Successfully Deleted',
					'delete'	=>	'Template Successfully Deleted',
				),
				'class'		=>	'thwec-save-success',
			),
			false	=> array(
				'msg' 	=> 	array(
					'save'				=>	'Your changes were not saved due to an error (or you made none!).',
					'reset'				=>	'Reset not done due to an error (or nothing to reset!).',
					'delete'			=>	'An error occured or (or template file doesn\'t exist!).',
					'template-missing'	=>  'Your changes were not saved due to missing template files'
				),
				'class'		=>	'thwec-save-error',
			),
		);

		$this->admin_email_status = array(
			'admin-new-order',
			'admin-cancelled-order',
			'admin-failed-order'
		);

		$this->template_status =array(
			'0'=>'admin-new-order',
			'1'=>'admin-cancelled-order',
			'2'=>'admin-failed-order',
			'3'=>'customer-completed-order',
			'4'=>'customer-on-hold-order',
			'5'=>'customer-processing-order',
			'6'=>'customer-refunded-order',
			'7'=>'customer-invoice',
			'8'=>'customer-note',
			'9'=>'customer-reset-password',
			'10'=>'customer-new-account',
		);

		$this->subject_placeholders = array(
			'{customer_name}',
			'{customer_full_name}',
			'{site_name}', 
			'{order_id}'			,
			'{order_created_date}'	,
			'{order_completed_date}',
			'{order_total}',
			'{order_formatted_total}',
			'{billing_first_name}',
			'{billing_last_name}',
			'{billing_last_name}',
			'{billing_company}',
			'{billing_country}',
			'{billing_address_1}',
			'{billing_address_2}',
			'{billing_city}',
			'{billing_state}',
			'{billing_postcode}',
			'{billing_phone}',
			'{billing_email}',
			'{shipping_first_name}',
			'{shipping_last_name}',
			'{shipping_company}',
			'{shipping_country}',
			'{shipping_address_1}',
			'{shipping_address_2}',
			'{shipping_city}',
			'{shipping_state}',
			'{shipping_postcode}',
			'{payment_method}'
		);

		$this->link_tabs = array( 'custom' => 'Custom Templates', 'sample' => 'Sample Templates' );

		$this->email_descriptions = array(
			'admin_new_order' => 'An email sent to the admin when a new order is received/paid for',
			'admin_cancelled_order' => 'An email sent to the admin when an order is cancelled',
			'admin_failed_order' => 'An email sent to the admin when payment fails to go through',
			'customer_completed_order' => 'An email sent to the customer when the order is marked complete and usual indicates that the order has been shipped',
			'customer_on_hold_order' => 'An email sent to the customer when a new order is on-hold for',
			'customer_processing_order' => 'An email sent to the customer when a new order is paid for',
			'customer_refund_order' => 'An email sent to the customer when the order is marked refunded',
			'customer_invoice' => 'An email sent to the customer via admin',
			'customer_note' => 'An email sent when you add a note to an order',
			'reset_password' => 'An email sent to the customer when they reset their password',
			'new_account' => 'An email sent to the customer when they create an account',
		);

	}

	public function is_default_lang_template( $template_name ){
		$lang_code = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_default_language', NULL ), true );
		return str_replace( '-'.$lang_code, '', $template_name );
	}

	public function is_wpml_template( $template, $skip_default=false ){
		return is_array( $this->wpml_map ) && array_key_exists( $template, $this->wpml_map );
	}

	public function prepare_template_edit_select_box( $t_list ){
		$wpml_templates = array();
		$select_box = array('' => 'Default Template');
		$user = array();
		if( isset( $t_list['user']  ) && is_array( $t_list['user'] ) ){
			foreach ($t_list['user'] as $tkey => $tdata) {
				if( $this->is_wpml_template( $tkey ) ){
					if( in_array( $tdata['display_name'], $wpml_templates ) ){
						continue;
					}
					array_push( $wpml_templates, $tdata['display_name'] );
					$tkey = isset( $t_list['base'] ) ? $tdata['base'] : ( isset( $tdata['lang'] ) ? str_replace( '-'.$tdata['lang'] , '', $tkey ) : '' );
				}
				$user[$tkey] = $tdata['display_name'];
			}
		}
		if( $user ){
			$select_box = $select_box+$user;
		}
		return $select_box;
	}

	public function render_page(){
		$this->render_content();
	}

	private function render_content(){
		$map_result = 'onload';
		$map_action = false;
		if( isset( $_POST['save_map'] ) ){
			$save_status = $this->save_settings();
			if(isset($save_status['error'])){
				$map_result = false;
				$map_action = 'template-missing';
			}else{
				$map_result = $save_status;
				$map_action = 'save';
			}
		}
		else if( isset($_POST['reset_map'] ) ){
			$map_result = $this->reset_to_default();
			$map_action = 'reset';
		}
		else if(isset($_POST['delete_template'])){
			$map_result = $this->delete_template();
			$map_action = 'delete';
		}
		if($map_result !== 'onload' && $map_action){
			$class = isset($this->map_msgs[$map_result]['class']) ? $this->map_msgs[$map_result]['class'] : '';
			$msg = isset($this->map_msgs[$map_result]['msg'][$map_action]) ? $this->map_msgs[$map_result]['msg'][$map_action] : '';
			?>	
			<div id="thwec_temp_map_save_messages" class="thwec-show-save <?php echo $class; ?>">
				<?php 
				echo $msg;
				?>
			</div>
			<script type="text/javascript">
				jQuery(function($) {
				    setTimeout(function(){
						$("#thwec_temp_map_save_messages").remove();
					}, 2000);
				});
			</script>
			<?php
		}

		$this->init_helpers();
		$this->render_tabs();
		$this->render_manage_templates( true );
		$this->render_template_mapping();
    }

    public function check_for_missing_dependencies(){
    	$changed = false;
    	if( isset( $this->db_settings[THWEC_Utils::get_template_samples_key()] ) && empty( $this->db_settings[THWEC_Utils::get_template_samples_key()] ) ){
    		$this->db_settings[THWEC_Utils::get_template_samples_key()] = THWEC_Utils::get_sample_settings();
    		$changed = true;
    	}
    	if( isset( $this->db_settings[THWEC_Utils::get_template_subject_key()] ) && empty( $this->db_settings[THWEC_Utils::get_template_subject_key()] ) ){
    		$this->db_settings[THWEC_Utils::get_template_subject_key()] = THWEC_Utils::email_subjects();
    		$changed = true;
    	}

    	if( $changed ){
    		THWEC_Utils::save_template_settings($this->db_settings);
    		$this->db_settings = THWEC_Utils::get_template_settings();
    	}
    }

    public function init_helpers(){
    	$this->db_settings = THWEC_Utils::get_template_settings();
    	$this->check_for_missing_dependencies();
    	$this->email_subjects = isset( $this->db_settings['email_subject'] ) && !empty( $this->db_settings['email_subject'] ) ? $this->db_settings['email_subject'] : false;
    	$this->template_map = THWEC_Utils::get_template_map( $this->db_settings );			
    	$this->wpml_map = THWEC_Utils::get_wpml_map( $this->db_settings );
		$this->template_list = THWEC_Utils::get_template_list($this->db_settings, true);
		$this->user_templates = isset( $this->template_list['user'] ) && !empty( $this->template_list['user'] ) ? $this->template_list['user'] : false;
		$user_templates = $this->prepare_template_edit_select_box( $this->template_list );
		$this->map_template_form_props = array(
			'section_map_templates' 	=> array(
				'title'=>'Email Notification Mapping', 'type'=>'separator', 'colspan'=>'2','sub_label'=>'Choose the templates for woocommerce order notifications from the list'
			),
			'template-list'				=> array(
				'type'=>'select', 'name'=>'template-list[]', 'label'=>'', 'value'=>'','class'=>'thwec-template-map-select2','options'=>$user_templates
			),
			'template-subject'			=> array(
				'type'=>'textarea', 'name'=>'template-subject[]', 'label'=>'', 'value'=>'','class'=>''
			),
		);

		$this->edit_url = $this->get_admin_url();
    }

    public function render_tabs(){
    	?>
    	<div id="thwec_template_manager">
    		<?php $this->render_table(); ?>
    	</div>
    	<?php
    }

    public function render_table(){
		$notif_tab = isset( $_POST['thwec_notification_tab'] ) && !empty( $_POST['thwec_notification_tab'] ) ? sanitize_key( $_POST['thwec_notification_tab'] ) : 'manage';
		$manage_class = $map_class = 'thwec-template-manage-tabs';
	
		if( $notif_tab == 'mapping' ){
			$map_class .= ' thwec-template-manage-active';
		}else{
			$manage_class .= ' thwec-template-manage-active';
		}
		?>
        <table id="email_template_manager_table" cellspacing="0">
			<thead>
				<tr>
					<th class="<?php echo $manage_class; ?>" data-name="manage">
						<?php echo __( 'Manage Template', 'woocommerce-email-customizer-pro' ); ?>
					</th>
					<th class="<?php echo $map_class; ?>" data-name="mapping">
						<?php echo __( 'Template Mapping', 'woocommerce-email-customizer-pro' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				if( $notif_tab == 'manage' ){
					$this->render_manage_templates( false );
				}else{
					$this->render_template_mapping( false );
				}
				?>
			</tbody>
		</table>
    	<?php
	}

	public function render_template_manage_header(){
		$url = $this->get_template_manage_url();
		?>
		<tr class="thwec-template-manager-header-links">
			<td colspan="2">
				<h3 class="thwec-template-title">Templates</h3>
				<a type="button" href="<?php echo $this->get_admin_url(); ?>" class="button thwec-templates-title-action thwec-add-new">Add New</a>
			</td>
		</tr>
		<?php
	}

	public function render_template_manage_sub_header( $link_tab ){
		?>
		<tr class="thwec-template-manager-header-links">
			<td colspan="2">
				<?php
				$url = $this->get_template_manage_url();
				
				$counter = 1;
				foreach ( $this->link_tabs as $key => $value ) {
					$url = $url.'&section='.$key.'';
					$active = ( $link_tab == $key ) ? ' link-tab-active' : '';
					echo '<a class="thwec-link-tab'.$active.'" href="'.$url.'">'.$value.'</a>';
					end($this->link_tabs);
    				echo $key === key($this->link_tabs) ? '' : ' | ';
				}
				?>
			</td>
		</tr>
		<?php
		$subtitle = $this->render_list_title( $link_tab, true );
		if( $subtitle ){
			echo '<tr><td colspan="2"><i class="thwec-template-map-subtitle">'.$subtitle.'</i></td></tr>';
		}
	}

	public function render_manage_templates( $render = true ){
		$link_tab = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'custom';
		if( $render ){
			echo '<table id="thwec_manage_template_table" style="display:none;"><tbody>';
		}
		$this->render_template_manage_header();
		$this->render_template_manage_sub_header( $link_tab );
		$this->prepare_templates_list( $link_tab );

		if( $render ){
			echo '</tbody></table>';
		}
	}

	public function prepare_templates_list( $link_tab ){
		$builder_url = $this->get_admin_url();
		$link_tab = $link_tab == 'custom' ? 'user' : $link_tab;
		$list = $this->get_link_tab_templates( $link_tab );
		?>
		<tr>
			<td colspan="2">
				<table class="wc_emails widefat thpladmin-form-email-notification-table">
					<thead>
						<tr>
							<th class="thwec-template-column-name">Name</td>
							<th class="thwec-template-column-assigned">
								<?php echo $link_tab == 'sample' ? 'Description' : 'Assigned To'; ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						if( $link_tab == 'user' || $link_tab == 'sample' ){
							if( $link_tab == 'user' && empty( $list ) ){
								?>
								<tr>
									<td colspan="3" class="thwec-template-manage-columns">
										<i>You have not created any templates till now. <a href="<?php echo $builder_url; ?>">Create a template</a></i>
									</td>
								</tr>
								<?php
								return;
							}

							if( $link_tab == 'user' && THWEC_Utils::is_wpml_active() && apply_filters('thwec_wpml_template_list_filter', true ) ){
								$list = $this->format_template_list($list);
							}

							if( THWEC_Utils::is_not_empty( $list, 'array' ) ){
								$this->render_templates_list( $list, $link_tab );
							}
						}else{
							if( THWEC_Utils::is_not_empty( $list, 'array' ) ){
								foreach ($list as $key => $value) {
									$this->render_list_title( $key );

									if( $key == 'user' && empty( $value ) ){
										?>
										<tr>
											<td colspan="3" class="thwec-template-manage-columns">
												<i>You have not created any templates till now. <a href="<?php echo $builder_url; ?>">Create a template</a></i>
											</td>
										</tr>
										<?php
									}
									if( $key == 'user' && THWEC_Utils::is_wpml_active() && apply_filters('thwec_wpml_template_list_filter', true ) ){
										$value = $this->format_template_list($value);
									}
									foreach ($value as $tkey => $tvalue) {
										$this->render_settings( $key, $tkey, $tvalue, false, '' );
									}
								}
							}
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	public function render_templates_list( $list, $key ){
		foreach ($list as $tkey => $tvalue) {
			$this->render_settings( $key, $tkey, $tvalue, false, '' );
		}
	}

	public function render_list_title( $key, $link_title=false ){

		$key = $key == 'custom' ? 'user' : $key;
		$user_sublabel = 'Custom templates are templates created by you. ';
		$sample_sublabel = 'You can use Sample email templates to reduce the effort of starting from scratch.';
		
		if( $link_title ){
			return $key == 'user' ? $user_sublabel.$sample_sublabel : $sample_sublabel;
		}else if( $key == 'user' ){
			$this->render_settings( $key, '', 'User Templates', true, $user_sublabel );
		
		}else if($key == 'sample'){
			$this->render_settings( $key, '', 'Sample Templates', true, $sample_sublabel );
		
		}

	}

	public function get_link_tab_templates( $link_tab ){
		return isset( $this->template_list[$link_tab] ) && !empty( $this->template_list[$link_tab] ) ? $this->template_list[$link_tab] : array();
	}

	public function prepare_template_name( $basename, $list ){
		$langs = icl_get_languages();
		if( is_array( $langs ) ){
			foreach ( $langs as $language => $object ) {
				$key = $basename.'-'.strtolower($object['default_locale']);
				if( isset( $list[$key] ) && isset( $list[$key]['display_name'] ) ){
					return array( 'status' => 'missing', 'display_name' => $list[$key]['display_name'] );
				}
			}
		}
	}

	public function format_template_list( $list ){
		$nvalue = array();
		$wpml_list = array();
		$def_lang = THWEC_Utils::get_wpml_locale( apply_filters( 'wpml_default_language', NULL ), true );
		foreach ($list as $index => $data) {
			if( isset($data['lang']) && array_key_exists( $index, $this->wpml_map ) ){
				// Lang templates
				$base_template = $this->wpml_map[$index];
				if( !in_array( $base_template, $wpml_list ) ){
					array_push( $wpml_list, $base_template );
					$template = $base_template.'-'.$def_lang;
					$nvalue[$template] = isset( $list[$template] ) ? $list[$template] : $this->prepare_template_name( $base_template, $list ) ;
				}
			}else{
				//Other non wpml templates
				$nvalue[$index] = $data;
			}
		}
		return $nvalue;
	}

	public function render_template_mapping( $render = true ){
		if( $render ){
			echo '<table id="thwec_map_template_table" style="display:none;"><tbody>';
		}
		?>
		<tr>
			<td colspan="2">
				<i class="thwec-template-map-subtitle">You can assign the custom templates to the corresponding WooCommerce transaction emails here.</i>
				<form name="template_map_form" action="" method="POST">
					<input type="hidden" name="thwec_notification_tab" value="mapping">
					<table class="wc_emails widefat thpladmin-form-email-notification-table">
						<thead>
							<th>Name</th>
							<th class="thwec-map-form-toggle-set">
								<button type="button" class="thwec-open-all" onclick="thwecMapFormToggle(this)">Expand all</button>
							</th>
						</thead>
						<?php
						foreach ( THWEC_Utils::email_statuses() as $email_key => $email_label ) {
						?>
							<tr>
								<td colspan="2" class="thwec-mapping-wrapper">
									<div class="template-map-single-row">
										<div class="thwec-template-map-title">
											<h4><?php echo $email_label; ?></h4>
											<span class="dashicons dashicons-arrow-right-alt2"></span>	
										</div>
										<div class="thwec-template-map-content">
											<?php
									    	foreach ($this->map_template_form_props as $key => $field) {
									    		
									    		if($key !=='section_map_templates'){
													?>
													<div class="thwec-template-map-fields">
														<?php
														if( $field['name'] == 'template-subject[]' ){
															$field['value'] = $this->get_email_subject( $email_key);
														}else{
															$field['value'] = isset( $this->template_map[$email_key] ) ? $this->template_map[$email_key] : '';
														}
														$label = $this->get_map_form_field_label( $field );
														echo '<div class="thwec-template-map-labels">'.$label.'</div>';
														$this->render_form_field_element($field, $this->cell_props_L, false, false);  
														?> 
													</div>
													<?php
												}
									    	}
											?>
										</div>
									</div>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="2">
								<div class="thwec-template-map-footer thwec-map-form-actions">
									<button type="submit" class="button btn button-primary" name="save_map">Save</button>
									<button type="submit" class="button btn" name="reset_map">Reset</button>
								</div>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		<?php
		if( $render ){
			echo '</tbody></table>';
		}
	}


	public function render_settings( $key, $i_name, $template, $heading, $sublabel ){
		if( $heading ){
			echo '<tr><th colspan="2" class="thwec-template-manage-columns">';
			echo '<h4>'.$template.'</h4>';
			echo '<i>'.$sublabel.'</i>';
			echo '</th></tr>';
			return;
		}

		$label = isset( $template['display_name'] ) ? $template['display_name'] : '';

		$edit_button_label = 'Edit';
		$missing_template = false;
		if( isset( $template['status'] ) && $template['status'] == 'missing' ){
			$label .= '<span class="thwec-template-list-warning">The template translation is not created for current site language. Create one now';
			$edit_button_label = 'Create Translation';
			$missing_template = true;
		}
		if( $key == 'user' && ( !THWEC_Utils::is_wpml_active() || !apply_filters('thwec_wpml_template_list_filter', true ) ) ){
			if( $key == 'user' && isset( $template['lang'] ) && !empty( $template['lang'] ) ){
				$label = $label.'['.strtoupper( $template['lang'] ).']';
			}
		}
		?>
		<tr>
			<td class="thwec-template-manage-columns thwec-template-column-name">
				<?php 
				echo '<p class="thwec-template-single-title">'.$label.'</p>'; 
				$this->template_action_links( $key, $i_name, $template, $missing_template, $edit_button_label  );
				?>		
			</td>
			<td class="thwec-template-manage-columns thwec-template-column-assigned thwec-template-column-<?php echo $key; ?>">
				<?php 
				if( $key == 'user' ){
					echo $this->get_assigned_to_status( $i_name, $template ); 
				}else{
					echo $this->get_sample_template_description( $i_name );
				}
				?>
			</td>
		</tr>
		<?php
	}

	public function get_sample_template_description( $id ){
		return isset( $this->email_descriptions[$id] ) ? $this->email_descriptions[$id] : '';
	}

	public function template_action_links( $key, $i_name, $template, $missing_template, $edit_button_label ){
		?>
		<form name="thwec_edit_template_form_<?php echo $i_name; ?>" action="" method="POST">
			<input type="hidden" name="i_template_type" value="<?php echo $key; ?>">
			<input type="hidden" name="i_template_name" value="<?php echo $i_name; ?>">
			<button type="submit" class="thwec-template-action-links" formaction="<?php echo $this->edit_url ?>" name="edit_template"><?php echo $edit_button_label; ?></button>
			<?php if( $key == 'user' && !$missing_template ){ ?>
				| <button type="submit" class="thwec-template-action-links" data-lang="<?php echo isset($template['lang']) ? $template['lang'] : '';?>" name="delete_template" onclick="thwecDeleteTemplate(this)">Delete</button>
			<?php } ?>
		</form>
		<?php
	}

	public function get_assigned_to_status( $key, $template ){
		$email_status = array();
		$wpml_template = isset( $template['lang'] ) ? $template['lang'] : false;
		$key = $wpml_template ? str_replace( '-'.$wpml_template, '', $key ) : $key;
		if( in_array( $key, $this->template_map ) ){
			$status = array_keys( $this->template_map, $key );
			if( is_array( $status ) ){
				foreach ($status as $skey => $svalue) {
					if( array_key_exists( $svalue, THWEC_Utils::email_statuses() )  ){
						array_push( $email_status, THWEC_Utils::email_statuses()[$svalue] );
					}
				}
			}
			
		}
		return !empty( $email_status ) ? implode(', ' , $email_status) : '--';
	}

	public function get_map_form_field_label( $field ){
		return $field['type'] == 'select' ? 'Choose from saved templates' : 'Edit email subject';
	}

	public function get_email_subject( $status ){
		$subject = '[{site_title}]: You have a new message';
		$defaults = THWEC_Utils::email_subjects();
		if( isset( $this->email_subjects[$status] ) && !empty( $this->email_subjects[$status] ) ){
			$subject = $this->email_subjects[$status];

		}else if( isset( $defaults[$status] ) && !empty( $defaults[$status] ) ){
			$subject = $defaults[$status];

		}
		return $subject;
	}

	private function delete_file( $template_name ){
		$file = THWEC_CUSTOM_TEMPLATE_PATH.$template_name.'.php';
		if(is_file($file)){
			unlink($file); // delete file		  	
		}
	}

	private function delete_settings(){

	}

	private function delete_db_data( $template_name, $templates, $wpml_map, $settings ){
		if( isset( $templates[$template_name] ) ){
			if( $wpml_map && is_array( $wpml_map ) && array_key_exists( $template_name, $wpml_map ) ){
				//Check if wpml template
				if( apply_filters('thwec_wpml_template_list_filter', true ) ){
				// Delete all translation of a template at once
					$keys = array_keys( $wpml_map, $wpml_map[$template_name] );
					if( is_array( $keys ) ){
						foreach ( $keys as $index => $translated ) {
							$this->delete_file( $translated );
							unset( $wpml_map[$translated] );
							if( isset( $templates[$translated] ) ){
								unset( $templates[$translated]);
							}
						}
					}
				}else{
					// Delete Single WPML template
					$this->delete_file( $template_name );
					unset($templates[$template_name]);
					unset( $wpml_map[$template_name] );
				}
				$settings[THWEC_Utils::wpml_map_key()] = $wpml_map;
			}else{
				// Non WPML template
				$this->delete_file( $template_name );
				unset($templates[$template_name]);
			}
			$settings[THWEC_Utils::SETTINGS_KEY_TEMPLATE_LIST] = $templates;
		}
		return $settings;
		
	}

	private function delete_template(){
		$result = false;
		$template_name = isset( $_POST['i_template_name'] ) ? sanitize_text_field( $_POST['i_template_name'] ) : false ;
		if( $template_name ){
			$settings = THWEC_Utils::get_template_settings();
			$templates = $settings[THWEC_Utils::SETTINGS_KEY_TEMPLATE_LIST];
			$wpml_map = isset( $settings[THWEC_Utils::wpml_map_key()] ) ? $settings[THWEC_Utils::wpml_map_key()] : false;
			$settings = $this->delete_db_data( $template_name, $templates, $wpml_map, $settings );
			$result = THWEC_Utils::save_template_settings($settings);	
		}
		return $result;
	}

	

	private function save_settings(){
		$temp_data = array();
		$settings = $this->prepare_settings($_POST);
		$result = THWEC_Utils::save_template_settings($settings);
		return $result;
		
	}

	private function prepare_settings($posted){
		$settings = THWEC_Utils::get_template_settings();
		$template_map = $settings[THWEC_Utils::SETTINGS_KEY_TEMPLATE_MAP];
		$template_subject = $this->prepare_template_subjects( $settings );
		$file_ext = 'php';
		$def_subjects = THWEC_Utils::email_subjects();
		foreach ($this->template_status as $key => $value) {
			$template_map[$this->template_status[$key]] = isset( $_POST['i_template-list'][$key] ) ? sanitize_text_field( $_POST['i_template-list'][$key] ) : '';
			
			$subject = isset( $_POST['i_template-subject'][$key] ) ? sanitize_text_field( $_POST['i_template-subject'][$key] ) : '';
			if( empty( $subject ) ){
				$subject = isset( $def_subjects[$value] ) ? $def_subjects[$value] : '[{site_title}]: You have a new message';
			}
			$template_subject[$this->template_status[$key]] = $subject;
		}
		$this->create_subject_translations( $template_subject );
		
		$settings[THWEC_Utils::SETTINGS_KEY_TEMPLATE_MAP] 	= $template_map;
		$settings[THWEC_Utils::SETTINGS_KEY_SUBJECT_MAP] 	= $template_subject;
		return $settings;
	}

	private function create_subject_translations( $subjects ){
		if( is_array( $subjects ) ){
			foreach ($subjects as $id => $subject) {
				if( !in_array( $id, $this->admin_email_status ) ){
					$subject = $this->clean_subjects_for_translation( $subject );
					THWEC_Admin_Utils::wpml_register_string( $id, $subject);
				}
			}
		}
	}

	private function clean_subjects_for_translation( $subject ){
		foreach ($this->subject_placeholders as $index => $placeholder ) {
			$subject = str_replace( $placeholder, '%s', $subject);
		}
		return $subject;
	}

	private function prepare_template_subjects( $settings ){
		$subjects = array();
		if( isset( $settings[THWEC_Utils::SETTINGS_KEY_SUBJECT_MAP] ) &&  !empty( $settings[THWEC_Utils::SETTINGS_KEY_SUBJECT_MAP] ) ){
			$subjects = $settings[THWEC_Utils::SETTINGS_KEY_SUBJECT_MAP];
		}
		return $subjects;
	}

	public function reset_to_default() {
		$delete_opt = false;
		$delete_opt = THWEC_Utils::delete_settings();
		return $delete_opt;
	}

	public function get_thwec_db_settings(){
		//Path
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'].'/thwec_templates';
	    $dir = trailingslashit($dir);
		$path =  $dir.'thwec-db-settings-local.php';
		//Content
		$content = json_encode(get_option('thwec_template_settings'));
		$file = fopen($path, "w") or die("Unable to open file!");
		if(false !== $file){
			fwrite($file, $content);
			fclose($file);
		}
	}

}

endif;