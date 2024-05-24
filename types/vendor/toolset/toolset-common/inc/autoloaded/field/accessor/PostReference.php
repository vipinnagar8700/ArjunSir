<?php

namespace OTGS\Toolset\Common\Field\Accessor;

/**
 * Value accessor for the post reference field.
 *
 * Note that this works only for post fields, which is not a problem since we don't allow
 * other domains in relationships yet. Furthermore, this accessor is read-only, because PRFs are being saved
 * in Types in an arbitrary way, and other methods than get_raw_value() are never used.
 *
 * Works only if m2m is enabled, obviously.
 *
 * @since Types 3.3
 */
class PostReference extends \Toolset_Field_Accessor_Abstract {


	/** @var null|\Toolset_Relationship_Query_Factory  */
	private $_query_factory;


	/** @var \Toolset_Field_Definition */
	private $field_definition;


	/**
	 * PostReference constructor.
	 *
	 * @param int $post_id
	 * @param \Toolset_Field_Definition $field_definition
	 * @param null|\Toolset_Relationship_Query_Factory $query_factory_di
	 */
	public function __construct( $post_id, \Toolset_Field_Definition $field_definition, $query_factory_di = null ) {
		parent::__construct( $post_id, $field_definition->get_meta_key(), false );
		$this->field_definition = $field_definition;
		$this->_query_factory = $query_factory_di;
	}


	/**
	 * @return null|int[] Field value from the database.
	 */
	public function get_raw_value() {
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return null;
		}

		$query = $this->get_query_factory()->associations_v2();
		$results = $query->add( $query->relationship_slug( $this->field_definition->get_slug() ) )
			->add( $query->child_id( $this->object_id ) )
			->limit( 1 )
			->add( $query->has_origin( \Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD ) )
			->return_element_ids( new \Toolset_Relationship_Role_Parent() )
			->get_results();

		if ( empty( $results ) ) {
			return array( 0 );
		}

		return array( (int) array_pop( $results ) );
	}


	/**
	 * @param mixed $value New value to be saved to the database.
	 * @param mixed $prev_value Previous field value. Use if updating an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function update_raw_value( $value, $prev_value = '' ) {
		throw new \RuntimeException( 'Not implemented' );
	}


	/**
	 * Add new metadata. Note that if the accessor is set up for a repetitive field, the is_unique argument
	 * of add_*_meta should be false and otherwise it should be true.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_term_meta/
	 *
	 * @param mixed $value New value to be saved to the database
	 *
	 * @return mixed
	 */
	public function add_raw_value( $value ) {
		throw new \RuntimeException( 'Not implemented' );
	}


	/**
	 * Delete field value from the database.
	 *
	 * @param string $value Specific value to be deleted. Use if deleting an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function delete_raw_value( $value = '' ) {
		throw new \RuntimeException( 'Not implemented' );
	}


	/**
	 * @return \Toolset_Relationship_Query_Factory
	 */
	private function get_query_factory() {
		if( null === $this->_query_factory ) {
			$this->_query_factory =new \Toolset_Relationship_Query_Factory();
		}
		return $this->_query_factory;
	}
}
