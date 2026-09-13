<?php

namespace GFML\TranslationEditor;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\FP\Type;

class FieldsOrder implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	use JobTrait;

	/** @var int[] */
	private $formIdsToSort = [];

	public function add_hooks() {
		add_filter( 'gform_form_update_meta', [ $this, 'checkFieldsOrder' ], 10, 3 );
		add_action( 'wpml_gf_register_strings', [ $this, 'updateTranslationStatus' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_job', [ $this, 'applyFieldsOrder' ], 10, 2 );
	}

	/**
	 * @param array $formMeta
	 *
	 * @return int[]
	 */
	private function extractFieldIds( $formMeta ) {
		return Lst::pluck( 'id', Obj::propOr( [], 'fields', $formMeta ) );
	}

	/**
	 * @param int $formId
	 *
	 * @return int[]
	 */
	private function getFormFieldsOrder( $formId ) {
		$form = \GFAPI::get_form( $formId );
		if ( ! $form ) {
			return [];
		}
		return $this->extractFieldIds( $form );
	}

	/**
	 * Compare the saving fields with the previous stored fields and flag the form if they were reordered.
	 *
	 * @param array      $meta
	 * @param int|string $formId
	 * @param string     $metaName
	 *
	 * @return array
	 */
	public function checkFieldsOrder( $meta, $formId, $metaName ) {
		if ( 'display_meta' !== $metaName ) {
			return $meta;
		}

		// Despite its signature, the filter passes a numeric string.
		$formId = (int) $formId;

		$storedFieldsOrder = $this->getFormFieldsOrder( $formId );
		$savingFieldsOrder = $this->extractFieldIds( $meta );
		if ( $storedFieldsOrder !== $savingFieldsOrder ) {
			$this->formIdsToSort[] = $formId;
		}

		return $meta;
	}

	/**
	 * If the form fields were reordered, flag the package translation as needing update.
	 *
	 * @param array     $form        The Gravity Form data after 'gform_after_save_form'.
	 * @param \stdClass $formPackage The package you can use with register_gf_string().
	 */
	public function updateTranslationStatus( $form, $formPackage ) {
		if ( ! in_array( (int) $form['id'], $this->formIdsToSort, true ) ) {
			return;
		}

		// TODO Depends on https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-2846.
	}

	/**
	 * @param array     $fields
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function applyFieldsOrder( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}

		$formId            = apply_filters( 'wpml_element_id_from_package', null, Obj::prop( 'original_doc_id', $job ) );
		$storedFieldsOrder = $this->getFormFieldsOrder( $formId );

		// Patterns can be a string match, hence we have a suffix stating the field ID to group by, or a check against specific strings, which produce first-level fields.
		$sectionPatterns = [
			// See GroupsAndLabels::handleStandardFieldsLabels.
			'fields'        => Str::match( '/(?:' . GroupsAndLabels::TOP_LEVEL_GROUP_SLUG . '\/)(?:' . GroupsAndLabels::FIELDS_GROUP_PREFIX . '|' . GroupsAndLabels::PAGINATION_GROUP_PREFIX . ')(\d+)/' ),
			// See GroupsAndLabels::ONE_TIME_LABELS.
			'submit'        => Relation::equals( GroupsAndLabels::TOP_LEVEL_GROUP_SLUG . '/gf-submit-button' ),
			// See GroupsAndLabels::ONE_TIME_LABELS.
			'settings'      => Relation::equals( GroupsAndLabels::TOP_LEVEL_GROUP_SLUG . '/gf-settings' ),
			// See GroupsAndLabels::PREFIXED_LABELS.
			'notifications' => Str::match( '/(?:' . GroupsAndLabels::TOP_LEVEL_GROUP_SLUG . '\/gf-notification-)(.+)/' ),
			// See GroupsAndLabels::PREFIXED_LABELS.
			'confirmations' => Str::match( '/(?:' . GroupsAndLabels::TOP_LEVEL_GROUP_SLUG . '\/gf-confirmation-)(.+)/' ),
		];

		$fieldsBySection             = array_fill_keys( array_keys( $sectionPatterns ), [] );
		$fieldsBySection['orphaned'] = [];

		/**
		 * @param array $field
		 *
		 * @return int|null
		 */
		$getGroupId = function( $field ) {
			// See WPML on WPML_TM_Xliff_Writer::get_translation_unit_data.
			$extraData      = Obj::pathOr( '', [ 'attributes', 'extradata' ], $field );
			$fieldExtraData = json_decode( str_replace( '&quot;', '"', $extraData ) );
			if ( null === $fieldExtraData ) {
				return null;
			}

			return Obj::prop( 'group_id', $fieldExtraData );
		};

		/**
		 * @param array $field
		 *
		 * @uses $getGroupId
		 * @uses $sectionPatterns
		 * @uses $fieldsBySection
		 */
		$triageField = function( $field ) use ( $getGroupId, $sectionPatterns, &$fieldsBySection ) {
			$groupId = $getGroupId( $field );

			/**
			 * @param array  $field
			 * @param string $section
			 *
			 * @uses $fieldsBySection
			 */
			$addToSection = function( $field, $section ) use ( &$fieldsBySection ) {
				$fieldsBySection[ $section ][] = $field;
			};

			/**
			 * @param array  $field
			 * @param string $section
			 * @param array  $patternOutcome
			 *
			 * @uses $fieldsBySection
			 */
			$addToSectionByMatchId = function( $field, $section, $patternOutcome ) use ( &$fieldsBySection ) {
				list( , $itemId )                         = $patternOutcome;
				$fieldsBySection[ $section ][ $itemId ]   = $fieldsBySection[ $section ][ $itemId ] ?? [];
				$fieldsBySection[ $section ][ $itemId ][] = $field;
			};

			if ( null === $groupId ) {
				$addToSection( $field, 'orphaned' );
				return;
			}

			foreach ( $sectionPatterns as $section => $pattern ) {
				$patternOutcome = $pattern( $groupId );
				if ( true === $patternOutcome ) {
					// Pattern is Relation::equals so the field is a first-level field in its section.
					$addToSection( $field, $section );
					return;
				}
				if ( Type::isArray( $patternOutcome ) && Relation::gte( Lst::length( $patternOutcome ), 2 ) ) {
					// Pattern is Str::match so the field is assigned to a group based on the field ID.
					$addToSectionByMatchId( $field, $section, $patternOutcome );
					return;
				}
			}

			$addToSection( $field, 'orphaned' );
		};

		wpml_collect( $fields )->each( $triageField );

		// Reorder our form fields matching the stored fields order.
		$relevantStoredFieldsOrder = array_intersect( $storedFieldsOrder, array_keys( $fieldsBySection['fields'] ) );
		$sortedFieldsById          = array_replace_recursive( array_flip( $relevantStoredFieldsOrder ), $fieldsBySection['fields'] );

		// Return in order: form fields, other (orphaned) form items, submit button, settings, notifications, confirmations.
		return Lst::concat(
			Lst::concat(
				Lst::flattenToDepth( 1, $sortedFieldsById ),
				Lst::concat(
					$fieldsBySection['orphaned'],
					$fieldsBySection['submit']
				)
			),
			Lst::concat(
				$fieldsBySection['settings'],
				Lst::concat(
					Lst::flattenToDepth( 1, $fieldsBySection['notifications'] ),
					Lst::flattenToDepth( 1, $fieldsBySection['confirmations'] )
				)
			)
		);
	}

}
