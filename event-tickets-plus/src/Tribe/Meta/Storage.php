<?php


/**
 * Class Tribe__Tickets_Plus__Meta__Storage
 *
 * Handles CRUD operations of attendee meta temporary storage.
 *
 * @since 4.2.6
 */
class Tribe__Tickets_Plus__Meta__Storage {

	/**
	 * The index used to store attendee meta information in the $_POST global.
	 */
	const META_DATA_KEY = 'tribe-tickets-meta';

	/**
	 * The prefix prepended to the transient created to store the ticket meta
	 * information; an hash will be appended to it.
	 */
	const TRANSIENT_PREFIX = 'tribe_tickets_meta_';

	/**
	 * The name of the cookie storing the hash of the transient storing the ticket meta.
	 */
	const HASH_COOKIE_KEY = 'tribe-event-tickets-plus-meta-hash';

	/**
	 * @var array
	 */
	protected $data_cache = [];

	/**
	 * The time in seconds after which the ticket meta transient will expire.
	 *
	 * Defaults to a day.
	 *
	 * @var int
	 */
	protected $ticket_meta_expire_time = 86400;

	/**
	 * A flag to prevent maybe_update_ticket_meta_cookie from running more than necessary.
	 *
	 * This is required because we only want to update the ticket meta cookie once per request,
	 * however multiple objects of this type may be created (once by the RSVP provider, for
	 * example, and once by the WooCommerce provider).
	 *
	 * @var boolean
	 */
	private static $has_updated_meta_cookie = false;

	/**
	 * Sets or updates the attendee meta cookies and returns the name of the transient storing them.
	 *
	 * @param null|array  $ticket_meta List of ticket meta to save, null if using $_POST.
	 * @param null|string $provider    Provider name.
	 *
	 * @return string|false The hash key or false if not set.
	 */
	public function maybe_set_attendee_meta_cookie( $ticket_meta = null, $provider = null ) {
		if ( null === $ticket_meta ) {
			if ( ! isset( $_POST[ self::META_DATA_KEY ] ) ) {
				return false;
			}

			// Skip the auto-handling for saving tickets if coming from new AR flow.
			if ( ! empty( $_POST['tribe_tickets_ar'] ) ) {
				return false;
			}

			// Skip the edit screen flow.
			if ( ! empty( $_POST['process-tickets'] ) ) {
				return false;
			}

			$ticket_meta = $_POST[ self::META_DATA_KEY ];
		}

		// Bad meta.
		if ( ! is_array( $ticket_meta ) ) {
			return false;
		}

		// Delete the meta.
		if ( empty( $ticket_meta ) ) {
			$this->delete_meta_data();

			return false;
		}

		// Update the cookie / meta.
		if ( null !== $this->get_hash_cookie() ) {
			return $this->maybe_update_ticket_meta_cookie( $ticket_meta, $provider );
		}

		// Set the cookie / meta.
		return $this->set_ticket_meta_cookie( $ticket_meta, $provider );
	}

	/**
	 * Sets the ticket meta cookie.
	 *
	 * @param array       $ticket_meta List of ticket meta to save.
	 * @param null|string $provider    Provider name.
	 *
	 * @return string|bool The transient hash or `false` if the transient setting
	 *                     failed.
	 */
	protected function set_ticket_meta_cookie( $ticket_meta = null, $provider = null ) {
		if ( empty( $ticket_meta ) || ! is_array( $ticket_meta ) ) {
			return false;
		}

		// Generate a new hash key.
		$hash_key = uniqid();

		$ticket_meta = Tribe__Utils__Array::escape_multidimensional_array( $ticket_meta );

		// Clean up attendee meta (even though there's nothing to merge with.
		$ticket_meta = $this->combine_new_and_saved_attendee_meta( $ticket_meta, [] );

		$set = $this->update_meta_data( $ticket_meta, null, $hash_key );

		if ( ! $set ) {
			return false;
		}

		$this->set_hash_cookie( $hash_key, $ticket_meta, $provider );

		return $hash_key;
	}

