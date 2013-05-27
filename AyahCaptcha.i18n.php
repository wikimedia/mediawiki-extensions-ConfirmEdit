<?php
/**
 * Internationalisation file for the AyahCaptcha sub-extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author strix
 */
$messages['en'] = array(
	'ayahcaptcha-edit'               => 'To edit this page, please PlayThru ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-desc'               => 'Provides CAPTCHA techniques to protect against spam and password-guessing',
	'ayahcaptcha-label'              => 'CAPTCHA',
	'ayahcaptcha-addurl'             => 'Your edit includes new external links.
To protect the wiki against automated spam, we kindly ask you to PlayThru in order to save your edit ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-badlogin'           => 'To protect the wiki against automated password cracking, we kindly ask you to PlayThru ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-createaccount'      => 'To protect the wiki against automated account creation, we kindly ask you to PlayThru ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-createaccount-fail' => 'Incomplete PlayThru!',
	'ayahcaptcha-create'             => 'To create the page, please PlayThru ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-sendemail'          => 'To protect the wiki against automated spamming, we kindly ask you to PlayThru ([[Special:Captcha/help|more info]]):',
	'ayahcaptcha-sendemail-fail'     => 'Incomplete PlayThru!',
	'ayahcaptcha-disabledinapi'      => 'This action requires a captcha, so it cannot be performed through the API.',
        'ayahcaptcha-nojs'               => '\'\'\'<span style="font-size: 150%; color: red;">You need to enable JavaScript in order to verify by PlayThru.</span>\'\'\'',
	'ayahcaptchahelp-text'           => "Web sites that accept postings from the public, like this wiki, are often abused by spammers who use automated tools to post their links to many sites.
While these spam links can be removed, they are a significant nuisance.

Sometimes, especially when adding new web links to a page, the wiki may ask you to complete a PlayThru puzzle.  Since this is a task that's hard to automate, it will allow most real humans to make their posts while stopping most spammers and other robotic attackers.  Please contact the  [[{{MediaWiki:Grouppage-sysop}}|site administrators]] for assistance if this is unexpectedly preventing you from making legitimate actions.

Hit the 'back' button in your browser to return to the page editor.",
);

/** Message documentation (Message documentation)
 * @author Aotake
 * @author Hamilton Abreu
 * @author MF-Warburg
 * @author Meithal
 * @author Meno25
 * @author Purodha
 * @author Siebrand
 * @author The Evil IP address
 * @author Toliño
 * @author Umherirrender
 */
$messages['qqq'] = array(
	'ayahcaptcha-edit' => 'This message will be shown when editing if the wiki requires solving a captcha for editing.
See also
*{{msg-mw|Questycaptcha-edit}}
*{{msg-mw|Fancycaptcha-edit}}',
	'ayahcaptcha-desc' => '{{desc}}',
	'ayahcaptcha-label' => 'Label field for input field shown in forms',
	'ayahcaptcha-addurl' => 'The explanation of CAPTCHA shown to users trying to add new external links.
See also
*{{msg-mw|Questycaptcha-addurl}}
*{{msg-mw|Fancycaptcha-addurl}}',
	'ayahcaptcha-badlogin' => 'The explanation of CAPTCHA shown to users failed three times to type in correct password.
See also
*{{msg-mw|Questycaptcha-badlogin}}
*{{msg-mw|Fancycaptcha-badlogin}}',
	'ayahcaptcha-createaccount' => 'The explanation of CAPTCHA shown to users trying to create a new account.
See also
*{{msg-mw|Questycaptcha-createaccount}}
*{{msg-mw|Fancycaptcha-createaccount}}',
	'ayahcaptcha-createaccount-fail' => 'Used as failure message.
{{Related|ConfirmEdit-createaccount-fail}}',
	'ayahcaptcha-create' => 'This message will be shown when creating a page if the wiki requires solving a captcha for that.
See also
*{{msg-mw|Questycaptcha-create}}
*{{msg-mw|Fancycaptcha-create}}',
        'ayahcaptcha-nojs'  => 'This message will be shown when a PlayThru should be shown, but user has JavaScript disabled.',
	'ayahcaptcha-sendemail' => 'Used as footer text.
{{Related|ConfirmEdit-sendemail}}',
	'ayahcaptcha-sendemail-fail' => 'Used as failure message.

See also:
* {{msg-mw|Captcha-createaccount-fail}}',
	'ayahcaptcha-disabledinapi' => 'Used as error message when in the API mode.',
	'ayahcaptchahelp-text' => 'This is the help text shown on [[Special:Captcha/help]].',
);
