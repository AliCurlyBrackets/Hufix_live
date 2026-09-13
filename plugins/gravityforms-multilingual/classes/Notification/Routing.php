<?php

namespace GFML\Notification;

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\FP;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\FP\Type;
use function WPML\FP\pipe;

class Routing {

	/** @var array */
	private $form;

	/** @var array */
	private $routingFields = [];

	/**
	 * @param array $form
	 */
	public function __construct( $form ) {
		$this->form = $form;
		$this->registerRoutingFields( $form );
	}

	private function registerRoutingFields( $form ) {
		Logic::ifElse(
			pipe(
				Obj::prop( 'notifications' ),
				Type::isArray()
			),
			pipe(
				Obj::prop( 'notifications' ),
				Fns::each(
					Logic::ifElse(
						Logic::allPass(
							[
								pipe(
									Obj::prop( 'toType' ),
									Relation::equals( 'routing' )
								),
								pipe(
									Obj::prop( 'routing' ),
									Logic::allPass(
										[
											Type::isArray(),
											Logic::isNotEmpty(),
										]
									)
								),
							]
						),
						pipe(
							Obj::prop( 'routing' ),
							Fns::each(
								Logic::ifElse(
									Logic::allPass(
										[
											pipe(
												Obj::prop( 'fieldId' ),
												Logic::isNotEmpty()
											),
											pipe(
												Obj::prop( 'value' ),
												Logic::isNotEmpty()
											),
										]
									),
									function( $routing ) {
										$this->routingFields[ Obj::prop( 'fieldId', $routing ) ][ Obj::prop( 'value', $routing ) ] = Obj::prop( 'value', $routing );
									},
									Fns::identity()
								)
							)
						),
						Fns::identity()
					)
				)
			),
			Fns::identity(),
			$form
		);
	}

	private function hasRoutingFields() {
		return Logic::isNotEmpty( $this->routingFields );
	}

	/**
	 * @param int    $fieldId
	 * @param string $value
	 * @param string $translatedValue
	 */
	public function registerFieldTranslation( $fieldId, $value, $translatedValue ) {
		if ( Obj::path( [ $fieldId, $value ], $this->routingFields ) ) {
			$this->routingFields[ $fieldId ][ $value ] = $translatedValue;
		}
	}

	/**
	 * @param int $fieldId
	 *
	 * @return string|null
	 */
	private function getFieldType( $fieldId ) {
		$field = Lst::find(
			function( $field ) use ( $fieldId ) {
				return Relation::equals( Cast::toStr( $fieldId ), Cast::toStr( Obj::prop( 'id', $field ) ) );
			},
			Obj::propOr( [], 'fields', $this->form )
		);

		return Obj::prop( 'type', $field );
	}

	/**
	 * @param array $routing
	 */
	private function processRouting( &$routing ) {
		$routingFieldId = Obj::prop( 'fieldId', $routing );
		$routingValue   = Obj::prop( 'value', $routing );
		if ( ! $routingFieldId || ! $routingValue ) {
			return;
		}

		$fieldType = $this->getFieldType( $routingFieldId );

		switch ( $fieldType ) {
			case 'post_category':
				$routing['value'] = apply_filters( 'wpml_object_id', $routingValue, 'category', true );
				break;
			default:
				$getRoutingFieldValue = Obj::path( [ $routingFieldId, $routingValue ] );
				$routing['value']     = Logic::ifElse(
					$getRoutingFieldValue,
					$getRoutingFieldValue,
					FP::always( $routing['value'] ),
					$this->routingFields
				);
				break;
		}
	}

	/**
	 * @param array $notification
	 */
	private function processNotification( &$notification ) {
		foreach ( $notification['routing'] as &$routing ) {
			$this->processRouting( $routing );
		}
	}

	/**
	 * @param array $translatedForm
	 *
	 * @return array
	 */
	public function applyFieldTranslations( $translatedForm ) {
		if ( Logic::not( $this->hasRoutingFields() ) ) {
			return $translatedForm;
		}

		foreach ( $translatedForm['notifications'] as &$notification ) {
			$this->processNotification( $notification );
		}

		return $translatedForm;
	}

}
