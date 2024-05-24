// eslint-disable-next-line no-var
var ToolsetTypes = ToolsetTypes || {};

ToolsetTypes.ExportImportScreen = function( $ ) {
	// const self = this;

	$( document ).on( 'change', '.js-types-import-method input', function() {
		const selected = $( '.js-types-import-method input:checked' ).val();
		$( '.js-types-import-method-extra' ).hide();
		$( '.js-types-import-method-extra-' + selected ).fadeIn( 'fast' );
	} );

	$( document ).on( 'click', '.js-show-tooltip', function() {
		ToolsetTypes.Utils.Pointer.show( this );
	} );
};

jQuery( function( $ ) {
	ToolsetTypes.export_import_screen = new ToolsetTypes.ExportImportScreen( $ );
} );
