<?php

namespace WPML\Import\Commands\Base;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Provider;

// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
abstract class CleanupFields implements Command {

	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 200;

	/** @var \wpdb $wpdb */
	protected $wpdb;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return string
	 */
	abstract protected function getFieldsTable();

	/**
	 * @param class-string $command
	 *
	 * @return string[]
	 */
	abstract protected function getCommandFields( $command );

	/**
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		$fields = $this->getFieldsToCleanup();

		return (int) $this->wpdb->get_var(
			"
			SELECT
				COUNT(*)
			FROM {$this->getFieldsTable()}
			WHERE meta_key IN(" . wpml_prepare_in( $fields ) . ")
			"
		);
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null ) {
		$fields = $this->getFieldsToCleanup();

		return (int) $this->wpdb->query(
			$this->wpdb->prepare(
				"
				DELETE FROM {$this->getFieldsTable()}
				WHERE meta_key IN(" . wpml_prepare_in( $fields ) . ")
				LIMIT %d
				",
				$this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT )
			)
		);
	}

	/**
	 * @return array
	 */
	private function getFieldsToCleanup() {
		$fieldsFromCommands = wpml_collect( Provider::getProcessCommands( static::class ) )
			->reduce(
				function ( array $carry, $commandClass ) {
					return array_merge( $carry, $this->getCommandFields( $commandClass ) );
				},
				[]
			);

		return array_merge(
			[
				\WPML\Import\Fields::LANGUAGE_CODE,
				\WPML\Import\Fields::SOURCE_LANGUAGE_CODE,
				\WPML\Import\Fields::TRANSLATION_GROUP,
				\WPML\Import\Fields::FINAL_POST_STATUS,
				\WPML\Import\Integrations\WooCommerce\ImportHooks::TRANSLATION_SKU_META_KEY,
			],
			$fieldsFromCommands
		);
	}
}
// phpcs:enable