<?php


namespace OTGS\Toolset\Types\Post\Export;

/**
 * Class Extender
 * @package OTGS\Toolset\Types\Post\Export
 *
 * @since 3.0
 */
class Extender implements IExport {

	/** @var IExport[] */
	private $export_modules = array();

	/**
	 * @param IExport $module
	 */
	public function addExportModule( IExport $module ) {
		$this->export_modules[] = $module;
	}

	/**
	 * @param \IToolset_Post $toolset_post
	 *
	 * @return array key and value of extra data
	 */
	public function getExportArray( \IToolset_Post $toolset_post ) {
		$extra_data = array();

		foreach( $this->export_modules as $module ){
			$extra_data = array_merge( $extra_data, $module->getExportArray( $toolset_post ) );
		}

		return $extra_data;
	}
}