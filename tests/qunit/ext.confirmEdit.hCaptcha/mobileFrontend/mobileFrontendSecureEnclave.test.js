const mobileFrontendSecureEnclave = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/mobileFrontend/mobileFrontendSecureEnclave.js' );

QUnit.module(
	'ext.confirmEdit.hCaptcha.mobileFrontend.mobileFrontendSecureEnclave',
	QUnit.newMwEnvironment( {
		beforeEach() {
			mw.config.set( 'wgDBname', 'testwiki' );

			this.ceHookName = ( method ) => `confirmEdit.hCaptcha.${ method }`;

			this.track = this.sandbox.stub( mw, 'track' );
			this.logError = this.sandbox.stub( mw.errorLogger, 'logError' );

			// Sinon fake timers as of v21 only return a static fake value from
			// performance.measure(), so use a regular stub instead.
			this.measure = this.sandbox.stub( performance, 'measure' );
			this.measure.returns( { duration: 0 } );

			this.getEntriesByName = this.sandbox.stub( performance, 'getEntriesByName' );

			// We don't want to add real script elements to the page or interact with
			// the real hCaptcha, so stub the code that does this for this test
			this.window = {
				hcaptcha: {
					render: this.sandbox.stub(),
					execute: this.sandbox.stub(),
					reset: this.sandbox.stub()
				},
				document: document,
				performance: {
					measure: this.measure,
					getEntriesByName: this.getEntriesByName
				}
			};

			const form = document.createElement( 'form' );
			const realCreateElement = document.createElement.bind( document );
			const fakeScriptTag = realCreateElement( 'script' );

			this.submit = this.sandbox.stub( form, 'submit' );
			this.sandbox.stub( fakeScriptTag.classList, 'contains' ).returns( false );
			this.sandbox.stub( document, 'createElement' ).callsFake( ( tagName ) => {
				if ( tagName.toLowerCase() === 'script' ) {
					return fakeScriptTag;
				}

				return realCreateElement( tagName );
			} );

			this.sandbox.stub( document.head, 'appendChild' );
			this.sandbox.stub( document.head, 'removeChild' );

			this.$form = $( form )
				.append( '<input type="text" name="some-input" />' )
				.append( '<textarea name="some-textarea"></textarea>' )
				.append( '<input type="hidden" id="h-captcha">' )
				.append(
					'<ul class="header-cancel"><li>' +
					'  <button type="button" class="back">' +
					'    <span class="mf-icon mf-icon-previous "> </span>' +
					'    <span>Close</span>' +
					'  </button>' +
					'</li></ul>'
				)
				.append(
					'<div class="header-action">' +
					'  <button type="button" class="save submit">' +
					'    <span>Save</span>' +
					'  </button>' +
					'</div>'
				);

			this.$form.appendTo( $( '#qunit-fixture' ) );

			// The display property may be "none" if the element is hidden, but
			// it can also not be set at all (i.e. undefined, which (void 0)
			// equals to) if the loading indicator was never added to the DOM in
			// the first place. When the element is being shown, its display
			// property may be either "block" or "flex".
			this.isLoadingIndicatorVisible = () => !(
				[ undefined, 'none' ].includes(
					this.$form
						.find( '.ext-confirmEdit-hCaptchaLoadingIndicator' )
						.css( 'display' )
				)
			);

			this.areSubmitButtonsDisabled = () => {
				const $save = this.$form.find( '.header-action button.save' );
				const $back = this.$form.find( '.header-cancel button.back' );
				const buttons = [
					...$save.toArray(),
					...$back.toArray()
				];

				return buttons.length > 0 && buttons.every( ( btn ) => btn.disabled );
			};
		},

		afterEach() {
			this.track.restore();
			this.measure.restore();
			this.logError.restore();
			this.getEntriesByName.restore();
		}
	} )
);

