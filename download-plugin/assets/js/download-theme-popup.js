/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
// Plugin Info Modal

jQuery(document).ready(function(){
    jQuery('.dtwap-modal-view').addClass('is-active');
    jQuery('.dtwap-modal-wrap').removeClass('dtwap-popup-out');
    jQuery('.dtwap-modal-wrap').addClass('dtwap-popup-in');
    jQuery('.dtwap-modal-overlay').removeClass('dtwap-popup-overlay-fade-out');
    jQuery('.dtwap-modal-overlay').addClass('dtwap-popup-overlay-fade-in');

    jQuery('.dtwap-modal-close').click(function () {
        setTimeout(function () {
            jQuery('.dtwap-modal-view').hide();
        }, 400);
    });
    jQuery('.dtwap-modal-close, .dtwap-modal-overlay').on('click', function () {
        jQuery('.dtwap-modal-wrap').removeClass('dtwap-popup-in');
        jQuery('.dtwap-modal-wrap').addClass('dtwap-popup-out');
        jQuery('.dtwap-modal-overlay').removeClass('dtwap-popup-overlay-fade-in');
        jQuery('.dtwap-modal-overlay').addClass('dtwap-popup-overlay-fade-out');
    });
});