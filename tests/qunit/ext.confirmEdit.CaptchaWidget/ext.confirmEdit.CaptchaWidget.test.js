'use strict';

QUnit.module( 'ext.confirmEdit.CaptchaWidget', QUnit.newMwEnvironment(), () => {
	QUnit.test( 'captchaNeededForEdit returns value of wgConfirmEditCaptchaNeededForGenericEdit', ( assert ) => {
		mw.config.set( 'wgConfirmEditCaptchaNeededForGenericEdit', 'captcha' );

		assert.strictEqual(
			mw.libs.confirmEdit.CaptchaWidget.static.captchaNeededForEdit(),
			'captcha',
			'captchaNeededForEdit should return the value of wgConfirmEditCaptchaNeededForGenericEdit'
		);
	} );

	QUnit.test( 'constructor called without container provided', ( assert ) => {
		assert.throws(
			() => {
				// eslint-disable-next-line no-new
				new mw.libs.confirmEdit.CaptchaWidget( {} );
			},
			/CaptchaWidget requires a container/,
			'Construction without container provided should throw error'
		);
	} );

	QUnit.test( 'constructor makes CAPTCHA type lowercase', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: '#qunit-fixture',
			type: 'Test-Captcha'
		} );

		assert.strictEqual(
			captchaWidget.config.type,
			'test-captcha',
			'CAPTCHA type should be lowercase in internal config'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if CAPTCHA type not specified', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: '#qunit-fixture'
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA type not specified/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA type has not been specified'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if CAPTCHA not supported', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: $qunitFixture[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA not supported/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA has not been rendered'
		);
		assert.strictEqual(
			$qunitFixture.find( '.mw-confirmEdit-captchaWidget' ).length,
			1,
			'Only one captcha widget container should exist'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if container does not exist in the DOM', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: $( '<div>' )[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA container should exist in the DOM before calling renderCaptcha/,
			'renderCaptcha should reject if the CAPTCHA container is not in the DOM'
		);
	} );

	QUnit.test( 'getCaptchaDataForSubmission rejects if CAPTCHA not rendered', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		assert.rejects(
			captchaWidget.getCaptchaDataForSubmission(),
			/Render the CAPTCHA before getting the CAPTCHA data/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA has not been rendered'
		);
	} );

	QUnit.test( 'getCaptchaDataForSubmission rejects if CAPTCHA not supported', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );
		captchaWidget.captchaRendered = true;

		assert.rejects(
			captchaWidget.getCaptchaDataForSubmission(),
			/CAPTCHA not supported/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA is not supported'
		);
	} );

	QUnit.test( 'updateForFailure resolves if no re-rendering needed', function ( assert ) {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		captchaWidget.renderCaptcha = this.sandbox.stub();

		return captchaWidget.updateForFailure( { type: 'Test-captcha' } ).then( () => {
			assert.true(
				captchaWidget.renderCaptcha.notCalled,
				'renderCaptcha should not be called if no re-rendering was needed'
			);
			assert.strictEqual(
				captchaWidget.config.type,
				'test-captcha',
				'Internal CAPTCHA type should be in lowercase'
			);
		} );
	} );

	QUnit.test( 'updateForFailure re-renders the CAPTCHA if captcha changed', function ( assert ) {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		captchaWidget.captchaRendered = true;
		captchaWidget.renderCaptcha = this.sandbox.stub().returns( Promise.resolve() );

		return captchaWidget.updateForFailure( { type: 'test-Captcha-2' } ).then( () => {
			assert.strictEqual(
				captchaWidget.renderCaptcha.callCount,
				1,
				'renderCaptcha should be called once if the CAPTCHA type has changed'
			);
			assert.strictEqual(
				captchaWidget.config.type,
				'test-captcha-2',
				'Internal CAPTCHA type should be in lowercase'
			);
		} );
	} );

	QUnit.test.each( 'CAPTCHA widget is hCaptcha', {
		'hCaptcha is in invisible mode': {
			hCaptchaInvisibleMode: true,
			wgConfirmEditForceShowCaptchaConfigValue: false
		},
		'hCaptcha is not in invisible mode': {
			hCaptchaInvisibleMode: false,
			wgConfirmEditForceShowCaptchaConfigValue: false
		},
		'wgConfirmEditForceShowCaptcha config set': {
			hCaptchaInvisibleMode: true,
			wgConfirmEditForceShowCaptchaConfigValue: true
		}
	}, function ( assert, options ) {
		mw.config.set(
			'wgConfirmEditForceShowCaptcha',
			options.wgConfirmEditForceShowCaptchaConfigValue
		);

		const mockHCaptchaModule = {
			utils: {
				loadHCaptcha: this.sandbox.stub().returns( Promise.resolve() ),
				renderHCaptcha: this.sandbox.stub().returns( 'widget-id' ),
				executeHCaptcha: this.sandbox.stub().returns( Promise.resolve( 'test-response' ) ),
				getHCaptchaSiteKey: () => 'test-site-key',
				isHCaptchaInInvisibleMode: () => options.hCaptchaInvisibleMode
			}
		};

		this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.hCaptcha' )
			.resolves( ( requiredModuleName ) => {
				if ( requiredModuleName === 'ext.confirmEdit.hCaptcha' ) {
					return mockHCaptchaModule;
				} else {
					assert.true( false, 'Unexpected module required using mw.loader.using' );
				}
			} );

		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'hcaptcha',
			container: $qunitFixture[ 0 ],
			interfaceName: 'test-interface'
		} );

		return captchaWidget.renderCaptcha().then( () => {
			// Expect hCaptcha is loaded and rendered
			assert.true(
				mockHCaptchaModule.utils.loadHCaptcha.calledOnce,
				'loadHCaptcha should be called'
			);
			assert.deepEqual(
				mockHCaptchaModule.utils.loadHCaptcha.firstCall.args,
				[ window, 'test-interface', { render: 'explicit' } ],
				'loadHCaptcha arguments are as expected'
			);
			assert.true(
				mockHCaptchaModule.utils.renderHCaptcha.calledOnce,
				'renderHCaptcha should be called'
			);
			assert.deepEqual(
				mockHCaptchaModule.utils.renderHCaptcha.firstCall.args.slice( 0, 2 ),
				[ window, 'test-interface' ],
				'renderHCaptcha arguments are as expected'
			);
			assert.true(
				$qunitFixture[ 0 ].contains(
					mockHCaptchaModule.utils.renderHCaptcha.firstCall.args[ 2 ]
				),
				'renderHCaptcha 3rd argument should be an element inside the specified container'
			);
			assert.strictEqual(
				mockHCaptchaModule.utils.renderHCaptcha.firstCall.args[ 3 ].sitekey,
				'test-site-key',
				'The expected sitekey is used'
			);
			assert.true(
				mockHCaptchaModule.utils.executeHCaptcha.notCalled,
				'executeHCaptcha should not have been called yet'
			);

			assert.strictEqual(
				$( mockHCaptchaModule.utils.renderHCaptcha.firstCall.args[ 2 ] ).attr( 'data-size' ),
				options.hCaptchaInvisibleMode ? 'invisible' : undefined,
				'The data-size attribute should be set only if in invisible mode'
			);
			assert.strictEqual(
				$( '.ext-confirmEdit-hcaptcha-privacy-policy', $qunitFixture ).length,
				options.hCaptchaInvisibleMode ? 1 : 0,
				'The hCaptcha privacy policy text should be set only if in invisible mode'
			);
			assert.strictEqual(
				$( '.ext-confirmEdit-force-show-captcha-notice', $qunitFixture ).length,
				0,
				'The force show captcha notice should not be shown for first CAPTCHA use attempt'
			);

			return captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
				assert.true(
					mockHCaptchaModule.utils.executeHCaptcha.calledOnce,
					'executeHCaptcha should have been called'
				);
				assert.deepEqual(
					mockHCaptchaModule.utils.executeHCaptcha.firstCall.args,
					[ window, 'widget-id', 'test-interface' ],
					'executeHCaptcha arguments are as expected'
				);

				const expectedCaptchaData = { captchaid: '', captchaword: 'test-response' };
				if ( options.wgConfirmEditForceShowCaptchaConfigValue ) {
					expectedCaptchaData.wgConfirmEditForceShowCaptcha = true;
				}
				assert.deepEqual(
					captchaData,
					expectedCaptchaData,
					'Captcha data returned by promise is as expected'
				);
			} );
		} );
	} );

	QUnit.test.each( 'updateForFailure when CAPTCHA is hCaptcha', {
		'Captcha data does not contain a sitekey when hCaptcha already rendered': {
			captchaData: { type: 'hcaptcha' },
			hCaptchaAlreadyRendered: true,
			shouldReRenderHCaptcha: true,
			shouldResetHCaptcha: false,
			expectedNewSiteKey: ''
		},
		'Captcha data does not contain a sitekey when hCaptcha not rendered': {
			captchaData: { type: 'hcaptcha' },
			hCaptchaAlreadyRendered: false,
			shouldReRenderHCaptcha: false,
			shouldResetHCaptcha: false,
			expectedNewSiteKey: ''
		},
		'Captcha data contains a sitekey when hCaptcha already rendered': {
			captchaData: { type: 'hcaptcha', key: 'api-provided-site-key' },
			hCaptchaAlreadyRendered: true,
			shouldReRenderHCaptcha: true,
			shouldResetHCaptcha: false,
			expectedNewSiteKey: 'api-provided-site-key'
		},
		'Failure not CAPTCHA related when hCaptcha already rendered': {
			captchaData: undefined,
			hCaptchaAlreadyRendered: true,
			shouldReRenderHCaptcha: false,
			shouldResetHCaptcha: true,
			expectedNewSiteKey: ''
		},
		'Failure not CAPTCHA related when hCaptcha not rendered': {
			captchaData: undefined,
			hCaptchaAlreadyRendered: false,
			shouldReRenderHCaptcha: false,
			shouldResetHCaptcha: false,
			expectedNewSiteKey: ''
		}
	}, function ( assert, options ) {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'hcaptcha',
			container: '#qunit-fixture'
		} );

		captchaWidget.captchaWord = 'value-to-assert-this-is-cleared';
		captchaWidget.captchaRendered = options.hCaptchaAlreadyRendered;
		if ( options.hCaptchaAlreadyRendered ) {
			captchaWidget.hCaptchaWidgetId = 'widget-id';
		}
		captchaWidget.renderCaptcha = this.sandbox.stub().returns( Promise.resolve() );

		const mockHCaptchaModule = {
			utils: {
				resetHCaptcha: this.sandbox.stub()
			}
		};

		this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.hCaptcha' )
			.resolves( ( requiredModuleName ) => {
				if ( requiredModuleName === 'ext.confirmEdit.hCaptcha' ) {
					return mockHCaptchaModule;
				} else {
					assert.true( false, 'Unexpected module required using mw.loader.using' );
				}
			} );

		return captchaWidget.updateForFailure( options.captchaData ).then( ( actualRecommendResubmit ) => {
			assert.false(
				actualRecommendResubmit,
				'updateForFailure should not recommend resubmit'
			);
			if ( options.shouldReRenderHCaptcha ) {
				assert.strictEqual(
					captchaWidget.renderCaptcha.callCount,
					1,
					'renderCaptcha should have been called'
				);
			} else {
				assert.true(
					captchaWidget.renderCaptcha.notCalled,
					'renderCaptcha should have not been called'
				);
			}
			assert.strictEqual(
				mockHCaptchaModule.utils.resetHCaptcha.callCount,
				options.shouldResetHCaptcha ? 1 : 0,
				'resetHCaptcha should only be called when hCaptcha is rendered but not being re-rendered'
			);
			if ( options.shouldResetHCaptcha ) {
				assert.deepEqual(
					mockHCaptchaModule.utils.resetHCaptcha.firstCall.args,
					[ window, 'widget-id' ],
					'resetHCaptcha arguments are as expected'
				);
			}
			assert.strictEqual(
				captchaWidget.captchaWord,
				'',
				'captchaWord should be cleared for failure when CAPTCHA is hCaptcha'
			);
			assert.strictEqual(
				captchaWidget.hCaptchaSiteKey,
				options.expectedNewSiteKey,
				'hCaptcha sitekey should use sitekey in captcha API data unless not specified'
			);
		} );
	} );

	QUnit.test( 'hCaptcha widget for forceshowcaptcha CAPTCHA failure', function ( assert ) {
		mw.config.set( 'wgConfirmEditForceShowCaptcha', false );

		const mockHCaptchaModule = {
			utils: {
				loadHCaptcha: this.sandbox.stub().returns( Promise.resolve() ),
				renderHCaptcha: this.sandbox.stub().returns( 'widget-id' ),
				executeHCaptcha: this.sandbox.stub().returns( Promise.resolve( 'test-response' ) ),
				getHCaptchaSiteKey: () => 'test-site-key',
				isHCaptchaInInvisibleMode: () => true
			}
		};

		this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.hCaptcha' )
			.resolves( ( requiredModuleName ) => {
				if ( requiredModuleName === 'ext.confirmEdit.hCaptcha' ) {
					// False positive
					// eslint-disable-next-line qunit/no-early-return
					return mockHCaptchaModule;
				} else {
					assert.true( false, 'Unexpected module required using mw.loader.using' );
				}
			} );

		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'hcaptcha',
			container: $qunitFixture[ 0 ],
			interfaceName: 'test-interface'
		} );

		return captchaWidget.renderCaptcha().then( () => {
			// Expect hCaptcha is loaded and rendered
			assert.true(
				mockHCaptchaModule.utils.loadHCaptcha.calledOnce,
				'loadHCaptcha should be called'
			);
			assert.true(
				mockHCaptchaModule.utils.renderHCaptcha.calledOnce,
				'renderHCaptcha should be called'
			);
			assert.strictEqual(
				$( '.ext-confirmEdit-force-show-captcha-notice', $qunitFixture ).length,
				0,
				'The force show captcha notice should not be shown for first CAPTCHA use attempt'
			);

			const captchaData = { type: 'hcaptcha', error: 'forceshowcaptcha' };
			return captchaWidget.updateForFailure( captchaData ).then( ( actualRecommendResubmit ) => {
				assert.true(
					actualRecommendResubmit,
					'updateForFailure should recommend resubmission'
				);
				assert.strictEqual(
					mockHCaptchaModule.utils.renderHCaptcha.callCount,
					2,
					'renderHCaptcha should be called again after forceshowcaptcha failure'
				);
				assert.strictEqual(
					$( '.ext-confirmEdit-force-show-captcha-notice', $qunitFixture ).length,
					1,
					'The force show captcha notice should be shown after API responds with forceshowcaptcha'
				);
			} );
		} );
	} );

	QUnit.test( 'Methods reject when ext.confirmEdit.hCaptcha fails to load', function ( assert ) {
		this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.hCaptcha' )
			.rejects( new Error( 'Test rejection' ) );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'hcaptcha',
			container: $( '#qunit-fixture' )[ 0 ],
			interfaceName: 'test-interface'
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/Test rejection/,
			'renderCaptcha should reject if ext.confirmEdit.hCaptcha fails to load'
		);

		captchaWidget.captchaRendered = true;
		assert.rejects(
			captchaWidget.updateForFailure( { type: 'hcaptcha' } ),
			/Test rejection/,
			'updateForFailure should reject if ext.confirmEdit.hCaptcha fails to load'
		);

		captchaWidget.captchaRendered = true;
		assert.rejects(
			captchaWidget.getCaptchaDataForSubmission(),
			/Test rejection/,
			'getCaptchaDataForSubmission should reject if ext.confirmEdit.hCaptcha fails to load'
		);
	} );

	QUnit.test( 'CAPTCHA widget is QuestyCaptcha', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'question',
			container: $qunitFixture[ 0 ]
		} );

		const questionHtml = $( '<div>' )
			.html( $( '<p>' ).addClass( 'test-question-class' ).text( 'What is 2 + 2?' ) )
			.html();

		return captchaWidget.updateForFailure( {
			type: 'question',
			id: 'questy-captcha-id',
			mime: 'text/html',
			question: questionHtml
		} ).then( () => captchaWidget.renderCaptcha() ).then( () => {
			const $captchaInput = $qunitFixture.find( '.mw-confirmEdit-captchaInputField' );

			assert.strictEqual(
				$captchaInput.length,
				1,
				'CAPTCHA input should be rendered'
			);
			assert.true(
				$qunitFixture.text().includes( '(questycaptcha-edit)' ),
				'QuestyCaptcha label should be rendered'
			);

			const $captchaQuestion = $( '.test-question-class', $qunitFixture );
			assert.strictEqual(
				$captchaQuestion.length,
				1,
				'The question should have rendered as HTML'
			);
			assert.strictEqual(
				$captchaQuestion.text(),
				'What is 2 + 2?',
				'The question text should be as expected'
			);

			$captchaInput.val( '4' );

			return captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
				assert.deepEqual(
					captchaData,
					{ captchaid: 'questy-captcha-id', captchaword: '4' },
					'QuestyCaptcha data should include the expected ID and entered answer'
				);
			} );
		} );
	} );

	QUnit.test( 'CAPTCHA widget is SimpleCaptcha', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'simple',
			container: $qunitFixture[ 0 ]
		} );

		return captchaWidget.updateForFailure( {
			type: 'simple',
			id: 'simple-captcha-id',
			mime: 'text/plain',
			question: 'What is 4 + 2?'
		} ).then( () => captchaWidget.renderCaptcha() ).then( () => {
			const $captchaInput = $qunitFixture.find( '.mw-confirmEdit-captchaInputField' );

			assert.strictEqual(
				$captchaInput.length,
				1,
				'CAPTCHA input should be rendered'
			);
			assert.true(
				$qunitFixture.text().includes( '(captcha-edit)' ),
				'SimpleCaptcha label should be rendered'
			);
			assert.true(
				$qunitFixture.text().includes( 'What is 4 + 2?' ),
				'SimpleCaptcha question should be rendered'
			);

			$captchaInput.val( '4' );

			return captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
				assert.deepEqual(
					captchaData,
					{ captchaid: 'simple-captcha-id', captchaword: '4' },
					'SimpleCaptcha data should include the expected ID and entered answer'
				);
			} );
		} );
	} );

	QUnit.test( 'CAPTCHA widget is FancyCaptcha', function ( assert ) {
		const $qunitFixture = $( '#qunit-fixture' );

		const usingStub = this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.fancyCaptcha' )
			.resolves();

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'image',
			container: $qunitFixture[ 0 ]
		} );

		return captchaWidget.updateForFailure( {
			type: 'fancycaptcha',
			id: 'fancy-captcha-id',
			url: 'https://example.org/fancy-captcha.png'
		} ).then( () => {
			const captchaLoadPromise = captchaWidget.renderCaptcha();

			// setTimeout call is used so that we can wait for the mocked mw.loader.using
			// call to return it's resolved promise.
			setTimeout( () => {
				const $captchaContainer = $( '.mw-confirmEdit-captchaWidget', $qunitFixture );
				const $captchaImage = $( '.fancycaptcha-image', $captchaContainer );
				const $captchaInput = $( '.mw-confirmEdit-captchaInputField', $captchaContainer );

				assert.true(
					usingStub.calledOnce,
					'ext.confirmEdit.fancyCaptcha should be loaded'
				);
				assert.true(
					// eslint-disable-next-line no-jquery/no-class-state
					$captchaContainer.hasClass( 'fancycaptcha-captcha-container' ),
					'FancyCaptcha container class should be applied'
				);
				assert.strictEqual(
					$captchaImage.length,
					1,
					'FancyCaptcha image should be rendered'
				);
				assert.strictEqual(
					$captchaImage.attr( 'src' ),
					'https://example.org/fancy-captcha.png',
					'FancyCaptcha image src should match API data'
				);
				assert.strictEqual(
					$captchaImage.data( 'captchaId' ),
					'fancy-captcha-id',
					'FancyCaptcha image should store captcha ID'
				);
				assert.strictEqual(
					$( '.fancycaptcha-reload', $captchaContainer ).length,
					1,
					'FancyCaptcha reload link should be rendered'
				);
				assert.strictEqual(
					$captchaInput.length,
					1,
					'FancyCaptcha input should be rendered'
				);

				$captchaImage.trigger( 'load' );
			} );

			return captchaLoadPromise.then( () => {
				const $captchaInput = $( '.mw-confirmEdit-captchaInputField', $qunitFixture );
				const $captchaImage = $( '.fancycaptcha-image', $qunitFixture );

				$captchaInput.val( 'stale-answer' );
				$captchaImage.data( 'captchaId', 'fancy-captcha-id-2' ).trigger( 'fancycaptcha-reloaded' );

				assert.strictEqual(
					$captchaInput.val(),
					'',
					'FancyCaptcha input should be cleared when the captcha image reloads'
				);

				$captchaInput.val( 'abc123' );

				return captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
					assert.deepEqual(
						captchaData,
						{ captchaid: 'fancy-captcha-id-2', captchaword: 'abc123' },
						'FancyCaptcha data should include the expected ID and entered answer'
					);
				} );
			} );
		} );
	} );

	QUnit.test( 'CAPTCHA widget FancyCaptcha renderCaptcha rejects when image fails to load', function ( assert ) {
		const $qunitFixture = $( '#qunit-fixture' );

		this.sandbox.stub( mw.loader, 'using' )
			.withArgs( 'ext.confirmEdit.fancyCaptcha' )
			.resolves();

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'fancycaptcha',
			container: $qunitFixture[ 0 ]
		} );

		return captchaWidget.updateForFailure( {
			type: 'fancycaptcha',
			id: 'fancy-captcha-id',
			url: 'https://example.org/fancy-captcha.png'
		} ).then( () => {
			const captchaLoadPromise = captchaWidget.renderCaptcha();

			setTimeout( () => {
				// Use triggerHandler so the widget's handler runs without firing a global window error.
				$( '.fancycaptcha-image', $qunitFixture ).triggerHandler( 'error' );
			} );

			return assert.rejects(
				captchaLoadPromise,
				/FancyCaptcha image failed to load/,
				'renderCaptcha should reject if the FancyCaptcha image fails to load'
			);
		} );
	} );

	QUnit.test( 'CAPTCHA widget is QuestyCaptcha but captcha mime is unknown', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'question',
			container: $qunitFixture[ 0 ]
		} );

		return captchaWidget.updateForFailure( {
			type: 'question',
			id: 'questy-captcha-id',
			mime: 'text/abc',
			question: 'test-question'
		} ).then( () => {
			assert.rejects(
				captchaWidget.renderCaptcha(),
				/The mime type of the question is not recognised/,
				'renderCaptcha should fail if the captcha question mime type is unrecognised'
			);
		} );
	} );

	QUnit.test( 'CAPTCHA widget is QuestyCaptcha but no captcha question defined', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'question',
			container: $qunitFixture[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/Please provide the captcha ID and question via updateForFailure/,
			'renderCaptcha should fail if captcha ID and question are not yet defined'
		);
	} );

	QUnit.test( 'CAPTCHA widget is FancyCaptcha but no image is defined', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'FancyCaptcha',
			container: $qunitFixture[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/Please provide the captcha ID and image URL via updateForFailure/,
			'renderCaptcha should fail if captcha ID and image url are not yet defined'
		);
	} );
} );
