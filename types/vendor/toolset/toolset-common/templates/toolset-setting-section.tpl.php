<?php
/**
 * Template for a single section on a Toolset Settings tab.
 *
 * Context is passed via the $item_data array and following elements are supported:
 *
 * - slug (required)
 * - hidden
 * - title (required)
 * - below_title
 * - content
 * - callback
 *
 * @since unknown
 */

/** @var array $item_data */
$wrap_id_attribute = ( isset( $item_data['slug'] ) ? ' id="toolset-' . $item_data['slug'] . '"' : '' );
$wrap_classes = 'toolset-setting-container js-toolset-setting-container';
if ( isset( $item_data['slug'] ) ) {
	$wrap_classes .= ' js-toolset-' . $item_data['slug'];
}
$wrap_hidden_style = ( isset( $item_data['hidden'] ) ? 'style="display:none;"' : '' );

?>

<div
        <?php echo $wrap_id_attribute ?>
        class="<?php echo $wrap_classes ?>"
        <?php echo $wrap_hidden_style ?>
>
	<div class="toolset-settings-header">
		<h2><?php echo $item_data['title']; ?></h2>
		<?php
			if( array_key_exists( 'below_title', $item_data ) ) {
				echo $item_data['below_title'];
			}
		?>
	</div>
	<div class="toolset-setting">
		<?php 
		if ( isset( $item_data['content'] ) ) {
			echo $item_data['content'];
		}
		if ( 
			isset( $item_data['callback'] ) 
			&& is_callable( $item_data['callback'] )
		) {
			call_user_func( $item_data['callback'] );
		}
		?>
	</div>
</div>