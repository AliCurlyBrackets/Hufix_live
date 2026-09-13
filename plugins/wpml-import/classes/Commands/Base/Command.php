<?php

namespace WPML\Import\Commands\Base;

use WPML\Collect\Support\Collection;

interface Command {

	/**
	 * @return string
	 */
	public static function getTitle();

	/**
	 * @return string
	 */
	public static function getDescription();

	/**
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function countPendingItems( Collection $args = null );

	/**
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null );
}
