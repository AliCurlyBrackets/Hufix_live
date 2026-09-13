<?php

namespace WPML\Import\Integrations\WPImportExport;

use WPML\FP\Obj;
use WPML\Import\Helper\Page;

use function WPML\Container\make;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_CLI_Action_Loader {

	const LABEL = 'WP Import Export';

	/**
	 * @return \IWPML_Action[]|null
	 */
	public function create() {
		$hooks = [];

		if ( self::isExporting() ) {
			$hooks[] = make( \WPML\Import\Integrations\Base\Strategies\Simulate\ExportPostsHooks::class );
			$hooks[] = make( \WPML\Import\Integrations\Base\Strategies\Simulate\ExportTermsHooks::class );
		} elseif ( self::isImporting() ) {
			$hooks[] = make( ImportPostsStatusHooks::class );
		}

		$hooks[] = make( PrepareFieldsHooks::class );

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
	private static function isExporting() {
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			&& 'wpie_export_update_data' === Obj::prop( 'action', $_GET )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private static function isImporting() {
		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			&& 'wpie_import_data' === Obj::prop( 'action', $_GET )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isOnExportPage() {
		return Page::isOn( '/admin.php?page=wpie-new-export' );
	}

	/**
	 * @return bool
	 */
	public static function isOnImportPage() {
		return Page::isOn( '/admin.php?page=wpie-new-import' );
	}
}
