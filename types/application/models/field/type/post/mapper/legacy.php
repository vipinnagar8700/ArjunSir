<?php

use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Mapper for "Post" field
 *
 * @since 2.3
 */
class Types_Field_Type_Post_Mapper_Legacy extends Types_Field_Mapper_Abstract {

	/** @var Types_Field_Type_Post_Factory */
	protected $field_factory;

	/** @var PostStatus */
	protected $post_status;

	/** @var Toolset_Relationship_Definition_Repository */
	protected $relationship_repository;

	/** @var Factory */
	private $relationships_factory;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * Types_Field_Type_Post_Mapper_Legacy constructor.
	 *
	 * @param Types_Field_Factory_Interface $factory
	 * @param Types_Field_Gateway_Interface $gateway
	 * @param PostStatus $post_status
	 * @param Toolset_Relationship_Definition_Repository $relationship_repository
	 * @param Factory $relationships_factory
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		Types_Field_Factory_Interface $factory,
		Types_Field_Gateway_Interface $gateway,
		PostStatus $post_status,
		Toolset_Relationship_Definition_Repository $relationship_repository,
		Factory $relationships_factory,
		WpmlService $wpml_service
	) {
		parent::__construct( $factory, $gateway );
		$this->post_status = $post_status;
		$this->relationship_repository = $relationship_repository;
		$this->relationships_factory = $relationships_factory;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * @param int|string $id
	 * @param int $id_post
	 *
	 * @return null|Types_Field_Type_Post
	 * @throws Exception
	 */
	public function find_by_id( $id, $id_post ) {
		$field = $this->database_get_field_by_id( $id );
		if ( ! $field ) {
			return null;
		}

		if ( $field['type'] !== 'post' ) {
			throw new RuntimeException( 'Types_Field_Type_Post_Mapper_Legacy can not map type: ' . $field['type'] );
		}

		$field = $this->map_common_field_properties( $field );

		if ( isset( $field['data'], $field['data']['post_reference_type'] ) ) {
			$field['post_reference_type'] = $field['data']['post_reference_type'];
		}

		$relationship = $this->relationship_repository->get_definition( $id );
		if ( $relationship ) {
			$query = $this->relationships_factory->association_query();

			if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
				$query->add( $query->child_id( $id_post ) );
			} else {
				// We might be overriding the TRID for an auto-draft post, in which case, we must query by the future
				// TRID and not just by the post ID. See WpmlTridAutodraftOverride for more information.
				$query->add( $query->element_trid_or_id_and_domain(
					$this->wpml_service->get_post_trid( $id_post ),
					$id_post,
					Toolset_Element_Domain::POSTS,
					new Toolset_Relationship_Role_Child()
				) );
			}

			$user_selected_post = $query
				->add( $query->relationship( $relationship ) )
				->add( $query->element_status(
				// We'll show a trashed post when it's already set to provide the full information to the user
				// and to allow them clearing the field if necessary.
					array_merge( $this->post_status->get_available_post_statuses(), [ 'trash' ] ),
					new Toolset_Relationship_Role_Parent()
				) )
				->limit( 1 )
				->return_element_ids( new Toolset_Relationship_Role_Parent() )
				->get_results();

			if ( ! empty( $user_selected_post ) ) {
				$field['value'] = (string) $user_selected_post[0];
			}
		}

		return $this->field_factory->get_field( $field );
	}
}
