/* eslint-env node */
module.exports = function ( grunt ) {
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
		banana: {
			all: [
				'i18n/',
				'i18n/api/',
				'**/i18n/',
				'**/i18n/api/'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
