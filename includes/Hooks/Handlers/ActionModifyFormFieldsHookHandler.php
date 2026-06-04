<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Actions\Hook\ActionModifyFormFieldsHook;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;

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
		$formInformation = $captcha->getFormInformation( 1, $out );
		$formMetainfo = $formInformation;
		unset( $formMetainfo['html'] );
		$captcha->addFormInformationToOutput( $out, $formMetainfo );

		$fields['captcha'] = [
			'type' => 'info',
			'raw' => true,
			'label' => '',
			'default' => '<div class="captcha">' .
				$captcha->getMessage( CaptchaTriggers::EDIT )->parseAsBlock() .
				$formInformation['html'] .
				"</div>\n",
		];
	}
}
