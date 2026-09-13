<?php

namespace GFML\Compatibility\UserRegistration;

use GFSignup;
use SitePress;
use WP_User;
use WPML\FP\Obj;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	/** @var SitePress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'gform_user_registration_signup_meta', [ $this, 'onSubmission' ] );
		add_filter( 'insert_user_meta', [ $this, 'onActivation' ], 10, 3 );
	}

	/**
	 * Save the language the user submitted the form into the entry meta data.
	 *
	 * @param array $meta Entry meta data.
	 * @return array
	 */
	public function onSubmission( $meta ) {
		$meta['icl_admin_language'] = $this->sitepress->get_current_language();
		$meta['icl_admin_locale']   = $this->sitepress->get_locale_from_language_code( $meta['icl_admin_language'] );
		return $meta;
	}

	/**
	 * Set the user locale and preferred language.
	 *
	 * @param array   $meta User meta data.
	 * @param WP_User $user
	 * @param bool    $update
	 * @return array
	 */
	public function onActivation( $meta, WP_User $user, $update ) {
		$getKeys = function() {
			$key = rgpost( 'key' );
			if ( $key ) {
				// From ajax activation.
				return [ $key ];
			}
			$key = rgpost( 'item' );
			if ( $key ) {
				// From form submission activation.
				return [ $key ];
			}
			$key = rgget( 'gfur_activation' );
			if ( $key ) {
				// From frontend user self-activation.
				return [ $key ];
			}
			$keys = rgpost( 'items' );
			if ( $keys ) {
				// From bulk backend activation.
				return $keys;
			}
			return [];
		};

		if ( ! $update && class_exists( 'GFSignup' ) ) {
			$keys = $getKeys();
			foreach ( $keys as $key ) {
				$signup = GFSignup::get( $key );
				if (
					$signup instanceof GFSignup
					&& $signup->meta['email'] === $user->user_email
					&& Obj::prop( 'icl_admin_language', $signup->meta )
					&& Obj::prop( 'icl_admin_locale', $signup->meta )
				) {
					$meta['icl_admin_language'] = $signup->meta['icl_admin_language'];
					$meta['locale']             = $signup->meta['icl_admin_locale'];
					do_action( 'wpml_switch_language_for_email', $user->user_email );
				}
			}
		}

		return $meta;
	}
}
