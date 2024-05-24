<?php
/**
 * Events Gutenberg Assets
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Editor__Assets {
	/**
	 * @since 4.9
	 *
	 * @return void
	 */
	public function hook() {
	}

	/**
	 * Registers and Enqueues the assets
	 *
	 * @since 4.9
	 */
	public function register() {
		$plugin = Tribe__Tickets_Plus__Main::instance();
		tribe_asset(
			$plugin,
			'tribe-tickets-plus-gutenberg-data',
			'app/data.js',
			/**
			 * @todo revise this dependencies
			 */
			array(
				'react',
				'react-dom',
				'thickbox',
				'wp-components',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
			),
			'enqueue_block_editor_assets',
			array(
				'in_footer'    => false,
				'localize'     => array(),
				'conditionals' => tribe_callback( 'tickets.editor', 'current_type_support_tickets' ),
				'priority'     => 200,
			)
		);
	}
}
