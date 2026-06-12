<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Actions\Hook\ActionModifyFormFieldsHook;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Output\OutputPage;

/**
 * Adds the CAPTCHA widget as a form field for action=mcrundo and action=mcrrestore.
 */
class ActionModifyFormFieldsHookHandler implements ActionModifyFormFieldsHook {

	private const array SUPPORTED_ACTIONS = [ 'mcrundo', 'mcrrestore' ];

	public function __construct(
		private readonly CaptchaFactory $captchaFactory,
	) {
	}

	/** @inheritDoc */
	public function onActionModifyFormFields( $name, &$fields, $article ): void {
		if ( !in_array( $name, self::SUPPORTED_ACTIONS, true ) ) {
			return;
		}

		$context = $article->getContext();
		$title = $article->getTitle();
		$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );

		if ( !$captcha->triggersCaptcha( CaptchaTriggers::EDIT, $title ) ) {
			return;
		}
		if ( $captcha->canSkipCaptcha( $context->getUser() ) ) {
			return;
		}
		$captcha->setAction( CaptchaTriggers::EDIT );

		$out = $context->getOutput();

		$fields['captcha'] = [
			'type' => 'info',
			'raw' => true,
			'label' => '',
			'default' => fn (): string => $this->renderCaptchaField( $captcha, $out ),
		];
	}

	/**
	 * Renders the CAPTCHA widget. Called via a Closure default, which
	 * HTMLInfoField evaluates at form display time, after the submit is
	 * processed, so the widget reflects state set during the submit (e.g.
	 * an AbuseFilter "showcaptcha" consequence forcing the sitekey).
	 */
	private function renderCaptchaField( SimpleCaptcha $captcha, OutputPage $out ): string {
		$formInformation = $captcha->getFormInformation( 1, $out );
		$formMetainfo = $formInformation;
		unset( $formMetainfo['html'] );
		$captcha->addFormInformationToOutput( $out, $formMetainfo );

		return '<div class="captcha">' .
			$captcha->getMessage( CaptchaTriggers::EDIT )->parseAsBlock() .
			$formInformation['html'] .
			"</div>\n";
	}
}
