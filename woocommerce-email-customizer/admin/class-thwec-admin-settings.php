<?php
/**
 * The admin settings page specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      1.0.0
 *
 * @package    woocommerce-email-customizer-pro
 * @subpackage woocommerce-email-customizer-pro/admin
 */
if(!defined('WPINC')){ die; }

if(!class_exists('THWEC_Admin_Settings')):

abstract class THWEC_Admin_Settings {
	protected $page_id = '';	
	public static $section_id = '';
	
	protected $tabs = '';
	protected $sections = '';
	
	public function __construct($page, $section = '') {
		$this->page_id = $page;
		if($section){
			self::$section_id = $section;
		}else{
			self::set_first_section_as_current();
		}
		$this->tabs = array( 'general_settings' => 'General Settings','template_settings'=>'Add/Edit Templates','license_settings' => 'Plugin License');
	}
	
	public function get_tabs(){
		return $this->tabs;
	}

	public function get_current_tab(){
		return $this->page_id;
	}
	
	public function get_sections(){
		return $this->sections;
	}
	
	public function get_current_section(){
		return isset( $_GET['section'] ) ? esc_attr( $_GET['section'] ) : self::$section_id;
	}
	
	public static function set_current_section($section_id){
		if($section_id){
			self::$section_id = $section_id;
		}
	}
	
	public static function set_first_section_as_current(){
		$sections = false; //THWEC_Admin_Utils::get_sections();
		if($sections && is_array($sections)){
			$array_keys = array_keys( $sections );
			if($array_keys && is_array($array_keys) && isset($array_keys[0])){
				self::set_current_section($array_keys[0]);
			}
		}
	}

