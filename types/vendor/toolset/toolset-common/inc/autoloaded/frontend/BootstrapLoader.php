<?php

namespace OTGS\Toolset\Common;

/**
 * Manages enqueuing Bootstrap assets on the front-end.
 *
 * @since BS4
 */
class BootstrapLoader {

	/** @var \Toolset_Settings */
	private $settings;


	/**
	 * BootstrapLoader constructor.
	 *
	 * @param \Toolset_Settings $settings
	 */
	public function __construct( \Toolset_Settings $settings ) {
		$this->settings = $settings;
	}


	/**
	 * Initialize the controller. This needs to happen during init on frontend requests.
	 */
	public function initialize() {
		$bs_setting = $this->settings->get_bootstrap_setting();
		if ( ! $bs_setting->needs_enqueuing() ) {
			return;
		}

		/**
		 * Allow overriding the priority for enqueuing Bootstrap assets.
		 *
		 * See BootstrapSetting::get_enqueuing_priority() for details.
		 *
		 * @param int Priority for the wp_enqueue_scripts action hook.
		 * @return int
		 * @since BS4
		 */
		$enqueuing_priority = (int) apply_filters( 'toolset_bootstrap_enqueuing_priority', $bs_setting->get_enqueuing_priority() );

		add_action( 'wp_enqueue_scripts', function () use ( $bs_setting ) {
			do_action( 'toolset_enqueue_styles', $bs_setting->get_styles_to_enqueue() );
			do_action( 'toolset_enqueue_scripts', $bs_setting->get_scripts_to_enqueue() );
		}, $enqueuing_priority );
	}
}
