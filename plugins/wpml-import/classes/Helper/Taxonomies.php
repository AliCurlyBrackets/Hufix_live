<?php

namespace WPML\Import\Helper;

use WPML\FP\Str;

class Taxonomies {

	/**
	 * @param bool $withWpmlPrefix
	 *
	 * @return array
	 */
	public static function getTranslatableOnly( $withWpmlPrefix = false ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		return wpml_collect( self::getTranslatable() )
			->diff( $sitepress->get_display_as_translated_taxonomies() )
			->map( self::addWpmlPrefix( $withWpmlPrefix ) )
			->toArray();
	}

	/**
	 * @param bool $withWpmlPrefix
	 *
	 * @return array
	 */
	public static function getTranslatable( $withWpmlPrefix = false ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		return wpml_collect( $sitepress->get_translatable_taxonomies() )
			->map( self::addWpmlPrefix( $withWpmlPrefix ) )
			->toArray();
	}

	/**
	 * @param bool $withWpmlPrefix
	 *
	 * @return callable(string):string
	 */
	private static function addWpmlPrefix( $withWpmlPrefix ) {
		return Str::concat( $withWpmlPrefix ? 'tax_' : '' );
	}

	/**
	 * @param  string $taxonomy
	 *
	 * @return bool
	 */
	public static function isTranslatable( $taxonomy ) {
		return in_array( $taxonomy, self::getTranslatable(), true );
	}
}
