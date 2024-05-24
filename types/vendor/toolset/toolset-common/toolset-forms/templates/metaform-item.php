<?php
$has_output_bootstrap = (isset( $cfg['attribute']['output'] ) && $cfg['attribute']['output'] == 'bootstrap');

$is_repeatable = (bool) toolset_getarr( $cfg, 'repetitive' );

if ( Toolset_Utils::is_real_admin() ) {
	// This is necessary to disable the GUI for managing values of a repeatable fields, which needs to be
	// disabled in this case.
	$is_copied_field = function_exists( 'wpcf_wpml_field_is_copied' ) && wpcf_wpml_field_is_copied( $cfg );

	?>
	<div class="js-wpt-field-item wpt-field-item">
		<?php
		if ( $is_repeatable && ! $is_copied_field) {
			?>
			<a class="js-wpt-repdrag wpt-repdrag dashicons dashicons-move" title="<?php _e( 'Drag to change order', 'wpv-views' );?>">&nbsp;</a>
			<?php
		}

		echo $out;

		if ( $is_repeatable && ! $is_copied_field) {
			?>
			<a class="js-wpt-repdelete wpt-repdelete" data-wpt-type="<?php echo $cfg['type']; ?>"
				data-wpt-id="<?php echo $cfg['id']; ?>">
				<?php echo apply_filters( 'toolset_button_delete_repetition_text', __( 'Remove', 'wpv-views' ), $cfg ); ?>
			</a>
			<?php
		}

		?>
	</div>
	<?php
} else {
	$toolset_repdrag_image = '';
	$button_extra_classnames = '';
	if ( $has_output_bootstrap ) {
		if ( $is_repeatable ) {
			echo '<div class="wpt-repctl wpt-repctl-flex">';
			echo '<div class="wpt-repetitive-controls">';
			echo '<span role="button" class="js-wpt-repdrag wpt-repdrag dashicons dashicons-move"></span>';
			$str = sprintf( __( '%s repetition', 'wpv-views' ), $cfg['title'] );
			echo '<span role="button" class="js-wpt-repdelete wpt-repdelete dashicons-before dashicons-trash" title="';
			echo apply_filters( 'toolset_button_delete_repetition_text', esc_attr( __( 'Remove', 'wpv-views' ) ) . " " . esc_attr( $str ), $cfg );
			echo '"></span>';
			echo '</div>';
			echo '<div class="wpt-repetitive-field">';
		}
		echo $out;
		if ( $is_repeatable ) {
			echo '</div>';
			echo '</div>';
		}
	} else {
		if ( $is_repeatable ) {
			$toolset_repdrag_image = apply_filters( 'wptoolset_filter_wptoolset_repdrag_image', $toolset_repdrag_image );
			echo '<div class="wpt-repctl">';
			echo '<span class="js-wpt-repdrag wpt-repdrag"><img class="wpv-repdrag-image" src="' . $toolset_repdrag_image . '" /></span>';
		}
		echo $out;
		if ( $is_repeatable ) {
			if ( ! $has_output_bootstrap && array_key_exists( 'use_bootstrap', $cfg ) && $cfg['use_bootstrap'] ) {
				switch( Toolset_Settings::get_instance()->bootstrap_version_numeric ) {
					case \OTGS\Toolset\Common\Settings\BootstrapSetting::NUMERIC_BS4:
						$button_extra_classnames = ' btn btn-secondary btn-sm';
						break;
					default:
						$button_extra_classnames = ' btn btn-default btn-sm';
						break;
				}

			}
			$str = sprintf( __( '%s repetition', 'wpv-views' ), $cfg['title'] );
			echo '<input type="button" href="#" class="js-wpt-repdelete wpt-repdelete' . $button_extra_classnames . '" value="';
			echo apply_filters( 'toolset_button_delete_repetition_text', esc_attr( __( 'Remove', 'wpv-views' ) ) . " " . esc_attr( $str ), $cfg );
			echo '" />';
			echo '</div>';
		}
	}
}
