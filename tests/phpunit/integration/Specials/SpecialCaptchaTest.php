<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Special;

use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaHashStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaSessionStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;
use SpecialPageTestBase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Specials\SpecialCaptcha
 * @covers \MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 * @group Database
 */
class SpecialCaptchaTest extends SpecialPageTestBase {

	public function setUp(): void {
		parent::setUp();
		Hooks::unsetInstanceForTests();
		CaptchaStore::unsetInstanceForTests();
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Hooks::unsetInstanceForTests();
		CaptchaStore::unsetInstanceForTests();
	}

	public static function provideCaptchaStorageClasses(): array {
		return [
			'CaptchaSessionStore (uses cookies)' => [ CaptchaSessionStore::class, true ],
			'CaptchaHashStore (does not use cookies)' => [ CaptchaHashStore::class, false ],
		];
	}

	/** @dataProvider provideCaptchaStorageClasses */
	public function testExecuteForSimpleCaptcha( $captchaStorageClass, $usesCookies ) {
		$this->overrideConfigValue( 'CaptchaStorageClass', $captchaStorageClass );
		$this->overrideConfigValue( 'CaptchaClass', SimpleCaptcha::class );

		[ $html ] = $this->executeSpecialPage( '', null, null, null, true );

		$this->assertStringContainsString( '(captchahelp-text)', $html );
		$this->assertStringContainsString( '(captchahelp-title)', $html );
		if ( $usesCookies ) {
			$this->assertStringContainsString( '(captchahelp-cookies-needed', $html );
		} else {
			$this->assertStringNotContainsString( '(captchahelp-cookies-needed', $html );
		}
	}

	/** @dataProvider provideCaptchaStorageClasses */
	public function testExecuteForFancyCaptcha( $captchaStorageClass, $usesCookies ) {
		$this->overrideConfigValue( 'CaptchaStorageClass', $captchaStorageClass );
		$this->overrideConfigValue( 'CaptchaClass', QuestyCaptcha::class );

		[ $html ] = $this->executeSpecialPage( '', null, null, null, true );

		$this->assertStringContainsString( '(questycaptchahelp-text)', $html );
		$this->assertStringContainsString( '(captchahelp-title)', $html );
		if ( $usesCookies ) {
			$this->assertStringContainsString( '(captchahelp-cookies-needed', $html );
		} else {
			$this->assertStringNotContainsString( '(captchahelp-cookies-needed', $html );
		}
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'Captcha' );
	}
}
