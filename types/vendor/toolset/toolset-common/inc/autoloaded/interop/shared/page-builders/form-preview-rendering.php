<?php

namespace OTGS\Toolset\Common\Interop\Shared\PageBuilders;

/**
 *
 *
 * @since 3.0.7
 */
class ToolsetFormsPreviewRenderer {
	/**
	 * The post type for the "Post" forms.
	 */
	const POSTS_FORM_POST_TYPE = 'cred-form';

	/**
	 * The post type for the "User" forms.
	 */
	const USERS_FORM_POST_TYPE = 'cred-user-form';

	/**
	 * A dictionary that correlates the form post type with the Element domain, in order to use the proper factory and
	 * get the right field definitions.
	 */
	private $forms_post_type_dictionary = array(
		self::POSTS_FORM_POST_TYPE => \Toolset_Element_Domain::POSTS,
		self::USERS_FORM_POST_TYPE => \Toolset_Element_Domain::USERS,
	);

	/**
	 * Holds the form post type.
	 *
	 * @var string
	 */
	private $cred_form_post_type;

	public function render_preview( $form ) {
		$this->cred_form_post_type = $form->post_type;
		$this->add_fake_preview_shortcodes();
		$form_preview = do_shortcode( $form->post_content );
		$this->remove_fake_preview_shortcodes();
		return $form_preview;
	}

	public function add_fake_preview_shortcodes() {
		add_shortcode( 'credform', array( $this, 'eliminate_irrelevant_shortcodes' ) );
		add_shortcode( 'creduserform', array( $this, 'eliminate_irrelevant_shortcodes' ) );
		add_shortcode( 'cred_field', array( $this, 'render_cred_form_field_preview' ) );
	}

	public function remove_fake_preview_shortcodes() {
		remove_shortcode( 'credform' );
		remove_shortcode( 'creduserform' );
		remove_shortcode( 'cred_field' );
	}

	public function eliminate_irrelevant_shortcodes(
		/** @noinspection PhpUnusedParameterInspection */ $atts, $content
	) {
		return do_shortcode( $content );
	}

	/**
	 * Render the form field preview.
	 *
	 * @param array $atts The array with the form field shortcode attributes.
	 *
	 * @return string The HTML markup of the form field.
	 */
	public function render_cred_form_field_preview( $atts ) {
		$non_form_fields = array( 'form_messages' );

		if ( ! in_array( $atts['field'], $non_form_fields, true ) ) {
			$button_text = '';

			$field_definition_factory = \Toolset_Field_Definition_Factory::get_factory_by_domain( $this->forms_post_type_dictionary[ $this->cred_form_post_type ] );

			// Only populate fields if Types is active
			$types_condition = new \Toolset_Condition_Plugin_Types_Active();
			if ( $types_condition->is_met() ) {
				$field_definition = $field_definition_factory->load_field_definition( $atts['field'] );
			} else {
				$field_definition = null;
			}

			if ( null === $field_definition ) {
				return $this->render_field_without_field_definition( $atts );
			}

			return $this->render_field( $atts, $field_definition->get_type()->get_slug(), $field_definition, $button_text );
		}

		return '';
	}

	/**
	 * Render the form field when a field definition doesn't exist.
	 *
	 * @param array $atts The array with the form field shortcode attributes.
	 *
	 * @return string The HTML markup of the form field.
	 */
	public function render_field_without_field_definition( $atts ) {
		$output = '';

		$button_text = '';

		$field_type_slug = '';

		// Handle taxonomy form fields.
		$taxonomy = get_taxonomy( $atts['field'] );
		if ( $taxonomy ) {
			// Hierarchical taxonomy.
			if (
				isset( $atts['display'] )
				&& 'checkbox' === $atts['display']
			) {
				$taxonomy_terms = get_terms(
					array(
						'taxonomy' => $taxonomy->name,
						'hide_empty' => false,
					)
				);
				$field_type_slug = '';
				foreach ( $taxonomy_terms as $taxonomy_term ) {
					$output .= '<input type="checkbox" disabled="disabled" /> ' . esc_html( $taxonomy_term->name ) . '<br />';
				}
			} else { // Flat taxonomy.
				$field_type_slug = 'textfield';
			}
		}

		if (
			isset( $atts['taxonomy'] )
			&& '' !== $atts['taxonomy']
		) {
			if ( 'show_popular' == $atts['type'] ) {
				$field_type_slug = '';
				$output = '<button disabled>' . esc_html( __( 'Add', 'wpv-views' ) ) . '</button><br />';
				$output .= '<a class="add-new">' . esc_html( __( 'Show popular', 'wpv-views' ) ) . '</a>';
			} elseif ( 'add_new' == $atts['type'] ) {
				$field_type_slug = '';
				$output .= '<a class="add-new">' . esc_html( __( 'Add new', 'wpv-views' ) ) . '</a>';
			}
		}

		// Handle relationship selector form fields.
		preg_match( '/_wpcf_belongs_(?!_id).*_id/', $atts['field'], $parent_fields );
		preg_match( '/@(.*(\.parent|\.child))/', $atts['field'], $m2m_ralationship_fields );
		if (
			0 !== count( $parent_fields )
			|| 0 !== count( $m2m_ralationship_fields )
		) {
			$field_type_slug = 'select';
		}

		// Handle post title.
		if ( 'post_title' === $atts['field'] ) {
			$field_type_slug = 'textfield';
		}

		// Handle post content.
		if ( 'post_content' === $atts['field'] ) {
			$field_type_slug = 'wysiwyg';
		}

		// Handle form submit.
		if ( 'form_submit' === $atts['field'] ) {
			$field_type_slug = 'button';
			$button_text = __( 'Submit', 'wpv-views' );
		}

		if (
			'' === $output &&
			'' === $field_type_slug
		) {
			$field_type_slug = 'textfield';
		}

		if ( '' !== $field_type_slug ) {
			$output = $this->render_field( $atts, $field_type_slug, null, $button_text );
		}

		return $output;
	}

