<?php

namespace GFML\Entries;

use WPML\FP\Obj;
use WPML_String_Functions;

class FieldValuesHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \GFML_TM_API $tmApi */
	protected $tmApi;

	/** @var \GFML_String_Name_Helper $snh */
	protected $snh;

	/**
	 * @param \GFML_TM_API             $tmApi
	 * @param \GFML_String_Name_Helper $snh
	 */
	public function __construct( \GFML_TM_API $tmApi, \GFML_String_Name_Helper $snh ) {
		$this->tmApi = $tmApi;
		$this->snh   = $snh;
	}

	public function add_hooks() {
		add_action( 'gform_entries_column_filter', [ $this, 'columnValue' ], 10, 4 );
		add_action( 'gform_export_field_value', [ $this, 'exportValue' ], 10, 4 );
	}

	/**
	 * The hook signature for $entry states that it is an object; however, GF passes an array.
	 *
	 * @param mixed  $value
	 * @param int    $formId
	 * @param string $fieldId
	 * @param array  $entry
	 */
	public function columnValue( $value, $formId, $fieldId, $entry ) {
		$field = $this->getField( $value, $formId, $fieldId, $entry );
		if ( null === $field ) {
			return $value;
		}

		if ( null !== $this->getMatchingChoice( $formId, $fieldId, $field, $entry ) ) {
			$value = "<i class='fa fa-check gf_valid'></i>";
		}

		return $value;
	}

	/**
	 * The hook signature for $entry states that it is an object; however, GF passes an array.
	 *
	 * @param mixed  $value
	 * @param int    $formId
	 * @param string $fieldId
	 * @param array  $entry
	 */
	public function exportValue( $value, $formId, $fieldId, $entry ) {
		$field = $this->getField( $value, $formId, $fieldId, $entry );
		if ( null === $field ) {
			return $value;
		}

		$matchingChoiceValue = $this->getMatchingChoice( $formId, $fieldId, $field, $entry );
		if ( null !== $matchingChoiceValue ) {
			return $matchingChoiceValue;
		}

		return $value;
	}

	/**
	 * @param mixed  $value
	 * @param int    $formId
	 * @param string $fieldId
	 * @param array  $entry
	 *
	 * @return \GF_Field|null
	 */
	private function getField( $value, $formId, $fieldId, $entry ) {
		if ( ! empty( $value ) ) {
			return null;
		}

		// We only need to adjust choice values, which have an ID of the shape X.Y.
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( absint( $fieldId ) == $fieldId ) {
			return null;
		}

		if ( $this->tmApi->getEntryLanguage( $entry ) === apply_filters( 'wpml_default_language', null ) ) {
			return null;
		}

		$form  = \GFAPI::get_form( $formId );
		$field = \GFAPI::get_field( $form, $fieldId );
		if ( false === $field ) {
			return null;
		}
		if ( 'checkbox' !== $field->type ) {
			// We only need to adjustb checkboxes choices values.
			return null;
		}

		return $field;
	}

	/**
	 *
	 * @param int       $formId
	 * @param string    $fieldId
	 * @param \GF_Field $field
	 * @param array     $entry
	 *
	 * @return string|null
	 *
	 * @see GF_Field_Checkbox::is_checkbox_checked
	 */
	private function getMatchingChoice( $formId, $fieldId, $field, $entry ) {
		$this->snh->field   = $field;
		$entryFieldValue    = Obj::propOr( '', $fieldId, $entry );
		$entryLanguage      = $this->tmApi->getEntryLanguage( $entry );
		$translationContext = $this->tmApi->get_st_context( $formId );

		foreach ( $field->choices as $index => $choice ) {
			$this->snh->field_choice       = $choice;
			$this->snh->field_choice_index = $index;
			$hasTranslation                = null;
			$choiceLabelTranslated         = icl_t(
				$translationContext,
				$this->snh->get_field_multi_input_choice_text(),
				$choice['text'],
				$hasTranslation,
				false,
				$entryLanguage
			);
			if ( $choiceLabelTranslated === $entryFieldValue ) {
				return $entryFieldValue;
			}

			if ( WPML_String_Functions::is_not_translatable( $choice['value'] ) ) {
				$choiceValueTranslated = $choice['value'];
			} else {
				$choiceValueTranslated = icl_t(
					$translationContext,
					$this->snh->get_field_multi_input_choice_value(),
					$choice['value'],
					$hasTranslation,
					false,
					$entryLanguage
				);
			}
			if ( $choiceValueTranslated === $entryFieldValue ) {
				return $entryFieldValue;
			}

			if ( $field->enablePrice ) {
				// TODO Check how this works for this WooCommerce (?) integration (?).
				// We are leaving it by now: it should work as it did before, so no harm.
				$ary   = explode( '|', $entryFieldValue );
				$val   = $ary[0] ?? '';

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if ( $val == $choice['value'] ) {
					return $val;
				}
			}
		}

		return null;
	}

}
