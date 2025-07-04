<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\FancyCaptcha;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\HTMLFancyCaptchaField;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\FancyCaptcha\HTMLFancyCaptchaField
 * @group Database
 */
class HTMLFancyCaptchaFieldTest extends MediaWikiIntegrationTestCase {

	public function testGetHTML() {
		$html = $this->getForm( [ 'imageUrl' => 'https://example.com/' ] )->getHTML( false );
		$this->assertMatchesRegularExpression( '/"fancycaptcha-image"/', $html );
		$this->assertMatchesRegularExpression( '#src="https://example.com/"#', $html );
		$this->assertDoesNotMatchRegularExpression( '/"mw-createacct-captcha-assisted"/', $html );

		$html = $this->getForm( [ 'imageUrl' => '', 'showCreateHelp' => true ] )->getHTML( false );
		$this->assertMatchesRegularExpression( '/"mw-createacct-captcha-assisted"/', $html );

		$html = $this->getForm( [ 'imageUrl' => '', 'label' => 'FooBarBaz' ] )->getHTML( false );
		$this->assertMatchesRegularExpression( '/FooBarBaz/', $html );
	}

	public function testValue() {
		$mockClosure = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ '__invoke' ] )->getMock();
		$request = new FauxRequest( [ 'wpcaptchaWord' => 'abc' ], true );
		$form = $this->getForm( [ 'imageUrl' => 'https://example.com/' ], $request );
		$form->setSubmitCallback( $mockClosure );

		$mockClosure->expects( $this->once() )->method( '__invoke' )
			->with( [ 'captchaWord' => 'abc' ] )->willReturn( true );
		$form->trySubmit();
	}

	protected function getForm( $params = [], ?WebRequest $request = null ) {
		if ( $request ) {
			$context = new DerivativeContext( RequestContext::getMain() );
			$context->setRequest( $request );
		} else {
			$context = RequestContext::getMain();
		}
		$params['class'] = HTMLFancyCaptchaField::class;
		$form = new HTMLForm( [ 'captchaWord' => $params ], $context );
		$form->setTitle( Title::newFromText( 'Foo' ) );
		$form->prepareForm();
		return $form;
	}
}
