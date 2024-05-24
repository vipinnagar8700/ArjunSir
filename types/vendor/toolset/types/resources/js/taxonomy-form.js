/**
 *
 * Taxonomies form JS
 *
 *
 */

jQuery( function( $ ) {
    $( '.wpcf-tax-form' ).on( 'submit', function() {
        return $( this ).wpcfProveSlug();
    } );
} );
