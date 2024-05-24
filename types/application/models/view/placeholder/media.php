<?php

/**
 * @since 2.3
 */
class Types_View_Placeholder_Media implements Types_View_Placeholder_Interface {

	/**
	 * @param string|string[]|mixed $input
	 * @param null|string|Types_Interface_Media $media
	 *
	 * @param Types_Media_Service|null $service
	 *
	 * @return string|string[]|mixed
	 */
	public function replace( $input, $media = null, Types_Media_Service $service = null ) {
		if (
			$media === null
			|| ( ! is_string( $input ) && ! is_array( $input ) )
		) {
			return $input;
		}

		$input_as_string = is_array( $input )
			? implode( '', $input )
			: $input;

		if ( strpos( $input_as_string, '%%' ) === false ) {
			// no replacements needed
			return $input;
		}

		if ( is_string( $media ) ) {
			// only url is given, check if we need the full media object for replacement
			$media = $service->find_by_url( $media );
		}

		if ( ! $media instanceof Types_Interface_Media ) {
			// media data not available
			return $input;
		}

		// full media object given, proceed replacement
		$supported_replacements = array(
			'%%TITLE%%' => $media->get_title(),
			'%%CAPTION%%' => $media->get_caption(),
			'%%ALT%%' => $media->get_alt(),
			'%%DESCRIPTION%%' => $media->get_description(),
		);

		return str_replace(
			array_keys( $supported_replacements ),
			array_values( $supported_replacements ),
			$input
		);
	}
}
