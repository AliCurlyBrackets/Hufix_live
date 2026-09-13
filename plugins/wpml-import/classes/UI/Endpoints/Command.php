<?php

namespace WPML\Import\UI\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Left;
use WPML\FP\Right;
use WPML\Import\Commands\Provider as CommandsProvider;

class Command implements IHandler {

	const KEY_COMMAND_CLASS     = 'commandClass';
	const KEY_INDEX             = 'index';
	const KEY_STATUS            = 'status';
	const KEY_COUNT_TOTAL_ITEMS = 'countTotalItems';
	const KEY_COUNT_PROCESSED   = 'countProcessed';

	const STATUS_PROCESSING = 'processing';
	const STATUS_COMPLETE   = 'complete';

	const POSTS_PER_BATCH = 20;

	/**
	 * @param Collection $data
	 *
	 * @return Either
	 */
	public function run( Collection $data ) {
		if ( ! current_user_can( 'wpml_manage_languages' ) ) {
			return Left::of( [] );
		}

		$command = CommandsProvider::getCommandInstance( $data->get( self::KEY_COMMAND_CLASS ) );

		if ( ! $command ) {
			return Left::of( [] );
		}

		$index           = (int) $data->get( self::KEY_INDEX, 0 );
		$countTotalItems = (int) $data->get( self::KEY_COUNT_TOTAL_ITEMS, 0 );
		$countProcessed  = (int) $data->get( self::KEY_COUNT_PROCESSED, 0 );

		if ( $index ) {
			$countProcessedInStep = $command->run();
			if ( 0 === $countProcessedInStep ) {
				$processStatus = self::STATUS_COMPLETE;
			} else {
				$countProcessed += $countProcessedInStep;
				$processStatus   = $countProcessed >= $countTotalItems ? self::STATUS_COMPLETE : self::STATUS_PROCESSING;
			}
		} else {
			$countTotalItems = $command->countPendingItems();
			$processStatus   = self::STATUS_PROCESSING;
		}

		return Right::of(
			[
				self::KEY_INDEX             => $index + 1,
				self::KEY_STATUS            => $processStatus,
				self::KEY_COUNT_TOTAL_ITEMS => $countTotalItems,
				self::KEY_COUNT_PROCESSED   => $countProcessed,
			]
		);
	}
}
