<?php

namespace WPML\Forms\WPForms\Addons;

class WPFormsUserRegistration {

	public function addHooks() {
		add_filter( 'wpforms_user_registration_process_base_get_data', [ $this, 'apply_user_registration_locate' ] );
	}

	/**
	 * @param array $user_data User data.
	 */
	public function apply_user_registration_locate( $user_data ) {
		$languages = apply_filters( 'wpml_active_languages', null, [
			'skip_missing' => 0,
		] );

		$current_language = apply_filters( 'wpml_current_language', null );

		$locale = $languages[ $current_language ]['default_locale'] ?? null;

		if ( $locale ) {
			$user_data['locale'] = $locale;
		}

		return $user_data;
	}
}