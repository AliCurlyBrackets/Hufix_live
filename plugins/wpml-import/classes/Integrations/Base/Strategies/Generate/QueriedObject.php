<?php

namespace WPML\Import\Integrations\Base\Strategies\Generate;

use WPML\FP\Str;

trait QueriedObject {

	/**
	 * @return string
	 */
	abstract protected function getQuerySignature();

	/**
	 * @param  string $query
	 *
	 * @return bool
	 */
	private function isMetaQuery( $query ) {
		return (bool) Str::startsWith( $this->getQuerySignature(), $query );
	}

	/**
	 * @param  string $query
	 *
	 * @return int
	 */
	private function getQueriedObjectId( $query ) {
		return (int) trim( Str::replace( $this->getQuerySignature(), '', $query ) );
	}

	/**
	 * @param  string $query
	 *
	 * @return \WP_Post|null
	 */
	private function getQueriedPost( $query ) {
		return get_post( $this->getQueriedObjectId( $query ) );
	}

	/**
	 * @param  string $query
	 *
	 * @return \WP_Term|null
	 */
	private function getQueriedTerm( $query ) {
		return get_term( $this->getQueriedObjectId( $query ) );
	}
}
