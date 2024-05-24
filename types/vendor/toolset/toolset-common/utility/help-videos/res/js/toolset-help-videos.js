var WP_Toolset = WPV_Toolset || {};

WP_Toolset.HelpVideos = {};

if( typeof _ !== 'undefined' && _.templateSettings )
{
    _.templateSettings = {
        escape: /\{\{([^\}]+?)\}\}(?!\})/g,
        evaluate: /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
    };
}

WP_Toolset.HelpVideosFactory = function ($) {
    var self = this,
        videos = WP_ToolsetVideoSettings.video_instances,
        current = WP_ToolsetVideoSettings.current,
        seen = WP_ToolsetVideoSettings.seen,
        collection,
        append_done = false,
        triggered_manually = false,
        collection_view = null;

    self.init = function () {
        return self.show_video(current);
    };

    self.get_seen = function(){
        return seen;
    }

    self.populate_collection = function(){
        collection = new WP_Toolset.HelpVideosCollection();
        _.each(videos, function(v){
            collection.add( new WP_Toolset.HelpVideo( v ) );
        });
        return collection;
    };

    self.get_videos = function(){
        return collection;
    };

    self.create_on_the_fly = function(element,append_to){
        if( append_done === false ){
            var el = self.create_element(element);
            jQuery(append_to).append(el);
            append_done = true;
        }
    };

    self.handle_list = function(){
        var models = self.populate_collection();
        collection_view = new WP_Toolset.HelpVideosListView({model:models});
        jQuery( '.js-toolset-videos-wrapper' ).append( jQuery(WP_ToolsetVideoSettings.VIDEOS_LIST_TITLE), collection_view.$el );
    };

    self.show_video = function( video ){
        if ( videos.hasOwnProperty(video) ) {

            if( adminpage !== WP_ToolsetVideoSettings.detached_page && videos[video].hasOwnProperty('append_to') && videos[video].append_to !== '' ){
                self.create_on_the_fly( videos[video].element ? videos[video].element : WP_ToolsetVideoSettings.GENERIC_ELEMENT, videos[video].append_to );
            }

            try{
                var model = WP_Toolset.HelpVideos.hasOwnProperty(video) ? WP_Toolset.HelpVideos[video] : new WP_Toolset.HelpVideo(videos[video]),
                    view  = self.getVideoViewInstance(model, {
                        el: videos[video].element ? videos[video].element : WP_ToolsetVideoSettings.GENERIC_ELEMENT,
                        model: model
                    });
            } catch( error ){
                console.log( error.message );
            }


            if( adminpage !== WP_ToolsetVideoSettings.detached_page && self.get_seen() === 'seen' && triggered_manually === false ){
                view.$el.hide();
                view.manual_trigger();
                triggered_manually = true;
            }

            jQuery('.js-toolset-videos-wrapper').width( model.get('width') ).height( model.get('height') );

            WP_Toolset.HelpVideos[video] = model;

            return WP_Toolset.HelpVideos[video];
        }

        return null;
    };

    self.getVideoViewInstance = function( model, arguments ){
        var className = "Help"+model.getCapitalisedRenderer()+"View",
            nameSpacedClassName = "WP_Toolset";

        // this is to be formally correct, but if this would be true we couldn't be here
        if( _.isUndefined( window[nameSpacedClassName] ) ){
            throw new TypeError( nameSpacedClassName + ' namespace is not defined' );
        }

        // if there is no alternative, then use default base video class
        if( ! _.isFunction( window[nameSpacedClassName][className] ) ){
            className = "HelpVideoView";
        }

        instance = window[nameSpacedClassName][className];

        return new instance( arguments );
    };

    self.show_new_video = function( model ){
        self.remove_list();
        jQuery('.js-toolset-videos-wrapper').append( self.create_element( model.get('element') ) );
        self.show_video( model.get('name') );
    };

    self.create_element = function( selector ){
            var sel = selector.substring(1);
            return jQuery('<div class="'+sel+'" id="'+sel+'"></div>')
    };

    self.remove_list = function(){
        jQuery('.js-videos-list-title').remove();
        collection_view.remove();
    };

    self.init();
};

WP_Toolset.HelpVideo = Backbone.Model.extend({
    defaults: {
        title:'',
        name: '',
        url: '',
        element: '',
        screens: [],
        width:'600px',
        height:'400px',
        renderer: 'video',
        track: ''
    },
    getRenderer: function(){
        return this.get('renderer');
    },
    getCapitalisedRenderer: function(){
        var renderer = this.getRenderer(),
            name = renderer.charAt(0).toUpperCase() + renderer.substring(1);

        return name;
    }
});

WP_Toolset.HelpVideosCollection = Backbone.Collection.extend({
    model:WP_Toolset.HelpVideo,
    current:null
});

