<?php

namespace WPML\Import\Integrations\WooCommerce;

use WPML\Import\Commands\Base\Command;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class CommandHooks implements \IWPML_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_import_process_commands' )
			->then( spreadArgs( [ $this, 'addCommands' ] ) );
	}

	/**
	 * @param class-string<Command>[] $commands The command class names.
	 *
	 * @return class-string<Command>[]
	 */
	public function addCommands( $commands ) {
		return array_merge(
			$commands,
			[
				Commands\ConnectAttributesUsedInProductVariations::class,
				Commands\ConnectRelatedProducts::class,
				Commands\ProcessLocalAttributeLabels::class,
			]
		);
	}
}
