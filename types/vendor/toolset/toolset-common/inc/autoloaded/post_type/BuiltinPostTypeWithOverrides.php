<?php

namespace OTGS\Toolset\Common\PostType;


use IToolset_Post_Type_Registered;

/**
 * Represents a special case: A built-in post type with some selected settings overridden by Toolset (Types).
 *
 * Minimal implementation that aims to minimize side-effects, to be extended as needed.
 * This needs to be considered carefully in each scenario when this kind of overriding is being used.
 * Only some things can be overridden, others will be hardcoded.
 *
 * @since Types 3.2.2
 */
class BuiltinPostTypeWithOverrides extends \Toolset_Post_Type_Registered implements \IToolset_Post_Type_From_Types {


	/** @var \IToolset_Post_Type_From_Types */
	private $types_override;


	/**
	 * BuiltinPostTypeWithOverrides constructor.
	 *
	 * @param \IToolset_Post_Type_Registered $original_instance
	 * @param \IToolset_Post_Type_From_Types $types_override Instance representing the post type coming from Types
	 */
	public function __construct( \IToolset_Post_Type_Registered $original_instance, \IToolset_Post_Type_From_Types $types_override ) {
		parent::__construct( $original_instance->get_wp_object() );

		$this->types_override = $types_override;
	}


	/**
	 * @inheritdoc
	 *
	 * Uses the editor mode setting from Types.
	 *
	 * @return string
	 */
	public function get_editor_mode() {
		return $this->types_override->get_editor_mode();
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $value
	 */
	public function set_editor_mode( $value ) {
		$this->types_override->set_editor_mode( $value );
	}

	/**
	 * "touch" the post type before saving, update the timestamp and user who edited it last.
	 */
	public function touch() {
		$this->types_override->touch();
	}

	/**
	 * Get the definition array from Types.
	 *
	 * Do not use directly if possible: Instead, implement the getter you need.
	 *
	 * @return array
	 */
	public function get_definition() {
		return $this->types_override->get_definition();
	}

	/**
	 * Set a specific post type label.
	 *
	 * @param string $label_name Label name from Toolset_Post_Type_Labels.
	 * @param string $value Value of the label.
	 */
	public function set_label( $label_name, $value ) {
		$this->types_override->set_label( $label_name, $value );
	}

	/**
	 * Flag a (fresh) post type as an intermediary one.
	 */
	public function set_as_intermediary() {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * Remove the intermediary flag from the post type.
	 *
	 * @return void
	 */
	public function unset_as_intermediary() {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * Set the flag indicating whether this post type acts as a repeating field group.
	 *
	 * @param bool $value
	 *
	 * @return void
	 */
	public function set_is_repeating_field_group( $value ) {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * Never use directly: Change the slug via Toolset_Post_Type_Repository::rename() instead.
	 *
	 * @param string $new_value
	 */
	public function set_slug( $new_value ) {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * Set the 'public' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_public( $value ) {
		$this->types_override->set_is_public( $value );
	}

	/**
	 * Set the 'disabled' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_is_disabled( $value ) {
		$this->types_override->set_is_disabled( $value );
	}

	/**
	 * @return bool Corresponds with the disabled status of the post type.
	 */
	public function is_disabled() {
		return $this->types_override->is_disabled();
	}

	/**
	 * @return IToolset_Post_Type_Registered|null
	 * @since 2.6.3
	 */
	public function get_registered_post_type() {
		return $this->types_override->get_registered_post_type();
	}

	/**
	 * @param IToolset_Post_Type_Registered $registered_post_type
	 *
	 * @since 2.6.3
	 */
	public function set_registered_post_type( IToolset_Post_Type_Registered $registered_post_type ) {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * Set the 'show_in_rest' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_show_in_rest( $value ) {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * @return bool Corresponds with the show_in_rest option of the post type.
	 */
	public function has_show_in_rest() {
		return $this->types_override->has_show_in_rest();
	}

	/**
	 * Set the 'hierarchical' option of the post type.
	 *
	 * @param bool $value
	 */
	public function set_hierarchical( $value = true ) {
		throw new \RuntimeException( 'Not supported.' );
	}

	/**
	 * @return bool Corresponds with the hierarchical option of the post type.
	 */
	public function has_hierarchical() {
		return $this->types_override->has_hierarchical();
	}


	/**
	 * @inheritDoc
	 */
	public function get_hidden_related_content_metaboxes() {
		return $this->types_override->get_hidden_related_content_metaboxes();
	}


	/**
	 * @inheritDoc
	 */
	public function set_hidden_related_content_metaboxes( $relationship_slugs ) {
		$this->types_override->set_hidden_related_content_metaboxes( $relationship_slugs );
	}


	/**
	 * @inheritDoc
	 */
	public function has_show_in_menu() {
		return $this->types_override->has_show_in_rest();
	}


	/**
	 * @inheritDoc
	 */
	public function set_show_in_menu( $value ) {
		$this->types_override->set_show_in_menu( $value );
	}
}