WP_Toolset.HelpVideoView = Backbone.View.extend({
    DELAY:200,
    initialize: function (options) {
        var self = this;
        self.template_selector = self.getTemplateName();
        self.template = _.template(jQuery(self.template_selector).html());
        self.deatch_url = WP_ToolsetVideoSettings.detach_url;
        self.render(options).el;
    },
    getTemplateName: function(){
        var self = this,
            model = self.model,
            renderer = model.getRenderer(),
            name = renderer.toLowerCase();

        return '#toolset-'+name+'-template';
    },
    render: function (options) {
        var self = this;
        self.$el.html(self.template(self.model.toJSON()));
        self.wrap = jQuery('.js-toolset-box-container', self.$el);
        self.handle_detach();
        self.handleOverlay( self.wrap, 'show', {
            class:'loader-overlay-high-z',
            css : {
                "opacity" : "0.65",
                height : self.model.get('height')
            }
        } );

        self.hidden_wrap = jQuery('.js-video-player-box', self.$el);
        self.remove_button = jQuery('.js-remove-video', self.$el);
        self.handle_video();
        self.remove_video();
        return self;
    },
    handle_detach:function(){
        var self = this, $button = jQuery('.js-detach-video', self.$el);
        if( adminpage === WP_ToolsetVideoSettings.detached_page ){
            $button.hide();
        } else {
            $button.on('click', function(event){
                event.stopImmediatePropagation();
                event.preventDefault();
                self.remove_button.trigger('click');
                window.open( self.deatch_url  );
            });
        }
    },
    handle_video:function(){
        var self = this;
        var video = jQuery('.js-video-player');
        if( video.length === 0 ) return; // if no player instances in DOM then do nothing
        self.player = new MediaElementPlayer( video[0], {
            alwaysShowHours: false,
            width:self.model.get('width'),
            height:self.model.get('height'),
            success: function (mediaElement, domObject, instance) {
                var playerContainer = instance.getElement(instance.container);
                var startPlayer = function(e) {
                    mediaElement.pause();
                    self.hidden_wrap.fadeIn(self.DELAY, function(event){
                        self.setPlay( mediaElement );
                        e.target.removeEventListener( 'controlsshown', startPlayer, false );
                    });
                };
                playerContainer.addEventListener('controlsshown', startPlayer, false);

                mediaElement.addEventListener('ended', function(e) {
                    self.setPlay( mediaElement, true );
                }, false);
                mediaElement.addEventListener('play', function(e) {
                    self.handleOverlay( jQuery('.mejs-mediaelement'), 'hide', {} );
                }, false);
            },
            // fires when a problem is detected
            error: function () {
                console.log( 'error', arguments );
            }
        } );
    },
    setPlay:function( mediaElement, after_ended ){
        var self = this,
            play = jQuery('<i class="fa fa-play-circle js-toolset-play-video"></i>'),
            $title = jQuery('.js-video-box-title-open').eq(0).detach().clone();


        self.handleOverlay( jQuery('.mejs-mediaelement'), 'show', {
            class:'loader-overlay-high-z',
            css : {
                "opacity" : "0.7",
                'height': jQuery('.mejs-mediaelement').height() - 30 + 'px'
            }
        });

        jQuery('.js-video-box-title-open').remove();
        jQuery('.toolset-box-container .loader-overlay').append($title);

        jQuery('.toolset-box-container .preloader').css({
            'background':'none'
        }).append(play);

        jQuery('.js-toolset-play-video').on('click', function(event){
            event.stopImmediatePropagation();
            event.preventDefault();
            self.handleOverlay( jQuery('.mejs-mediaelement'), 'hide', {onRemove:function(){
                    mediaElement.play();
                }
            });
        });

        self.handleOverlay( self.wrap, 'hide', {onRemove:function(){
            }, fadeOutSpeed:200} );
    },
    handleOverlay : function( $element, action, configurationObject ){
        $element.loaderOverlay( action, configurationObject );
    },
    remove_video:function(){
        var self = this;
        self.remove_button.on('click', function(event){
                event.stopImmediatePropagation();
                event.preventDefault();
                self.$el.hide(400, function(){
                    if( adminpage === WP_ToolsetVideoSettings.detached_page ){
                        WP_Toolset.HelpVideos.main.handle_list();
                    } else {
                        self.insert_title_view();
                    }
                    self.remove();
                });
        });
    },
    manual_trigger:function(){
        var self = this;
        self.remove_button.trigger('click');
    },
    insert_title_view:function(){
        var self = this,
            index = self.$el.index(),
            parent = self.$el.parent(),
            element = self.model.get('element') ? self.model.get('element').substring(1) : WP_ToolsetVideoSettings.GENERIC_ELEMENT,
            new_me = new WP_Toolset.HelpVideoListView({
                model:self.model,
                template_selector:'#toolset-video-header-template',
                tagName:'div',
                classes:element,
                id:element
            });

        parent.insertAtIndex(index, new_me.$el);
        return new_me;
    }
});

