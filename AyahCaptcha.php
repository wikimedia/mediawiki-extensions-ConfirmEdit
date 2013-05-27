<?php

/**
 * Captcha class using the Are You A Human service
 *
 * @file
 * @ingroup Extensions
 * @author strix
 * @copyright © 2013 Strix
 * @licence GNU General Public Licence 2.0
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$dir = __DIR__;
require_once $dir . '/ConfirmEdit.php';
$wgCaptchaClass = 'AyahCaptcha';

$wgExtensionMessagesFiles['AyahCaptcha'] = $dir . '/AyahCaptcha.i18n.php';
$wgAutoloadClasses['AyahCaptcha'] = $dir . '/AyahCaptcha.class.php';
