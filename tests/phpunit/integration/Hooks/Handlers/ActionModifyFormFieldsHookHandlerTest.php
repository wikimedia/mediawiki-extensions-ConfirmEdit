<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use Closure;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\ActionModifyFormFieldsHookHandler;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Page\Article;
use MediaWikiIntegrationTestCase;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\ActionModifyFormFieldsHookHandler
 * @group Database
 */
class ActionModifyFormFieldsHookHandlerTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	protected function setUp(): void {
		parent::setUp();
		self::clearCaptchaFactoryGlobalInstances();
	}

	/**
	 * @dataProvider provideMcrUndoCaptchaInjectionAsFormField
	 */
	public function testMcrUndoCaptchaInjectionAsFormField(
		string $actionName,
		bool $editTriggerEnabled,
		bool $canSkipCaptcha,
		bool $expectCaptchaField
	): void {
		$this->overrideConfigValue( 'CaptchaTriggers', [ CaptchaTriggers::EDIT => $editTriggerEnabled ] );
		self::clearCaptchaFactoryGlobalInstances();
		\OOUI\Theme::setSingleton( new \OOUI\BlankTheme() );

		$this->setTemporaryHook(
			'ConfirmEditCanUserSkipCaptcha',
			static function ( $user, &$result ) use ( $canSkipCaptcha ) {
				$result = $canSkipCaptcha;
			}
		);

		$wikiPage = $this->getExistingTestPage();
		$title = $wikiPage->getTitle();
		$context = new RequestContext();
		$context->setUser( $this->getMutableTestUser()->getUser() );
		$context->setTitle( $title );
		$context->setWikiPage( $wikiPage );

		$article = new Article( $title );
		$article->setContext( $context );

		$handler = new ActionModifyFormFieldsHookHandler(
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);

		$fields = [];
		$handler->onActionModifyFormFields( $actionName, $fields, $article );

		if ( $expectCaptchaField ) {
			$this->assertArrayHasKey( 'captcha', $fields );
			$this->assertSame( 'info', $fields['captcha']['type'] );
			$this->assertTrue( $fields['captcha']['raw'] );
			$this->assertSame( '', $fields['captcha']['label'], 'empty label suppresses FieldLayout header text' );
			$this->assertInstanceOf(
				Closure::class,
				$fields['captcha']['default'],
				'widget HTML is rendered lazily at form display time'
			);
			$html = $fields['captcha']['default']();
			$this->assertNotEmpty( $html );
			$elements = DOMCompat::querySelectorAll(
				DOMUtils::parseHTML( $html ),
				'.captcha'
			);
			$this->assertCount( 1, $elements, 'Should add a .captcha widget as a form field' );
		} else {
			$this->assertArrayNotHasKey( 'captcha', $fields );
		}
	}

	public static function provideMcrUndoCaptchaInjectionAsFormField(): array {
		return [
			'mcrundo with EDIT trigger enabled adds captcha field' => [
				'actionName' => 'mcrundo',
				'editTriggerEnabled' => true,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => true,
			],
			'mcrrestore with EDIT trigger enabled adds captcha field' => [
				'actionName' => 'mcrrestore',
				'editTriggerEnabled' => true,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => true,
			],
			'mcrundo with EDIT trigger disabled leaves fields untouched' => [
				'actionName' => 'mcrundo',
				'editTriggerEnabled' => false,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => false,
			],
			'mcrrestore with EDIT trigger disabled leaves fields untouched' => [
				'actionName' => 'mcrrestore',
				'editTriggerEnabled' => false,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => false,
			],
			'mcrundo skips captcha for users with skipcaptcha right' => [
				'actionName' => 'mcrundo',
				'editTriggerEnabled' => true,
				'canSkipCaptcha' => true,
				'expectCaptchaField' => false,
			],
			'protect action is ignored' => [
				'actionName' => 'protect',
				'editTriggerEnabled' => true,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => false,
			],
			'delete action is ignored' => [
				'actionName' => 'delete',
				'editTriggerEnabled' => true,
				'canSkipCaptcha' => false,
				'expectCaptchaField' => false,
			],
		];
	}
}
