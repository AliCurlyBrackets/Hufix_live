<?php

namespace WPML\Forms\WPForms\Hooks;

class ChoiceValidation {

	const CHOICE_FIELD_TYPES = [ 'radio', 'select', 'checkbox', 'gdpr-checkbox' ];

	/** @var array<int, array|null> */
	private $originalForms = [];

	public function addHooks() {
		add_filter( 'wpforms_field_choices_allow_unknown_value', [ $this, 'allowOriginalChoiceValues' ], 10, 4 );
		add_filter( 'wpforms_process_filter', [ $this, 'restoreBlankChoiceValues' ], 10, 3 );
	}

	/**
	 * Allow submission of values that match the original (untranslated) configured choices.
	 *
	 * WPForms 1.10.0.5 validates submitted values against an allowlist built from the
	 * current (possibly translated) form data. When WPML switches to a secondary language
	 * during AJAX form processing, the allowlist contains translated labels while the
	 * frontend rendered and submitted the original-language labels — causing false
	 * validation failures.
	 *
	 * @param bool         $allow        Current filter value.
	 * @param string|array $fieldSubmit  Submitted field value.
	 * @param array        $field        Translated field configuration.
	 * @param array        $formData     Full (translated) form data.
	 *
	 * @return bool
	 */
	public function allowOriginalChoiceValues( $allow, $fieldSubmit, array $field, array $formData ) {
		if ( $allow ) {
			return true;
		}

		$formId  = isset( $formData['id'] ) ? (int) $formData['id'] : 0;
		$fieldId = isset( $field['id'] ) ? (int) $field['id'] : 0;

		if ( ! $formId || ! $fieldId ) {
			return $allow;
		}

		$originalField = $this->getOriginalField( $formId, $fieldId );
		if ( ! $originalField ) {
			return $allow;
		}

		return $this->isSubmissionAllowedByOriginalChoices( $fieldSubmit, $originalField );
	}

	/**
	 * Restore choice field values that were blanked by sanitize_choices_submission() due to a
	 * language mismatch between the submitted value and the (translated) form data allowlist.
	 *
	 * WPForms 1.10.0.5 calls sanitize_choices_submission() inside format(), which strips any
	 * submitted value not found in the current (possibly translated) allowlist. When WPML has
	 * switched to a secondary language, the allowlist contains translated labels while the
	 * frontend submitted original-language labels — producing an empty value that is then
	 * skipped by the entry-fields save guard (value === ''). This prevents the submission from
	 * being counted in poll/survey totals even though validation already passed.
	 *
	 * @param array $fields    Formatted field data keyed by field ID.
	 * @param array $entry     Raw submitted entry (fields keyed by field ID).
	 * @param array $formData  Full (translated) form data.
	 *
	 * @return array
	 */
	public function restoreBlankChoiceValues( array $fields, array $entry, array $formData ): array {
		$formId = isset( $formData['id'] ) ? (int) $formData['id'] : 0;
		if ( ! $formId ) {
			return $fields;
		}

		foreach ( $fields as $fieldId => &$field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( ! in_array( $field['type'] ?? '', self::CHOICE_FIELD_TYPES, true ) ) {
				continue;
			}

			if ( ( $field['value'] ?? '' ) !== '' || ( $field['value_raw'] ?? '' ) !== '' ) {
				continue;
			}

			$submitted = $entry['fields'][ $fieldId ] ?? '';
			if ( $this->isEmptyItem( $submitted ) || $submitted === [] ) {
				continue;
			}

			$originalField = $this->getOriginalField( $formId, (int) $fieldId );
			if ( ! $originalField || ! $this->isSubmissionAllowedByOriginalChoices( $submitted, $originalField ) ) {
				continue;
			}

			if ( is_array( $submitted ) ) {
				$items = array_filter( $submitted, [ $this, 'isNonOtherItem' ], ARRAY_FILTER_USE_BOTH );
				$items = array_map( 'sanitize_text_field', array_values( $items ) );

				$field['value']     = implode( "\n", $items );
				$field['value_raw'] = '';
			} else {
				$value = sanitize_text_field( (string) $submitted );

				$field['value']     = $value;
				$field['value_raw'] = $value;
			}
		}
		unset( $field );

		return $fields;
	}

