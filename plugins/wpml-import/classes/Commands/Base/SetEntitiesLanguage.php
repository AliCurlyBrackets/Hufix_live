<?php

namespace WPML\Import\Commands\Base;

use WPML\Collect\Support\Collection;
use WPML\FP\Lst;
use WPML\FP\Obj;

abstract class SetEntitiesLanguage implements Command {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 200;

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	protected $sitepress;

	public function __construct( \wpdb $wpdb, \SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		return (int) count( $this->getPendingItems() );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null ) {
		$countProcessed = 0;

		foreach ( $this->getPendingItemsByTranslationGroup( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) ) as $itemsGroup ) {
			$this->processTranslationGroup( $itemsGroup );
			$countProcessed += count( $itemsGroup );
		}

		return $countProcessed;
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int $limit
	 *
	 * @return array[]
	 */
	private function getPendingItemsByTranslationGroup( $limit ) {
		$items       = $this->getPendingItems( $limit );
		$isLastBatch = count( $items ) < $limit;
		$itemsGroups = [];

		foreach ( $items as $item ) {
			if ( ! array_key_exists( $item->translation_group, $itemsGroups ) ) {
				$itemsGroups[ $item->translation_group ] = [];
			}

			$itemsGroups[ $item->translation_group ][] = $item;
		}

		if ( ! $isLastBatch ) {
			array_pop( $itemsGroups ); // Remove the last group to prevent incomplete ones.
		}

		return $itemsGroups;
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param array $itemsGroup
	 *
	 * @return void
	 */
	private function processTranslationGroup( array $itemsGroup ) {
		$isTranslation                 = Obj::prop( 'source_language_code' );
		list( $translations, $source ) = Lst::partition( $isTranslation, $itemsGroup );
		$source                        = reset( $source );
		list( $trid, $elementType )    = $this->getTridAndType( $source, $translations );

		foreach ( $translations as $translation ) {
			// This causes DB errors (WordPress database error Duplicate entry '657-en' for key 'trid_lang')
			// when the source language is not the default language.
			// Indeed, any imported content is in default language initially.
			// We might need an adaptation of the `set_element_language_details` method (see how WPML connects existing translations).
			$this->sitepress->set_element_language_details( $translation->element_id, $elementType, $trid, $translation->language_code, $translation->source_language_code );
		}

		$this->deleteLanguageFields( $itemsGroup );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int|null $limit
	 *
	 * @return array
	 */
	abstract protected function getPendingItems( $limit = null );

	/**
	 * @codeCoverageIgnore
	 *
	 * @param object|null $source
	 * @param object[]    $translations
	 *
	 * @return array
	 */
	abstract protected function getTridAndType( $source, $translations );

	/**
	 * @codeCoverageIgnore
	 *
	 * @param object[] $itemsGroup
	 *
	 * @return void
	 */
	abstract protected function deleteLanguageFields( $itemsGroup );
}
