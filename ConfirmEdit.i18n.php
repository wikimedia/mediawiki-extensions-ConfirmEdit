<?php
/**
 * Internationalisation file for ConfirmEdit extension.
 *
 * @package MediaWiki
 * @subpackage Extensions
*/

$wgConfirmEditMessages = array();

$wgConfirmEditMessages['en'] = array(
	'captcha-short'              => "Your edit includes new URL links; as a protection against automated spam, you'll need to type in the words that appear in this image:<br />
([[Special:Captcha/help|What is this?]])",
	'captchahelp-title'          => 'Captcha help',
	'captchahelp-text'           => "Web sites that accept postings from the public, like this wiki, are often abused by spammers who use automated tools to post their links to many sites. While these spam links can be removed, they are a significant nuisance.

Sometimes, especially when adding new web links to a page, the wiki may show you an image of colored or distorted text and ask you to type the words shown. Since this is a task that's hard to automate, it will allow most real humans to make their posts while stopping most spammers and other robotic attackers.

Unfortunately this may inconvenience users with limited vision or using text-based or speech-based browsers. At the moment we do not have an audio alternative available. Please contact the site administrators for assistance if this is unexpectedly preventing you from making legitimate posts.

Hit the 'back' button in your browser to return to the page editor.",
	'captcha-createaccount'      => "As a protection against automated spam, you'll need to type in the words that appear in this image to register an account:<br />
([[Special:Captcha/help|What is this?]])",
	'captcha-createaccount-fail' => "Incorrect or missing confirmation code.",
);
$wgConfirmEditMessages['he'] = array(
	'captcha-short'              => "עריכתכם כוללת קישורים חיצוניים חדשים; כהגנה מפני ספאם אוטומטי, עליכם להקליד את המילים המופיעות בתמונה:<br />
([[{{ns:special}}:Captcha/help|מה זה?]])",
	'captchahelp-title'          => 'עזרה במערכת הגנת הספאם',
	'captchahelp-text'           => "פעמים רבות מנצלים ספאמרים אתרים שמקבלים תוכן מהציבור, כמו הוויקי הזה, כדי לפרסם את הקישורים שלהם לאתרים רבים באינטרנט, באמצעות כלים אוטומטיים. אמנם ניתן להסיר את קישורי הספאם הללו, אך זהו מטרד משמעותי.

לעיתים, בעיקר כשאתם מכניסים קישורי אינטרנט חדשים לתוך עמוד, הוויקי עשוי להראות תמונה של טקסט צבעוני או מעוקם ויבקש מכם להקליד את המילים המוצגות. כיוון שזו משימה שקשה לבצעה בצורה אוטומטית, הדבר יאפשר לבני־אדם אמיתיים לשלוח את הדפים, אך יעצור את רוב הספאמרים והמתקיפים הרובוטיים.

לרוע המזל, הדבר עשוי לגרום לאי נוחות למשתמשים עם דפדפן בגרסה מוגבלת, או שמשתמשים בדפדפנים מבוססי טקסט או דיבור. כרגע, אין לנו חלופה קולית זמינה. אנא צרו קשר עם מנהלי האתר לעזרה אם המערכת מונעת מכם באופן בלתי צפוי לבצע עריכות לגיטימיות.

אנא לחצו על הכפתור 'Back' בדפדפן שלכם כדי לחזור לדף העריכה.",
	'captcha-createaccount'      => "כהגנה מפני ספאם אוטומטי, עליכם להקליד את המילים המופיעות בתמונה כדי להירשם לחשבון:<br />
([[{{ns:special}}:Captcha/help|מה זה?]])",
	'captcha-createaccount-fail' => "לא הקלדתם קוד אישור, או שהוא שגוי.",
);
?>
