<?php

class Tribe__Tickets_Plus__Meta__Render {
	public function __construct() {
		add_filter( 'tribe_tickets_attendee_table_columns', array( $this, 'insert_details_column' ), 20 );
		add_filter( 'tribe_events_tickets_attendees_table_column', array( $this, 'populate_details_column' ), 10, 3 );
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'ticket_email_meta' ) );
		add_action( 'event_tickets_attendees_table_after_row', array( $this, 'table_meta_data' ) );
	}

	/**
	 * Register an additional column, to be added next to 'primary_info' column,
	 * to allow access to attendee meta details.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function insert_details_column( array $columns ) {
		return Tribe__Main::array_insert_after_key( 'primary_info', $columns, array(
			'meta_details' => esc_html_x( 'Details', 'attendee table meta', 'event-tickets-plus' ),
		) );
	}

	/**
	 * Populates the meta details column.
	 *
	 * @param string $value
	 * @param array  $item
	 * @param string $column
	 *
	 * @return string
	 */
	public function populate_details_column( $value, $item, $column ) {
		if ( 'meta_details' !== $column ) {
			return $value;
		}

		$toggle = $this->get_meta_toggle( $item );
		return $toggle;
	}

	public function get_meta_toggle( array $item ) {
		$meta_data = get_post_meta( $item['attendee_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( ! $meta_data ) {
			return '<span class="event-tickets-no-meta-toggle">&ndash;</span>';
		}


		$view_details = sprintf( esc_html__( 'View details %s', 'event-tickets-plus' ), '&#9660;' ); // "&#9660;" := downward arrow
		$hide_details = sprintf( esc_html__( 'Hide details %s', 'event-tickets-plus' ), '&#9650;' ); // "&#9650;" := upward arrow

		return '
			<a href="" class="event-tickets-meta-toggle">
				<span class="event-tickets-meta-toggle-view">' . $view_details . '</span>
				<span class="event-tickets-meta-toggle-hide">' . $hide_details . '</span>
			</a>
		';
	}

	public function table_meta_data( $item ) {
		if ( ! isset( $item['product_id'] ) || ! isset( $item['attendee_id'] ) ) {
			return;
		}

		wp_enqueue_style( 'event-tickets-meta' );
		wp_enqueue_script( 'event-tickets-meta-report' );

		$meta_fields   = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $item['product_id'] );
		$meta_data     = get_post_meta( $item['attendee_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );
		$orphaned_data = (array) $meta_data;

		$valid_meta_html    = '';
		$orphaned_meta_html = '';

		foreach ( $meta_fields as $field ) {
			if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
				$values = array();
				foreach ( $field->extra['options'] as $option ) {
					if ( '' === $option ) {
						continue;
					}

					// Support longer options by using the hash of the string.
					$key = $field->slug . '_' . md5( sanitize_title( $option ) );

					if ( ! isset( $meta_data[ $key ] ) ) {
						// Support existing fields that did not save with md5 hash.
						$key = $field->slug . '_' . sanitize_title( $option );
					}

					if ( isset( $meta_data[ $key ] ) ) {
						$values[] = $meta_data[ $key ];
						unset( $orphaned_data[ $key ] );
					}
				}

				// There were no values for this checkbox.
				if ( empty( $values ) ) {
					continue;
				}

				$value = implode( ', ', $values );
			} elseif ( isset( $meta_data[ $field->slug ] ) ) {
				$value = $meta_data[ $field->slug ];
				unset( $orphaned_data[ $field->slug ] );
			} else {
				continue;
			}

			if ( '' === trim( $value ) ) {
				$value = '&nbsp;';
			}

			$valid_meta_html .= '
				<dt class="event-tickets-meta-label_' . sanitize_html_class( $field->slug ) . '">
					' . wp_kses_post( $field->label ) . '
				</dt>
				<dd class="event-tickets-meta-data_' . sanitize_html_class( $field->slug ) . '">
					' . wp_kses_post( $value ) . '
				</dd>
			';
		}

		if ( ! empty( $valid_meta_html ) ) {
			$valid_meta_html = '<dl>' . $valid_meta_html . '</dl>';
		}

		foreach ( $orphaned_data as $key => $value ) {
			$value = trim( $value );

			// There is no value for this meta.
			if ( '' === $value ) {
				continue;
			}

			$key = esc_html( $key );
			$value = esc_html( $value );

			$orphaned_meta_html .= "
				<dt class='event-tickets-orphaned-meta-label'> $key </dt>
				<dd class='event-tickets-orphaned-meta-data'> $value </dd>
			";
		}

		if ( ! empty( $orphaned_meta_html ) ) {
			$orphaned_meta_html = '
				<h4>' . esc_html_x( 'Other attendee data:', 'orphaned attendee meta data', 'event-tickets-plus' ) . '</h4>
				<dl>' . $orphaned_meta_html . '</dl>
			';
		}

		?>
		<tr class="event-tickets-meta-row">
			<th></th>
			<td colspan="6">
				<?php echo $valid_meta_html; ?>
				<?php echo $orphaned_meta_html; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Inject custom meta in to tickets
	 *
	 * @param array $item Attendee data
	 */
	public function ticket_email_meta( $item ) {
		if ( ! isset( $item['product_id'] ) ) {
			return;
		}

		$meta_fields = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $item['product_id'] );

		/**
 		 * Filters the ticket meta fields to be included in the tickets email
 		 *
  		 * @since 4.5.4
  		 *
  		 * @param array $meta_fields
  		 * @param array $item
  		 */
		$meta_fields = apply_filters( 'tribe_event_tickets_plus_email_meta_fields', $meta_fields, $item );

		$meta_data = get_post_meta( $item['qr_ticket_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( ! $meta_fields || ! $meta_data ) {
			return;
		}

		?>
		<table class="inner-wrapper" border="0" cellpadding="0" cellspacing="0" width="620" bgcolor="#f7f7f7" style="margin:0 auto !important; width:620px; padding:0;">
			<tr>
				<td valign="top" class="ticket-content" align="left" width="580" border="0" cellpadding="20" cellspacing="0" style="padding:20px; background:#f7f7f7;" colspan="2">
					<h6 style="color:#909090 !important; margin:0 0 4px 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; text-transform:uppercase; font-size:13px; font-weight:700 !important;"><?php esc_html_e( 'Attendee Information', 'event-tickets-plus' ); ?></h6>
				</td>
			</tr>
			<?php
			foreach ( $meta_fields as $field ) {
				if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
					$values = [];

					foreach ( $field->extra['options'] as $option ) {
						if ( '' === $option ) {
							continue;
						}

						// Support longer options by using the hash of the string.
						$key = $field->slug . '_' . md5( sanitize_title( $option ) );

						if ( ! isset( $meta_data[ $key ] ) ) {
							// Support existing fields that did not save with md5 hash.
							$key = $field->slug . '_' . sanitize_title( $option );
						}

						if ( isset( $meta_data[ $key ] ) ) {
							$values[] = $meta_data[ $key ];
						}
					}

					// There were no values for this checkbox.
					if ( empty( $values ) ) {
						continue;
					}

					$value = implode( ', ', $values );
				} elseif ( isset( $meta_data[ $field->slug ] ) ) {
					$value = $meta_data[ $field->slug ];
				} else {
					continue;
				}

				if ( '' === trim( $value ) ) {
					$value = '&nbsp;';
				}
				?>
				<tr>
					<th valign="top" class="event-tickets-meta-label_<?php echo sanitize_html_class( $field->slug ); ?>" align="left" border="0" cellpadding="20" cellspacing="0" style="padding:0 20px; background:#f7f7f7;min-width:100px;">
						<?php echo wp_kses_post( $field->label ); ?>
					</th>
					<td valign="top" class="event-tickets-meta-data_<?php echo sanitize_html_class( $field->slug ); ?>" align="left" border="0" cellpadding="20" cellspacing="0" style="padding:0 20px; background:#f7f7f7;">
						<?php echo wp_kses_post( $value ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}
}
