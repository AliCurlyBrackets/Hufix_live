<?php

namespace WPML\Import\UI;

use WPML\Collect\Support\Collection;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\Import\Fields;

/**
 * @see https://ant.design/components/table
 */
class ExampleData {

	const COLUMN_TITLE = 'title';

	/**
	 * This map is based on icl_get_languages_locales
	 * and the translations were provided by AI.
	 *
	 * @see icl_get_languages_locales
	 */
	const TITLE_MAP = [
		'af'      => 'Item %s', // Afrikaans.
		'ar'      => 'عنصر %s', // Arabic.
		'az'      => 'Maddə %s', // Azerbaijani.
		'be'      => 'Элемент %s', // Belarusian.
		'bg'      => 'Елемент %s', // Bulgarian.
		'bn'      => 'আইটেম %s', // Bengali.
		'bs'      => 'Stavka %s', // Bosnian.
		'ca'      => 'Element %s', // Catalan.
		'cs'      => 'Položka %s', // Czech.
		'cy'      => 'Eitem %s', // Welsh.
		'da'      => 'Vare %s', // Danish.
		'de'      => 'Artikel %s', // German.
		'el'      => 'Στοιχείο %s', // Greek.
		'en'      => 'Item %s', // English.
		'eo'      => 'Ero %s', // Esperanto.
		'es'      => 'Artículo %s', // Spanish.
		'et'      => 'Üksus %s', // Estonian.
		'eu'      => 'Elementua %s', // Basque.
		'fa'      => 'مورد %s', // Persian.
		'fi'      => 'Kohde %s', // Finnish.
		'fo'      => 'Liður %s', // Faroese.
		'fr'      => 'Article %s', // French.
		'ga'      => 'Mír %s', // Irish.
		'gl'      => 'Elemento %s', // Galician.
		'he'      => 'פריט %s', // Hebrew.
		'hi'      => 'आइटम %s', // Hindi.
		'hr'      => 'Stavka %s', // Croatian.
		'hu'      => 'Tétel %s', // Hungarian.
		'hy'      => 'Տարր %s', // Armenian.
		'id'      => 'Item %s', // Indonesian.
		'is'      => 'Liður %s', // Icelandic.
		'it'      => 'Articolo %s', // Italian.
		'ja'      => '項目 %s', // Japanese.
		'ka'      => 'ელემენტი %s', // Georgian.
		'km'      => 'ធាតុ %s', // Khmer.
		'ko'      => '항목 %s', // Korean.
		'ku'      => 'Tişt %s', // Kurdish.
		'lt'      => 'Elementas %s', // Lithuanian.
		'lv'      => 'Vienība %s', // Latvian.
		'mg'      => 'Zavatra %s', // Malagasy.
		'mk'      => 'Ставка %s', // Macedonian.
		'mn'      => 'Зүйл %s', // Mongolian.
		'ms'      => 'Item %s', // Malay.
		'mt'      => 'Oġġett %s', // Maltese.
		'nb'      => 'Vare %s', // Norwegian Bokmål.
		'ne'      => 'वस्तु %s', // Nepali.
		'no'      => 'Vare %s', // Norwegian.
		'nn'      => 'Vare %s', // Norwegian Nynorsk.
		'nl'      => 'Item %s', // Dutch.
		'pa'      => 'ਆਈਟਮ %s', // Punjabi.
		'pl'      => 'Pozycja %s', // Polish.
		'pt-br'   => 'Item %s', // Portuguese (Brazil).
		'pt-pt'   => 'Item %s', // Portuguese (Portugal).
		'qu'      => 'Unuq %s', // Quechua.
		'ro'      => 'Articol %s', // Romanian.
		'ru'      => 'Элемент %s', // Russian.
		'si'      => 'අයිතමය %s', // Sinhalese.
		'sk'      => 'Položka %s', // Slovak.
		'sl'      => 'Element %s', // Slovenian.
		'so'      => 'Shay %s', // Somali.
		'sq'      => 'Artikull %s', // Albanian.
		'sr'      => 'Ставка %s', // Serbian.
		'su'      => 'Item %s', // Sundanese.
		'sv'      => 'Artikel %s', // Swedish.
		'ta'      => 'உருப்படி %s', // Tamil.
		'tg'      => 'Мавод %s', // Tajik.
		'th'      => 'รายการ %s', // Thai.
		'tr'      => 'Öğe %s', // Turkish.
		'ug'      => 'تۈر %s', // Uighur.
		'uk'      => 'Елемент %s', // Ukrainian.
		'ur'      => 'آئٹم %s', // Urdu.
		'uz'      => 'Element %s', // Uzbek.
		'vi'      => 'Mục %s', // Vietnamese.
		'zh-hans' => '项目 %s', // Chinese (Simplified).
		'zh-hant' => '項目 %s', // Chinese (Traditional).
	];


	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function get() {
		return [
			'columns'      => self::getTableColumns(),
			'dataSource'   => self::getTableDataSource(),
			'languageList' => $this->getActiveLanguages( 'display_name' )->implode( ', ' ),
		];
	}

	/**
	 * @return array
	 */
	private function getTableColumns() {
		return wpml_collect(
			[
				'title',
				Fields::TRANSLATION_GROUP,
				Fields::LANGUAGE_CODE,
				Fields::SOURCE_LANGUAGE_CODE,
				Fields::FINAL_POST_STATUS,
			]
		)->map(
			function ( $name ) {
				return (object) [
					'dataIndex'        => $name,
					self::COLUMN_TITLE => $name,
					'fixed'            => 'title' === $name ? 'left' : false,
				];
			}
		)->toArray();
	}

	/**
	 * @return array
	 */
	private function getTableDataSource() {
		$data = [];

		$activeCodes = $this->getActiveLanguages( 'code' )->toArray();

		foreach ( [
			1 => 'A',
			2 => 'B',
		] as $trGroup => $item ) {
			foreach ( $activeCodes as $code ) {
				$data[] = $this->getTableRow( $item, $trGroup, $code );
			}
		}

		return $data;
	}

	/**
	 * @param string     $item
	 * @param string|int $trGroup
	 * @param string     $lang
	 *
	 * @return object
	 */
	private function getTableRow( $item, $trGroup, $lang ) {
		static $key = 0;

		$postTitle = isset( self::TITLE_MAP[ $lang ] )
			? sprintf( self::TITLE_MAP[ $lang ], $item )
			: sprintf( '[%s] Item %s', $lang, $item );

		$defaultLang = $this->sitepress->get_default_language();
		$sourceLang  = $lang === $defaultLang ? null : $defaultLang;

		return (object) [
			'key'                        => $key++,
			self::COLUMN_TITLE           => $postTitle,
			Fields::TRANSLATION_GROUP    => $trGroup,
			Fields::LANGUAGE_CODE        => $lang,
			Fields::SOURCE_LANGUAGE_CODE => $sourceLang,
			Fields::FINAL_POST_STATUS    => 'published',
			'isRTL'                      => $this->sitepress->is_rtl( $lang ),
		];
	}

	/**
	 * @param string $prop
	 *
	 * @return Collection
	 */
	private function getActiveLanguages( $prop ) {
		return wpml_collect( $this->sitepress->get_active_languages() )
			->prioritize( Relation::propEq( 'code', $this->sitepress->get_default_language() ) )
			->map( Obj::prop( $prop ) );
	}
}
