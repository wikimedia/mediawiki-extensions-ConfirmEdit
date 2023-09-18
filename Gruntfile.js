/* eslint-env node */
module.exports = function ( grunt ) {
	var messagesDirs = grunt.file.readJSON( 'extension.json' ).MessagesDirs;

	var subExtensions = [
		'QuestyCaptcha',
		'ReCaptchaNoCaptcha',
		'FancyCaptcha',
		'MathCaptcha',
		'hCaptcha'
	];

	subExtensions.forEach(
		function ( subExtension ) {
			messagesDirs[ subExtension ] = grunt.file.readJSON( subExtension + '/extension.json' ).MessagesDirs[ subExtension ].map(
				function ( path ) {
					return subExtension + '/' + path;
				}
			);
		} );

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
