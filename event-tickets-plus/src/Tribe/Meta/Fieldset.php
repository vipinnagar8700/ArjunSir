<?php

class Tribe__Tickets_Plus__Meta__Fieldset {
	const POSTTYPE = 'ticket-meta-fieldset';
	const META_KEY = '_tribe_tickets_meta_template';

	/**
	 * Label for the Meta Fieldsets
	 *
	 * @var string
	 */
	public $plural_label;

	public function __construct() {
		$this->plural_label = esc_html( sprintf( __( 'Registration Fieldsets', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'fieldsets' ) ) );

		add_action( 'admin_menu', [ $this, 'add_menu_item' ], 11 );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 3 );
		$this->register_posttype();

		add_filter( 'wp_insert_post_data', [ $this, 'maybe_add_default_title' ], 10, 2 );
	}

	public function add_menu_item() {
		add_submenu_page(
			Tribe__Settings::instance()->get_parent_slug(),
			$this->plural_label,
			$this->plural_label,
			'edit_posts',
			'edit.php?post_type=' . self::POSTTYPE
		);
	}

	public function save_meta( $post_id, $post, $update ) {
		// Autosave? bail
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if the post type isn't a fieldset, bail
		if ( self::POSTTYPE !== $post->post_type ) {
			return;
		}

		// if this is a post revision, bail
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['tribe-tickets-input'] ) ) {
			return;
		}

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		$meta = $meta_object->build_field_array( null, $_POST );

		update_post_meta( $post_id, self::META_KEY, $meta );
	}

	public function register_posttype() {
		$ticket_label_singular = tribe_get_ticket_label_singular( 'fieldsets' );

		$ticket_label_singular_lower = tribe_get_ticket_label_singular_lowercase( 'fieldsets' );

		$args = [
			'label'                => $this->plural_label,
			'labels'               => [
				'name'                  => $this->plural_label,
				'singular_name'         => esc_html( sprintf( __( '%s Fieldset', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'add_new_item'          => esc_html( sprintf( __( 'Add New %s Fieldset', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'edit_item'             => esc_html( sprintf( __( 'Edit %s Fieldset', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'new_item'              => esc_html( sprintf( __( 'New %s Fieldset', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'view_item'             => esc_html( sprintf( __( 'View %s Fieldset', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'search_items'          => esc_html( sprintf( __( 'Search %s Fieldsets', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'not_found'             => esc_html( sprintf( __( 'No %s fieldsets found', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
				'not_found_in_trash'    => esc_html( sprintf( __( 'No %s fieldsets found in Trash', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
				'all_items'             => esc_html( sprintf( __( 'All %s Fieldsets', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'archives'              => esc_html( sprintf( __( '%s Fieldset Archives', 'event-tickets-plus' ), $ticket_label_singular ) ),
				'insert_into_item'      => esc_html( sprintf( __( 'Insert into %s fieldset', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
				'uploaded_to_this_item' => esc_html( sprintf( __( 'Uploaded to this %s fieldset', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
			],
			'description'          => esc_html( sprintf( __( 'Saved fieldsets for %s custom meta', 'event-tickets-plus' ), $ticket_label_singular_lower ) ),
			'exclude_from_search'  => true,
			'menu_icon'            => 'dashicons-tickets-alt',
			'supports'             => [
				'title',
			],
			'show_ui'              => true,
			'show_in_menu'         => false,
			'register_meta_box_cb' => [ $this, 'register_metabox' ],
		];

		register_post_type( self::POSTTYPE, $args );
	}

	public function register_metabox( $fieldset ) {
		add_meta_box(
			self::POSTTYPE . '-metabox',
			esc_html( sprintf( __( 'Custom %s Fields', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'fieldset_label' ) ) ),
			[ $this, 'metabox' ],
			null
		);
	}

	public function metabox( $fieldset ) {
		$templates     = [];
		$meta          = get_post_meta( $fieldset->ID, self::META_KEY, true );
		$ticket_id     = null;
		$fieldset_form = true;

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		$active_meta = [];

		if ( $meta ) {
			foreach ( $meta as $field ) {
				$active_meta[] = $meta_object->generate_field( null, $field['type'], $field );
			}
		}

		?>
		<div id="tribetickets" class="event-tickets-plus-fieldset-table tribe-tickets-plus-fieldset-page">
			<?php include Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/meta.php'; ?>
		</div>
		<?php
	}

	/**
	 * Fetch fieldsets
	 *
	 * @return array
	 */
	public function get_fieldsets() {
		$templates = get_posts(
			[
				'post_type'      => self::POSTTYPE,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => - 1,
			]
		);

		return $templates;
	}

	/**
	 * Add a default title to the Ticket Fieldset if none set
	 *
	 * @since 4.7.3
	 *
	 * @param $data    array An array of post data.
	 * @param $postarr array An array of elements that make up a post to update or insert.
	 *
	 * @return array
	 */
	public function maybe_add_default_title( $data, $postarr ) {
		if (
			self::POSTTYPE === $data['post_type'] &&
			empty( $data['post_title'] )
		) {
			$id                 = empty( $postarr['ID'] ) ? $data['post_date'] : $postarr['ID'];
			$data['post_title'] = esc_html( sprintf( __( '%s Fieldset', 'event-tickets-plus' ), tribe_get_ticket_label_singular( 'fieldsets' ) ) ) . ' - ' . $id;
		}

		return $data;
	}
}