<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Hooks\Handlers;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler;
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
			new HashConfig( [] )
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertSame( [ 'wgConfirmEditCaptchaNeededForGenericEdit' => false ], $vars );
	}
}
