<?php

/**
 * Handler for the types_query_groups filter.
 *
 * @since 2.2
 */
class Types_Api_Handler_Query_Groups implements Types_Api_Handler_Interface {


	public function __construct() { }


	/**
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return Toolset_Field_Group[]|Toolset_Field_Group[][]|null
	 */
	public function process_call( $arguments ) {
		$query = toolset_getarr( $arguments, 1, array() );

		$domain = toolset_getarr( $query, 'domain', 'all' );
		unset( $query['domain'] );

		// Sanitize input
		if ( ! is_string( $domain )  || ! is_array( $query ) ) {
			return null;
		}

		if ( 'all' === $domain ) {
			// Separate query for each available domain.
			$groups_by_domain = array();
			foreach( Toolset_Element_Domain::all() as $field_domain ) {
				$groups_by_domain[ $field_domain ] = $this->query_specific_domain( $field_domain, $query );
			}

			return $groups_by_domain;
		}

		return $this->query_specific_domain( $domain, $query );
	}


	/**
	 * Query field groups from a single domain.
	 *
	 * @param string $domain One of the valid field domains. Legacy "meta type" values will be also accepted.
	 * @param array $query Query arguments for Types_Field_Group_Factory::query_groups().
	 *
	 * @return null|Toolset_Field_Group[] Array of field groups or null on error.
	 * @since m2m
	 */
	private function query_specific_domain( $domain, $query ) {

		// Make sure we have a valid domain string.
		if( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
			$domain = Toolset_Field_Utils::legacy_meta_type_to_domain( $domain );
		}

		// Pass the group query to the proper factory class.
		try {
			$group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );
			$groups = $group_factory->query_groups( $query );
		} catch( Exception $e ) {
			// We don't care, it's a failure.
			return null;
		}

		return $groups;

	}

}
