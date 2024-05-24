<?php
/**
 * Renders field
 *
 * Override this template in your own theme by creating a file at:
 *
 * [your-theme]/tribe-events/meta/birth.php
 *
 * @since   4.12.1
 *
 * @var Tribe__Tickets_Plus__Meta__Field__Birth $this
 */
$option_id = "tribe-tickets-meta_{$this->slug}" . ( $attendee_id ? '_' . $attendee_id : '' );

$classes = [
	'tribe-tickets-meta'          => true,
	'tribe-tickets-meta-birth'    => true,
	'tribe-tickets-meta-required' => $required,
];

?>
<div class="tribe_horizontal_datepicker__container">
	<div <?php tribe_classes( $classes ) ?>>
		<label for="<?php echo esc_attr( $option_id ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>

		<!-- Month -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__month"
			>
				<option value="" disabled selected><?php esc_html_e( 'Month', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $this->get_months() as $month_number => $month_name ) : ?>
					<option value="<?php echo esc_attr( $month_number ); ?>"><?php echo esc_html( $month_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<!-- Day -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__day"
			>
				<option value="" disabled selected><?php esc_html_e( 'Day', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $this->get_days() as $birth_day ) : ?>
					<option value="<?php echo esc_attr( $birth_day ); ?>"><?php echo esc_html( $birth_day ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<!-- Year -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__year"
			>
				<option value="" disabled selected><?php esc_html_e( 'Year', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $this->get_years() as $birth_year ) : ?>
					<option value="<?php echo esc_attr( $birth_year ); ?>"><?php echo esc_html( $birth_year ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<input
				type="hidden"
				id="<?php echo esc_attr( $option_id ); ?>"
				class="ticket-meta tribe_horizontal_datepicker__value"
				name="tribe-tickets-meta[<?php echo esc_attr( $attendee_id ); ?>][<?php echo esc_attr( $this->slug ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				<?php echo $required ? 'required' : ''; ?>
				<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
			/>
		</div>
	</div>
</div>
