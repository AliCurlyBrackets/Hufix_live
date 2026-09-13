<?php

namespace WPML\Import\Helper;

class Language {

	/**
	 * @param string   $langCode
	 * @param callable $callback
	 *
	 * @return mixed
	 */
	public static function switchAndRun( $langCode, callable $callback ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		$tempSwitchLang = new \WPML_Temporary_Switch_Language( $sitepress, $langCode );
		$value          = $callback();
		$tempSwitchLang->restore_lang();

		return $value;
	}
}
