<?php

namespace OTGS\Toolset\Types\Ajax\Handler\MergeRelationships;

use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\ExceptionWithMessage;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Relationship_Role_Parent;
use Toolset_Result;
use Toolset_Result_Set;
use IToolset_Association;


/**
 * Command for the relationship merge process.
 *
 * Migrate a batch of associations into the new relationship.
 * We do this by processing intermediary posts that are connected to both old relationships.
 *
 * @package OTGS\Toolset\Types\Ajax\Handler\MergeRelationships
 * @since 3.0.4
 */
class MigrateAssociations extends AbstractCommand {


	/** @var \Toolset_WPML_Compatibility */
	protected $wpml_service;


	/**
	 * MigrateAssociations constructor.
	 *
	 * @param \Toolset_Relationship_Query_Factory $query_factory
	 * @param \Toolset_Relationship_Definition_Repository $relationship_definition_repository
	 * @param \Toolset_WPML_Compatibility $wpml_service
	 */
	public function __construct(
		\Toolset_Relationship_Query_Factory $query_factory,
		\Toolset_Relationship_Definition_Repository $relationship_definition_repository,
		\Toolset_WPML_Compatibility $wpml_service
	) {
		parent::__construct( $query_factory, $relationship_definition_repository );
		$this->wpml_service = $wpml_service;
	}


	/**
	 * @param array $options Option array coming from the dialog.
	 * @param int $batch_number
	 * @param int $batch_size
	 *
	 * @return PhaseResult
	 */
	public function run(
		$options, $batch_number, $batch_size
	) {
		$results = new PhaseResult();

		try {
			list( $relationship_left, $relationship_right ) = $this->get_old_relationships( $options );

			$new_relationship_slug = toolset_getnest( $options, array( 'newRelationship', 'slug' ), '' );
			$new_relationship = $this->relationship_definition_repository->get_definition( $new_relationship_slug );

			if ( null === $new_relationship ) {
				throw new ExceptionWithMessage( __( 'Unable to load the newly created relationship.', 'wpcf' ) );
			}

			// We care only about default language posts, obviously.
			$this->wpml_service->switch_language( $this->wpml_service->get_default_language() );

			$wp_query = $this->query_factory->wp_query(
				array(
					'post_type' => $new_relationship->get_intermediary_post_type(),
					'post_status' => 'any',
					// this still ignores trashed and auto-draft posts (anything with exclude_from_search)
					'posts_per_page' => $batch_size,
					'offset' => $batch_size * $batch_number,
					'fields' => 'ids',
					// Standard WP_Query optimizations
					'ignore_sticky_posts' => true,
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'orderby' => 'ID',
					'order' => 'ASC',
				)
			);

			foreach ( $wp_query->posts as $intermediary_id ) {
				try {
					// These will fail (with an ExceptionWithMessage) when the association doesn't exist.
					$left_association = $this->get_association_by_intermediary( $intermediary_id, $relationship_left );
					$right_association = $this->get_association_by_intermediary( $intermediary_id, $relationship_right );

					$new_association = $new_relationship->create_association(
						$left_association->get_element( new Toolset_Relationship_Role_Parent() )
							->get_default_language_id(),
						$right_association->get_element( new Toolset_Relationship_Role_Parent() )
							->get_default_language_id(),
						$intermediary_id
					);

					if ( $new_association instanceof Toolset_Result ) {
						throw new ExceptionWithMessage( $new_association->get_message() );
					}

					$results->add(
						true,
						sprintf(
							__( 'Created an association #%d (with posts #%d, #%d, #%d) in the relationship %s, which replaces associations #%d and #%d.', 'wpcf' ),
							$new_association->get_uid(),
							$new_association->get_element_id( new Toolset_Relationship_Role_Parent() ),
							$new_association->get_element_id( new Toolset_Relationship_Role_Child() ),
							$new_association->get_element_id( new Toolset_Relationship_Role_Intermediary() ),
							$new_relationship_slug,
							$left_association->get_uid(),
							$right_association->get_uid()
						)
					);

				} catch ( \Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
					$results->add( $e );
					continue;
				} catch ( ExceptionWithMessage $e ) {
					$results->add( $e );
					continue;
				}
			}

			$this->wpml_service->switch_language_back();

			if ( $wp_query->post_count < $batch_size ) {
				$results->set_is_phase_complete( true );
			}


		} catch ( ExceptionWithMessage $e ) {
			$results->add( $e );
		}

		return $results;
	}


	/**
	 * @param int $intermediary_id
	 * @param IToolset_Relationship_Definition $relationship_definition
	 *
	 * @return IToolset_Association
	 * @throws ExceptionWithMessage
	 */
	private function get_association_by_intermediary( $intermediary_id, IToolset_Relationship_Definition $relationship_definition ) {
		$query = $this->query_factory->associations_v2();

		/** @var IToolset_Association[] $associations */
		$associations = $query
			->do_not_add_default_conditions()
			->add( $query->relationship( $relationship_definition ) )
			->add( $query->child_id( $intermediary_id ) )
			->use_cache( false )
			->limit( 1 )
			->need_found_rows()
			->dont_translate_results()
			->return_association_instances()
			->get_results();

		if ( $query->get_found_rows() !== 1 ) {
			throw new ExceptionWithMessage(
				sprintf(
					__( 'Post #%d has %d parents in the relationship %s and cannot be used as an intermediary post (exactly one parent is required).', 'wpcf' ),
					$intermediary_id,
					$query->get_found_rows(),
					$relationship_definition->get_slug()
				)
			);
		}

		return array_pop( $associations );
	}


}
