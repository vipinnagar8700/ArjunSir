<?php

class Toolset_User_Editors_Editor_Screen_Visual_Composer_Frontend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * Store IDs for the Content Templates which custom CSS has been logged already.
	 *
	 * @var array
	 */
	private $custom_css_rendered = array();

	/**
	 * Accumulative custom CSS for Content Templates using the Visual Composer editor.
	 *
	 * @var string
	 */
	private $custom_css = '';

	public function initialize() {
		add_action( 'init', array( $this, 'map_all_vc_shortcodes' ) );

		add_filter( 'the_content', array( $this, 'render_custom_css' ) );

		add_filter( 'vc_basic_grid_find_post_shortcode', array( $this, 'maybe_get_shortcode_from_assigned_ct_id' ), 10, 3 );

		// this adds the [Fields and Views] to editor of WPBakery Page Builder (former Visual Composer) text element
		if( array_key_exists( 'action', $_POST ) && $_POST['action'] == 'vc_edit_form' ) {
			add_filter( 'wpv_filter_dialog_for_editors_requires_post', '__return_false' );
		}

		// This filters the content of the Visual Composer WP Text Widget module before the global post switched from the
		// current post in the loop to the top current post. The switch happens because the module calls the "the_widget()"
		// method which ultimately calls WP_Widget_Text::widget.
		add_filter( 'vc_wp_text_widget_shortcode', 'wpv_do_shortcode' );

		add_action( 'wp_footer', array( $this, 'maybe_render_custom_css' ), 1 );
	}

	/**
	 * We need to force the registration of all the WPBakery Page Builder (former Visual Composer) shortcodes in order to be rendered properly upon CT
	 * rendering.
	 */
	public function map_all_vc_shortcodes() {
		// make sure all vc shortcodes are loaded (needed for ajax pagination)
		if ( method_exists( 'WPBMap', 'addAllMappedShortcodes' ) ) {
			WPBMap::addAllMappedShortcodes();
		}
	}

	/**
	 * WPBakery Page Builder (former Visual Composer) stores custom css as postmeta.
	 * We need to check if current post has content_template and if so apply the custom css.
	 * Hooked to the_content
	 *
	 * @param string $content
	 * @return string
	 */
	public function render_custom_css( $content ) {
		if(
			method_exists( 'Vc_Base', 'addPageCustomCss' )
			&& method_exists( 'Vc_Base', 'addShortcodesCustomCss' )
		) {
			$content_template = apply_filters( 'wpv_content_template_for_post', 0, get_post( get_the_ID() ) );

			if (
				$content_template
				&& ! toolset_getarr( $this->custom_css_rendered, $content_template, false )
			) {
				$vcbase = new Vc_Base();
				ob_start();
				$vcbase->addPageCustomCss( $content_template );
				$vcbase->addShortcodesCustomCss( $content_template );
				$this->custom_css .= ob_get_clean();
				$this->custom_css_rendered[ $content_template ] = true;
			}
		}
		return $content;
	}

	/**
	 * Some of the WPBakery Page Builder (former Visual Composer) shortcodes are accessing the post meta of the post
	 * currently displayed to get the setting of the shortcodes settings. For the case where a Content Template is built
	 * with WPBakery Page Builder (former Visual Composer), in order for the shortcode settings to be retrieved correctly
	 * we need to access the Content Template post.
	 *
	 * @note Part of the method's code is copied by "findPostShortcodeById" method of WPBakery Page Builder.
	 *
	 * @param $shortcode The shortcode currently being rendered.
	 * @param $page_id   The page ID currently displayed.
	 * @param $grid_id   The grid ID saved inside the post meta.
	 *
	 * @return array     The new shortcode fetched from the Content Template
	 *
	 * @since 2.5.7
	 */
	public function maybe_get_shortcode_from_assigned_ct_id( $shortcode, $page_id, $grid_id ) {
		if (
			class_exists( 'WPBMap' )
			&& class_exists( 'WPBakeryShortCode_VC_Basic_Grid' )
			&& method_exists( 'WPBMap', 'getShortCode' )
			&& method_exists( 'WPBakeryShortCode_VC_Basic_Grid', 'findPostShortcodeById' )
		) {
			$page_id = intval( $page_id );

			$template_selected = apply_filters( 'wpv_content_template_for_post', 0, get_post( $page_id ) );

			if (
				! empty( $template_selected )
				&& intval( $template_selected ) > 0
			) {
				$page_id = $template_selected;
			}

			remove_filter( 'vc_basic_grid_find_post_shortcode', array( $this, 'maybe_get_shortcode_from_assigned_ct_id' ) );

			$ct_post_meta = get_post_meta( (int) $page_id, '_vc_post_settings', true );

			$settings = WPBMap::getShortCode( $ct_post_meta['vc_grid_id']['shortcodes'][ $grid_id ]['tag'] );

			$vc_basic_grid = new WPBakeryShortCode_VC_Basic_Grid( $settings );

			$shortcode = $vc_basic_grid->findPostShortcodeById( $page_id, $grid_id );

			add_filter( 'vc_basic_grid_find_post_shortcode', array( $this, 'maybe_get_shortcode_from_assigned_ct_id' ) );
		}

		return $shortcode;
	}

	/**
	 * Render the accumulated custom CSS from Content Templates using the Visual Composer editor.
	 *
	 * @since 2.8.1
	 */
	public function maybe_render_custom_css() {
		echo $this->custom_css;
	}
}
