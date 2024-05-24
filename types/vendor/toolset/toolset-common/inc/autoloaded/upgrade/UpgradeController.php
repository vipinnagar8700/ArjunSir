<?php

namespace OTGS\Toolset\Common\Upgrade;

use Exception;
use OTGS\Toolset\Common\Result\ResultSet;
use OTGS\Toolset\Common\Result\SingleResult;
use Toolset_Admin_Notice_Error;
use Toolset_Admin_Notices_Manager;

/**
 * Generic upgrade mechanism for plugins and for the Toolset Common library.
 *
 * Compares a version number in database with the current one. If the current version is lower,
 * it executes all the commands from the provided command repository that haven't been executed yet, and
 * updates the stored database number.
 *
 * Performance cost on every server request:
 *     - one add_action hook
 *     - one autoloaded option
 *
 * @since 2.5.1
 * @since 4.0 Refactored into a generic mechanism that can be used by other plugins, not just Toolset Common.
 */
class UpgradeController {

	/** @var bool */
	private $is_initialized = false;

	/** @var CommandDefinitionRepository */
	private $command_definition_repository;

	/** @var ExecutedCommands */
	private $executed_commands;

	/** @var Version */
	private $version;


	/**
	 * UpgradeController constructor.
	 *
	 * @param CommandDefinitionRepository $command_definition_repository
	 * @param ExecutedCommands $executed_commands
	 * @param Version $version
	 */
	public function __construct(
		CommandDefinitionRepository $command_definition_repository,
		ExecutedCommands $executed_commands,
		Version $version
	) {
		$this->command_definition_repository = $command_definition_repository;
		$this->executed_commands = $executed_commands;
		$this->version = $version;
	}


	/**
	 * Hook to check upgrades after Toolset Common is loaded.
	 */
	public function initialize() {
		if ( $this->is_initialized ) {
			return;
		}

		// When everything is loaded and possibly hooked in the upgrade action, we can proceed.
		add_action( 'toolset_common_loaded', [ $this, 'check_upgrade' ] );

		$this->is_initialized = true;
	}


	/**
	 * Check if an upgrade is needed.
	 *
	 * Do not call this manually, there's no need to.
	 *
	 * @since m2m
	 */
	public function check_upgrade() {
		$database_version = $this->version->get_version_from_database();
		$library_version = $this->version->get_current_version();
		$is_upgrade_needed = ( 0 !== $library_version && $database_version < $library_version );

		if ( 0 === $database_version ) {
			// Run on new sites without a database version.
			$this->do_setup();
		}

		if ( $is_upgrade_needed ) {

			// Safety measure - Abort if the library isn't fully loaded.
			if ( false === apply_filters( 'toolset_is_toolset_common_available', false ) ) {
				return;
			}

			$this->do_upgrade( $database_version, $library_version );
		}
	}


	/**
	 * Setup defaults on new sites without a database version.
	 *
	 * @since 3.6.0
	 */
	private function do_setup() {
		$results = new ResultSet();
		foreach ( $this->command_definition_repository->get_setup_commands() as $command_definition ) {
			$this->process_command( $command_definition, $results );
		}

		// No need to update the database, just manage errors.
		if ( $results->has_results() && ! $results->is_complete_success() ) {
			$this->show_error_notice( $results );
		}
	}


	/**
	 * Perform the actual upgrade.
	 *
	 * @param int $from_version
	 * @param int $to_version
	 */
	private function do_upgrade( $from_version, $to_version ) {
		$results = new ResultSet();

		foreach ( $this->command_definition_repository->get_commands() as $command_definition ) {
			if ( ! $command_definition->should_run( $from_version, $to_version ) ) {
				continue;
			}

			$this->process_command( $command_definition, $results );
		}

		// Only consider the database updated when everything has succeeded.
		if ( ! $results->has_results() || $results->is_complete_success() ) {
			$this->version->update_database_version( $to_version );
		} else {
			$this->show_error_notice( $results );
		}
	}


	/**
	 * Process a single command that ought to be executed based on the version number.
	 *
	 * @param CommandDefinition $command_definition
	 * @param ResultSet $results
	 */
	private function process_command( CommandDefinition $command_definition, ResultSet $results ) {
		if ( $this->executed_commands->was_executed(
			$this->command_definition_repository->get_prefix() . $command_definition->get_command_name()
		) ) {
			return;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$command = $command_definition->get_command();
			$result = $command->run();
		} else {
			// Ignore errors as we don't have a proper way to display any output from this yet.
			try {
				$command = $command_definition->get_command();
				$result = $command->run();
			}
				/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
				/** @noinspection PhpFullyQualifiedNameUsageInspection */
			catch ( \Throwable $e ) {
				// PHP 7
				$result = new SingleResult( $e );
			} /** @noinspection PhpRedundantCatchClauseInspection */
				/** @noinspection PhpWrongCatchClausesOrderInspection */
			catch ( Exception $e ) {
				// PHP 5
				$result = new SingleResult( $e );
			}
		}

		$is_success = $result instanceof ResultSet
			? $result->is_complete_success()
			: $result->is_success();

		if ( $is_success ) {
			$this->executed_commands->add_executed_command(
				$this->command_definition_repository->get_prefix() . $command_definition->get_command_name()
			);
		}

		$results->add( $result );
	}


	/**
	 * Show an undismissible temporary error message with upgrade results.
	 *
	 * @param ResultSet $results
	 *
	 * @since 2.6.4
	 */
	private function show_error_notice( ResultSet $results ) {
		$notice = new Toolset_Admin_Notice_Error(
			'toolset-database-upgrade-error',
			'<p>'
			. __( 'Oops! There\'s been a problem when upgrading Toolset data structures. Please make sure your current configuration allows WordPress to alter database tables.', 'wpv-views' )
			. sprintf(
				// translators: "a" tag with link to the support forum (opening and closing),
				__( 'If the problem persists, please don\'t hesitate to contact %1$sour support%2$s with this technical information:', 'wpv-views' ),
				'<a href="https://toolset.com/forums/forum/professional-support/?utm_source=plugin&utm_medium=gui&utm_campaign=toolset" target="_blank">',
				' <i class="fa fa-external-link"></i></a>'
			)
			. '</p>'
			. '<p><code>' . $results->concat_messages( "\n" ) . '</code></p>'
		);

		$notice->set_is_dismissible_permanent( false );
		$notice->set_is_dismissible_globally( false );
		$notice->set_is_only_for_administrators( true );

		Toolset_Admin_Notices_Manager::add_notice( $notice );
	}
}
