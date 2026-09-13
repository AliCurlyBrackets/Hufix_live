<?php

namespace WPML\Import\Integrations\WPImportExport;

use WPML\FP\Just;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\FP\Str;
use WPML\Import\Integrations\Base\Fields;
use WPML\Import\Integrations\Base\Languages;

use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class PrepareFieldsHooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {
	use Fields;
	use Languages;

	public function add_hooks() {
		Hooks::onFilter( 'wpie_pre_execute_post_query' )->then( spreadArgs( [ $this, 'includeAllLanguagesInQuery' ] ) );
		Hooks::onFilter( 'wpie_pre_execute_taxonomy_query' )->then( spreadArgs( [ $this, 'includeAllLanguagesInQuery' ] ) );
		Hooks::onFilter( 'wpie_export_fields' )->then( spreadArgs( [ $this, 'registerMetaFields' ] ) );
	}

	/**
	 * The relevant filter is used to initialize and set fields for posts,
	 * but also for terms, comments, users, and eventually any other type.
	 *
	 * We only want to offer and include fields for posts and terms,
	 * and the default (standard) fields for each object type include at least one prefixed item.
	 *
	 * @param  array $availableData
	 *
	 * @return bool
	 */
	private function canRegisterMetaFields( $availableData ) {
		/**
		 * @var \Closure(array):bool $hasFieldTypeStartingWithPostOrTerm
		 */
		$hasFieldTypeStartingWithPostOrTerm = pipe(
			Obj::prop( 'type' ),
			Logic::anyPass(
				[
					Str::startsWith( 'post_' ),
					Str::startsWith( 'term_' ),
				]
			)
		);

		return (bool) wpml_collect( (array) Obj::prop( 'data', Obj::prop( 'standard', $availableData ) ) )
			->first( $hasFieldTypeStartingWithPostOrTerm );
	}

	/**
	 * Offer WPML import fields only for export types that can have them: posts and terms.
	 *
	 * Include the fields in the following groups:
	 * - standard: the ones that get included by default.
	 * - meta: list of post/term meta keys to offer when manually selecting fields.
	 *
	 * As a result, in the page to select fields to export, our fields will appear under two groups:
	 * the list of standard fields and the list of available meta fields.
	 *
	 * @param  array $availableData
	 *
	 * @return array
	 */
	public function registerMetaFields( $availableData ) {
		if ( ! $this->canRegisterMetaFields( $availableData ) ) {
			return $availableData;
		}

		/**
		 * @param array  $availableData
		 * @param string $group
		 *
		 * @return array
		 */
		$initGroupArray = function ( $availableData, $group ) {
			if (
				! array_key_exists( $group, $availableData )
				|| ! is_array( $availableData[ $group ] )
			) {
				$availableData[ $group ] = [];
			}
			if ( ! array_key_exists( 'data', $availableData[ $group ] ) ) {
				$availableData[ $group ]['data'] = [];
			}

			return $availableData;
		};

		/**
		 * @param string $group
		 *
		 * @return \Closure(array):array
		 */
		$addFieldDefinitionsToGroup = function ( $group ) use ( $initGroupArray ) {
			return function ( $availableData ) use ( $group, $initGroupArray ) {
				$availableData = $initGroupArray( $availableData, $group );

				foreach ( $this->getImportFields() as $field ) {
					$availableData[ $group ]['data'][] = [
						'name'      => $field,
						'type'      => 'wpie_cf',
						'metaKey'   => $field,
						'isDefault' => true,
					];
				}

				return $availableData;
			};
		};

		return Just::of( $availableData )
			->map( $addFieldDefinitionsToGroup( 'standard' ) )
			->map( $addFieldDefinitionsToGroup( 'meta' ) )
			->get();
	}
}
