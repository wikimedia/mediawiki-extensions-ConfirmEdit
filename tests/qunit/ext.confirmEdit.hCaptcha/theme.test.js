QUnit.module( 'ext.confirmEdit.hCaptcha.theme', QUnit.newMwEnvironment( {
	beforeEach() {
		this.theme = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/theme.js' );
	}
} ) );

QUnit.test.each( 'isDarkMode', {
	'night class present': {
		themeClass: 'skin-theme-clientpref-night',
		prefersColorScheme: null,
		expected: true
	},
	'no theme class present': {
		themeClass: null,
		prefersColorScheme: null,
		expected: false
	},
	'os class present and OS prefers dark': {
		themeClass: 'skin-theme-clientpref-os',
		prefersColorScheme: 'dark',
		expected: true
	},
	'os class present and OS prefers light': {
		themeClass: 'skin-theme-clientpref-os',
		prefersColorScheme: 'light',
		expected: false
	},
	'os class present and matchMedia unavailable': {
		themeClass: 'skin-theme-clientpref-os',
		prefersColorScheme: 'unavailable',
		expected: false
	}
}, function ( assert, data ) {
	const contains = this.sandbox.stub().returns( false );
	if ( data.themeClass ) {
		contains.withArgs( data.themeClass ).returns( true );
	}

	const win = {
		document: { documentElement: { classList: { contains: contains } } },
		matchMedia: data.prefersColorScheme === 'unavailable' ?
			undefined :
			this.sandbox.stub().returns( { matches: data.prefersColorScheme === 'dark' } )
	};

	assert.strictEqual( this.theme.isDarkMode( win ), data.expected );
} );

QUnit.test( 'getDarkThemeValue returns the custom dark theme object', function ( assert ) {
	const value = this.theme.getDarkThemeValue();
	assert.strictEqual( typeof value, 'object', 'should return a custom theme object' );
	assert.strictEqual( value.palette.mode, 'dark', 'the theme object should use dark palette mode' );
} );