	/**
	 * Return the HTML markup for the rendered form field.
	 *
	 * @param array      $atts             The array with the form field shortcode attributes.
	 * @param string     $field_type_slug  The form field slug.
	 * @param \Toolset_Field_Definition|null $field_definition The form field definition array or null.
	 * @param string     $button_text      The form button text.
	 *
	 * @return string    The HTML markup for the rendered form field.
	 */
	public function render_field( $atts, $field_type_slug, $field_definition, $button_text ) {
		$output = '';
		switch ( $field_type_slug ) {
			case 'button':
				$output = '<button disabled>' . esc_html( $button_text ) . '</button>';
				break;
			case 'textfield':
			case 'numeric':
			case 'embed':
			case 'email':
			case 'phone':
			case 'skype':
			case 'url':
			case 'colorpicker':
				$output = '<input type="text" disabled="disabled" />';
				break;
			case 'textarea':
				$output = '<textarea disabled="disabled"></textarea>';
				break;
			case 'wysiwyg':
				$output = '<textarea class="wysiwyg-editor" disabled="disabled">' . esc_html( __( 'WYSIWYG Editor', 'wpv-views' ) ) . '</textarea>';
				break;
			case 'select':
			case 'post': // Case for the post reference field.
				$default_option = __( 'Select an option', 'wpv-views' );
				if (
					isset( $atts['select_text'] )
					&& '' !== $atts['select_text']
				) {
					$default_option = $atts['select_text'];
				}
				$output = '<select disabled="disabled"><option>' . esc_html( $default_option ) . '</option></select>';
				break;
			case 'checkbox':
				$output = '<input type="checkbox" disabled="disabled" /> ' . esc_html( $field_definition->get_name() );
				break;
			case 'checkboxes':
			case 'radio':
				$field_type_slug_singular = 'checkboxes' == $field_type_slug ? 'checkbox' : $field_type_slug;
				$output .= '<ul>';
				$checkboxes_options = $field_definition->get_field_options();
				foreach ( $checkboxes_options as $option ) {
					$output .= '<li><input type="' . esc_attr( $field_type_slug_singular ) . '" disabled="disabled" /> ' . esc_html( $option->get_label() ) . '</li>';
				}
				$output .= '</ul>';
				break;
			case 'image':
			case 'file':
			case 'audio':
			case 'video':
				$output = '<button disabled>' . esc_html( __( 'Choose file', 'wpv-views' ) ) . '</button> <span>' . esc_html( __( 'No file chosen', 'wpv-views' ) ) . '</span>';
				break;
			case 'date':
				$calendar_image_readonly = WPTOOLSET_FORMS_RELPATH . '/images/calendar-readonly.gif';
				$output = '<input type="text" disabled="disabled" class="date" /><img src="' . esc_attr( $calendar_image_readonly ) . '" />';
				break;
			default:
				$output = 'Not handled ' . $field_type_slug;
				break;
		}

		if (
			null !== $field_definition
			&& $field_definition->get_is_repetitive()
		) {
			$output .= '<br /><a class="add-new">' . esc_html( __( 'Add new', 'wpv-views' ) ) . '</a>';
		}

		return $output;
	}
}
