<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\ReCaptchaNoCaptcha;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha\HTMLReCaptchaNoCaptchaField;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha\HTMLReCaptchaNoCaptchaField
 */
class HTMLReCaptchaNoCaptchaFieldTest extends MediaWikiIntegrationTestCase {

	public function testSubmit() {
		$request = new FauxRequest( [
			'foo' => 'abc',
			'g-recaptcha-response' => 'def',
		], true );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );

		$form = new HTMLForm( [
			'foo' => [
				'class' => HTMLReCaptchaNoCaptchaField::class,
				'key' => '123',
			],
		], $context );

		$mockClosure = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ '__invoke' ] )->getMock();
		$mockClosure->expects( $this->once() )->method( '__invoke' )
			->with( [ 'foo' => 'def' ] )->willReturn( true );

		$form->setTitle( Title::newFromText( 'Title' ) );
		$form->setSubmitCallback( $mockClosure );
		$form->prepareForm();
		$form->trySubmit();
	}
}
