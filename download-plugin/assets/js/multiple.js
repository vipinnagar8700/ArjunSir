/*-- MPI Jquery Script
-------------------------------------------------------*/

//validate upload plugin and check ZIP
function check_valid_zipfile(dpwap_eleId, max_size_upload){ 
  var extension = ".zip";
  var maxSize = max_size_upload * 1048576;
  var inp = document.getElementById(dpwap_eleId);
  var count = inp.files.length;
  if(count <= 20){
    for(var a = 0; a < count; a++){
      var fieldvalue = inp.files.item(a).name;
      var fileSize = inp.files.item(a).size;
      var thisext = fieldvalue.substr(fieldvalue.lastIndexOf('.'));
      if(thisext == extension){ 
        if(fileSize <= maxSize){
          jQuery('.containerul').show();
          return true; 
        }
        else{ 
          alert(dpwap_string.server_max_upload + " "+ max_size_upload + dpwap_string.mb); 
          return false; 
        }
      }
    }
    alert(dpwap_string.valid_zip_message);
    return false;
  }else{
    alert(dpwap_string.max_20_upload);
    return false;
  }
}

//all plugins form submit  function
function activateAllPLugins(){
  document.getElementById('form_alldpwap').submit();
}

//single plugin activated function
jQuery(document).ready(function() {
  jQuery('.dpwap_inner a').click(function() {
    var that = this;
    var dpwapUrl = jQuery(this).attr("href");
    var dpwapUrl2 = decodeURIComponent(dpwapUrl).split("&");
    var dpwapUrl3 = dpwapUrl2[1].split('=');
    //var dpwapNoce = dpwapUrl2[2].split('=');
    var scePlugin = jQuery(that).closest(".dpwap_inner").data('plugin_sec');
    jQuery.ajax({
      url    : ajaxurl,
      type : 'post',
      data : {
        action : 'dpwap_plugin_activate',
        dpwap_url : dpwapUrl3[1],
        nonce: scePlugin
      },
      success : function( response ) {
        alert(dpwap_string.activate_message);
        jQuery(that).replaceWith('<h4>'+ dpwap_string.activated_message+'</h4>');
      }
    }); 
    return false;
  });   
  
  jQuery('#second-slide-button').click(function() {
    jQuery('#dpwap_section_first-slide').hide();
    jQuery("#dpwap_section_second-slide").show();
  });
  
  jQuery('#third-slide-button').click(function() {
    jQuery('#dpwap_section_first-slide').hide();
    jQuery("#dpwap_section_second-slide").hide();
    jQuery("#dpwap_section_third-slide").show();
  });
  
  var length = jQuery('.dpwap_section_content').length; 
  jQuery('.map-marker').click(function() {
    var index = jQuery(this).index(); 
    jQuery('.map-marker,.dpwap_section_content').removeClass('current');
    jQuery('.dpwap_section_content:eq(' + index + ')').addClass('current');
    jQuery(this).addClass('current');
    if (index == 0) {
      jQuery('.prev-tab').hide();
      jQuery('.next-tab').show();
    } else if (index == (length - 1)) {
      jQuery('.prev-tab').show();
      jQuery('.next-tab').hide();
    } else {
      jQuery('.prev-tab,.next-tab').show();
    }
  });
  
  jQuery('.next-tab').click(function() {
    var currentIndex = jQuery('.dpwap_section_content.current').index(); 
    var nextIndex = currentIndex + 1; // add 1
    jQuery('.map-marker, .dpwap_section_content').removeClass('current');
    jQuery('.dpwap_section_content:eq(' + nextIndex + '),.map-marker:eq(' + nextIndex + ')').addClass('current');
    if (nextIndex == (length - 1)) {
      jQuery(this).hide();
    }
  });
  
  jQuery('.prev-tab').click(function() {
    var currentIndex = jQuery('.dpwap_section_content.current').index(); 
    var prev = currentIndex - 1; 
    jQuery('.map-marker, .dpwap_section_content').removeClass('current');
    jQuery('.dpwap_section_content:eq(' + prev + '),.map-marker:eq(' + prev + ')').addClass('current');
    if (prev == 0) {
      jQuery(this).hide();
    }
  });

  //feature poup second section activated function
  jQuery('#next_first').click(function() {
    jQuery('#dpwap_section_first').hide();
    jQuery("#dpwap_section_second").show();
  });
  
  //feature poup third(suggest theme and plugins) section activated function
  jQuery('#next_second').click(function() {
    if (jQuery('input[name="feature"]').is(':checked')) {
      jQuery("#dpwap_section_first").hide();
      jQuery('#dpwap_section_second').hide();
      jQuery("#dpwap_section_third").show();
      jQuery('#feature_activate').attr('disabled','disabled');
      var wpdapFeature = [];
      jQuery.each(jQuery("input[name='feature']:checked"), function(){            
        wpdapFeature.push(jQuery(this).val());
      });
      jQuery.ajax({
        url    : ajaxurl,
        type   : 'post',
        data   : {
          action        : 'dpwap_feature_select',
          dpwap_feature : wpdapFeature
        },
        beforeSend: function() {
          jQuery('#thirdLoading').text(dpwap_string.wait_text);
        },
        success : function( response ) {
          jQuery('#thirdLoading').hide();
          jQuery('#title_third').text(dpwap_string.feature_message);
          jQuery("#dpwap_third_inner").html(response);
          jQuery('#feature_activate').removeAttr('disabled');
        }
      });
    }else { 
      alert(dpwap_string.select_feature); 
      return false; 
    }   
  });
  //feature poup back button click function
  jQuery('#back_second').click(function() {
    jQuery("#dpwap_section_first").hide();
    jQuery('#dpwap_section_second').show();
    jQuery("#dpwap_section_third").hide();
  });
  var count_checked = 0;
  //sk admin onclick top apply button multiple plugin download
  jQuery( document ).on( 'click', '#doaction', function() { 
    var getAction = jQuery('#bulk-action-selector-top').val();
    var count_checked = jQuery("[name='checked[]']:checked").length;
    if (getAction == 'all_download' && count_checked == 0){
      jQuery("#no-items-selected").hide();
      alert(dpwap_string.no_plugin_message);
      return false;
    }else{
      if(getAction == "all_download"){
        var maxAllowed = 30;
        if (count_checked <= maxAllowed) {
          jQuery("#dpwapLoader").show();
          jQuery("[name='checked[]']:checked").each(function () {
            var plgname = jQuery(this).val();
            setTimeout(function(){
              dpwap_recursively_download(count_checked, plgname);
            }, 3000); 
          });
        }
        else{ 
          alert(dpwap_string.max_30_dpwnload); 
        }
        return false;
      }
    }
  });
  
  //sk admin onclick bottom apply button multiple plugin download
  jQuery( document ).on( 'click', '#doaction2', function() { 
    var getAction = jQuery('#bulk-action-selector-bottom').val();
    var count_checked = jQuery("[name='checked[]']:checked").length;
    if (getAction == 'all_download' && count_checked == 0){
      jQuery("#no-items-selected").hide();
      alert(dpwap_string.no_plugin_message);
      return false;
    }else{
      if(getAction == "all_download"){
        var maxAllowed = 30;
        if (count_checked <= maxAllowed) {
          jQuery("#dpwapLoader").show();
          jQuery("[name='checked[]']:checked").each(function () {
            var plgname = jQuery(this).val();
            setTimeout(function(){
              dpwap_recursively_download(count_checked, plgname);
            }, 3000); 
          });
        }else{ 
          alert(dpwap_string.max_30_dpwnload);
        }
        return false;
      }
    }
  });
  
  var getUpdate = jQuery(".update-nag").attr('class');
  if(getUpdate == 'update-nag'){ 
    jQuery("#btn_upload").css("margin-top", "74px");
  }else{ 
    jQuery("#btn_upload").css("margin-top", "9px"); 
  }  
  
  if(jQuery("#activate_yes").length!=0){
    jQuery(".dpwap_allactive").show();
    jQuery('.containerul').hide();
  }
  
  if(jQuery("#form_alldpwap").length!=0){
    jQuery("#dpwap-plugin-zipbox").hide();
  }

  jQuery(".dpwap-download-info").click(function(){
    jQuery("#dpwap_modal").modal();
  });
});

//feature poup form submit function
function activateFeaturePLugins(){ 
  document.getElementById('dpwapActivate').submit();
}

var prev_count = 0;
function dpwap_recursively_download(count_checked, plgname){
  //alert(plgname);
  var pass_data = count_checked;
  var chartMenu = plgname;
  jQuery.ajax({
    type    : "POST",
    async   : false, // set async false to wait for previous response
    url     : ajaxurl,
    dataType: "json",
    data    : {
      action      : 'dpwap_plugin_download_url',
      pluginData  : chartMenu,
      plugin_count: prev_count+1,
      _wpnonce : jQuery('#bulk-action-form #_wpnonce').val()
    },
    complete: function() {
      prev_count++;
      if(prev_count < pass_data){
        //recursively_ajax();        
      }else{
        window.location.href ="plugins.php?action=multiple_download";
        //alert('AllZip Created successfully');
      }
    },
    success: function(data){
      // prev_count++;
      if(prev_count < pass_data){
        //recursively_ajax();
      }
    }
  });
}