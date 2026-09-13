<?php

namespace WPML\Import\Integrations\WordPress;

use WPML\FP\Str;
use WPML\Import\Helper\Page;
use function WPML\Container\make;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_CLI_Action_Loader {

	const LABEL = 'WordPress';

	/**
	 * @return \IWPML_Action[]|null
	 */
	public function create() {
		$hooks = [];

		if ( self::isExportingWithCli() || self::isExportingWithGui() ) {
			$hooks[] = make( ExportPostsHooks::class );
			$hooks[] = make( ExportTermsHooks::class );
		} elseif ( self::isImportingWithGui() || self::isImportingWithCli() ) {
			$hooks[] = make( ImportPostsStatusHooks::class );
		}

		if ( self::isOnExportPage() ) {
			$hooks[] = make( ExportNotice::class );
		} elseif ( self::isOnImportPage() ) {
			$hooks[] = make( ImportNotice::class );
		}

		return $hooks;
	}

	/**
	 * @return bool
	 */
	private static function isExportingWithCli() {
		if ( defined( 'WP_CLI' ) && isset( $_SERVER['argv'] ) ) {
			/** @var \Closure(string):bool $isCommandParam */
			$isCommandParam = Str::startsWith( '-' );

			$cliCommand = wpml_collect( (array) $_SERVER['argv'] )
				->forget( [ 0 ] ) // CLI process path.
				->reject( $isCommandParam )
				->first();

			return 'export' === $cliCommand;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function isExportingWithGui() {
		if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
			return false;
		}

		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return false;
		}

		$queryArgs = [];
		wp_parse_str( $_SERVER['QUERY_STRING'], $queryArgs );
		if ( ! array_key_exists( 'download', $queryArgs ) ) {
			return false;
		}

		if ( false === strpos( $_SERVER['HTTP_REFERER'], admin_url( 'export.php' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private static function isImportingWithCli() {
		if ( defined( 'WP_CLI' ) && isset( $_SERVER['argv'] ) ) {
			/** @var \Closure(string):bool $isCommandParam */
			$isCommandParam = Str::startsWith( '-' );

			$cliCommand = wpml_collect( (array) $_SERVER['argv'] )
				->forget( [ 0 ] ) // CLI process path.
				->reject( $isCommandParam )
				->first();

			return 'import' === $cliCommand;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function isImportingWithGui() {
		if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
			return false;
		}

		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return false;
		}

		$queryArgs = [];
		wp_parse_str( $_SERVER['QUERY_STRING'], $queryArgs );
		if ( ! array_key_exists( 'import', $queryArgs ) ) {
			return false;
		}
		if ( ! array_key_exists( 'step', $queryArgs ) ) {
			return false;
		}
		if (
			'wordpress' !== $queryArgs['import']
			|| 2 !== (int) $queryArgs['step']
		) {
			return false;
		}

		$referer = admin_url( 'admin.php?import=wordpress&step=1' );
		if ( substr( $_SERVER['HTTP_REFERER'], 0, strlen( $referer ) ) !== $referer ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function isOnExportPage() {
		return Page::isOn( '/export.php' );
	}

	/**
	 * @return bool
	 */
	public static function isOnImportPage() {
		return Page::isOn( '/admin.php?import=wordpress' );
	}
}
