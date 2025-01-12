/* eslint-env node */
module.exports = function ( grunt ) {
	const messagesDirs = require( './extension.json' ).MessagesDirs;
	for ( const subExtension of [
		'QuestyCaptcha',
		'ReCaptchaNoCaptcha',
		'FancyCaptcha',
		'hCaptcha'
	] ) {
		// eslint-disable-next-line security/detect-non-literal-require
		messagesDirs[ subExtension ] = require( './' + subExtension + '/extension.json' )
			.MessagesDirs[ subExtension ]
			.map( ( path ) => subExtension + '/' + path );
	}

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				fix: grunt.option( 'fix' )
			},
			all: [
				'**/*.{js,json}',
				'!{vendor,node_modules}/**'
			]
		},
		stylelint: {
			options: {
				cache: true
			},
			all: [
				'**/*.{css,less}',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		banana: messagesDirs
	} );

	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
