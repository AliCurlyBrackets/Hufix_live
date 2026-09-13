<?php

namespace WPML\Import\Integrations\Base;

use WPML\Import\Fields;

abstract class StatusManagement implements \IWPML_Backend_Action {

	const INVISIBLE_POST_STATUS = 'draft';
	const INVISIBLE_POST_STATI  = [
		'draft',
		'pending',
		'auto-draft',
		'trash',
		'inherit',
	];

	abstract public function add_hooks();

	/**
	 * @param int          $id
	 * @param string|false $postStatus
	 * @param array        $data
	 */
	protected function maybeSetPostInvisible( $id, $postStatus, $data ) {
		if (
			false === $postStatus
			|| $this->isAlreadyInvisible( $postStatus )
		) {
			return;
		}

		if (
			$this->hasStatusField( $data )
			|| ! $this->hasLanguageField( $data )
		) {
			return;
		}

		if ( $this->skipSetPostInvisible( $id ) ) {
			return;
		}

		$postType = get_post_type( $id );
		if (
			false !== $postType
			&& $this->skipSetPostTypeInvisible( $postType )
		) {
			return;
		}

		wp_update_post(
			[
				'ID'          => $id,
				'post_status' => self::INVISIBLE_POST_STATUS,
			]
		);
		update_post_meta( $id, Fields::FINAL_POST_STATUS, $postStatus );
	}

	/**
	 * @param  string $postStatus
	 *
	 * @return bool
	 */
	protected function isAlreadyInvisible( $postStatus ) {
		return in_array( $postStatus, self::INVISIBLE_POST_STATI, true );
	}

	/**
	 * @param  array $data
	 *
	 * @return bool
	 */
	protected function hasLanguageField( $data ) {
		return $this->hasField( Fields::LANGUAGE_CODE, $data );
	}

	/**
	 * @param  array $data
	 *
	 * @return bool
	 */
	protected function hasStatusField( $data ) {
		return $this->hasField( Fields::FINAL_POST_STATUS, $data );
	}

	/**
	 * @param  string $field
	 * @param  array  $data
	 *
	 * @return bool
	 */
	abstract protected function hasField( $field, $data );

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	protected function skipSetPostInvisible( $id ) {
		$skip = false;

		/**
		 * Short-circuit the mechanism to automatically manage imported posts status,
		 * based on the post ID.
		 *
		 * @param bool $skip
		 * @param int  $id
		 *
		 * @return bool
		 */
		return apply_filters( 'wpml_import_skip_set_post_invisible', $skip, $id );
	}

	/**
	 * @param  string $postType
	 *
	 * @return bool
	 */
	protected function skipSetPostTypeInvisible( $postType ) {
		$skip = false;

		/**
		 * Short-circuit the mechanism to automatically manage imported posts status,
		 * based on the post type.
		 *
		 * Useful when dealing with WooCommerce-related addons:
		 * turning variations into drafts or private posts messes up variable products stock management.
		 *
		 * @param bool   $skip
		 * @param string $postType
		 *
		 * @return bool
		 */
		return apply_filters( 'wpml_import_skip_set_post_type_invisible', $skip, $postType );
	}

	/**
	 * @return array
	 */
	protected static function getPostTypes() {
		return get_post_types( '', 'names' );
	}
}
