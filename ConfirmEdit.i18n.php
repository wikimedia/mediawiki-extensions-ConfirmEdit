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
$wgConfirmEditMessages['bs'] = array(
	'captcha-short' => 'Vaša izmjena uključuje nove URL poveznice; kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje su prikazane u slici:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captchahelp-text' => 'Vebsajtovi koji podržavaju slanje sadržaja iz javnosti, kao što je ovaj viki, često zloupotrebljavaju vandali koji koriste automatizovane alate da šalju svoje poveznice ka mnogim sajtovima.  Iako se ove neželjene poveznice mogu ukloniti, one ipak zadaju veliku muku.

Ponekad, pogotovo kad se dodaju nove internet poveznice na stranicu, viki softver Vam može pokazati sliku obojenog i izvrnutog teksta i tražiti da ukucate traženu riječ.  Pošto je teško automatizovati ovakav zadatak, on omogućuje svim pravim ljudima da vrše svoje izmjene, ali će zato spriječiti vandale i ostale robotske napadače.

Nažalost, ovo može da bude nepovoljno za korisnike sa ograničenim vidom i za one koji koriste brauzere bazirane na tekstu ili govoru.  U ovom trenutku, audio alternativa nije dostupna.  Molimo Vas da kontaktirate administratore sajta radi pomoći ako Vas ovo neočekivano ometa u pravljenju dobrih izmjena.

Kliknite \'nazad\' (\'back\') dugme vašeg brauzera da se vratite na polje za unos teksta.',
	'captcha-createaccount' => 'Kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje se nalaze na slici da biste registrovali nalog:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captcha-createaccount-fail' => 'Netačan unos ili nedostatak šifre za potvrđivanje.',
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
$wgConfirmEditMessages['id'] = array(
	'captcha-short'              => "Suntingan Anda menyertakan pralana luar baru. Sebagai perlindungan terhadap ''spam'' otomatis, Anda harus mengetikkan kata atau hasil perhitungan yang tertera berikut ini:<br />
([[Special:Captcha/help|Apa ini?]])",
	'captchahelp-title'          => 'Mengenai Captcha',
	'captchahelp-text'           => "Situs-situs web yang menerima masukan data dari publik, seperti {{ns:project}} ini, kerapkali disalahgunakan oleh pengguna-pengguna yang tidak bertanggungjawab untuk mengirimkan spam dengan menggunakan program-program otomatis. Walaupun spam-spam tersebut dapat dibuang, tetapi tetap saja menimbulkan gangguan berarti.

Ketika menambahkan pranala web baru ke suatu halaman, {{ns:project}} akan menampilkan sebuah gambar tulisan yang terdistorsi atau suatu perhitungan sederhana dan meminta Anda untuk mengetikkan kata atau hasil dimaksud. Karena ini merupakan suatu pekerjaan yang sulit diotomatisasi, pembatasan ini akan mengizinkan hampir semua manusia untuk melakukannya, tapi di sisi lain akan menghentikan kebanyakan aksi spam dan penyerangan yang dilakukan oleh bot otomatis.

Sayangnya, hal ini dapat menimbulkan kesulitan bagi pengguna dengan keterbatasan penglihatan atau pengguna yang menggunakan penjelajah basis teks atau suara. Saat ini, kami tidak memiliki suatu alternatif suara untuk hal ini. Silakan minta bantuan dari pengurus situs jika hal ini menghambat Anda untuk mengirimkan suntingan yang layak.

Tekan tombol 'back' di penjelajah web Anda untuk kembali ke halaman penyuntingan.",
	'captcha-createaccount'      => "Sebagai perlindungan melawan spam, Anda diharuskan untuk mengetikkan kata atau hasil perhitungan di bawah ini di kotak yang tersedia untuk dapat mendaftarkan pengguna baru:<br />
([[Special:Captcha/help|Apa ini?]])",
	'captcha-createaccount-fail' => "Kode konfirmasi salah atau belum diisi.",
);
$wgConfirmEditMessages['nl'] = array(
	'captcha-short'              => "Uw bewerking bevat nieuwe externe links (URL's). Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn:<br />
([[Special:Captcha/help|Wat is dit?]])",
	'captchahelp-title'          => 'Captcha help',
	'captchahelp-text'           => "Websites die vrij te bewerken zijn, zoals deze wiki, worden vaak misbruikt door spammers die er met hun programma's automatisch links op zetten naar vele websites. Hoewel deze externe links weer verwijderd kunnen worden, leveren ze wel veel hinder en administratief werk op.

Soms, en in het bijzonder bij het toevoegen van externe links op pagina's, toont de wiki u een afbeelding met gekleurde of vervormde tekst en wordt u gevraagd de getoonde tekst in te voeren. Omdat dit proces lastig te automatiseren is, zijn vrijwel alleen mensen in staat dit proces succesvol te doorlopen en worden hiermee spammers en andere geautomatiseerde aanvallen geweerd.

Helaas levert deze bevestiging voor gebruikers met een visuele handicap of een tekst- of spraakgebaseerde browser problemen op. Op het moment is er geen alternatief met geluid beschikbaar. Vraag alstublieft assistentie van de sitebeheerders als dit proces u verhindert een nuttige bijdrage te leveren.

Klik op de knop 'terug' in uw browser om terug te gaan naar het tekstbewerkingsscherm.",
	'captcha-createaccount'      => "Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn om uw gebruiker aan te maken:<br />
([[Special:Captcha/help|Wat is dit?]])",
	'captcha-createaccount-fail' => "Onjuiste bevestigingscode of niet ingevuld.",
);
$wgConfirmEditMessages['wa'] = array(
	'captcha-short' => 'Dins vos candjmints i gn a des novelès hårdêyes (URL); po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captchahelp-title' => 'Aidance passete d\' acertinaedje',
	'captchahelp-text' => 'Les waibes k\' acceptèt des messaedjes do publik, come ci wiki chal, sont sovint eployîs pa des må-fjhants spameus, po pleur mete, avou des usteyes otomatikes, des loyéns di rclame viè les sites da zels.
Bén seur, on pout todi les disfacer al mwin, mins c\' est on soyant ovraedje.

Adon, pa côps, copurade cwand vos radjoutez des hårdêyes a ene pådje, ou å moumint d\' ahiver on novea conte sol wiki, on eployrè ene passete d\' acertinaedje, dj\' ô bén k\' on vos mostere ene imådje avou on tecse kitoirdou eyet vs dimander di taper les mots so l\' imådje. Come li ricnoxhance di ç\' tecse la est målåjheye a fé otomaticmint pa on robot, çoula permete di leyî les vraiyès djins fé leus candjmints tot arestant l\' plupårt des spameus et des sfwaitès atakes pa robot.

Målureuzmint çoula apoite eto des målåjhminces po les cis k\' ont des problinmes po vey, ou k\' eployèt des betchteus e môde tecse ou båzés sol vwès. Pol moumint, nos n\' avans nén ene alternative odio. S\' i vs plait contactez les manaedjeus do site po d\' l\' aidance si çoula vos espaitche di fé vos candjmints ledjitimes.

Clitchîz sol boton «En erî» di vosse betchteu waibe po rivni al pådje di dvant.',
	'captcha-createaccount' => 'Po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin po-z ahiver vosse conte, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captcha-createaccount-fail' => 'Li côde d\' acertinaedje est incorek ou mancant.',
);
?>
