<?php

class Tribe__Tickets_Plus__Meta__Contents {
	public function get_ticket_stored_meta( $tickets = [] ) {
		$stored_data = [];
		$storage     = new Tribe__Tickets_Plus__Meta__Storage;

		foreach ( $tickets as $ticket_id => $quantity ) {
			$ticket_id = (int) $ticket_id;

			$stored_data[ $ticket_id ] = $storage->get_meta_data_for( $ticket_id );
		}

		return $stored_data;
	}

	/**
	 * Determines if the provided ticket/quantity array of tickets has all of the stored meta up to date
	 *
	 * Up to date means: Do all tickets have an entry in the storage transient and are all required fields populated?
	 *
	 * @since 4.9
	 *
	 * @param array $quantity_by_ticket_id Array indexed by ticket id with ticket quantities as the values
	 * @return boolean
	 */
	public function is_stored_meta_up_to_date( $quantity_by_ticket_id = [] ) {
		// if there aren't any tickets, consider them up to date
		if ( empty( $quantity_by_ticket_id ) ) {
			return true;
		}

		$stored_data = $this->get_ticket_stored_meta( $quantity_by_ticket_id );
		$meta        = Tribe__Tickets_Plus__Main::instance()->meta();

		foreach ( $quantity_by_ticket_id as $ticket_id => $quantity ) {
			$data        = empty( $stored_data[ $ticket_id ] ) ? [] : $stored_data[ $ticket_id ];
			$ticket_meta = $meta->get_meta_fields_by_ticket( $ticket_id );

			// Continue if the ticket doesn't have any meta
			if ( ! $meta->ticket_has_meta( $ticket_id ) ) {
				continue;
			}

			/**
			 * Return false if the data for the ticket is empty or if the number of items stored for
			 * the ticket is different than the quantity in the cart.
			 */
			if ( empty( $data[ $ticket_id ] ) || count( $data[ $ticket_id ] ) !== $quantity ) {
				return false;
			}

			// Going through the stored data, to see if there's a required field missing
			foreach ( $ticket_meta as $meta_field ) {
				$meta_slug = $meta_field->slug;

				// Skip fields if they are not required.
				if ( ! $meta->meta_is_required( $ticket_id, $meta_slug ) ) {
					continue;
				}

				foreach ( $data as $the_ticket => $the_meta ) {
					if ( empty( $the_meta ) ) {
						return false;
					}

					foreach ( $the_meta as $attendee_number => $meta_item ) {
						/*
						 * Give special treatment to checkboxes as they store
						 * differently from the rest of the fields.
						 */
						if ( 'checkbox' === $meta_field->type ) {
							$checkbox_values  = [];
							$checkbox_options = [];

							if ( ! empty( $meta_field->extra['options'] ) ) {
								$checkbox_options = $meta_field->extra['options'];
							}

							foreach ( $checkbox_options as $checkbox_option ) {
								$field_slug = sanitize_title( $meta_field->slug );
								$field_slug .= '_' . md5( sanitize_title( $checkbox_option ) );

								if ( isset( $meta_item[ $field_slug ] ) && '' !== $meta_item[ $field_slug ] ) {
									$checkbox_values[] = $checkbox_option;
								}
							}

							// Check if there are any checkbox values set for this field.
							if ( ! empty( $checkbox_values ) ) {
								continue;
							}

							// There's no checkbox value set if the $meta_item is not an array or it's empty.
							return false;
						}

						// There's no value set if the field slug is not set or it's empty.
						if (
							! isset( $meta_item[ $meta_slug ] )
							|| '' === $meta_item[ $meta_slug ]
						) {
							return false;
						}
					}
				}
			}
		}

		return true;
	}
}
