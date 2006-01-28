<?php
/**
 * Experimental captcha plugin framework.
 * Not intended as a real production captcha system; derived classes
 * can extend the base to produce their fancy images in place of the
 * text-based test output here.
 *
 * Copyright (C) 2005, 2006 Brion Vibber <brion@pobox.com>
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @package MediaWiki
 * @subpackage Extensions
 */

if ( defined( 'MEDIAWIKI' ) ) {

global $wgExtensionFunctions, $wgGroupPermissions;

$wgExtensionFunctions[] = 'ceSetup';

/**
 * The 'skipcaptcha' permission key can be given out to
 * let known-good users perform triggering actions without
 * having to go through the captcha.
 *
 * By default, sysops and registered bot accounts will be
 * able to skip, while others have to go through it.
 */
$wgGroupPermissions['*'            ]['skipcaptcha'] = false;
$wgGroupPermissions['user'         ]['skipcaptcha'] = false;
$wgGroupPermissions['autoconfirmed']['skipcaptcha'] = false;
$wgGroupPermissions['bot'          ]['skipcaptcha'] = true; // registered bots
$wgGroupPermissions['sysop'        ]['skipcaptcha'] = true;

global $wgCaptcha, $wgCaptchaClass, $wgCaptchaTriggers;
$wgCaptcha = null;
$wgCaptchaClass = 'SimpleCaptcha';

/**
 * Currently the captcha works only for page edits.
 *
 * If the 'edit' trigger is on, *every* edit will trigger the captcha.
 * This may be useful for protecting against vandalbot attacks.
 *
 * If using the default 'addurl' trigger, the captcha will trigger on
 * edits that include URLs that aren't in the current version of the page.
 * This should catch automated linkspammers without annoying people when
 * they make more typical edits.
 */
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
 * Regex to whitelist URLs to known-good sites...
 * For instance:
 * $wgCaptchaWhitelist = '#^https?://([a-z0-9-]+\\.)?(wikimedia|wikipedia)\.org/#i';
 */
$wgCaptchaWhitelist = false;

/**
 * Set up message strings for captcha utilities.
 */
function ceSetup() {
	global $wgMessageCache, $wgHooks, $wgCaptcha, $wgCaptchaClass;
	$wgMessageCache->addMessages( array(
		'captcha-short' =>
			"Your edit includes new URL links; as a protection against automated " .
			"spam, you'll need to type in the words that appear in this image:\n" .
			"<br />([[Special:Captcha/help|What is this?]])",
		'captchahelp-title' =>
			'Captcha help',
		'captchahelp-text' =>
			"Web sites that accept postings from the public, like this wiki, " .
			"are often abused by spammers who use automated tools to post their " .
			"links to many sites. While these spam links can be removed, they " .
			"are a significant nuisance." .
			"\n\n" .
			"Sometimes, especially when adding new web links to a page, " .
			"the wiki may show you an image of colored or distorted text and " .
			"ask you to type the words shown. Since this is a task that's hard " .
			"to automate, it will allow most real humans to make their posts " . 
			"while stopping most spammers and other robotic attackers." .
			"\n\n" .
			"Unfortunately this may inconvenience users with limited vision or " .
			"using text-based or speech-based browsers. At the moment we do not " .
			"have an audio alternative available. Please contact the site " .
			"administrators for assistance if this is unexpectedly preventing " .
			"you from making legitimate posts." . 
			"\n\n" .
			"Hit the 'back' button in your browser to return to the page editor." ) );
	
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
	 * Insert a captcha prompt into the edit form.
	 * This sample implementation generates a simple arithmetic operation;
	 * it would be easy to defeat by machine.
	 *
	 * Override this!
	 *
	 * @param OutputPage $out
	 */
	function formCallback( &$out ) {
		$a = mt_rand(0, 100);
		$b = mt_rand(0, 10);
		$op = mt_rand(0, 1) ? '+' : '-';
		
		$test = "$a $op $b";
		$answer = ($op == '+') ? ($a + $b) : ($a - $b);
		
		$index = $this->storeCaptcha( array( 'answer' => $answer ) );
		
		$out->addWikiText( wfMsg( "captcha-short" ) );	
		$out->addHTML( "<p><label for=\"wpCaptchaWord\">$test</label> = " .
			wfElement( 'input', array(
				'name' => 'wpCaptchaWord',
				'id'   => 'wpCaptchaWord',
				'tabindex' => 1 ) ) . // tab in before the edit textarea
			"</p>\n" .
			wfElement( 'input', array(
				'type'  => 'hidden',
				'name'  => 'wpCaptchaId',
				'id'    => 'wpCaptchaId',
				'value' => $index ) ) );
	}
	
	/**
	 * Check if the submitted form matches the captcha session data provided
	 * by the plugin when the form was generated.
	 *
	 * Override this!
	 *
	 * @param WebRequest $request
	 * @param array $info
	 * @return bool
	 */
	function keyMatch( $request, $info ) {
		return $request->getVal( 'wpCaptchaWord' ) == $info['answer'];
	}
	
	// ----------------------------------
	
	/**
	 * @param EditPage $editPage
	 * @param string $newtext
	 * @param string $section
	 * @return bool true if the captcha should run
	 */
	function shouldCheck( &$editPage, $newtext, $section ) {
		$this->trigger = '';
		
		global $wgUser;
		if( $wgUser->isAllowed( 'skipcaptcha' ) ) {
			wfDebug( "ConfirmEdit: user group allows skipping captcha\n" );
			return false;
		}
	
		global $wgEmailAuthentication, $ceAllowConfirmedEmail;
		if( $wgEmailAuthentication && $ceAllowConfirmedEmail &&
			$wgUser->isEmailConfirmed() ) {
			wfDebug( "ConfirmEdit: user has confirmed mail, skipping captcha\n" );
			return false;
		}
		
		global $wgCaptchaTriggers;
		if( !empty( $wgCaptchaTriggers['edit'] ) ) {
			// Check on all edits
			wfDebug( "ConfirmEdit: checking all edits...\n" );
			return true;
		}
		
		if( !empty( $wgCaptchaTriggers['addurl'] ) ) {
			// Only check edits that add URLs
			$oldtext = $this->loadText( $editPage, $section );
			
			$oldLinks = $this->findLinks( $oldtext );
			$newLinks = $this->findLinks( $newtext );
			$unknownLinks = array_filter( $newLinks, array( &$this, 'filterLink' ) );
			
			$addedLinks = array_diff( $unknownLinks, $oldLinks );
			$numLinks = count( $addedLinks );
			
			if( $numLinks > 0 ) {
				global $wgUser, $wgTitle;
				$this->trigger = sprintf( "%dx url trigger by '%s' at [[%s]]: %s",
					$numLinks,
					$wgUser->getName(),
					$wgTitle->getPrefixedText(),
					implode( ", ", $addedLinks ) );
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Filter callback function for URL whitelisting
	 * @return bool true if unknown, false if whitelisted
	 * @access private
	 */
	function filterLink( $url ) {
		global $wgCaptchaWhitelist;
		return !( $wgCaptchaWhitelist && preg_match( $wgCaptchaWhitelist, $url ) );
	}
	
	/**
	 * The main callback run on edit attempts.
	 * @param EditPage $editPage
	 * @param string $newtext
	 * @param string $section
	 * @param bool true to continue saving, false to abort and show a captcha form
	 */
	function confirmEdit( &$editPage, $newtext, $section ) {
		if( $this->shouldCheck( $editPage, $newtext, $section ) ) {
			$info = $this->retrieveCaptcha();
			if( $info ) {
				global $wgRequest;
				if( $this->keyMatch( $wgRequest, $info ) ) {
					$this->log( "passed" );
					return true;
				} else {
					$this->log( "bad form input" );
				}
			} else {
				$this->log( "new captcha session" );
			}
			$editPage->showEditForm( array( &$this, 'formCallback' ) );
			return false;
		} else {
			wfDebug( "ConfirmEdit: no new links.\n" );
			return true;
		}
	}
	
	function log( $message ) {
		wfDebugLog( 'captcha', 'ConfirmEdit: ' . $message . '; ' .  $this->trigger );
	}
	
	/**
	 * Generate a captcha session ID and save the info in PHP's session storage.
	 * (Requires the user to have cookies enabled to get through the captcha.)
	 *
	 * A random ID is used so legit users can make edits in multiple tabs or
	 * windows without being unnecessarily hobbled by a serial order requirement.
	 * Pass the returned id value into the edit form as wpCaptchaId.
	 *
	 * @param array $info data to store
	 * @param string $index optional, to overwrite used session
	 * @return string captcha ID key
	 */
	function storeCaptcha( $info, $index=null ) {
		if( is_null( $index ) ) {
			$index = strval( mt_rand() );
			$info['index'] = $index;
		}
		$_SESSION['captcha' . $index] = $info;
		return $index;
	}
	
	/**
	 * Fetch this session's captcha info.
	 * @return mixed array of info, or false if missing
	 */
	function retrieveCaptcha() {
		global $wgRequest;
		$index = $wgRequest->getVal( 'wpCaptchaId' );
		if( isset( $_SESSION['captcha' . $index] ) ) {
			return $_SESSION['captcha' . $index];
		} else {
			return false;
		}
	}
	
	/**
	 * Retrieve the current version of the page or section being edited...
	 * @param EditPage $editPage
	 * @param string $section
	 * @return string
	 * @access private
	 */
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
	
	/**
	 * Extract a list of all recognized HTTP links in the text.
	 * @param string $text
	 * @return array of strings
	 */
	function findLinks( $text ) {
		$regex = '/((?:' . HTTP_PROTOCOLS . ')' . EXT_LINK_URL_CLASS . '+)/';
		
		if( preg_match_all( $regex, $text, $matches, PREG_PATTERN_ORDER ) ) {
			return $matches[1];
		} else {
			return array();
		}
	}
	
	/**
	 * Show a page explaining what this wacky thing is.
	 */
	function showHelp() {
		global $wgOut, $ceAllowConfirmedEmail;
		$wgOut->setPageTitle( wfMsg( 'captchahelp-title' ) );
		$wgOut->addWikiText( wfMsg( 'captchahelp-text' ) );
	}
	
}

} # End invocation guard

?>
