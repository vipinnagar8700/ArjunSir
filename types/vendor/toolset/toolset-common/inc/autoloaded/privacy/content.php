<?php

namespace OTGS\Toolset\Common\Privacy;

/**
 * Privacy content registerer, to be extended by any Toolset plugin that collects private data.
 * 
 * @since 3.1
 */
abstract class Content {

    /**
     * Initialize the privacy content registration.
     *
     * @since 3.1
     */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'privacy_policy' ) );
	}

    /**
     * Register the privacy content.
     *
     * @since 3.1
     */
	public function privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$policy_text_content = $this->get_privacy_policy();
		if ( $policy_text_content ) {
			if ( is_array( $policy_text_content ) ) {
				$policy_text_content = '<p>' . implode( '</p><p>', $policy_text_content ) . '</p>';
			}
			wp_add_privacy_policy_content( $this->get_plugin_name(), $policy_text_content );
		}
	}

	/**
     * Get the name of the plugin collecting private data.
     * 
	 * @return string
	 */
	abstract protected function get_plugin_name();

	/**
     * Get the privacy content.
     * 
	 * @return string|array a single or an array of strings (plain text or HTML). Array items will be wrapped by a paragraph tag.
	 */
	abstract protected function get_privacy_policy();
}
