<?php

namespace GFML\AddOn;

use GFML\TranslationEditor\TopLevelGroupTrait;
use WPML\FP\Obj;
use WPML\FP\Str;

class Survey implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	use TopLevelGroupTrait;

	const FIELD_TYPE = 'survey';

	const INPUT_TYPE_LIKERT   = 'likert';
	const INPUT_TYPE_RANK     = 'rank';
	const INPUT_TYPE_RATING   = 'rating';
	const INPUT_TYPE_CHECKBOX = 'checkbox';
	const INPUT_TYPE_RADIO    = 'radio';
	const INPUT_TYPE_SELECT   = 'select';
	const INPUT_TYPE_TEXT     = 'text';
	const INPUT_TYPE_TEXTAREA = 'textarea';

	const LIKERT_ROWS_KEY               = 'gsurveyLikertRows';
	const LIKERT_ROWS_NAME_PREFIX       = 'survey-likert-rows-';
	const LIKERT_TRANSLATION_GROUP_SLUG = 'survey-likert-rows';
	const LIKERT_TRANSLATION_GROUP      = 'Survey Fields: Likert Rows';

	/** @var \GFML_TM_API $tmApi */
	private $tmApi;

	/** @var \GFML_String_Name_Helper $snh */
	private $snh;

	public function __construct( \GFML_TM_API $tmApi, \GFML_String_Name_Helper $snh ) {
		$this->tmApi = $tmApi;
		$this->snh   = $snh;
	}

	public function add_hooks() {
		add_action( 'wpml_gf_register_strings_field_' . self::FIELD_TYPE, [ $this, 'register' ], 10, 3 );
		add_filter( 'wpml_gf_generic_field_group_and_label', [ $this, 'addGroupAndLabel' ] );
		add_filter( 'wpml_gf_translate_strings_field_' . self::FIELD_TYPE, [ $this, 'translate' ], 10, 3 );
	}

	/**
	 * @param \GF_Field $field
	 *
	 * @return bool
	 */
	private function isSurveyField( $field ) {
		return self::FIELD_TYPE === Obj::prop( 'type', $field );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	private function getLikerRowStringName( $value ) {
		return $this->snh->sanitize_string( self::LIKERT_ROWS_NAME_PREFIX . $value );
	}

	/**
	 * @param array     $form
	 * @param \stdClass $package
	 * @param \GF_Field $field
	 */
	public function register( $form, $package, $field ) {
		if ( ! $this->isSurveyField( $field ) ) {
			return;
		}

		$inputType = Obj::prop( 'inputType', $field );

		switch ( $inputType ) {
			case self::INPUT_TYPE_LIKERT:
				$this->registerLikertRows( $package, $field );
				$this->registerFieldChoices( $package, $field );
				break;
			case self::INPUT_TYPE_RANK:
			case self::INPUT_TYPE_RATING:
			case self::INPUT_TYPE_CHECKBOX:
			case self::INPUT_TYPE_RADIO:
			case self::INPUT_TYPE_SELECT:
				$this->registerFieldChoices( $package, $field );
				break;
			case self::INPUT_TYPE_TEXT:
			case self::INPUT_TYPE_TEXTAREA:
			default:
				break;
		}
	}

	/**
	 * @param \stdClass $package
	 * @param \GF_Field $field
	 */
	private function registerFieldChoices( $package, $field ) {
		$this->tmApi->register_strings_field_option( $package, $field, false );
	}

	/**
	 * @param \stdClass $package
	 * @param \GF_Field $field
	 */
	private function registerLikertRows( $package, $field ) {
		$likertRows = Obj::propOr( [], self::LIKERT_ROWS_KEY, $field );
		foreach ( $likertRows as $likertRow ) {
			$value = $likertRow['text'];
			$name  = $this->getLikerRowStringName( $value );
			$this->tmApi->register_gf_string( $value, $name, $package, $name );
		}
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public function addGroupAndLabel( $field ) {
		/** @phpstan-ignore-next-line */
		if ( false === Str::startsWith( self::LIKERT_ROWS_NAME_PREFIX, Obj::prop( 'title', $field ) ) ) {
			return $field;
		}
		$field['group'] = $this->addTopLevelGroup(
			[
				self::LIKERT_TRANSLATION_GROUP_SLUG => self::LIKERT_TRANSLATION_GROUP,
			]
		);
		$field['title'] = apply_filters(
			'wpml_labelize_string',
			Str::replace( self::LIKERT_ROWS_NAME_PREFIX, '', Obj::prop( 'title', $field ) ),
			'TranslationJob'
		);
		return $field;
	}

	/**
	 * @param \GF_Field $field
	 * @param array     $form
	 * @param \stdClass $package
	 *
	 * @return \GF_Field
	 */
	public function translate( $field, $form, $package ) {
		if ( ! $this->isSurveyField( $field ) ) {
			return $field;
		}

		$inputType = Obj::prop( 'inputType', $field );

		switch ( $inputType ) {
			case self::INPUT_TYPE_LIKERT:
				$field = $this->translateLikertRows( $field, $package );
				break;
			default:
				break;
		}

		return $field;
	}

	/**
	 * @param \GF_Field $field
	 * @param \stdClass $package
	 *
	 * @return \GF_Field
	 */
	private function translateLikertRows( $field, $package ) {
		$likertRows = Obj::propOr( [], self::LIKERT_ROWS_KEY, $field );
		if ( empty( $likertRows ) ) {
			return $field;
		}

		foreach ( $likertRows as &$likertRow ) {
			$likertRow['text'] = apply_filters(
				'wpml_translate_string',
				$likertRow['text'],
				$this->getLikerRowStringName( $likertRow['text'] ),
				$package
			);
		}

		$field->{self::LIKERT_ROWS_KEY} = $likertRows;
		return $field;
	}

}
