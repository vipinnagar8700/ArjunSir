<?php

/**
 * Page controller for the Relationships page if m2m is not enabled.
 *
 * It explains the situation to the user and allows to run the m2m activation wizard.
 *
 * @since 2.3-b4
 */
class Types_Page_Relationships_Inactive extends Types_Page_Persistent {


	const MAIN_ASSET_HANDLE = 'types-page-relationships-inactive';


	/** @var Toolset_Renderer */
	private $renderer;


	/** @var Types_Output_Template_Repository */
	private $template_repository;


	/**
	 * Types_Page_Relationships_Inactive constructor.
	 *
	 * @param array $args
	 * @param Toolset_Renderer|null $renderer_di
	 * @param Types_Output_Template_Repository|null $template_repository_di
	 */
	public function __construct(
		$args,
		Toolset_Renderer $renderer_di = null,
		Types_Output_Template_Repository $template_repository_di = null
	) {
		parent::__construct( $args );

		$this->renderer = $renderer_di ?: Toolset_Renderer::get_instance();
		$this->template_repository = $template_repository_di ?: Types_Output_Template_Repository::get_instance();
	}


	/**
	 * Render the page.
	 */
	public function render_page() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->renderer->render(
			$this->template_repository->get( Types_Output_Template_Repository::RELATIONSHIPS_PAGE_M2M_INACTIVE ),
			array()
		);
	}


	public function prepare() {
		$page_extension = new Types_Page_Extension_M2m_Migration_Dialog();
		$page_extension->prepare();

		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ) );
	}


	public function on_admin_enqueue_scripts() {
		wp_enqueue_script(
			self::MAIN_ASSET_HANDLE,
			TYPES_RELPATH . '/public/page/relationships/inactive.js',
			array(
				'jquery',
				Toolset_Assets_Manager::SCRIPT_HEADJS,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
				Types_Page_Extension_M2m_Migration_Dialog::MAIN_ASSET_HANDLE,
			),
			TYPES_VERSION
		);
	}

}
