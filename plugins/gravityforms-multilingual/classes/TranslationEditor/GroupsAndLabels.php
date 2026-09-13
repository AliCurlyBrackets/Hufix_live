<?php

namespace GFML\TranslationEditor;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\FP\Obj;

class GroupsAndLabels implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	use JobTrait;
	use TopLevelGroupTrait;

	const TOP_LEVEL_GROUP      = 'Gravity Forms';
	const TOP_LEVEL_GROUP_SLUG = 'gravity-forms';

	const FIELDS_GROUP        = 'Field';
	const FIELDS_GROUP_PREFIX = 'gf-field-';

	const PAGINATION_GROUP_PREFIX = 'gf-pagination-';

	const ONE_TIME_LABELS = [
		// See GFML_TM_API::register_global_strings.
		[
			'group'  => [ 'gf-submit-button' => 'Submit Button' ],
			// See GFML_String_Name_Helper::get_form_submit_button.
			'labels' => [ 'form_submit_button' => 'Submit Button' ],
		],
		// See GFML_TM_API::register_global_strings.
		[
			'group'  => [ 'gf-settings' => 'Settings' ],
			'labels' => [
				// See GFML_String_Name_Helper::get_form_title.
				'form_title'                        => 'Form Title',
				// See GFML_String_Name_Helper::get_form_description.
				'form_description'                  => 'Form Description',
				/** @see GFML_String_Name_Helper::getFormCustomRequiredIndicator */
				'form_custom_required_indicator'    => 'Form Custom Required Field Indicator',
				// See GFML_String_Name_Helper::get_form_save_and_continue_later_text.
				'form_save_and_continue_later_text' => 'Form Save & Continue',
			],
		],
		// See GFML_TM_API::register_strings_pagination.
		[
			'group'  => [
				'gf-pagination-last-page' => 'Last Page',
			],
			'labels' => [
				// See GFML_String_Name_Helper::get_form_pagination_last_page_button_text.
				'lastPageButton'              => 'Previous Button Text',
				// See GFML_String_Name_Helper::get_form_pagination_last_page_button_img_url.
				'lastPageButtonImageUrl'      => 'Previous Button Image Url',
				// See GFML_String_Name_Helper::get_form_pagination_completion_text.
				'progressbar_completion_text' => 'Progress Indicator Completion Text',
			],
		],
	];

	// See GFML_TM_API::register_global_strings.
	const PREFIXED_LABELS = [
		// See GFML_TM_API::register_form_notifications.
		[
			'group'  => [ 'gf-notification-%s' => 'Notification' ],
			'labels' => [
				// See GFML_String_Name_Helper::get_form_notification_subject.
				'notification-subject_'       => 'Subject',
				// See GFML_String_Name_Helper::get_form_notification_message.
				'field-notification-message_' => 'Message',
			],
		],
		// See GFML_TM_API::register_form_confirmations.
		[
			'group'  => [ 'gf-confirmation-%s' => 'Confirmation' ],
			'labels' => [
				// See GFML_String_Name_Helper::get_form_confirmation_message.
				'field-confirmation-message_' => 'Message',
				// See GFML_String_Name_Helper::get_form_confirmation_redirect_url.
				'confirmation-redirect_'      => 'Redirect Url',
			],
		],
	];

	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addGroupsAndLabels' ], 10, 2 );
	}

	/**
	 * @param array[]   $fields
	 * @param \stdClass $job
	 *
	 * @return array[]
	 */
	public function addGroupsAndLabels( $fields, $job ) {
		if ( ! $this->isOurJob( $job ) ) {
			return $fields;
		}
		foreach ( $fields as &$field ) {
			$field = $this->processField( $field );
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function processField( $field ) {
		$fieldTitle = (string) Obj::prop( 'title', $field );

		/**
		 * @param array $item
		 *
		 * @uses $fieldTitle
		 *
		 * @return bool
		 */
		$oneTimeLabelsMatch = function( $item ) use ( $fieldTitle ) {
			return array_key_exists( $fieldTitle, $item['labels'] );
		};
		$oneTimeLabels      = Lst::find( $oneTimeLabelsMatch, self::ONE_TIME_LABELS );
		if ( $oneTimeLabels ) {
			$field['group'] = $this->addTopLevelGroup( Obj::prop( 'group', $oneTimeLabels ) );
			$field['title'] = Obj::path( [ 'labels', $fieldTitle ], $oneTimeLabels );
			return $field;
		}

		/**
		 * @param array $item
		 *
		 * @uses $fieldTitle
		 *
		 * @return mixed
		 */
		$prefixedLabelsMatch = function( $item ) use ( $fieldTitle ) {
			return wpml_collect( $item['labels'] )
				->keys()
				->first( Str::startsWith( Fns::__, $fieldTitle ) );
		};

		/**
		 * @param array  $groups
		 * @param string $name
		 *
		 * @return array
		 */
		$applyGroupIdsPlaceholder = function( $groups, $name ) {
			return Lst::zipObj(
				wpml_collect( array_keys( $groups ) )
					->map( Str::replace( '%s', $name ) )
					->toArray(),
				array_values( $groups )
			);
		};

		$prefixedLabels = Lst::find( $prefixedLabelsMatch, self::PREFIXED_LABELS );
		if ( $prefixedLabels ) {
			$prefixedLabel  = wpml_collect( $prefixedLabels['labels'] )
				->keys()
				->first( Str::startsWith( Fns::__, $fieldTitle ) );
			$fieldName      = sanitize_title( Str::replace( $prefixedLabel, '', $fieldTitle ) );
			$field['group'] = $this->addTopLevelGroup( $applyGroupIdsPlaceholder( $prefixedLabels['group'], $fieldName ) );
			$field['title'] = Obj::path( [ 'labels', $prefixedLabel ], $prefixedLabels );
			return $field;
		}

		// See GFML_TM_API::register_strings_common_fields.
		// See GFML_String_Name_Helper::get_field_common.
		$matchStandardFieldsLabels = Str::match( '/([^-]+)-(\d+)-(.+)/' );

		// Standard fields.
		if ( $matchStandardFieldsLabels( $fieldTitle ) ) {
			list( , $fieldType, $fieldId, $fieldData ) = $matchStandardFieldsLabels( $fieldTitle );
			$field                                     = $this->handleStandardFieldsLabels( $field, $fieldType, $fieldId, $fieldData );
			return $field;
		}

		$field = apply_filters( 'wpml_gf_generic_field_group_and_label', $field );

		return $field;
	}

	/**
	 * @param array  $field
	 * @param string $fieldType
	 * @param int    $fieldId
	 * @param string $fieldData
	 *
	 * @return array
	 */
	private function handleStandardFieldsLabels( $field, $fieldType, $fieldId, $fieldData ) {
		if ( 'page' === $fieldType ) {
			// See GFML_TM_API::register_strings_field_page.
			// See GFML_String_Name_Helper::get_field_page_nextButton.
			// See GFML_String_Name_Helper::get_field_page_previousButton.
			return $this->handlePaginationFieldsLabels( $field, $fieldId, $fieldData );
		}

		$field['group'] = $this->addTopLevelGroup(
			[ self::FIELDS_GROUP_PREFIX . $fieldId => self::FIELDS_GROUP ]
		);

		if ( Str::startsWith( 'customLabel', $fieldData ) ) {
			return $this->handleCustomLabels( $field );
		}

		if ( Str::startsWith( 'placeholder', $fieldData ) ) {
			return $this->handlePlaceholderLabels( $field );
		}

		if ( Str::startsWith( 'choice', $fieldData ) ) {
			// See GFML_TM_API::register_strings_field_choices.
			// See GFML_String_Name_Helper::get_field_multi_input_choice_text.
			// See GFML_String_Name_Helper::get_field_multi_input_choice_value.
			return $this->handleChoiceLabels( $field, $fieldId, $fieldData );
		}

		$field['title'] = apply_filters( 'wpml_labelize_string', $fieldData, 'TranslationJob' );
		return $field;
	}

	/**
	 * @param array  $field
	 * @param int    $fieldId
	 * @param string $fieldData
	 *
	 * @return array
	 */
	private function handlePaginationFieldsLabels( $field, $fieldId, $fieldData ) {
		if ( 'title' === $fieldData ) {
			$field['group'] = $this->addTopLevelGroup(
				[ self::PAGINATION_GROUP_PREFIX . 'page-title' => 'Progress Indicator Page Name' ]
			);
			$field['title'] = 'Page ' . $fieldId;
			return $field;
		}

		$field['group']   = $this->addTopLevelGroup(
			[ self::PAGINATION_GROUP_PREFIX . $fieldId => 'Page Break' ]
		);
		$fieldTitlePieces = explode( '-', $fieldData );
		$field['title']   = wpml_collect( $fieldTitlePieces )
			->map(
				function( $piece ) {
					return apply_filters( 'wpml_labelize_string', $piece, 'TranslationJob' );
				}
			)
			->implode( ' ' );
		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function handleCustomLabels( $field ) {
		$field['title'] = 'Custom Label';
		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function handlePlaceholderLabels( $field ) {
		$field['title'] = 'Placeholder';
		return $field;
	}

	/**
	 * @param array  $field
	 * @param int    $fieldId
	 * @param string $fieldData
	 *
	 * @return array
	 */
	private function handleChoiceLabels( $field, $fieldId, $fieldData ) {
		// See GFML_TM_API::register_strings_field_choices.
		// See GFML_String_Name_Helper::get_field_multi_input_choice_text.
		// See GFML_String_Name_Helper::get_field_multi_input_choice_value.
		$fieldOption           = Str::match( '/choice-(\d+)/' );
		list( , $optionOrder ) = $fieldOption( $fieldData );
		$field['title']        = sprintf(
			'Option #%d %s',
			intval( $optionOrder ) + 1,
			Str::endsWith( '-value', $fieldData ) ? 'Value' : 'Label'
		);
		return $field;
	}

}