WP_Toolset.HelpYouTubeView = WP_Toolset.HelpVideoView.extend({
    initialize:function(options){

        this.origin = window.location.protocol + '//' + window.location.host;
        
        WP_Toolset.HelpVideoView.prototype.initialize.call(this, options);
    },
    handleOverlay:function(){
        // do nothing
    },
    setPlay: function(){
        // do nothing
    },
    handle_video:function(){
        var self = this;
        var video = jQuery('.js-video-player');
        if( video.length === 0 ) return; // if no player instances in DOM then do nothing
        self.player = new MediaElementPlayer( video[0], {
            plugins: ['youtube'],
            features: ['playpause','progress','current','tracks','volume'],
            startLanguage: 'en',
            alwaysShowHours: false,
            width:self.model.get('width'),
            height:self.model.get('height'),
            youtube:{
                cc_load_policy: 1,
                enablejsapi: 1,
                iv_load_policy: 1,
                origin: self.origin,
                rel: 0,
                showinfo: 1,
                widget_referrer: self.origin
            },
            success: function (mediaElement, domObject, instance) {
                var playerContainer = instance.getElement(instance.container);
                var startPlayer = function(e) {
                    mediaElement.pause();
                    self.hidden_wrap.fadeIn(self.DELAY, function(event){
                        self.setPlay( mediaElement );
                        e.target.removeEventListener( 'controlsshown', startPlayer, false );
                    });
                };
                playerContainer.addEventListener('controlsshown', startPlayer, false);

                mediaElement.addEventListener('ended', function(e) {
                    self.setPlay( mediaElement, true );
                }, false);
                mediaElement.addEventListener('play', function(e) {
                    self.handleOverlay( jQuery('.mejs-mediaelement'), 'hide', {} );
                }, false);
            },
            // fires when a problem is detected
            error: function () {
                console.log( 'error', arguments );
            }
        } );
    }
});

WP_Toolset.HelpVideoListView = Backbone.View.extend({
    initialize:function(options){
        var self = this;
        self.tagName = options.tagName;
        self.$el.addClass(options.classes + ' video-title-alone');
        if( options.hasOwnProperty('id') ){
            self.$el.prop('id', options.id);
        }
        self.template_selector = options.template_selector;
        self.template = _.template(jQuery(self.template_selector).html());
        self.render( options ).el
        return self;
    },
    render:function( options ){
        var self = this;
        self.$el.html(self.template(self.model.toJSON()));
        self.show();
        return self;
    },
    show:function(){
        var self = this;
        self.$el.on('click', function(event){
            event.stopImmediatePropagation();
            event.preventDefault();
            if( adminpage === WP_ToolsetVideoSettings.detached_page ) {
                WP_Toolset.HelpVideos.main.show_new_video(self.model);
            } else {
                self.insert_video_view();
            }
        });
    },
    insert_video_view:function(){
        var self = this,
            index = self.$el.index(),
            parent = self.$el.parent();

        self.$el.empty();
        parent.insertAtIndex( index, self.$el.clone() );
        self.remove();
        WP_Toolset.HelpVideos.main.show_video( self.model.get('name') );
    }
});

WP_Toolset.HelpVideosListView = Backbone.View.extend({
    el:'.js-videos-list',
    tagName: 'ul',
    initialize:function(options){
        var self = this;
        self.$el = self.create_element();
        self.el = self.$el[0];
        self.render(options).el;
        return self;
    },
    render:function( option ){

        var self = this,
            options = _.extend({}, option);

        self.$el.empty();

        self.fragment = document.createDocumentFragment();

        self.appendModelElement( options );

        self.$el.append( self.fragment );

        return self;
    },
    appendModelElement:function( option ){
        var self = this, view, el, options = option;

        self.model.each(function(model){

            try{

                options = {
                    model:model
                }

                view = new WP_Toolset.HelpVideoListView({
                        model:model,
                        template_selector:'#toolset-video-list-template',
                        tagName:'li',
                        classes:'js-video-list toolset-video-list'
                });

                el = view.el;

                self.fragment.appendChild( el );

            }
            catch( e )
            {
                console.error( e.message );
            }
        }, self)

        return this;
    },
    create_element:function(){
        return jQuery('<ul class="js-videos-list toolset-videos-list"></ul>');
    }
});

(function ($) {
    $(function () {
        WP_Toolset.HelpVideos.main = {};
        WP_Toolset.HelpVideosFactory.call(WP_Toolset.HelpVideos.main, $);
    });
}(jQuery));