<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\FancyCaptcha;

use InvalidArgumentException;
use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Request\FauxRequest;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use UnderflowException;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha
 * @group Database
 */
class FancyCaptchaTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideGetCaptchaCount */
	public function testGetCaptchaCount( $filenames, $expectedCount ) {
		$captchaDirectory = $this->getNewTempDirectory();

		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );

		// Create captcha files in the $captchaDirectory/subfolder folder.
		mkdir( $captchaDirectory . '/subfolder' );
		foreach ( $filenames as $filename ) {
			file_put_contents( $captchaDirectory . '/subfolder/' . $filename, 'abc' );
		}

		$fancyCaptcha = new FancyCaptcha();
		$this->assertSame( $expectedCount, $fancyCaptcha->getCaptchaCount() );
	}

	public static function provideGetCaptchaCount(): array {
		return [
			'No captcha files present' => [ [], 0 ],
			'One captcha file present' => [ [ 'test.png' ], 1 ],
			'Three captcha files present' => [ [ 'test.png', 'testing.png', 'abc.png' ], 3 ],
		];
	}

	public function testGetCaptchaWhenOutOfImages() {
		// Get a captcha directory with no images
		$captchaDirectory = $this->getNewTempDirectory();
		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );

		// Attempt to pass the captcha and expect it throws because no images could be found
		$fancyCaptcha = new FancyCaptcha();
		$this->expectException( UnderflowException::class );
		$this->expectExceptionMessage( 'Ran out of captcha images' );
		$fancyCaptcha->getCaptcha();
	}

	/**
	 * Gets the hash for an answer to a FancyCaptcha.
	 * Uses the method described at {@link FancyCaptcha::getCaptcha}.
	 */
	private function getHash( string $secret, string $salt, string $answer ): string {
		return substr( md5( $secret . $salt . $answer . $secret . $salt ), 0, 16 );
	}

	/** @dataProvider providePassCaptcha */
	public function testPassCaptcha( bool $captchaPassedSuccessfully, bool $deleteOnSolve ) {
		$captchaDirectory = $this->getNewTempDirectory();

		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );
		$this->overrideConfigValue( 'CaptchaSecret', 'secret' );
		$this->overrideConfigValue( 'CaptchaDeleteOnSolve', $deleteOnSolve );

		// Create one captcha file in the $captchaDirectory folder with a defined hash and salt in the filename.
		$correctAnswer = 'abcdef';
		$imageSalt = '0';
		$imageHash = $this->getHash( 'secret', $imageSalt, $correctAnswer );
		$captchaImageFilename = $captchaDirectory . "/image_{$imageSalt}_{$imageHash}.png";
		file_put_contents( $captchaImageFilename, 'abc' );

		// Either use the correct answer or another answer depending on whether the user should pass the captcha.
		$userProvidedAnswer = $captchaPassedSuccessfully ? $correctAnswer : 'abc';

		// Expect that a debug log is created to indicate that the captcha either was solved or was not solved.
		$mockLogger = $this->createMock( LoggerInterface::class );
		if ( $captchaPassedSuccessfully ) {
			$mockLogger->expects( $this->once() )
				->method( 'debug' )
				->with(
					'FancyCaptcha: answer hash matches expected {expected_hash}',
					[ 'expected_hash' => $imageHash ]
				);
		} else {
			$mockLogger->expects( $this->once() )
				->method( 'debug' )
				->with(
					'FancyCaptcha: answer hashes to {answer_hash}, expected {expected_hash}',
					[
						'answer_hash' => $this->getHash( 'secret', $imageSalt, $userProvidedAnswer ),
						'expected_hash' => $imageHash,
					]
				);
		}
		$this->setLogger( 'captcha', $mockLogger );

		// Attempt to pass the captcha and expect that it either passes or does not pass
		$fancyCaptcha = new FancyCaptcha();
		$info = $fancyCaptcha->getCaptcha();
		$index = $fancyCaptcha->storeCaptcha( $info );
		$this->assertSame(
			$captchaPassedSuccessfully,
			$fancyCaptcha->passCaptchaFromRequest(
				new FauxRequest( [ 'wpCaptchaWord' => $userProvidedAnswer, 'wpCaptchaId' => $index ] ),
				$this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
			)
		);

		// The captcha image we used should still exist unless $wgCaptchaDeleteOnSolve is true and the user passed
		// the captcha.
		if ( $deleteOnSolve && $captchaPassedSuccessfully ) {
			$this->assertFileDoesNotExist( $captchaImageFilename );
		} else {
			$this->assertFileExists( $captchaImageFilename );
		}
	}

	public static function providePassCaptcha(): array {
		return [
			'Passes FancyCaptcha check, no delete on solve' => [ true, false ],
			'Passes FancyCaptcha check, delete on solve' => [ true, true ],
			'Fails FancyCaptcha check' => [ false, true ],
		];
	}

	public function testHashFromImageNameForValidName() {
		$fancyCaptcha = new FancyCaptcha();
		$this->assertSame(
			[ '01234', 'abcdef' ],
			$fancyCaptcha->hashFromImageName( "image_01234_abcdef.png" )
		);
	}

	public function testHashFromImageNameForInvalidName() {
		$fancyCaptcha = new FancyCaptcha();
		$this->expectException( InvalidArgumentException::class );
		$fancyCaptcha->hashFromImageName( "image_ghjk_4567.png" );
	}
}
