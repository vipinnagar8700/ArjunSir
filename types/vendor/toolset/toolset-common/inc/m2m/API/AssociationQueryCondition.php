<?php

namespace OTGS\Toolset\Common\Relationships\API;

/**
 * Represents a single condition for the AssociationQuery.
 *
 * Note: It is very important that if an OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Element_Selector_Provider instance
 * is passed to the condition, it doesn't try to obtain the element selector object
 * within its constructor.
 */
interface AssociationQueryCondition extends \IToolset_Query_Condition {

}