	public function render_tabs(){
		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		if(empty($tabs)){
			return;
		}
		
		echo '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach( $tabs as $id => $label ){
			$active = ( $current_tab == $id ) ? 'nav-tab-active' : '';
			$label = THWEC_i18n::t($label);
			echo '<a class="nav-tab '.$active.'" href="'. $this->get_admin_url($id) .'">'.$label.'</a>';
		}
		echo '</h2>';		
	}
	
	public function render_sections() {
		$current_section = $this->get_current_section();
		$sections = $this->get_sections();

		if(empty($sections)){
			return;
		}
		
		$array_keys = array_keys( $sections );
		$section_html = '';
		
		foreach( $sections as $id => $label ){
			$label = THWEC_i18n::t($label);
			$url   = $this->get_admin_url($this->page_id, sanitize_title($id));	
			$section_html .= '<li><a href="'. $url .'" class="'.($current_section == $id ? 'current' : '').'">'.$label.'</a> '.(end($array_keys) == $id ? '' : '|').' </li>';
		}	
		
		if($section_html){
			echo '<ul class="thpladmin-sections">';
			echo $section_html;	
			echo '</ul>';
		}
	} 
	
	public function get_admin_url($tab = false, $section = false){
		$url = 'admin.php?page=th_email_customizer_pro';
		if($tab && !empty($tab)){
			$url .= '&tab='. $tab;
		}
		if($section && !empty($section)){
			$url .= '&section='. $section;
		}
		return admin_url($url);
	}

	public function get_template_manage_url( $action=false ){
		$url = 'admin.php?page=th_email_customizer_templates';
		if( $action && !empty( $action ) ){
			$url .= '&action='.$action;
		}
		return admin_url($url);
	}
	
	/*************************************************
	******* Form field render functions - START ******
	*************************************************/
	public function render_form_field_element($field, $atts = array(), $render_cell = true){
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'label_cell_props' => '',
				'input_cell_props' => '',
				'label_cell_colspan' => '',
				'input_cell_colspan' => '',
			), $atts );
			$ftype     = isset($field['type']) ? $field['type'] : 'text';
			$flabel    = isset($field['label']) && !empty($field['label']) ? THWEC_i18n::t($field['label']) : '';
			$sub_label = isset($field['sub_label']) && !empty($field['sub_label']) ? THWEC_i18n::t($field['sub_label']) : '';
			$tooltip   = isset($field['hint_text']) && !empty($field['hint_text']) ? THWEC_i18n::t($field['hint_text']) : '';
			$template_error = isset($field['template_error']) && !empty($field['template_error']) ? $field['template_error'] : '';
			$field_html = '';
			$additional_data = '';
			if($ftype == 'text'){
				$field_html = $this->render_form_field_element_inputtext($field, $atts);
			}
			else if($ftype == 'hidden'){
				$field_html = $this->render_form_field_element_hidden($field,$atts);

			}else if($ftype == 'textarea'){
				$field_html = $this->render_form_field_element_textarea($field, $atts);
				   
			}else if($ftype == 'select'){
				$field_html = $this->render_form_field_element_select($field, $atts);  
				
			}else if($ftype == 'opt-select'){
				$field_html = $this->render_form_field_element_opt_select($field, $atts);     
			}else if($ftype == 'multiselect'){
				$field_html = $this->render_form_field_element_multiselect($field, $atts);
				
			}else if($ftype == 'colorpicker'){
				$field_html = $this->render_form_field_element_colorpicker($field, $atts);
				$additional_data .= 'class="thwec-color-picker-wrapper"';              
            
			}else if($ftype == 'twoside'){
				$field_html = $this->render_form_field_element_twoside($field, $atts);              
			}else if($ftype == 'fourside'){
				$field_html = $this->render_form_field_element_fourside($field, $atts);              
			}else if($ftype == 'alignment-icons'){
				$field_html = $this->render_form_field_element_alignment_icon($field, $atts);                          
			}else if($ftype == 'checkbox'){
				$field_html = $this->render_form_field_element_checkbox($field, $atts, $render_cell);   

			}else if($ftype == 'radio'){
				$field_html = $this->render_form_field_element_radio($field, $atts, $render_cell);

			}else if($ftype == 'number'){
				$field_html = $this->render_form_field_element_number($field, $atts); 
				  
			}
			if($render_cell && $render_cell !== 'template-map'){
				$color_picker_class="thwec-color-picker-wrapper";
				$required_html = isset($field['required']) && $field['required'] ? '<abbr class="required" title="required">*</abbr>' : '';
				$label_cell_props = !empty($args['label_cell_props']) ? $args['label_cell_props'] : '';
				$input_cell_props = !empty($args['input_cell_props']) ? $args['input_cell_props'] : '';
				echo '<tr class="thwec-input-spacer"><td></td></tr>';
				if($flabel){
				?>
				<tr>
				<td <?php echo $label_cell_props ?> >
					<?php echo $flabel; echo $required_html; 
					if($sub_label){
						?>
						<br/><span class="thpladmin-subtitle"><?php echo $sub_label; ?></span>
						<?php 
					}
					?>
				</td>
				</tr>
				<?php 
				}
				?>
				<tr><td <?php echo $additional_data; ?> ><?php echo $field_html;?></td>
				</tr>
				<?php
				if($template_error){
					echo '<tr><td><span class="thwec-missing-template-warning">Template not found</span></td></tr>';
				}
			}else if($render_cell=='template-map'){
				$color_picker_class="thwec-color-picker-wrapper";
				$required_html = isset($field['required']) && $field['required'] ? '<abbr class="required" title="required">*</abbr>' : '';
				$label_cell_props = !empty($args['label_cell_props']) ? $args['label_cell_props'] : '';
				$input_cell_props = !empty($args['input_cell_props']) ? $args['input_cell_props'] : '';
				?>
				<tr>
				<td <?php echo $label_cell_props ?> colspan="2">
					<div class="template-map-single-row thwec-map-toggle-active">
						<div class="thwec-template-map-title">
							<h4><?php echo $flabel; ?></h4>
							<span class="dashicons dashicons-arrow-down-alt2"></span>	
							</div>
						<div class="thwec-template-map-content"><?php echo $field_html;?></div> 
					</div>
				</td>
				</tr>
				<?php
			}
			else{
				echo $field_html;

			}
		}
	}
	
	private function prepare_form_field_props($field, $atts = array()){
		$field_props = '';
		$args = shortcode_atts( array(
			'input_width' => '',
			'input_height' => '',
			'input_name_prefix' => 'i_',
			'input_name_suffix' => '',
			'input_margin' => '',
			'input_b_r' => '',
			'input_font_size' => '',
		), $atts );
		
		$ftype = isset($field['type']) ? $field['type'] : 'text';
		
		if($ftype == 'multiselect'){
			$args['input_name_suffix'] = $args['input_name_suffix'].'[]';
		}
		$fname  = $args['input_name_prefix'].$field['name'].$args['input_name_suffix'];
		$fvalue = isset($field['value']) ? $field['value'] : '';
		$input_width  = $args['input_width'] ? 'width:'.$args['input_width'].';' : '';
		$input_height  = $args['input_height'] ? 'height:'.$args['input_height'].';' : '';
		$input_margin  = $args['input_margin'] ? 'margin:'.$args['input_margin'].';' : '';
		$input_b_r = $args['input_b_r'] ? 'border-radius:'.$args['input_b_r'].';' : '';
		$input_font_size = $args['input_font_size'] ? 'font-size:'.$args['input_font_size'].';' : '';
		$field_props  = 'name="'. $fname .'" style="'. $input_width.$input_height.$input_b_r.$input_margin.$input_font_size .'"';
		if($ftype !== 'select'){
			$field_props  .= 'value="'. $fvalue .'"';
		}
		$field_props .= ( isset($field['placeholder']) && !empty($field['placeholder']) ) ? ' placeholder="'.$field['placeholder'].'"' : '';
		$field_props .= ( isset($field['class']) && !empty($field['class']) ) ? ' class="'.$field['class'].'"' : '';
		$field_props .= ( isset($field['onchange']) && !empty($field['onchange']) ) ? ' onchange="'.$field['onchange'].'"' : '';
		return $field_props;
	}
	
	private function render_form_field_element_inputtext($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$fclass = isset($field['class']) ? $field['class'] : '';
			$field_html = '<input type="text" class="'.$fclass.'" '. $field_props .' />';
		}
		return $field_html;
	}
	
	private function render_form_field_element_hidden($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="hidden" '. $field_props .' />';
		}
		return $field_html;
	}

	private function render_form_field_element_textarea($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'rows' => '4',
				'cols' => '100',
			), $atts );
		
			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $atts);
			$field_html = '<textarea '. $field_props .' rows="'.$args['rows'].'" cols="'.$args['cols'].'" >'.$fvalue.'</textarea>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_select($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $value => $label){
				// $selected = $value === $fvalue ? 'selected' : '';
				$selected = $value == $fvalue ? 'selected' : '';
				$field_html .= '<option value="'. trim($value) .'" '.$selected.'>'. THWEC_i18n::t($label) .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}

	private function render_form_field_element_opt_select($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$fvalue = isset($field['value']) ? $field['value'] : '';
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select '. $field_props .' >';
			foreach($field['options'] as $key => $sub_arr){
				if( $key == 'user' || $key == 'default_woocommerce' ){
					$field_html .= '<optgroup label="'.ucwords( str_replace('_', ' ', $key ) ).' Templates">';
					if( is_array( $sub_arr ) && !empty( $sub_arr ) ){
						foreach ($sub_arr as $value => $label) {
							$field_html .= '<option value="'. trim($value) .'">'. THWEC_i18n::t($label) .'</option>';
						}
					}else{
						$field_html .= '<option value=""> -- empty -- </option>';
					}
					$field_html .= '</optgroup>';
				}else{
					$field_html .= '<option value="'. trim($key) .'">'. THWEC_i18n::t($sub_arr) .'</option>';
				}
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_multiselect($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html = '<select multiple="multiple" '. $field_props .' class="thpladmin-enhanced-multi-select" >';
			foreach($field['options'] as $value => $label){
				$field_html .= '<option value="'. trim($value) .'" >'. THWEC_i18n::t($label) .'</option>';
			}
			$field_html .= '</select>';
		}
		return $field_html;
	}
	
	private function render_form_field_element_radio($field, $atts = array(), $render_cell = true){
		$field_html = '';
		$args = shortcode_atts( array(
			'label_props' => '',
			'cell_props'  => 3,
			'render_input_cell' => false,
			'render_label_cell' => false,
			), $atts );

		$atts = array(
		'input_width' => 'auto',
		);

		if($field && is_array($field)){
			$fvalue = isset($field['value']) ? $field['value'] : '';	
			$fclass = isset($field['class']) && !empty($field['class']) ? 'r_f'. $field['class'] : '';
			$onchange = isset($field['onchange']) && !empty($field['onchange']) ? ' onchange="'.$field['onchange'].'"' : '';

			foreach ($field['options'] as $value => $label) {
				$checked ='';
				$flabel = isset($label['name']) && !empty($label['name']) ? __($label['name'],'') : '';
				$checked = $value === $fvalue ? 'checked' : '';
				$selected = $value === $fvalue ? 'rad-selected' : '';	
				$field_html .= '<input type="radio" name="i_' . $field['name'] . '" class="'.$fclass.'"value="'. trim($value) .'" ' . $checked . $onchange . '/>'.$label;
			}
		}	
		return $field_html;
	}


	private function render_form_field_element_checkbox($field, $atts = array(), $render_cell = true){
		$field_html = '';
		if($field && is_array($field)){
			$args = shortcode_atts( array(
				'label_props' => '',
				'cell_props'  => 3,
				'render_input_cell' => false,
			), $atts );
		
			$fid 	= 'a_f'. $field['name'];
			$fclass = isset($field['class']) && !empty($field['class']) ? 'c_f'. $field['class'] : '';
			$flabel = isset($field['label']) && !empty($field['label']) ? THWEC_i18n::t($field['label']) : '';
			
			$field_props  = $this->prepare_form_field_props($field, $atts);
			$field_props .= $field['checked'] ? ' checked' : '';
			$field_html  = '<input type="checkbox" id="'. $fid .'" class="'.$fclass.'" '.$field_props .' />';
			$field_html .= '<label for="'. $fid .'" '. $args['label_props'] .' > '. $flabel .'</label>';
		}
		if(!$render_cell && $args['render_input_cell']){
			return '<td '. $args['cell_props'] .' >'. $field_html .'</td>';
		}else{
			return $field_html;
		}
	}
	
	private function render_form_field_element_number($field, $atts = array(), $render_cell = true){
		$field_html = '';
		if($field && is_array($field)){

			$flabel = isset($field['label']) && !empty($field['label']) ? THWEC_i18n::t($field['label']) : '';
			$fmin = isset($field['min']) ? THWEC_i18n::t($field['min']) : '';			
			$fmax = isset($field['max']) && !empty($field['max']) ? THWEC_i18n::t($field['max']) : '';			
			$fstep = isset($field['step']) && !empty($field['step']) ? THWEC_i18n::t($field['step']) : '';		
			// $field_props .= 'min="'.$fmin.'" max="'.$fmax.'"';
			$field_props  = 'min="'.$fmin.'" max="'.$fmax.'" step="'.$fstep.'"';
			$field_props .= $this->prepare_form_field_props($field, $atts);
			$field_html = '<input type="number" '. $field_props .' />';
		}
		return $field_html;
	}
	

	private function render_form_field_element_colorpicker($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$atts = array('input_width' => '105px','input_height'=>'30px','input_font_size' => '13px');

			$field_props = $this->prepare_form_field_props($field, $atts);
			
			$field_html  = '<span class="thpladmin-colorpickpreview '.$field['name'].'_preview" style=""></span>';
            $field_html .= '<input type="text" '. $field_props .' class="thpladmin-colorpick" size="8" autocomplete="off"/>';
		}
		return $field_html;
	}

	private function render_form_field_element_twoside($field, $atts = array()){
				$field_html = '';
		if($field && is_array($field)){
			$fclass  = isset($field['class']) ? $field['class'] : '';
			$fclass .= ' size-input-group';

			$atts_width = array('input_name_suffix' => '_width', 'input_margin' => '0 6px 0 0', 'input_width'=>'136px', 'input_height'=>'30px', 'input_b_r' => '4px', 'input_font_size' => '13px');
			$atts_height = array('input_name_suffix' => '_height','input_width'=>'136px', 'input_height'=>'30px', 'input_b_r' => '4px', 'input_font_size' => '13px');
			
			$field_props_width = $this->prepare_form_field_props($field, $atts_width);
			$field_props_height = $this->prepare_form_field_props($field, $atts_height);

			$field_html  = '<input type="text" placeholder="Width" class="'.$fclass.'" '. $field_props_width .' />';
			$field_html .= '<input type="text" placeholder="Height" class="'.$fclass.'" '. $field_props_height .' />';
		}
		return $field_html;
	}

	private function render_form_field_element_fourside($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$fclass  = isset($field['class']) ? $field['class'] : '';
			$fclass .= ' input-group';

			$atts_top = array('input_name_suffix' => '_top', 'input_margin' => '0 6px 0 0','input_width'=>'65px','input_height'=>'30px','input_b_r' => '4px', 'input_font_size' => '13px');
			$atts_right = array('input_name_suffix' => '_right', 'input_margin' => '0 6px 0 0','input_width'=>'65px','input_height'=>'30px','input_b_r' => '4px', 'input_font_size' => '13px');
			$atts_bottom = array('input_name_suffix' => '_bottom', 'input_margin' => '0 6px 0 0','input_width'=>'65px','input_height'=>'30px','input_b_r' => '4px', 'input_font_size' => '13px');
			$atts_left = array('input_name_suffix' => '_left','input_width'=>'65px','input_height'=>'30px','input_b_r' => '4px', 'input_font_size' => '13px');

			$field_props_top = $this->prepare_form_field_props($field, $atts_top);
			$field_props_right = $this->prepare_form_field_props($field, $atts_right);
			$field_props_bottom = $this->prepare_form_field_props($field, $atts_bottom);
			$field_props_left = $this->prepare_form_field_props($field, $atts_left);

			$field_html  = '<input type="text" placeholder="Top" class="'.$fclass.'" '. $field_props_top .' />';
			$field_html .= '<input type="text" placeholder="Right" class="'.$fclass.'" '. $field_props_right .' />';
			$field_html .= '<input type="text" placeholder="Bottom" class="'.$fclass.'" '. $field_props_bottom .' />';
			$field_html .= '<input type="text" placeholder="Left" class="'.$fclass.'" '. $field_props_left .' />';
		}
		return $field_html;
	}

	public function render_form_field_element_alignment_icon($field, $atts = array()){
		$field_html = '';
		if($field && is_array($field)){
			$field_props = $this->prepare_form_field_props($field, $atts);
			$fclass = isset($field['class']) ? $field['class'] : '';
			$field_html = '<div class="thwec-aligment-icon-wrapper">';
			$field_html .= '<div class="img-wrapper" data-align="left" style="margin-right:6px;"><img src='.THWEC_ASSETS_URL_ADMIN.'images/align-left.svg alt="left"></div>';
			$field_html .= '<div class="img-wrapper" data-align="center" style="margin-right:6px;"><img src='.THWEC_ASSETS_URL_ADMIN.'images/align-center.svg alt="center"></div>';
			$field_html .= '<div class="img-wrapper" data-align="right" style="margin-right:6px;"><img src='.THWEC_ASSETS_URL_ADMIN.'images/align-right.svg alt="right"></div>';
			if(isset($field['icon_flag']) && $field['icon_flag']){
				$field_html .= '<div class="img-wrapper" data-align="justify"><img src='.THWEC_ASSETS_URL_ADMIN.'images/align-justify.svg alt="justify"></div>';
			}
			$field_html .= '<br><input type="hidden" class="'.$fclass.'" '. $field_props .' />';
			$field_html .= '</div>';
		}
		return $field_html;
	}
	
	public function render_form_element_tooltip($tooltip){
        $tooltip_html = '';
        
        if($tooltip){
            $tooltip_html = '<a href="javascript:void(0)" title="'. $tooltip .'" class="thpladmin_tooltip"><span class="dashicons 
dashicons-editor-help"></span></a>';
        }
        ?>
        <td style="width: 26px; padding:0px;"><?php echo $tooltip_html; ?></td>
        <?php
    }
	
	public function render_form_fragment_h_separator($atts = array(),$icon=false){
		$args = shortcode_atts( array(
			'colspan' 	     => '',
			'padding-top'    => '5px',
			'padding-bottom' => '',
			'border-style'   => 'dashed',
    		'border-width'   => '1px',
			'border-color'   => '#e6e6e6',
			'content'	     => '',
			'class'			 => 'thwec-seperator-heading',
			'padding'  		 => '8px 0px 6px 5px',
			'font-size'  	 => '13px',
			'additional'	 => '',
		), $atts );
		
		$style  = $args['padding-bottom'] ? 'padding-bottom:'.$args['padding-bottom'].';' : '';
		$style .= $args['padding'] ? 'padding:'.$args['padding'].';' : '';
		$style .= $args['border-style'] ? ' border-bottom:'.$args['border-width'].' '.$args['border-style'].' '.$args['border-color'].';' : '';
		
		?>
		<tr class="thwec-spacer"><td></td></tr>
        <tr><td colspan="<?php echo $args['colspan']; ?>" style="<?php echo $style; ?>" class="<?php echo $args['class']; ?>"><?php echo $args['content']; 
        if($icon){
        	echo '<span class="dashicons dashicons-arrow-down-alt2 direction-arrow"></span>';
        }
        if( isset( $args['additional'] ) && $args['additional'] == 'button' ){
        	echo '<button type="button" name="additional_css" class="button button-primary thwec-seperator-button" onclick="thwecSaveAdditionalCss(this)">Save CSS</button>';
        }
        ?>	
        </td></tr>
        <tr class="thwec-spacer"><td></td></tr>
        <?php
	}
	
	public function render_field_form_fragment_h_spacing($padding = 5){
		$style = $padding ? 'padding-top:'.$padding.'px;' : '';
		?>
        <tr><td colspan="6" style="<?php echo $style ?>"></td></tr>
        <?php
	}

	public function render_form_element_empty_cell(){
        ?>
        <td width="13%">&nbsp;</td>
        <?php $this->render_form_element_tooltip(false); ?>
        <td width="34%">&nbsp;</td>
        <?php
    }
	
	public function render_form_field_blank($colspan = 3){
		?>
        <td colspan="<?php echo $colspan; ?>">&nbsp;</td>  
        <?php
	}
	
	public function render_form_section_separator($props, $atts=array()){
		?>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:10px;"></td></tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" class="thpladmin-form-section-title" ><?php echo $props['title']; ?>
			<?php 
			if(isset($props['dashicons'])){
				?>

				<a href="javascript:void(0)" title="<?php echo isset($props['dashicon-title']) ? $props['dashicon-title'] : ''; ?>" class="thwec_admin_dashicon_tooltip thpladmin_tooltip"><span class="dashicons <?php echo $props["dashicons"]?> thwec-seperator-dashicons"></span></a>
			<?php } ?>
			</td>
		</tr>
		<tr valign="top"><td colspan="<?php echo $props['colspan']; ?>" style="height:0px;"></td></tr>
		<?php

	}
	/***********************************************
	******* Form field render functions - END ******
	***********************************************/
}

endif;