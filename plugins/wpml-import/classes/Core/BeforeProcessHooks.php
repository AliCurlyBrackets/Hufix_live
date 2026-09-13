<?php

namespace WPML\Import\Core;

use WPML\Import\Fields;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class BeforeProcessHooks implements \IWPML_Backend_Action, \IWPML_REST_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_exclude_post_from_auto_translate', 10, 2 )
			->then( spreadArgs( [ self::class, 'blockAutoTranslationForImportedPost' ] ) );
	}

	/**
	 * @param bool $isExcluded
	 * @param int  $postId
	 *
	 * @return bool
	 */
	public static function blockAutoTranslationForImportedPost( $isExcluded, $postId ) {
		if ( ! $isExcluded ) {
			return '' !== (string) get_post_meta( $postId, Fields::TRANSLATION_GROUP, true );
		}

		return $isExcluded;
	}
}
