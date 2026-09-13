<?php

namespace WPML\Forms\WPForms\SharedAPI;

use SitePress;
use WPML\Forms\Loader\Base;
use WPML\Forms\WPForms\Addons\WPFormsUserRegistration;
use WPML\Forms\WPForms\Hooks\AteLayoutHooks;
use WPML\Forms\WPForms\Hooks\ConversationalForms;
use WPML\Forms\WPForms\Hooks\DynamicChoices;
use WPML\Forms\WPForms\Hooks\EntryEdit;
use WPML\Forms\WPForms\Hooks\EntryPreviewField;
use WPML\Forms\WPForms\Hooks\FormPages;
use WPML\Forms\WPForms\Hooks\Import;
use WPML\Forms\WPForms\Addons\SaveAndResume;
use WPML\Forms\WPForms\Hooks\SubLabels;
use WPML\Forms\WPForms\Hooks\TranslateEverythingHooks;
use WPML\Forms\Translation\Factory as FormsFactory;
use WPML\Forms\WPForms\Addons\SurveyAndPolls;
use WPML\Forms\WPForms\Hooks\ChoiceValidation;
use WPML\Forms\WPForms\Hooks\Factory;


class WpForms extends Base {

	const SLUG = 'wpforms';

	const TITLE = 'WPForms';

	/** Gets package slug. */
	protected function getSlug() {
		return self::SLUG;
	}

	/** Gets package title. */
	protected function getTitle() {
		return self::TITLE;
	}

	/** Adds hooks. */
	protected function addHooks() {
		/** @var SitePress $sitepress */
		global $sitepress;

		$formsFactory = new FormsFactory( $this->preferences );

		$wpforms = new Strings(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory,
			$sitepress
		);
		$wpforms->addHooks();

		$notifications = new Notifications(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory
		);
		$notifications->addHooks();

		$package_filter = new Package( $this->getSlug() );
		$package_filter->addHooks();

		$conversational_forms = new ConversationalForms( $wpforms );
		$conversational_forms->addHooks();

		$dynamic_choices = new DynamicChoices();
		$dynamic_choices->addHooks();

		if ( defined( 'WPFORMS_FORM_PAGES_VERSION' ) ) {
			$form_pages = new FormPages( $wpforms );
			$form_pages->addHooks();
		}

		if ( is_admin() ) {
			$entryEdit = new EntryEdit( $wpforms );
			$entryEdit->addHooks();
		}

		$entryPreviewField = new EntryPreviewField(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory,
			$sitepress
		);
		$entryPreviewField->addHooks();

		$factory   = new Factory();
		$ateLayout = new AteLayoutHooks( $factory );
		$ateLayout->addHooks();

		$translateEverything = new TranslateEverythingHooks();
		$translateEverything->addHooks();

		$import = new Import();
		$import->addHooks();

		$subLabels = new SubLabels();
		$subLabels->addHooks();

		$choiceValidation = new ChoiceValidation();
		$choiceValidation->addHooks();

		if ( defined( 'WPFORMS_SURVEYS_POLLS_VERSION' ) ) {
			$surveyAndPolls = new SurveyAndPolls(
				$this->getSlug(),
				$this->getTitle(),
				$formsFactory
			);
			$surveyAndPolls->addHooks();
		}

		if ( defined( 'WPFORMS_SAVE_RESUME_VERSION' ) ) {
			$saveAndResume = new SaveAndResume(
				$this->getSlug(),
				$this->getTitle(),
				$formsFactory
			);
			$saveAndResume->addHooks();
		}

		if ( defined( 'WPFORMS_USER_REGISTRATION_VERSION' ) ) {
			$userRegistration = new WPFormsUserRegistration();
			$userRegistration->addHooks();
		}
	}
}
