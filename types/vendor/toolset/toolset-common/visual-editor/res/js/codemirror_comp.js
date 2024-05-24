/*
 * Bring a compatibility layer to Codemirror.
 *
 * When third parties load the core-provided Codemirror library, they hijack the namespace with a cut-down version of it,
 * lacking some of the most important components like generating from a textarea or defining extensions.
 *
 * This layer makes sure that we merge both versions so we can use all the library resources.
 */
if (
	_.has( window, 'CodeMirror')
	&& ! _.has( CodeMirror, 'fromTextArea' )
	&& _.has( window, 'wp' )
	&& _.has( wp, 'CodeMirror' )
) {
	window.CodeMirror = jQuery.extend( true, window.CodeMirror, window.wp.CodeMirror );
}
