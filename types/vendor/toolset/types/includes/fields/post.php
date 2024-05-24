<?php

/**
 * Bridge
 *
 * @param array $form_data
 * @param string $parent_name
 *
 * @return mixed
 */
function wpcf_fields_post_insert_form( $form_data = array(), $parent_name = '' ) {
	$factory = new Types_Field_Type_Post_Factory();
	$view_backend_creation = $factory->get_view_backend_creation( $factory->get_field() );

	return $view_backend_creation->legacy_get_input_array_for_post_type();
}

function wpcf_fields_post_get_option( $parent_name = '', $form_data = array() ) {
    $id = isset( $form_data['key'] ) ? $form_data['key'] : 'wpcf-fields-select-option-'
            . wpcf_unique_id( serialize( $form_data ) );
    $form = array();
    $value = isset( $_GET['count'] ) ? __( 'Option title', 'wpcf' ) . ' ' . intval( $_GET['count'] ) : __( 'Option title', 'wpcf' ) . ' 1';
    $value = isset( $form_data['title'] ) ? $form_data['title'] : $value;
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'placeholder' => __('Title', 'wpcf'),
        ),
        '#before' => sprintf(
            '<span class="js-types-sortable hndle"><i title="%s" class="js-types-sort-button fa fa-arrows-v"></i></span>',
            esc_attr__( 'Move this option', 'wpcf')
        ),
        '#pattern' => '<tr><td class="num"><BEFORE></td><td><ELEMENT><AFTER></td>',
    );
    $value = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
    $value = isset( $form_data['value'] ) ? $form_data['value'] : $value;
    $form[$id . '-value'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-value',
        '#name' => $parent_name . '[options][' . $id . '][value]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'class' => 'wpcf-compare-unique-value',
            'placeholder' => __('Value', 'wpcf'),
        ),
        '#pattern' => '<td><BEFORE><ELEMENT><AFTER></td>',
    );
    $form[$id . '-default'] = array(
        '#type' => 'radio',
        '#id' => $id . '-default',
        '#inline' => true,
        '#title' => __( 'Default', 'wpcf' ),
        '#after' => '</div>',
        '#name' => $parent_name . '[options][default]',
        '#value' => $id,
        '#default_value' => isset( $form_data['default'] ) ? $form_data['default'] : false,
        '#pattern' => '<td class="num"><BEFORE><ELEMENT></td><td class="num"><AFTER></td></tr>',
        '#after' => sprintf(
            '<span><a href="#" class="js-wpcf-button-delete" data-message-delete-confirm="%s" data-id="%s"><i title="%s" class="fa fa-trash"></i></span>',
            esc_attr__( 'Are you sure?', 'wpcf' ),
            esc_attr(sprintf('%s-title-display-value-wrapper', $id)),
            esc_attr__( 'Delete this option', 'wpcf' )
        ),
    );
    return $form;
}
