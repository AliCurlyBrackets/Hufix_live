<?php

namespace WPML\Import\Commands;

use WPML\FP\Lst;
use WPML\Import\Fields;


// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
class SetTermsLanguage extends Base\SetEntitiesLanguage {

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Setting Terms’ Language', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Connecting taxonomy terms to the default (original) language and translations.', 'wpml-import' );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int|null $limit
	 *
	 * @return array
	 */
	protected function getPendingItems( $limit = null ) {
		return $this->getResultsWithLimit(
			"
			SELECT
				meta_value AS translation_group,
				tt.term_taxonomy_id AS element_id,
				tt.term_taxonomy_id,
				tm.term_id,
				tt.taxonomy,
				(SELECT meta_value FROM {$this->wpdb->termmeta} WHERE term_id = tm.term_id AND meta_key='" . Fields::LANGUAGE_CODE . "' LIMIT 1) AS language_code,
				(SELECT meta_value FROM {$this->wpdb->termmeta} WHERE term_id = tm.term_id AND meta_key='" . Fields::SOURCE_LANGUAGE_CODE . "' LIMIT 1) AS source_language_code
			FROM {$this->wpdb->termmeta} tm
			LEFT JOIN {$this->wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
			WHERE meta_key = '" . Fields::TRANSLATION_GROUP . "'
				HAVING language_code IS NOT NULL
			ORDER BY meta_value ASC,
				CASE WHEN source_language_code = '' THEN 0 ELSE 1 END
			",
			$limit
		);
	}


	/**
	 * @codeCoverageIgnore
	 *
	 * @param object|null $source
	 * @param object[]    $translations
	 *
	 * @return array
	 */
	protected function getTridAndType( $source, $translations ) {
		$getElementType = function ( $item ) {
			return 'tax_' . $item->taxonomy;
		};

		if ( $source ) {
			$elementType            = $getElementType( $source );
			$elementLanguageDetails = $this->sitepress->get_element_language_details( $source->element_id, $elementType );

			if ( $elementLanguageDetails ) {
				$trid = $elementLanguageDetails->trid;
			}
			if ( ! $elementLanguageDetails || $elementLanguageDetails->language_code !== $source->language_code ) {
				$this->sitepress->set_element_language_details( $source->element_id, $elementType, 0, $source->language_code );
				$trid = $this->sitepress->get_element_trid( $source->element_id, $elementType );
			}
		} else {
			/** @var object $firstTranslation */
			$firstTranslation = reset( $translations );
			$elementType      = $getElementType( $firstTranslation );

			$trid = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"
					SELECT trid FROM {$this->wpdb->prefix}icl_translations AS t
						LEFT JOIN {$this->wpdb->term_taxonomy} AS tt
					    	ON t.element_type = %s
					    		AND t.element_id = tt.term_taxonomy_id
						LEFT JOIN {$this->wpdb->termmeta} AS tm
				    		ON tm.term_id = tt.term_id
					WHERE tm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
						AND tm.meta_value = %s
						AND t.language_code = %s
					ORDER BY t.trid ASC
					LIMIT 1
					",
					$elementType,
					$firstTranslation->translation_group,
					$firstTranslation->source_language_code
				)
			);
		}

		return [ $trid, $elementType ];
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param object[] $itemsGroup
	 *
	 * @return void
	 */
	protected function deleteLanguageFields( $itemsGroup ) {
		$itemIds = Lst::pluck( 'term_id', $itemsGroup );

		// Do not delete '_wpml_import_translation_group' to keep a reference on update.
		$this->wpdb->query(
			"
			DELETE FROM {$this->wpdb->termmeta}
			WHERE term_id IN(" . wpml_prepare_in( $itemIds, '%d' ) . ")
				AND meta_key IN('" . Fields::LANGUAGE_CODE . "','" . Fields::SOURCE_LANGUAGE_CODE . "')
			"
		);
	}
}
// phpcs:enable
