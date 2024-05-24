<?php

/**
 * This function is just a bridge between new and legacy code
 *
 * @return type
 */
function wpcf_fields_post() {
    $factory = new Types_Field_Type_Post_Factory();
    $view_backend_creation = $factory->get_view_backend_creation( $factory->get_field() );

    return $view_backend_creation->legacy_get_settings_array();
}