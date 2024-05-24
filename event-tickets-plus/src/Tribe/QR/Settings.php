<?php

/**
 * Adds settings relating directly to handling of ticket QR codes to the
 * Events ‣ Settings ‣ Tickets admin screen.
 *
 * @since 4.7.5
 */
class Tribe__Tickets_Plus__QR__Settings {

	/**
	 * Hook into Event Tickets/Event Tickets Plus.
	 *
	 * @since 4.7.5
	 */
	public function hook() {
		add_filter( 'tribe_tickets_settings_tab_fields', [ $this, 'add_settings' ] );
		add_action( 'wp_ajax_tribe_tickets_plus_generate_api_key', [ $this, 'generate_key' ] );
	}

	/**
	 * Append global Event Tickets Plus settings section to tickets settings tab
	 *
	 * @since 4.7.5
	 *
	 * @param array $settings_fields
	 *
	 * @return array
	 */
	public function add_settings( array $settings_fields ) {
		$extra_settings = $this->additional_settings();

		return Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $settings_fields, $extra_settings );
	}

	/**
	 * Adds the general ticket QR code settings to the Events ‣ Settings ‣ Tickets screen.
	 *
	 * @since 4.7.5
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function additional_settings( array $settings = [] ) {
		$ticket_label_plural_lower = esc_html( tribe_get_ticket_label_plural_lowercase( 'check_in_app' ) );

		return Tribe__Main::array_insert_before_key(
			'tribe-form-content-end', $settings, [
				'tickets-plus-qr-options-title'            => [
					'type' => 'html',
					'html' => '<h3>' . esc_html__( 'QR Codes', 'event-tickets-plus' ) . '</h3>',
				],
				'tickets-plus-qr-options-intro'            => [
					'type' => 'html',
					'html' => '<p>'
						. sprintf(
							esc_html__(
								'Emailed %1$s can include QR codes to provide secure check in for your attendees. %2$sDownload our Registration app%3$s for an easy mobile check-in process that syncs with your attendee records.', 'event-tickets-plus'
							),
							$ticket_label_plural_lower,
							'<a href="https://m.tri.be/1a55" target="_blank">',
							'</a>'
						)
						. '</p>',
				],
				'tickets-enable-qr-codes'                  => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html( sprintf( __( 'Show QR codes on %s', 'event-tickets-plus' ), $ticket_label_plural_lower ) ),
					'default'         => true,
					'validation_type' => 'boolean',
				],
				'tickets-plus-qr-options-api-key'          => [
					'type'            => 'text',
					'label'           => sprintf( esc_html__( '%s check-in app API key', 'event-tickets-plus' ), tribe_get_ticket_label_plural( 'check_in_app' ) ),
					'tooltip'         => esc_html__( 'Enter this API key in the settings of your Event Registrations Plus app to activate check in.', 'event-tickets-plus' ),
					'size'            => 'medium',
					'validation_type' => 'alpha_numeric_with_dashes_and_underscores',
					'can_be_empty'    => true,
					'parent_option'   => Tribe__Main::OPTIONNAME,
				],
				'tickets-plus-qr-options-generate-api-key' => [
					'type' => 'html',
					'html' => '<fieldset class="tribe-field tribe-field-html">
							<legend>' . esc_html__( 'Generate Key', 'event-tickets-plus' ) . '</legend>
							<div class="tribe-field-wrap"><a href="' . Tribe__Settings::instance()->get_url(
							[
								'page' => 'event-tickets-plus',
								'tab'  => 'event-tickets',
							]
						) . '" class="button tribe-generate-qr-api-key">' . esc_html__( 'Generate API Key', 'event-tickets-plus' ) . '</a>
								<p class="tooltip description">'
						. esc_html( sprintf(
							__( 'If you change the API key then agents will no longer be able to check-in %s until they add the new key in their app settings.', 'event-tickets-plus' ),
							$ticket_label_plural_lower
						) )
						. '</p>
								<div class="tribe-generate-qr-api-key-msg"></div>
							</div>
						   </fieldset>
						   <div class="clear"></div>',
				],
			]
		);
	}

	/**
	 * Generate QR API Key
	 *
	 * @since 4.7.5
	 *
	 */
	public function generate_key() {

		$confirm = tribe_get_request_var( 'confirm', false );

		if ( ! $confirm || ! wp_verify_nonce( $confirm, 'generate_qr_nonce' ) ) {
			wp_send_json_error( __( 'Permission Error', 'event-tickets-plus' ) );
		}

		$random  = $this->generate_random_int();
		$api_key = $this->generate_qr_api_hash( $random );

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'The QR API key was not generated, please try again.', 'event-tickets-plus' ) );
		}

		Tribe__Settings_Manager::set_option( 'tickets-plus-qr-options-api-key', $api_key );

		$data = [
			'msg' => __( 'QR API Key Generated', 'event-tickets-plus' ),
			'key' => $api_key,
		];

		wp_send_json_success( $data );

	}

	/**
	 * Generate a random number for the QR API Key
	 *
	 * @since 4.7.5
	 *
	 * @return int $random a random number
	 */
	protected function generate_random_int() {
		$random = base_convert( mt_rand( 0, mt_getrandmax() ), 10, 32 );

		/**
		 * Filters the random number generated for QR API key
		 *
		 * @since 4.7.5
		 *
		 * @param int $random a random number
		 */
		return apply_filters( 'tribe_tickets_plus_qr_api_random_int', $random );
	}

	/**
	 * Generate a hash key for QR API
	 *
	 * @since 4.7.5
	 *
	 * @return int $random a random number
	 */
	protected function generate_qr_api_hash( $random ) {
		$api_key = substr( md5( $random ), 0, 8 );

		/**
		 * Filters the generated hash key for QR API
		 *
		 * @since 4.7.5
		 *
		 * @param string $api_key a API key string
		 */
		return apply_filters( 'tribe_tickets_plus_qr_api_hash', $api_key );
	}
}