<?php

namespace GFML\Entries;

use GFForms;
use GFFormsModel;
use WPML\FP\Obj;

class FormConditions implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \GFML_TM_API $tmApi */
	protected $tmApi;

	/**
	 * @param \GFML_TM_API $tmApi
	 */
	public function __construct( \GFML_TM_API $tmApi ) {
		$this->tmApi = $tmApi;
	}

	public function add_hooks() {
		add_filter( 'gform_form_post_get_meta', [ $this, 'translateConditions' ] );
	}

	/**
	 * @param array $form
	 *
	 * @return array
	 */
	public function translateConditions( $form ) {
		$page = GFForms::get_page();
		if ( ! $page || ! in_array( $page, [ 'entry_detail' ], true ) ) {
			return $form;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$entryId = absint( Obj::propOr( Obj::propOr( 0, 'lid', $_GET ), 'entry_id', $_POST ) );
		if ( 0 === $entryId ) {
			return $form;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$entry         = $this->tmApi->getEntry( $entryId );
		$entryLanguage = $this->tmApi->getEntryLanguage( $entry );

		$currentLanguage = apply_filters( 'wpml_current_language', null );

		do_action( 'wpml_switch_language', $entryLanguage );
		$form = $this->tmApi->translate_conditional_logic( $form );
		do_action( 'wpml_switch_language', $currentLanguage );

		return $form;
	}

}
