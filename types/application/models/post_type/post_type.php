<?php

use OTGS\Toolset\Common\PostType\BuiltinPostTypeWithOverrides;
use OTGS\Toolset\Common\PostType\EditorMode;

/**
 * FIXME please document this!
 *
 * @property-read string $name
 */
class Types_Post_Type {

	/** @var null|WP_Post_Type */
	protected $wp_post_type;

	protected $name;

	protected $field_groups;
	protected $taxonomies;

	private $post_type_model;

	public function __construct( $post_type ) {
		if( is_object( $post_type ) && isset( $post_type->name ) ) {
			$this->wp_post_type = $post_type;
			$this->name = $post_type->name;
		} else {
			$this->name = $post_type;
			$registered = get_post_type_object( $post_type );

			if( $registered ) {
				$this->wp_post_type = $registered;
			}
		}
	}


	/** @noinspection MagicMethodsValidityInspection */
	public function __isset( $property ) {
		if( $this->wp_post_type === null ) {
			return false;
		}

		if( ! property_exists( $this->wp_post_type, 'labels' ) ) {
			return false;
		}

		if( ! property_exists( $this->wp_post_type->labels, $property ) ) {
			return false;
		}

		return true;
	}

	public function __get( $property ) {
		if( ! $this->__isset( $property ) ) {
			return false;
		}

		return $this->wp_post_type->labels->$property;
	}


	public function __set( $name, $value ) {
		throw new RuntimeException( 'Property ' . $name . ' is read-only.' );
	}


	public function get_name() {
		return $this->name;
	}


	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . 'admin.php?page=wpcf-edit-type&wpcf-post-type=' . $this->get_name();
	}

	/**
	 * Register Post Type
	 */

	/**
	 * Assigned Field Groups
	 */
	private function fetch_field_groups() {
		global $wpdb;
		$sql = 'SELECT post_id FROM ' .$wpdb->postmeta . '
                    WHERE meta_key="_wp_types_group_post_types"
                    AND (meta_value LIKE "%,' . $this->name . ',%" OR meta_value="all" OR meta_value REGEXP "^[,]+$")
                    ORDER BY post_id';
		$post_ids = $wpdb->get_col( $sql );

		$this->field_groups = array();
		foreach( $post_ids as $id ) {
			$field_group = Toolset_Field_Group_Post_Factory::load( $id );

			if( $field_group ) {
				$this->field_groups[] = $field_group;
			}
		}
	}

	public function get_field_groups() {
		if( $this->field_groups === null ) {
			$this->fetch_field_groups();
		}

		return $this->field_groups;
	}

	/**
	 * Assigned Taxonomies
	 */
	private function fetch_taxonomies() {
		$taxonomies = array();
		$all_taxonomies = get_taxonomies( [], 'objects' );

		foreach( $all_taxonomies as $tax ) {
			if( $tax->show_ui && in_array( $this->get_name(), $tax->object_type, true ) ) {
				$taxonomies[] = new Types_Taxonomy( $tax );
			}
		}

		$this->taxonomies = $taxonomies;
	}

	public function get_taxonomies() {
		if( $this->taxonomies === null ) {
			$this->fetch_taxonomies();
		}

		return $this->taxonomies;
	}


	/**
	 * @return IToolset_Post_Type|null
	 */
	private function get_post_type_model() {
		if( null === $this->post_type_model ) {
			$this->post_type_model = Toolset_Post_Type_Repository::get_instance()->get( $this->get_name() );
		}

		return $this->post_type_model;
	}


	/**
	 * @return string One of the EditorMode values or an empty string if the mode is not defined.
	 * @since 3.2.2
	 */
	public function get_editor_mode() {
		$post_type_model = $this->get_post_type_model();
		if( null === $post_type_model ) {
			return '';
		}

		return $post_type_model->get_editor_mode();
	}


	/**
	 * @return bool
	 * @since 3.2.2
	 */
	public function is_from_types() {
		$post_type_model = $this->get_post_type_model();
		if( null === $post_type_model ) {
			return false;
		}

		return $post_type_model->is_from_types();
	}


	/**
	 * @return bool
	 * @since 3.2.2
	 */
	public function is_builtin() {
		$post_type_model = $this->get_post_type_model();
		if( null === $post_type_model ) {
			return false;
		}

		return $post_type_model->is_builtin();
	}


	/**
	 * @return bool True, if the post type is a built-in that has already been altered by Types.
	 * @since 3.2.2
	 */
	public function is_builtin_editable() {
		if( ! $this->is_builtin() ) {
			return false;
		}

		return ( $this->get_post_type_model() instanceof BuiltinPostTypeWithOverrides );
	}


	/**
	 * Get the display name of the post type's editor mode.
	 *
	 * @return string
	 * @since 3.2.2
	 */
	public function get_editor_mode_label() {
		switch( $this->get_editor_mode() ) {
			case EditorMode::CLASSIC:
				// Translators: Editor mode for a post type.
				return __( 'Classic editor', 'wpcf' );
			case EditorMode::BLOCK:
				// Translators: Editor mode for a post type.
				return __( 'Block editor', 'wpcf' );
			case EditorMode::PER_POST:
				// Translators: Editor mode for a post type.
				return __( 'Editor choice per post', 'wpcf' );
			default:
				return '';
		}
	}
}
