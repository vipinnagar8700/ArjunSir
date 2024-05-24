(function() {

    tinymce.PluginManager.add('ccj_shortcodes', function( editor, url ) {

        var ccj_menu = [];
        if ( typeof ccj_shortcodes !== 'undefined' && ccj_shortcodes.length > 0 ) {
            ccj_shortcodes.forEach(function(item){
                ccj_menu.push({
                    text: item.id + ' - ' + item.title,
                    onclick: function() {
                        editor.insertContent( '[ccj id="'+item.id+'"]' );
                    }
                });
            });
        } else {
            ccj_menu.push({
                text: 'No shortcodes defined',
            });
        }

        editor.addButton( 'ccj_shortcodes', {
            title : 'Custom CSS & JS Shortcodes',
            icon: 'icon ccj_shortcodes-icon',
            onclick: function() {
            },
            type: 'menubutton',
            menu: ccj_menu,
        });
    });
})();
