<?php
/**
 * Renders radio field
 *
 * Override this template in your own theme by creating a file at:
 *
 * [your-theme]/tribe-events/meta/radio.php
 *
 * @since 4.3.5
 * @since 4.10.2 Use md5() for field name slugs.
 * @since 4.10.7 Undo use of md5() within this file to fix editing existing responses.
 *
 * @version 4.12.1
 *
 * @var Tribe__Tickets_Plus__Meta__Field__Radio $this
 */
$options = $this->get_hashed_options_map();

if ( ! $options ) {
	return;
}

?>
<div class="tribe-tickets-meta tribe-tickets-meta-radio <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<header class="tribe-tickets-meta-label">
		<?php echo wp_kses_post( $field['label'] ); ?>
	</header>
	<?php
	foreach ( $options as $option_hash => $option_value ) {
		$option_id = "tribe-tickets-meta_{$this->slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_hash }";
		?>
		<label for="<?php echo esc_attr( $option_id ); ?>" class="tribe-tickets-meta-field-header">
			<input
				type="radio"
				id="<?php echo esc_attr( $option_id ); ?>"
				class="ticket-meta"
				name="tribe-tickets-meta[<?php echo esc_attr( $attendee_id ); ?>][<?php echo esc_attr( $this->slug ); ?>]"
				value="<?php echo esc_attr( $option_value ); ?>"
				<?php checked( $option_value, $value ); ?>
				<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
			>
			<span class="tribe-tickets-meta-option-label">
				<?php echo wp_kses_post( $option_value ); ?>
			</span>
		</label>
		<?php
	}
	?>
</div>
