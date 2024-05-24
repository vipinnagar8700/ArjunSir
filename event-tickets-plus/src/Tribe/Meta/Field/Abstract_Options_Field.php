<?php

abstract class Tribe__Tickets_Plus__Meta__Field__Abstract_Options_Field extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public function build_extra_field_settings( $meta, $data ) {
		$options = isset( $data['extra'] ) && isset( $data['extra']['options'] ) ? $data['extra']['options'] : '';

		if ( $options ) {
			if ( ! isset( $meta['extra'] ) ) {
				$meta['extra'] = array();
			}

			if ( is_string( $options ) ) {
				$options = explode( "\n", $options );
				$options = array_map( 'trim', $options );
				//remove empty lines, don't create dummy options
				$options = array_filter( $options, 'strlen' );
			}

			$meta['extra']['options'] = $options;
		}

		return $meta;
	}
}
