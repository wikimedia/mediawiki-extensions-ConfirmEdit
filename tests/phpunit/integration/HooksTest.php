<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Content\ContentHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Page\Article;
use MediaWiki\Status\Status;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks
 * @group Database
 */
class HooksTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	protected function setUp(): void {
		parent::setUp();
		self::clearCaptchaFactoryGlobalInstances();
	}

	private function getObjectUnderTest(): Hooks {
		return new Hooks(
			$this->getServiceContainer()->getMainWANObjectCache(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
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
		$this->getObjectUnderTest()->onEditPageBeforeEditButtons( $editPage, $buttons, $tabindex );
	}

	/**
	 * @dataProvider provideGetCaptchaTriggerActionFromPage
	 */
	public function testEditPage__showEditForm_fields( bool $pageExists, string $expectedAction ): void {
		$wikiPage = $pageExists ? $this->getExistingTestPage() : $this->getNonexistingTestPage();
		$requestContext = RequestContext::getMain();
		$requestContext->setWikiPage( $wikiPage );
		$requestContext->setUser( $this->getTestUser()->getUser() );
		$article = $this->createMock( Article::class );
		$article->method( 'getPage' )->willReturn( $wikiPage );
		$article->method( 'getTitle' )->willReturn( $wikiPage->getTitle() );
		$article->method( 'getContext' )->willReturn( $requestContext );
		$editPage = new EditPage( $article );
		$this->setTemporaryHook( 'ConfirmEditCaptchaClass',
			function ( $action, &$_ ) use ( $expectedAction ) {
				$this->assertEquals( $expectedAction, $action );
			} );
		$this->getObjectUnderTest()->onEditPage__showEditForm_fields( $editPage, $requestContext->getOutput() );
	}

	public static function provideGetCaptchaTriggerActionFromPage(): array {
		return [
			'Existing page returns edit action' => [ true, CaptchaTriggers::EDIT ],
			'Non-existing page returns create action' => [ false, CaptchaTriggers::CREATE ],
		];
	}

	public function testOnEditFilterMergedContentWhenUserSkipsCaptchas() {
		$this->setTemporaryHook( 'ConfirmEditCanUserSkipCaptcha', static function ( $user, &$result ) {
			$result = true;
		} );

		$user = $this->getTestUser()->getUser();
		$title = $this->getNonexistingTestPage()->getTitle();
		$status = Status::newGood();
		$context = RequestContext::getMain();
		$context->setUser( $user );
		$context->setTitle( $title );

		$simpleCaptcha = Hooks::getInstance( 'create' );
		$this->assertFalse(
			$simpleCaptcha->editFilterMergedContentHandlerAlreadyInvoked(),
			'::editFilterMergedContentHandlerAlreadyInvoked should be false before hook is run'
		);

		$this->assertTrue( $this->getObjectUnderTest()->onEditFilterMergedContent(
			$context, ContentHandler::makeContent( '', $title ), $status, '', $user, false
		) );
		$this->assertStatusGood( $status );

		$simpleCaptcha = Hooks::getInstance( 'create' );
		$this->assertTrue(
			$simpleCaptcha->editFilterMergedContentHandlerAlreadyInvoked(),
			'::editFilterMergedContentHandlerAlreadyInvoked should be true after hook is run'
		);
	}

	public function testOnEditFilterMergedContentWhenUserFailsCaptcha() {
		$this->clearHook( 'ConfirmEditCanUserSkipCaptcha' );
		$this->setTemporaryHook( 'ConfirmEditTriggersCaptcha', static function ( $action, $title, &$result ) {
			$result = true;
		} );

		// Set up the request and context such that no captcha word is provided,
		// so that the captcha check will fail
		$user = $this->getTestUser()->getUser();
		$title = $this->getNonexistingTestPage()->getTitle();
		$status = Status::newGood();
		$context = RequestContext::getMain();
		$context->setUser( $user );
		$context->setTitle( $title );

		$simpleCaptcha = Hooks::getInstance( 'create' );
		$this->assertFalse( $simpleCaptcha->editFilterMergedContentHandlerAlreadyInvoked() );

		$this->assertFalse( $this->getObjectUnderTest()->onEditFilterMergedContent(
			$context, ContentHandler::makeContent( '', $title ), $status, '', $user, false
		) );
		$this->assertStatusNotGood( $status );

		$simpleCaptcha = Hooks::getInstance( 'create' );
		$this->assertTrue(
			$simpleCaptcha->editFilterMergedContentHandlerAlreadyInvoked(),
			'::editFilterMergedContentHandlerAlreadyInvoked should be true after hook is run'
		);
	}
}
