<?php

/**
 * Internationalisation file for the FancyCaptcha plug-in
 *
 * @package MediaWiki
 * @subpackage Extensions
*/

function efFancyCaptchaMessages() {
	return array(
	
/* English */
'en' => array(
'fancycaptcha-edit' => 'Your edit includes new external links. To help protect against automated
spam, please enter the words that appear below in the box ([[Special:Captcha/help|more info]]):',
'fancycaptcha-createaccount' => 'To help protect against automated account creation, please enter the words
that appear below in the box ([[Special:Captcha/help|more info]]):',
),

/* German */
'de' => array(
'fancycaptcha-edit' => 'Ihre Bearbeitung enthält neue externe Links. .
Zum Schutz vor automatisiertem Spamming ist es nötig, dass Sie das nachfolgende Wort
in das darunter erscheinende Feld eintragen. Klicken Sie dann erneut auf „Seite speichern“.
<br />[[{{ns:special}}:Captcha/help|(Was soll das?)]]',
'fancycaptcha-createaccount' => 'Zum Schutz vor automatisierter Anlage von Benutzerkonten
ist es nötig, dass Sie das nachfolgende Wort in das darunter erscheinende Feld eintragen.
[[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]]',
),

	);
}

?>