	/**
	 * Create a transient to store the attendee meta information if not set already.
	 *
	 * @param array $ticket_meta List of ticket meta to save.
	 *
	 * @return string|bool The transient hash or `false` if the cookie setting
	 *                     was not needed or failed.
	 */
	private function maybe_update_ticket_meta_cookie( $ticket_meta ) {
		if ( empty( $ticket_meta ) || ! is_array( $ticket_meta ) ) {
			return false;
		}

		$hash_key = $this->get_hash_cookie();

		$has_updated_meta_cookie = self::$has_updated_meta_cookie;

		/**
		 * Allows for the "has updated meta cookie" flag to be manually overridden.
		 *
		 * @since 4.5.6
		 *
		 * @param boolean $has_updated_meta_cookie
		 */
		$has_updated_meta_cookie = apply_filters( 'tribe_tickets_plus_meta_cookie_flag', $has_updated_meta_cookie );

		if ( $has_updated_meta_cookie ) {
			return $hash_key;
		}

		$ticket_meta = Tribe__Utils__Array::escape_multidimensional_array( $ticket_meta );

		$stored_ticket_meta = $this->get_meta_data();

		// Prevents Catchable Fatal when it doesn't exist or is a scalar
		if ( empty( $stored_ticket_meta ) || is_scalar( $stored_ticket_meta ) ) {
			$stored_ticket_meta = [];
		}

		// Merge the new with currently saved meta.
		$merged = $this->combine_new_and_saved_attendee_meta( $ticket_meta, $stored_ticket_meta );

		// Update meta data in transient.
		$set = $this->update_meta_data( $merged );

		if ( ! $set ) {
			return false;
		}

		return $hash_key;
	}

