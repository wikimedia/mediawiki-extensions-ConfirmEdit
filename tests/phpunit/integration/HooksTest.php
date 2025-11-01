<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Page\Article;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks
 * @group Database
 */
class HooksTest extends MediaWikiIntegrationTestCase {

	public function testGetInstanceNewStyleTriggers() {
		$this->overrideConfigValues(
			[
				'CaptchaClass' => 'SimpleCaptcha',
				'CaptchaTriggers' => [
					// New style trigger
					'edit' => [
						'trigger' => true,
						'class' => 'FancyCaptcha',
					],
					// Old style trigger
					'move' => false,
				]
			]
		);

		// Returns the default for $wgCaptchaClass
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance() );

		// Returns the default for $wgCaptchaClass, because it uses the old style trigger (boolean)
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance( 'move' ) );

		// Returns the default for $wgCaptchaClass, because the trigger isn't defined
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance( 'foo' ) );

		// Returns the FancyCaptcha instance for the edit trigger
		$this->assertInstanceOf( FancyCaptcha::class, Hooks::getInstance( 'edit' ) );
	}

	public function testOnConfirmEditHooksGetInstance() {
		$this->overrideConfigValues( [
			'CaptchaClass' => 'SimpleCaptcha',
			'CaptchaTriggers' => [ 'createaccount' => [
				'trigger' => true,
				'class' => 'FancyCaptcha',
			] ]
		] );
		$this->setTemporaryHook( 'ConfirmEditCaptchaClass', static function ( $action, &$className ) {
			if ( $action === 'createaccount' ) {
				$className = 'HCaptcha';
			} elseif ( $action === 'edit' ) {
				$className = 'QuestyCaptcha';
			} elseif ( $action === 'badlogin' ) {
				$className = 'HCaptcha';
			}
		} );

		$instance = Hooks::getInstance( 'createaccount' );
		$this->assertInstanceOf( HCaptcha::class, $instance );
		$instance->setForceShowCaptcha( true );
		$newInstance = Hooks::getInstance( 'createaccount' );
		$this->assertTrue(
			$newInstance->shouldForceShowCaptcha(),
			'Calling ::getInstance() again returns the cached instance'
		);

		$instance = Hooks::getInstance( 'badlogin' );
		$this->assertInstanceOf( HCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		$instance = Hooks::getInstance( 'edit' );
		$this->assertInstanceOf( QuestyCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		$instance = Hooks::getInstance( 'move' );
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		// Check that cached instance is returned when no action is specified.
		$instance = Hooks::getInstance();
		$instance->setForceShowCaptcha( true );
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$instance = Hooks::getInstance();
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$this->assertTrue( $instance->shouldForceShowCaptcha() );
	}

	/**
	 * @dataProvider provideGetCaptchaTriggerActionFromPage
	 */
	public function testGetCaptchaTriggerActionFromPage( bool $pageExists, string $expectedAction ) {
		$page = $pageExists ? $this->getExistingTestPage() : $this->getNonExistingTestPage();
		$actualAction = Hooks::getCaptchaTriggerActionFromTitle( $page->getTitle() );
		$this->assertEquals( $expectedAction, $actualAction );
	}

	/**
	 * @dataProvider provideGetCaptchaTriggerActionFromPage
	 */
	public function testEditPageBeforeEditButtons( bool $pageExists, string $expectedAction ): void {
		$wikiPage = $pageExists ? $this->getExistingTestPage() : $this->getNonexistingTestPage();
		$requestContext = RequestContext::getMain();
		$requestContext->setWikiPage( $wikiPage );
		// User is needed due to code in EditPage's constructor
		$requestContext->setUser( $this->getTestUser()->getUser() );
		$hooks = new Hooks( $this->getServiceContainer()->getMainWANObjectCache() );
		$article = $this->createMock( Article::class );
		$article->method( 'getPage' )->willReturn( $wikiPage );
		$article->method( 'getTitle' )->willReturn( $wikiPage->getTitle() );
		$article->method( 'getContext' )->willReturn( $requestContext );
		$editPage = new EditPage( $article );
		$buttons = [];
		$tabindex = 0;
		$this->setTemporaryHook(
			'ConfirmEditCaptchaClass',
			function ( $action, &$_ ) use ( $expectedAction ) {
				$this->assertEquals( $expectedAction, $action );
			}
		);
		$hooks->onEditPageBeforeEditButtons( $editPage, $buttons, $tabindex );
	}

	/**
	 * @dataProvider provideGetCaptchaTriggerActionFromPage
	 */
	public function testEditPage__showEditForm_fields( bool $pageExists, string $expectedAction ): void {
		$wikiPage = $pageExists ? $this->getExistingTestPage() : $this->getNonexistingTestPage();
		$requestContext = RequestContext::getMain();
		$requestContext->setWikiPage( $wikiPage );
		$requestContext->setUser( $this->getTestUser()->getUser() );
		$hooks = new Hooks( $this->getServiceContainer()->getMainWANObjectCache() );
		$article = $this->createMock( Article::class );
		$article->method( 'getPage' )->willReturn( $wikiPage );
		$article->method( 'getTitle' )->willReturn( $wikiPage->getTitle() );
		$article->method( 'getContext' )->willReturn( $requestContext );
		$editPage = new EditPage( $article );
		$this->setTemporaryHook( 'ConfirmEditCaptchaClass',
			function ( $action, &$_ ) use ( $expectedAction ) {
				$this->assertEquals( $expectedAction, $action );
			} );
		$hooks->onEditPage__showEditForm_fields( $editPage, $requestContext->getOutput() );
	}

	public static function provideGetCaptchaTriggerActionFromPage(): array {
		return [
			'Existing page returns edit action' => [ true, CaptchaTriggers::EDIT ],
			'Non-existing page returns create action' => [ false, CaptchaTriggers::CREATE ],
		];
	}
}
