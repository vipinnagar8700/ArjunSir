<?php

/**
 * @since m2m
 */
class Types_Field_Group_Repeatable_Item_Builder extends Types_Post_Builder {

	/** @var Types_Field_Group_Repeatable|null */
	private $belongs_to_rfg;

	/** @var Types_Field_Group_Repeatable_Item */
	protected $types_post;


	/**
	 * Types_Field_Group_Repeatable_Item_Builder constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->types_post = new Types_Field_Group_Repeatable_Item();
	}


	/**
	 * Use this to build another Types_Field_Group_Repeatable_Item with the same builder object
	 */
	public function reset() {
		$this->types_post = new Types_Field_Group_Repeatable_Item();
		$this->belongs_to_rfg = null;
	}


	/**
	 * @param Types_Field_Group_Repeatable $rfg
	 */
	public function set_belongs_to_rfg( Types_Field_Group_Repeatable $rfg ) {
		$this->belongs_to_rfg = $rfg;
	}


	/**
	 * Load the nested repeatable field groups of the item.
	 *
	 * @param int $depth How many levels of nested rfgs should be loaded
	 * @param Types_Field_Group_Mapper_Interface|null $rfg_mapper
	 */
	public function load_assigned_field_groups( $depth = 1, Types_Field_Group_Mapper_Interface $rfg_mapper = null ) {
		if ( $this->types_post->get_wp_post() === null ) {
			throw new RuntimeException( 'You need to set_wp_post( WP_Post $post ) before you can load assigned field groups.' );
		}

		if ( $this->belongs_to_rfg === null ) {
			throw new RuntimeException( 'You need to set_belongs_to_rfg() before you can load assigned fields groups.' );
		}

		$rfg_service = new Types_Field_Group_Repeatable_Service();
		$types_field_service = Types_Field_Service_Store::get_instance()->get_service( false );
		$types_field_gateway = new Types_Field_Gateway_Wordpress_Post();

		foreach ( $this->belongs_to_rfg->get_field_slugs() as $field_slug ) {
			if ( $depth > 0 && $rfg_id = $rfg_service->get_id_from_prefixed_string( $field_slug ) ) {
				// repeatable field group
				if ( $rfg = $rfg_service->get_object_by_id( $rfg_id, $this->types_post->get_wp_post(), $depth - 1 ) ) {
					$this->types_post->add_field_group( $rfg );
				}
				continue;
			}

			// regular custom field
			$field = $types_field_service->get_field( $types_field_gateway, $field_slug, $this->types_post->get_wp_post()->ID );
			if ( $field ) {
				$this->types_post->add_field( $field );
			}
		}

	}
}
