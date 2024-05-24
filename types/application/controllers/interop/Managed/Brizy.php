<?php

namespace OTGS\Toolset\Types\Controller\Interop\Managed;

use OTGS\Toolset\Types\Controller\Interop\HandlerInterface2;
use OTGS\Toolset\Types\Controller\Interop\Managed\Brizy\Gateway;
use OTGS\Toolset\Types\Wordpress\Hooks;

/**
 * Ensure compatibility with the Brizy page builder.
 *
 * @since 3.4.2
 */
class Brizy implements HandlerInterface2 {


	/** @var Gateway */
	private $brizy_gateway;

	/** @var Hooks */
	private $hooks;

	/** @var object */
	private $brizy_admin_templates_instance;


	/**
	 * Brizy constructor.
	 *
	 * @param Gateway $brizy_gateway
	 * @param Hooks $hooks
	 */
	public function __construct( Gateway $brizy_gateway, Hooks $hooks ) {
		$this->brizy_gateway = $brizy_gateway;
		$this->hooks = $hooks;
	}


	/**
	 * @inheritDoc
	 */
	public function initialize() {
		// Brizy hooks into the_content filter and replaces the current post's content with its own content.
		// The problem is that we might apply the_content filter when processing a WYSIWYG field via the "types"
		// shortcode.
		//
		// That means, the shortcode output will become the page's output, which contains the shortcode itself again,
		// and we have an infinite loop.
		//
		// One solution is to use the suppress_filters="true" attribute for the types shortcode, but that may be
		// undesirable for other reasons, and we should never allow a fatal error just because of a missing attribute.
		//
		// Hence, we try to unhook Brizy before processing a content of Types shortcode.
		$this->hooks->add_filter(
			'pre_do_shortcode_tag', [ $this, 'maybe_disable_brizy_content_filter' ], 10, 2
		);
	}


	/**
	 * Disable the Brizy content filter if we're dealing with the "types" shortcode.
	 *
	 * @param string $return
	 * @param string $tag
	 *
	 * @return string
	 */
	public function maybe_disable_brizy_content_filter( $return, $tag ) {
		if ( 'types' === $tag ) {
			$this->disable_brizy_content_filter();
		}

		return $return;
	}


	private function disable_brizy_content_filter() {
		if ( ! $this->brizy_gateway->brizy_admin_templates_class_exists() ) {
			// Just a failsafe against unexpected changes in Brizy.
			return;
		}

		$this->brizy_admin_templates_instance = $this->brizy_gateway->get_brizy_admin_templates_instance();
		$this->hooks->remove_filter(
			'the_content', [ $this->brizy_admin_templates_instance, 'filterPageContent' ], - 12000
		);

		// Re-add the filter after the shortcode has been processed. This is most likely not needed, but better
		// safe than sorry.
		$this->hooks->add_filter(
			'do_shortcode_tag',
			[ $this, 'readd_brizy_content_filter' ],
			10,
			2
		);
	}


	/**
	 * Re-add the Brizy content filter when processing a "types" shortcode.
	 *
	 * Expects that $this->brizy_admin_templates_instance has been populated before.
	 *
	 * @param string $output
	 * @param string $tag
	 *
	 * @return string
	 */
	public function readd_brizy_content_filter( $output, $tag ) {
		if ( 'types' === $tag ) {
			$this->hooks->add_filter(
				'the_content', [ $this->brizy_admin_templates_instance, 'filterPageContent' ], - 12000
			);
		}

		return $output;
	}
}