	/**
	 * Sets the transient hash id in a cookie.
	 *
	 * @param string      $hash_key    Hash key.
	 * @param array       $ticket_meta List of ticket meta being saved.
	 * @param null|string $provider    Provider name.
	 */
	protected function set_hash_cookie( $hash_key, $ticket_meta = [], $provider = null ) {
		if ( ! headers_sent() ) {
			setcookie( self::HASH_COOKIE_KEY, $hash_key, 0, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}

		$_COOKIE[ self::HASH_COOKIE_KEY ] = $hash_key;

		/**
		 * Allow hooking into after we set the hash cookie.
		 *
		 * @since 4.11.0
		 *
		 * @param string      $hash_key    Hash key.
		 * @param array       $ticket_meta List of ticket meta being saved.
		 * @param null|string $provider    Provider name.
		 */
		do_action( 'tribe_tickets_plus_meta_storage_set_hash_cookie', $hash_key, $ticket_meta, $provider );
	}

	/**
	 * Gets the hash value from the cookie or commerce provider.
	 *
	 * @since 4.9
	 * @since 4.11.0 Added $id parameter and filter to help with context for commerce providers.
	 *
	 * @param null|int $id The ticket ID.
	 *
	 * @return string|null The hash value or null if not found.
	 */
	public function get_hash_cookie( $id = null ) {
		$hash = null;

		if ( isset( $_COOKIE[ self::HASH_COOKIE_KEY ] ) ) {
			$hash = sanitize_text_field( $_COOKIE[ self::HASH_COOKIE_KEY ] );
		}

		/**
		 * Allow filtering of hash cookie value.
		 *
		 * @since 4.11.0
		 *
		 * @param null|string $hash The hash value.
		 * @param null|int    $id   The ticket ID.
		 */
		return apply_filters( 'tribe_tickets_plus_meta_storage_get_hash_cookie', $hash, $id );
	}

	/**
	 * Gets the transient name to use and combines with the hash key.
	 *
	 * @since 4.11.0
	 *
	 * @param null|int    $id       The ticket ID.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return string|null The transient name or null if not found.
	 */
	public function get_transient_name( $id = null, $hash_key = null ) {
		if ( null === $hash_key ) {
			// Get the hash key from the cookie.
			$hash_key = $this->get_hash_cookie( $id );
		}

		if ( empty( $hash_key ) ) {
			return null;
		}

		return self::TRANSIENT_PREFIX . $hash_key;
	}

	/**
	 * Adds attendee meta from currently-being-bought tickets to tickets that are already in the cart.
	 *
	 * @since 4.5.6
	 *
	 * @param array $new The attendee meta data that's not yet been saved.
	 * @param array $saved The existing attendee data storied in cookies/transients.
	 *
	 * @return array The combined attendee meta to save.
	 */
	protected function combine_new_and_saved_attendee_meta( $new, $saved ) {
		$to_be_saved = $saved;

		foreach ( $new as $ticket_id => $data ) {
			$ticket_id = (int) $ticket_id;
			$data      = array_values( $data );

			/*
			 * The logic here used to merge $to_be_saved with $data and not totally overwrite it.
			 *
			 * This was changed in https://central.tri.be/issues/129188 so that submitting
			 * AR meta after adding/removing ticket quantity will result in AR flow being presented again.
			 */

			$to_be_saved[ $ticket_id ] = $data;
		}

		/**
		 * Allow filtering the meta to be saved.
		 *
		 * @since 4.11.0
		 *
		 * @param array $to_be_saved The combined attendee meta to save.
		 */
		$to_be_saved = apply_filters( 'tribe_tickets_plus_meta_storage_combine_new_and_saved_meta', $to_be_saved );

		return $to_be_saved;
	}

	/**
	 * Store temporary data as a transient.
	 *
	 * @param array $temporary_data Temporary data to store.
	 *
	 * @return string
	 */
	public function store_temporary_data( $temporary_data ) {
		$hash_key = uniqid();

		$this->update_meta_data( $temporary_data, null, $hash_key );

		return $hash_key;
	}

	/**
	 * Retrieve temporary data from a transient.
	 *
	 * @param string $hash_key Hash key to use for transient.
	 *
	 * @return array|mixed
	 */
	public function retrieve_temporary_data( $hash_key ) {
		return $this->get_meta_data( null, $hash_key );
	}

	/**
	 * Get saved meta from transient.
	 *
	 * @param null|int    $id       Post ID (or null if using current post).
	 *                              Note: This is only for context, it does not affect what is returned.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return array Saved meta from transient.
	 */
	public function get_meta_data( $id = null, $hash_key = null ) {
		// Get the transient name.
		$transient_name = $this->get_transient_name( $id, $hash_key );

		if ( empty( $transient_name ) ) {
			return [];
		}

		$meta = get_transient( $transient_name );

		if ( ! is_array( $meta ) ) {
			return [];
		}

		$this->data_cache = $meta;

		return $meta;
	}

	/**
	 * Gets the ticket data associated to a specified ticket.
	 *
	 * @param int         $id       The ticket ID.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return array|mixed Either the data stored for the specified id
	 *                     or an empty array.
	 */
	public function get_meta_data_for( $id, $hash_key = null ) {
		$id = (int) $id;

		if ( isset( $this->data_cache[ $id ] ) ) {
			return [
				$id => $this->data_cache[ $id ],
			];
		}

		$data = $this->get_meta_data( $id, $hash_key );

		if ( ! isset( $data[ $id ] ) ) {
			return [];
		}

		$data = [
			$id => $data[ $id ],
		];

		return $data;
	}

	/**
	 * Update meta in transient.
	 *
	 * @since 4.11.0
	 *
	 * @param array       $data     Meta data to save.
	 * @param null|int    $id       Post ID (or null if using current post).
	 *                              Note: This is only for context, it does not affect what is saved.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return boolean Whether the transient was saved.
	 */
	public function update_meta_data( $data, $id = null, $hash_key = null ) {
		if ( empty( $data ) ) {
			return $this->delete_meta_data( $id, $hash_key );
		}

		// Get the transient name.
		$transient_name = $this->get_transient_name( $id, $hash_key );

		if ( empty( $transient_name ) ) {
			return false;
		}

		$this->data_cache = $data;

		return set_transient( $transient_name, $data, $this->ticket_meta_expire_time );
	}

	/**
	 * Delete transient for meta.
	 *
	 * @since 4.11.0
	 *
	 * @param null|int    $id       Post ID (or null if using current post).
	 *                              Note: This is only for context, it does not affect what is deleted.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return boolean Whether the transient was deleted.
	 */
	public function delete_meta_data( $id = null, $hash_key = null ) {
		// Get the transient name.
		$transient_name = $this->get_transient_name( $id, $hash_key );

		if ( empty( $transient_name ) ) {
			return false;
		}

		delete_transient( $transient_name );

		$this->delete_cookie( $id );

		$this->data_cache = [];

		return true;
	}

	/**
	 * Delete transient for meta.
	 *
	 * @since 4.11.0
	 *
	 * @param null|int    $id       Post ID (or null if using current post).
	 *                              Note: This is only for context, it does not affect what is deleted.
	 * @param null|string $hash_key The hash key.
	 *
	 * @return boolean Whether the transient was deleted.
	 */
	public function delete_meta_data_for( $id, $hash_key = null ) {
		$id = (int) $id;

		$data = $this->get_meta_data( $id, $hash_key );

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! isset( $data[ $id ] ) ) {
			return false;
		}

		unset( $data[ $id ] );

		if ( isset( $this->data_cache[ $id ] ) ) {
			unset( $this->data_cache[ $id ] );
		}

		if ( empty( $data ) ) {
			$this->delete_meta_data( $id, $hash_key );
		} else {
			$this->update_meta_data( $data, $id, $hash_key );
		}

		return true;
	}

