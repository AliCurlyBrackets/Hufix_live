<?php

namespace WPML\Import\Integrations\WordPress;

use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\Languages;
use WPML\Import\Integrations\Base\Strategies\Generate\QueriedObject;

use function WPML\FP\spreadArgs;

class ExportTermsHooks extends \WPML\Import\Integrations\Base\Strategies\Generate\ExportTermsHooks {
	use Languages;
	use QueriedObject;

	const META_FIELDS_DOING = 'doing';
	const META_FIELDS_DONE  = 'done';

	/**
	 * @var string|null
	 */
	private $metaFieldsStatus;

	/**
	 * @var bool
	 */
	private $skipNextQueryFilterCallback = false;

	public function add_hooks() {
		Hooks::onFilter( 'get_terms_args' )->then( spreadArgs( [ $this, 'includeAllLanguagesInQuery' ] ) );
		Hooks::onFilter( 'query' )->then( spreadArgs( [ $this, 'setTermMetaFields' ] ) );
		parent::add_hooks();
	}

	private function areMetaFieldsDone() {
		return self::META_FIELDS_DONE === $this->metaFieldsStatus;
	}

	private function setDoingMetaFields() {
		$this->metaFieldsStatus = self::META_FIELDS_DOING;
	}

	private function setMetaFieldsMaybeDone() {
		if ( self::META_FIELDS_DOING === $this->metaFieldsStatus ) {
			$this->metaFieldsStatus = self::META_FIELDS_DONE;
		}
	}

	/**
	 * @return bool
	 */
	private function shouldApplyQueryFilter() {
		if ( $this->skipNextQueryFilterCallback ) {
			return false;
		}
		if ( $this->areMetaFieldsDone() ) {
			return false;
		}
		return true;
	}

	/**
	 * All terms are processed one after the other so we should be able to detect when we are done
	 *
	 * @param  string $query
	 *
	 * @return string
	 */
	public function setTermMetaFields( $query ) {
		if ( ! $this->shouldApplyQueryFilter() ) {
			return $query;
		}
		if ( $this->isMetaQuery( $query ) ) {
			$this->skipNextQueryFilterCallback = true;
			$this->setMetaFields( $this->getQueriedTerm( $query ) );
			$this->skipNextQueryFilterCallback = false;
		} else {
			$this->setMetaFieldsMaybeDone();
		}
		return $query;
	}

	/**
	 * @return string
	 */
	protected function getQuerySignature() {
		return "SELECT * FROM {$this->wpdb->termmeta} WHERE term_id = ";
	}

	/**
	 * @param \WP_Term|null $term
	 */
	public function setMetaFields( $term ) {
		if ( ! $term ) {
			return;
		}
		if ( ! $this->taxonomies->isTranslatable( $term->taxonomy ) ) {
			return;
		}

		$this->setDoingMetaFields();

		parent::setMetaFields( $term );
	}
}
