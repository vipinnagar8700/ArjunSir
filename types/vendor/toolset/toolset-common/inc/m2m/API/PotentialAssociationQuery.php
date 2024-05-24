<?php

namespace OTGS\Toolset\Common\Relationships\API;

/**
 * When you have a relationship and a specific element in one role, this
 * query will help you to find elements that can be associated with it.
 *
 * It takes into account all the aspects, like whether the relationship is distinct or not.
 *
 * Important terminology for the potential association query codebase:
 *
 * - $for_element: The element for which we're searching posts that can be connected to it.
 * - $target_role: Role on the opposite side of $for_element in the given relationship.
 *
 * @since 4.0
 */
interface PotentialAssociationQuery {


	/**
	 * @param bool $check_can_connect_another_element Check wheter it is possible to connect any other element at all,
	 *     and return an empty result if not.
	 * @param bool $check_distinct_relationships Exclude elements that would break the "distinct" property of a
	 *     relationship. You can set this to false if you're overwriting an existing association.
	 *
	 * @return \IToolset_Element[]
	 */
	public function get_results( $check_can_connect_another_element = true, $check_distinct_relationships = true );


	/**
	 * Returns the number of found elements _after_ the query has been performed (via get_results()).
	 *
	 * @return int
	 */
	public function get_found_elements();


	/**
	 * Check whether a specific single element can be associated.
	 *
	 * The relationship, target role and the other element are those provided in the constructor.
	 *
	 * @param \IToolset_Element $association_candidate Element that wants to be associated.
	 * @param bool $check_is_already_associated Perform the check that the element is already associated for distinct
	 *     relationships. Default is true. Set to false only if the check was performed manually before.
	 *
	 * @return \Toolset_Result Result with an user-friendly message in case the association is denied.
	 */
	public function check_single_element( \IToolset_Element $association_candidate, $check_is_already_associated = true );


	/**
	 * Check whether the element provided in the constructor can accept any new association whatsoever.
	 *
	 * @return \Toolset_Result Result with an user-friendly message in case the association is denied.
	 */
	public function can_connect_another_element();


	/**
	 * Check whether there already exists an association between the the target element and the provided one.
	 *
	 * Note that it doesn't always have to be a problem, it depends on whether the relationship is distinct or not.
	 * This was made public to optimize performance during the m2m migration process.
	 *
	 * @param \IToolset_Element $element
	 *
	 * @return bool
	 */
	public function is_element_already_associated( \IToolset_Element $element );

}
