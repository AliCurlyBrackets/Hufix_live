<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;

class FlushTranslationsCache implements Base\Command {

	public static function getTitle(): string {
		return __( 'Clearing Translations Cache', 'wpml-import' );
	}

	public static function getDescription(): string {
		return __( 'Invalidating the persistent cache so it can be regenerated.', 'wpml-import' );
	}

	public function countPendingItems( Collection $args = null ): int {
		return 1;
	}

	public function run( Collection $args = null ): int {
		if ( ! class_exists( \WPML_WP_Cache::class ) ) {
			return 1;
		}

		wpml_collect(
			[
				defined( 'WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP' ) ? WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP : null, // Optional constant.
				'WPML_Name_Query_Filter_Translated',
				'WPML_Name_Query_Filter_Untranslated',
			]
		)
			->filter() // Removes nulls for undefined constants.
			->each( fn( $group ) => ( new \WPML_WP_Cache( $group ) )->flush_group_cache() );

		return 1;
	}
}
