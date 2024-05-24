<?php
namespace OTGS\Toolset\Common\Interop\Handler;

use OTGS\Toolset\Common\CodeSnippets\Repository;
use OTGS\Toolset\Common\CodeSnippets\SettingsTab;
use OTGS\Toolset\Common\CodeSnippets\SnippetOption;
use OTGS\Toolset\Common\Utils\RequestMode;
use Toolset_Common_Bootstrap;


/**
 * Interop handler for the code snippet support.
 *
 * Execute active snippets when appropriate and initialize the GUI (which further routes to
 * the OTGS\Toolset\Common\CodeSnippets\SettingsTab class).
 *
 * DIC-wise, this class acts as wiring/bootstrap code.
 *
 * @since 3.0.8
 */
class CodeSnippets {


	/** @var string Slug of the Toolset Settings tab */
	const TAB_SLUG = 'code-snippets';

	/** @var string Name of the $_REQUEST parameter that can be used to disable code snippets in that request. */
	const RESCUE_MODE_PARAMETER = 'toolset-disable-code-snippets';

	/** @var string Name of the $_REQUEST parameter that has to be used to run an on-demand snippet. */
	const ON_DEMAND_RUN_TRIGGER = 'toolset-run-code-snippet';


	/** @var \Toolset_Constants */
	private $constants;


	/** @var RequestMode */
	private $request_mode;


	/** @var Repository */
	private $snippet_repository;


	/**
	 * CodeSnippets constructor.
	 *
	 * @param \Toolset_Constants $constants
	 * @param RequestMode $request_mode
	 */
	public function __construct( \Toolset_Constants $constants, RequestMode $request_mode ) {
		$this->constants = $constants;
		$this->request_mode = $request_mode;
	}


	/**
	 * Run snippets and initialize the GUI, depending on control constants.
	 */
	public function initialize() {
		$need_gui = (
			! $this->constants->defined( 'TOOLSET_DISABLE_CODE_SNIPPETS_GUI' )
			|| ! $this->constants->constant( 'TOOLSET_DISABLE_CODE_SNIPPETS_GUI' )
		);

		$need_snippets_run = (
			( ! $this->constants->defined( 'TOOLSET_CODE_SNIPPETS_TEST_MODE' )
				|| $this->constants->constant( 'TOOLSET_CODE_SNIPPETS_TEST_MODE' ) )
			&& ! toolset_getarr( $_REQUEST, self::RESCUE_MODE_PARAMETER )
		);

		if( $need_gui || $need_snippets_run ) {
			$dic = toolset_dic();
			/** @noinspection PhpUnhandledExceptionInspection */
			$this->snippet_repository = $dic->make( Repository::class );
			/** @noinspection PhpUnhandledExceptionInspection */
			$dic->share( $this->snippet_repository );
		}

		if( $need_gui ) {
			$this->initialize_gui();
		}

		if( $need_snippets_run ) {
			add_action( 'init', array( $this, 'run_snippets' ), 20 );
		}
	}


	private function initialize_gui() {
		if( $this->request_mode->get() !== RequestMode::ADMIN ) {
			return;
		}

		$tab_slug = self::TAB_SLUG;

		// Initialize the page (tab) controller early enough, so that it can enqueue assets, etc.
		add_action( 'init', [ $this, 'on_init' ] );

		// Add a new tab to Toolset Settings.
		add_filter( 'toolset_filter_toolset_register_settings_section', static function( $tabs ) use( $tab_slug ) {
			$tabs[ $tab_slug ] = array(
				'title' => __( 'Custom Code', 'wpv-views' ),
				'icon' => ''
			);
			return $tabs;
		}, 130 );

		// Render the content of the new tab.
		add_filter(
			"toolset_filter_toolset_register_settings_{$tab_slug}_section",
			static function( $sections ) use( $tab_slug ) {
				$dic = toolset_dic();
				/** @var SettingsTab $tab_controller */
				$tab_controller = $dic->make( '\OTGS\Toolset\Common\CodeSnippets\SettingsTab' );

				$sections[ "$tab_slug-main" ] = array(
					'slug' => "$tab_slug-main",
					'title' => __( 'Custom code snippets', 'wpv-views' ) . ' ' . $tab_controller->render_add_new_button(),
					'callback' => function() use( $tab_controller ) {
						echo $tab_controller->render_main_content();
					},
					'below_title' => $tab_controller->render_left_side()
				);
				return $sections;
			} );
	}


	public function on_init() {
		Toolset_Common_Bootstrap::get_instance()->register_gui_base();

		$dic = toolset_dic();
		/** @var SettingsTab $tab_controller */
		/** @noinspection PhpUnhandledExceptionInspection */
		$tab_controller = $dic->make( SettingsTab::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$dic->share( $tab_controller );
		$tab_controller->initialize();
	}


	/**
	 * Run scripts that should be executed in the current context.
	 */
	public function run_snippets() {

		if(
			$this->request_mode->get() === RequestMode::AJAX
		) {
			// Using DIC since we don't want to load and instantiate Toolset_Ajax on every request.
			$dic = toolset_dic();
			/** @var \Toolset_Ajax $ajax_manager */
			/** @noinspection PhpUnhandledExceptionInspection */
			$ajax_manager = $dic->make( \Toolset_Ajax::class );

			if( $ajax_manager->get_action_js_name( \Toolset_Ajax::CALLBACK_CODE_SNIPPETS_ACTION ) === toolset_getpost( 'action' ) ) {
				// Prevent double execution in case we're re-running a snippet manually when updating it.
				return;
			}
		}

		foreach( $this->snippet_repository->get_active_snippets() as $active_snippet ) {

			if( ! in_array( $this->request_mode->get(), $active_snippet->get_run_contexts() ) ) {
				// The script should not be run in this context.
				continue;
			}

			switch( $active_snippet->get_run_mode() ) {
				case SnippetOption::RUN_ON_DEMAND:
					// Skip the snippet if we're missing the on-demand trigger.
					if( toolset_getarr( $_REQUEST, self::ON_DEMAND_RUN_TRIGGER ) !== $active_snippet->get_slug() ) {
						continue 2; // continue with the next item in foreach
					}
					break;
				case SnippetOption::RUN_ONCE:
					// We will allow the script to run (below) but we'll also disable it, so that it doesn't run next time.
					// Note: Keeping this in case the "run once" mode is reintroduced in the future.
					$active_snippet->set_is_active( false );
					$this->snippet_repository->needs_option_update();
					break;
			}

			$result = $active_snippet->run();

			// Update the last error message if necessary.
			if( $result->is_error() || $result->is_error() !== $active_snippet->has_last_error() ) {
				$active_snippet->set_last_error( $this->decorate_error_message( $result ) );
				$this->snippet_repository->needs_option_update();
			}
		}

		// This is needed because of run-once snippets.
		$this->snippet_repository->maybe_update_option();
	}


	/**
	 * Add a request mode and a timestamp to the error message.
	 *
	 * @param \Toolset_Result $result
	 *
	 * @return string Decorated error message.
	 */
	private function decorate_error_message( \Toolset_Result $result ) {
		$message = sprintf('[%s, %s] %s', current_time( 'mysql' ), $this->request_mode->get(), $result->get_message() );
		return $message;
	}

}
