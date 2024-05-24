<?php

namespace OTGS\Toolset\Types\Ajax\Handler\MergeRelationships;

use IToolset_Post_Type_From_Types;
use OTGS\Toolset\Common\ExceptionWithMessage;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;
use Toolset_Element_Domain;
use Toolset_Post_Type_Repository;
use Toolset_Relationship_Cardinality;
use Toolset_Relationship_Definition_Repository;
use Toolset_Relationship_Origin_Wizard;
use Toolset_Relationship_Query_Factory;


/**
 * Command for the relationship merge process.
 *
 * Make sure that it is possible to create a new relationship under given circumstances, and do so.
 *
 * @package OTGS\Toolset\Types\Ajax\Handler\MergeRelationships
 * @since 3.0.4
 */
class CreateNewDefinition extends AbstractCommand {

	/** @var Toolset_Post_Type_Repository */
	protected $post_type_repository;


	public function __construct(
		Toolset_Relationship_Query_Factory $query_factory,
		Toolset_Relationship_Definition_Repository $relationship_definition_repository,
		Toolset_Post_Type_Repository $post_type_repository
	) {
		parent::__construct( $query_factory, $relationship_definition_repository );
		$this->post_type_repository = $post_type_repository;
	}


	/**
	 * @param array $options Option array coming from the dialog.
	 *
	 * @return ResultInterface
	 */
	public function run( $options ) {

		$results = new ResultSet();

		try {
			list( $relationship_left, $relationship_right ) = $this->get_old_relationships( $options );

			$new_relationship_data = toolset_ensarr( toolset_getarr( $options, 'newRelationship' ) );
			$new_slug = toolset_getarr( $new_relationship_data, 'slug' );
			if ( ! is_string( $new_slug ) || empty( $new_slug ) ) {
				return new SingleResult( false, __( 'Invalid slug of the new relationship.', 'wpcf' ), 1 );
			}

			// Abort if there is any mismatch regarding post types involved on the child role of both relationships.
			//
			//
			$left_child_types = $relationship_left->get_child_type()->get_types();
			$right_child_types = $relationship_right->get_child_type()->get_types();
			if (
				count( $left_child_types ) !== 1
				|| count( $right_child_types ) !== 1
				|| $left_child_types[0] !== $right_child_types[0]
				|| $relationship_left->get_child_type()->get_domain() !== Toolset_Element_Domain::POSTS
				|| $relationship_right->get_child_type()->get_domain() !== Toolset_Element_Domain::POSTS
			) {
				return new SingleResult( false, __( 'Child post type mismatch between the two relationships to be merged.', 'wpcf' ), 2 );
			}
			$intermediary_post_type_slug = $right_child_types[0]; // same as left, at this point

			// Abort if there is any problem regarding parent post types.
			//
			//
			$left_parent_types = $relationship_left->get_parent_type()->get_types();
			$right_parent_types = $relationship_right->get_parent_type()->get_types();
			if(
				count( $left_parent_types ) !== 1
				|| count( $right_parent_types ) !== 1
				|| (
					$left_parent_types[0] === $right_parent_types[0]
					&& $relationship_left->get_parent_domain() === $relationship_right->get_parent_domain()
				)
			) {
				return new SingleResult( false, __( 'Parent types of the two relationships to be merged are the same.', 'wpcf' ), 7 );
			}

			// Validate the intermediary post type.
			//
			//
			$intermediary_post_type = $this->post_type_repository->get( $intermediary_post_type_slug );
			if ( null === $intermediary_post_type ) {
				return new SingleResult( false, __( 'Child post type (future intermediary) not found.', 'wpcf' ), 3 );
			}

			if ( ! $intermediary_post_type instanceof IToolset_Post_Type_From_Types ) {
				return new SingleResult( false, __( 'Child post type cannot be used as an intermediary because it isn\'t registered by Types.', 'wpcf' ), 4 );
			}

			// We need to skip the check for involvment in other relationships, because we know it is involved (in
			// $relationship_left and $relationship_right). But we'll check that there are no more relationships later on.
			$intermediary_post_type_check = $intermediary_post_type->can_be_used_as_intermediary( true, true );
			if ( $intermediary_post_type_check->is_error() ) {
				return $intermediary_post_type_check;
			}

			$ipt_query = $this->query_factory->relationships_v2();
			$ipt_query->do_not_add_default_conditions()
				->add( $ipt_query->has_domain_and_type( $intermediary_post_type->get_slug(), Toolset_Element_Domain::POSTS ) )
				->need_found_rows()
				->get_results();
			$intermediary_post_type_appearance_in_relationships = $ipt_query->get_found_rows();
			if ( $intermediary_post_type_appearance_in_relationships > 2 ) {
				return new SingleResult( false, __( 'Child post type cannot be used as an intermediary because it\'s involved in one or more other relationships.', 'wpcf' ), 5 );
			}

			// Create the new relationship and set the intermediary post.
			//
			//
			$new_relationship = $this->relationship_definition_repository->create_definition(
				$new_slug,
				$relationship_left->get_parent_type(),
				$relationship_right->get_parent_type()
			);

			$new_relationship->set_cardinality( Toolset_Relationship_Cardinality::from_string( '0..*:0..*' ) );
			$new_relationship->set_origin( new Toolset_Relationship_Origin_Wizard() );
			$new_relationship->set_intermediary_post_type( $intermediary_post_type, true );
			$new_relationship->set_display_name( toolset_getarr( $new_relationship_data, 'plural', $new_slug ) );
			$new_relationship->set_display_name_singular( toolset_getarr( $new_relationship_data, 'singular', $new_slug ) );

			$result = $this->relationship_definition_repository->persist_definition( $new_relationship );

			if ( ! $result->is_success() ) {
				return $result;
			}

			$results->add( true, __( 'New many-to-many relationship has been created successfully and the child post type has been set as intermediary for the new relationship.', 'wpcf' ) );

		} catch ( ExceptionWithMessage $e ) {
			$results->add( $e, null, 6 );
		}

		return $results;
	}


}
