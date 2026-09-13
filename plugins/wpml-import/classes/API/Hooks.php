<?php

namespace WPML\Import\API;

use WPML\LIB\WP\Hooks as WPHooks;
use WPML\Import\Helper\ImportedItems;

class Hooks implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const TRIGGER_ENDPOINT = 'wpml_import_trigger';
	const TRIGGER_HOOK     = 'wpml_import_process';

	/**
	 * @var Commands
	 */
	private $commands;

	/**
	 * @var ImportedItems
	 */
	private $importedItems;

	public function __construct( Commands $commands, ImportedItems $importedItems ) {
		$this->commands      = $commands;
		$this->importedItems = $importedItems;
	}

	public function add_hooks() {
		WPHooks::onAction( 'init' )
			->then( [ $this, 'handleUrlRequest' ] );

		WPHooks::onAction( self::TRIGGER_HOOK )
			->then( [ $this->commands, 'processImport' ] );
	}

	public function handleUrlRequest() {
		if ( ! $this->isValidTriggerRequest() ) {
			return;
		}

		$key = $this->getTriggerKey();

		if ( ! $this->isValidKey( $key ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wpml-import' ), esc_html__( 'Unauthorized', 'wpml-import' ), 401 );
		}

		if ( ! $this->hasItemsToProcess() ) {
			wp_die( esc_html__( 'No items to process', 'wpml-import' ), esc_html__( 'No Content', 'wpml-import' ), 204 );
		}

		try {
			$this->commands->processImport( 'endpoint' );

			wp_die( esc_html__( 'Import process completed', 'wpml-import' ), esc_html__( 'Success', 'wpml-import' ), 200 );
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG_LOG' ) && constant( 'WP_DEBUG_LOG' ) ) {
				/* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log */
				error_log( $e->getMessage() );
			}

			wp_die( esc_html__( 'Import process failed', 'wpml-import' ), esc_html__( 'Internal Server Error', 'wpml-import' ), 500 );
		}
	}

	/**
	 * @return bool
	 */
	private function isValidTriggerRequest(): bool {
		$method = $this->getRequestMethod();

		if ( 'POST' === $method ) {
			/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
			return ! empty( $_POST[ self::TRIGGER_ENDPOINT ] );
		}

		// GET is retained for backward compatibility (see getTriggerKey()).
		if ( 'GET' === $method ) {
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			return ! empty( $_GET[ self::TRIGGER_ENDPOINT ] );
		}

		return false;
	}

	/**
	 * Reads the trigger key from the current request.
	 *
	 * POST is the supported transport. Reading the key from $_GET is kept for
	 * backward compatibility with existing integrations and will be removed in
	 * the next major release.
	 *
	 * @deprecated 2.0.0 Triggering the endpoint via GET is deprecated; use POST.
	 *
	 * @return string
	 */
	private function getTriggerKey(): string {
		if ( 'GET' === $this->getRequestMethod() ) {
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			return sanitize_text_field( $_GET[ self::TRIGGER_ENDPOINT ] ?? '' );
		}

		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		return sanitize_text_field( $_POST[ self::TRIGGER_ENDPOINT ] ?? '' );
	}

	/**
	 * @return string
	 */
	private function getRequestMethod(): string {
		/* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */
		return strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	private function isValidKey( string $key ): bool {
		if ( ! defined( 'WPML_IMPORT_KEY' ) ) {
			return false;
		}
		$expectedKey = constant( 'WPML_IMPORT_KEY' );
		if ( ! is_string( $expectedKey ) || '' === $expectedKey ) {
			return false;
		}
		return hash_equals( $expectedKey, $key );
	}

	/**
	 * @return bool
	 */
	private function hasItemsToProcess() {
		return $this->importedItems->countPosts() > 0 || $this->importedItems->countTerms() > 0;
	}
}
