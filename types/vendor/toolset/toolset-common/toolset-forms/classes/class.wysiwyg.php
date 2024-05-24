<?php

require_once 'class.textarea.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 *
 */
class WPToolset_Field_Wysiwyg extends WPToolset_Field_Textarea {

    /**
     * Priority for the filter callback to avoid Toolset buttons over WYSIWYG editors.
     *
     * It needs to be high enough as to effectively rmeove buttons despite other callbacks.
     */
    const TOOLSET_BUTTONS_FILTER_PRIORITY = 9999;

    protected $_settings = array('min_wp_version' => '3.3');

    public function metaform() {

        $attributes = $this->getAttr();
        $form = array();
        $markup = '';
        $wpml_action = $this->getWPMLAction();

        if ( Toolset_Utils::is_real_admin() ) {
            $markup .= '<div class="form-item form-item-markup">';
            $extra_markup = '';
            if (
                    defined( 'WPML_TM_VERSION' ) && intval( $wpml_action ) === 1 && function_exists( 'wpcf_wpml_post_is_original' ) && !wpcf_wpml_post_is_original() && function_exists( 'wpcf_wpml_have_original' ) && wpcf_wpml_have_original()
            ) {
                $attributes['readonly'] = 'readonly';
				$extra_markup .= sprintf(
					'<i class="fa fa-lock icon-warning-sign js-otgs-popover-tooltip" title="%s"></i>',
					esc_attr( __( 'This field is locked for editing because WPML will copy its value from the original language.', 'wpv-views' ) )
				);
            }
            $markup .= sprintf(
                    '<label class="wpt-form-label wpt-form-textfield-label">%1$s%2$s</label>', stripcslashes( $this->getTitle() ), $extra_markup
            );
        }

        if ( Toolset_Utils::is_real_admin() ) {
            $markup .= '<div class="description wpt-form-description wpt-form-description-textfield description-textfield">' . stripcslashes( $this->getDescription() ) . '</div>';
        } else {
            $markup .= stripcslashes( $this->getDescription() );
        }
        $markup .= $this->_editor( $attributes );
        if ( Toolset_Utils::is_real_admin() ) {
            $markup .= '</div>';
        }
        $form[] = array(
            '#type' => 'markup',
            '#markup' => $markup
        );
        return $form;
    }