QUnit.test(
	'should not wait for a form submission when run for the MobileFrontend Editor',
	async function ( assert ) {
		this.window.document.head.appendChild.callsFake( async () => {
			assert.false(
				this.isLoadingIndicatorVisible(),
				'should not show loading indicator prior to execute'
			);
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.callsFake(
			() => Promise.resolve( {
				response: 'some-token'
			} )
		);

		const hook = mw.hook( this.ceHookName( 'executionSuccess' ) );
		const spy = this.sandbox.spy( hook, 'fire' );

		// The promise from mobileFrontendSecureEnclave should resolve without
		// waiting for a form submission - this will time out if it doesn't.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		assert.true(
			this.window.document.head.appendChild.calledOnce,
			'should load hCaptcha SDK once'
		);
		assert.true(
			this.window.hcaptcha.render.calledOnce,
			'should render hCaptcha widget once'
		);
		assert.deepEqual(
			this.window.hcaptcha.render.firstCall.args[ 0 ],
			this.$form.find( '#h-captcha' )[ 0 ],
			'should render hCaptcha widget in correct element'
		);
		assert.true(
			this.window.hcaptcha.execute.calledOnce,
			'should execute hCaptcha without waiting for a form submission'
		);

		assert.true( spy.calledOnce, 'Hook was fired once' );
		assert.deepEqual(
			spy.firstCall.args[ 0 ],
			'some-token',
			'Hook was fired with expected arguments'
		);

		// Clean up spy to avoid affecting later tests
		spy.restore();
	}
);

QUnit.test(
	'should not retry after a challenge-closed error',
	async function ( assert ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.callsFake(
			() => Promise.reject( 'challenge-closed' )
		);

		const hook = mw.hook( this.ceHookName( 'executionSuccess' ) );
		const spy = this.sandbox.spy( hook, 'fire' );

		// The promise from mobileFrontendSecureEnclave should resolve without
		// waiting for a form submission - this will time out if it doesn't.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		assert.true(
			this.window.hcaptcha.execute.calledOnce,
			'should not have attempted a retry after challenge-closed'
		);
		assert.false(
			spy.called,
			'executionSuccess hook should not have been fired'
		);

		// Clean up spy to avoid affecting later tests
		spy.restore();
	}
);

QUnit.test(
	'should not re-render hCaptcha widget on a subsequent save attempt',
	async function ( assert ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.onFirstCall().callsFake(
			() => Promise.reject( 'challenge-closed' )
		);
		this.window.hcaptcha.execute.onSecondCall().callsFake(
			() => Promise.resolve( { response: 'some-token' } )
		);

		// First save: user sees the challenge and dismisses it.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );
		// Second save: same container, render() must not be called again.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		assert.true(
			this.window.hcaptcha.render.calledOnce,
			'should render hCaptcha widget only once across both save attempts'
		);
		assert.strictEqual(
			this.window.hcaptcha.execute.callCount,
			2,
			'should execute hCaptcha once per save attempt'
		);
		assert.true(
			this.window.hcaptcha.reset.calledOnceWithExactly( 'some-captcha-id' ),
			'should reset the cached widget after the dismissed first challenge'
		);
	}
);

QUnit.test(
	'should re-render hCaptcha widget when the container element is replaced',
	async function ( assert ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.onFirstCall().callsFake(
			() => Promise.reject( 'challenge-closed' )
		);
		this.window.hcaptcha.execute.onSecondCall().callsFake(
			() => Promise.resolve( { response: 'some-token' } )
		);

		// First save: user sees the challenge and dismisses it.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		// Simulate cleanupDuplicateHCaptchaContainers() replacing the #h-captcha element,
		// as happens in the AbuseFilter flow before a subsequent save attempt.
		$( '#h-captcha' ).remove();
		$( '#qunit-fixture' ).append( '<input type="hidden" id="h-captcha">' );

		// Second save: new container element, render() must be called again.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		assert.strictEqual(
			this.window.hcaptcha.render.callCount,
			2,
			'should re-render hCaptcha widget into the new container element'
		);
	}
);

QUnit.test.each(
	'should initiate a new workflow after a recoverable error',
	{
		'challenge-expired': 'challenge-expired',
		'internal-error': 'internal-error',
		'network-error': 'network-error',
		'rate-limited': 'rate-limited'
	},
	async function ( assert, error ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.onFirstCall().callsFake(
			() => {
				assert.true(
					this.areSubmitButtonsDisabled(),
					'submit buttons should be disabled before calling execute'
				);
				return Promise.reject( error );
			}
		);
		this.window.hcaptcha.execute.onSecondCall().callsFake(
			() => {
				assert.true(
					this.areSubmitButtonsDisabled(),
					'submit buttons should remain disabled between retries'
				);
				return Promise.resolve( { response: 'some-token' } );
			}
		);

		const hook = mw.hook( this.ceHookName( 'executionSuccess' ) );
		const spy = this.sandbox.spy( hook, 'fire' );

		// The promise from mobileFrontendSecureEnclave should resolve without
		// waiting for a form submission - this will time out if it doesn't.
		await mobileFrontendSecureEnclave( this.window, 'mobilefrontendeditor' );

		assert.strictEqual(
			this.window.hcaptcha.execute.callCount,
			2,
			`should retry once after ${ error }`
		);
		assert.false(
			this.areSubmitButtonsDisabled(),
			'submit buttons should be re-enabled after the retry succeeds'
		);
		assert.true(
			spy.calledOnce,
			'executionSuccess should have been fired after the retry succeeds'
		);
		assert.deepEqual(
			spy.firstCall.args[ 0 ],
			'some-token',
			'Hook was fired with expected arguments'
		);

		// Clean up spy to avoid affecting later tests
		spy.restore();
	}
);
