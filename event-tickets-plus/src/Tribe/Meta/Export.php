<?php
/**
 * Handles adding attendee meta data to attendee list exports.
 */
class Tribe__Tickets_Plus__Meta__Export {
	/**
	 * List of the possible meta columns for any given event.
	 *
	 * @var array
	 */
	protected $meta_columns = array();


	/**
	 * Listen out for the generation of a filtered (exportable) attendee list:
	 * we don't need to do anything unless that fires.
	 */
	public function __construct() {
		add_action( 'tribe_events_tickets_generate_filtered_attendees_list', array( $this, 'setup_columns' ) );
	}

	/**
	 * If the current event has tickets that support attendee meta data, hook into
	 * the list to add the appropriate number of extra columns.
	 *
	 * @param int $event_id
	 */
	public function setup_columns( $event_id ) {
		$this->meta_columns = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_event( $event_id );

		// Add Orphaned Columns
		$this->add_orphaned_columns( $event_id );

		if ( empty( $this->meta_columns ) ) {
			return;
		}

		//Add Handler for Community Tickets to Prevent Notices in Exports
		if ( ! is_admin() ) {
			$screen_base = 'tribe_events_page_tickets-attendees';
		} else {
			$screen      = get_current_screen();
			$screen_base = $screen->base;
		}
		$filter_name = "manage_{$screen_base}_columns";

		add_filter( $filter_name, array( $this, 'add_columns' ), 20 );
		add_filter( 'tribe_events_tickets_attendees_table_column', array( $this, 'populate_columns' ), 10, 3 );
	}

	/**
	 * Add orphaned columns to the export based on what we have for
	 * the event attendees.
	 *
	 * @since 4.8.3
	 *
	 * @param int $event_id the event to fetch the attendee data from
	 *
	 * @return void
	 */
	public function add_orphaned_columns( $event_id ) {
		// Go through the event attendees and get the fields.
		foreach ( Tribe__Tickets__Tickets::get_event_attendees( $event_id ) as $attendee ) {
			// Get the meta fields of that attendee.
			$meta_fields = get_post_meta( $attendee['attendee_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );

			// If we have some meta fields.
			if ( ! is_array( $meta_fields ) ) {
				return;
			}

			// Go through the meta fields.
			foreach ( $meta_fields as $key => $value ) {
				$this->add_orphaned_column( $key );
			}
		}
	}

	/**
	 * Add orphaned column to the export based on what we have for
	 * the event attendees.
	 *
	 * @since 4.8.3
	 *
	 * @param string $key the column key
	 * @param string $value the column label
	 *
	 * @return array $field
	 */
	public function add_orphaned_column( $key ) {

		// Bail if the field is already part of the columns
		if ( $this->column_exists( $key ) ) {
			return false;
		}

		// Add the column with a format that
		// can be processed by the exporter
		$field          = array();
		$field['type']  = 'orphaned';
		$field['slug']  = $key;
		$field['label'] = ucwords( str_replace( '-', ' ', str_replace( '_', ': ', $key ) ) );

		// Add the field to the columns
		$this->meta_columns[] = (object) $field;

		return $field;

	}

	/**
	 * Check if a column exists, by checking agains the object slug
	 *
	 * @since 4.8.3
	 *
	 * @param string $key The slug we want to compare
	 *
	 * @return bool
	 */
	public function column_exists( $key ) {

		foreach ( $this->meta_columns as $column ) {
			// Bail if the column key exists or if it's a checkbox
			if (
				$column->slug === $key
				|| 'checkbox' === $column->type
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add headers for our extra columns.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_columns( $columns ) {

		foreach ( $this->meta_columns as $meta_field ) {
			if ( 'checkbox' === $meta_field->type && isset( $meta_field->extra['options'] ) ) {
				foreach ( $meta_field->extra['options'] as $option ) {
					$key = $meta_field->slug . '_' . sanitize_title( $option );

					$columns[ $key ] = "{$meta_field->label}: {$option}";
				}
				continue;
			}

			$columns[ $meta_field->slug ] = $meta_field->label;
		}

		return $columns;
	}

	/**
	 * Handle the actual population of attendee meta fields.
	 *
	 * @param string $existing
	 * @param array  $item
	 * @param string $column
	 *
	 * @return string
	 */
	public function populate_columns( $existing, $item, $column ) {
		$meta_data = get_post_meta( $item['attendee_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( isset( $meta_data[ $column ] ) ) {
			return $meta_data[ $column ];
		}

		/**
		 * Allow plugins to remove support for checkbox field values being displayed or override the text shown.
		 *
		 * @since 4.10.4
		 *
		 * @param string|false $checkbox_label Label value of checkbox that is checked, return false to turn off label support.
		 */
		$checkbox_label = apply_filters( 'tribe_events_tickets_plus_attendees_list_checkbox_label', __( 'Checked', 'event-tickets-plus' ) );

		if ( false === $checkbox_label ) {
			return $existing;
		}

		// Checkbox support
		$checkbox_parts = explode( '_', $column );

		if ( 2 !== count( $checkbox_parts ) ) {
			return $existing;
		}

		$key = $checkbox_parts[0] . '_' . md5( $checkbox_parts[1] );

		if ( isset( $meta_data[ $key ] ) ) {
			return $checkbox_label;
		}

		return $existing;
	}
}
