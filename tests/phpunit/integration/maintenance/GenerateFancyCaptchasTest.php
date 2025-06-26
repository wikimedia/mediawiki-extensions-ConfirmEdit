<?php

namespace MediaWiki\Extension\ConfirmEdit\Test\Integration\Maintenance;

use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\Maintenance\GenerateFancyCaptchas;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Maintenance\GenerateFancyCaptchas
 */
class GenerateFancyCaptchasTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return GenerateFancyCaptchas::class;
	}

	public function setUp(): void {
		parent::setUp();
		Hooks::unsetInstanceForTests();
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		Hooks::unsetInstanceForTests();
	}

	public function testExecuteWhenCaptchaInstanceNotFancyCaptcha() {
		$this->overrideConfigValue( 'CaptchaClass', SimpleCaptcha::class );

		$this->expectOutputRegex( '/\$wgCaptchaClass is not FancyCaptcha/' );
		$this->expectCallToFatalError();
		$this->maintenance->execute();
	}

	public function testExecuteWhenCaptchaContainerAlreadyFilledToSpecifiedNumber() {
		$captchaDirectory = $this->getNewTempDirectory();

		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );

		// Create one captcha file in the $captchaDirectory/subfolder folder
		mkdir( $captchaDirectory . '/subfolder' );
		$captchaFilename = $captchaDirectory . '/subfolder/test.png';
		file_put_contents( $captchaFilename, 'abc' );

		$this->maintenance->setOption( 'fill', 1 );
		$this->maintenance->setOption( 'wordlist', $this->getNewTempFile() );
		$this->maintenance->setOption( 'font', $this->getNewTempFile() );
		$this->maintenance->execute();

		// Verify that the script did not attempt to generate more captchas as it already has a captcha
		$actualOutput = $this->getActualOutputForAssertion();
		$this->assertStringContainsString( 'Current number of captchas is 1.', $actualOutput );
		$this->assertStringContainsString( 'No need to generate any extra captchas.', $actualOutput );
		$this->assertDoesNotMatchRegularExpression( '/Generating.*new captchas\.\.\./', $actualOutput );
		$this->assertFileExists( $captchaFilename );
	}
}