    protected function _editor(&$attributes) {

        $media_buttons = isset( $this->_data['has_media_button'] )
            ? $this->_data['has_media_button']
            : false;
        $toolset_buttons = isset( $this->_data['has_toolset_buttons'] )
            ? $this->_data['has_toolset_buttons']
            : false;
        $quicktags = true;

        if (
            isset( $attributes['readonly'] ) && $attributes['readonly'] == 'readonly'
        ) {
            add_filter( 'tiny_mce_before_init', array(&$this, 'tiny_mce_before_init_callback') );
            $media_buttons = false;
            $toolset_buttons = false;
            $quicktags = false;
        }

        //EMERSON: Rewritten to set do_concat to TRUE so WordPress won't echo styles directly to the browser
        //This will fix a lot of issues as WordPress will not be echoing content to the browser before header() is called
        //This fix is important so we will not be necessarily adding ob_start() here in this todo:
        //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/185336518/comments#282283111
        //Using ob_start in that code will have some side effects of some styles from other plugins not being properly loaded.

        global $wp_styles;
        $wp_styles->do_concat = TRUE;
        ob_start();
        // In some cases (related content metaboxes) the same wysiwyg editor could be displayed several times in the same page,
        // it makes the tinymce fail, so a different ID must be used.
        // In order to use it, you need to add a new filter 'toolset_field_factory_get_attributes' which adds the following item:
        // $attributes['types-related-content'] = true;
        $id = $this->getId();
        if ( true === toolset_getarr( $attributes, 'types-related-content' ) ) {
          $id .= '_' . rand( 10000, 99999 );
        }

		$editor_classnames = array( 'wpt-wysiwyg' );

		if ( function_exists( 'wp_enqueue_editor' ) ) {
			// In WP 4.8 and above, enqueue the needed assets for dynamically initialize the editor
			wp_enqueue_editor();
		}

        // Manage editor toolbar buttons.
		$include_editor_toolbar = ( $media_buttons || $toolset_buttons );
		if ( ! $media_buttons ) {
            remove_action( 'media_buttons', 'media_buttons' );
            $editor_classnames[] = 'js-toolset-wysiwyg-skip-media';
		}
		if ( ! $toolset_buttons ) {
            add_filter( 'toolset_editor_add_form_buttons', array( $this, 'return_false' ), self::TOOLSET_BUTTONS_FILTER_PRIORITY );
            $editor_classnames[] = 'js-toolset-wysiwyg-skip-toolset';
		}

	    // When we're disabling a form element, make sure we also submit it.
	    // See https://onthegosystems.myjetbrains.com/youtrack/issue/types-1784#focus=streamItem-102-308144-0-0
	    // for a lengthy explanation of why it is needed.
	    //
	    // There's no better way than using the_editor filter, unfortunately. wp_editor() doesn't allow adding
		// custom attributes to the underlying textarea.
		$validation_data = $this->getValidationData();
		$add_submitanyway = function( $markup ) use ( $validation_data ) {
			$search = '<textarea';
			$replacement = '<textarea data-submitanyway ';
			if (
				array_key_exists( 'required', $validation_data ) &&
				in_array( '$value', $validation_data['required']['args'], true ) &&
				in_array( true, $validation_data['required']['args'], true )
			) {
				$replacement = '<textarea data-submitanyway data-wpt-validate=\'{"required":{"args":{"1":true},"message":"This field is required"}}\' ';
			}
			$updated_markup = str_replace( $search, $replacement, $markup );

			return $updated_markup;
		};

		add_filter( 'the_editor', $add_submitanyway, 100 );

        wp_editor( $this->getValue(), $id, array(
            'wpautop' => true, // use wpautop?
            'media_buttons' => $include_editor_toolbar, // show insert/upload button(s)
            'textarea_name' => $this->getName(), // set the textarea name to something different, square brackets [] can be used here
            'textarea_rows' => get_option( 'default_post_edit_rows', 10 ), // rows="..."
            'tabindex' => '',
            'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
            'editor_class' => implode( ' ', $editor_classnames ), // add extra class(es) to the editor textarea
            'teeny' => false, // output the minimal editor config used in Press This
            'dfw' => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
            'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
            'quicktags' => $quicktags // load Quicktags, can be used to pass settings directly to Quicktags using an array(),
        ) );

        remove_filter( 'the_editor', $add_submitanyway, 100 );

        $return = ob_get_clean() . "\n\n";
        if (
            isset( $attributes['readonly'] ) && $attributes['readonly'] == 'readonly'
        ) {
            remove_filter( 'tiny_mce_before_init', array(&$this, 'tiny_mce_before_init_callback') );
            $return = str_replace( '<textarea', '<textarea readonly="readonly"', $return );
        }
        $wp_styles->do_concat = FALSE;

        // Maybe restore editor toolbar buttons.
		if ( ! $media_buttons ) {
			add_action( 'media_buttons', 'media_buttons' );
		}
		if ( ! $toolset_buttons ) {
			remove_filter( 'toolset_editor_add_form_buttons', array( $this, 'return_false' ), self::TOOLSET_BUTTONS_FILTER_PRIORITY );
		}

        return $return;
    }

    public function tiny_mce_before_init_callback($args) {
        $args['readonly'] = 1;
        return $args;
    }

    /**
     * Callback for filters to return FALSE.
     *
     * We need an unique, proper and own callback to manage Toolset buttons over editors because
     * we do add and remove this callback, and using the native __return_false function
     * would mean removing it as a callback even if a tird party used it to remove such buttons.
     *
     * @param bool $dummy
     * @return bool
     */
	public function return_false( $dummy ) {
		return false;
	}

}
