<?php

# Prelim in-progress code. Proof of concept for framework, not
# intended as a real production captcha system!

# Loader for spam blacklist feature
# Include this from LocalSettings.php

if ( defined( 'MEDIAWIKI' ) ) {

global $wgExtensionFunctions, $wgHooks, $wgGroupPermissions;

$wgExtensionFunctions[] = 'ceSetup';

$wgHooks['EditFilter'][] = 'ceConfirmEditLinks';

$wgGroupPermissions['*'        ]['skipcaptcha'] = false;
$wgGroupPermissions['user'     ]['skipcaptcha'] = false;
$wgGroupPermissions['bot'      ]['skipcaptcha'] = true; // registered bots
$wgGroupPermissions['sysop'    ]['skipcaptcha'] = true;

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
	global $wgMessageCache;
	$wgMessageCache->addMessage('captcha-short', "Your edit includes new URL links; as a protection
		against automated spam, you'll need to enter the answer to this
		simple arithmetic test:" );
	SpecialPage::addPage( new SpecialPage( 'Captcha', false,
		/*listed*/ false, /*function*/ false, /*file*/ false ) );
}

/**
 * Entry point for Special:Captcha
 */
function wfSpecialCaptcha( $par = null ) {
	switch( $par ) {
	case "image":
		return ceShowImage();
	case "help":
	default:
		return ceShowHelp();
	}
}

function ceConfirmEditLinks( &$editPage, $newtext, $section ) {
	$oldtext = ceLoadText( $editPage, $section );
	
	$oldLinks = ceFindLinks( $oldtext );
	$newLinks = ceFindLinks( $newtext );
	
	$addedLinks = array_diff( $newLinks, $oldLinks );
	$numLinks = count( $addedLinks );
	
	/*
	var_dump( $oldtext );
	var_dump( $newtext );
	var_dump( $oldLinks );
	var_dump( $newLinks );
	var_dump( $addedLinks );
	die( '---' );
	*/
	
	if( $numLinks > 0 ) {
		wfDebug( "ConfirmEdit found $numLinks new links...\n" );
		if( ceKeyMatch() ) {
			wfDebug( "ConfirmEdit given proper key from form, passing.\n" );
			return true;
		} else {
			wfDebug( "ConfirmEdit missing form key, prompting.\n" );
			$editPage->showEditForm( 'ceFormCallback' );
			return false;
		}
	} else {
		wfDebug( "ConfirmEdit: no new links.\n" );
		return true;
	}
}

function ceKeyMatch() {
	global $wgUser;
	if( $wgUser->isAllowed( 'skipcaptcha' ) ) {
		wfDebug( "ConfirmEdit: user group allows skipping captcha\n" );
		return true;
	}

	global $wgEmailAuthentication, $ceAllowConfirmedEmail;
	if( $wgEmailAuthentication && $ceAllowConfirmedEmail &&
		$wgUser->isEmailConfirmed() ) {
		wfDebug( "ConfirmEdit: user has confirmed mail, skippng captcha\n" );
		return true;
	}
	
	if( !isset( $_SESSION['ceAnswerVar'] ) ) {
		wfDebug( "ConfirmEdit no session captcha key set, this is new visitor.\n" );
		return false;
	}
	global $wgRequest;
	return $wgRequest->getVal( $_SESSION['ceAnswerVar'] ) == $_SESSION['ceAnswer'];
}

function ceFormCallback( &$out ) {
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
		<p><span id="$source">$test</span> = <input name="$dest" id="$dest" /></p>
END
		);
}

function ceLoadText( $editPage, $section ) {
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

function ceFindLinks( $text ) {
	$regex = '/((?:' . HTTP_PROTOCOLS . ')' . EXT_LINK_URL_CLASS . '+)/';
	
	if( preg_match_all( $regex, $text, $matches, PREG_PATTERN_ORDER ) ) {
		return $matches[1];
	} else {
		return array();
	}
}

function ceShowHelp() {
	global $wgOut, $ceAllowConfirmedEmail;
	$wgOut->setPageTitle( 'Captcha help' );
	$wgOut->addWikiText( <<<END
So what's this wacky captcha thing about?

It's your enemy. It's here to kill you. RUN WHILE YOU STILL CAN
END
		);
}

} # End invocation guard
?>
