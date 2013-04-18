( function ( $, mw ) {
	var api = new mw.Api();
	$( document ).on( 'click', '.fancycaptcha-reload', function () {
		var staticImageDirectory = mw.config.get( 'wgExtensionAssetsPath' ) + '/ConfirmEdit/images/',
			$this = $( this ), reloadButtonImage, captchaImage;

		reloadButtonImage = $this
			.find( '.fancycaptcha-reload-button' )
			.attr( 'src', staticImageDirectory + 'ajax-loader-10x10.gif' );

		captchaImage = $( '.fancycaptcha-image' );

		// AJAX request to get captcha index key
		api.post( {
			action: 'fancycaptchareload',
			format: 'xml'
		}, {
			dataType: 'xml'
		} )
		.done( function ( xmldata ) {
			var imgSrc, captchaIndex;
			captchaIndex = $( xmldata ).find( 'fancycaptchareload' ).attr( 'index' );
			if ( typeof captchaIndex === 'string' ) {
				// replace index key with a new one for captcha image
				imgSrc = captchaImage.attr( 'src' )
				.replace( /(wpCaptchaId=)\w+/, '$1' + captchaIndex );
				captchaImage.attr( 'src', imgSrc );

				// replace index key with a new one for hidden tag
				$( '#wpCaptchaId' ).val( captchaIndex );
				$( '#wpCaptchaWord' ).val( '' ).focus();
			}
		} )
		.always( function () {
			reloadButtonImage.attr( 'src', staticImageDirectory + 'fancycaptcha-reload-icon.png' );
		} );

		return false;
	} );
} )( jQuery, mediaWiki );
