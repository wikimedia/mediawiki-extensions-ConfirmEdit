'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );
const esX = require( 'eslint-plugin-es-x' );

// Directories with their own config, which other configs don't apply to.
// These were directories with `"root": true` in .eslintrc.json.
const rootDirs = {
	gradeC: [ 'resources/grade-c/**' ],
	resources: [ 'resources/**' ],
	qunit: [ 'tests/qunit/**' ]
};

module.exports = defineConfig( [
	globalIgnores( [
		// Build
		'vendor/',
		'docs/',

		// Language files written automatically by TranslateWiki
		'**/i18n/**/*.json',
		'!**/i18n/**/en.json',
		'!**/i18n/**/qqq.json'
	] ),

	// Gruntfile.js, etc.
	{
		ignores: Object.values( rootDirs ).flat(),
		extends: [ wikimedia( 'server' ) ]
	},

	// Code which runs in MediaWiki
	{
		files: rootDirs.resources,
		ignores: rootDirs.gradeC,
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki' ) ]
	},
	{
		files: [
			'resources/ve-confirmedit/**',
			'resources/ve-confirmedit-reCaptchaNoCaptcha/**',
			'resources/ve-confirmedit-turnstile/**',
			'resources/ext.confirmEdit.hCaptcha/ve/**'
		],
		languageOptions: {
			globals: {
				ve: 'readonly'
			}
		}
	},
	{
		files: [
			'resources/libs/**',
			'resources/ve-confirmedit/**',
			'resources/ve-confirmedit-reCaptchaNoCaptcha/**',
			'resources/ve-confirmedit-turnstile/**',
			'resources/ext.confirmEdit.hCaptcha/ve/**'
		],
		languageOptions: {
			globals: {
				OO: 'readonly'
			}
		}
	},
	{
		files: [
			'resources/ext.confirmEdit.hCaptcha/**/ErrorWidget.js',
			'resources/ext.confirmEdit.hCaptcha/**/ProgressIndicatorWidget.js',
			'resources/ext.confirmEdit.hCaptcha/**/secureEnclave.js',
			'resources/ext.confirmEdit.hCaptcha/**/theme.js',
			'resources/ext.confirmEdit.hCaptcha/**/utils.js'
		],
		rules: {
			...esX.configs[ 'flat/restrict-to-es2015' ].rules,
			'compat/compat': [ 'error', 'Chrome >= 49, Edge >= 79, Firefox >= 49, Safari >= 10, iOS >= 10, Android >= 5' ],
			'es-x/no-object-entries': 'off',
			'es-x/no-array-prototype-includes': 'off',
			'no-restricted-syntax': [
				'error',
				{
					selector: "ForOfStatement > VariableDeclaration[kind='const']",
					message: 'Some older browsers reject `for (const … of …)` with a SyntaxError; use an indexed `for` or `.forEach()`.'
				},
				{
					selector: "MemberExpression[property.name='searchParams']",
					message: '`URL.prototype.searchParams` is undefined on some older browsers (observed on Chrome 49-50 macOS High Sierra); build query strings with `encodeURIComponent` instead.'
				}
			]
		}
	},

	// Code which runs on Grade C browsers
	{
		files: rootDirs.gradeC,
		extends: [ wikimedia( 'client/common' ) ],
		languageOptions: {
			globals: {
				$: 'readonly',
				mw: 'writable',
				__defineModule: 'readonly',
				__require: 'readonly'
			}
		},
		rules: {
			'no-implicit-globals': 'off',
			'no-underscore-dangle': 'off',
			'jsdoc/require-param': 'off'
		}
	},

	// QUnit tests
	{
		files: rootDirs.qunit,
		extends: [ wikimedia( 'mediawiki/qunit' ) ]
	},
	{
		files: [
			'tests/qunit/ext.confirmEdit.visualEditor/**',
			'tests/qunit/ext.confirmEdit.hCaptcha/ve/**'
		],
		languageOptions: {
			globals: {
				ve: 'readonly'
			}
		}
	}
] );
