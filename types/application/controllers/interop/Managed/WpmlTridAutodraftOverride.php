<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;

/**
 * Override the WPML TRID value for the current post on the Add New page.
 *
 * This is required for the first rendering of the Related Content metabox and also for post reference fields.
 *
 * @since 3.4
 */
class WpmlTridAutodraftOverride implements HandlerInterface2 {

	/** @var \OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride */
	private $on_demand_override;

	/** @var bool */
	private $is_initialized = false;


	/**
	 * WpmlTridAutodraftOverride constructor.
	 *
	 * @param \OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride $on_demand_override
	 */
	public function __construct(
		\OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride $on_demand_override
	) {
		$this->on_demand_override = $on_demand_override;
	}


	/**
	 * @inheritDoc
	 */
	public function initialize() {
		// We can't do it right now because the global $post variable is not populated yet at this point.
		//
		// Used priority 5, so that this surely happens before Types adds its metaboxes, where the
		// TRID override is needed.
		add_action( 'add_meta_boxes', function () {
			if ( $this->is_initialized ) {
				return;
			}

			global $post;
			$this->on_demand_override->initialize(
				$post ? (int) $post->ID : 0,
				(int) toolset_getget( 'trid' ),
				esc_attr( toolset_getget( 'lang' ) )
			);

			$this->is_initialized = true;
		}, 5, 0 );
	}
}
