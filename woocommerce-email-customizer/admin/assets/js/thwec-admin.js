var thwec_base = (function($, window, document) {
	'use strict';
	
	function escapeHTML(html) {
	   var fn = function(tag) {
		   var charsToReplace = {
			   '"': '&quot;',
			   "'": '&#39;',
		   };
		   return charsToReplace[tag] || tag;
	   }
	   return html.replace(/[&<>"]/g, fn);
	}

	function removeHTML(html){
		return html.replace(/[&<>"]/g, '');
	}


	function decodeHTML(html) {
	   
	   return html.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
	}
	 	 
	function isHtmlIdValid(id) {
		var re = /^[a-z\_]+[a-z0-9\_]*$/;
		return re.test(id.trim());
	}

	function isCssValid( property, value ){
		var div = $("<div>");
		var _old = div.css(property);
		div.css(property,value);
		var _new = div.css(property);
		return _old!=_new;
	}
	
	function isValidHexColor(value) {      
		if ( preg_match( '/^#[a-f0-9]{6}$/i', value ) ) { // if user insert a HEX color with #     
			return true;
		}     
		return false;
	}
	
	function setup_tiptip_tooltips(){
		var tiptip_args = {
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		};

		$('.tips').tipTip( tiptip_args );
	}
	
	function setup_enhanced_multi_select(parent){
		parent.find('select.thwec-enhanced-multi-select').each(function(){
			if(!$(this).hasClass('enhanced')){
				$(this).select2({
					minimumResultsForSearch: 10,
					allowClear : true,
					placeholder: $(this).data('placeholder')
				}).addClass('enhanced');
			}
		});
	}
	
	function setup_enhanced_multi_select_with_value(parent){
		parent.find('select.thwec-enhanced-multi-select').each(function(){
			if(!$(this).hasClass('enhanced')){
				$(this).select2({
					minimumResultsForSearch: 10,
					allowClear : true,
					placeholder: $(this).data('placeholder')
				}).addClass('enhanced');
				
				var value = $(this).data('value');
				value = value.split(",");
				
				$(this).val(value);
				$(this).trigger('change');
			}
		});
	}
	
	function setup_color_picker(form){
		form.find('.thpladmin-colorpick').iris({
			change: function( event, ui ) {
				var input = $(this);
				input.parent().find( '.thpladmin-colorpickpreview' ).css({ backgroundColor: ui.color.toString() });
				input.val(ui.color.toString());
				input.trigger('keyup');
			},
			hide: true,
			border: true
		}).click( function() {
			$('.iris-picker').hide();
			$(this ).closest('td').find('.iris-picker').show();

		});
	
		$('body').click( function() {
			$('.iris-picker').hide();
		});
	
		$('.thpladmin-colorpick').click( function( event ) {
			event.stopPropagation();
		});
	}
	
	function setup_color_pick_preview(form){
		form.find('.thpladmin-colorpick').each(function(){
			$(this).parent().find('.thpladmin-colorpickpreview').css({ backgroundColor: this.value });
		});
	}
	
	function setup_popup_tabs(form, selector_prefix){
		$("."+selector_prefix+"-tabs-menu a").click(function(event) {
			event.preventDefault();
			$(this).parent().addClass("current");
			$(this).parent().siblings().removeClass("current");
			var tab = $(this).attr("href");
			$("."+selector_prefix+"-tab-content").not(tab).css("display", "none");
			$(tab).fadeIn();
		});
	}
	
	function prepare_field_order_indexes(elm) {
		$(elm+" tbody tr").each(function(index, el){
			$('input.f_order', el).val( parseInt( $(el).index(elm+" tbody tr") ) );
		});
	}
	
	function setup_sortable_table(parent, elm, left){
		parent.find(elm+" tbody").sortable({
			items:'tr',
			cursor:'move',
			axis:'y',
			handle: 'td.sort',
			scrollSensitivity:40,
			helper:function(e,ui){
				ui.children().each(function(){
					$(this).width($(this).width());
				});
				ui.css('left', left);
				return ui;
			}		
		});	
		
		$(elm+" tbody").on("sortstart", function( event, ui ){
			ui.item.css('background-color','#f6f6f6');										
		});
		$(elm+" tbody").on("sortstop", function( event, ui ){
			ui.item.removeAttr('style');
			prepare_field_order_indexes(elm);
		});
	}
	
	function get_property_field_value(field){
		var value = '';
		var type = '';
		var name = '';
		switch(type) {
			case 'select':
			case 'textarea':
			default:
				value = field.val();
				value = value == null ? '' : value;
				break;
				
			case 'checkbox':
				value = field.prop('checked');
				value = value ? 1 : 0;
				break;
		}	
		
		return value;
	}

	function get_form_field_value( field, type ){
		var value = '';
        switch(type) {
            case 'select':
            case 'textarea':
            default:
                value = field.val();
                value = value == null ? '' : value;
                break;
            case 'checkbox':
                value = field.prop('checked');
                value = value ? 1 : 0;
                break;
        }    
        
        return value;
	}

	function get_property_field_value(form, type, name){
        var value = '';
        switch(type) {
            case 'select':
                value = form.find("select[name=i_"+name+"]").val();
                value = value == null ? '' : value;
                break;
            case 'checkbox':
                value = form.find("input[name=i_"+name+"]").prop('checked');
                value = value ? 1 : 0;
                break;
            
            case 'textarea':
                value = form.find("textarea[name=i_"+name+"]").val();
                value = value == null ? '' : value;
                break;
            default:
                value = form.find("input[name=i_"+name+"]").val();
                value = value == null ? '' : value;
        }    
        
        return value;
    }
	
	function set_property_field_value(form, type, name, value, multiple){
		switch(type) {
			case 'select':
				if(multiple == 1 && typeof(value) === 'string'){
					value = value.split(",");
					name = name+"[]";
				}
				form.find('select[name="i_'+name+'"]').val(value);
				break;
				
			case 'checkbox':
				value = value == 1 ? true : false;
				form.find("input[name=i_"+name+"]").prop('checked', value);
				break;
				
			case 'textarea':
				form.find("textarea[name=i_"+name+"]").val(value);
				break;
				
			default:
				form.find("input[name=i_"+name+"]").val(value);
		}	
	}
	
	function convert_rgb2hex(rgb){
 		rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
 		return (rgb && rgb.length === 4) ? "#" +
  		("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
  		("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
  		("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
	}

	return {
		escapeHTML : escapeHTML,
		decodeHTML : decodeHTML,
		removeHTML : removeHTML,
		isHtmlIdValid : isHtmlIdValid,
		isCssValid : isCssValid,
		isValidHexColor : isValidHexColor,
		setup_tiptip_tooltips : setup_tiptip_tooltips,
		setupEnhancedMultiSelect : setup_enhanced_multi_select,
		setupEnhancedMultiSelectWithValue : setup_enhanced_multi_select_with_value,
		setupColorPicker : setup_color_picker,
		setup_color_pick_preview : setup_color_pick_preview,
		setupSortableTable : setup_sortable_table,
		setupPopupTabs : setup_popup_tabs,
		get_form_field_value  : get_form_field_value,
		get_property_field_value : get_property_field_value,
		set_property_field_value : set_property_field_value,
   	};
}(window.jQuery, window, document));

/* Common Functions */
function thwecSetupEnhancedMultiSelectWithValue(elm){
	thwec_base.setupEnhancedMultiSelectWithValue(elm);
}

function thwecSetupSortableTable(parent, elm, left){
	thwec_base.setupSortableTable(parent, elm, left);
}

function thwecSetupPopupTabs(parent, elm, left){
	thwec_base.setupPopupTabs(parent, elm, left);
}

function thwecOpenFormTab(elm, tab_id, form_type){
	thwec_base.openFormTab(elm, tab_id, form_type);
}
function thwec_setup_color_picker(form){
	thwec_base.setupColorPicker(form);
}
var thwec_tbuilder = (function($, window, document) {
    'use strict';
    var WECM_CM = false;
    var SIDEBAR_HELPER = new Array('configure', 'layouts', 'layout-element', 'settings');
    var LAYOUT_BLOCKS = new Array('one_column', 'two_column', 'three_column', 'four_column', 'left-large-column', 'right-large-column', 'gallery-column');
    var LAYOUT_COLUMNS = new Array('one_column_one', 'two_column_one', 'two_column_two', 'three_column_one', 'three_column_two', 'three_column_three', 'four_column_one', 'four_column_two', 'four_column_three', 'four_column_four','column_clone');
    var HOOK_FEATURES = new Array('email_header','email_order_details','before_order_table','after_order_table','order_meta','customer_details','email_footer','downloadable_product');
    var ADDRESS_BLOCKS = new Array('billing_address', 'shipping_address', 'customer_address'); 
    var IMG_CSS = new Array('upload_bg_url','background');
    var SB_VALIDATION_REQ_BLOCKS = new Array('custom_hook');
    var COLUMN_NUMBER = {'one_column':1, 'two_column':2, 'three_column':3, 'four_column':4};
    var NUMBER_TO_WORDS = {1:'one',2:'two',3:'three',4:'four'};
    var preview_wrapper;
    var FILE_TYPES = new Array('image/jpeg', 'image/png', 'image/jpg');
    var DRAGG_CLASS;    
    var TB_DEFAULT = '{"b_t":"0px","b_r":"0px","b_b":"0px","b_l":"0px","border_style":"none","border_color":"","bg_color":"#f6f6f6","upload_bg_url":"","bg_position":"","bg_size":"","bg_repeat":"repeat"}';
    var LAYOUT_OBJ={};
    var confirm_flag = true;
    var confirm_clone_column_flag = true;
    var BLANK_TD_DATA = '<span class="builder-add-btn btn-add-element">+ Add Element</span>';
    var BASIC_PROPS = {"width":"50%","p_t":"10px","p_r":"10px","p_b":"10px","p_l":"10px","b_t":"1px","b_r":"1px","b_b":"1px","b_l":"1px","border_style":"dotted","border_color":"#dddddd","upload_bg_url":"","bg_color":"none","bg_position":"","bg_size":"","bg_repeat":"repeat","text_align":"center"};

    function initialize_tbuilder(){ 
        preview_wrapper = $('#thwec_tbuilder_editor_preview');
        setup_sidebar_builder_clicks();
        setup_confirmation_alerts();
        setup_template_builder();
        body_resize_sidebar_scroll();
        
        $('.thwec-tbuilder-elm-wrapper').on('click', '.column-name', function(event) {
            $(this).closest('.columns').find('.element-set').toggle();

        });
        $('.thwec-tbuilder-elm-wrapper').on('click', '.row-name', function(event) {
            $(this).closest('.rows').find('.column-set').toggle();
            $(this).closest('.rows').toggleClass('rows-collpase');
        });

        $('.thwec-tbuilder-header-panel').on("blur","input",function(){
            if($(this).val() != ""){
                $(this).addClass("has-value");
            }else{
                $(this).removeClass("has-value");
            }
        });

        $('#thwec-sidebar-settings').on('keyup', 'form :input', function( event ){
            save_sidebar_form_onchange( $(this), event );
        });
        $('#thwec-sidebar-settings').on('change', 'select', function( event ){
            save_sidebar_form_onchange( $(this), event );
        });
        $('#thwec-sidebar-settings').on('change', 'input[name="i_checkbox_option_image"]', function( event ){
            save_sidebar_form_onchange( $(this), event );
        });
        
    }

    function remove_builder_panel_highlights(){
        $('#thwec-sidebar-configure .thwec-builder-elm-layers').find('.thwec-panel-highlight').removeClass('thwec-panel-highlight');
        $('#tb_temp_builder').find('.thwec-builder-highlight').removeClass('thwec-builder-highlight');
    }

    function input_event_change_trigger( value, name, id ){
        switch( name ){
            case 'text':
                $('#tb_'+id).find('.thwec-block-text-holder').html(value);
                break;
            case 'button':
                $('#tb_'+id).find('.thwec-button-link').html(value);
                break;
            case 'header_details':
                $('#tb_'+id).find('.header-text h1').html(value);
                break;
            case 'order_details':
                $('#tb_'+id).find('.order-title').html(value);
                break;
            case 'customer_address':
                $('#tb_'+id).find('.thwec-customer-header').html(value);
                break;
            case 'billing_address':
                $('#tb_'+id).find('.thwec-billing-header').html(value);
                break;
            case 'shipping_address':
                $('#tb_'+id).find('.thwec-shipping-header').html(value);
                break;
            default: '';
        }
    }

    function body_resize_sidebar_scroll(){
        activate_panel_wrapper_scroll(false);
        $(window).on('resize', function(){
            var active_tab1 = $('#thwec-sidebar-configure.active-tab');
            var active_tab2 = $('#thwec-sidebar-settings.active-tab');
            if(active_tab1.length > 0){
               activate_panel_wrapper_scroll(active_tab1);
            }else if(active_tab2.length > 0){
               activate_panel_layout_wrapper_scroll(active_tab2, true, true, true);
            }
        });
    }

    function activate_panel_wrapper_scroll(active_tab1){
        active_tab1 = active_tab1 ? active_tab1 : $('#thwec-sidebar-configure.active-tab');
        var sidebar_wrapper = $('#thwec-sidebar-element-wrapper');
        var height1 = sidebar_wrapper.height() - 55; // Use it only if there is wecm-disclaimer section in main sidebar
        active_tab1.find('.thwec-layers-outer-wrapper').css({
            'height': height1,
        });
    }
    function activate_panel_layout_wrapper_scroll(active_tab2, layout_wrapper, edit_wrapper, element_wrapper){
        active_tab2 = active_tab2 ? active_tab2 : $('#thwec-sidebar-settings.active-tab');
        var sidebar_wrapper = $('#thwec-sidebar-element-wrapper');
        var height2 = sidebar_wrapper.height() - 4;
        var height3 = sidebar_wrapper.height() - 4;
        if(layout_wrapper){
            active_tab2.find('.panel-layout-outer-wrapper').css({
               'height': height2,
            });
        }
        if(edit_wrapper){
            active_tab2.find('.thwec_field_form_outer_wrapper').css({
                'height': height3,
            });
        }
        if(element_wrapper){
            active_tab2.find('.outer-wrapper').css({
                'height': height2,
            });
        }
    }


    function setup_category_toggle_functions(elm){
        var click_icon = $(elm);
        var click_cat = $(elm).closest('.grid-category');
        if(click_cat.hasClass('category-collapse')){
            click_cat.removeClass('category-collapse');
            $('.thwec-tbuilder-elm-grid-layout-element').find('.grid-category').not(click_cat).each(function(index, el) {
                $(this).addClass('category-collapse');
            });
        }else{
            click_cat.addClass('category-collapse');
        }
    }

        /*----------------------------------
     *---- Helper Variabes - START -----
     *----------------------------------*/

    var mapImgSizeObj = {
       "1": { 'thwec_replace_img_width':'213px','thwec_replace_img_height':'138px'},
       "2": { 'thwec_replace_img_width':'183px','thwec_replace_img_height':'119px'},
       "3": { 'thwec_replace_img_width':'159px','thwec_replace_img_height':'103px'},
       "4": { 'thwec_replace_img_width':'127px','thwec_replace_img_height':'82px'},
    };

    var FONT_LIST = {
        "helvetica" : "'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif",
        "georgia" : "Georgia, serif",
        "times" : "'Times New Roman', Times, serif",
        "arial" : "Arial, Helvetica, sans-serif",
        "arial-black" : "'Arial Black', Gadget, sans-serif",
        "comic-sans" : "'Comic Sans MS', cursive, sans-serif",
        "impact" : "Impact, Charcoal, sans-serif",
        "tahoma" : "Tahoma, Geneva, sans-serif",
        "trebuchet" : "'Trebuchet MS', Helvetica, sans-serif",
        "verdana" : "Verdana, Geneva, sans-serif",
    }

    var HTML_TABLE_PROPS = {
        "row_cellpadding" : "cellpadding",
        "row_cellspacing" : "cellspacing"
    }
    
    var CSS_PROPS = {   
        p_t : {name : 'padding-top'},
        p_r : {name : 'padding-right'},
        p_b : {name : 'padding-bottom'},
        p_l : {name : 'padding-left'}, 
        m_t : {name : 'margin-top'},
        m_r : {name : 'margin-right'},
        m_b : {name : 'margin-bottom'},
        m_l : {name : 'margin-left'},
        vertical_align : {name : 'vertical-align'},


        width : {name : 'width'},
        height : {name : 'height'},
        size_width : {name : 'width'},
        size_height : {name : 'height'},
        content_size_width : {name : 'width'},
        content_size_height : {name : 'height'},

        b_t : {name : 'border-top-width'},
        b_r : {name : 'border-right-width'},
        b_b : {name : 'border-bottom-width'},
        b_l : {name : 'border-left-width'},
        border_style : {name : 'border-style'},
        border_color : {name : 'border-color'},
        border_radius : {name : 'border-radius'},
        upload_bg_url : {name : 'background-image'},
        upload_img_url : {name : 'display'},
        bg_color     : {name : 'background-color'},
        bg_position : {name : 'background-position'},
        bg_size : {name : 'background-size'},
        bg_repeat : {name : 'background-repeat'},


        color : {name : 'color'},
        font_size : {name : 'font-size'},
        font_weight : {name : 'font-weight'},
        text_align : {name : 'text-align'},
        content_align : {name : 'text-align'},
        line_height : {name : 'line-height'},
        font_family : {name : 'font-family'},

        align : {name : 'text-align'},
        img_width : {name : 'width'},
        img_height : {name : 'height'},
        img_size_width : {name : 'width'},
        img_size_height : {name : 'height'},
        
        icon_p_t : {name : 'padding-top'},
        icon_p_r : {name : 'padding-right'},
        icon_p_b : {name : 'padding-bottom'},
        icon_p_l : {name : 'padding-left'},

        img_bg_color : {name : 'background-color'},
        img_p_t : {name : 'padding-top'},
        img_p_r : {name : 'padding-right'},
        img_p_b : {name : 'padding-bottom'},
        img_p_l : {name : 'padding-left'},
        img_m_t : {name : 'margin-top'},
        img_m_r : {name : 'margin-right'},
        img_m_b : {name : 'margin-bottom'},
        img_m_l : {name : 'margin-left'},  
        img_border_width_top : {name : 'border-top-width'},  
        img_border_width_right : {name : 'border-right-width'},  
        img_border_width_bottom : {name : 'border-bottom-width'},  
        img_border_width_left : {name : 'border-left-width'},  
        img_border_style : {name : 'border-style'},  
        img_border_color : {name : 'border-color'},  
        img_border_radius : {name : 'border-radius'},  

        details_color : {name : 'color'},
        details_font_size : {name : 'font-size'},
        details_font_weight : {name : 'font-weight'},
        details_text_align : {name : 'text-align'},
        details_line_height : {name : 'line-height'},
        details_font_family : {name : 'font-family'},

        content_border_width_top : {name : 'border-top-width'},
        content_border_width_right : {name : 'border-right-width'},
        content_border_width_bottom : {name : 'border-bottom-width'},
        content_border_width_left : {name : 'border-left-width'},
        content_border_style : {name : 'border-style'},
        content_border_color : {name : 'border-color'},
        content_border_radius : {name : 'border-radius'},
        content_width : {name : 'width'},
        content_height : {name : 'height'},
        content_bg_color : {name : 'background-color'},

        content_p_t : {name : 'padding-top'},
        content_p_r : {name : 'padding-right'},
        content_p_b : {name : 'padding-bottom'},
        content_p_l : {name : 'padding-left'},
        content_m_t : {name : 'margin-top'},
        content_m_r : {name : 'margin-right'},
        content_m_b : {name : 'margin-bottom'},
        content_m_l : {name : 'margin-left'},
        border_spacing : {name : 'border-spacing'},
        divider_height :{name : 'border-top-width'},
        divider_color :{name : 'border-top-color'},
        divider_style :{name : 'border-top-style'},
        product_img : {name : 'display'},
        url1 : {name : 'display'},
        url2 : {name : 'display'},
        url3 : {name : 'display'},
        url4 : {name : 'display'},
        url5 : {name : 'display'},
        url6 : {name : 'display'},
        url7 : {name : 'display'},
        url : {name : 'display'}
    }
    
    var ELM_BLOCK_FORM_PROPS = {
        p_t : {name : 'padding_top', type : 'text'},
        p_r : {name : 'padding_right', type : 'text'},
        p_b : {name : 'padding_bottom', type : 'text'},
        p_l : {name : 'padding_left', type : 'text'}, 
        m_t : {name : 'margin_top', type : 'text'},
        m_r : {name : 'margin_right', type : 'text'},
        m_b : {name : 'margin_bottom', type : 'text'},
        m_l : {name : 'margin_left', type : 'text'},
        vertical_align : {name : 'vertical_align', type : 'select'},

        width : {name : 'width', type : 'text'},
        height : {name : 'height', type : 'text'},
        size_width : {name : 'size_width', type : 'text'},
        size_height : {name : 'size_height', type : 'text'},
        content_size_width : {name : 'content_size_width', type : 'text'},
        content_size_height : {name : 'content_size_height', type : 'text'},
        img_size_width : {name : 'img_size_width', type : 'text'},
        img_size_height : {name : 'img_size_height', type : 'text'},

        b_t : {name : 'border_width_top', type : 'text'},
        b_r : {name : 'border_width_right', type : 'text'},
        b_b : {name : 'border_width_bottom', type : 'text'},
        b_l : {name : 'border_width_left', type : 'text'},
        border_style : {name : 'border_style', type : 'select'},
        border_color : {name : 'border_color', type : 'text'},
        border_radius : {name : 'border_radius', type : 'text'},

        bg_color     : {name : 'bg_color', type : 'text'},
        upload_bg_url : {name : 'upload_bg_url', type : 'text'},
        upload_img_url : {name : 'upload_img_url', type : 'text', attribute : 'yes'},
        bg_position : {name : 'bg_position', type : 'text'},
        bg_size : {name : 'bg_size', type : 'text'},
        bg_repeat : {name : 'bg_repeat', type : 'select'},

        content : {name : 'content', type : 'text',attribute : 'yes'},
        color : {name : 'color', type : 'text'},
        font_size : {name : 'font_size', type : 'text'},
        font_weight : {name : 'font_weight', type : 'text'},
        text_align : {name : 'text_align', type : 'hidden'},
        content_align : {name : 'content_align', type : 'hidden'},
        line_height : {name : 'line_height', type : 'text'},
        font_family : {name : 'font_family', type : 'select'},
        
        url : {name : 'url', type : 'text', attribute : 'yes'},
        title : {name : 'title', type : 'text', attribute : 'yes'},
        align : {name : 'align', type : 'hidden'},
        img_width : {name : 'img_width', type : 'text'},
        content_width : {name : 'content_width', type : 'text'},
        content_height : {name : 'content_height', type : 'text'},
        img_height : {name : 'img_height', type : 'text'},
        img_bg_color : {name : 'img_bg_color', type : 'text'},
       
        icon_p_t : {name : 'icon_padding_top', type : 'text'},
        icon_p_r : {name : 'icon_padding_right', type : 'text'},
        icon_p_b : {name : 'icon_padding_bottom', type : 'text'},
        icon_p_l : {name : 'icon_padding_left', type : 'text'},

        img_p_t : {name : 'img_padding_top', type : 'text'},
        img_p_r : {name : 'img_padding_right', type : 'text'},
        img_p_b : {name : 'img_padding_bottom', type : 'text'},
        img_p_l : {name : 'img_padding_left', type : 'text'},
        img_m_t : {name : 'img_margin_top', type : 'text'},
        img_m_r : {name : 'img_margin_right', type : 'text'},
        img_m_b : {name : 'img_margin_bottom', type : 'text'},
        img_m_l : {name : 'img_margin_left', type : 'text'},
          
        img_border_width_top : {name : 'img_border_width_top', type : 'text'},  
        img_border_width_right : {name : 'img_border_width_right', type : 'text'},  
        img_border_width_bottom : {name : 'img_border_width_bottom', type : 'text'},  
        img_border_width_left : {name : 'img_border_width_left', type : 'text'},  
        img_border_style : {name : 'img_border_style', type : 'select'},  
        img_border_color : {name : 'img_border_color', type : 'text'},  
        img_border_radius : {name : 'img_border_radius', type : 'text'},  

        details_color : {name : 'details_color', type : 'text'},
        details_font_size : {name : 'details_font_size', type : 'text'},
        details_font_weight : {name : 'details_font_weight', type : 'text'},
        details_text_align : {name : 'details_text_align', type : 'hidden'},
        details_line_height : {name : 'details_line_height', type : 'text'},
        details_font_family : {name : 'details_font_family', type : 'select'},
        textarea_content : {name : 'textarea_content', type : 'textarea', attribute : 'yes'},
        
        content_border_width_top : {name : 'content_border_width_top', type : 'text'},
        content_border_width_right : {name : 'content_border_width_right', type : 'text'},
        content_border_width_bottom : {name : 'content_border_width_bottom', type : 'text'},
        content_border_width_left : {name : 'content_border_width_left', type : 'text'},
        content_border_style : {name : 'content_border_style', type : 'select'},
        content_border_color : {name : 'content_border_color', type : 'text'},
        content_border_radius : {name : 'content_border_radius', type : 'text'},
        content_bg_color : {name : 'content_bg_color', type : 'text'},
        
        content_p_t : {name : 'content_padding_top', type : 'text'},
        content_p_r : {name : 'content_padding_right', type : 'text'},
        content_p_b : {name : 'content_padding_bottom', type : 'text'},
        content_p_l : {name : 'content_padding_left', type : 'text'},
        content_m_t : {name : 'content_margin_top', type : 'text'},
        content_m_r : {name : 'content_margin_right', type : 'text'},
        content_m_b : {name : 'content_margin_bottom', type : 'text'},
        content_m_l : {name : 'content_margin_left', type : 'text'},
        border_spacing : {name: 'border_spacing', type:'text'},
        url1 : {name : 'url1', type : 'text', attribute : 'yes'},
        url2 : {name : 'url2', type : 'text', attribute : 'yes'},
        url3 : {name : 'url3', type : 'text', attribute : 'yes'},
        url4 : {name : 'url4', type : 'text', attribute : 'yes'},
        url5 : {name : 'url5', type : 'text', attribute : 'yes'},
        url6 : {name : 'url6', type : 'text', attribute : 'yes'},
        url7 : {name : 'url7', type : 'text', attribute : 'yes'},
        divider_height : {name : 'divider_height', type : 'text'},
        divider_color : {name : 'divider_color', type : 'text'},
        divider_style : {name : 'divider_style', type : 'select'},
        product_img : {name : 'checkbox_option_image', type : 'checkbox'},
        row_cellpadding : {name : 'row_cellpadding', type : 'checkbox'},
        row_cellspacing : {name : 'row_cellspacing', type : 'checkbox'},
        custom_hook_name     : {name : 'custom_hook_name', type : 'text', attribute : 'yes'},
        additional_css : {name : 'additional_css', type : 'textarea'},
    }

    var ELM_FORM_PROPS = {
        checkbox_option_image   : 'product_img',
        content_padding_top     : 'content_p_t',
        content_padding_right   : 'content_p_r',
        content_padding_bottom  : 'content_p_b',
        content_padding_left    : 'content_p_l',
        content_margin_top      : 'content_m_t',
        content_margin_right    : 'content_m_r',
        content_margin_bottom   : 'content_m_b',
        content_margin_left     : 'content_m_l',
        icon_padding_top        : 'icon_p_t',
        icon_padding_right      : 'icon_p_r',
        icon_padding_bottom     : 'icon_p_b',
        icon_padding_left       : 'icon_p_l',
        img_padding_top         : 'img_p_t', 
        img_padding_right       : 'img_p_r', 
        img_padding_bottom      : 'img_p_b', 
        img_padding_left        : 'img_p_l', 
        img_margin_top          : 'img_m_t', 
        img_margin_right        : 'img_m_r', 
        img_margin_bottom       : 'img_m_b', 
        img_margin_left         : 'img_m_l',
        border_width_top        : 'b_t',
        border_width_right      : 'b_r',
        border_width_bottom     : 'b_b',
        border_width_left       : 'b_l',
        padding_top             : 'p_t',
        padding_right           : 'p_r', 
        padding_bottom          : 'p_b',
        padding_left            : 'p_l',
        margin_top              : 'm_t',
        margin_right            : 'm_r',
        margin_bottom           : 'm_b',
        margin_left             : 'm_l',
    }

    var DEFAULT_CSS = {
        'color' : 'transparent',
        'background-color' : 'transparent',
        'border-color': 'transparent',
        'background-color' : 'transparent',
        'padding-top' : '0px',
        'padding-right'  : '0px',
        'padding-bottom' : '0px',
        'padding-left' : '0px',
        'background-image' : 'none',
    }

    var VISIBLE_OPTIONS = {
        url : {name : 'url', css: 'block'},
        upload_img_url : { name : 'upload_img_url', header_details: {css: 'table-row'}, image : {css: 'inline-block'}, gif : {css: 'inline-block'}},
        url1  : {name : 'url1', css: 'table-cell'},
        url2  : {name : 'url2', css: 'table-cell'},
        url3  : {name : 'url3', css: 'table-cell'},
        url4  : {name : 'url4', css: 'table-cell'},
        url5  : {name : 'url5', css: 'table-cell'},
        url6  : {name : 'url6', css: 'table-cell'},
        url7  : {name : 'url7', css: 'table-cell'},
    };




     /*----------------------------------
     *---- Helper Variabes - END -----
     *----------------------------------*/


    /*----------------------------------
    *---- Helper Fuctions - Start ------
    *---------------------------------*/

    function get_random_string(){
        var block_id = $('#tb_temp_builder').attr('data-global-id');
        var new_block_id = parseInt(block_id)+1;
        if($('#tb_'+new_block_id).length > 0){
            get_new_random_id(new_block_id);
        }else{
            $('#tb_temp_builder').attr('data-global-id',new_block_id);
        }
        return new_block_id;
    }

    function get_new_random_id(new_id){
      var new_id = parseInt(new_id)+1;
      if($('#tb_'+new_id).length > 0){
        get_new_random_id(new_id);
      }else{
        $('#tb_temp_builder').attr('data-global-id',new_id);
      }
    }

    function get_cleaned_block_name(name){ 
        var text = name.replace(/_/g,' ').replace(/\b[a-z]/g, function(string) {
            return string.toUpperCase();
        });
        return text;
    }

    function is_layout_block(name){
        if($.inArray(name, LAYOUT_BLOCKS) !== -1){
            return true;
        }
        return false;
    }

    function is_sidebar_validation_required_block(name){
        if($.inArray(name, SB_VALIDATION_REQ_BLOCKS) !== -1){
            return true;
        }
        return false;
    }

    function is_layout_column(name){
        if($.inArray(name, LAYOUT_COLUMNS) !== -1){
            return true;
        }
        return false;
    }

    function is_address_block(name){
        if($.inArray(name, ADDRESS_BLOCKS) !== -1){
            return true;
        }
        return false;
    }

    function is_hooks_features(name){
        if($.inArray(name, HOOK_FEATURES) !== -1){
            return true;
        }
        return false;
    }


    function clear_tbuilder(elm){
        var popup = $('#thwec_confirmation_alerts');
        var builder_css = $('#tb_temp_builder').attr('data-css-change');
        popup.find('.thwec-messages').html($('#thwec_clear_builder_confirm').html());
        var elm_length = $('.thwec-builder-elm-layers .panel-builder-block').length;
        if(elm_length >1 || builder_css == 'false'){
            popup.dialog("option", "buttons", [
                {
                    text:"No",
                    click: function() {
                        $(this).dialog('close');
                    }
                },
                {
                    text:"Yes",
                    click: function() {
                        confirmation_tbuilder_clear();
                        $(this).dialog('close');
                    }
                }
            ]);
            popup.dialog('open');
        }
    }

    function confirmation_tbuilder_clear(){
        var tb_builder =  $('#tb_temp_builder');
        tb_builder.empty();
        $('div.thwec-builder-elm-layers').empty();
        setup_blank_track_panel_msg();
        tb_builder.attr('data-css-props',TB_DEFAULT);
        tb_builder.attr('data-global-id','1000');
        tb_builder.attr('data-track-save','1000');
        $('#thwec_template_css_override').html('');
        $('#thwec_template_css_preview_override').html('');
    }

    function reset_col_width(row,columns){
        var col_width = 100/parseInt(columns);
        var siblings_props;
        $('#tb_temp_builder').find('#tb_'+row+' tbody > tr > td').each(function(index, el) {
            var data_props = $(this).attr('data-css-props');
            if($(this).attr('id')){
                var block_id = $(this).attr('id').replace('tb_','');
                if(data_props){
                    data_props = JSON.parse(data_props);
                    siblings_props = data_props;
                }else{
                    var data_props= BASIC_PROPS;
                }
                data_props['width'] = col_width+'%';
                var data_props_json = JSON.stringify(data_props);
                $(this).attr('data-css-props',data_props_json);
                if( !$.isEmptyObject( data_props ) ){
                    $.each(data_props, function(index, val) {
                        var props = {};
                        props[index] = val;
                        prepare_css_functions(index, props, block_id, 'column_clone');
                    });
                }
            }
        });
    }

    function get_column_html(id){
        var html = '<td class="column-padding thwec-col thwec-columns" id="tb_'+id+'">'+BLANK_TD_DATA+'</td>';
        return html;
    }

    function focus_selected_element(b_elm, p_elm, status){
        remove_builder_panel_highlights();
        var tbuilder_ref = $('#tb_temp_builder');
        var panel_ref = $('.thwec-sidebar-config-elm-layers');
        tbuilder_ref.find('.thwec-builder-highlight').removeClass('thwec-builder-highlight');
        panel_ref.find('.thwec-panel-highlight').removeClass('thwec-panel-highlight');
        p_elm.addClass('thwec-panel-highlight');
        b_elm.addClass('thwec-builder-highlight');
        $('html, body').animate({scrollTop: get_element_position_b(b_elm)}, 500);
        var p_container = $('.thwec-layers-outer-wrapper');
        p_container.animate({scrollTop: get_element_position_p(p_elm, p_container)}, 500);
        setTimeout(
            function() { 
                p_elm.removeClass('thwec-panel-highlight');
                b_elm.removeClass('thwec-builder-highlight'); 
            },
            3000
        );
    }

    function get_element_position_b(elm){
        return parseInt(elm.offset().top)-200;
    }

    function get_element_position_p(elm,container){
        return parseInt(elm.offset().top - container.offset().top + container.scrollTop())-200;
    }

    function prepare_css(props){
        var css = '';
        $.each( props, function( name, css_prop ) {
            if(name in CSS_PROPS){
                var css_pname = CSS_PROPS[name]['name'];
                css_prop =  css_prop ? css_prop : DEFAULT_CSS[css_pname];
                if(css_prop){
                    css += css_pname+':'+css_prop+';';
                }
            }
        });
        return css;
    }    

    function get_css_parent_selector(blockId, isPrev){
        var p_selector = "#"+blockId;
        var selector = isPrev ? p_selector : p_selector+' ';
        return selector;
    }

     /*----------------------------------
     *---- Click Fuctions - START -------
     *----------------------------------*/

     function template_action_add_row(elm){
        change_sidebar_status(false);
        var CLICK_FLAG = 'columns';
        setup_sidebar_general('layout');
        activate_panel_layout_wrapper_scroll(false, true, false, false);
     }

    function builder_block_delete(elm){
        if($(elm).closest('div.panel-builder-block').hasClass('hooks')){
            var id = $(elm).closest('div.panel-builder-block').attr('id');
            $(elm).closest('div.panel-builder-block').remove();
            var block = $('#tb_temp_builder').find('#tb_'+id);
            var block_parent = block.closest('.thwec-columns');
            block.remove();
            if(block_parent.find('.builder-block').length < 1 && block_parent.find('.hook-code').length < 1){ // Show html to add new content on deleting all elements inside
                block_parent.html(BLANK_TD_DATA);
            }
        }else{
            var select_block = $(elm).closest('div.panel-builder-block');
            var delete_panel_elm = select_block;
            var delete_id = select_block.attr('id');
            var builder_element = $('#tb_temp_builder').find('#tb_'+delete_id);
            var panel_element = $('div.thwec-builder-elm-layers').find('#'+delete_id);
            if(delete_panel_elm.hasClass('rows') || delete_panel_elm.hasClass('elements')){//  deleting rows or elements on clicking delete
                var builder_element_parent = builder_element.closest('.thwec-columns');
                builder_element.remove();
                panel_element.remove();
                if(builder_element_parent.find('.builder-block').length < 1 && builder_element_parent.find('.hook-code').length < 1){ // Show html to add new content on deleting all elements inside
                    builder_element_parent.html(BLANK_TD_DATA);
                }

            }else if(delete_panel_elm.hasClass('columns')){ // If deleting columns, count of column updated on rows and width of resulting columns reset 
                var builder_element_parent = builder_element.closest('.thwec-row');
                var panel_element_parent = panel_element.closest('.rows');
                var columns = panel_element_parent.attr('data-columns');
                if(columns <= 1){
                    builder_element_parent.remove();
                    panel_element_parent.remove();
                }else{
                    var updated_columns = parseInt(columns)-1;
                    panel_element_parent.attr('data-columns',updated_columns); 
                    builder_element_parent.attr('data-column-count',updated_columns);
                    builder_element.remove();
                    panel_element.remove();
                    var builder_element_parent_id = builder_element_parent.attr('id').replace('tb_','');
                    reset_col_width(builder_element_parent_id,updated_columns); // Resetting width of each column in the parent table
                }    
            }
        }
        $('#tb_temp_builder').attr('data-sidebar-change', 'false');
        setup_blank_track_panel_msg();
    }

    function reset_row_attributes(parent, old_count, new_count, parent_status){

        if(parent_status== 'id'){
            var parent = $('#tb_'+parent); 
        }
        var remove_class = old_count in NUMBER_TO_WORDS ? 'thwec-block-'+NUMBER_TO_WORDS[old_count]+'-column' : 'thwec-block-n-column';
        var add_class = new_count in NUMBER_TO_WORDS ? 'thwec-block-'+NUMBER_TO_WORDS[new_count]+'-column' : 'thwec-block-n-column';
        $(parent).removeClass(remove_class).addClass(add_class);
    }

    /*-----------------------------------------------
    /*---- New Insertion Function  -  START ---------
    /*---------------------------------------------*/
    
    function open_builder_block_edit_sidebar(elm, blockId, blockName){
        setup_sidebar_general('settings');
        var content = $('#thwec-sidebar-settings');
        setup_settings_form_edit(content, blockId, blockName);
        change_sidebar_status(false);
        var form = $('#thwec-sidebar-settings').find('form');
        thwec_base.set_property_field_value(form, 'hidden', 'block_id', blockId, 0);
        thwec_base.set_property_field_value(form, 'hidden', 'block_name', blockName, 0);
        thwec_base.setupColorPicker(form);
        thwec_base.setup_color_pick_preview(form);
        thwec_base.setup_tiptip_tooltips();
        setup_additional_field_styles(form);
        if(blockName == 'temp_builder'){
            WECM_CM = wp.codeEditor.initialize($('.additional_css'), thwec_var.cm_settings);
            var add_css = $('#thwec_template_css_additional_css');
            if(add_css.length && $.trim(add_css.html()) != ''){
                WECM_CM.codemirror.setValue($.trim(add_css.html()));
            }
        }

        // Change scrollbar div height according to window height - MAC compatibility
        activate_panel_layout_wrapper_scroll(false, false, true, false);
    }

    function setup_additional_field_styles(form){
        form.find('.thwec-aligment-icon-wrapper').each(function(index, el) {
            var icon_val = $(this).find('input[type="hidden"]').val();
            $(this).find(".img-wrapper[data-align='"+icon_val+"']").addClass('thwec-active-icon');
        });
    }

    function setup_settings_form_edit(content, blockId, blockName){
        var form = $('#thwec-sidebar-settings').find('form');
        if(is_layout_block(blockName)){ 
            content.find('.thwec_field_form_general td').html($('#thwec_field_form_id_row').html());
        }else if(is_layout_column(blockName)){ 
            content.find('.thwec_field_form_general td').html($('#thwec_field_form_id_col').html());
        }else{
            content.find('.thwec_field_form_general td').html($('#thwec_field_form_id_'+blockName).html());
        }
        remove_builder_panel_highlights();
        populate_builder_block_form_general(form, blockId, blockName); // Check for previous values
    }

    function thwec_clean_textarea_content(text){
        var data = text.replace(/&#39;/g,"'").replace(/&quot;/g,'"');
        return data;
    }

    function populate_builder_block_form_general(form, blockId, blockName){
        var ref_elem = $('#tb_'+blockId);
        var block_props_json = ref_elem.attr('data-css-props');
        var block_props = block_props_json ? JSON.parse(block_props_json) : false;
        var url_props_json = ref_elem.attr('data-text-props');
        var url_props = url_props_json ? JSON.parse(url_props_json): '';
        var default_upload_url =  form.find('.thwec-upload-preview').attr('data-default-url');
        var def_vals = get_default_form_values(blockName);
        if(block_props && !$.isEmptyObject(block_props)){
            $.each(block_props, function (key, value) {
                if(key == 'img_comp_width' || key == 'img_comp_height'){
                    return;
                }
                var props = ELM_BLOCK_FORM_PROPS[key];
                if(ELM_BLOCK_FORM_PROPS[key]['attribute'] && ELM_BLOCK_FORM_PROPS[key]['attribute'] == 'yes'){
                    if(key in VISIBLE_OPTIONS){
                        value = (value == 'inline-block' && key == 'upload_img_url' && blockName == 'header_details') ? VISIBLE_OPTIONS[key][blockName]['css'] : value; // Compatibility with previous versions.
                        value = (value == 'inline-block' && key == 'upload_img_url' && blockName == 'image') ? VISIBLE_OPTIONS[key][blockName]['css'] : value; // Compatibility with previous versions.
                        value = (value == 'inline-block' && blockName == 'social') ? VISIBLE_OPTIONS[key]['css'] : value; // Compatibility for version upto 2.0.1.1
                        if(blockName == 'image' || blockName == 'gif' || blockName == 'header_details'){
                            value = (value == VISIBLE_OPTIONS[key][blockName]['css']) ? url_props[key] : '';
                        }else{
                            value = (value == VISIBLE_OPTIONS[key]['css']) ? url_props[key] : '';
                        }
                    }else{
                        value = url_props[key];
                    }
                    if(key == 'textarea_content'){
                        value = thwec_clean_textarea_content(url_props[key]);
                    }
                }
                    
                if($.inArray(key,IMG_CSS) != -1){
                    value = value != '' && value !== default_upload_url ? value.match(/\((.*)\)/)[1] : false;
                }else if(key == 'product_img'){ 
                    value = (value == 'block') ? 1 : 0;
                }
                if(props['type'] == 'fourside'){
                    props['type'] = 'text';
                }
                if(key == 'upload_img_url'){
                    value = value == '' ? default_upload_url : value;
                    form.find('.img-preview-image .thwec-upload-preview img').attr('src',value);
                    if(value != default_upload_url){
                        form.find('.img-preview-'+blockName+' .remove-upload-btn').removeClass('remove-upload-inactive');
                    }
                    form.find('.img-preview-'+blockName+' .thwec-upload-preview img').attr('src',value);
                }
                if(key == 'upload_bg_url'){
                    if(value != false){
                        form.find('.img-preview-bg_image .remove-upload-btn').removeClass('remove-upload-inactive');
                    }else{
                        value = default_upload_url;
                    }
                    form.find('.img-preview-bg_image .thwec-upload-preview img').attr('src',value);
                }
                if(key == 'font_family' || key == 'details_font_family'){
                    $.each(FONT_LIST, function(key,valueObj){
                        if(valueObj==value){
                            value = key;
                        }
                    });
                }
                if(value == '' && def_vals != '' && key in def_vals){
                    value = def_vals[key];
                }
                thwec_base.set_property_field_value(form, props['type'], props['name'], value, 0);
            });
        }else if($.isEmptyObject(block_props) && blockName == 'custom_hook' && url_props != ''){
             $.each(url_props, function (key, value) {
                if(ELM_BLOCK_FORM_PROPS[key]){
                    var props = ELM_BLOCK_FORM_PROPS[key];
                    thwec_base.set_property_field_value(form, props['type'], props['name'], value, 0);
                }
            });
                    
        }
    }

    function get_default_form_values(blockName){
        var default_values = {};
        switch(blockName){
            case 'one_column':
            case 'two_column':
            case 'three_column':
            case 'four_column':
                    default_values = {
                        p_t  : '0px',
                        p_r : '0px',
                        p_b : '0px',
                        p_l : '0px',
                        m_t : '0px',
                        m_r : 'auto',
                        m_b : '0px',
                        m_l : 'auto',
                        b_t : '0px',
                        b_r : '0px',
                        b_b : '0px',
                        b_l : '0px',
                        border_spacing : '0px',
                        bg_size : '100%',
                        bg_position : 'center',
                    };
                    break;   

            case 'one_column_one':
                    default_values = {
                        width : '100%',
                        b_t : '1px',
                        b_r : '1px',
                        b_b : '1px',
                        b_l : '1px',
                        border_style : 'dotted',
                    };
                    break;  
            case 'two_column_one':
            case 'two_column_two':
                    default_values = {
                        width : '50%',
                        b_t : '1px',
                        b_r : '1px',
                        b_b : '1px',
                        b_l : '1px',
                        border_style : 'dotted',
                    };
                    break;
            case 'three_column_one':
            case 'three_column_two':
            case 'three_column_three':
                    default_values = {
                        width : '33%',
                        b_t : '1px',
                        b_r : '1px',
                        b_b : '1px',
                        b_l : '1px',
                        border_style : 'dotted',
                    };
                    break;   
            case 'four_column_one':
            case 'four_column_two':
            case 'four_column_three':
            case 'four_column_four':
                    default_values = {
                        width : '25%',
                        b_t : '1px',
                        b_r : '1px',
                        b_b : '1px',
                        b_l : '1px',
                        border_style : 'dotted',
                    };
                    break;
             case 'custom_hook':
                    default_values = {
                        custom_hook_name : 'custom_hook_name'
                    };
                    break;
                                               
            case 'temp_builder' : 
                    default_values = {
                        b_t : '1px',
                        b_r : '1px',
                        b_b : '1px',
                        b_l : '1px',
                        border_style : 'solid',
                        bg_color : '#edf1e4'
                    };
                    break;                                     
            default :
                    default_values ='';
        }
        return default_values;
    }


    function add_builder_elements(blockName, blockId, col_count){
        var new_id = get_random_string(); 
        var track_html = prepare_new_track_html(blockName,new_id); 
        var html = prepare_new_block_content_html(blockName, col_count);
        create_track_builder_blocks(html,track_html, blockId);
        setup_blank_track_panel_msg();
    }

    function prepare_new_track_html(blockName, row_id){
        if(is_layout_block(blockName)){
            var html = '<div id="'+row_id+'" class="rows panel-builder-block" data-columns="'+COLUMN_NUMBER[blockName]+'">';
            LAYOUT_OBJ['row'] = 'tb_'+row_id;
            html+= $('#thwec_tracking_panel_row_html').html();
            html = html.replace('{bl_id}', row_id);
            html = html.replace('{bl_name}', blockName);
            html = html.replace('data-icon-attr="{bl_name}"', 'data-icon-attr="'+blockName+'"');
            html+= '<div class="column-set">';
            for(var i=1;i<=COLUMN_NUMBER[blockName];i++){
                var column_id = get_random_string();
                var column_name = 'column_'+i;
                LAYOUT_OBJ[column_name] = 'tb_'+column_id;
                html+= add_new_track_col(row_id, column_id, blockName, i);
            }
            html+= '<div class="panel-add-btn btn-add-column" data-parent="'+row_id+'"><a href="#">Add Column</a></div>';
            html+= '</div></div>';
        }else if(is_hooks_features(blockName)){
            var name = get_cleaned_block_name(blockName);
            var html = '<div id="'+row_id+'" class="hooks panel-builder-block">';
            html+= $('#thwec_tracking_panel_hook_html').html();
            html = html.replace('{name}',get_cleaned_block_name(blockName));
            html+= '</div>';
            LAYOUT_OBJ['hook'] = 'tb_'+row_id;
        }else{
            var html = '<div id="'+row_id+'" class="elements panel-builder-block">';
            html+= $('#thwec_tracking_panel_elm_html').html();
            html = html.replace('{name}',get_cleaned_block_name(blockName));
            html = html.replace('{bl_id}', row_id);
            html = html.replace('{bl_name}', blockName);
            html = html.replace('{bl_attr_name}', blockName.toLowerCase());
            html+= '</div>;'
            LAYOUT_OBJ['element'] = 'tb_'+row_id;
        }

        return html;

    }

    function add_new_track_col(row_id, column_id, blockName, i){
        var column_name = blockName+'_'+NUMBER_TO_WORDS[i];
        if(!is_layout_column(column_name)){
            column_name = 'column_clone'; 
        }
        var t_html = '<div id="'+column_id+'" class="columns panel-builder-block" data-parent="'+row_id+'">';
        t_html+= $('#thwec_tracking_panel_col_html').html();
        t_html = t_html.replace('{bl_id}', column_id);
        t_html = t_html.replace('{bl_name}', column_name); 
        t_html = t_html.replace('data-icon-attr="{bl_name}"', 'data-icon-attr="'+column_name+'"');
        t_html+= '<div class="element-set">';
        t_html+= '<div class="thwec-hidden-sortable elements"></div><div class="btn-add-element panel-add-btn panel-add-element"><a href="#">Add Element</a></div>';
        t_html+= '</div></div>';
        return t_html;
    }

    function setup_blank_track_panel_msg(){
        if($('div.thwec-builder-elm-layers').find('.rows').length < 1){
            $('.thwec-empty-layer-msg').removeClass('thwec-layers-toggle');
        }else{
            $('.thwec-empty-layer-msg').addClass('thwec-layers-toggle');
        } 
    }

    function create_track_builder_blocks(builder_html,track_html, blockId){
        if(blockId){
            var id = 'tb_'+blockId;
            $(track_html).insertBefore($('div.thwec-builder-elm-layers').find('#'+blockId+' > .element-set > div.panel-add-element'));
            var target = $('#tb_temp_builder').find('#'+id);
            if(target.find('>.btn-add-element').length > 0){
                target.find('>.btn-add-element').remove();
            }
            target.append(builder_html);
        }else{
            $('div.thwec-builder-elm-layers').append(track_html);
            $('#tb_temp_builder .thwec-builder-column').append(builder_html);
        }
    }


     function prepare_new_block_content_html(blockName, col_count){
        var block_elm = '';
        var elm_type = '';
        if(blockName == "one_column"){
            block_elm = $('#thwec_template_layout_1_col');
            elm_type = 'layout';

        }else if(blockName == "two_column"){
            block_elm = $('#thwec_template_layout_2_col');
            elm_type = 'layout';

        }else if(blockName == "three_column"){
            block_elm = $('#thwec_template_layout_3_col');
            elm_type = 'layout';

        }else if(blockName == "four_column"){
            block_elm = $('#thwec_template_layout_4_col');
            elm_type = 'layout';

        }else if(blockName == "left-large-column"){
            block_elm = $('#thwec_template_layout_left_large_col');
            elm_type = 'layout';

        }else if(blockName == "right-large-column"){
            block_elm = $('#thwec_template_layout_right_large_col');
            elm_type = 'layout';

        }else if(blockName == "gallery-column"){
            block_elm = $('#thwec_template_layout_gallery_col');
            elm_type = 'layout';

        }else if(blockName == "header_details"){
            block_elm = $('#thwec_template_elm_header');
            elm_type = 'block';

        }else if(blockName == "footer_details"){
            block_elm = $('#thwec_template_elm_footer');
            elm_type = 'block';

        }else if(blockName == "customer_address"){
            block_elm = $('#thwec_template_elm_customer_address');
            elm_type = 'block';
            
        }else if(blockName == "order_details"){
            block_elm = $('#thwec_template_elm_order_details');
            elm_type = 'block';
            
        }else if(blockName == "billing_address"){
            block_elm = $('#thwec_template_elm_billing_address');
            elm_type = 'block';
            
        }else if(blockName == "shipping_address"){
            block_elm = $('#thwec_template_elm_shipping_address');
            elm_type = 'block';
            
        }else if(blockName == "text"){
            block_elm = $('#thwec_template_elm_text');
            elm_type = 'block';
            
        }else if(blockName == "image"){
            block_elm = $('#thwec_template_elm_image');
            elm_type = 'block';
            
        }else if(blockName == "social"){
            block_elm = $('#thwec_template_elm_social');
            elm_type = 'block';
            
        }else if(blockName == "button"){
            block_elm = $('#thwec_template_elm_button');
            elm_type = 'block';
            
        }else if(blockName == "divider"){
            block_elm = $('#thwec_template_elm_divider');
            elm_type = 'block';
            
        }else if(blockName == "gap"){
            block_elm = $('#thwec_template_elm_gap');
            elm_type = 'block';
            
        }else if(blockName == "gif"){
            block_elm = $('#thwec_template_elm_gif');
            elm_type = 'block';
            
        }else if(blockName == "video"){
            block_elm = $('#thwec_template_elm_video');  
            elm_type = 'block';

        }else if(blockName == "email_header"){
            block_elm = $('#thwec_template_hook_email_header');
            elm_type = 'hook';  

        }else if(blockName == "email_order_details"){
            block_elm = $('#thwec_template_hook_order_details'); 
            elm_type = 'hook'; 

        }else if(blockName == "before_order_table"){
            block_elm = $('#thwec_template_hook_before_order_table');  
            elm_type = 'hook';

        }else if(blockName == "after_order_table"){
            block_elm = $('#thwec_template_hook_after_order_table');
            elm_type = 'hook';  

        }else if(blockName == "order_meta"){
            block_elm = $('#thwec_template_hook_order_meta');
            elm_type = 'hook';  

        }else if(blockName == "customer_details"){
            block_elm = $('#thwec_template_hook_customer_address');
            elm_type = 'hook';  

        }else if(blockName == "email_footer"){
            block_elm = $('#thwec_template_hook_email_footer');  
            elm_type = 'hook';
        }else if(blockName == "downloadable_product"){
            block_elm = $('#thwec_template_downloadable_product');
            elm_type = 'hook';
        }else if(blockName == "custom_hook"){
            block_elm = $('#thwec_template_custom_hook');
            elm_type = 'block';
        }

        var block_html = '';
        if(block_elm.length){
            if(elm_type == 'layout'){
                block_html = clean_block_content_layout_html(block_elm,blockName);            
            }else if(elm_type == 'hook'){
                block_html = clean_block_content_element_html(block_elm,blockName,'hook',false);
            }else{
                block_html = clean_block_content_element_html(block_elm,blockName,'element', col_count);
            }
        }
        return block_html;
    }

    function clean_block_content_layout_html(block_elm,blockName){
        var html = block_elm.html();
        html = html.replace(blockName,LAYOUT_OBJ['row']);
        for(var i=1;i<=COLUMN_NUMBER[blockName];i++){
            var column_name = 'column_'+i;
            var replace_name = blockName+'_'+i;
            html = html.replace(replace_name,LAYOUT_OBJ[column_name]);
        }
        LAYOUT_OBJ={};
        return html;
    }


    function clean_block_content_element_html(block_elm,blockName,index, col_count){
        var html = block_elm.html();
        html = html.replace('{'+blockName+'}',LAYOUT_OBJ[index]);
        LAYOUT_OBJ={};
        return html;
    }

    function prepare_special_cleaning(html, blockName, col_count){
        if( (blockName == 'image' || blockName == 'gif') && mapImgSizeObj[col_count] && col_count){
            var img_object = mapImgSizeObj[col_count];
            $.each(img_object, function(index, el) {
                html = html.replace(index, el);
            });
        }else if(col_count > 4){
            var img_object = mapImgSizeObj[4];
            $.each(img_object, function(index, el) {
                html = html.replace(index, el);
            });
        }
        return html;
    }


    function setup_sidebar_general($active_tab){
       if($active_tab == 'configure'){ 
        }else if($active_tab == 'layout'){ 
            $('#thwec-sidebar-settings').html($('#template_builder_panel_layout').html());
        }else if($active_tab == 'layout-elements'){ 
            $('#thwec-sidebar-settings').html($('#template_builder_panel_layout_element').html());
        }else if($active_tab == 'settings'){
            $('#thwec-sidebar-settings').html($('#thwec_builder_block_edit_form').html());
        }
    }

    function template_sidebar_nav_back(elm){
        var sidebar_class = $(elm).data('nav');
        change_sidebar_status(true);
    }

    function save_additional_css( elm ){
        if(WECM_CM){
            WECM_CM.codemirror.save();
            save_sidebar_additional_css( elm );
        }
        
    }

    function save_sidebar_additional_css( elm ){
        var form = $(elm).closest('form');
        var block_id = thwec_base.get_property_field_value(form, 'hidden', 'block_id');
        var block_name = thwec_base.get_property_field_value(form, 'hidden', 'block_name');
        var additional_css = '';
        var save_block = $('#tb_'+block_id);
        var data_json_css = save_block.attr('data-css-props');
        var data_json_css = save_block.attr('data-css-props');
        data_json_css = $.parseJSON(data_json_css);

        //For compatibility with previous version
        // (Editing templates created from version that doesn't support additional css)
        if( block_name == 'temp_builder' && thwec_var.add_css_feature && !('additional_css' in data_json_css) ){
            var extra_css = {"additional_css":""};
            $.extend(data_json_css, extra_css);
        }
        var a_css = thwec_base.get_property_field_value(form, 'textarea', 'additional_css');
        render_additional_css( a_css );
        if( data_json_css ){
            var css_json_props = JSON.stringify(data_json_css);
            save_block.attr('data-css-props',css_json_props);
        }
        $('#tb_'+block_id).attr('data-css-change','false');
        if(thwec_var.save_temp_on_settings){
            thwec_settings.initiateTemplateSave( $('#thwec_template_save') );
        }
        display_save_messages();
    }

    function render_additional_css(a_css){
        $('#thwec_template_css_additional_css').html(a_css);
    }

    /*-------------------------------------------------------------------------------------------------------------------
    ------------------------------------------- SideBar Click Functions -------------------------------------------------
    -------------------------------------------------------------------------------------------------------------------*/ 
    function save_sidebar_form_onchange( field, event ){
        var form = $('#thwec-sidebar-settings').find('form');
        var key = field.attr('name').replace( 'i_', '' );
        var block_id = thwec_base.get_property_field_value(form, 'hidden', 'block_id');
        var block_name = thwec_base.get_property_field_value(form, 'hidden', 'block_name');
        var additional_css = '';
        var validation_req = false;
        var sidebar_form_valid = [];
        var save_block = $('#tb_'+block_id);
        var css_props = {};
        var js_props = {};
        var url_props = {};
        var textarea_content = false;
        var data_json_css = save_block.attr('data-css-props');
        var data_json_text = save_block.attr('data-text-props');
     
        var default_upload_url = $('#thwec-sidebar-element-wrapper').find('.thwec-upload-preview').attr('data-default-url');

        data_json_css = $.parseJSON(data_json_css);
        data_json_text = $.parseJSON(data_json_text);

        

        var data_css = $.extend({}, data_json_css, data_json_text);
        if(is_sidebar_validation_required_block(block_name)){
            validation_req = true;
        }

        var value = thwec_base.get_form_field_value(field, event.target.type);
        if( key in ELM_FORM_PROPS ){
            key = ELM_FORM_PROPS[key];
        }
        var style_props = key in ELM_BLOCK_FORM_PROPS ? ELM_BLOCK_FORM_PROPS[key] : false;
        if( style_props ){
            if(key in data_css){
                if(validation_req){ // If custom hook 
                    sidebar_form_valid = thwec_sb_form_custom_validations(key, value, block_name);
                }
                if(key == 'additional_css'){
                    additional_css = value;
                    value='';
                }
                if(typeof style_props['attribute'] != 'undefined'){
                    // Value to be managed by JS and CSS
                    if(key == 'textarea_content'){
                        var cleaned_textarea = thwec_base.escapeHTML(value);
                        data_json_text[key] = cleaned_textarea;
                        data_json_css[key] = '';
                        js_props[key] = cleaned_textarea;
                        css_props[key] = '';
                    }else if(key == 'custom_hook_name'){
                        data_json_text[key] = value;
                        js_props[key] = value;
                    }
                    else{
                    //all url fields
                        data_json_text[key] = value;
                        js_props[key] = value;
                        var cleaned_value = clean_css_value( key, value, block_name, block_id, default_upload_url );
                        
                        data_json_css[key] = cleaned_value;
                        css_props[key] = cleaned_value;
                    }
                }else{
                    //Value to be managed by CSS only
                    value = clean_css_value( key, value, block_name, block_id, default_upload_url  );

                    data_json_css[key] = value;
                    css_props[key] = value;
                }
            }

            if( !$.isEmptyObject( css_props ) ){
                if( key in CSS_PROPS ){
                    var css_prop = CSS_PROPS[key]['name'];
                    var css_value =  css_props[key] != '' ? css_props[key] : DEFAULT_CSS[css_prop];
                    if( !thwec_base.isCssValid( css_prop, css_value ) ){
                        return;
                    }
                }
            }

            if(sidebar_form_valid.length === 0 ){    // Validation only returns on status. If success then no returns. If changing the code, keep an eye here
                $('#thwec-sidebar-settings').find('#sb_validation_msg').html('');
                var text_json_props = JSON.stringify(data_json_text);
                if( data_json_css && css_props ){
                    var css_json_props = JSON.stringify(data_json_css);
                    save_block.attr('data-css-props',css_json_props);
                }
                if( data_json_text && js_props ){
                    save_block.attr('data-text-props',text_json_props);
                }

                $.each(FONT_LIST, function(key, value) {
                    if(key == css_props['font_family']){
                        css_props['font_family'] = value;
                    }
                    if(key == css_props['details_font_family']){
                        css_props['details_font_family'] = value;
                    }
                });
                perform_block_level_functions(block_id, block_name, css_props, additional_css );
                prepare_css_functions(key, css_props, block_id, block_name);
                after_css_rendering(block_id,block_name);
                if( block_name == 'order_details' ){
                    var product_name = 'ot_head_product_enable' in css_props ? css_props['ot_head_product_enable'] : true;
                    var product_qty = 'ot_head_quantity_enable' in css_props ? css_props['ot_head_quantity_enable'] : true;
                    var product_price = 'ot_head_price_enable' in css_props ? css_props['ot_head_price_enable'] : true;
                }
                if( !$.isEmptyObject( js_props ) ){
                    prepare_text_override(js_props, block_id, block_name);
                }
                $('#tb_temp_builder').attr('data-css-change','false');
                if( thwec_var.save_temp_on_settings == false ){
                    // display_save_messages();  
                }
                if(thwec_var.save_temp_on_settings){
                    thwec_settings.initiateTemplateSave( $('#thwec_template_save') );
                }
            }else{
                var message = '';
                $.each(sidebar_form_valid, function( status, msg ) {
                    message+= '<p class="thwec-msg-error">'+msg['message']+'</p>';
                });
                $('#thwec-sidebar-settings').find('#sb_validation_msg').html(message);
            }
        }
    }

    function clean_css_value( key, value, block_name, block_id, default_upload_url ){
        if(key in  VISIBLE_OPTIONS){
            if(block_name == 'header_details'){
                value = value !== default_upload_url ? value : "";
            }
            if(block_name == 'image' || block_name == 'gif' || block_name == 'header_details'){
                value = (value !== '') ? VISIBLE_OPTIONS[key][block_name]['css'] : 'none';
            }else{
                value = (value !== '') ? VISIBLE_OPTIONS[key]['css'] : 'none';
            }
        }
        if($.inArray(key, IMG_CSS) !== -1 && value){
            value = value !== default_upload_url && value !== '' ? 'url('+value+')' : '';
        }
        if(key == 'product_img'){
            value = (value == 1) ? 'block' : 'none';
            set_order_table_product_image(value, block_id);
        }
        if(key in HTML_TABLE_PROPS){
            var cell_flag = (value == 1) ? true : false;
            set_layout_row_html_table_props(block_id, value, cell_flag, key);
        }
        return value;
    }

    function display_save_messages(){
        $('#thwec_builder_save_messages').html('Settings Saved').addClass('thwec-show-save thwec-save-success');
        $('#save_settings_button').find('.save_form').attr('disabled',true);
        setTimeout(function(){
            $('#thwec_builder_save_messages').removeClass("thwec-show-save thwec-save-success");
            $('#save_settings_button').find('.save_form').removeAttr('disabled');
        },3000);
    }

    function perform_block_level_functions( id, name, css, additional ){
        if(is_address_block(name)){
            var align_float = css['align'];
            css['align'] = '';
            $('#tb_'+id).find('.thwec-address-alignment').attr('align',align_float);
        }else if(name == "order_details"){
            var align_float = css['align'];
            var order_block =  $('#tb_'+id);
            order_block.find('.order-padding').attr('align',align_float);
        }else if(name == "social"){
            var align_content = css['content_align'];
            $('#tb_'+id+' .thwec-social-outer-td').attr('align',align_content);
        }
    }

    function after_css_rendering(blo_id, blo_name){
        if(blo_name == 'image' || blo_name == 'social' || blo_name == 'header_details' || blo_name == 'gif'){
            var img = $('#tb_'+blo_id).find('img');
            var img_wrap = $('#tb_'+blo_id).find('p'); 
            var width = img.width();
            var height = img.height();
            var img_w = img_wrap.width();
            var img_h = img_wrap.height();
            var json_comp = {'cmp_img_width':width,'cmp_img_height':height,'cmp_img_wrap_width':img_w,'cmp_img_wrap_height':img_h};
            img.attr({'width':width,'height':height});
            img_wrap.attr({'width':img_w,'height':img_h});
            $('#tb_'+blo_id).attr('data-css-compat',JSON.stringify(json_comp));
        }
    }

    function thwec_sb_form_custom_validations(f_key, f_value, b_name){
        var validate = [];
        var result = false;
        if(b_name == 'custom_hook'){
            if(f_key == 'custom_hook_name'){
                result = thwec_settings.custom_tname_validation(f_value,'Hook');
            }
        }
        return result;
    }

    function set_layout_row_html_table_props(id, value, cell_flag, cell_attr){
        var elm = $('#tb_'+id);
        if(cell_flag){
            elm.attr(HTML_TABLE_PROPS[cell_attr],'0');
        }else{
            elm.removeAttr(HTML_TABLE_PROPS[cell_attr]);
        }
    }


    function set_order_table_product_image(value,block_id){
        var elm = $('#tb_'+block_id).find('.thwec-order-item-img');
        if(value == 'block'){
            $('#tb_'+block_id).find('.thwec-order-item-img').addClass('show-product-img');
        }else if(value == 'none'){    
            if(elm.hasClass('show-product-img')){
                $('#tb_'+block_id).find('.thwec-order-item-img').removeClass('show-product-img');
            }
        }
        
    }

    function prepare_css_functions(key, props, block_id, block_name){
        var tb_css_override_elm = $('#thwec_template_css_override');
        var tb_css_override = tb_css_override_elm.html();
        tb_css_override += prepare_css_override(key, props, 'tb_'+block_id, block_name, true);
        tb_css_override_elm.html(tb_css_override);

        var prev_css_override_elm = $('#thwec_template_css_preview_override');
        var prev_css_override = prev_css_override_elm.html();
        prev_css_override += prepare_css_override(key, props, 'tp_'+block_id, block_name, true);
        prev_css_override_elm.html(prev_css_override);
    }

    function prepare_css_override(key, props, blockId, blockName, isPrev ){
        var css = '';
        switch(blockName) {
            case 'one_column':
            case 'two_column':
            case 'three_column':
            case 'four_column':
            case 'row_clone':
                css = prepare_css_override_layout_row(key, props, blockId, isPrev);
                break;
            case 'one_column_one':
            case 'two_column_one':
            case 'two_column_two':
            case 'three_column_one':
            case 'three_column_two':
            case 'three_column_three':
            case 'four_column_one':
            case 'four_column_two':
            case 'four_column_three':
            case 'four_column_four':
            case 'column_clone':
                css = prepare_css_override_layout_col(key, props, blockId, isPrev);
                break;
            case 'header_details':
                css = prepare_css_override_elm_header(key, props, blockId, isPrev);
                break;
            case 'footer_details':
                css = prepare_css_override_elm_footer(key, props, blockId, isPrev);
                break;
            case 'customer_address':
                css = prepare_css_override_elm_customer(key, props, blockId, isPrev);
                break; 
            case 'billing_address':
                css = prepare_css_override_elm_billing(key, props, blockId, isPrev);
                break; 
            case 'shipping_address':
                css = prepare_css_override_elm_shipping(key, props, blockId, isPrev);
                break;      
            case 'text':
                css = prepare_css_override_elm_text(key, props, blockId, isPrev);
                break;
            case 'image':
                css = prepare_css_override_elm_image(key, props, blockId, isPrev);
                break;                                                                    
            case 'social':
                css = prepare_css_override_elm_social(key, props, blockId, isPrev);
                break;  
            case 'button':
                css = prepare_css_override_elm_button(key, props, blockId, isPrev);
                break;       
            case 'order_details':
                css = prepare_css_override_elm_order(key, props, blockId, isPrev);
                break;  
            case 'gap':
                css = prepare_css_override_elm_gap(key, props, blockId, isPrev);
                break;  
            case 'divider':
                css = prepare_css_override_elm_divider(key, props, blockId, isPrev);
                break;      
            case 'gif':
                css = prepare_css_override_elm_gif(key, props, blockId, isPrev);
                break;  
            case 'temp_builder':
                css = prepare_css_override_builder_container(key, props, blockId, isPrev);
                break;  
            default:
                css = '';
        }
        return css;
    }

    function prepare_text_override(js_props, block_id, block_name){
        block_id = 'tb_'+block_id;
        var text_elm_class = prepare_text_attributes(js_props, block_id, block_name);
        $.each(text_elm_class, function( name, props) {

            if(props['class'] == ''){
                var block_ref = $('#'+block_id);
            }else{
                var block_ref = $('#'+block_id).find(props['class']); 
                block_ref = block_ref.length < 1  ? $('#'+block_id) : block_ref;
                // If style not applied correctly the above line is culprit
                // If block_ref cannot find class means , changes to be made to block_id.
            }
            if(block_ref){
                if(props['attribute'] == 'image' ){
                    block_ref.attr('src', js_props[name]);
                }else if(props['attribute'] == 'html'){
                    if(block_name =='text' && js_props[name] != ''){
                        var formated_txt = js_props[name].replace(/\r?\n/g,'<br/>');
                        formated_txt = thwec_base.escapeHTML(formated_txt);
                        block_ref.html(formated_txt);
                    }
                    else{
                        block_ref.html(js_props[name]);
                    }
                }else if(props['attribute'] == 'text'){
                    block_ref.text(js_props[name]);

                }else if(props['attribute'] == 'link'){
                    block_ref.attr('href', js_props[name]);

                }else if(props['attribute'] == 'title'){
                    block_ref.attr('title', js_props[name]);
                } 
            }
        });
    }

    function text_to_paragraph( text ){
        var paragraph = '';
        if( text.length != 0 ){
            $.each( text, function(index, content ) {
                if( content ){
                    paragraph += '<p class="thwec-text-line">'+thwec_base.removeHTML(content)+'</p>';
                }
            });
        }
        return paragraph;
    }

    function prepare_css_override_layout_row(key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-row"; 
        var row_css = '';
        var row_props_arr = ['height', 'border_spacing', 'p_t', 'p_r', 'p_b', 'p_l', 'm_t', 'm_r', 'm_b', 'm_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'upload_bg_url', 'bg_color', 'bg_position', 'bg_size', 'bg_repeat'];
        if( $.inArray( key, row_props_arr ) !== -1 ){
            row_css = w_selector+' {';
            row_css += prepare_css( props );
            row_css += '}';
        }
        return row_css;
       
        
    }

    function prepare_css_override_layout_col(key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".column-padding{";
        var col_css = '';
        var col_props_arr = ['width', 'text_align', 'vertical_align', 'p_t', 'p_r', 'p_b', 'p_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_size', 'bg_repeat', 'bg_position'];
        if( $.inArray( key, col_props_arr ) !== -1 ){
            col_css = w_selector;
            col_css += prepare_css(props);
            col_css += '}';
        }
        return col_css;
    }

    function prepare_css_override_elm_header( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-header";
        var w_css = '';
        var tr_css = '';
        var td_css = '';
        var img_css = '';
        var h1_wp_css = '';
        var h1_css = '';

        var w_props = ['size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_repeat', 'bg_position', 'bg_size', 'm_t', 'm_r', 'm_b', 'm_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+'{';
            w_css += prepare_css( props );
            w_css += '}';
        }

        var tr_props = ['upload_img_url'];
        if( $.inArray( key, tr_props ) !== -1 ){
            tr_css = w_selector+' .header-logo-tr{';
            tr_css += prepare_css( props );
            tr_css += '}';
        }

        var td_props = ['content_align', 'img_p_t', 'img_p_r', 'img_p_b', 'img_p_l'];
        if( $.inArray( key, td_props ) !== -1 ){
            td_css = w_selector+' .header-logo{';
            td_css += prepare_css( props );
            td_css += '}';
        }

        var img_props = ['img_m_t', 'img_m_r', 'img_m_b', 'img_m_l', 'img_size_height','img_size_width','img_border_width_top','img_border_width_right', 'img_border_width_bottom', 'img_border_width_left', 'img_border_color', 'img_border_style', 'img_bg_color', 'align'];
        if( $.inArray( key, img_props ) !== -1 ){
            img_css = w_selector+' .header-logo .header-logo-ph{';
            img_css += prepare_css( props );
            img_css += '}';
        }

        var h1_wp_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, h1_wp_props ) !== -1 ){
            h1_wp_css = w_selector+' .header-text{';
            h1_wp_css += prepare_css( props );
            h1_wp_css += '}';
        }

        var h1_props = ['font_size', 'color','font_weight','text_align', 'line_height','font_family'];
        if( $.inArray( key, h1_props ) !== -1 ){
            h1_css = w_selector+' .header-text h1{';
            h1_css += prepare_css( props );
            h1_css += '}';
        }

        return w_css+tr_css+td_css+img_css+h1_wp_css+h1_css;
    }

    function prepare_css_override_elm_text( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-text";
        var table_css   ='';
        var elm_css ='';
        var elm_child_css   ='';
        
        var table_props_arr = [ 'color', 'align', 'font_size', 'line_height', 'font_weight', 'font_family', 'size_width', 'size_height', 'm_t', 'm_r', 'm_b', 'm_l', 'text_align']
        if( $.inArray( key, table_props_arr ) !== -1 ){
            table_css = w_selector+'{';
            table_css += prepare_css(props);
            table_css += '}';
        }

        var elm_props_arr = ['color', 'font_size', 'font_weight', 'line_height', 'text_align','font_family', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_color', 'border_style', 'p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, elm_props_arr ) !== -1 ){
            elm_css = w_selector+' .thwec-block-text-holder{';
            elm_css += prepare_css(props);
            elm_css += '}';
        }

        var elm_child_props_arr = ['color', 'font_size', 'font_weight', 'line_height','font_family'];
        if( $.inArray( key, elm_child_props_arr ) !== -1 ){
            var elm_child_css = w_selector+' *:not(br){';
            elm_child_css += prepare_css(props);
            elm_child_css += '}';
        }
        return table_css+elm_css+elm_child_css;
    }

    function prepare_css_override_elm_image( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-image";
        var tb_css = '';
        var td_css = '';
        var img_css = '';

        var tb_props_arr = ['img_m_t', 'img_m_r', 'img_m_b', 'img_m_l', 'img_bg_color', 'img_bg_color'];
        if( $.inArray( key, tb_props_arr ) !== -1 ){
            tb_css = w_selector+'{';
            tb_css += prepare_css(props);
            tb_css += '}';
        }

        var td_props_arr = ['content_align'];
        if( $.inArray( key, td_props_arr ) !== -1 ){
            td_css = w_selector+' td.thwec-image-column{';
            td_css += prepare_css(props);
            td_css += '}';
        }

        var img_props_arr = ['img_size_width', 'img_size_height',  'img_p_t', 'img_p_r', 'img_p_b', 'img_p_l', 'img_border_width_top', 'img_border_width_right', 'img_border_width_bottom', 'img_border_width_left', 'img_border_style', 'img_border_color'];
        if( $.inArray( key, img_props_arr ) !== -1 ){
            img_css = w_selector+' td.thwec-image-column p{';
            img_css += prepare_css(props);
            img_css += '}';
        }

        return tb_css+td_css+img_css;
    }    

    function prepare_css_override_elm_social( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-social";

        var w_css = '';
        var inner_tb_css = '';
        var table_td_css = '';
        var icons_css = '';
        var icon1_css = '';
        var icon2_css = '';
        var icon3_css = '';
        var icon4_css = '';
        var icon5_css = '';
        var icon6_css = '';
        var icon7_css = '';

        var tb_props_arr = ['content_align', 'm_t', 'm_r', 'm_b', 'm_l', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color']; 
        if( $.inArray( key, tb_props_arr ) !== -1 ){
            w_css = w_selector+'{';
            w_css += prepare_css(props);
            w_css += '}';
        }

        var inner_tb_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, inner_tb_props ) !== -1 ){
            inner_tb_css = w_selector+' .thwec-social-outer-td{';
            inner_tb_css += prepare_css(props);
            inner_tb_css += '}';
        }

        var table_td_props = ['content_align', 'icon_p_t', 'icon_p_r', 'icon_p_b', 'icon_p_l'];
        if( $.inArray( key, table_td_props ) !== -1 ){
            table_td_css = w_selector+' .thwec-social-td{';
            table_td_css += prepare_css(props);
            table_td_css += '}';
        }

        var icons_props = ['img_size_width', 'img_size_height'];
        if( $.inArray( key, icons_props ) !== -1 ){
            icons_css = w_selector+' .thwec-social-icon{';
            icons_css += prepare_css(props);
            icons_css += '}';
        }

        if( $.inArray( key, ['url1'] ) !== -1 ){
            icon1_css = w_selector+' td.thwec-td-fb{';
            icon1_css += prepare_css(props);
            icon1_css += '}';
        }

        if( $.inArray( key, ['url2'] ) !== -1 ){
            icon2_css = w_selector+' td.thwec-td-mail{';
            icon2_css += prepare_css(props);
            icon2_css += '}';
        }

        if( $.inArray( key, ['url3'] ) !== -1 ){
            icon3_css = w_selector+' td.thwec-td-tw{';
            icon3_css += prepare_css(props);
            icon3_css += '}';
        }

        if( $.inArray( key, ['url4'] ) !== -1 ){
            icon4_css = w_selector+' td.thwec-td-yb{';
            icon4_css += prepare_css(props);
            icon4_css += '}';
        }

        if( $.inArray( key, ['url5'] ) !== -1 ){
            icon5_css = w_selector+' td.thwec-td-lin{';
            icon5_css += prepare_css(props);
            icon5_css += '}';
        }

        if( $.inArray( key, ['url6'] ) !== -1 ){
            icon6_css = w_selector+' td.thwec-td-pin{';
            icon6_css += prepare_css(props);
            icon6_css += '}';
        }

        if( $.inArray( key, ['url7'] ) !== -1 ){
            icon7_css = w_selector+' td.thwec-td-insta{';
            icon7_css += prepare_css(props);
            icon7_css += '}';
        }

        return w_css+inner_tb_css+table_td_css+icons_css+icon1_css+icon2_css+icon3_css+icon4_css+icon5_css+icon6_css+icon7_css;
    }


    function prepare_css_override_elm_button( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-button-wrapper-table";
        var btn_wrapper_css = '';
        var btn_css = '';
        var link_css = '';

        var btn_wrapper_props = ['size_width', 'size_height', 'm_t', 'm_r', 'm_b', 'm_l', 'p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, btn_wrapper_props ) !== -1 ){
            btn_wrapper_css = w_selector+'{';
            btn_wrapper_css += prepare_css( props );
            btn_wrapper_css += '}';
        }     

        var btn_props = ['font_weight', 'font_size', 'font_family','color', 'bg_color','upload_bg_url', 'bg_repeat', 'bg_size', 'bg_position', 'b_t', 'b_b', 'b_l', 'b_r',  'border_style', 'border_color','content_p_t', 'content_p_r', 'content_p_b', 'content_p_l', 'text_align'];
        if( $.inArray( key, btn_props ) !== -1 ){
            btn_css = w_selector+' .thwec-button-wrapper{';
            btn_css += prepare_css( props );
            btn_css += '}';
        }

        var link_props = ['font_weight', 'line_height', 'font_size','font_family','color', 'text_align'];
        if( $.inArray( key, link_props ) !== -1 ){
            link_css = w_selector+' .thwec-button-link{';
            link_css += prepare_css( props );
            link_css += '}';
        }

        return btn_wrapper_css+btn_css+link_css;
    }

    function prepare_css_override_elm_customer( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-customer";
        var tb_wrapper_css = '';
        var w_css = '';
        var h2_css = '';
        var details_css = '';

        var tb_wrapper_props = ['size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'];
        if( $.inArray( key, tb_wrapper_props ) !== -1 ){
            tb_wrapper_css = w_selector+' .thwec-address-wrapper-table{';
            tb_wrapper_css += prepare_css( props );
            tb_wrapper_css += '}';
        }

        var w_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+' .customer-padding{';
            w_css += prepare_css( props );
            w_css += '}';
        }

        var h2_props = ['font_size', 'color','text_align','font_weight','line_height','font_family'];
        if( $.inArray( key, h2_props ) !== -1 ){
            h2_css = w_selector+' .thwec-customer-header{';
            h2_css += prepare_css( props );
            h2_css += '}';
        }

        var details_props = ['details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'];
        if( $.inArray( key, details_props ) !== -1 ){
            details_css = w_selector+' .thwec-customer-body{';
            details_css += prepare_css( props );
            details_css += '}';
        }

        return tb_wrapper_css+w_css+h2_css+details_css;
    }

    function prepare_css_override_elm_billing( key, props, blockId, isPrev ){ 
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-billing";
        var tb_wrapper_css = '';
        var w_css = '';
        var h2_css = '';
        var details_css = '';

        var tb_wrapper_props = ['size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'];
        if( $.inArray( key, tb_wrapper_props ) !== -1 ){
            tb_wrapper_css = w_selector+' .thwec-address-wrapper-table{';
            tb_wrapper_css += prepare_css( props );
            tb_wrapper_css += '}';
        }

        var w_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+' .billing-padding{';
            w_css += prepare_css( props );
            w_css += '}';
        }

        var h2_props = ['font_size', 'color','text_align','font_weight','font_family','line_height'];
        if( $.inArray( key, h2_props ) !== -1 ){
            h2_css = w_selector+' .thwec-billing-header{';
            h2_css += prepare_css( props );
            h2_css += '}';
        }

        var details_props = ['details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'];
        if( $.inArray( key, details_props ) !== -1 ){
            details_css = w_selector+' .thwec-billing-body{';
            details_css += prepare_css( props );
            details_css += '}';
        }

        return tb_wrapper_css+w_css+h2_css+details_css;
    }

    function prepare_css_override_elm_shipping( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-shipping";
        var tb_wrapper_css = '';
        var w_css = '';
        var h2_css = '';
        var details_css = '';

        var tb_wrapper_props = ['size_width', 'size_height', 'bg_color', 'upload_bg_url', 'bg_size', 'bg_position', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'm_t', 'm_r', 'm_b', 'm_l'];
        if( $.inArray( key, tb_wrapper_props ) !== -1 ){
            tb_wrapper_css = w_selector+' .thwec-address-wrapper-table{';
            tb_wrapper_css += prepare_css( props );
            tb_wrapper_css += '}';
        }

        var w_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+' .shipping-padding{';
            w_css += prepare_css( props );
            w_css += '}';
        }

        var h2_props = ['font_size', 'color','text_align','font_weight','line_height','font_family'];
        if( $.inArray( key, h2_props ) !== -1 ){
            h2_css = w_selector+' .thwec-shipping-header{';
            h2_css += prepare_css( props );
            h2_css += '}';
        }

        var details_props = ['details_font_size', 'details_color','details_text_align','details_font_family','details_font_weight','details_line_height'];
        if( $.inArray( key, details_props ) !== -1 ){
            details_css = w_selector+' .thwec-shipping-body{';
            details_css += prepare_css( props );
            details_css += '}';
        }

        return tb_wrapper_css+w_css+h2_css+details_css;
    }

    function prepare_css_override_elm_order( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-order";
        var elm_css = '';
        var w_css = '';
        var h2_css = '';
        var table_css = '';
        var tb_content_css = '';
        var tb_image_css = '';

        var elm_props =['align', 'size_width', 'size_height', 'bg_color', 'm_t', 'm_r', 'm_b', 'm_l', 'upload_bg_url', 'bg_size', 'bg_repeat', 'bg_repeat', 'b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color'];
        if( $.inArray( key, elm_props ) !== -1 ){
            elm_css = w_selector+'{';
            elm_css += prepare_css( props );
            elm_css += '}';
        }

        var w_props = ['p_t', 'p_r', 'p_b', 'p_l'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+' .order-padding{';
            w_css += prepare_css( props );
            w_css += '}';
        }

        var h2_props = ['color', 'font_size', 'text_align', 'font_weight', 'line_height','font_family'];
        if( $.inArray( key, h2_props ) !== -1 ){
            h2_css = w_selector+' .thwec-order-heading{';
            h2_css += prepare_css( props );
            h2_css += '}';
        }

        var table_props = [ 'content_size_width', 'content_size_height', 'content_bg_color', 'content_border_color', 'content_m_t', 'content_m_r', 'content_m_b', 'content_m_l','details_font_family'];
        if( $.inArray( key, table_props ) !== -1 ){
            table_css = w_selector+' .thwec-order-table{';
            table_css += prepare_css( props );
            table_css += '}';
        }

        var tb_content_props = ['details_font_size', 'details_color', 'details_text_align','details_font_family','content_border_color', 'details_font_weight', 'details_line_height', 'content_p_t', 'content_p_r', 'content_p_b', 'content_p_l'];
        if( $.inArray( key, tb_content_props ) !== -1 ){
            tb_content_css = w_selector+' .thwec-td{';
            tb_content_css += prepare_css( props );
            tb_content_css += '}';
            prepare_ot_column_styles( blockId, key, props );
        }

        var tb_image_props = ['product_img'];
        if( $.inArray( key, tb_image_props ) !== -1 ){
            tb_image_css = w_selector+' .thwec-td .thwec-order-item-img{';
            tb_image_css += prepare_css( props );
            tb_image_css += '}';    
        }

        return elm_css+w_css+h2_css+table_css+tb_content_css+tb_image_css;
    }

    function prepare_ot_column_styles( blockId, key, props ){
        var block = $('#'+blockId).length === 1 ? $('#'+blockId): false;
        var ot_td_css = false;
        if( block ){
            var ot_td_elm = block.find('.order-total-loop-end');
            // var ot_td_css = ot_td_elm.data('ot-css');
            var ot_td_css = ot_td_elm.attr('data-ot-css');
            if( ot_td_css ){
                ot_td_css = $.parseJSON( ot_td_css);
                if( ot_td_css[key ] ){
                    ot_td_css[key] = props[key];
                }
                ot_td_css = JSON.stringify( ot_td_css);
            }else{

            }
            ot_td_elm.attr('data-ot-css', ot_td_css);
        }
    }

    function prepare_css_override_elm_gap( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-gap";
        var w_css = '';

        var w_props = ['height', 'bg_color', 'upload_bg_url', 'bg_repeat', 'bg_size', 'bg_position', 'b_t', 'b_b', 'b_l', 'b_r',  'border_style', 'border_color'];
        if( $.inArray( key, w_props ) !== -1 ){
            w_css = w_selector+'{';
            w_css += prepare_css( props );
            w_css += '}';
        }
        return w_css;
    }


    function prepare_css_override_elm_divider( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-divider";
        var tb_css = '';
        var td_css = '';
        var hr_css = '';
        
        var tb_props = ['m_t', 'm_r', 'm_b', 'm_l'];
        if( $.inArray( key, tb_props ) !== -1 ){
            tb_css = w_selector+'{';
            tb_css += prepare_css( props );
            tb_css += '}';
        }

        var td_props = ['p_t', 'p_r', 'p_b', 'p_l', 'content_align'];
        if( $.inArray( key, td_props ) !== -1 ){
            td_css = w_selector+' td{';
            td_css += prepare_css( props );
            td_css += '}';
        }

        var hr_props = ['width', 'divider_height', 'divider_color', 'divider_style'];
        if( $.inArray( key, hr_props ) !== -1 ){
            hr_css = w_selector+' td hr{';
            hr_css += prepare_css( props );
            hr_css += '}';
        }

        return tb_css+td_css+hr_css;   
    }

    function prepare_css_override_elm_gif( key, props, blockId, isPrev ){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".thwec-block-gif";
        var tb_css = '';
        var td_css = '';
        var gif_css = '';

        var tb_props = ['upload_bg_url', 'bg_position', 'bg_size', 'bg_repeat', 'bg_color', 'm_t', 'm_r', 'm_b', 'm_l'];
        if( $.inArray( key, tb_props ) !== -1 ){
            tb_css = w_selector+'{';
            tb_css += prepare_css( props );
            tb_css += '}';
        }

        var td_props = ['content_align'];
        if( $.inArray( key, td_props ) !== -1 ){
            td_css = w_selector+' td.thwec-gif-column{';
            td_css += prepare_css( props );
            td_css += '}';
        }

        var gif_props = ['img_size_width', 'img_size_height', 'p_t', 'p_r', 'p_b', 'p_l', 'b_t', 'b_r', 'b_b', 'b_l', 'border_color', 'border_style'];
        if( $.inArray( key, gif_props ) !== -1 ){
            gif_css = w_selector+' td.thwec-gif-column p{';
            gif_css += prepare_css( props );
            gif_css += '}';
        }

        return tb_css+td_css+gif_css;
    }

    function prepare_css_override_builder_container( key, props, blockId, isPrev){
        var p_selector = get_css_parent_selector(blockId, isPrev);
        var w_selector = p_selector+".main-builder .thwec-builder-column";
        var tb_css = '';

        var tb_props = ['b_t', 'b_r', 'b_b', 'b_l', 'border_style', 'border_color', 'bg_size', 'bg_repeat', 'bg_color', 'upload_bg_url', 'bg_position'];
        if( $.inArray( key, tb_props ) !== -1 ){
            var tb_css = w_selector+'{';
            tb_css += prepare_css( props );
            tb_css += '}';
        }

        return tb_css;
    }

    function prepare_text_attributes(js_props, block_id, block_name){
        var text_css = {};
        switch(block_name){
            case 'header_details':
                text_css =  {
                    content : {'class' : '.header-text h1', 'attribute' : 'html'},
                    upload_img_url : {'class' : '.header-logo-ph img', 'attribute' : 'image'},
                };
                break;
            case 'footer_details' :
                text_css =  {
                    textarea_content : {'class' : '.footer-text', 'attribute' : 'html'},
                };
                break;
            case 'customer_address' :
                text_css =  {
                    content : {'class' : '.thwec-customer-header', 'attribute' : 'html'},
                };
                break;
            case 'order_details' :
                text_css = { 
                    content : {'class' : '.thwec-order-heading .order-title', 'attribute' : 'html'},
                };
                break;
            case 'billing_address' :
                text_css =  {
                    content : {'class' : '.thwec-billing-header', 'attribute' : 'html'},
                };
                break;   
            case 'shipping_address' :
                text_css =  {
                    content : {'class' : '.thwec-shipping-header', 'attribute' : 'html'},
                };
                break; 
            case 'text' :
                text_css = {
                    textarea_content : {'class' : '.thwec-block-text-holder', 'attribute' : 'html'},
                };
                break;
            case 'image' :
                text_css = {
                    upload_img_url : {'class' : 'img', 'attribute' : 'image'},
                };
                break;  
            case 'social' :
                text_css = {
                    url1 : {'class' : '.facebook', 'attribute' : 'link'},
                    url2 : {'class' : '.mail', 'attribute' : 'link'},
                    url3 : {'class' : '.twitter', 'attribute' : 'link'},
                    url4 : {'class' : '.youtube', 'attribute' : 'link'},
                    url5 : {'class' : '.linkedin', 'attribute' : 'link'},
                    url6 : {'class' : '.pinterest', 'attribute' : 'link'},
                    url7 : {'class' : '.instagram', 'attribute' : 'link'},
                };
                break;
            case 'button' :
                text_css = {
                    content : {'class' : '.thwec-button-link', 'attribute' : 'html'},
                    url : {'class' : '.thwec-button-link', 'attribute' : 'link'},
                    title : {'class' : '.thwec-button-link', 'attribute' : 'title'},
                };
                break;
            case 'menu' :
                text_css = {
                    block_text_url1 : {'class' : '.menu-item1 .menu-link', 'attribute' : 'link'},
                    block_text_url2 : {'class' : '.menu-item2 .menu-link', 'attribute' : 'link'},
                    block_text_url3 : {'class' : '.menu-item3 .menu-link', 'attribute' : 'link'},
                    block_text_url4 : {'class' : '.menu-item4 .menu-link', 'attribute' : 'link'},
                    block_text_url5 : {'class' : '.menu-item5 .menu-link', 'attribute' : 'link'},
                    block_text1 : {'class' : '.menu-item1 .menu-link', 'attribute' : 'html'},
                    block_text2 : {'class' : '.menu-item2 .menu-link', 'attribute' : 'html'},
                    block_text3 : {'class' : '.menu-item3 .menu-link', 'attribute' : 'html'},
                    block_text4 : {'class' : '.menu-item4 .menu-link', 'attribute' : 'html'},
                    block_text5 : {'class' : '.menu-item5 .menu-link', 'attribute' : 'html'},
                };
                break;
            case 'gif' :
                text_css = {
                    upload_img_url : {'class' : 'img', 'attribute' : 'image'},
                };
                break;
            case 'custom_hook' :
                text_css = {
                    custom_hook_name : {'class' : '.hook-text', 'attribute' : 'text'},
                };
                break;
            default    :
                text_css = '';
        }
        return text_css;
    }

    function change_sidebar_status($configure){
        // set $configure to true if tracking panel is to be displayed, false if settings page is to be displayed
        if($configure){
            var active = 'thwec-sidebar-configure';
            var inactive = 'thwec-sidebar-settings';
            var add_class = true;
            $('#thwec_header_sidebar_nav').addClass('thwec-inactive-nav');
        }else{
            var active = 'thwec-sidebar-settings';
            var inactive = 'thwec-sidebar-configure';
            var add_class = false;
            $('#thwec_header_sidebar_nav').removeClass('thwec-inactive-nav');
        }
        var settings_panel = $('#thwec-sidebar-element-wrapper');
        settings_panel.find('#'+active).removeClass('inactive-tab').addClass('active-tab');
        settings_panel.find('#'+inactive).removeClass('active-tab').addClass('inactive-tab');
        var sidebar = $('#thwec-sidebar-settings');
        if( sidebar.find('.thwec-block-settings-form').length == 1 && $configure ){
            var block_id = $('#thwec-sidebar-settings').find('input[name="i_block_id"]').val();
            var builder_elm = $('#tb_'+block_id);
            var panel_elm = $('#'+block_id);
            if( builder_elm.hasClass('thwec-block') || builder_elm.hasClass('hook-code') ){
                focus_selected_element(builder_elm, panel_elm,'builder');
            }
        }
    }

    function setup_sidebar_builder_clicks(){

        $('#thwec-sidebar-settings').on('click', '.elm-col', function(event) {
            var form = $('#thwec-sidebar-settings').find('form');
            remove_builder_panel_highlights();
            change_sidebar_status('thwec-sidebar-configure','thwec-sidebar-configure');
            var block_identifier = thwec_base.get_property_field_value(form, 'hidden', 'block_id');
            var col_count = thwec_base.get_property_field_value(form, 'hidden', 'col_count');
            block_identifier = block_identifier ? block_identifier : ''; 
            var name = $(this).find('div').data('block-name');
            add_builder_elements(name,block_identifier,col_count);
            setup_template_builder();
            activate_panel_wrapper_scroll(false);
        });

       
        // Click on elements or layouts from sidebar panel

        $('.thwec-builder-elm-layers').on('click', '.btn-add-column', function(event) {
            event.preventDefault();
            remove_builder_panel_highlights();
            var popup = $("#thwec_confirmation_alerts");
            var form = $("#thwec_confirmation_alert_form");
            popup.find('.thwec-messages').html($('#thwec_column_confirm').html());
            var selected_id = $(this).closest('div.btn-add-column').data('parent');
            if(confirm_flag){
                thwec_base.set_property_field_value(form, 'hidden', 'thwec_column_reference', selected_id, 0);
                thwec_base.set_property_field_value(form, 'hidden', 'thwec_flag_reference', 'add_column', 0);
                popup.dialog('open');
            }else{
                add_column_confirmation(selected_id,'add_column');
            }
            
        });  

        $('.thwec-tbuilder-wrapper').on('click', '.btn-add-element', function(event) {
            event.preventDefault();
            remove_builder_panel_highlights();
            setup_sidebar_general('layout-elements');
            var form = $('#thwec-sidebar-settings').find('form');

            if($(this).hasClass('panel-add-btn')){
                var status = 'panel';
                var click_elm = $(this).closest('div.columns').attr('id');
                var column_count = $(this).closest('.rows.panel-builder-block').attr('data-columns');
            }else if($(this).hasClass('builder-add-btn')){
                var status = 'builder';
                var click_elm = $(this).closest('td').attr('id').replace('tb_','');
                var column_count = $(this).closest('.thwec-row.builder-block').attr('data-column-count');
            }
            thwec_base.set_property_field_value(form, 'hidden', 'block_id', click_elm, 0);
            thwec_base.set_property_field_value(form, 'hidden', 'col_count', column_count, 0);
            change_sidebar_status(false);
            $('#thwec-sidebar-settings').find('.thwec-tbuilder-elm-grid-layout-element .column-basic-elements .grid-category').removeClass('category-collapse');

            // Change scrollbar div height according to window height - MAC compatibility
            activate_panel_layout_wrapper_scroll(false, false, false, true);
        });

        $('.thwec-tbuilder-wrapper').on('click', '.element-name,.hook-name', function(event) {
            var clicked = $(this);
            var panel_elm = clicked.closest('.panel-builder-block');
            var panel_elm_id = panel_elm.attr('id');
            var builder_elm = $('#tb_'+panel_elm_id);
            focus_selected_element(builder_elm, panel_elm,'builder');
        });

        $('#tb_temp_builder').on('click', '.thwec-block', function(event) {
            remove_builder_panel_highlights();
            var builder_elm = $(this);
            var builder_elm_id = builder_elm.attr('id');
            var panel_elm_id = builder_elm_id.replace('tb_','');
            var panel_elm = $('.thwec-builder-elm-layers').find('#'+panel_elm_id);
            // focus_selected_element(builder_elm, panel_elm,'panel');
            open_builder_block_edit_sidebar(builder_elm, panel_elm_id, builder_elm.attr('data-block-name') );
        });

        $('.thwec-tbuilder-wrapper').on('click', '.thwec-aligment-icon-wrapper .img-wrapper', function(event) {
            var icon_elm = $(this);
            var icon_wrapper = $(this).closest('.thwec-aligment-icon-wrapper');
            var form = $('#thwec-sidebar-settings').find('form');
            var align_val = icon_elm.attr('data-align');
            icon_wrapper.find('.thwec-text-align-input').val(align_val);
            icon_wrapper.find('.thwec-text-align-input').trigger('keyup');
            icon_wrapper.find('.thwec-active-icon').removeClass('thwec-active-icon');
            icon_wrapper.find('.thwec-active-icon').removeClass('thwec-active-icon');
            icon_elm.addClass('thwec-active-icon');

        });

    }

/*------------------------------------------------------------------------------------------------------------------
-------------------------------------- Additional Functions -------------------------------------------------------
------------------------------------------------------------------------------------------------------------------*/ 
    
    function setup_confirmation_alerts(){
        $("#thwec_confirmation_alerts").dialog({
            position: {
                my: 'center',
                at: 'center',
                of: $('#thwec-sidebar-element-wrapper')
            },
            draggable: true,
            appendTo: "#thwec-sidebar-element-wrapper",
            modal:true,
            width:275,
            resizable: false,
            autoOpen: false,
            dialogClass: 'thwec-confirmations',
            buttons: [
                {
                    text: "Cancel",
                    click: function() { 
                        $(this).dialog("close"); 
                    }  
                },
                {
                    text: "Ok",
                    click: function() {
                        var form = $(this).find('#thwec_confirmation_alert_form');
                        var f_option = thwec_base.get_property_field_value(form, 'hidden', 'thwec_flag_reference');
                        add_column_confirmation(false,f_option);
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }

    function add_column_confirmation(row_id,flag_option){
        var form = $("#thwec_confirmation_alert_form");
         var flag_option = thwec_base.get_property_field_value(form, 'hidden', 'thwec_flag_reference');
        if(flag_option == 'add_column'){
           if(!row_id){
                var row_id = thwec_base.get_property_field_value(form, 'hidden', 'thwec_column_reference');
            }
            confirm_flag = false;
            var column_id = get_random_string();
            var column_html = get_column_html(column_id);
            $('#tb_temp_builder').find('#tb_'+row_id+' > tbody > tr:first').append(column_html);
            var column_track_html = add_new_track_col(row_id, column_id,'column_clone',null);
            $(column_track_html).insertBefore($('div.thwec-builder-elm-layers').find('#'+row_id+' > .column-set > div.btn-add-column'));
            var old_column_number = $('#'+row_id).attr('data-columns');
            var new_column_number =  parseInt(old_column_number)+1;
            $('#'+row_id).attr('data-columns',new_column_number);
            $('#tb_'+row_id).attr('data-column-count',new_column_number);
            reset_col_width(row_id,new_column_number);
        }else if(flag_option == 'clone_column'){
            var column_id = thwec_base.get_property_field_value(form, 'hidden', 'thwec_column_id');
            confirm_clone_column_flag = false;
            builder_block_clone(column_id,true);
        } 
    }

    function confirm_builder_block_clone(elm){
        if($(elm).closest('.panel-builder-block').hasClass('columns')){
            var popup = $("#thwec_confirmation_alerts");
            var form = $("#thwec_confirmation_alert_form");
            popup.find('.thwec-messages').html($('#thwec_column_confirm').html());
            var selected_id = $(elm).closest('.panel-builder-block').attr('id');
            if(confirm_clone_column_flag){
                thwec_base.set_property_field_value(form, 'hidden', 'thwec_column_reference', '', 0);
                thwec_base.set_property_field_value(form, 'hidden', 'thwec_flag_reference', 'clone_column', 0);
                thwec_base.set_property_field_value(form, 'hidden', 'thwec_column_id', selected_id, 0);
                popup.dialog('open');
            }else{
                builder_block_clone(selected_id,true);
            }
        }else{
            builder_block_clone(elm,false);
        }   
    }

    function builder_block_clone(elm_ref,$check_flag){
        var elm = $check_flag ? $('#'+elm_ref+' >.layout-lis-item .template-action-clone') : elm_ref ;
        var clone_status = '';
        var settings_block = $(elm).closest('.thwec-block-settings');
        var selected_panel_block = settings_block.closest('.panel-builder-block');
        var selected_panel_block_id = selected_panel_block.attr('id');
        var selected_builder_block = $('#tb_temp_builder').find('#tb_'+selected_panel_block_id);
        var selected_panel_block_class = selected_panel_block.attr('class').split(" ");
        var panel_clone = selected_panel_block.clone([true][true]);
        var builder_clone = selected_builder_block.clone([true][true]);
        panel_clone.removeClass('thwec-panel-highlight');
        builder_clone.removeClass('thwec-builder-highlight');
        var id = panel_clone.attr('id');
        var parent_id = panel_clone.attr('data-parent');
        var new_id = get_random_string();
        var data_props = panel_clone.attr('data-props');
        var data_social = panel_clone.attr('data-social');
        panel_clone.attr('id',new_id);
        panel_clone.find('> .layout-lis-item > .thwec-block-settings').html(function(i, oldHTML) {
            return oldHTML.replace(id, new_id);
        }); // Change id of edit link inside li 

        if(panel_clone.hasClass('rows')){
            panel_clone.find('> .column-set > div').each(function(index, el) {
                if($(this).attr('data-parent')){
                    $(this).attr('data-parent',new_id);
                }
             });
        }
        builder_clone.attr('id','tb_'+new_id);
        setup_clone_css(builder_clone,new_id);
        if(panel_clone.find('[id]')){           // Means either it is a row or column with element(not blank column)
            panel_clone.find('[id]').each(function(index, el) { 
                var find = $(this).attr('id');
                var find_class = $(this).attr('class');
                if(find_class == 'rows'){ // if nested row 
                    $(this).find('> .column-set > div').each(function(index, el) {
                        if($(this).attr('data-parent')){
                            $(this).attr('data-parent',id);
                        }
                    });
                }
                var replace = get_random_string();
                $(this).attr('id',replace);
                $(this).find('> .layout-lis-item > .thwec-block-settings').html(function(i, oldHTML) {
                    return oldHTML.replace(find, replace);
                 });
                var builder_obj =  builder_clone.find('#tb_'+find);
                setup_clone_css(builder_obj,replace);
                builder_clone.find('#tb_'+find).attr('id','tb_'+replace);
            });
        }
        if(selected_panel_block_class[0] == 'rows'){
            clone_status = true;

        }else if(selected_panel_block_class[0] == 'columns'){
            clone_status = true; 
         
        }else if(selected_panel_block_class[0] == 'elements'){
            clone_status=true;
        }
        if(clone_status){
            $(builder_clone).insertAfter(selected_builder_block);
            $(panel_clone).insertAfter(selected_panel_block);
            if(selected_panel_block_class[0] == 'columns'){ 
                var cols = $('#'+parent_id).attr('data-columns');
                var col_count = parseInt(cols)+1;
                var remove_class = cols in NUMBER_TO_WORDS ? 'thwec-block-'+NUMBER_TO_WORDS[cols]+'-column' : 'thwec-block-n-column';
                var add_class = col_count in NUMBER_TO_WORDS ? 'thwec-block-'+NUMBER_TO_WORDS[col_count]+'-column' : 'thwec-block-n-column';
                // reset_row_attributes(parent_id,cols, col_count, 'id');
                $('#'+parent_id).attr('data-columns',col_count);
                $('#tb_'+parent_id).attr('data-column-count',col_count);
                $('#tb_'+parent_id).removeClass(remove_class).addClass(add_class);
                reset_col_width(parent_id, col_count);
            }
        }
    }  

    function setup_clone_css(builder_obj,replace){
        if(builder_obj.attr('data-css-props')){
            var builder_css = builder_obj.attr('data-css-props');
            builder_css = JSON.parse(builder_css);
            $.each(FONT_LIST, function(key, value) {
                if(key == builder_css['font_family']){
                    builder_css['font_family'] = value;
                }else if(key == builder_css['details_font_family']){
                    builder_css['details_font_family'] = value;
                }
            });
            if(builder_obj.attr('data-text-props')){
                var builder_text = builder_obj.attr('data-text-props');
            }
            if(builder_obj.attr('data-block-name')){
                var obj_type = builder_obj.data('block-name');
            }else if(builder_obj.hasClass('thwec-row')){
                var obj_type = 'row_clone';
            }else if(builder_obj.hasClass('thwec-col')){
                var obj_type = 'column_clone';
            }
            if( !$.isEmptyObject( builder_css ) ){
                $.each(builder_css, function(index, val) {
                    var props = {};
                    props[index] = val;
                    prepare_css_functions(index, props, replace, obj_type);
                });
            }
        }
    }
    /*------------------------------------------------------------------------------------------------------------------
    -------------------------------------- Additional Functions -------------------------------------------------------
    ------------------------------------------------------------------------------------------------------------------*/ 

    function remove_uploaded_image(elm){
        var upload_pointer = $(elm);
        var default_url = upload_pointer.siblings('.thwec-upload-preview').attr('data-default-url');
        upload_pointer.siblings('.thwec-upload-url').val(default_url);
        upload_pointer.siblings('.thwec-upload-url').trigger('keyup');

        upload_pointer.closest('.upload-action-settings').find('.thwec-upload-preview img').attr('src',default_url);
        upload_pointer.closest('.upload-action-settings').find('.thwec-upload-preview img').attr('src',default_url);
        var notice_tag = upload_pointer.closest('.upload-action-settings').siblings('.thwec-upload-notices');
        if(notice_tag.find('p').length > 0){
            notice_tag.find('p').remove();
        }
        upload_pointer.addClass('remove-upload-inactive');
    }

    function setup_image_uploader(elm,prop){ 
        var frame;
        var file_valid;
        var validation_msg;
        frame = wp.media({
            title: 'Upload Media Of Your Interest',
            button: {
                text: "Choose this" 
            },
            multiple: false  // Set to true to allow multiple files to be selected
        });
        frame.open();
        frame.on( 'select', function() { 
            // Get media attachment details from the frame state
            var thwec_attachment = frame.state().get('selection').first().toJSON();
            if(prop == 'gif'){
                file_valid = thwec_attachment['mime'] == 'image/gif' ? true : false;
                validation_msg = '<p>Invalid file type. Choose a Gif file</p>';
            }else{
                file_valid = thwec_attachment['type'] == 'image' &&  $.inArray(thwec_attachment['mime'], FILE_TYPES) !== -1 ? true : false;
                validation_msg = '<p>Invalid file type. Choose an image file <br>[ jpg, jpeg, png ]</p>';
            }
            var thwec_attachment_url = thwec_attachment['url'];
            var thwec_attachment_name = thwec_attachment['name'];
            var form_table = $('#thwec-sidebar-settings').find('form');
            var upload_block = form_table.find('.upload-action-settings.img-preview-'+prop);
            if(file_valid){
                if(prop=='bg_image'){
                    thwec_base.set_property_field_value(form_table, 'text', 'upload_bg_url', thwec_attachment_url, 0);
                    form_table.find('input[name="i_upload_bg_url"]').trigger('keyup');
                }else if(prop == 'image' || prop == 'gif'){
                    thwec_base.set_property_field_value(form_table, 'text', 'upload_img_url', thwec_attachment_url, 0);
                    form_table.find('input[name="i_upload_img_url"]').trigger('keyup');
                }
                upload_block.find('.thwec-upload-preview img').attr('src',thwec_attachment_url);
                if(upload_block.find('.remove-upload-btn').hasClass('remove-upload-inactive')){
                    upload_block.find('.remove-upload-btn').removeClass('remove-upload-inactive');
                }
                upload_block.closest('td').find('.thwec-upload-notices').html('');
            }else{
                upload_block.closest('td').find('.thwec-upload-notices').html(validation_msg);
            }
            
        }); 
    }


      /*----------------------------------------------
     *---- Sortable Content Fuctions - Start ------
     *----------------------------------------------*/

    function setup_template_builder(){        
        sortable_tracking_elements('div.thwec-builder-elm-layers', '.sortable-row-handle', '.rows', '.element-set,.thwec-builder-elm-layers', sortable_start_handler, sortable_out_handler, sortable_stop_handler, null, sortable_receive_handler, sortable_update_handler);
        sortable_tracking_elements('div.column-set', '.sortable-col-handle', '> div.columns', '', sortable_start_handler, sortable_out_handler, sortable_stop_handler, null, null, sortable_update_handler);
        sortable_tracking_elements('div.element-set', '.sortable-elm-handle', 'div.hooks,div.elements:not(.panel-add-btn)', '.element-set,div.thwec-builder-elm-layers', sortable_start_handler, sortable_out_handler, sortable_stop_handler, null, null, sortable_update_handler);
    }
    
    function sortable_tracking_elements(elm, handle, items, connectWith, start_handler, out_handler, stop_handler, over_handler, receive_handler, update_handler){
        $(elm).sortable({
            handle: handle,
            axis:'x,y',
            items: items,
            scroll: true,
            cursor: "move",
            helper:"clone",
            placeholder: "sortable-row-placeholder",
            connectWith: connectWith,
            forcePlaceholderSize: true,
            start: start_handler,
            out: out_handler,
            over: over_handler,
            stop: stop_handler,
            receive: receive_handler,
            update: update_handler
        });
        $(elm).disableSelection();
    }


    function sortable_start_handler(event, ui){
        DRAGG_CLASS = ui.item.attr('class');
        DRAGG_CLASS = $.trim(DRAGG_CLASS.replace('panel-builder-block',''));
            $(ui.helper).addClass('dragg');
            if(DRAGG_CLASS == 'rows'){
                ui.helper.find('.column-set').css('display','none');
                ui.helper.css('height','unset');
            }else if(DRAGG_CLASS == 'columns'){  
                ui.helper.find('.element-set').css('display','none');
                ui.helper.css('height','unset');
                ui.placeholder.addClass('ui-sortable-placeholder-columns');
            }else if(DRAGG_CLASS == 'elements' || DRAGG_CLASS == 'hooks'){
                ui.placeholder.addClass('ui-sortable-placeholder-element-hooks');
            }
    }

    function sortable_out_handler(event, ui){
        if(DRAGG_CLASS == 'rows' || DRAGG_CLASS == 'elements'){
            if(ui.item.closest('.columns').length > 0){
                var prev_parent_id = ui.item.closest('.columns').attr('id');
                ui.item.data('prev-parent',prev_parent_id);
            }
        }
    }

    function sortable_stop_handler(event, ui){ 
        DRAGG_CLASS == '';
        $(ui.helper).removeClass('dragg');
        if($(this).hasClass('elements') && ui.item.hasClass('elements')){
        }
    }
    
    function sortable_over_handler(event, ui){
    }

    function sortable_receive_handler(event, ui){
       if($(this).hasClass('thwec-builder-elm-layers') && (DRAGG_CLASS == 'elements' || DRAGG_CLASS == 'hooks')){
            ui.placeholder.hide();
            ui.sender.sortable('cancel');  
            alert('Cannot place an element outside a column');
        }
    }
    
    function sortable_update_handler(event, ui){
        var track_id = 'tb_'+ui.item.attr('id');
        var next_id =  ui.item.next().attr('id');
        var prev_id =  ui.item.prev().attr('id');
        if(prev_id){
            prev_id = 'tb_'+prev_id;
            $( $('#tb_temp_builder').find('#'+track_id) ).insertAfter($('#'+prev_id));
        }else{;
            next_id = 'tb_'+next_id;
            $( $('#tb_temp_builder').find('#'+track_id) ).insertBefore($('#'+next_id));
        }
        if(ui.item.closest('.element-set').closest('.columns').length > 0){
                          
            var prev_parent = ui.item.data('prev-parent'); 
            var current_parent = ui.item.closest('.element-set').closest('.columns').attr('id');
            var track_id = ui.item.attr('id');
            var next_id =  ui.item.next().attr('id');
            var prev_id =  ui.item.prev().attr('id');
            if(ui.item.closest('.columns').attr('id')){
                var column_id = ui.item.closest('.columns').attr('id');
            }
            var data = $('#tb_temp_builder').find('#tb_'+track_id);
            if(prev_id){
                $(data).insertAfter($('#tb_'+prev_id));
            }else if(next_id){
                $(data).insertBefore($('#tb_'+next_id));
            }
            else if(!next_id && !prev_id){
                $('#tb_temp_builder').find('#tb_'+column_id).html(data);
            }
            var elem = $('#tb_'+prev_parent);
            if(elem.find('.builder-block').length < 1 && elem.find('.hook-code').length <1){
               $('#tb_'+prev_parent).html(BLANK_TD_DATA);
            }
        }
    }

    function get_style_property_name( key ){
        return key in CSS_PROPS ? CSS_PROPS[key]['name'] : false;
    }

    /*----------------------------------------------
     *---- Sortable Content Fuctions - END ---------
     *----------------------------------------------*/

    return {
        initialize_tbuilder : initialize_tbuilder,
        open_builder_block_edit_sidebar : open_builder_block_edit_sidebar,
        setup_category_toggle_functions : setup_category_toggle_functions,
        builder_block_delete : builder_block_delete,
        confirm_builder_block_clone : confirm_builder_block_clone,
        prepare_css_functions : prepare_css_functions,
        clear_tbuilder : clear_tbuilder,
        setup_image_uploader : setup_image_uploader,
        template_action_add_row : template_action_add_row,
        template_sidebar_nav_back : template_sidebar_nav_back,
        remove_uploaded_image : remove_uploaded_image,
        remove_builder_panel_highlights : remove_builder_panel_highlights,
        get_style_property_name : get_style_property_name,
        save_additional_css : save_additional_css
    };
}(window.jQuery, window, document));    

function thwecBuilderBlockEdit(elm, blockId, blockName){
    thwec_tbuilder.open_builder_block_edit_sidebar(elm, blockId, blockName);    
}

function thwecClearTemplateBuilder(elm){
    thwec_tbuilder.clear_tbuilder(elm);
}

function thwecBuilderBlockDelete(elm){
    thwec_tbuilder.builder_block_delete(elm);    
}
function thwecBuilderBlockClone(elm){
    thwec_tbuilder.confirm_builder_block_clone(elm);    
}

function thwecImageUploader(elm,prop){
    thwec_tbuilder.setup_image_uploader(elm,prop);
}

function thwecTActionAddRow(elm){
    thwec_tbuilder.template_action_add_row(elm);
}

function thwecSidebarNavigateBack(elm){
    thwec_tbuilder.template_sidebar_nav_back(elm);
}

function thwecCategoryCollapse(elm){
    thwec_tbuilder.setup_category_toggle_functions(elm);
}

function thwecRemoveUploadedImg(elm){
    thwec_tbuilder.remove_uploaded_image(elm);
}

function thwecSaveAdditionalCss(elm){
    thwec_tbuilder.save_additional_css(elm);
}

var thwec_settings = (function($, window, document) {
  	'use strict';
  	var dataid;
  	var JSON_TREE = {}
	var TEST_MAIL_VALIDATE_NOTICE = '<span>Please enter a valid email id</span>';
    var LANG_SWITCHED = false;
    var T_DUPLICATE = false;
	/*------------------------------------
	*---- ON-LOAD FUNCTIONS - SATRT ----- 
	*------------------------------------*/
	$(function() {
		var settings_form = $('#thwec_settings_fields_form');
		initialize_thwec();
	});
	/*------------------------------------
	*---- ON-LOAD FUNCTIONS - END -------
	*------------------------------------*/
	

	function initialize_thwec(){
        thwec_base.setup_tiptip_tooltips();
        thwec_tbuilder.initialize_tbuilder();
        setup_reload_functions();
        delete_template_button();

        var TEMPLATE_AJAX_LOAD = $('#thwec-ajax-load-modal');
        $('#email_template_manager_table .thwec-template-manage-tabs').click(function(){
            if( !$(this).hasClass('thwec-template-manage-active') ){
                var tab = $(this).attr('data-name');
                var content = tab == 'manage' ? $('#thwec_manage_template_table tbody').html() : $('#thwec_map_template_table tbody').html();
                $('#email_template_manager_table > tbody').html( content );
                if( tab == 'mapping' ){
                    setup_select2_template_map();
                }else{
                    setup_select2_template_map( true );
                }
                $('#thwec_template_manager').find('input[name="thwec_notification_tab"]').val(tab);
                $(this).siblings('.thwec-template-manage-active').removeClass('thwec-template-manage-active');
                $(this).addClass('thwec-template-manage-active');
            }
        });

         $('#email_template_manager_table').on('click', '.thwec-template-map-title', function(event) {
            if( $(this).closest('.template-map-single-row').hasClass('thwec-map-toggle-active') ){
                $(this).closest('.template-map-single-row').removeClass('thwec-map-toggle-active');
            }else{
                $(this).closest('.template-map-single-row').addClass('thwec-map-toggle-active');
                $(this).find('h4').removeClass('thwec-map-title-decoration');
            }
        });

        $('#thwec_wpml_lang').on('click', function(event) {
            var switcher = $(this);
            switcher.data( 'lang-previous', switcher.val() );
        }).on('change', function(event) {
            var lang_switcher = $(this);
            var lang = lang_switcher.val();
            var template = $('#template_save_name').val();
            if( lang && template){
                if( tbuilder_pending_save() ){
                    var prev_lang = lang_switcher.data('lang-previous');
                    if(  prev_lang && prev_lang != lang ){
                        lang_switcher.val( prev_lang );
                    }
                    alert('Save the template before switching template.');
                }else{
                    initiate_lang_switch(this);
                    $("#wpml_lang_selector").submit();
                }
            }
        });
    }

    function initiate_lang_switch( elm ){
        $(elm).parents('.thwec-header-icons').addClass('thwec-lang-switching');
    }

    function setup_select2_template_map( destroy ){
        if( destroy ){
            $('.thwec-template-map-select2.select2-hidden-accessible').select2('destroy');
        }else{
            $('.thwec-template-map-select2').select2();
        }
    }

    /*------------------------------------
    *---- TEST MAIL FUNCTIONS - START ----
    *------------------------------------*/

    function send_test_mail( elm ){
        var button = $(elm);
        var email_id = button.siblings('input[name="test_mail_input"]').val();
        var validation_box = button.closest('.thwec-test-mail-modal').siblings('.thwec-action-validation-box');
        var email_validation = test_mail_validations( email_id );
        if( email_validation['valid'] ){
            validation_box.html('');
            set_preview_template_content(false);
            var test_mail_data = $('#thwec_tbuilder_editor_preview').html();
            var css = $('#thwec_template_css').html();
            css = css+$('#thwec_template_css_preview_override').html();
            var additional_css = $('#thwec_template_css_additional_css').length ? $('#thwec_template_css_additional_css').html() : "";
            prepare_test_email_ajax(test_mail_data, css, additional_css, email_id, validation_box);
        }else{
            var error_msg = '';
            $.each(email_validation['errors'], function( key, value ) {
                error_msg += prepare_validation_html( value, email_validation['valid'] );
            });
            validation_box.html( error_msg );
        }
    }

    function test_mail_validations(mail_id){
    	var validation_set = {};
    	var error_msg = [];
    	validation_set.valid = true;
    	var builder_valid = true;
    	var email_valid = true;
        var split_pattern = mail_id.split("@");
        if(split_pattern){
            var validation0 = split_pattern[0];
            var validation1 = split_pattern[1];
            // if no @ in email id then validation1 is null
            //if no string after @ in email id, then validation1 is false
            if(validation1 != '' && validation1 != null){
                var valid_split = validation1.split(".");
                var validation20 =valid_split[0];
                var validation21 =valid_split[1];
                
                if(validation21 != null && validation21 !=''){
                    email_valid = true;
                }else{
                    email_valid = false;
                    error_msg.push(TEST_MAIL_VALIDATE_NOTICE);
                }
               
            }else{
                email_valid = false;
                error_msg.push(TEST_MAIL_VALIDATE_NOTICE);
            }
        }
        if($('#tb_temp_builder').find('.builder-block').length == 0){
            error_msg.push('Add contents to the builder');
            builder_valid = false;
        }
       

    	validation_set.valid = email_valid && builder_valid ? true : false;
    	validation_set.errors = error_msg;
    	return validation_set;
      
    }

    function prepare_test_email_ajax(mail_data, mail_css, add_css, mail_id, msg_box){
        var template_ajax_load = $('#thwec-ajax-load-modal');
        var test_data = {
            action : 'thwec_save_email_template',
            thwec_action_index : 'settings',
            test_mail_data: mail_data,
            test_mail_css: mail_css,
            test_mail_add_css : add_css,
            test_mail_id: mail_id
        };
        $.ajax({
            type: 'POST',
            url: ajaxurl,                        
            data: test_data,
            beforeSend: function(){
                template_ajax_load.addClass("thwec-ajax-loading");
            },
            success:function(data){
                if(data == 'success'){
                    msg_box.html( prepare_validation_html( 'Email sent successfully', true ) );
                }
            },
            complete: function() {
                template_ajax_load.removeClass("thwec-ajax-loading");
            },
            error: function(){
                alert('error');
            }
        });
    }

    /*------------------------------------
    *---- TEST MAIL FUNCTIONS - END ------
    *------------------------------------*/

    function create_new_template(){
    	location.reload();
    	prepare_page_reload();
    }

    function setup_reload_functions(){

    	window.addEventListener("beforeunload", function (event) {
    		prepare_page_reload();
		});
    }

    function prepare_page_reload(){
        if( tbuilder_pending_save() ){
            event.returnValue = "\o/";
        }else{
            return ;
        }
    }

    function tbuilder_pending_save(){
        var builder_obj = $('#tb_temp_builder');
        var block_length = builder_obj.find('.builder-block').length;
        var data_track = builder_obj.attr('data-track-save');
        var data_global = builder_obj.attr('data-global-id');
        var data_css = builder_obj.attr('data-css-change');
        var data_sidebar = builder_obj.attr('data-sidebar-change');
        if(builder_obj.length > 0 && block_length > 0 && data_track != data_global){
            //New contents added to builder since last save
            return true;

        }else if(data_css=='false'){//Pending CSS changes to be saved
            return true;

        }else if(data_sidebar=='false'){//Elements deleted from sidebar
            return true;

        }
        else{//All clear
            return false;
        }
    }

    function prepare_validation_html( message, valid ){
        var html = '';
        var valid_class = valid ? 'thwec-validation-success' : 'thwec-validation-failed';
        html+= '<p class="'+valid_class+'">'+message+'</p>';
        return html;
    }

    function performTemplateNameValidations( tname, template_builder, popup ){
        var validation_box = $('#thwec_builder_header_action_box .thwec-action-validation-box');
        var template_name_valid = true;
        var blank_builder_valid = true;
        var block_length = template_builder.find('.thwec-block').length;
        var hook_length = template_builder.find('.hook-code').length;
        validation_box.html('');
        
        var input_validation = custom_tname_validation( tname, 'Template' );
        template_name_valid = input_validation.length === 0 ? true : false;
        blank_builder_valid = block_length > 0 || hook_length > 0 ? true : false;
        validation_box.siblings('.thwec-validation-special-char').removeClass('thwec-validation-failed');
        if(!blank_builder_valid){
            if( !popup ){
                alert('Add elements to save the template');
            }else{
                validation_box.html( prepare_validation_html( 'Add elements to save the template', blank_builder_valid ) );
            }
        }            
        else if( !template_name_valid ){
            $.each(input_validation, function( key, value ) {
                if( value['error'] == 'special_char' ){
                    validation_box.siblings('.thwec-validation-special-char').addClass('thwec-validation-failed');
                }else{
                    validation_box.append( prepare_validation_html( value['message'], template_name_valid ) );
                }
            });
        }
        return template_name_valid && blank_builder_valid;
    }

    function initiateTemplateSave(elm, tname, popup ){
        var template_builder = $('#tb_temp_builder');
        var tname = $('#template_save_name').val();
        var disp_name_input = $(elm).siblings('.thwec-template-display-name');
        var disp_name = disp_name_input.val();

        if( tname && disp_name ){
            // Check if duplicating template.
            if( tname != disp_name ){
                var result = confirm( 'You have changed the template name. Do you want to create a duplicate ?' );
                if( result ){
                    T_DUPLICATE = true;
                    tname = disp_name; 
                }else{
                    return;
                }
            }
        }else if( !tname ){
            // Saving template for the first time.
            tname = disp_name;
        }else if( !disp_name ){
            //Field left blank
            disp_name_input.val(tname);
        }
        
        var tn_validated = performTemplateNameValidations( tname, template_builder, popup );
        if( tn_validated ){ // Condition for Template Save Passed
            var tcontent = $('#template_drag_and_drop').innerHTML;
            var tcss = $('#thwec_template_css').html();
            var json_tree = create_json_layout_structure();
            
            template_builder.attr('data-track-save',template_builder.attr('data-global-id'));
            prepare_template_content(tname, tcontent, tcss, json_tree);
            template_builder.attr('data-css-change','true');
            template_builder.attr('data-sidebar-change','true');
        }
        
    }

    function create_json_layout_structure(){
    	var layer_data = $('.thwec-tbuilder-elm-wrapper').find('.thwec-sidebar-config-elm-layers .thwec-builder-elm-layers');
    		var row_count = 0;
    		var row_struct={};
    		var row_array = [];
    	layer_data.find('> .rows.panel-builder-block').each(function(index, el) {
    		var row_data = {};
    		var col_struct={};
    		var col_array = [];
    		$(this).find('> .column-set > .columns.panel-builder-block').each(function(index, el) {
    			var col_data = {};
    			var elm_struct={};
    			var elm_array = [];
    			$(this).find('> .element-set > .panel-builder-block').each(function(index, el) {
    				var elm_data = {};
    				if($(this).hasClass('rows')){
    					var data = create_json_rows($(this));
    					elm_array.push(data);
    				}else if($(this).hasClass('elements')){
    					elm_data.data_id = $(this).attr('id');
    					elm_data.data_type = 'element';
    					elm_data.data_name = $(this).find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
                        elm_data.data_css = $('#tb_'+$(this).attr('id')).attr('data-css-props');
    					elm_data.data_text = $('#tb_'+$(this).attr('id')).attr('data-text-props');
    					elm_data.child = $(this).find('.element-name').text();
                        elm_data.data_css_compat = $('#tb_'+$(this).attr('id')).attr('data-css-compat');
    					elm_array.push(elm_data);
    				}else{
                        elm_data.data_id = $(this).attr('id');
                        elm_data.data_type = 'hook';
                        var hook_names = $(this).find('.hook-name').text();
                        var hook_suffix = hook_names == 'Downloadable Product' ? 'table' : 'hook';
                        hook_names = hook_names+'_'+hook_suffix;
                        elm_data.data_name = hook_names.toLowerCase().replace(/\s+/g, "_");
                        elm_data.child = $(this).find('.hook-name').text();
                        elm_array.push(elm_data);
                    }
    			});

    			col_data.data_id = $(this).attr('id');
    			col_data.data_type = 'column';
    			col_data.data_name = $(this).find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
    			col_data.data_css = $('#tb_'+$(this).attr('id')).attr('data-css-props');
    			col_data.child = elm_array;
    			col_array.push(col_data);
    		});
    		row_data.data_id = $(this).attr('id');
    		row_data.data_type = 'row';
    		row_data.data_name = $(this).find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
            row_data.data_css = $('#tb_'+$(this).attr('id')).attr('data-css-props');
    		row_data.data_text = $('#tb_'+$(this).attr('id')).attr('data-text-props');
    		row_data.data_count = $('#tb_'+$(this).attr('id')).attr('data-column-count');
            row_data.child = col_array;
    		row_array.push(row_data); 
    	});
    	row_struct.row = row_array;
        row_struct.data_id = 'temp_builder';
        row_struct.data_type = 'builder';
    	row_struct.track_save = $('#tb_temp_builder').attr('data-global-id');
        row_struct.data_css = $('#tb_temp_builder').attr('data-css-props');
    	var json_row = JSON.stringify(row_struct);
    	return json_row;
    }

    function create_json_rows(elm){
    	var row_struct={};
    	var row_data = {};
    	var row_array = [];
    	var col_array = [];
    	elm.find('> .column-set > .columns.panel-builder-block').each(function(index, el) {
    		var col_data = {};
    		var elm_struct={};
    		var elm_array = [];
    		$(this).find('> .element-set > .panel-builder-block').each(function(index, el) {
    			var elm_data = {};
    			if($(this).hasClass('rows')){
    				var data = create_json_rows($(this));
                    elm_array.push(data);
    			}else if($(this).hasClass('elements')){
    				elm_data.data_id = $(this).attr('id');
    				elm_data.data_type = 'element';
    				elm_data.data_name = $(this).find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
                    elm_data.data_css  = $('#tb_'+$(this).attr('id')).attr('data-css-props');
    				elm_data.data_text = $('#tb_'+$(this).attr('id')).attr('data-text-props');
                    elm_data.child = $(this).find('.element-name').text();
                    elm_data.data_css_compat = $(this).attr('data-css-compat');
    				elm_array.push(elm_data);
    			}else{
                    elm_data.data_id = $(this).attr('id');
                    elm_data.data_type = 'hook';
                    var hook_names = $(this).find('.hook-name').text();
                    var hook_suffix = hook_names == 'Downloadable Product' ? 'table' : 'hook';
                    hook_names = hook_names+'_'+hook_suffix;
                    elm_data.data_name = hook_names.toLowerCase().replace(/\s+/g, "_");
                    elm_data.child = $(this).find('.hook-name').text();
                    elm_array.push(elm_data);
                }
    		});
    		col_data.data_id = $(this).attr('id');
    		col_data.data_type = 'column';
            col_data.data_name = $(this).find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
    		col_data.child = elm_array;
            col_data.data_css = $('#tb_'+$(this).attr('id')).attr('data-css-props');
    		col_array.push(col_data);
    	});
    	row_data.data_id = elm.attr('id');
        row_data.data_type = 'row';
        row_data.data_name = elm.find('.thwec-block-settings .template-action-edit').attr('data-icon-attr');
        row_data.data_css = $('#tb_'+elm.attr('id')).attr('data-css-props');
    	row_data.data_text = $('#tb_'+elm.attr('id')).attr('data-text-props');
        row_data.data_count = $('#tb_'+elm.attr('id')).attr('data-column-count');
    	row_data.child = col_array;
    	row_array.push(row_data); 
    	row_struct.row = row_array;
    	return row_struct;
    }

    function custom_tname_validation(tname,dependend){
        var validate = [];
        var validation_set = [];
        var valid = true;
    	if(tname==''){
            validate['status'] = false;
            validate['validate_flag'] = 'name';
            validate['message'] = dependend+' name is empty';
            validation_set.push(validate); 
    	}
        if(dependend == 'Hook'){
            if(/^[a-zA-Z_]*$/.test(tname) == false) {
                validate['status'] = false;
                validate['validate_flag'] = false; 
                validate['message'] = 'Use only letters ([a-z],[A-Z]) and underscores ("_") for template name'; 
                validation_set.push(validate); 
            } 
        }else if(dependend == 'Template'){
            if(/^[a-zA-Z0-9-_ ]*$/.test(tname) == false) {
                validate = [];
                validate['status'] = false;
                validate['error'] = 'special_char';
                validate['validate_flag'] = false;
                validate['message'] = 'Use only letters ([a-z],[A-Z]), digits ([0-9]), hyphen ("-") and underscores ("_") for template name'; 
                validation_set.push(validate); 
            }
            if( tname.length > 100 ){
                validate = [];
                validate['status'] = false;
                validate['validate_flag'] = false;
                validate['message'] = 'Template name cannot be longer than 100 characters'; 
                validation_set.push(validate); 
            }
            if(/^([_|-]+)$/.test(tname) == true) {
                validate = [];
                validate['status'] = false;
                validate['validate_flag'] = false;
                validate['message'] = 'Template name must contain atleast one alphabet or number.'; 
                validation_set.push(validate); 
            }
            if(tname.startsWith("_") || tname.startsWith("-")) {
                validate = [];
                validate['status'] = false;
                validate['validate_flag'] = false;
                validate['message'] = 'Template name must begin with an Alphabet or number'; 
                validation_set.push(validate); 
            }
        }
        return validation_set;
    }

    function prepare_template_content(name, content, css, json_tree){
        update_template_name( name )
		var render_hooks = true;
		set_preview_template_content(render_hooks);

		var template_data = $('#thwec_tbuilder_editor_preview').html();
		var content_raw = $('.thwec-tbuilder-wrapper').wrap('<p/>').parent().html();
		$('.thwec-tbuilder-wrapper').unwrap();
		var css_cleaned = css+$('#thwec_template_css_preview_override').html();
        var additional_css = $('#thwec_template_css_additional_css').length ? $('#thwec_template_css_additional_css').html() : "";
		ajax_call_save_data(name, content_raw, template_data, css_cleaned, additional_css, json_tree);
    }

    function update_template_name( name ){
        var template_name = $('#template_save_name');
        template_name.attr('value', name);
        var format_name = name.replace(/\s+/g, '_').toLowerCase();
        if( name ){
            var template_meta = $('.thwec-header-icons-holder.thwec-meta-display-box');
            template_meta.removeClass('thwec-empty-name');
            template_meta.find('.text-content').html(name);
        }
        if( format_name ){
            $('#template_format_name').val(format_name);
        }
    }

    function ajax_call_save_data(name, content_raw, content_cleaned, css_cleaned, additional_css, json_tree){
    	var template_ajax_load = $('#thwec-ajax-load-modal');
        var sample_data = {
            action: 'thwec_save_email_template',
            thwec_action_index : 'settings',
            template_name: name,
            template_edit_data: content_raw,
            template_render_data:  content_cleaned,
            template_render_css: css_cleaned,
            template_add_css : additional_css,
            template_json_tree : json_tree,
            template_lang : $('#thwec_wpml_lang').val(),
        };
        $.ajax({
            type: 'POST',
            url: ajaxurl,                        
            data: sample_data,
            beforeSend: function(){
            	template_ajax_load.addClass("thwec-ajax-loading");
                $('#thwec_builder_save_messages').html('').removeClass("thwec-show-save thwec-save-success");
                $('#thwec-template-builder-wrapper').addClass('thwec-template-save-active');
            },
            success:function(data){
                var msg = 'Template saved';
                if( T_DUPLICATE ){
                    T_DUPLICATE = false;
                    msg = 'Template Duplicate created successfully';
                }
                $('#thwec_builder_save_messages').html(msg).addClass('thwec-show-save thwec-save-success');
                setTimeout(function(){
                    $('#thwec_builder_save_messages').removeClass("thwec-show-save thwec-save-success");
                },3000);
                
            },
            complete: function() {
            	template_ajax_load.removeClass("thwec-ajax-loading");
                close_modal();
                $('#thwec-template-builder-wrapper').removeClass('thwec-template-save-active');
            },
            error: function(){
                 $('#thwec_builder_save_messages').html('Something went wrong').addClass('thwec-show-save thwec-save-error');
                 setTimeout(function(){
                            $('#thwec_builder_save_messages').removeClass("thwec-show-save thwec-save-error");
                        },3000);
            }
        });
    }

    function toggle_builder_preview_tab( elm ){
        var tab_elm = $(elm);
        var tab = tab_elm.data('tab');
        var active_tab_elm = tab_elm.closest('.thwec-header-icons').find('.active-tab');
        var active_tab = active_tab_elm.data('tab');
        thwec_tbuilder.remove_builder_panel_highlights();
        if( tab != active_tab){
            if( tab == 'builder' ){
                switch_to_builder();
            }else if( tab == 'preview' ){
                if($('#tb_temp_builder').find('.thwec-block').length > 0){
                    switch_to_preview();
                }else{
                    alert('Nothing to Preview');
                    return;
                }
            }
            active_tab_elm.removeClass('active-tab');
            tab_elm.addClass('active-tab');
        }
        
    }

    function switch_to_preview(){
        hide_builder_sidebar();
        var show_hooks = false;
        set_preview_template_content(show_hooks); // Check order of functions
    }

    function switch_to_builder(){
        $('#thwec-template-builder-wrapper').removeClass('thwec-preview-active');
        $('#wpfooter').show();
        $('#thwec_tbuilder_editor_preview').hide();
    }

    function hide_builder_sidebar(){
        $('#thwec-template-builder-wrapper').addClass('thwec-preview-active');
        $('#wpfooter').hide();
        $('#thwec_tbuilder_editor_preview').show();
    }

    function prepare_custom_hook_function(block_elm, block_name){
        if(block_name == 'custom_hook'){
            block_elm.replaceWith('[WECM_CUSTOM_HOOK name="'+block_elm.find('.hook-text').text()+'"]');
            block_elm.attr('class','thwec-custom-hook');
            block_elm.removeAttr('data-block-name');
            block_elm.removeAttr('data-text-props');
            block_elm.removeAttr('id');
        }
    }

	function set_preview_template_content(show_hooks){
		var preview_html = $('#tb_temp_builder').clone(false);
		clean_preview_panel(preview_html);
		preview_html.find('.btn-add-element').remove();
		preview_html.find('.builder-block').each(function(index, el) {
			var block_name = $(this).data('block-name');
            if(show_hooks){
    			set_contents_hooks_and_data($(this), block_name);
                prepare_custom_hook_function($(this),block_name);
			}else{
                prepare_content_hooks_for_test_mail($(this), block_name);
            }
            
			if($(this).attr('id')){
				var id = $(this).attr('id');
				id = id.replace('tb_','tp_');
				$(this).attr('id',id);
			}
			if($(this).hasClass('thwec-row')){
				$(this).find(' tbody> tr > .thwec-columns').each(function(index, el) {
					var id = $(this).attr('id');
					id = id.replace('tb_','tp_');
					$(this).attr('id',id);
				});
			}
        });
		preview_html.find('.builder-block').removeClass('builder-block');
		preview_html.find('[data-css-props]').each(function(index, el) {
			$(this).removeAttr('data-css-props');
            if($(this).attr('data-text-props')){
                $(this).removeAttr('data-text-props');
            }
		});
        preview_html.removeAttr('data-css-props');
        preview_html.removeAttr('data-global-id');
        preview_html.removeAttr('data-track-save');
        preview_html.removeAttr('data-css-change');
		$('#thwec_tbuilder_editor_preview').html(preview_html);
	}

	function clean_preview_panel(elm){
		elm.find('p.hook-code').each(function(index, el) {
			$(this).removeAttr('id');
		});
		elm.attr('id',elm.attr('id').replace('tb_','tp_'));
		elm.removeClass('thwec-dropable sortable ui-sortable ui-droppable');
		elm.find('.thwec-icon-panel').remove(); // Removing all icon panels of rows
		elm.find('.thwec-columns .dashicons-edit').remove(); // Removing all icon panels inside columns
		elm.find('input[type="hidden"]').remove(); // Removing all hidden fields
		elm.find('.thwec-columns').css('min-height','0');
		elm.find('.ui-sortable').removeClass('ui-sortable ui-droppable');
	}


	function clean_block_layout(elm){
		var prev_elm_id = elm.attr('data-prev-elm');
		var content_elm = elm.find('> .thwec-row');

		if(content_elm.length){
			content_elm.attr('id', prev_elm_id);
			content_elm.unwrap();
		}
	}

	function clean_block_element(elm){
		var prev_elm_id = elm.attr('data-prev-elm');
		var content_elm = elm.find('> .thwec-block');

		if(content_elm.length){
			content_elm.attr('id', prev_elm_id);
			content_elm.unwrap();
		}
	}

    function prepare_content_hooks_for_test_mail(block_elm, block_name){
        if(block_name == 'order_details'){
            block_elm.find('.item-loop-start').remove();
            block_elm.find('.item-loop-end').remove();
            block_elm.find('.order-total-loop-start').remove();
            block_elm.find('.order-total-loop-end').remove();
        }
    }

    function ot_loop_end_html( block_elm ){
        var inline_css = '';
        var styles = block_elm.find('tr.order-total-loop-end').attr('data-ot-css');
        if( styles ){
            styles = $.parseJSON(styles);
            $.each( styles, function(key, val) {
                var property = thwec_tbuilder.get_style_property_name( key );
                if( property ){
                    inline_css +=  property+':'+val+';';
                }
                
            });
        }
        var content = "[WECM_ORDER_TD_CSS styles='"+inline_css+"']";
        return content;
    }

	function set_contents_hooks_and_data(block_elm, block_name){
		if(block_name == 'order_details'){
			
            var order_heading = block_elm.find('.thwec-order-heading .order-title').html();
            block_elm.find('.thwec-order-heading').html('[WECM_ORDER_T_HEAD text="'+order_heading+'"]');
			block_elm.find('.thwec-order-table .woocommerce_order_item_class-filter2').remove();
			block_elm.find('.thwec-order-table .order-footer .order-footer-row:gt(0)').remove();
			
			if(block_elm.find('.thwec-order-item-img').hasClass('show-product-img')){
				block_elm.find('.order-item').html('{order_items_img}');
			}else{
				block_elm.find('.order-item').html('{order_items}');
			}

			block_elm.find('.order-item-qty').html('{order_items_qty}');
			block_elm.find('.order-item-price').html('{order_items_price}');
			block_elm.find('.order-total-label').html('{total_label}');
			block_elm.find('.order-total-value').html('{total_value}');
			
			block_elm.find('.order-head').each(function(index, el) {
				$(this).html('{Order_'+$(this).text()+'}');
			});
            var id = block_elm.attr('id');
            block_elm.find('tr.order-total-loop-end').replaceWith( ot_loop_end_html( block_elm ) );
		}

		if(block_name == "billing_address"){
			calculate_hook_position(block_elm,'{billing_address}','thwec-billing-body',false,false);
		}
		if(block_name == "shipping_address"){
			calculate_hook_position(block_elm,'{shipping_address}','thwec-shipping-body',false,false);
            additional_support_functions(block_elm,block_name);
		}
		if(block_name == "customer_address"){
			calculate_hook_position(block_elm,'{customer_hook}',null,true,false);
			calculate_hook_position(block_elm,'{customer_address}','thwec-customer-body',false,false);
		}
	}

    function additional_support_functions($elm,$name){
        if($name == 'shipping_address'){
            var shipping_table = $elm.find('> tbody > tr > td');
            shipping_table.prepend('<span>{thwec_before_shipping_address}</span>');
            shipping_table.append('<span>{thwec_after_shipping_address}</span>');
        }
    }

	function calculate_hook_position($obj,$hook_name,$class_name,$control,$position){
		var elm_blk = $obj.closest('.thwec-element-block');
		var elm_col = $obj.closest('.thwec-columns');
		var row_col = $obj.closest('.column_layout');
		var insert = '';

		if($control){
			if(elm_blk.siblings().length){
				insert = elm_blk;
			}else if(elm_blk.closest('.column-padding').length){
				insert = elm_col;
			}else{
				insert = row_col;
			}

			if($position){
				$('<span>'+$hook_name+'</span>').insertAfter(insert);          
			}else{
				$('<span>'+$hook_name+'</span>').insertBefore(insert); 
			}
		}
		else{
			$obj.find('.'+$class_name).html('<span>'+$hook_name+'</span>'); 
		}
	}

	/*--------------------------------------------------
	 *------- Functions of Add/Edit Template Page ------
	 *--------------------------------------------------*/
    function edit_template_change_listner(elm){
    	var form = $('#thwec_edit_template_form');
    	var template_to_edit = thwec_base.get_property_field_value(form, 'select', 'edit_template');
        if(template_to_edit==''){
          	alert('Select a template to edit');
          	event.preventDefault();
        } else {
            var optgroup = get_optgroup_label( form );
            if( form.find('input[name="i_template_type"]').length ){
                form.find('input[name="i_template_type"]').val(optgroup);
            }else{
                form.append('<input type="hidden" name="i_template_type" value="'+optgroup+'">');
            }
        }
    }

    function delete_template_button(){
    	$('#delete_template').click(function(event) {
    		var form = $('#thwec_edit_template_form');
			var value = thwec_base.get_property_field_value(form, 'select', 'edit_template');
    		if(value == ''){
    			alert('Select template to delete');
    			event.preventDefault();
    		}else{
                var optgroup = get_optgroup_label( form );
                if( optgroup == 'sample' ){
                    alert('Sample templates cannot be deleted' );
                    event.preventDefault();
                }else{
                    var delete_option = confirm('Delete the selected template ?');
                    if(!delete_option ){
                        event.preventDefault();
                    }
                }
    		}
    	});
    }

    function get_optgroup_label( form ){
        var optgroup = form.find('select[name="i_edit_template"] :selected').closest('optgroup').attr('label');
        optgroup = optgroup == 'Sample Templates' ? 'sample' : 'user';
        return optgroup;
    }

    function template_map_validation(event, elm){
        return false;
    }

    function open_modal( elm, flag ){
        var content = $('#thwec_modal_'+flag).html();
        var modal_box = $('#thwec_builder_header_action_box');
        var template_name = $('#template_save_name').val();
        thwec_tbuilder.remove_builder_panel_highlights();
        modal_box.find('.content-box').html(content);
        if( template_name ){
            modal_box.find('.thwec-template-display-name').val( template_name );
        }
        modal_box.addClass('thwec-modal-active');
    }

    function close_modal( elm ){
        $('#thwec_builder_header_action_box').removeClass('thwec-modal-active');
    }

    function save_template_event( elm ){
        var template_name = $('#template_save_name').val();
        if( template_name ){
            initiateTemplateSave( elm, template_name, false );
        }else{
            open_modal(elm, 'template_name');
        }
    }

    function edit_template_name(elm){
        open_modal(elm, 'template_name');
    }

    function map_form_toggle( elm ){
        var button = $(elm);
        var button_class = button.attr('class');
        var map_table = $('#email_template_manager_table .thpladmin-form-email-notification-table');
        if( button_class == 'thwec-close-all' ){
            map_table.find('.thwec-map-toggle-active').removeClass('thwec-map-toggle-active');
            button.text('Expand all');
            button.removeClass('thwec-close-all').addClass('thwec-open-all');
        }else if( button_class == 'thwec-open-all' ){
            map_table.find('.template-map-single-row ').addClass('thwec-map-toggle-active');
            button.text('Collpase all');
            button.removeClass('thwec-open-all').addClass('thwec-close-all');
        }
    }

    function delete_template(){
        if( thwec_var.wpml_active && !thwec_var.template_filter ){
            if( !confirm('All created translations of this template will be deleted. Proceed ?' ) ){
                event.preventDefault();
            }
        }
    }

    return {
        initiateTemplateSave : initiateTemplateSave,
        custom_tname_validation : custom_tname_validation,
        send_test_mail : send_test_mail,
        create_new_template : create_new_template,
        edit_template_change_listner : edit_template_change_listner,
        template_map_validation : template_map_validation,
        open_modal : open_modal,
        close_modal : close_modal,
        save_template_event : save_template_event,
        edit_template_name : edit_template_name,
        map_form_toggle : map_form_toggle,
        toggle_builder_preview_tab : toggle_builder_preview_tab,
        delete_template : delete_template
    };
}(window.jQuery, window, document));  

function thwecNewTemplate(elm){
	thwec_settings.create_new_template(elm);
}

function thwecInitiateTemplateSave(elm){
    thwec_settings.initiateTemplateSave(elm, false, true );    
}

function thwecPreviewTemplate(elm){
    thwec_settings.show_template_preview(elm);    
}
function editTemplateChangeListner(elm){
	thwec_settings.edit_template_change_listner(elm);
}

function thwecTemplateMapValidation(elm){
   thwec_settings.template_map_validation(elm);
}

function thwecOpenModal( elm, flag ){
    thwec_settings.open_modal( elm, flag );
}

function thwecCloseModal( elm ){
    thwec_settings.close_modal( elm );
}

function thwecSaveTemplateEvent( elm ){
    thwec_settings.save_template_event( elm );
}

function thwecEditTN(elm){
    thwec_settings.edit_template_name( elm );
}

function thwecMapFormToggle( elm ){
    thwec_settings.map_form_toggle( elm );
}

function thwecSendTestMail( elm ){
    thwec_settings.send_test_mail( elm );
}

function thwecBuilderPreviewTab( elm ){
    thwec_settings.toggle_builder_preview_tab( elm );
}

function thwecDeleteTemplate( elm ){
    thwec_settings.delete_template( elm );
}