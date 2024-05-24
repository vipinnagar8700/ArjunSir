<?php /** Translators: a message in the lock overlay that displays over a post editor.**/?>
<script type="text/html" id="js-toolset-lock-overlay-default-tpl">
	<p><?php esc_html_e( 'The content editor is not displayed because post content is rendered through Toolset.', 'toolset-common' ); ?></p>
	<p><?php printf( esc_html__( '%1$sShow editor anyway%2$s', 'toolset-common' ), '<span class="js-toolset-show-editor toolset-editor-controls">', '</span>' ); ?></p>
</script>
<?php /** Translators: a message below the editor area with a link to hide it with an overlay.**/?>
<script type="text/html" id="js-toolset-lock-overlay-default-message-tpl">
	<?php printf( esc_html__( 'This content is rendered through Toolset, so your edits in the content area on this page will not appear anywhere. %1$sHide editor area%2$s', 'toolset-common' ), '<span class="js-toolset-hide-editor toolset-editor-controls">', '</span>' ); ?>
</script>
