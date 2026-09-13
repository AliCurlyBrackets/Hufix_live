<?php

namespace WPML\Import\API;

use WPML\Import\Commands\Provider;

class Commands {

	/**
	 * Process import commands for the given context
	 *
	 * @param string $context
	 */
	public function processImport( $context = 'hook' ) {
		$commands = Provider::get( $context );

		foreach ( $commands as $commandClass ) {
			$command = Provider::getCommandInstance( $commandClass );

			if ( $command ) {
				$toProcessCount = $command->countPendingItems();

				while ( $toProcessCount > 0 ) {
					$processedCount  = $command->run();
					$toProcessCount -= $processedCount;
				}
			}
		}
	}
}
