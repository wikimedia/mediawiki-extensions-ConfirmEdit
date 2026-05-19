<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;
use MediaWiki\Utils\FileContentsHasher;

/**
 * ResourceLoader module that assembles the self-contained Grade C hCaptcha
 * bundle on demand.
 *
 * The bundle includes jQuery, a minimal mw shim, a CommonJS-style require
 * runtime, the JS files necessary for the wikitext desktop editor and the
 * entry point. It is served via load.php with only=scripts&raw=1 so the
 * response is the raw concatenated source, without any mw.loader.impl
 * wrapper, suitable for delivery via a plain <script src> tag through
 * window.NORLQ on Grade C clients.
 */
class GradeCBundleModule extends Module {

	private const HCAPTCHA_MODULES = [
		'./ErrorWidget.js',
		'./ProgressIndicatorWidget.js',
		'./utils.js',
		'./secureEnclave.js',
	];

	public function getScript( Context $context ): string {
		$paths = $this->getInputPathMap();
		$lines = [
			'( function ( window ) {',
			"'use strict';",
			file_get_contents( $paths['jquery'] ),
			'var $ = window.jQuery;',
			file_get_contents( $paths['mwShim'] ),
			'var mw = window.mw;',
			$this->getRequireRuntime(),
		];
		foreach ( self::HCAPTCHA_MODULES as $name ) {
			// Inner IIFE so a stray "});" in the source can't terminate the
			// __defineModule call and inject a sibling module registration.
			$lines[] = '__defineModule(' . json_encode( $name )
				. ', function (module, exports, require) { ( function () {';
			$lines[] = file_get_contents( $paths[$name] );
			$lines[] = '} )(); } );';
		}
		$lines[] = file_get_contents( $paths['entry'] );
		$lines[] = '}( window ) );';
		return implode( "\n", $lines ) . "\n";
	}

	public function getDefinitionSummary( Context $context ): array {
		return [
			...parent::getDefinitionSummary( $context ),
			'fileHashes' => FileContentsHasher::getFileContentsHash( array_values( $this->getInputPathMap() ) ),
			'classHash' => FileContentsHasher::getFileContentsHash( [ __FILE__ ] ),
		];
	}

	/**
	 * Map of input label → file path. Labels 'jquery', 'mwShim', 'entry' name
	 * the bundle scaffolding; the remaining labels are HCAPTCHA_MODULES entries.
	 *
	 * @return array<string,string>
	 */
	private function getInputPathMap(): array {
		$extDir = dirname( __DIR__, 2 );
		$paths = [
			'jquery' => MW_INSTALL_PATH . '/resources/lib/jquery/jquery.js',
			'mwShim' => $extDir . '/resources/grade-c/source/mwShim.js',
			'entry' => $extDir . '/resources/grade-c/source/entry.js',
		];
		foreach ( self::HCAPTCHA_MODULES as $name ) {
			$paths[$name] = $extDir . '/resources/ext.confirmEdit.hCaptcha/' . ltrim( $name, './' );
		}
		return $paths;
	}

	/**
	 * Pure ES5 CommonJS-style require runtime. Modules are looked up by the
	 * exact key they were registered with (no path normalisation), so the
	 * canonical files' require('./utils.js') calls resolve directly.
	 */
	private function getRequireRuntime(): string {
		return <<<'JS'
var __modules = {};
var __cache = {};
function __defineModule( name, factory ) {
	__modules[ name ] = factory;
}
function __require( name ) {
	if ( !Object.prototype.hasOwnProperty.call( __cache, name ) ) {
		var module = __cache[ name ] = { exports: {} };
		__modules[ name ]( module, module.exports, __require );
	}
	return __cache[ name ].exports;
}
JS;
	}
}
