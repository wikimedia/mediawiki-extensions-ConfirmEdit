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
$wgConfirmEditMessages['zh-cn'] = array(
	'captcha-short'              => "你编辑的内容中含有一个新的URL链接；为了免受自动垃圾程序的侵扰，你需要输入显示在下面图片中的文字：<br />
([[Special:Captcha/help|这是什么？]])",
	'captchahelp-title'          => 'Captcha 帮助',
	'captchahelp-text'           => "象本站一样，对公众开放编辑的站点经常被垃圾链接骚扰。那些人使用自动化垃圾程序将他们的链接张贴到很多站点。虽然这些链接可以被清除，但是这些东西确实令人十分讨厌。

有时，特别是当给一个页面添加新的网页链接时，本站会让你看一幅有颜色的或者有变形文字的图像，并且要你输入所显示的文字。因为这是难以自动完成的一项任务，它将允许人保存他们的编辑，同时阻止大多数发送垃圾邮件者和其他机器人的攻击。

令人遗憾是，这会使得视力不好的人，或者使用基于文本或者基于声音的浏览器的用户感到不便。而目前我们还没有提供的音频的选择。如果这正好阻止你进行正常的编辑，请和管理员联系获得帮助。

单击你浏览器中的“后退”按钮返回你所编辑的页面。",
	'captcha-createaccount'      => "为了防止程序自动注册，你需要输入以下图片中显示的文字才能注册帐户：<br />
([[Special:Captcha/help|这是什么？]])",
	'captcha-createaccount-fail' => "验证码错误或丢失。",
);
$wgConfirmEditMessages['zh-tw'] = array(
	'captcha-short'              => "你編輯的內容中含有一個新的URL連結；為了免受自動垃圾程式的侵擾，你需要輸入顯示在下面圖片中的文字：<br />
([[Special:Captcha/help|這是什麼？]])",
	'captchahelp-title'          => 'Captcha 說明',
	'captchahelp-text'           => "像本站一樣，對公眾開放編輯的網站經常被垃圾連結騷擾。那些人使用自動化垃圾程序將他們的連結張貼到很多網站。雖然這些連結可以被清除，但是這些東西確實令人十分討厭。

有時，特別是當給一個頁面添加新的網頁連結時，本站會讓你看一幅有顏色的或者有變形文字的圖像，並且要你輸入所顯示的文字。因為這是難以自動完成的一項任務，它將允許人保存他們的編輯，同時阻止大多數發送垃圾郵件者和其他機器人的攻擊。

令人遺憾是，這會使得視力不好的人，或者使用基於文本或者基於聲音的瀏覽器的用戶感到不便。而目前我們還沒有提供的音頻的選擇。如果這正好阻止你進行正常的編輯，請和管理員聯繫獲得幫助。

點擊瀏覽器中的「後退」按鈕返回你所編輯的頁面。",
	'captcha-createaccount'      => "為了防止程式自動註冊，你需要輸入以下圖片中顯示的文字才能註冊帳戶：<br />
([[Special:Captcha/help|這是什麼？]])",
	'captcha-createaccount-fail' => "驗證碼錯誤或丟失。",
);
$wgConfirmEditMessages['zh-yue'] = array(
	'captcha-short'              => "你編輯的內容中含有新的URL連結；為咗避免受到自動垃圾程式的侵擾，你需要輸入顯示喺下面圖片度嘅文字：<br />
([[Special:Captcha/help|呢個係乜嘢嚟？]])",
	'captchahelp-title'          => 'Captcha 幫助',
	'captchahelp-text'           => "就好似呢個wiki咁，對公眾開放編輯嘅網站係會經常受到垃圾連結騷擾。嗰啲人利用自動化垃圾程序將佢哋嘅連結張貼到好多網站。雖然呢啲連結可以被清除，但係呢啲嘢確實令人十分之討厭。

有時，特別係當響一頁添加新嘅網頁連結嗰陣，呢個網站會畀你睇一幅有顏色的或者有變形文字嘅圖像，跟住要你輸入所顯示嘅文字。因為咁係難以自動完成嘅一項任務，它將允許人保存佢哋嘅編輯，同時亦阻止大多數發送垃圾郵件者同其它機械人嘅攻擊。

令人遺憾嘅係，咁會令到視力唔好嘅人，或者利用基於文本或者基於聲音嘅瀏覽器用戶感到不便。而目前我哋仲未能夠提供音頻嘅選擇。如果咁樣咁啱阻止到你進行正常嘅編輯，請同管理員聯繫以獲得幫助。

撳一下響瀏覽器度嘅「後退」掣返去你之前所編輯緊嘅頁面。",
	'captcha-createaccount'      => "為咗防止程式自動註冊，你需要輸入以下圖片中顯示的文字先至能夠註冊得到個戶口：<br />
([[Special:Captcha/help|呢個係乜嘢嚟？]])",
	'captcha-createaccount-fail' => "驗證碼錯誤或者唔見咗。",
);
$wgConfirmEditMessages['zh-hk'] = $wgConfirmEditMessages['zh-tw'];
$wgConfirmEditMessages['zh-sg'] = $wgConfirmEditMessages['zh-cn'];
?>
