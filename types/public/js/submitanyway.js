/**
 * Prefixed and slightly modified version for Toolset Types.
 *
 * @license GPL 3.0
 * @link https://github.com/ilisepe1/submitanyway
 */

var Toolset = Toolset || {};
Toolset.Types = Toolset.Types || {};
Toolset.Types.SubmitAnyway = window.SubmitAnyway || {};

Toolset.Types.SubmitAnyway = function ( $ ) {

	//filter form elements with same name
	var merge = function( els, formSelector ) {
		var ret = [];
		var merged = [];
		$.each( els, function ( i, el ) {
			//console.log("name=" + el.name + ", type=" + el.type);
			var type = el.type;
			var name = el.name;
			if ( merged.indexOf( name ) < 0 && ( type === "radio" || type === "checkbox" ) ) {
				merged.push( name );
				$( formSelector + ' input[name="' + name + '"]:checked' ).each( function () {
					// .serialize() ?
					var tmpEl = { "name": name, "value": this.value };
					ret.push( tmpEl );
				} );
			} else if ( merged.indexOf( name ) < 0 && type === "select-multiple" || type === "select-one" ) {
				merged.push( name );
				$( formSelector + ' select[name="' + name + '"] :selected' ).each( function () {
					var tmpEl = { "name": name, "value": this.value };
					ret.push( tmpEl );
				} );
			} else if ( merged.indexOf( name ) < 0 && ( type === "text" || type === "hidden" || type === 'textarea' ) ) {
				var elementSelector = formSelector + ' input[name="' + name + '"]';
				if ( type === 'textarea' ) {
					elementSelector = formSelector + ' textarea[name="' + name + '"]'
				}
				ret.push( { 'name': name, 'value': $( elementSelector ).first().val() } );
			}

		} );
		return ret;
	};

	//delete temp hidden x-submitanyway-temp elements
	var cleanup = function(selector) {
		$( selector + " [x-submitanyway-temp]" ).remove();
	};

	var onSubmit = function( e, formElement ) {
		// Had to remove "e.preventDefault();" from here, since it was triggering a "confirmUnload" message
		// in the browser through some TinyMCE dark magic.

		// Make sure all TinyMCE editors are saved before we grab the values from their underlying textareas.
		// This should address the race condition on the "submit" event.
		if ( 'undefined' !== typeof( window.tinyMCE ) ) {
			window.tinyMCE.triggerSave();
		}

		// Another adjustment: Survive forms that are identified only by a class name and make sure everything
		// keeps working even if there are multiple forms with submitanyway on the same page.
		var id = !! formElement ? formElement.id : this.id;
		var className = !! formElement ? formElement.className : this.className;
		var formSelector = !! id ? '#' + id : '.' + className;

		cleanup( formSelector );

		//get values of form elements with data-submitanyway attributes
		//and add them to form
		var params = merge( $( formSelector + " [data-submitanyway]" ), formSelector );
		//console.log("params=", params);
		if ( params != null && params.length > 0 ) {
			$.each( params, function ( i, param ) {
				// console.log("adding x-temp: name=" + param.name + ", value=" + param.value);
				$( '<input />' ).attr( 'type', 'hidden' )
						.attr( 'name', param.name )
						.attr( 'value', param.value )
						.attr( 'x-submitanyway-temp', "" )
						.appendTo( formSelector );
			} );
		}
	};

	var onSubmitWrapper = function( formElement ) {
		onSubmit( null, formElement )
	}

	//the main handler
	var submitanyway = function ( selector ) {
		$( selector ).submit( onSubmit );
	};

	return {
		"submitanyway": submitanyway,
		"onsubmit": onSubmitWrapper,
		"cleanup": cleanup
	}

}( jQuery );
