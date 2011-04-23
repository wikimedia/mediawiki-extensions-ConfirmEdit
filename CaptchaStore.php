<?php
class CaptchaSessionStore {

	function store( $index, $info ) {
		$_SESSION['captcha' . $info['index']] = $info;
	}

	function retrieve( $index ) {
		if ( isset( $_SESSION['captcha' . $index] ) ) {
			return $_SESSION['captcha' . $index];
		} else {
			return false;
		}
	}

	function clear( $index ) {
		unset( $_SESSION['captcha' . $index] );
	}

	function cookiesNeeded() {
		return true;
	}
}

class CaptchaCacheStore {

	function store( $index, $info ) {
		global $wgMemc, $wgCaptchaSessionExpiration;
		$wgMemc->set( wfMemcKey( 'captcha', $index ), $info,
			$wgCaptchaSessionExpiration );
	}

	function retrieve( $index ) {
		global $wgMemc;
		$info = $wgMemc->get( wfMemcKey( 'captcha', $index ) );
		if ( $info ) {
			return $info;
		} else {
			return false;
		}
	}

	function clear( $index ) {
		global $wgMemc;
		$wgMemc->delete( wfMemcKey( 'captcha', $index ) );
	}

	function cookiesNeeded() {
		return false;
	}
}
