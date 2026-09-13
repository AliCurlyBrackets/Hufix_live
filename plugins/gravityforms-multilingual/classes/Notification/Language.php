<?php

namespace GFML\Notification;

use GFFormDisplay;
use RGFormsModel;
use WPML\API\Sanitize;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use function WPML\FP\pipe;

/**
 * Translate the notifications based on the preferred language of the recipients.
 */
class Language implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \GFML_TM_API $tmApi */
	protected $tmApi;

	/** @var \GFML_String_Name_Helper $snh */
	protected $snh;

	/**
	 * @param \GFML_TM_API             $tmApi
	 * @param \GFML_String_Name_Helper $snh
	 */
	public function __construct( \GFML_TM_API $tmApi, \GFML_String_Name_Helper $snh ) {
		$this->tmApi = $tmApi;
		$this->snh   = $snh;
	}

	public function add_hooks() {
		add_filter( 'gform_notification', [ $this, 'translate' ], 10, 3 );
	}

	/**
	 * @param string $email
	 *
	 * @return string|null
	 */
	private function getUserLanguage( $email ) {
		$language = apply_filters( 'wpml_user_email_language', null, $email );

		if ( ! $language ) {
			$user = get_user_by( 'email', $email );
			if ( isset( $user->ID ) ) {
				$userLocale = get_user_meta( $user->ID, 'locale', true );
				if ( $userLocale ) {
					$language = $this->tmApi->getLanguageCodeFromLocale( $userLocale );
				}
			}
		}
		if ( ! $language ) {
			$language = get_option( 'wpml_user_email_language' );
		}
		if ( ! $language ) {
			$language = apply_filters( 'wpml_default_language', null );
		}

		return $language;
	}

	/**
	 * @param string[] $emails
	 *
	 * @return string|null
	 */
	private function switchToRecipientsLanguage( $emails ) {
		$currentLanguage = apply_filters( 'wpml_current_language', null );

		/**
		 * @param string $email
		 *
		 * @return string|null
		 */
		$getLanguageByUserEmail = function( $email ) {
			if ( ! email_exists( $email ) ) {
				return null;
			}
			$userLanguage = $this->getUserLanguage( $email );
			if ( ! $userLanguage ) {
				return null;
			}
			return $userLanguage;
		};
		/**
		 * @param array $emails
		 *
		 * @return array
		 */
		$getRecipientsLanguages = pipe(
			Fns::map( $getLanguageByUserEmail ),
			pipe(
				'array_unique',
				'array_filter'
			)
		);
		$recipientsLanguages    = $getRecipientsLanguages( $emails );

		if ( 1 !== count( $recipientsLanguages ) ) {
			return null;
		}

		$recipientsLanguage = reset( $recipientsLanguages );
		if ( $recipientsLanguage === $currentLanguage ) {
			return null;
		}

		do_action( 'wpml_switch_language', $recipientsLanguage );
		return $currentLanguage;
	}

	/**
	 * @param array $notification
	 * @param array $form
	 * @param mixed $lead
	 *
	 * @return string[]
	 */
	private function getRecipients( $notification, $form, $lead ) {
		/**
		 * @param string $list
		 *
		 * @return array
		 */
		$trimSplit = pipe(
			Str::split( ',' ),
			Fns::map( 'trim' )
		);

		/**
		 * @param int|string $fieldId
		 *
		 * @return string
		 */
		$getFieldValue = function( $fieldId ) use ( $form, $lead ) {
			$sourceField = RGFormsModel::get_field( $form, $fieldId );
			return RGFormsModel::get_lead_field_value( $lead, $sourceField );
		};

		$emailsTo                  = [];
		$notificationRecipientType = Obj::prop( 'toType', $notification );
		switch ( $notificationRecipientType ) {
			case 'field':
				$toField = Logic::ifElse(
					pipe(
						Obj::propOr( '', 'toField' ),
						Logic::isEmpty()
					),
					// This might be some backward compatibility thing, from the outside it makes little sense.
					Obj::propOr( '', 'to' ),
					Obj::propOr( '', 'toField' ),
					$notification
				);
				if ( ! empty( $toField ) ) {
					$prepareEmailsTo = pipe(
						$getFieldValue,
						$trimSplit
					);
					$emailsTo        = $prepareEmailsTo( $toField );
				}
				break;
			case 'routing':
				if ( Logic::isEmpty( Obj::prop( 'routing', $notification ) ) ) {
					break;
				}
				foreach ( $notification['routing'] as $routing ) {
					if ( empty( Obj::prop( 'email', $routing ) ) ) {
						continue;
					}

					// Note that routing conditions values were translated with the form on the gform_pre_process filter.
					$sourceField  = RGFormsModel::get_field( $form, Obj::prop( 'fieldId', $routing ) );
					$fieldValue   = RGFormsModel::get_lead_field_value( $lead, $sourceField );
					$routingValue = Obj::propOr( '', 'value', $routing );
					$isValueMatch = RGFormsModel::is_value_match( $fieldValue, $routingValue, Obj::propOr( 'is', 'operator', $routing ), $sourceField, $routing, $form ) && ! RGFormsModel::is_field_hidden( $form, $sourceField, array(), $lead );

					if ( $isValueMatch ) {
						$emailsTo[] = $routing['email'];
					}
				}
				break;
			default:
				$emailsTo = $trimSplit( Obj::propOr( '', 'to', $notification ) );
				break;
		}

		/**
		 * @param string $placeholder
		 *
		 * @return string
		 */
		$replaceAdminPlaceholder = Logic::ifElse(
			Relation::equals( '{admin_email}' ),
			// See GFCommon::replace_variables.
			Fns::always( get_bloginfo( 'admin_email' ) ),
			Fns::identity()
		);

		$prepareEmailsTo = pipe(
			Fns::map( $replaceAdminPlaceholder ),
			pipe(
				'array_unique',
				'array_filter'
			)
		);

		return $prepareEmailsTo( $emailsTo );
	}

	/**
	 * @param array $notification
	 * @param array $form
	 * @param mixed $lead
	 *
	 * @return array
	 */
	public function translate( $notification, $form, $lead ) {
		if ( isset( $notification['subject'] ) || isset( $notification['message'] ) ) {
			$this->snh->notification = $notification;
			$stContext               = $this->tmApi->get_st_context( $form['id'] );
			$recipients              = $this->getRecipients( $notification, $form, $lead );

			if ( empty( $recipients ) ) {
				return $notification;
			}

			// Capture the translated form title before switching language, while still in the submission language context.
			// In async notification processing, $form['title'] is always the default-language title because GFAPI::get_form()
			// bypasses GFML's translation hooks, so {form_title} must be replaced manually.
			$translatedFormTitle = ! empty( $form['title'] ) ? icl_t( $stContext, $this->snh->get_form_title(), $form['title'] ) : null;

			$languageToRestore = $this->switchToRecipientsLanguage( $recipients );

			if ( isset( $notification['subject'] ) ) {
				$notification['subject'] = apply_filters( 'wpml_translate_single_string', $notification['subject'], $stContext, $this->snh->get_form_notification_subject() );
			}
			if ( isset( $notification['message'] ) ) {
				$notification['message'] = apply_filters( 'wpml_translate_single_string', $notification['message'], $stContext, $this->snh->get_form_notification_message() );

				if ( isset( $notification['toType'] ) && 'hidden' === $notification['toType'] ) {
					// phpcs:disable WordPress.Security.NonceVerification.Missing
					$resume_token = isset( $_POST['gform_resume_token'] ) ? Sanitize::string( wp_unslash( $_POST['gform_resume_token'] ) ) : '';
					$resume_email = isset( $_POST['gform_resume_email'] ) ? Sanitize::string( wp_unslash( $_POST['gform_resume_email'] ) ) : '';
					// phpcs:enable WordPress.Security.NonceVerification.Missing

					if ( $resume_token && $resume_email ) {
						$notification['message'] = GFFormDisplay::replace_save_variables( $notification['message'], $form, $resume_token, $resume_email );
					}
				}
			}

			if ( null !== $languageToRestore ) {
				do_action( 'wpml_switch_language', $languageToRestore );
			}

			if ( null !== $translatedFormTitle ) {
				if ( isset( $notification['subject'] ) ) {
					$notification['subject'] = str_replace( '{form_title}', $translatedFormTitle, $notification['subject'] );
				}
				if ( isset( $notification['message'] ) ) {
					$notification['message'] = str_replace( '{form_title}', $translatedFormTitle, $notification['message'] );
				}
			}
		}

		return $notification;
	}

}
