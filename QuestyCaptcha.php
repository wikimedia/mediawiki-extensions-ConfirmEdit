<?php
/**
 * A question-based captcha plugin.
 *
 * Copyright (C) 2009 Benjamin Lees <emufarmers@gmail.com>
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
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

require_once __DIR__ . '/ConfirmEdit.php';
$wgCaptchaClass = 'QuestyCaptcha';

global $wgCaptchaQuestions;
$wgCaptchaQuestions = array();

/* Add your questions in LocalSettings.php using this format
$wgCaptchaQuestions = array(
	'A question?' => 'An answer!',
	'What is the capital of France?' => 'Paris', //Answers are normalized to lowercase: Paris and paris are the same
	'What is this wiki's name?' => $wgSitename,
	'2 + 2 ?' => array( '4', 'four' ), //Questions may have many answers
);
*/

$wgMessagesDirs['QuestyCaptcha'] = __DIR__ . '/i18n/questy';
$wgExtensionMessagesFiles['QuestyCaptcha'] = __DIR__ . '/QuestyCaptcha.i18n.php';
$wgAutoloadClasses['QuestyCaptcha'] = __DIR__ . '/QuestyCaptcha.class.php';
