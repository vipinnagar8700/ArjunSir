<?php

namespace OTGS\Toolset\Types\Wordpress;

use Exception;
use IToolset_Post;
use OTGS\Toolset\Common\M2M\Association\Repository as AssociationRepository;
use OTGS\Toolset\Types\Post\Export\Extender as PostExportExtender;
use OTGS\Toolset\Types\Wordpress\Postmeta\Temporary as TemporaryPostmeta;
use Toolset_Element_Domain;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Post_Type_Repository;

/**
 * This handles the extras we're doing on Wordpress Export
 * It should only be used on 'export_wp' hook with a priority lower than 10
 *
 * @since 3.0
 */
class Export {

	/** @var AssociationRepository */
	private $association_repository;

	/** @var Toolset_Post_Type_Repository */
	private $post_type_repository;

	/** @var PostExportExtender */
	private $post_export_extender;

	/** @var Toolset_Element_Factory */
	private $element_factory;

	/** @var TemporaryPostmeta */
	private $temporary_postmeta;


	/**
	 * Export constructor.
	 *
	 * @param AssociationRepository $association_repository
	 * @param Toolset_Post_Type_Repository $repository
	 * @param PostExportExtender $post_export_extender
	 * @param Toolset_Element_Factory $toolset_element_factory
	 * @param TemporaryPostmeta $temporary_postmeta
	 */
	public function __construct(
		AssociationRepository $association_repository,
		Toolset_Post_Type_Repository $repository,
		PostExportExtender $post_export_extender,
		Toolset_Element_Factory $toolset_element_factory,
		TemporaryPostmeta $temporary_postmeta
	) {
		$this->association_repository = $association_repository;
		$this->post_type_repository = $repository;
		$this->post_export_extender = $post_export_extender;
		$this->element_factory = $toolset_element_factory;
		$this->temporary_postmeta = $temporary_postmeta;

		// before export starts
		add_action( 'export_wp', array( $this, 'addExtraDataForPostTypes' ) );

		// we abuse this filter to remove our previously added data (there is no other option)
		add_filter( 'wxr_export_skip_postmeta', array( $this, 'revokeExtraData' ), 10, 2 );
	}


	/**
	 * @param array $args
	 */
	public function addExtraDataForPostTypes( $args ) {
		$post_types = $args['content'];

		if ( $post_types === 'all' ) {
			$post_types = get_post_types();
		} else {
			$post_types = array( $post_types );
		}

		// Load Associations of exported post types
		foreach ( $post_types as $post_type_slug ) {
			if ( $post_type = $this->post_type_repository->get( $post_type_slug ) ) {
				$this->association_repository->addAssociationsByPostType( $post_type );
			}
		}

		// Load all affected posts
		foreach ( $post_types as $post_type_slug ) {
			$posts = get_posts( array(
				'post_type' => $post_type_slug,
				'post_status' => 'any',
				'numberposts' => - 1,
				'suppress_filters' => true,
			) );

			foreach ( $posts as $post ) {
				try {
					$toolset_element = $this->element_factory->get_post( $post );

					if ( $export_data = $this->post_export_extender->getExportArray( $toolset_element ) ) {
						// we have additional data
						foreach ( $export_data as $meta_key => $meta_value ) {
							$this->temporary_postmeta->updatePostMeta(
								$toolset_element->get_id(),
								$meta_key,
								$meta_value
							);
						}
					}

				} catch ( Exception $e ) {
					// toolset element could not be loaded
				}
			}
		}
	}


	/**
	 * We abuse the wxr_export_skip_postmeta filter to revoke our previously added postmeta for associations.
	 * Therefore it's important to just bypass the return value
	 *
	 * @filter wxr_export_skip_postmeta
	 *
	 * @param bool $return
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function revokeExtraData( $return, $key ) {
		global $post;

		// revoke temp postmeta
		$this->temporary_postmeta->revokePostMetaChanges( $post->ID, $key );

		return $return;
	}
}
