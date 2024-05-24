<?php

/**
 * Class Types_Shortcode_Types
 *
 * @since 2.3
 */
class Types_Shortcode_Types implements Types_Shortcode_Interface {

	const SHORTCODE_NAME = 'types';

	// quite long as all fields are covered by one shortcode
	private $shortcode_atts = array(
		'field' => null, // field slug for post fields
		'termmeta' => null, // field slug for term fields
		'usermeta' => null, // field slug for user fields
		'item' => null, // post
		'id' => null, // synonym for 'item'
		'post_id' => null, // synonym for 'item'
		'output' => null, // all fields (raw output)
		'raw' => null, // @deprecated see 'output'
		'separator' => null, // all repeatable fields
		'style' => null, // date
		'format' => null, // date
		'title' => null, // image
		'loop' => null, // video / audio
		'autoplay' => null, // video / audio
		'preload' => null, // video / audio
		'option' => null, // checkbox
		'state' => null, // checkbox
		'width' => null, // image / video
		'height' => null, // image / video
		'alt' => null, // image
		'size' => null, // image
		'resize' => null, // image
		'align' => null, // image
		'button_style' => null, // skype legacy
		'button' => null, // skype 3.1
		'button-color' => null, // skype 3.1
		'button-icon' => null, // skype 3.1
		'button-label' => null, // skype 3.1
		'chat-color' => null, // skype 3.1
		'receiver' => null, // skype 3.1
		'no_protocol' => null, // url
		'poster' => null, // video
		'url' => null, // image
		'class' => null, // image
		'onload' => null, // image
		'proportional' => null, // image
		'padding_color' => null, // image
		'show_name' => null, // checkbox
		'target' => null, // url
		'user_id' => null, // usermeta option
		'user_name' => null, // usermeta option
		'user_current' => null, // usermeta option
		'current_user' => null, // usermeta option
		'term_id' => null, // termmeta option
		'index' => null, // zero-based index for repeating fields
		'suppress_filters' => null, // wysiwyg
	);


	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $field;

	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $item;

	/**
	 * Types_Shortcode_Types constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $field
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $field,
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->field = $field;
		$this->item  = $item;
	}


	/**
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @return string
	 * @throws Exception_Invalid_Shortcode_Attr_Field
	 * @throws Exception_Invalid_Shortcode_Attr_Item
	 */
	public function get_value( $atts, $content = null ) {
		$user_atts = $this->normalise_user_shortcode_values( shortcode_atts( $this->shortcode_atts, $atts ) );
		$user_content = $content;

		if( $this->needs_item_attribute( $atts ) ) {
			// This would fail for non-post fields (but only if there is no current post).
			if ( ! $item_id = $this->item->get( $user_atts ) ) {
				// no valid item
				throw new Exception_Invalid_Shortcode_Attr_Item();
			}

			$user_atts['item'] = $item_id;
		}

		if ( ! $field = $this->field->get( $user_atts ) ) {
			// no valid field
			throw new Exception_Invalid_Shortcode_Attr_Field();
		}


		return types_render_field( $field, $user_atts, $user_content );
	}


	/**
	 * @param array $atts Shortcode attributes.
	 *
	 * @return bool True if an item attribute (or an older variant of it) is necessary
	 *     for a successful evaluation of the code.
	 *
	 * @since 3.0.1
	 */
	private function needs_item_attribute( $atts ) {
		$usermeta_attribute = toolset_getarr( $atts, 'usermeta' );
		$termmeta_attribute = toolset_getarr( $atts, 'termmeta' );
		return ( empty( $usermeta_attribute ) && empty( $termmeta_attribute ) );
	}

	/**
	 * Users like to use "false" (as string) or "no" as parameter values.
	 * Let's normalise these values to save a lot of checks afterwards.
	 *
	 * @param array $atts
	 *
	 * @return array
	 */
	private function normalise_user_shortcode_values( $atts ) {
		array_walk( $atts, static function( &$value ) {
			if( $value === 'false' || $value === 'no' ) {
				$value = false;
			} elseif( 'true' === $value ) {
				$value = true;
			}
		} );

		return $atts;
	}
}
