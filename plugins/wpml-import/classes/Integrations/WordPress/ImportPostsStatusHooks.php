<?php

namespace WPML\Import\Integrations\WordPress;

use WPML\Import\Fields;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\StatusManagement;
use function WPML\FP\spreadArgs;

class ImportPostsStatusHooks extends StatusManagement {

	public function add_hooks() {
		Hooks::onFilter( 'wp_import_post_data_raw' )
			->then( spreadArgs( [ $this, 'forceInvisibleStatus' ] ) );
	}

	/**
	 * @param  array $post
	 *
	 * @return array
	 */
	public function forceInvisibleStatus( $post ) {
		$postStatus = $post['status'] ?? 'publish';
		if ( $this->isAlreadyInvisible( $postStatus ) ) {
			return $post;
		}

		$postMeta = $post['postmeta'] ?? [];
		if (
			$this->hasStatusField( $postMeta )
			|| ! $this->hasLanguageField( $postMeta )
		) {
			return $post;
		}

		$postType = $post['post_type'] ?? false;
		if (
			false !== $postType
			&& $this->skipSetPostTypeInvisible( $postType )
		) {
			return $post;
		}

		$postMeta[] = [
			'key'   => Fields::FINAL_POST_STATUS,
			'value' => $postStatus,
		];

		$post['status']   = self::INVISIBLE_POST_STATUS;
		$post['postmeta'] = $postMeta;

		return $post;
	}

	/**
	 * @param  string $field
	 * @param  array  $postMeta
	 *
	 * @return bool
	 */
	protected function hasField( $field, $postMeta ) {
		return (bool) Lst::find(
			function ( $item ) use ( $field ) {
				return $field === $item['key'];
			},
			$postMeta
		);
	}
}