	/**
	 * @param mixed      $item
	 * @param string|int $key
	 *
	 * @return bool
	 */
	private function isNonOtherItem( $item, $key ): bool {
		return $key !== 'other' && ! $this->isEmptyItem( $item );
	}

	/**
	 * Retrieve the original (untranslated) field data straight from the post content.
	 * Results are cached in-memory by form ID to avoid repeated DB reads per field.
	 *
	 * @param int $formId  WPForms form post ID.
	 * @param int $fieldId Field ID within the form.
	 *
	 * @return array|null
	 */
	private function getOriginalField( int $formId, int $fieldId ) {
		if ( ! array_key_exists( $formId, $this->originalForms ) ) {
			$content = get_post_field( 'post_content', $formId, 'raw' );
			$data    = $content ? json_decode( $content, true ) : null;

			$this->originalForms[ $formId ] = is_array( $data ) ? $data : null;
		}

		$data = $this->originalForms[ $formId ];

		if ( ! $data || empty( $data['fields'][ $fieldId ] ) ) {
			return null;
		}

		return $data['fields'][ $fieldId ];
	}

	/**
	 * Return true when every submitted value is present in the original choices allowlist.
	 *
	 * @param string|array $fieldSubmit    Submitted value.
	 * @param array        $originalField  Original field data.
	 *
	 * @return bool
	 */
	private function isSubmissionAllowedByOriginalChoices( $fieldSubmit, array $originalField ): bool {
		if ( empty( $originalField['choices'] ) || ! is_array( $originalField['choices'] ) ) {
			return false;
		}

		$allowlist = $this->buildOriginalAllowlist( $originalField );
		$hasOther  = $this->hasOtherChoice( $originalField );

		if ( is_array( $fieldSubmit ) && array_key_exists( 'other', $fieldSubmit ) ) {
			if ( ! $hasOther ) {
				return false;
			}
			foreach ( $fieldSubmit as $key => $item ) {
				if ( $key === 'other' ) {
					continue;
				}
				if ( ! $this->isEmptyItem( $item ) && ! in_array( trim( (string) $item ), $allowlist, true ) ) {
					return false;
				}
			}
			return true;
		}

		$items = is_array( $fieldSubmit ) ? $fieldSubmit : [ $fieldSubmit ];
		foreach ( $items as $item ) {
			if ( ! $this->isEmptyItem( $item ) && ! in_array( trim( (string) $item ), $allowlist, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build an allowlist of normalized choice values from the original (untranslated) field data.
	 *
	 * Mirrors the logic in WPForms_Field_Base::get_choice_allowlist_value().
	 *
	 * @param array $originalField Original field data.
	 *
	 * @return string[]
	 */
	private function buildOriginalAllowlist( array $originalField ): array {
		$allowlist  = [];
		$showValues = ! empty( $originalField['show_values'] );

		foreach ( $originalField['choices'] as $key => $choice ) {
			if ( $showValues && isset( $choice['value'] ) && $choice['value'] !== '' ) {
				$allowlist[] = trim( $choice['value'] );
			} elseif ( ! $showValues && isset( $choice['label'] ) && $choice['label'] !== '' ) {
				$allowlist[] = trim( $choice['label'] );
			} else {
				// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				$allowlist[] = trim( sprintf( esc_html__( 'Choice %s', 'wpforms-lite' ), $key ) );
			}
		}

		return $allowlist;
	}

	/**
	 * Check whether the original field has at least one choice with the "other" flag.
	 *
	 * @param array $originalField Original field data.
	 *
	 * @return bool
	 */
	private function hasOtherChoice( array $originalField ): bool {
		foreach ( $originalField['choices'] as $choice ) {
			if ( ! empty( $choice['other'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param mixed $item Submitted item.
	 *
	 * @return bool
	 */
	private function isEmptyItem( $item ): bool {
		return $item === '' || $item === null;
	}
}
