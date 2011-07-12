<?php
/**
 * Asirra CAPTCHA module for the ConfirmEdit MediaWiki extension.
 * @author Bachsau
 *
 * Makes use of the Asirra (Animal Species Image Recognition for
 * Restricting Access) CAPTCHA service, developed by John Douceur, Jeremy
 * Elson and Jon Howell at Microsoft Research.
 *
 * Asirra uses a large set of images from http://petfinder.com.
 *
 * For more information about Asirra, see:
 * http://research.microsoft.com/en-us/um/redmond/projects/asirra/
 *
 * This MediaWiki code is released into the public domain, without any
 * warranty. YOU CAN DO WITH IT WHATEVER YOU LIKE!
 *
 * @file
 * @ingroup Extensions
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/ConfirmEdit.php';
$wgCaptchaClass = 'Asirra';

// Default Asirra options.
// Use LocalSettings.php for any changes
$wgAsirraEnlargedPosition = 'bottom';
$wgAsirraCellsPerRow = '6';
$wgAsirraScriptPath = '';

// AsirraXmlParser initial values
$wgAsirra = array
(
	'inResult' => 0,
	'passed'   => 0
);

$wgExtensionMessagesFiles['Asirra'] = dirname( __FILE__ ) . '/Asirra.i18n.php';
$wgAutoloadClasses['Asirra'] = dirname( __FILE__ ) . '/Asirra.class.php';
