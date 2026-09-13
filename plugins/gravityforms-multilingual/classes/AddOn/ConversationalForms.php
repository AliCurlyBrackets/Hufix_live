<?php

namespace GFML\AddOn;

use GFForms;
use GFML\TranslationEditor\TopLevelGroupTrait;
use WPML\FP\Obj;

class ConversationalForms implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	use TopLevelGroupTrait;

	const STRING_NAME = 'form_conversational_continue_button_text';

	const TRANSLATION_GROUP_SLUG = 'conversational-forms';
	const TRANSLATION_GROUP      = 'Conversational Forms';
	const TRANSLATION_TITLE      = 'Continue Button Text';

	/** @var \GFML_TM_API $tmApi */
	protected $tmApi;

	/**
	 * @param \GFML_TM_API $tmApi
	 */
	public function __construct( \GFML_TM_API $tmApi ) {
		$this->tmApi = $tmApi;
	}

	public function add_hooks() {
		add_filter( 'gform_form_update_meta', [ $this, 'register' ], 10, 3 );
		add_filter( 'wpml_gf_generic_field_group_and_label', [ $this, 'addGroupAndLabel' ] );
		add_filter( 'gform_form_post_get_meta', [ $this, 'translate' ] );
	}

	/**
	 * Theme layers, AKA properly coded addons, save their settings page without triggering the 'gform_after_save_form' action.
	 * They only update the related form meta using GFFormsModel::update_form_meta. So we need a spwecific hook for those.
	 *
	 * @param array  $form
	 * @param int    $formId
	 * @param string $metaName
	 *
	 * @return array
	 *
	 * @see GFAddOn::save_form_settings
	 */
	public function register( $form, $formId, $metaName ) {
		if ( 'display_meta' !== $metaName ) {
			return $form;
		}

		$continueButtonText = Obj::path( [ 'gf_theme_layers', 'continue_button_text' ], $form );
		if ( $continueButtonText ) {
			$package = $this->tmApi->get_form_package( $form );
			$this->tmApi->register_gf_string(
				$continueButtonText,
				self::STRING_NAME,
				$package,
				sprintf( '%s %s', self::TRANSLATION_GROUP, self::TRANSLATION_TITLE )
			);
		}

		return $form;
	}

	/**
	 * @param array $field
	 *
	 * return array
	 */
	public function addGroupAndLabel( $field ) {
		if ( self::STRING_NAME !== $field['title'] ) {
			return $field;
		}

		$field['group'] = $this->addTopLevelGroup(
			[
				self::TRANSLATION_GROUP_SLUG => self::TRANSLATION_GROUP,
			]
		);
		$field['title'] = self::TRANSLATION_TITLE;

		return $field;
	}

	/**
	 * @param array $form
	 *
	 * @return array
	 */
	public function translate( $form ) {
		$page = GFForms::get_page();

		if ( $page && in_array( $page, [ 'new_form', 'form_editor' ], true ) ) {
			return $form;
		}

		$continueButtonText = Obj::path( [ 'gf_theme_layers', 'continue_button_text' ], $form );
		if ( $continueButtonText ) {
			$form['gf_theme_layers']['continue_button_text'] = icl_t(
				$this->tmApi->get_st_context( $form['id'] ),
				self::STRING_NAME,
				$continueButtonText
			);
		}

		return $form;
	}

}
