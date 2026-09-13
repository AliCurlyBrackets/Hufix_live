<?php

namespace WPML\Import\CLI;

use cli\Streams;

/**
 * Inspired by WP_CLI Progress class.
 *
 * @see \WP_CLI\Utils\make_progress_bar
 */
class Progress {

	const FORMAT_LINE         = '{:lineLabel}: {:processed}/{:total} items ({:percentage})';
	const FORMAT_LINE_NO_ITEM = '{:lineLabel}: no item';

	/**
	 * @var string $lineLabel
	 */
	private $lineLabel;

	/**
	 * @var int $totalItems
	 */
	private $totalItemsCount;

	/**
	 * @var int $processedItemsCount
	 */
	private $processedItemsCount = 0;

	/**
	 * @param string $lineLabel
	 * @param int    $totalItemsCount
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $lineLabel, $totalItemsCount ) {
		$this->lineLabel       = $lineLabel;
		$this->totalItemsCount = $totalItemsCount;

		$this->outputLine( \WP_CLI::colorize( '…' ) );
	}

	/**
	 * @param int $newProcessedItemsCount
	 *
	 * @return void
	 */
	public function tick( $newProcessedItemsCount ) {
		$this->processedItemsCount += $newProcessedItemsCount;
		$this->outputLine( \WP_CLI::colorize( '…' ) );
	}

	/**
	 * @return void
	 */
	public function finish() {
		$this->outputLine( \WP_CLI::colorize( '%G✓%n' ) );
		Streams::line(); // @phpstan-ignore-line
	}

	/**
	 * @param string $prefix
	 *
	 * @return void
	 */
	private function outputLine( $prefix ) {
		$format = $this->hasItem() ? self::FORMAT_LINE : self::FORMAT_LINE_NO_ITEM;
		Streams::out( "\r" ); // @phpstan-ignore-line
		Streams::out( $prefix . ' ' . $format, $this->getVars() ); // @phpstan-ignore-line
	}

	/**
	 * @return array
	 */
	private function getVars() {
		if ( $this->hasItem() ) {
			$percentage = min( 100, ceil( 100 * $this->processedItemsCount / $this->totalItemsCount ) );
		} else {
			$percentage = 100;
		}

		return [
			'lineLabel'  => $this->lineLabel,
			'processed'  => $this->processedItemsCount,
			'total'      => $this->totalItemsCount,
			'percentage' => $percentage . '%',
		];
	}

	/**
	 * @return bool
	 */
	private function hasItem() {
		return $this->totalItemsCount > 0;
	}
}
