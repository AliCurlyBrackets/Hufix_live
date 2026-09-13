<?php

namespace WPML\Import\CLI;

use WPML\Import\Commands\Provider;

class Commands {

	const CONTEXT = 'cli';

	/**
	 * Runs all the import process commands.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function process() {
		$commands = Provider::get( self::CONTEXT );

		foreach ( $commands as $commandClass ) {
			$command = Provider::getCommandInstance( $commandClass );

			if ( $command ) {
				$toProcessCount = $command->countPendingItems();
				$progress       = new Progress( $command->getTitle(), $toProcessCount );

				while ( $toProcessCount > 0 ) {
					$processedCount = $command->run();
					$progress->tick( $processedCount );
					$toProcessCount -= $processedCount;
				}

				$progress->finish();
			}
		}
	}
}
