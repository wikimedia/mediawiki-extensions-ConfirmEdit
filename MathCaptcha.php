<?php

/**
 * Captcha class using simple sums and the math renderer
 * Not brilliant, but enough to dissuade casual spam bots
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Rob Church <robchur@gmail.com>
 * @copyright © 2006 Rob Church
 * @licence GNU General Public Licence 2.0
 */

if( defined( 'MEDIAWIKI' ) ) {

	class MathCaptcha extends SimpleCaptcha {
	
		/** Validate a captcha response */
		function keyMatch( $req, $info ) {
			return (int)$req->getVal( 'wpCaptchaAnswer' ) == (int)$info['answer'];
		}
		
		/** Produce a nice little form */
		function getForm() {
			list( $sum, $answer ) = $this->pickSum();
			$index = $this->storeCaptcha( array( 'answer' => $answer ) );
			
			$form = '<table><tr><td>' . $this->fetchMath( $sum ) . '</td>';
			$form .= '<td>' . wfInput( 'wpCaptchaAnswer', false, false, array( 'tabindex' => '1' ) ) . '</td></tr></table>';
			$form .= wfHidden( 'wpCaptchaId', $index );
			return $form;
		}
		
		/** Pick a random sum */
		function pickSum() {
			$a = mt_rand( 0, 100 );
			$b = mt_rand( 0, 10 );
			$op = mt_rand( 0, 1 ) ? '+' : '-';
			$sum = "{$a} {$op} {$b} = ";
			$ans = $op == '+' ? ( $a + $b ) : ( $a - $b );
			return array( $sum, $ans );
		}
		
		/** Fetch the math */
		function fetchMath( $sum ) {
			$math = new MathRenderer( $sum );
			$math->setOutputMode( MW_MATH_PNG );
			$html = $math->render();
			return preg_replace( '/alt=".*"/', '', $html );
		}
	
	}
	
} else {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

?>