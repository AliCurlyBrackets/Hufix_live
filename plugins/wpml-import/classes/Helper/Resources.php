<?php

namespace WPML\Import\Helper;

use function WPML\FP\partial;

class Resources {

	/**
	 * @param string   $app
	 * @param string[] $dependencies WordPress script handles that must be
	 *                               loaded before this app's script.
	 *
	 * @return callable|\Closure
	 *
	 * @codeCoverageIgnore
	 */
	public static function enqueueApp( $app, array $dependencies = [] ) {
		return function ( $localize ) use ( $app, $dependencies ) {
			\WPML\LIB\WP\App\Resources::enqueueWithDeps(
				$app,
				WPML_IMPORT_PLUGIN_URL,
				WPML_IMPORT_PLUGIN_PATH,
				WPML_IMPORT_VERSION,
				'wpml-import',
				$localize,
				$dependencies
			);
		};
	}
}
