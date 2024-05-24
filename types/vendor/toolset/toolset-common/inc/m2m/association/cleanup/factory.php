<?php

/**
 * Factory for objects handling cleaning up and removing m2m-related data.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Factory {


	/** @var \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory */
	private $database_layer_factory;


	/**
	 * Toolset_Association_Cleanup_Factory constructor.
	 *
	 * @refactoring avoid creating new instances other than via DIC, then make the parameters mandatory.
	 * @param \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory
	 * @since 4.0
	 */
	public function __construct( \OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory $database_layer_factory = null ) {
		$this->database_layer_factory = $database_layer_factory ?: toolset_dic_make( '\OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory' );
	}


	/**
	 * @return \OTGS\Toolset\Common\Relationships\DatabaseLayer\Cleanup\PostCleanupInterface
	 */
	public function post() {
		return $this->database_layer_factory->post_cleanup( $this );
	}


	/**
	 * @return Toolset_Association_Cleanup_Association
	 */
	public function association() {
		return new Toolset_Association_Cleanup_Association( $this->database_layer_factory );
	}


	/**
	 * @return Toolset_Association_Cleanup_Cron_Handler
	 */
	public function cron_handler() {
		return new Toolset_Association_Cleanup_Cron_Handler( $this );
	}


	/**
	 * @return Toolset_Association_Cleanup_Dangling_Intermediary_Posts
	 */
	public function dangling_intermediary_posts() {
		return new Toolset_Association_Cleanup_Dangling_Intermediary_Posts( $this->database_layer_factory );
	}


	/**
	 * @return Toolset_Association_Cleanup_Cron_Event
	 */
	public function cron_event() {
		return new Toolset_Association_Cleanup_Cron_Event();
	}


	/**
	 * @return Toolset_Association_Cleanup_Troubleshooting_Section
	 */
	public function troubeshooting_section() {
		return new Toolset_Association_Cleanup_Troubleshooting_Section( $this );
	}


}
