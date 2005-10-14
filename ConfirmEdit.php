<?php

# Prelim in-progress code. Proof of concept for framework, not
# intended as a real production captcha system!

# Loader for simple captcha feature
# Include this from LocalSettings.php

if ( defined( 'MEDIAWIKI' ) ) {

global $wgExtensionFunctions, $wgGroupPermissions;

$wgExtensionFunctions[] = 'ceSetup';

$wgGroupPermissions['*'        ]['skipcaptcha'] = false;
$wgGroupPermissions['user'     ]['skipcaptcha'] = false;
$wgGroupPermissions['bot'      ]['skipcaptcha'] = true; // registered bots
$wgGroupPermissions['sysop'    ]['skipcaptcha'] = true;

global $wgCaptcha, $wgCaptchaClass, $wgCaptchaTriggers;
$wgCaptcha = null;
$wgCaptchaClass = 'SimpleCaptcha';

$wgCaptchaTriggers = array();
$wgCaptchaTriggers['edit']   = false; // Would check on every edit
$wgCaptchaTriggers['addurl'] = true;  // Check on edits that add URLs

/**
 * Allow users who have confirmed their e-mail addresses to post
 * URL links without being harassed by the captcha.
 */
global $ceAllowConfirmedEmail;
$ceAllowConfirmedEmail = false;

/**
 * Set up message strings for captcha utilities.
 */
function ceSetup() {
	global $wgMessageCache, $wgHooks, $wgCaptcha, $wgCaptchaClass;
	$wgMessageCache->addMessage('captcha-short', "Your edit includes new URL links; as a protection
		against automated spam, you'll need to enter the answer to this
		simple arithmetic test:" );
	
	SpecialPage::addPage( new SpecialPage( 'Captcha', false,
		/*listed*/ false, /*function*/ false, /*file*/ false ) );
	
	$wgCaptcha = new $wgCaptchaClass();
	$wgHooks['EditFilter'][] = array( &$wgCaptcha, 'confirmEdit' );
}

/**
 * Entry point for Special:Captcha
 */
function wfSpecialCaptcha( $par = null ) {
	global $wgCaptcha;
	switch( $par ) {
	case "image":
		return $wgCaptcha->showImage();
	case "help":
	default:
		return $wgCaptcha->showHelp();
	}
}

class SimpleCaptcha {
	/**
	 * @param EditPage $editPage
	 * @param string $newtext
	 * @param string $section
	 * @return bool true if the captcha should run
	 */
	function shouldCheck( &$editPage, $newtext, $section ) {
		global $wgUser;
		if( $wgUser->isAllowed( 'skipcaptcha' ) ) {
			wfDebug( "SimpleCaptcha: user group allows skipping captcha\n" );
			return false;
		}
	
		global $wgEmailAuthentication, $ceAllowConfirmedEmail;
		if( $wgEmailAuthentication && $ceAllowConfirmedEmail &&
			$wgUser->isEmailConfirmed() ) {
			wfDebug( "SimpleCaptcha: user has confirmed mail, skipping captcha\n" );
			return false;
		}
		
		global $wgCaptchaTriggers;
		if( !empty( $wgCaptchaTriggers['edit'] ) ) {
			// Check on all edits
			wfDebug( "SimpleCaptcha: checking all edits...\n" );
			return true;
		}
		
		if( !empty( $wgCaptchaTriggers['addurl'] ) ) {
			// Only check edits that add URLs
			$oldtext = $this->loadText( $editPage, $section );
			
			$oldLinks = $this->findLinks( $oldtext );
			$newLinks = $this->findLinks( $newtext );
			
			$addedLinks = array_diff( $newLinks, $oldLinks );
			$numLinks = count( $addedLinks );
			
			if( $numLinks > 0 ) {
				wfDebug( "SimpleCaptcha: found $numLinks new links; triggered...\n" );
				return true;
			}
		}
		
		return false;
	}
	
	function confirmEdit( &$editPage, $newtext, $section ) {
		if( $this->shouldCheck( $editPage, $newtext, $section ) ) {
			if( $this->keyMatch() ) {
				wfDebug( "ConfirmEdit given proper key from form, passing.\n" );
				return true;
			} else {
				wfDebug( "ConfirmEdit missing form key, prompting.\n" );
				$editPage->showEditForm( array( &$this, 'formCallback' ) );
				return false;
			}
		} else {
			wfDebug( "ConfirmEdit: no new links.\n" );
			return true;
		}
	}
	
	function keyMatch() {
		if( !isset( $_SESSION['ceAnswerVar'] ) ) {
			wfDebug( "ConfirmEdit no session captcha key set, this is new visitor.\n" );
			return false;
		}
		global $wgRequest;
		return $wgRequest->getVal( $_SESSION['ceAnswerVar'] ) == $_SESSION['ceAnswer'];
	}
	
	function formCallback( &$out ) {
		$source = 'ceSource' . mt_rand();
		$dest = 'ceConfirm' . mt_rand();
		
		$a = mt_rand(0, 100);
		$b = mt_rand(0, 10);
		$op = mt_rand(0, 1) ? '+' : '-';
		
		$test = "$a $op $b";
		$answer = ($op == '+') ? ($a + $b) : ($a - $b);
		$_SESSION['ceAnswer'] = $answer;
		$_SESSION['ceAnswerVar'] = $dest;
		
		
		$out->addWikiText( wfMsg( "captcha-short" ) );	
		$out->addHTML( <<<END
			<p><span id="$source"><label for="$dest">$test</label></span> = <input name="$dest" id="$dest" /></p>
END
			);
	}
	
	function loadText( $editPage, $section ) {
		$rev = Revision::newFromTitle( $editPage->mTitle );
		if( is_null( $rev ) ) {
			return "";
		} else {
			$text = $rev->getText();
			if( $section != '' ) {
				return Article::getSection( $text, $section );
			} else {
				return $text;
			}
		}
	}
	
	function findLinks( $text ) {
		$regex = '/((?:' . HTTP_PROTOCOLS . ')' . EXT_LINK_URL_CLASS . '+)/';
		
		if( preg_match_all( $regex, $text, $matches, PREG_PATTERN_ORDER ) ) {
			return $matches[1];
		} else {
			return array();
		}
	}
	
	function showHelp() {
		global $wgOut, $ceAllowConfirmedEmail;
		$wgOut->setPageTitle( 'Captcha help' );
		$wgOut->addWikiText( <<<END
	So what's this wacky captcha thing about?
	
	It's your enemy. It's here to kill you. RUN WHILE YOU STILL CAN
END
			);
	}
	
}

} # End invocation guard

?>
