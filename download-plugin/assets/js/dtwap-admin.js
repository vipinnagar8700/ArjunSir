jQuery(document).ready( function($){
	if ( $('.themes .theme .theme-actions').length > 0 ){
		$('.themes .theme .theme-actions').each( function( index, element ){
			var theme_folder  = $(this).parents('.theme').attr('data-slug');
			var download_link = '<a href="?dtwap_download='+theme_folder+'&_wpnonce='+dtwap.dtwap_nonce+'" class="button button-primary dtwap_download_link">'+dtwap.download_title+'</a>';
			$(this).prepend(download_link);
		});
	}
	// if only single theme
	if ( $('.themes.single-theme').length > 0 ){
		if ( $('.themes.single-theme .active-theme .customize').length > 0 ){
			var theme_href = $('.themes.single-theme .active-theme .customize').attr('href');
			var href_component = decodeURIComponent(theme_href).split("&");
			var theme_folder = href_component[0].split('=')[1];
			var download_link = '<a href="?dtwap_download='+theme_folder+'&_wpnonce='+dtwap.dtwap_nonce+'" class="button button-primary dtwap_download_link">'+dtwap.download_title+'</a>';
			$('.themes.single-theme .active-theme').each( function( index, element ){
				$(this).prepend(download_link);
			});
		}
	}
});
