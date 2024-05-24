<?php

namespace OTGS\Toolset\Types\Ajax\Handler\MergeRelationships;

use IToolset_Relationship_Definition;
use OTGS\Toolset\Common\ExceptionWithMessage;
use Toolset_Result;
use Toolset_Result_Set;


/**
 * Command for the relationship merge process.
 *
 * Delete old relationships, their remaining associations, and dangling intermediary posts (and schedule a cleanup
 * CRON event if there's too many of them).
 *
 * @package OTGS\Toolset\Types\Ajax\Handler\MergeRelationships
 * @since 3.0.4
 */
class Cleanup extends AbstractCommand {


	/** @var \Toolset_Association_Cleanup_Factory */
	protected $association_cleanup_factory;


	public function __construct(
		\Toolset_Relationship_Query_Factory $query_factory,
		\Toolset_Relationship_Definition_Repository $relationship_definition_repository,
		\Toolset_Association_Cleanup_Factory $association_cleanup_factory
	) {
		parent::__construct( $query_factory, $relationship_definition_repository );
		$this->association_cleanup_factory = $association_cleanup_factory;
	}


	/**
	 * @param array $options Option array coming from the dialog.
	 *
	 * @return Toolset_Result_Set
	 */
	public function run( $options ) {

		$results = new Toolset_Result_Set();

		try {
			list( $relationship_left, $relationship_right ) = $this->get_old_relationships( $options );

			$dip_cleanup = $this->association_cleanup_factory->dangling_intermediary_posts();
			$dip_cleanup->do_batch();

			$results->add(
				true,
				sprintf(
					__( 'Deleted %d dangling intermediary posts (that don\'t belong to a particular association).', 'wpcf' ),
					$dip_cleanup->get_deleted_posts()
				)
			);

			if ( $dip_cleanup->has_remaining_posts() ) {
				$this->association_cleanup_factory->cron_handler()->schedule_event();
				$results->add( true, __( 'Scheduled a CRON event to delete remaining dangling intermediary posts.', 'wpcf' ) );
			} else {
				$results->add( true, __( 'No remaining dangling intermediary posts found.', 'wpcf' ) );
			}

			$results->add( $this->remove_definition_with_message( $relationship_left ) );
			$results->add( $this->remove_definition_with_message( $relationship_right ) );

		} catch ( ExceptionWithMessage $e ) {
			$results->add( $e );
		}

		return $results;
	}

	/**
	 * @param IToolset_Relationship_Definition $relationship_definition
	 *
	 * @return Toolset_Result|Toolset_Result_Set
	 */
	private function remove_definition_with_message( IToolset_Relationship_Definition $relationship_definition ) {
		$result = $this->relationship_definition_repository->remove_definition( $relationship_definition, true );

		if ( ! $result->is_complete_success() ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'Error when deleting a relationship definition %s: %s', 'wpcf' ),
					$relationship_definition->get_slug(),
					$result->concat_messages( '; ' )
				)
			);
		}

		return $result;
	}


}