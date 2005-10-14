<?php

if ( defined( 'MEDIAWIKI' ) ) {

global $wgCaptchaDirectory;
$wgCaptchaDirectory = "$wgUploadDirectory/captcha"; // bad default :D

global $wgCaptchaSecret;
$wgCaptchaSecret = "CHANGE_THIS_SECRET!";


class FancyCaptcha extends SimpleCaptcha {
	function keyMatch() {
		global $wgRequest, $wgCaptchaSecret;
		
		if( !isset( $_SESSION['ceAnswerVar'] ) ) {
			wfDebug( "FancyCaptcha: no session captcha key set, this is new visitor.\n" );
			return false;
		}
		
		$var  = $_SESSION['ceAnswerVar'];
		$salt = $_SESSION['captchaSalt'];
		$hash = $_SESSION['captchaHash'];
		
		$answer = $wgRequest->getVal( $var );
		$digest = $wgCaptchaSecret . $salt . $answer . $wgCaptchaSecret . $salt;
		$answerHash = substr( md5( $digest ), 0, 16 );
		
		if( $answerHash == $hash ) {
			wfDebug( "FancyCaptcha: answer hash matches expected $hash\n" );
			return true;
		} else {
			wfDebug( "FancyCaptcha: answer hashes to $answerHash, expected $hash\n" );
			return false;
		}
	}
	
	function formCallback( &$out ) {
		$dest = 'wpCaptchaWord' . mt_rand();
		
		$img = $this->pickImage();
		if( !$img ) {
			die( 'aaargh' );
		}
		
		$_SESSION['ceAnswerVar'] = $dest;
		$_SESSION['captchaHash'] = $img['hash'];
		$_SESSION['captchaSalt'] = $img['salt'];
		$_SESSION['captchaViewed'] = false;
		wfDebug( "Picked captcha with hash ${img['hash']}, salt ${img['salt']}.\n" );
		
		$title = Title::makeTitle( NS_SPECIAL, 'Captcha/image' );
		$url = $title->getLocalUrl();
		
		
		$out->addWikiText( wfMsg( "captcha-short" ) );	
		$out->addHTML( <<<END
			<p><img src="$url" alt="Oh noes" /></p>
			<p><input name="$dest" id="$dest" /></p>
END
			);
	}
	
	function pickImage() {
		global $wgCaptchaDirectory;
		$dir = opendir( $wgCaptchaDirectory );
		
		$n = mt_rand( 0, 16 );
		$count = 0;
		
		$entry = readdir( $dir );
		while( false !== $entry ) {
			$entry = readdir( $dir );
			if( preg_match( '/^image_([0-9a-f]+)_([0-9a-f]+)\\.png$/', $entry, $matches ) ) {
				if( $count++ % 16 == $n ) {
					return array(
						'salt' => $matches[1],
						'hash' => $matches[2],
					);
				}
			}
		}
		return false;
	}
	
	function showImage() {
		global $wgOut;
		$wgOut->disable();
		if( !empty( $_SESSION['captchaViewed'] ) ) {
			wfHttpError( 403, 'Access Forbidden', "Can't view captcha image a second time." );
			return false;
		}
		$_SESSION['captchaViewed'] = wfTimestamp();
		
		if( isset( $_SESSION['captchaSalt'] ) ) {
			$salt = $_SESSION['captchaSalt'];
			if( isset( $_SESSION['captchaHash'] ) ) {
				$hash = $_SESSION['captchaHash'];
				
				global $wgCaptchaDirectory;
				$file = $wgCaptchaDirectory . DIRECTORY_SEPARATOR . "image_{$salt}_{$hash}.png";
				if( file_exists( $file ) ) {
					header( 'Content-type: image/png' );
					readfile( $file );
				}
			}
		} else {
			wfHttpError( 500, 'Internal Error', 'Requested bogus captcha image' );
		}
	}
}

} # End invocation guard

?>
