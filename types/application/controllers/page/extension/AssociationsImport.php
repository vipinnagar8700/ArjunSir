<?php

namespace OTGS\Toolset\Types\Controller\Page\Extension;

use OTGS\Toolset\Common\Wordpress\Option\IOption;
use OTGS\Toolset\Types\Post\Import\Association\View\Notice;
use OTGS\Toolset\Types\Post\Meta\Associations as AssociationsMeta;
use OTGS\Toolset\Types\Wordpress\Postmeta\Storage as StoragePostmeta;

/**
 * @since 3.0
 */
class AssociationsImport {

	/** @var bool $status */
	private $is_import_available;

	/** @var StoragePostmeta */
	private $storage_postmeta;

	/** @var AssociationsMeta $meta_associations */
	private $meta_associations;

	/** @var Notice $admin_notice */
	private $admin_notice;

	/** @var IOption */
	private $option_associations_import_is_available;


	/**
	 * Associations constructor.
	 *
	 * @action admin_init
	 *
	 * @param StoragePostmeta $storage_postmeta
	 * @param AssociationsMeta $meta
	 * @param Notice $admin_notice
	 * @param IOption $option_associations_import_is_available
	 */
	public function __construct(
		StoragePostmeta $storage_postmeta,
		AssociationsMeta $meta,
		Notice $admin_notice,
		IOption $option_associations_import_is_available
	) {
		$this->storage_postmeta = $storage_postmeta;
		$this->meta_associations = $meta;
		$this->admin_notice = $admin_notice;
		$this->option_associations_import_is_available = $option_associations_import_is_available;

		$this->setIsImportAvailable();
		$this->showAdminNotice();
	}


	/**
	 * Set the state if an import is availiable or not.
	 *
	 * We can only store the state for the case there is import data available. It's not possible
	 * to store the case when there is no data available, because we want to allow any importer
	 * to import association data, but we cannot force these importers to update the import available status
	 * when they really import a new association set.
	 *
	 */
	private function setIsImportAvailable() {
		$option = $this->option_associations_import_is_available->getOption();

		$this->is_import_available = $option
			? : $this->storage_postmeta->postMetaExistsByKey(
				$this->meta_associations->getKeyWithWildcardForMysql()
			);

		if ( $this->is_import_available && ! $option ) {
			// import available, store as option for next request.
			$this->option_associations_import_is_available->updateOption( true );
		}
	}


	/**
	 * Admin notice for importing associations
	 */
	private function showAdminNotice() {
		if ( ! $this->is_import_available ) {
			// no import data, no need to inform the client
			return;
		}

		$this->admin_notice->show();
	}
}
