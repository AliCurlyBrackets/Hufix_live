<?php

namespace GFML\RequiredFields;

use GFForms;
use WPML\FP\Obj;

class CustomIndicator implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const REQUIRED_INDICATOR_TYPE         = 'requiredIndicator';
	const REQUIRED_INDICATOR_CUSTOM_VALUE = 'customRequiredIndicator';

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
		add_filter( 'gform_form_post_get_meta', [ $this, 'translate' ] );
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

		if ( self::hasCustomRequiredIndicator( $form ) ) {
			$form[ self::REQUIRED_INDICATOR_CUSTOM_VALUE ] = icl_t(
				$this->tmApi->get_st_context( $form['id'] ),
				$this->snh->getFormCustomRequiredIndicator(),
				$form[ self::REQUIRED_INDICATOR_CUSTOM_VALUE ]
			);
		}

		return $form;
	}

	/**
	 * @param array $form
	 *
	 * @return bool
	 */
	public static function hasCustomRequiredIndicator( $form ) {
		if (
			'custom' === Obj::prop( self::REQUIRED_INDICATOR_TYPE, $form )
			&& Obj::prop( self::REQUIRED_INDICATOR_CUSTOM_VALUE, $form )
		) {
			return true;
		}
		return false;
	}

}
