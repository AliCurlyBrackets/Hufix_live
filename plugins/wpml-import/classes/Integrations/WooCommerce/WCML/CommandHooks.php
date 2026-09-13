<?php

namespace WPML\Import\Integrations\WooCommerce\WCML;

use WPML\Import\Commands\Base\Command;
use WPML\Import\Commands\SetInlineTermsLanguage;
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
		array_splice(
			$commands,
			array_search( SetInlineTermsLanguage::class, $commands, true ) ?: count( $commands ),
			1,
			[
				SetInlineTermsLanguage::class,
				Commands\RegisterAttributesAsTranslatableTaxonomies::class,
				Commands\SetAttributesLanguage::class,
			]
		);

		return $commands;
	}
}
