<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Hooks\Handlers;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Output\OutputPage;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler
 */
class MakeGlobalVariablesScriptHookHandlerTest extends MediaWikiUnitTestCase {
	public function testWhenGetWikiPageIsNotAvailable(): void {
		$out = $this->createMock( OutputPage::class );
		$out->method( 'canUseWikiPage' )
			->willReturn( false );

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$this->createNoOpMock( ExtensionRegistry::class ),
			new HashConfig( [] ),
			$this->createNoOpMock( CaptchaFactory::class )
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertSame( [ 'wgConfirmEditCaptchaNeededForGenericEdit' => false ], $vars );
	}
}