	/**
	 * Deletes the cookie storing the transient hash
	 *
	 * @param int $id The ticket ID.
	 */
	public function delete_cookie( $id ) {
		if ( ! headers_sent() ) {
			setcookie( self::HASH_COOKIE_KEY, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}

		if ( isset( $_COOKIE[ self::HASH_COOKIE_KEY ] ) ) {
			unset( $_COOKIE[ self::HASH_COOKIE_KEY ] );
		}

		/**
		 * Allow hooking into after we set delete the cookie.
		 *
		 * @since 4.11.0
		 *
		 * @param string $id The ticket ID.
		 */
		do_action( 'tribe_tickets_plus_meta_storage_delete_hash_cookie', $id );
	}

	/**
	 * Recursively Process Array to Remove Empty Values, but Keep 0
	 *
	 * @since 4.10.4
	 *
	 * @param array $input a multidimensional array of attendee meta
	 *
	 * @return array a multidimensional array of attendee meta with no empty values
	 */
	public function remove_empty_values_recursive( $input ) {
		foreach ( $input as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->remove_empty_values_recursive( $value );
			}
		}

		// reset array for current to work correctly
		reset( $input );
		if ( is_array( current( $input ) ) ) {
			// if current item is an array return it to prevent notices of string to array
			return array_filter( $input );
		}

		// remove empty values, but keep 0 as a value
		return array_filter( $input, 'strlen' );
	}

	/**
	 * Clears the stored data associated with a ticket.
	 *
	 * @param int $id The ticket ID.
	 *
	 * @return bool Whether the data for the specified ID was stored and cleared; `false`
	 *              otherwise.
	 *
	 * @deprecated 4.11.0 Use Tribe__Tickets_Plus__Meta__Storage::delete_meta_data_for() instead.
	 */
	public function clear_meta_data_for( $id ) {
		return $this->delete_meta_data_for( $id );
	}
}
