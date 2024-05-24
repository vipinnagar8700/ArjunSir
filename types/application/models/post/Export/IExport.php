<?php

namespace OTGS\Toolset\Types\Post\Export;

/**
 * Interface IExport
 * @package OTGS\Toolset\Types\Post\Export
 *
 * @since 3.0
 */
interface IExport {
	/**
	 * @param \IToolset_Post $post
	 *
	 * @return array
	 */
	public function getExportArray( \IToolset_Post $post );
}