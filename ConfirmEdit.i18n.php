<?php
/**
 * Internationalisation file for ConfirmEdit extension.
 *
 * @addtogroup Extensions
*/

$messages = array();

$messages['en'] = array(
	'captcha-edit' => 'To edit this article, please solve the simple sum below and enter the answer in
the box ([[Special:Captcha/help|more info]]):',
	'captcha-addurl' => 'Your edit includes new external links. To help protect against automated
spam, please solve the simple sum below and enter the answer in the box ([[Special:Captcha/help|more info]]):',
	'captcha-badlogin' => 'To help protect against automated password cracking, please solve the simple sum
below and enter the answer in the box ([[Special:Captcha/help|more info]]):',
	'captcha-createaccount' => 'To help protect against automated account creation, please solve the simple sum
	below and enter the answer in the box ([[Special:Captcha/help|more info]]):',
	'captcha-createaccount-fail' => "Incorrect or missing confirmation code.",
	'captcha-create' => 'To create the page, please solve the simple sum below and enter
the answer in the box ([[Special:Captcha/help|more info]]):',
	'captchahelp-title'          => 'Captcha help',
	'captchahelp-cookies-needed' => "You will need to have cookies enabled in your browser for this to work.",
	'captchahelp-text'           => "Web sites that accept postings from the public, like this wiki, are often abused by spammers who use automated tools to post their links to many sites. While these spam links can be removed, they are a significant nuisance.

Sometimes, especially when adding new web links to a page, the wiki may show you an image of colored or distorted text and ask you to type the words shown. Since this is a task that's hard to automate, it will allow most real humans to make their posts while stopping most spammers and other robotic attackers.

Unfortunately this may inconvenience users with limited vision or using text-based or speech-based browsers. At the moment we do not have an audio alternative available. Please contact the site administrators for assistance if this is unexpectedly preventing you from making legitimate posts.

Hit the 'back' button in your browser to return to the page editor.",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
# Syntax is as follows: 
#   * Everything from a "#" character to the end of the line is a comment
#   * Every non-blank line is a regex fragment which will only match hosts inside URLs
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['af'] = array(
	'captcha-edit'               => "U wysiging bevat nuwe webskakels. Neem kennis dat blote reklame van u werf, produk of besigheid as vandalisme beskou kan word. As beskerming teen outomatiese gemorsbydraes, sal u die woorde wat onder verskyn in die prentjie moet intik: <br />([[Spesiaal:Captcha/help|Wat is hierdie?]])",
	'captcha-addurl'               => "U wysiging bevat nuwe webskakels. Neem kennis dat blote reklame van u werf, produk of besigheid as vandalisme beskou kan word. As beskerming teen outomatiese gemorsbydraes, sal u die woorde wat onder verskyn in die prentjie moet intik: <br />([[Spesiaal:Captcha/help|Wat is hierdie?]])",
	'captcha-create'               => "U wysiging bevat nuwe webskakels. Neem kennis dat blote reklame van u werf, produk of besigheid as vandalisme beskou kan word. As beskerming teen outomatiese gemorsbydraes, sal u die woorde wat onder verskyn in die prentjie moet intik: <br />([[Spesiaal:Captcha/help|Wat is hierdie?]])",
	'captchahelp-title'          => 'Captcha-hulp',
	'captchahelp-text'           => "Webwerwe wat bydraes van die publiek aanvaar (soos hierdie wiki) word soms lastig geval deur kwaaddoeners met programme wat outomaties klomp skakels plak in die werf. Alhoewel hierdie gemors verwyder kan word, is dit lastig. In party gevalle, veral as u webskakels by 'n blad voeg, sal die wiki dalk 'n beeld met verwronge teks vertoon en vra dat u die woorde daarin intik. Omdat hierdie taak moeilik geoutomatiseer word, laat dit meeste regte mense toe om bydraes te maak terwyl dit meeste kwaaddoeners stop. Hierdie kan ongelukkig lastig wees vir mense met beperkte sig, of diegene wat teks- of spraakgebaseerde blaaiers gebruik. Tans is daar nog nie 'n klankalternatief beskikbaar nie. Kontak asseblief die werfadministrateurs vir hulp as hierdie u onverwags belemmer om legitieme bydraes te maak. Gebruik die \"terug\"-knoppie van u blaaier om na die vorige blad terug te keer.",
	'captcha-createaccount'      => "As 'n beskerming teen geoutomatiseerde gemors, tik asseblief die woorde wat in die beeld verskyn in om 'n rekening te skep: <br />([[Special:Captcha/help|Wat is hierdie?]])",
	'captcha-createaccount-fail' => "Verkeerde of geen bevestigingkode.",
);

$messages['am'] = array(
	'captcha-createaccount'       => 'ያልተፈለገ የመኪናነት አባልነት ለመከላከል፥ አባል ለመሆን በዚህ ምስል የታዩት እንግሊዝኛ ቃላት ወይም ቁጥር መልስ በትክክል መጻፍ ግዴታ ነው። ([[Special:Captcha/help|ይህ ምንድነው?]]):',
);

/** Arabic (العربية)
 * @author Mido
 * @author Meno25
 * @author OsamaK
 */
$messages['ar'] = array(
	'captcha-edit'               => 'يحتوي تعديلك هذا على وصلات خارجية. للحماية من السخام الأوتوماتيكي، قم من فضلك بحل المسألة الرياضية البسيطة أدناه وأدخل الحل في الصندوق ([[Special:Captcha/help|مزيد من المعلومات]]):',
	'captcha-addurl'             => 'تعديلك يحتوي على وصلات خارجية جديدة. للمساعدة في الحماية من السخام الأوتوماتيكي، من فضلك حل عملية الجمع بالأسفل و أضف الحل في الصندوق  ([[Special:Captcha/help|معلومات إضافية]]):',
	'captcha-badlogin'           => 'للمساعدة في الحماية ضد سرقة كلمات السر، من فضلك حل عملية الجمع البسيطة بالأسفل وأدخل الحل في الصندوق ([[Special:Captcha/help|معلومات إضافية]]):',
	'captcha-createaccount'      => 'كحماية ضد العمليات التخريبية، يجب أن تدخل ناتج العملية الحسابية التالية لكي تنشأ حسابا: <br />([[Special:Captcha/help|ما هذا؟]])',
	'captcha-createaccount-fail' => 'كود غير مطابق أو لم تقم بإدخاله.',
	'captcha-create'             => 'من فضلك قم بحل المسألة الرياضية التالية لإنشاء هذه الصفحة وأدخل
الجواب في الصندوق ([[Special:Captcha/help|ما هذا؟]]):',
	'captchahelp-title'          => 'مساعدة الكابتشا',
	'captchahelp-cookies-needed' => 'ستحتاج إلى أن تكون الكوكيز مفعلة في متصفحك لكي يعمل هذا',
	'captchahelp-text'           => "عادة ما يتم في المواقع التي تقبل الردود والرسائل من العامة، كهذا الويكي، تخريب الموقع عن طريق الأشخاص الذين يستعملون آليات معينة لإرسال وصلاتهم لمواقع متعددة بصورة آلية. وعلى الرغم من أن هذا يمكن إزالته ولكنه مزعج للغاية.

في بعض الأحيان، خصوصا عند إضافة وصلات لصفحة، ربما يعرض الويكي صورة ملونة أو مشوشة ويطلب منك إدخال كلمات موجودة بالصورة أو يعرض عليك مسألة رياضية عشوائية ويطلب منك حلها. ولأن هذه المهمة صعبة للغاية لأن يقوم بها برنامج، سيسمح هذا للأشخاص الآدميين بإضافة تحريراتهم بينما ستوقف البرامج التخريبية والهجمات الآلية الأخرى.

للأسف سيكون هذا صعبا بالنسبة لمستخدمي المتصفحات المحدودة أو التي تعتمد على النصوص فقط أو قراءة النصوص. في الوقت الحالي لا يوجد لدينا بديل سمعي. من فضلك راسل مديري الموقع للمساعدة إذا كان هذا الأمر يمنعك من التعديل ووضع وصلات قانونية.

إذا كنت تحرر صفحة معينة: اضغط زر 'العودة' في متصفحك للعودة إلى التحرير.",
	'captcha-addurl-whitelist'   => ' #<!-- leave this line exactly as it is --> <pre> 
# الصيغة كالتالي: 
#   * كل شيء من علامة "#" لنهاية السطر تعليق
#   * كل سطر غير فارغ هو قطعة ريجيكس والتي توافق فقط المعيلين داخل المسارات
 #</pre> <!-- leave this line exactly as it is -->',
);

/** Asturian (Asturianu)
 * @author SPQRobin
 */
$messages['ast'] = array(
	'captcha-edit'               => "Pa editar esti artículu, por favor resuelvi la suma simple d'embaxo y pon la rempuesta nel caxellu ([[Special:Captcha/help|más información]]):",
	'captcha-addurl'             => "La to edición inclúi nuevos enllaces esternos. P'aidar a protexer escontra'l spam automatizáu, por favor resuelvi la suma simple d'embaxo y pon la rempuesta nel caxellu ([[Special:Captcha/help|más información]]):",
	'captcha-createaccount'      => "P'aidar a protexer escontra la creación automática de cuentes, por favor resuelvi la suma simple d'embaxo y pon la rempuesta nel caxellu ([[Special:Captcha/help|más información]]):",
	'captcha-createaccount-fail' => 'Códigu de confirmación incorreutu o ausente.',
	'captcha-create'             => "Pa crear la páxina, por favor resuelvi la suma simple d'embaxo y pon la rempuesta nel caxellu ([[Special:Captcha/help|más información]]):",
	'captchahelp-title'          => 'Aida tocante al captcha',
	'captchahelp-cookies-needed' => "Has tener les cookies habilitaes nel to navegador pa que'l sistema funcione.",
	'captchahelp-text'           => "Los sitios web qu'aceuten mensaxes del publicu, como esta uiquipedia, son davezu oxetu d'abusu por spammers qu'usen programes pa incluyir los sos enllaces automáticamente. Si bien estos enllaces spam puen quitase, son una bona molestia.

Dacuando, especialmente cuando amiesta nuevos enllaces web nuna páxina, Uiquipedia pue amosate una imaxe de testu coloreáu o distorsionáu y va pidite qu'escribas les pallabres amosaes. Yá qu'esti ye un llabor difícil d'automatizar, permitirá a los más de les persones reales unviar los sos testos, al empar que detién los más de los spammers y otros atacantes automáticos.

Desafortunadamente esto pue suponer un inconveniente pa los usuarios con visión llimitada o qu'usen navegadores de testu o voz. De momentu nun tenemos disponible una alternativa por audiu. Por favor, contauta colos alministradores del sitiu pa pidir aida si esto t'impide facer ediciones llexítimes.

Calca nel botón 'atrás' del to navegador pa volver a la páxina d'edicion.",
);

$messages['bcl'] = array(
	'captcha-create'              => 'Tangarig maggibo an pahina, paki simbagan an simpleng suma sa ibaba asin ikaag an simbag sa laog kan kahon ([[Special:Captcha/help|more info]]):',
	'captchahelp-title'           => 'Tabang sa Captcha',
);

/** Bulgarian (Български)
 * @author DCLXVI
 * @author Spiritia
 */
$messages['bg'] = array(
	'captcha-edit'               => 'Редактирането на тази статия изисква потребителите да въведат отговора на задачата по-долу в текстовата кутия ([[Special:Captcha/help|повече информация]]):',
	'captcha-addurl'             => 'Тази редакция съдържа нови външни препратки. Като защита срещу автоматизиран спам системата изисква потребителите да въведат отговора на задачата по-долу в текстовата кутия ([[Special:Captcha/help|повече информация]]):',
	'captcha-badlogin'           => 'Като защита срещу автоматизирано компрометиране на пароли, системата изисква потребителите да въведат отговора на задачата по-долу в текстовата кутия ([[Special:Captcha/help|повече информация]]):',
	'captcha-createaccount'      => 'Като защита от автоматизирани регистрации, системата изисква при регистриране на потребителска сметка потребителите да въведат отговора на задачата по-долу в текстовата кутия ([[Special:Captcha/help|повече информация]]):',
	'captcha-createaccount-fail' => 'Грешен или липсващ код за потвърждение.',
	'captchahelp-text'           => "Уеб сайтовете, които позволяват свободно да се редактира и добавя ново съдържание (като това уики), често са обект на атаки от страна на спамъри, които използват средства за автоматизирано редактиране за публикуване на препратки към много сайтове. Въпреки че тези препратки могат да бъдат премахнати, те са особено неприятни за потребителите. 

Понякога, особено когато се добавят нови препратки към страниците, е възможно уикито да покаже картинка с текст, който трябва да бъде въведен в посоченото поле. Тъй като това е стъпка, която е трудно да бъде прескочена при автоматизирано редактиране, тя затруднява и спира повечето спамъри и роботи, но допуска истинските потребителите да правят редакции. 

За съжаление тази стъпка може да затрудни незрящи потребители или потребители, които използват текстови или речеви браузъри. За момента системата не разполага с възможност за гласова алтернатива. Обърнете се за помощ към администратор на сайта, ако това изискване на системата ви затруднява да допринасяте легитимно. 

Натиснете бутона 'back' на вашия браузър, за да се върнете към редактора на страници.",
);

$messages['br'] = array(
	'captcha-edit'                => 'Liammoù diavaez nevez zo bet ouzhpennet ganeoc\'h. A-benn en em wareziñ diouzh ar spam emgefre skrivit disoc\'h ar jedadennig eeun-mañ er stern : <br />([[Special:Captcha/help|Petra eo se?]])',
	'captcha-addurl'                => 'Liammoù diavaez nevez zo bet ouzhpennet ganeoc\'h. A-benn en em wareziñ diouzh ar spam emgefre skrivit disoc\'h ar jedadennig eeun-mañ er stern : <br />([[Special:Captcha/help|Petra eo se?]])',
	'captcha-create'                => 'Liammoù diavaez nevez zo bet ouzhpennet ganeoc\'h. A-benn en em wareziñ diouzh ar spam emgefre skrivit disoc\'h ar jedadennig eeun-mañ er stern : <br />([[Special:Captcha/help|Petra eo se?]])',
	'captcha-createaccount'       => 'A-benn hor skoazellañ d\'en em wareziñ diouzh ar c\'hrouiñ kontoù emgefre, skrivit ar gerioù a zeu war wel er stern-mañ evit enrollañ ho kont : <br />([[Special:Captcha/help|Petra eo se?]])',
	'captcha-createaccount-fail'  => 'Mankout a ra ar c\'hod kadarnaat pe fall eo.',
	'captchahelp-title'           => 'Skoazell Capcha',
	'captchahelp-text'            => 'Alies e vez taget al lec\'hiennoù a zegemer kemennadennoù a-berzh an holl, evel ar wiki-mañ, gant ar spamerien a implij ostilhoù emgefre evit postañ o liammoù war lec\'hiennoù a bep seurt. Diverket e c\'hallont bezañ, gwir eo, kazus-mat ez int memes tra. A-wechoù, dreist-holl pa vez ouzhpennet liammoù Web nevez war ur bajenn, e c\'hallo ar wiki-mañ diskouez deoc\'h ur skeudenn warni un tamm testenn liv pe a-dreuz. Goulennet e vo diganeoc\'h skrivañ ar gerioù deuet war wel. Un trevell start da emgefrekaat eo hemañ. Gant se e c\'hallo an implijerien wirion postañ ar pezh a fel ldezho tra ma vo lakaet un harz d\'an darn vrasañ eus ar spamerien pe d\'an dagerien robotek all. Koulskoude e c\'hallo an implijerien berr o gweled pe ar re a implij merdeerioù diazezet war ar skrid pe war ar vouezh bezañ strafuilhet gant se. N\'omp ket evit kinnig un diskoulm dre glevet evit c\'hoazh. Kit e darempred gant merourien al lec\'hienn m\'hoc\'h eus diaesterioù evit kemer perzh abalamour d\'an teknik-se. Pouezit war bouton \'kent\' ho merdeer evit distreiñ d\'ar bajenn gemmañ.',
);
$messages['bs'] = array(
	'captcha-edit'  => 'Vaša izmjena uključuje nove URL poveznice; kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje su prikazane u slici:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captcha-addurl'  => 'Vaša izmjena uključuje nove URL poveznice; kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje su prikazane u slici:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captcha-create'  => 'Vaša izmjena uključuje nove URL poveznice; kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje su prikazane u slici:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captchahelp-text' => 'Vebsajtovi koji podržavaju slanje sadržaja iz javnosti, kao što je ovaj viki, često zloupotrebljavaju vandali koji koriste automatizovane alate da šalju svoje poveznice ka mnogim sajtovima.  Iako se ove neželjene poveznice mogu ukloniti, one ipak zadaju veliku muku.

Ponekad, pogotovo kad se dodaju nove internet poveznice na stranicu, viki softver Vam može pokazati sliku obojenog i izvrnutog teksta i tražiti da ukucate traženu riječ.  Pošto je teško automatizovati ovakav zadatak, on omogućuje svim pravim ljudima da vrše svoje izmjene, ali će zato spriječiti vandale i ostale robotske napadače.

Nažalost, ovo može da bude nepovoljno za korisnike sa ograničenim vidom i za one koji koriste brauzere bazirane na tekstu ili govoru.  U ovom trenutku, audio alternativa nije dostupna.  Molimo Vas da kontaktirate administratore sajta radi pomoći ako Vas ovo neočekivano ometa u pravljenju dobrih izmjena.

Kliknite \'nazad\' (\'back\') dugme vašeg brauzera da se vratite na polje za unos teksta.',
	'captcha-createaccount' => 'Kao zaštita od automatizovanog vandalizma, moraćete da ukucate riječi koje se nalaze na slici da biste registrovali nalog:
<br />([[{{ns:special}}:Captcha/help|Šta je ovo?]])',
	'captcha-createaccount-fail' => 'Netačan unos ili nedostatak šifre za potvrđivanje.',
);
$messages['ca'] = array(
	'captcha-edit'                => 'Per a poder editar aquest article cal que resolgueu aquesta simple suma i introduïu el resultat en el quadre ([[Special:Captcha/help|més informació]]):',
	'captcha-addurl'              => 'La vostra edició conté enllaços externs nous. Com a protecció contra la brossa de propaganda automàtica, cal que resolgueu aquesta simple suma i introduïu el resultat en el quadre a continuació ([[Special:Captcha/help|més informació]]):',
	'captcha-badlogin'             => 'Per a ajudar en la protecció contra l\'obtenció automatitzada de contrasenyes haureu de resoldre la suma que apareix a continuació ([[Special:Captcha/help|més informació]]):',
	'captcha-createaccount'       => 'Com a protecció contra la creació automàtica de comptes d\'usuari necessitem que resolgueu aquesta simple suma i introduïu el resultat en el quadre a continuació ([[Special:Captcha/help|més informació]]):',
	'captcha-createaccount-fail'  => 'Manca el codi de confirmació, o bé és incorrecte.',
	'captcha-create'              => 'La vostra edició conté enllaços externs nous. Com a protecció contra la brossa de propaganda automàtica, cal que resolgueu aquesta simple suma i introduïu el resultat en el quadre a continuació ([[Special:Captcha/help|més informació]]):',
	'captchahelp-title'           => 'Ajuda amb el sistema captcha',
	'captchahelp-cookies-needed'  => 'Heu d\'activar les galetes al vostre navegador per a que funcioni.',
	'captchahelp-text'            => 'Els webs que accepten la publicació de missatges per part del seu públic, com aquesta wiki, són sovint víctimes de spam per part de robots automàtics que hi posen enllaços cap als seus webs. Aquests enllaços poden anar essent esborrats, però són un important destorb.

Quan creeu nous comptes d\'usuari o voleu afegir enllaços a una pàgina se us pot demanar que respongueu una pregunta fàcil, una suma o que digueu quina paraula apareix en una imatge distorsionada. Aquestes tasques són molt difícils de fer per a un programa automàtic i per tant permet que la majoria d\'usuaris humans puguin introduir la informació que creguin i alhora atura la majoria d\'atacants robots.

Malauradament, aquest sistema pot suposar un inconvenient per a usuaris amb problemes de visió o que utilitzin navegadors de text simple o de veu. Actualment no disposem de cap alternativa auditiva disponible. Contacteu els administradors del web si aquests sistema us impedeix de fer edicions legítimes.

Necessitareu tenir les galetes activades en el vostre navegador per a que funcioni.

Cliqueu el botó de retrocedir del vostre navegador per a tornar al formulari.',
);
$messages['cdo'] = array(
	'captcha-edit' => 'Nṳ̄ gă-tiĕng lāu sĭng gì nguôi-buô lièng-giék. Ôi lāu ê̤ṳ-huòng ô tiàng-sê̤ṳ cê̤ṳ-dông huák-buó bóng-só̤ séng-sék (\'\'spam\'\'), kī-dâe̤ng nṳ̄ gié-sáung â-dā̤ gāng-dăng gì gă-huák, gái ciŏng dák-áng siā diē gáh-gáh diē-sié ([[Special:Captcha/help|gáing sâ̤ séng-sék]]):',
	'captchahelp-title'          => 'Captcha bŏng-cô',
	'captchahelp-text'           => "Chiông wiki dēng kăi-huóng gì uōng-câng sèu-sèu ké̤ṳk bóng-só̤ séng-sék huák-buó-nè̤ng (\'\'spammer\'\') páh-chā: ĭ-gáuk-nè̤ng kĕk cê̤ṳ-dông-huá gì gă-sĭ táik bóng-só̤ guōng-gó̤ lièng gáu ĭ gì uōng-câng. Chŭi-iòng cī piĕ bóng-só̤ séng-sék â̤ dù lâi gì, dáng-sê iâ cêng-go̤ tō̤-iéng.

Ô sèng-hâiu, dĕk-biék sê găk nṳ̄ gă-tiĕng sĭng gì nguôi-buô lièng-giék gáu wiki gì sèng-hâiu, wiki â̤ hiēng-sê sáik-ké gáuk-iông hĕ̤k-ciā sê hìng-câung ô gāi-biéng gì ùng-cê dù-chiông, giéu nṳ̄ páh diē nṳ̄ sū káng giéng gì cê hĕ̤k sṳ̀ (hô̤ lō̤ \"captcha\"). Ĭng-ôi gĭ-ké-nè̤ng mâ̤ chiàng ciā êng-ô, gó-chṳ̄ cêu â̤ huòng-cī duâi-buô-hông iù gĭ-ké-nè̤ng huák-buó bóng-só̤ séng-sék (bók-guó, cĭng nè̤ng huák-buó  bóng-só̤ séng-sék, ciā huŏng-huák huòng mâ̤ lì).

Cĭng mì-hâng, dó̤i hī piĕ mĕ̤k-ciŭ mâ̤ hō̤, hĕ̤k-ciā sê sāi-ê̤ṳng gĭ-ṳ̀ ùng-buōng (\'\'text-based\'\') hĕ̤k gĭ-ṳ̀ siăng-ĭng (\'\'speech-based\'\') gì báuk-lāng-ké (\'\'browser\'\') gì ê̤ṳng-hô lì gōng, cūng-kuāng cĭng mâ̤ lê-biêng. Cī òng, nàng-gă gó mò̤ 1 cṳ̄ng gá hō̤ gì huŏng-huák. Nâ sê gōng, cuòi īng-hiōng nṳ̄ ciáng-siòng piĕng-cĭk, chiāng nṳ̄ lièng-hiê guāng-lī-uòng.

Ôi lāu captcha gì ciáng-siòng gĕ̤ng-cáuk, nṳ̄ diŏh páh kŭi báuk-lāng-ké gì cookie.

Buóh diōng kó̤ piĕng-cĭk hiĕk-miêng, áik \"diōng kó̤ sèng 1 hiĕk\" (\'\'back\'\').",
);
$messages['cs'] = array(
	'captcha-badlogin'            => 'V rámci ochrany před automatickým pokusům uhodnout heslo musíte vyřešit následující jednoduchý součet a napsat výsledek. ([[Special:Captcha/help|Co tohle znamená?]]):',
	'captchahelp-cookies-needed' => "Musíte mít zapnuty cookies ve svém prohlížeči.",
	'captcha-edit'               => 'Abyste mohli editovat tuto stránku, musíte vyřešit následující jednoduchý součet a napsat výsledek. ([[Special:Captcha/help|Co tohle znamená?]])',
	'captcha-addurl'             => 'Vaše editace obsahuje nové odkazy formou URL; v zájmu ochrany před automatickým spamováním musíte vyřešit následující jednoduchý součet a napsat výsledek. ([[Special:Captcha/help|Co tohle znamená?]])',
	'captcha-create'             => 'Abyste mohli založit stránku musíte vyřešit následující jednoduchý součet a napsat výsledek. ([[Special:Captcha/help|Co tohle znamená?]])',
	'captchahelp-title'          => 'Nápověda ke captcha',
	'captchahelp-text'           => "Webové stránky, do kterých mohou přispívat jejich návštěvníci (jako například tato wiki), jsou často terčem spammerů, kteří pomocí automatických nástrojů vkládají své odkazy na velké množství stránek. Přestože lze tento spam odstranit, představuje nepříjemné obtěžování.

Někdy, zvláště při přidávání nových webových odkazů, vám může wiki ukázat obrázek barevného či pokrouceného textu a požádat vás o opsání zobrazených znaků. Jelikož takovou úlohu lze jen těžko automatizovat, skuteční lidé mohou dále přispívat, zatímco většina spammerů a jiných robotických útočníků to zastaví.

Bohužel to však může představovat nepříjemný problém pro uživatele se zrakovým postižením či uživatele používající textové prohlížeče či hlasové čtečky. V současné době nemáme alternativní zvukovou verzi. Kontaktujte laskavě správce serveru, pokud vám to brání v užitečných příspěvcích a potřebujete pomoc.

Pro návrat na předchozí stránku stiskněte ve svém prohlížeči tlačítko „zpět“.",
	'captcha-createaccount'      => 'V rámci ochrany před automatickým vytvářením účtů musíte pro provedení registrace vyřešit následující jednoduchý součet a napsat výsledek. ([[Special:Captcha/help|Co tohle znamená?]])',
	'captcha-createaccount-fail' => 'Chybějící či neplatný potvrzovací kód.',
);
$messages['cy'] = array(
	'captcha-edit'               => "Mae eich golygiad yn cynnwys cysylltiadau URL newydd. Er mwyn profi nad ydych yn beiriant sbam, teipiwch y geiriau canlynol yn y blwch isod os gwelwch yn dda. <br />([[Arbennig:Captcha/help|Mwy o wybodaeth]])",
	'captcha-addurl'               => "Mae eich golygiad yn cynnwys cysylltiadau URL newydd. Er mwyn profi nad ydych yn beiriant sbam, teipiwch y geiriau canlynol yn y blwch isod os gwelwch yn dda. <br />([[Arbennig:Captcha/help|Mwy o wybodaeth]])",
	'captcha-create'               => "Mae eich golygiad yn cynnwys cysylltiadau URL newydd. Er mwyn profi nad ydych yn beiriant sbam, teipiwch y geiriau canlynol yn y blwch isod os gwelwch yn dda. <br />([[Arbennig:Captcha/help|Mwy o wybodaeth]])",
	'captchahelp-title'          => 'Cymorth "captcha"',
	'captchahelp-text'           => "Yn anffodus, mae safleoedd gwe fel Wicipedia, sy'n caniatau i'r cyhoedd ysgrifennu iddi, yn darged beunyddiol i sbamwyr sy'n defnyddio rhaglenni arbennig i bostio eu cysylltiadau. Gellir dileu'r dolenni o'r dudalen, ond mae hyn yn drafferth mawr. O dro i dro, fe fydd y safle hon yn dangos delwedd o destun, ac fe fydd yn rhaid i chi deipio'r geiriau a ddangosir. Mae hyn yn dasg anodd iawn i ragenni cyfrifiadurol, felly dylai golygwyr go iawn gyflawni'r dasg yn di-drafferth, yn wahanol i'r rhaglenni sbam. Mae hyn yn amlwg yn creu trafferthion i'r sawl sydd yn defnyddio porwyr testun neu sydd yn colli eu golwg. Ar hyn o bryd nid oes fersiwn sain ar gael. Cysylltwch â gweinyddwyr y safle os ydi'r nodwedd hon yn eich rhwystro rhag ychwanegu golygiadau dilys. Gwasgwch botwm 'nôl' eich porwr er mwyn dychwelyd.",
	'captcha-createaccount'      => "Teipiwch y geiriau sy'n ymddangos yn y ddelwedd isod os gwelwch yn dda. Mae'r nodwedd hon yn rhwystro rhaglenni sbam rhag creu cyfrifon i'w hunain. <br />([[Arbennig:Captcha/help|Mwy o wybodaeth]])",
	'captcha-createaccount-fail' => "Côd cadarnhau ar goll neu'n anghywir.",
);
$messages['da'] = array(
	'captcha-edit' => 'For at redigere denne side, skal du give svaret på regnestyket nedenfor, og angive resultatet i feltet under det. ([[Special:Captcha/help|mere information]]):',
	'captcha-addurl' => 'Din redigering tilføjer nye eksterne henvisninger til artiklen. Som beskyttelse mod automatiseret spam, skal du give svaret på regnestyket nedenfor, og angive resultatet i feltet under det. ([[Special:Captcha/help|mere information]]):',
	'captcha-badlogin' => 'For at beskytte mod automatiserede gæt på kodeord, skal du give svaret på regnestyket nedenfor, og angive resultatet i feltet under det. ([[Special:Captcha/help|mere information]]):',
	'captcha-createaccount' => 'For at beskytte mod automatisk oprettelse af brugernavne, skal du give svaret på regnestyket nedenfor, og angive resultatet i feltet under det. ([[Special:Captcha/help|mere information]]):',
	'captcha-createaccount-fail' => "Forkert eller manglende kodeord.",
	'captcha-create' => 'For at oprette en ny side, skal du give svaret på regnestyket nedenfor, og angive resultatet i feltet under det. ([[Special:Captcha/help|mere information]]):',
	'captchahelp-title'          => 'Captcha-hjælp',
	'captchahelp-cookies-needed' => "Din browser skal understøtte cookies, før dette kan gennemføres.",
	'captchahelp-text'           => "Websites der accepterer indhold fra offentligheden, bliver ofte udsat for angreb fra spammere. Disse angreb sker med automatiske værktøjer, der anbringer de samme links på et stort antal websites på kort tid. Selvom disse links kan fjernes, er de en vedligeholdelsesmæssig byrde.

I visse tilfælde, specielt når der tilføjes nye links til denne wiki, vil softwaren vise dig et billede af et stykke frovredet og sløret tekst. Du skal indtaste det ord, der vises, før du kan gennemføre handlingen. Formålet er at skelne mellem mennesker og automatiserede værktøjer, da de sidste har meget svært ved at genkende ordene.

Desværre kan dette medføre problemer for svagtseende brugere, og brugere der bruger software der oplæser indholdet af siden. For øjeblikket findes der ikke et lydbaseret alternativ. Kontakt venligst en administrator med henblik på at få hjælp, hvis dette forhindrer tilføjelsen af godartet materiale.

Tryk på 'tilbage'-knappen i din browser for at returnere til redigeringssiden.",
	'captcha-addurl-whitelist' => '
 #<!-- Undlad at rette denne linie --> <pre> 
# Vejledning: 
#   * Alt fra et "#"-tegn til slutningen af en linie er en kommentar
#   * Alle ikke-blanke linier benyttes som regulært udtryk, der anvendes på hostnavne i URLer
 #</pre> <!-- Undlad at rette denne linie -->',
);
$messages['de'] = array(
	'captcha-edit'		     => "Zur Bearbeitung der Seite löse die nachfolgende Rechenaufgabe und trage das Ergebnis in das Feld unten ein [[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]].",
	'captcha-addurl'	     => "Deine Bearbeitung enthält neue externe Links. Zum Schutz vor automatisiertem Spamming löse die nachfolgende Rechenaufgabe und trage das Ergebnis in das Feld unten ein. Klicke dann erneut auf „Seite speichern“ [[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]].",
	'captcha-badlogin'            => 'Zum Schutz vor einer Kompromittierung deines Benutzerkontos löse die nachfolgende Rechenaufgabe und trage das Ergebnis in das Feld unten ein [[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]]:',
	'captcha-createaccount'      => "Zum Schutz vor automatisierter Anlage von Benutzerkonten löse die nachfolgende Rechenaufgabe und trage das Ergebnis in das Feld unten ein [[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]].",
	'captcha-createaccount-fail' => "Falscher oder fehlender Bestätigungscode.",
	'captcha-create'	     => "Zur Erstellung der Seite löse die nachfolgende Rechenaufgabe und trage das Ergebnis in das Feld unten ein [[{{ns:special}}:Captcha/help|(Fragen oder Probleme?)]].",
	'captchahelp-title'          => 'Captcha-Hilfe',
	'captchahelp-cookies-needed' => "'''Wichtiger Hinweis:''' Es müssen Cookies im Browser erlaubt sein.",
	'captchahelp-text'           => "Internetangebote, die für Beiträge von praktisch jedem offen sind — so wie das {{SITENAME}}-Wiki — werden oft von Spammern missbraucht, die ihre Links automatisch auf vielen Webseiten platzieren. Diese Spam-Links können wieder entfernt werden, sie sind aber ein erhebliches Ärgernis. In manchen Fällen, insbesondere beim Hinzufügen von neuen Weblinks zu einer Seite, kann es vorkommen, dass dieses Wiki ein Bild mit einem farbigen und verzerrten Text anzeigt und dazu auffordert, die angezeigten Wörter einzutippen. Da eine solche Aufgabe nur schwer automatisch erledigt werden kann, werden dadurch die meisten Spammer, die mit automatischen Werkzeugen arbeiten, gestoppt, wogegen menschliche Benutzer ihren Beitrag absenden können. Leider kann dies zu Schwierigkeiten für Personen führen, die über eine eingeschränkte Sehfähigkeit verfügen oder text- oder sprachbasierte Browser verwenden. Eine Lösung ist die reguläre Anmeldung als Benutzer. Der „Zurück“-Knopf des Browsers führt zurück in das Bearbeitungsfenster.",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
#  Syntax:
#   * Alles von einem #-Zeichen bis zum Ende der Zeile ist ein Kommentar
#   * Jeder nicht-leere Zeile ist ein Regex-Fragment, das gegenüber den Hostnamen einer URL geprüft wird
 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['el'] = array(
	'captcha-badlogin'            => 'Για να βοηθήσετε στην προστασία ενάντια στον "σπασμένο" κωδικό πρόσβασης, παρακαλώ λύστε αυτή την απλή πράξη και εισάγετε το αποτέλεσμα της στο παρακάτω κενό ([[Special:Captcha/help|περισσότερες πληροφορίες]]):',
	'captcha-createaccount'       => 'Για να βοηθήσετε στην προστασία ενάντια στην αυτοματοποιημένη δημιουργία λογαριασμού, παρακαλώ λύστε την απλή πράξη
και εισάγετε την λύση της στο παρακάτω κενό
([[Special:Captcha/help|περισσότερες πληροφορίες]]):',
	'captchahelp-text'            => 'Οι ιστοσελίδες που δέχονται τις επεξεργασίες από το κοινό, όπως αυτό το wiki, δεν χρησιμοποιούνται συχνά σωστά από τους spammers που χρησιμοποιούν τα αυτοματοποιημένα εργαλεία για να αποστείλουν τις συνδέσεις τους με πολλές σελίδες. Αυτές οι spam συνδέσεις  μπορούν να αφαιρεθούν, επειδή είναι σημαντικά ενοχλητικές.

Μερικές φορές, ειδικά κατά την προσθήκη νέων συνδέσμων σε μια σελίδα, το wiki μπορεί να σας παρουσιάσει μια εικόνα με ένα χρωματισμένο ή διαστρεβλωμένο κείμενο και να σας ζητήσει να πληκτρολογήσετε τις λέξεις που παρουσιάζονται. Δεδομένου ότι αυτό είναι ένας στόχος που είναι δύσκολο να αυτοματοποιηθεί, θα επιτρέψει στους περισσότερους χρήστες να κάνουν τις επεξεργασίες τους, σταματώντας τους spammers και άλλους ρομποτικά επιτιθέμενους.

Δυστυχώς αυτό μπορεί να ενοχλήσει τους χρήστες περιορίζοντας το όραμα τους ή αυτούς που βασίζονται στο κείμενο ή στην ομιλία που βασίζεται στις μηχανές αναζήτησης. Προς το παρόν δεν έχουμε μια διαθέσιμη εναλλακτική λύση. Παρακαλώ ελάτε σε επαφή με τους διαχειριστές των σελίδων για βοήθεια, εάν αυτό σας αποτρέπει απροσδόκητα από την παραγωγή των νόμιμων επεξεργασιών. 

Πατήστε το κουμπί \'πίσω\' στη μηχανή αναζήτησης σας για να επιστρέψετε στο συντάκτη σελίδων.',
);
$messages['es'] = array(
	'captcha-edit'                => 'Para editar este artículo, por favor resuelve la sencilla suma que aparece abajo e introduce la solución en la caja ([[Special:Captcha/help|más información]]):',
	'captcha-addurl'              => 'Tu edición incluye nuevos enlaces externos. Para ayudar a proteger contra el spam automatizado, por favor resuelve la sencilla suma de abajo e introduce la respuesta en la caja ([[Special:Captcha/help|más información]]):',
	'captcha-createaccount'       => 'Para ayudar a protegernos de la creación automática de cuentas, por favor resuelve la simple suma de abajo e introduce la respuesta en la caja ([[Special:Captcha/help|más información]]):',
	'captcha-createaccount-fail'  => 'Falta el código de confirmación, o éste es incorrecto.',
	'captcha-create'              => 'Para crear la página, por favor resuelve la simple suma de abajo e introduce la respuesta en la caja ([[Special:Captcha/help|más información]]):',
	'captchahelp-title'           => 'Ayuda sobre el captcha',
	'captchahelp-cookies-needed'  => 'Debe tener las cookies activadas en el navegador para que el sistema funcione.',
	'captchahelp-text'            => 'Los sitios web que aceptan mensajes del público, como esta wiki, son a menudo objeto de abusos  por spammers que utilizan programas para incluir automáticamente sus enlaces. Si bien estos enlaces pueden quitarse, son una gran molestia.

En ocasiones, especialmente cuando añada nuevos enlaces a una página, la wiki le mostrará una imagen de texto coloreado o distorsionado y le pedirá que escriba las palabras que muestra. Dado que esta es una tarea difícil de automatizar, permite a la mayoría de las personas enviar sus textos, a la vez que detiene a la mayoría de los spammers y otros atacantes automáticos.',
);
$messages['et'] = array(
	'captcha-edit'               => "Teie muudatuses on uusi linke; kaitseks spämmi vastu peate sisestama järgneval pildil olevad sõnad:<br /> ([[Special:Captcha/help|Mis see on?]])",
	'captcha-addurl'               => "Teie muudatuses on uusi linke; kaitseks spämmi vastu peate sisestama järgneval pildil olevad sõnad:<br /> ([[Special:Captcha/help|Mis see on?]])",
	'captcha-create'               => "Teie muudatuses on uusi linke; kaitseks spämmi vastu peate sisestama järgneval pildil olevad sõnad:<br /> ([[Special:Captcha/help|Mis see on?]])",
	'captchahelp-title'          => 'Mis on Captcha?',
	'captchahelp-text'           => "Internetisaite, mis lubavad külastajatel sisu muuta (nagu ka see Viki), kasutavad sageli spämmerid ära, postitades reklaamlinke - spämmi. Kuigi neid linke saab alati ära võtta, on nad ikkagi üpris tülikad. Omale kasutajakontot registreerides või mõnele lehele uusi internetiaadresse postitades näidatakse teile moonutatud tekstiga pilti ning palutakse teil sisestada seal näidatud sõnad. Kuna selliselt pildilt on arvutil raske teksti välja lugeda, on see efektiivseks kaitseks spämmirobotite vastu, samas lubades tavainimestel oma muudatusi rahus teha. Kahjuks võib see tekitada ebamugavusi nägemisraskustega inimestele või neile, kes kasutavad kõnesüntesaatorit või tekstipõhist brauserit. Hetkel pole meil helipõhist alternatiivi. Kui teil tekib ootamatult raskusi oma muudatuste tegemisel, siis kirjutage sellest [[Vikipeedia:Üldine arutelu|üldise arutelu]] lehele. Konto registreerimise lehele või lehe redigeerimisele tagasi jõudmiseks vajutage oma brauseri tagasi-nuppu.",
	'captcha-createaccount'      => "Kaitsena spämmi vastu peate konto registreerimiseks lahtrisse kirjutama järgneva tehte tulemuse.<br /> ([[Special:Captcha/help|Mis see on?]])",
	'captcha-createaccount-fail' => "Puuduv või valesti sisestatud kinnituskood.",
);
$messages['eu'] = array(
	'captcha-edit'               => "Zure aldaketan URL lotura berriak daude; spam-a saihesteko, jarraian dagoen irudiko hitzak idaztea beharrezkoa da:<br /> ([[Special:Captcha/help|Zer da hau?]])",
	'captcha-addurl'               => "Zure aldaketan URL lotura berriak daude; spam-a saihesteko, jarraian dagoen irudiko hitzak idaztea beharrezkoa da:<br /> ([[Special:Captcha/help|Zer da hau?]])",
	'captcha-create'               => "Zure aldaketan URL lotura berriak daude; spam-a saihesteko, jarraian dagoen irudiko hitzak idaztea beharrezkoa da:<br /> ([[Special:Captcha/help|Zer da hau?]])",
	'captchahelp-title'          => 'Captcha laguntza',
	'captchahelp-text'           => "Publikoki aldaketak egiteko aukerak dituzten webguneetan, wiki honetan bezalaxe, spam testuak gehitzen dira sarritan tresna automatikoak erabiliz. Lotura horiek ezabatu egin daitezkeen arren, traba dira. Batzutan, eta bereziki webgune berri bateko loturak gehitzen dituzunean, hitz batzuk dituen irudi bat agertuko zaizu, eta bertan ageri den testua idazteko eskatuko zaizu. Lan hori automatizatzeko zaila da, eta pertsonei ezer kostatzen ez zaigunez, spam testuak saihesteko lagungarria da. Zoritxarrez, ikusmen mugatua edo testu bidezko nabigatzaileak erabiltzen dituzten erabiltzeek arazoak izan ditzakete. Horrelako zerbait gertatzen bazaizu, mesedez, jarri administratzaileekin harremanetan. Zure nabigatzaileko 'atzera' lotura erabili aldaketen orrialdera itzultzeko.",
	'captcha-createaccount'      => "Spam-a saihesteko, mesedez, irudian agertzen den hizki edo zenbaki kodea, beheko laukian idatzi zure kontua sortzeko:<br /> ([[Special:Captcha/help|Zer da hau?]])",
	'captcha-createaccount-fail' => "Baieztatze kode ezegokia.",
);

$messages['fi'] = array(
	'captcha-edit'                => 'Muokkauksesi sisältää uusia linkkejä muille sivuille. Ratkaise alla oleva summa jatkaaksesi ([[Special:Captcha/help|lisätietoja]]):',
	'captcha-addurl'              => 'Muokkauksesi sisältää uusia linkkejä muille sivuille. Ratkaise alla oleva summa jatkaaksesi ([[Special:Captcha/help|lisätietoja]]):',
	'captcha-badlogin'            => 'Salasananmurtajasovellusten takia, ratkaise alla oleva summa jatkaaksesi ([[Special:Captcha/help|lisätietoja]]):',
	'captcha-createaccount'       => 'Ratkaise alla oleva summa jatkaaksesi ([[Special:Captcha/help|lisätietoja]]):',
	'captcha-createaccount-fail'  => 'Väärä tai puuttuva varmistuskoodi.',
	'captcha-create'              => 'Muokkauksesi sisältää uusia linkkejä muille sivuille. Ratkaise alla oleva summa jatkaaksesi ([[Special:Captcha/help|lisätietoja]]):',
	'captchahelp-title'           => 'Captcha-ohje',
	'captchahelp-cookies-needed'  => 'Tämä toiminto vaatii evästeiden hyväksymistä.',
	'captchahelp-text'            => 'Verkkosivut, jotka sallivat ulkopuolisten lisätä sisältöä, joutuvat usein spam-hyökkäysten kohteeksi. Spam-hyökkäyksessä spammerit käyttävät työkaluja, jotka automaattisesti lisäävät linkkejä monille sivuille. Vaikka nämä linkit voidaan poistaa, aiheutuu niistä silti merkittävä haitta.

Joskus, erityisesti kun lisäät uusia linkkejä, saatat nähdä kuvan, jossa on värillistä ja vääristynyttä tekstiä, ja sinua pyydetään kirjoittamaan sen sisältämät sanat. Koska tätä tehtävää on vaikea automatisoida, se sallii melkein kaikkien oikeiden ihmisten tehdä muutoksensa, mutta estää automaattiset lisäykset.

Valitettavasti tämä saattaa estää käyttäjiä, joilla on rajoittunut näkökyky tai käyttäjiä, jotka käyttävät teksti- tai puhepohjaisia selaimia. Ota yhteyttä sivuston ylläpitäjään, jos et pysty tekemään kunnollisia muutoksia.

Varmistus ei toimi, jos evästeet eivät ole käytössä.

Voit palata muokkaustilaan selaimen paluutoiminnolla.',
);
$messages['fo'] = array(
	'captcha-createaccount'       => 'Sum ein vernd ímóti sjálvvirknum spam, er neyðugt hjá tær at skriva inn tey orð, sum koma fyri á myndini fyri at stovna eina kontu: <br />([[Special:Captcha/help|Hvat er hetta?]])',
	'captchahelp-title'           => 'Captcha hjálp',
);

/** French (Français)
 * @author Sherbrooke
 * @author Grondin
 * @author ChrisPtDe
 */
$messages['fr'] = array(
	'captcha-edit'               => 'Votre édition inclut de nouveaux liens externes. Comme protection contre le pourriel automatique, veuillez entrer le résultat de l’opération ci-dessous dans la boîte ([[Special:Captcha/help|plus d’informations]]) :',
	'captcha-addurl'             => 'Votre édition inclut de nouveaux liens externes. Comme protection contre le pourriel automatique, veuillez entrer le résultat de l’opération ci-dessous dans la boîte ([[Special:Captcha/help|plus d’informations]]) :',
	'captcha-badlogin'           => 'Coment protèccion contre les tentatives de cracâjo de mots de pâssa ôtomatisâs per des bots, volyéd relevar lo tèxte dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captcha-createaccount'      => 'Comme protection contre les créations de compte abusives, veuillez entrer le résultat de l’opération dans la boîte ci-dessous ([[Special:Captcha/help|plus d’informations]]) :',
	'captcha-createaccount-fail' => 'Code de confirmation erroné ou manquant.',
	'captcha-create'             => 'Votre édition inclut de nouveaux liens externes. Comme protection contre le pourriel automatique, veuillez entrer le résultat de l’opération ci-dessous dans la boîte ([[Special:Captcha/help|plus d’informations]]) :',
	'captchahelp-title'          => 'Aide sur les captcha',
	'captchahelp-cookies-needed' => 'Il faut activer les cookies dans votre navigateur Web pour que cela fonctionne.',
	'captchahelp-text'           => 'Les sites Web acceptant des contributions du public, comme ce wiki, sont souvent utilisés par des spammeurs qui utilisent des outils automatiques pour placer de nombreux liens vers leurs sites. Même si ces liens de « spam » peuvent être enlevés, ils représentent une nuisance.

Parfois, en particulier lors de l’ajout de nouveaux liens externes à une page, le wiki peut vous montrer une image représentant un texte coloré ou tordu et vous demander de taper les mots montrés. Cette tâche est difficile à automatiser, et permet aux humains de faire leurs contributions tout en stoppant la plupart des spammeurs.

Cette solution peut malheureusement gêner les utilisateurs malvoyants ou utilisant un navigateur en texte seul. Nous ne disposons pas d’alternative audio pour l’instant. Veuillez contacter un administrateur du site si vous n’arrivez pas à faire vos contributions.

Cliquez sur le bouton « Précédent » de votre navigateur pour revenir à la page d’édition.',
	'captcha-addurl-whitelist'   => ' #<!-- laissez cette ligne exactement telle quelle --> <pre> 
# La syntaxe est la suivante: 
#   * Tout caractère suivant "#" jusqu\'à la fin de la ligne sera interprêté comme un commentaire
#   * Toute ligne non vide est un code regex qui sera utilisé uniquement à l\'intérieur des liens hypertext.
 #</pre> <!-- laissez cette ligne exactement telle quelle -->',
);

/** Franco-Provençal (Arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'captcha-edit'               => 'Voutra èdicion encllut de novéls lims de defôr. Coment protèccion contre lo spame ôtomatico, 
volyéd entrar lo rèsultat de l’opèracion dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captcha-addurl'             => 'Voutra èdicion encllut de novéls lims de defôr. Coment protèccion contre lo spame ôtomatico, 
volyéd entrar lo rèsultat de l’opèracion dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captcha-badlogin'           => 'Coment protèccion contre les tentatives de cracâjo de mots de pâssa ôtomatisâs per des bots, volyéd relevar lo tèxte dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captcha-createaccount'      => 'Coment protèccion contre les crèacions de compto abusives, volyéd entrar lo rèsultat de l’opèracion dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captcha-createaccount-fail' => 'Code de confirmacion fôx ou manquent.',
	'captcha-create'             => 'Voutra èdicion encllut de novéls lims de defôr. Coment protèccion contre lo spame ôtomatico,  
volyéd entrar lo rèsultat de l’opèracion dens la bouèta ce-desot ([[Special:Captcha/help|més d’enformacions]]) :',
	'captchahelp-title'          => 'Éde sur los captcha',
	'captchahelp-cookies-needed' => "Fôt activar los tèmouens (''cookies'') dens voutron navigator por que cen fonccione.",
);

/** Irish (Gaeilge)
 * @author SPQRobin
 */
$messages['ga'] = array(
	'captcha-edit'               => 'Tá naisc URL nua san athrú seo atá tú ar tí a dhéanamh; mar chosaint in éadan turscair uathoibrithe, caithfidh tú na focail san íomhá seo a ionchur: <br />([[Speisialta:Captcha/help|Céard é seo?]])',
	'captcha-addurl'             => 'Tá naisc URL nua san athrú seo atá tú ar tí a dhéanamh; mar chosaint in éadan turscair uathoibrithe, caithfidh tú na focail san íomhá seo a ionchur: <br />([[Speisialta:Captcha/help|Céard é seo?]])',
	'captcha-createaccount'      => 'Mar chosaint in éadan turscair uathoibrithe, caithfidh tú na focail san íomhá seo a ionchur chun cuntas a chlárú: <br />([[Speisialta:Captcha/help|Céard é seo?]])',
	'captcha-createaccount-fail' => 'Ní raibh an cód deimhnithe ceart sa bhosca, nó ní raibh aon chód ann ar chor ar bith.',
	'captcha-create'             => 'Tá naisc URL nua san athrú seo atá tú ar tí a dhéanamh; mar chosaint in éadan turscair uathoibrithe, caithfidh tú na focail san íomhá seo a ionchur: <br />([[Speisialta:Captcha/help|Céard é seo?]])',
	'captchahelp-title'          => 'Cabhair maidir le Captcha',
);

$messages['gl'] = array(
	'captcha-edit'                => 'A súa edición inclúe novos enderezos URL; como protección contra as ferramentas de publicación automática de ligazóns publicitarias necesita teclear as palabras que aparecen nesta imaxe:<br /> ([[Special:Captcha/help|Qué é isto?]])',
	'captcha-addurl'              => 'A súa edición inclúe novos enderezos URL; como protección contra as ferramentas de publicación automática de ligazóns publicitarias necesita teclear as palabras que aparecen nesta imaxe:<br /> ([[Special:Captcha/help|Qué é isto?]])',
	'captcha-badlogin'            => 'Como protección para que non descubran o contrasinal por medios automáticos, resolva a suma simple de embaixo e introduza a resposta na caixa ([[Special:Captcha/help|máis información]])',
	'captcha-createaccount'       => 'Como protección fronte a sistemas de creación automática de contas de usuario usados polos \'\'spammers\'\', ten que teclear as palabras que aparecen na imaxe para rexistrar unha conta:<br /> ([[Special:Captcha/help|Qué é isto?]])',
	'captcha-createaccount-fail'  => 'Falta o código de confirmación ou é incorrecto.',
	'captcha-create'              => 'Para crear a páxina, resolva a suma simple que aparece embaixo e introduza a resposta na caixa  ([[Special:Captcha/help|Qué é isto?]])',
	'captchahelp-title'           => 'Axuda acerca do Captcha',
	'captchahelp-cookies-needed'  => 'Vostede necesita ter as cookies habilitadas no seu navegador para que funcione.',
	'captchahelp-text'            => '\'\'\'CAPTCHA\'\'\' (acrónimo de "\'\'\'C\'\'\'ompletely \'\'\'A\'\'\'utomated \'\'\'P\'\'\'ublic \'\'\'T\'\'\'uring test to tell \'\'\'C\'\'\'omputers and \'\'\'H\'\'\'umans \'\'\'A\'\'\'part") é un test de autentificación do tipo desafío-resposta usado nos contornos informáticos para distinguir usuarios humanos de máquinas. Os sitios web que aceptan publicar as contribucións dos usuarios coma este wiki sofren, con frecuencia, o abuso por parte de \'\'spammers\'\' que usan ferramentas que automatizan a inclusión de lixo en forma de ligazóns publicitarias nunha chea de páxinas en pouco tempo. Mentres ditas ligazóns non son eliminadas supoñen unha molestia e unha perda de tempo. En ocasións, en particular cando engada algún novo vínculo externo, o wiki pode mostrar unha imaxe dun texto coloreado e distorsionado e pedirlle que teclee as palabras mostradas. Como esta tarefa é difícil de automatizar, permite distinguir entre persoas e robots e dificulta os ataques automatizados dos \'\'spammers\'\'. Por desgraza, pódelles causar problemas a aqueles usuarios con dificultades de visión ou aos que utilicen navegadores de texto ou navegadores baseados en sistemas de voz. Polo de agora non dispoñemos dunha alternativa de audio. Por favor, contacte cun [[Special:Listusers/sysop|administrador]] do wiki para solicitar axuda se o sistema lle impide rexistrarse para facer contribucións lexítimas. Prema no botón "atrás" ou equivalente do seu navegador para volver á páxina na que estaba.',
	'captcha-addurl-whitelist'    => ' #<!-- deixe esta liña exactamente como está --> <pre>
# A sintaxe é a seguinte:
#   * Todo o que vai desde o carácter "#" até o final da liña é un comentario  
#   * Cada liña que non estea en branco é un fragmento de expresión regular que só coincidirá con hosts dentro de URLs
 #</pre> <!-- deixe esta liña exactamente como está -->',
);
$messages['he'] = array(
	'captcha-edit'               => 'כדי לערוך את הדף, אנא פיתרו את תרגיל החיבור הפשוט שלהלן והקלידו את התשובה בתיבה ([[{{ns:special}}:Captcha/help|מידע נוסף]]):',
	'captcha-addurl'             => 'עריכתכם כוללת קישורים חיצוניים חדשים. כהגנה מפני ספאם אוטומטי, אנא פיתרו את תרגיל החיבור הפשוט שלהלן והקלידו את התשובה בתיבה ([[{{ns:special}}:Captcha/help|מידע נוסף]]):',
	'captcha-badlogin'            => 'כהגנה מפני פריצת סיסמאות אוטומטית, אנא פיתרו את תרגיל החיבור הפשוט שלהלן והקלידו את התשובה בתיבה ([[{{ns:special}}:Captcha/help|מידע נוסף]]):',
	'captcha-createaccount'      => 'כהגנה מפני יצירת חשבונות אוטומטית, אנא פיתרו את תרגיל החיבור הפשוט שלהלן והקלידו את התשובה בתיבה ([[{{ns:special}}:Captcha/help|מידע נוסף]]):',
	'captcha-createaccount-fail' => 'לא הקלדתם קוד אישור, או שהוא שגוי.',
	'captcha-create'             => 'כדי ליצור את הדף, אנא פיתרו את תרגיל החיבור הפשוט שלהלן והקלידו את התשובה בתיבה ([[{{ns:special}}:Captcha/help|מידע נוסף]]):',
	'captchahelp-cookies-needed' => "עליכם להפעיל את תכונת העוגיות (Cookies) בדפדפן שלכם כדי שזה יעבוד.",
	'captchahelp-title'          => 'עזרה במערכת הגנת הספאם',
	'captchahelp-text'           => "פעמים רבות מנצלים ספאמרים אתרים שמקבלים תוכן מהציבור, כמו הוויקי הזה, כדי לפרסם את הקישורים שלהם לאתרים רבים באינטרנט, באמצעות כלים אוטומטיים. אמנם ניתן להסיר את קישורי הספאם הללו, אך זהו מטרד משמעותי.

לעיתים, בעיקר כשאתם מכניסים קישורי אינטרנט חדשים לתוך עמוד, הוויקי עשוי להראות תמונה של טקסט צבעוני או מעוקם ויבקש מכם להקליד את המילים המוצגות. כיוון שזו משימה שקשה לבצעה בצורה אוטומטית, הדבר יאפשר לבני־אדם אמיתיים לשלוח את הדפים, אך יעצור את רוב הספאמרים והמתקיפים הרובוטיים.

לרוע המזל, הדבר עשוי לגרום לאי נוחות למשתמשים עם דפדפן בגרסה מוגבלת, או שמשתמשים בדפדפנים מבוססי טקסט או דיבור. כרגע, אין לנו חלופה קולית זמינה. אנא צרו קשר עם מנהלי האתר לעזרה אם המערכת מונעת מכם באופן בלתי צפוי לבצע עריכות לגיטימיות.

אנא לחצו על הכפתור 'Back' בדפדפן שלכם כדי לחזור לדף העריכה.",
	'captcha-addurl-whitelist' => '
 #<!-- יש להשאיר שורה זו בדיוק כפי שהיא כתובה --> <pre> 
# זהו תחביר ההודעה: 
#   * כל דבר בשורה שנכתב לאחר סימן "#" הוא הערה
#   * כל שורה לא ריקה היא ביטוי רגולרי שיתאים לאתרים בכתובות URL
 #</pre> <!-- יש להשאיר שורה זו בדיוק כפי שהיא כתובה -->',
);
$messages['hr'] = array(
	'captcha-edit'                => 'Vaše uređivanje sadrži nove vanjske poveznice. Kao zaštitu od automatskog spama, trebate unijeti slova koja vidite na slici: <br />([[Posebno:Captcha/help|Pomoć?]])',
	'captcha-addurl'              => 'Vaše uređivanje sadrži nove vanjske poveznice. Kao zaštitu od automatskog spama, trebate unijeti slova koja vidite na slici: <br />([[Posebno:Captcha/help|Pomoć?]])',
	'captcha-badlogin'            => 'Da se spriječi automatizirano pogađanje lozinki,
molimo zbrojite donje brojeve i upišite rezultat ([[Special:Captcha/help|pomoć]]):',
	'captcha-createaccount'       => 'Kao zaštitu od automatskog spama, pri otvaranju računa trebate unijeti slova koja vidite na slici: <br />([[Posebno:Captcha/help|Pomoć]])',
	'captcha-createaccount-fail'  => 'Potvrdni kod je nepotpun ili netočan.',
	'captcha-create'              => 'Vaše uređivanje sadrži nove vanjske poveznice. Kao zaštitu od automatskog spama, trebate unijeti slova koja vidite na slici: <br />([[Posebno:Captcha/help|Pomoć?]])',
	'captchahelp-title'           => 'Antispam pomoć',
	'captchahelp-cookies-needed'  => 'Trebate imati uključene kolačiće (\'\'cookies\'\') u vašem web pregledniku za ovu funkciju.',
	'captchahelp-text'            => 'Web poslužitelje koji rade na temelju javnih doprinosa, poput wiki, često zloupotrebljavaju spameri. Oni koriste automatske alate pomoću kojih generiraju poveznice od vlastitog interesa. Iako se te poveznice najčešće uklanjaju, mogu predstavljati neugodnost pri radu. Ponekad se dogodi da wiki prikaže sliku čudnog tekstualnog sadržaja uz koju morate unijeti prikazana slova. Budući da je takvu radnju teško automatizirati, većina se napadača obeshrabri, a pravi suradnici bez većih smetnji nastavljaju pridonositi. Ukoliko ste suradnik koji koristi tekstualni klijent te vas česte ovakve provjere ometaju pri dodavanju važećih sadržaja, molimo da se obratite [[Special:Listusers/sysop|administratorima]].',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre> 
# Rabi se slijedeća sintaksa: 
#   * Sve od "#" znaka do kraja linije je komentar
#   * Svaki neprazni redak je regularni izraz (regex) koji odgovara poslužitelju unutar URL-a
 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['hsb'] = array(
	'captcha-edit'                => 'W twojej změnje su nowe eksterne wotkazy. Jako škitna naprawa přećiwo spamej dyrbiš slědowacy nadawk wuličeć a wuslědk do kašćika zapisować. Klikń potom znowa na „Składować”.<br /> [[{{ns:special}}:Captcha/help|(Čehodla?)]]',
	'captcha-addurl'              => 'W twojej změnje su nowe eksterne wotkazy. Jako škitna naprawa přećiwo spamej dyrbiš slědowacy nadawk wuličeć a wuslědk do kašćika zapisować. Klikń potom znowa na „Składować”.<br /> [[{{ns:special}}:Captcha/help|(Čehodla?)]]',
	'captcha-badlogin'             => 'Zo by so awtomatiskemu zadobywanju do hesłow zadźěwało, dyrbiš slědowacy nadawk wuličeć a wuslědk do kašćika zapisować. [[{{ns:special}}:Captcha/help|(Prašenja abo problemy?)]]',
	'captcha-createaccount'       => 'Jako škitna naprawa přećiwo awtomatiskemu wutworjenju wužiwarskich kontow dyrbiš slědowacy nadawk wuličeć. [[{{ns:special}}:Captcha/help|(Prašenja abo problemy?)]]',
	'captcha-createaccount-fail'  => 'Wopačny abo pobrachowacy wuslědk.',
	'captcha-create'              => 'W twojej změnje su nowe eksterne wotkazy. Jako škitna naprawa přećiwo spamej dyrbiš slědowacy nadawk wuličeć a wuslědk do kašćika zapisować. Klikń potom znowa na „Składować”.<br /> [[{{ns:special}}:Captcha/help|(Čehodla?)]]',
	'captchahelp-title'           => 'Pomoc z captcha',
	'captchahelp-cookies-needed'  => 'Dyrbiš placki (cookies) w swojim wobhladowaku zmóžnić.',
	'captchahelp-text'            => 'Sydła kaž {{SITENAME}}, kotrež móža so swobodnje wot kóždeho wobdźěłować su často z woporom spamarjow, kotřiž swoje wotkazy awtomatisce we wjele sydłach broja. Tute spam-wotkazy móža so zaso wotstronjeć, wubudźa pak njesnadne mjerzanje. W někotrych padach, wosebje při přidawanju nowych eksternych wotkazow, móže so stać, zo pokazuje tutón wiki wobraz z pisanym abo skomolenym tekstom abo kaza jednory ličenski nadawk wuličeć, kotrehož wuslědk dyrbi so potom do zapodawanskeho kašćika zapisować. Dokelž hodźi so tajki nadawk jenož ćežko z awtomatiskimi srědkami spamarjow spjelnić, móža so tajcy wotdźeržować, druzy wužiwarjo pak móža swoje změny składować. Bohužel móže to k wobćežnosćam za tutych wužiwarjow dowjesć, kotrychž kmanosć widźenja je wobmjezowana abo kotřiž dźěłaja z wobhladowakami kiž su na tekstowe abo rěčne wudawanje wusměrjene. Móžne rozrisanje tutoho problema je přizjewjenje jako wužiwar. Tłóčatko „Wróćo” swojeho wobhladowaka dowjedźe tebje zaso do wobdźěłowanskeho pola.',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre>
# Syntaks je slědowaca:
# * Wšo wot znamješka "#" hač do kónca linky je komentar
# * Kóžda popisana linka je fragment regex (regularneho wuraza) kotryž so z mjenom hosta wěsteje URL přirunuje

 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['id'] = array(
	'captcha-edit'               => "Suntingan Anda menyertakan pralana luar baru. Sebagai perlindungan terhadap ''spam'' otomatis, Anda harus mengetikkan kata atau hasil perhitungan yang tertera berikut ini:<br />
([[Special:Captcha/help|info lengkap]])",
	'captcha-addurl'               => "Suntingan Anda menyertakan pralana luar baru. Sebagai perlindungan terhadap ''spam'' otomatis, Anda harus mengetikkan kata atau hasil perhitungan yang tertera berikut ini:<br />
([[Special:Captcha/help|info lengkap]])",
	'captcha-badlogin'              => 'Untuk membantu perlindungan terhadap perengkahan kunci sandi otomatis, tolong masukkan kata atau hasil perhitungan sederhana berikut dalam kotak yang tersedia ([[Special:Captcha/help|info lengkap]]):',
	'captcha-create'               => "Suntingan Anda menyertakan pralana luar baru. Sebagai perlindungan terhadap ''spam'' otomatis, Anda harus mengetikkan kata atau hasil perhitungan yang tertera berikut ini:<br />
([[Special:Captcha/help|info lengkap]])",
	'captchahelp-title'          => 'Mengenai Captcha',
	'captchahelp-cookies-needed' => "Anda perlu mengaktifkan cookie pada penjelajah web Anda untuk menggunakan fitur ini.",
	'captchahelp-text'           => "Situs-situs web yang menerima masukan data dari publik, seperti {{ns:project}} ini, kerapkali disalahgunakan oleh pengguna-pengguna yang tidak bertanggungjawab untuk mengirimkan spam dengan menggunakan program-program otomatis. Walaupun spam-spam tersebut dapat dibuang, tetapi tetap saja menimbulkan gangguan berarti.

Ketika menambahkan pranala web baru ke suatu halaman, {{ns:project}} akan menampilkan sebuah gambar tulisan yang terdistorsi atau suatu perhitungan sederhana dan meminta Anda untuk mengetikkan kata atau hasil dimaksud. Karena ini merupakan suatu pekerjaan yang sulit diotomatisasi, pembatasan ini akan mengizinkan hampir semua manusia untuk melakukannya, tapi di sisi lain akan menghentikan kebanyakan aksi spam dan penyerangan yang dilakukan oleh bot otomatis.

Sayangnya, hal ini dapat menimbulkan kesulitan bagi pengguna dengan keterbatasan penglihatan atau pengguna yang menggunakan penjelajah basis teks atau suara. Saat ini, kami tidak memiliki suatu alternatif suara untuk hal ini. Silakan minta bantuan dari pengurus situs jika hal ini menghambat Anda untuk mengirimkan suntingan yang layak.

Tekan tombol 'back' di penjelajah web Anda untuk kembali ke halaman penyuntingan.",
	'captcha-createaccount'      => "Sebagai perlindungan melawan spam, Anda diharuskan untuk mengetikkan kata atau hasil perhitungan di bawah ini di kotak yang tersedia untuk dapat mendaftarkan pengguna baru:<br />
([[Special:Captcha/help|info lengkap]])",
	'captcha-createaccount-fail' => "Kode konfirmasi salah atau belum diisi.",
);
$messages['is'] = array(
	'captcha-edit'               => "Breyting þín fól í sér nýja tengla á aðrar vefsíður. Til þess að verjast sjálfvirku auglýsingarusli verðum við að biðja þig um að skrifa inn orðin sem sjást á þessari mynd: <br />([[Special:Captcha/help|Hvað er þetta?]])",
	'captcha-addurl'               => "Breyting þín fól í sér nýja tengla á aðrar vefsíður. Til þess að verjast sjálfvirku auglýsingarusli verðum við að biðja þig um að skrifa inn orðin sem sjást á þessari mynd: <br />([[Special:Captcha/help|Hvað er þetta?]])",
	'captcha-create'               => "Breyting þín fól í sér nýja tengla á aðrar vefsíður. Til þess að verjast sjálfvirku auglýsingarusli verðum við að biðja þig um að skrifa inn orðin sem sjást á þessari mynd: <br />([[Special:Captcha/help|Hvað er þetta?]])",
	'captchahelp-title'          => 'Captcha-hjálp',
	'captchahelp-text'           => "Vefsíður sem að leyfa framlög frá frá almenningi, líkt og þessi wiki-vefur, eru oft misnotaðar af svokölluðum „spömmurum“ sem nota sjálfvirk tól til þess að setja inn tengla á aðrar vefsíður. Aðrir notendur geta fjarlægt þessa tengla en töluverð truflun er af þeim.

Stundum þegar þú breytir síðum, sérstaklega ef breytingin felur í sér nýja tengla á aðra vefi, getur gerst að þú sért beðin(n) um að skrifa inn orð sem birtast á lituðum eða óskýrum myndum. Fyrir flesta notendur af holdi og blóði er þetta lítið mál en sjálfvirk tól ráða ekki við þetta.

Því miður kann þetta að valda notendum óþægindum sem hafa skerta sjón eða notast við talmálsvafra. Enn sem komið er eru ekki til aðrir valkostir fyrir þau tilvik. Ef þetta kemur í veg fyrir lögmætar breytingar af þinni hálfu getur þú leitað aðstoðar hjá stjórnendum vefsins.

Notaðu „back“-hnapp vafrans til að halda áfram.",
	'captcha-createaccount'      => "Til þess að verjast sjálfvirku auglýsingarusli verðum við að biðja þig um að skrifa inn orðin sem sjást á þessari mynd áður en þú skráir notandanafn: <br />([[Special:Captcha/help|Hvað er þetta?]])",
	'captcha-createaccount-fail' => "Staðfestingarkóðinn var rangur eða ekki til staðar.",
);
$messages['it'] = array(
	'captcha-edit'                => 'La modifica richiesta aggiunge dei nuovi collegamenti (URL) alla pagina; come misura precauzionale contro l\'inserimento automatico di spam, per confermarla è necessario inserire le parole che appaiono nell\'immagine:<br />
([[Special:Captcha/help|Cosa vuol dire?]])',
	'captcha-addurl'              => 'La modifica richiesta aggiunge dei nuovi collegamenti (URL) alla pagina; come misura precauzionale contro l\'inserimento automatico di spam, per confermarla è necessario inserire le parole che appaiono nell\'immagine:<br />
([[Special:Captcha/help|Cosa vuol dire?]])',
	'captcha-badlogin'             => 'Ai fini di contrastare tentativi di forzatura automatica della password, risolvi per favore il seguente semplice calcolo ed inserisci la risposta nella casella ([[Speciale:Captcha/help|maggiori informazioni]]):',
	'captcha-createaccount'       => 'Come misura di sicurezza nei confronti dei messaggi di spam automatici, per registrarsi è necessario inserire le parole che appaiono nell\'immagine:<br />
([[Speciale:Captcha/help|Cosa vuol dire?]])',
	'captcha-createaccount-fail'  => 'Codice di verifica errato o mancante.',
	'captcha-create'              => 'La modifica richiesta aggiunge dei nuovi collegamenti (URL) alla pagina; come misura precauzionale contro l\'inserimento automatico di spam, per confermarla è necessario inserire le parole che appaiono nell\'immagine:<br />
([[Special:Captcha/help|Cosa vuol dire?]])',
	'captchahelp-title'           => 'Cos\'è il captcha?',
	'captchahelp-cookies-needed'  => 'Devi abilitare il tuo browser ad accettare i cookie per proseguire',
	'captchahelp-text'            => 'Capita spesso che i siti Web che accettano messaggi pubblici, come questo wiki, siano presi di mira da spammer che usano strumenti automatici per inserire collegamenti pubblicitari verso un gran numero di siti. Per quanto i collegamenti indesiderati si possano rimuovere, si tratta comunque di una seccatura non indifferente.

In alcuni casi, ad esempio quando si tenta di aggiungere nuovi collegamenti Web in una pagina, il software wiki può mostrare una immagine con un breve testo colorato e/o distorto chiedendo di riscriverlo in un\'apposita finestrella. Poiché si tratta di un\'azione difficile da replicare da parte di un computer, questo meccanismo consente a (quasi tutti) gli utenti reali di completare l\'inserimento desiderato, impedendo l\'accesso alla maggior parte degli spammer e degli altri attacchi automatizzati.

Sfortunatamente, queste misure di sicurezza possono mettere in difficoltà gli utenti con problemi visivi o coloro che utilizzano browser testuali o basati sulla sintesi vocale. Purtroppo al momento non è disponibile un meccanismo alternativo basato su messaggi audio; se queste procedure impediscono l\'inserimento informazioni che si ritengono legittime, si prega di contattare gli amministratori del sito e chiedere loro assistenza.

Fare clic sul pulsante \'back\' del browser per tornare alla pagina di modifica.',
);
$messages['ja'] = array(
	'captcha-edit'                => 'このページを編集するには下記に現れる数式の答えを入力してください。<br />
([[Special:Captcha/help|詳細]])',
	'captcha-addurl'              => 'あなたの編集には新たに外部リンクが追加されています。スパム防止のため、下記の数式の答えを入力してください<br />
([[Special:Captcha/help|詳細]])',
	'captcha-badlogin'	      => '自動化スクリプトによるパスワードクラック攻撃を防止するため、下記に現れる数式の答えを入力してください<br />
([[Special:Captcha/help|詳細]])',
	'captcha-createaccount'       => 'スパム防止のため、アカウントを登録するには、下記に現れる数式の答えを入力してください<br />
([[Special:Captcha/help|詳細]])',
	'captcha-createaccount-fail'  => '確認コードの入力がないか、間違っています。',
	'captcha-create'              => 'あなたの編集は新たに外部リンクが追加されています。スパム防止のため、下記に現れる数式の答えを入力してください<br />
([[Special:Captcha/help|詳細]])',
	'captchahelp-title'           => 'Captcha ヘルプ',
	'captchahelp-cookies-needed'  => 'ブラウザのクッキー機能を有効にする必要があります。',
	'captchahelp-text'            => '当Wikiのような、投稿が公開されているウェブサイトは、多くのサイトに自分たちへのリンクを自動投稿するツールを用いるスパマーにより荒らされます。これらのスパムは除去できるものの、その作業大変うっとうしいものです。

時々、特に新しいリンクをページに追加したとき、Wikiは色の付いた、もしくは、ゆがめられた文字を提示し、その入力をお願いすることがあります。この作業は自動化が難しいため、本当の人間の投稿を可能にしつつ、多くのスパマーやロボットの攻撃を防ぐことが出来ます。

しかし、残念なことに、テキストベースやスピーチベースのブラウザを使っている、視覚障害者に不便をおかけする場合があります。現時点では、音声版の代替物がありません。正当な投稿をするにあたって、これが障害となっている場合、サイト管理者に連絡し、協力を求めてください。

編集ページに戻るには、ブラウザの戻るボタンを押してください。',
);
$messages['kk-cyrl'] = array(
	'captcha-edit' => 'Бұл бетті өңдеу үшін, төмендегі қосындылауды шешіңіз де, нәтижесін 
аумаққа енгізіңіз ([[{{ns:special}}:Captcha/help|көбірек ақпарат]]):',
	'captcha-addurl' => 'Түзетуіңізде жаңа сыртқы сілтемелер бар екен. Өздіктік «спам» жасалуынан қорғану үшін,
төмендегі қарапайым қосындылауды шешіңіз де, нәтижесін аумаққа енгізіңіз ([[{{ns:special}}:Captcha/help|көбірек ақпарат]]):',
	'captcha-badlogin' => 'Құпия сөзді өздіктік қиратудан қорғану үшін,
төмендегі қарапайым қосындылауды шешіңіз де, нәтижесін аумаққа енгізіңіз ([[{{ns:special}}:Captcha/help|көбірек ақпарат]]):',
	'captcha-createaccount' => 'Жаңа тіркелгі өздіктік жасалуынан қорғану үшін, төмендегі қарапайым қосындылауды
шешіңіз де, нәтижесін аумаққа енгізіңіз ([[{{ns:special}}:Captcha/help|көбірек ақпарат]]):',
	'captcha-createaccount-fail' => "Куәландыру коды дұрыс емес немесе жоқ.",
	'captcha-create' => 'Жаңа бетті бастау үшін, төмендегі қарапайым қосындылауды шешіңіз де, 
нәтижесін аумаққа енгізіңіз ([[{{ns:special}}:Captcha/help|көбірек ақпарат]]):',
	'captchahelp-title'          => 'CAPTCHA анықтамасы',
	'captchahelp-cookies-needed' => "Бұл жұмыс істеу үшін, шолғышыңызда  «cookies»  дегенді ендіріңіз.",
	'captchahelp-text'           => "Өздіктік құралдары бар «спаммерлер», баршадан жіберілген хабарды қабылдайтын, бұл уики сияқты, веб-тораптарға сілтемелерімен жиі жаудырады. Осындай «спам» сілтемелерін аластау болғанда да, бұл мәнді ыза келтіреді.

Кейде, әсіресе бетке жаңа веб сілтемесін қосқанда, уики өңі өзгерген не қисайған мәтінді көрсетіп және сол сөздерді енгізу сұрауы мүмкін. Бұл тапсырыс өздік түрде атқару өте қиын, сондықтан бұл иманды адам көпшілігіне кедергі болмайды, бірақ «спаммерлерді» және бұзақы ботпен басқа шабуыл жасағандарды тоқтатады.

Өкінішке орай, бұл көруі төмендеген, немесе мәтін не дауыс негізіндегі шолғышты қолданатын пайдаланушыға ыңғайсыздық келтіруге мүмкін. Осы қазір бізде дыбысты балама жоқ. Егер бұл адал жазуыңызға кедергілесе, торап бақылаушыларына қатынасыңыз.

Бұл жұмысты істеу үшін шолғышыңызда «cookies» дегенді ендіру қажет.

Бет өңдеуіне қайту бару үшін «Артқа» деген түймесін басыңыз."
);
$messages['kk-latn'] = array(
	'captcha-edit' => 'Bul betti öñdew üşin, tömendegi qosındılawdı şeşiñiz de, nätïjesin 
awmaqqa engiziñiz ([[{{ns:special}}:Captcha/help|köbirek aqparat]]):',
	'captcha-addurl' => 'Tüzetwiñizde jaña sırtqı siltemeler bar eken. Özdiktik «spam» jasalwınan qorğanw üşin,
tömendegi qarapaýım qosındılawdı şeşiñiz de, nätïjesin awmaqqa engiziñiz ([[{{ns:special}}:Captcha/help|köbirek aqparat]]):',
	'captcha-badlogin' => 'Qupïya sözdi özdiktik qïratwdan qorğanw üşin,
tömendegi qarapaýım qosındılawdı şeşiñiz de, nätïjesin awmaqqa engiziñiz ([[{{ns:special}}:Captcha/help|köbirek aqparat]]):',
	'captcha-createaccount' => 'Jaña tirkelgi özdiktik jasalwınan qorğanw üşin, tömendegi qarapaýım qosındılawdı
şeşiñiz de, nätïjesin awmaqqa engiziñiz ([[{{ns:special}}:Captcha/help|köbirek aqparat]]):',
	'captcha-createaccount-fail' => "Kwälandırw kodı durıs emes nemese joq.",
	'captcha-create' => 'Jaña betti bastaw üşin, tömendegi qarapaýım qosındılawdı şeşiñiz de, 
nätïjesin awmaqqa engiziñiz ([[{{ns:special}}:Captcha/help|köbirek aqparat]]):',
	'captchahelp-title'          => 'CAPTCHA anıqtaması',
	'captchahelp-cookies-needed' => "Bul jumıs istew üşin, şolğışıñızda  «cookies»  degendi endiriñiz.",
	'captchahelp-text'           => "Özdiktik quraldarı bar «spammerler», barşadan jiberilgen xabardı qabıldaýtın, bul wïkï sïyaqtı, veb-toraptarğa siltemelerimen jïi jawdıradı. Osındaý «spam» siltemelerin alastaw bolğanda da, bul mändi ıza keltiredi.

Keýde, äsirese betke jaña veb siltemesin qosqanda, wïkï öñi özgergen ne qïsaýğan mätindi körsetip jäne sol sözderdi engizw surawı mümkin. Bul tapsırıs özdik türde atqarw öte qïın, sondıqtan bul ïmandı adam köpşiligine kedergi bolmaýdı, biraq «spammerlerdi» jäne buzaqı botpen basqa şabwıl jasağandardı toqtatadı.

Ökinişke oraý, bul körwi tömendegen, nemese mätin ne dawıs negizindegi şolğıştı qoldanatın paýdalanwşığa ıñğaýsızdıq keltirwge mümkin. Osı qazir bizde dıbıstı balama joq. Eger bul adal jazwıñızğa kedergilese, torap baqılawşılarına qatınasıñız.

Bul jumıstı istew üşin şolğışıñızda «cookies» degendi endirw qajet.

Bet öñdewine qaýtw barw üşin «Artqa» degen tüýmesin basıñız."
);
$messages['kk-arab'] = array(
	'captcha-edit' => 'بۇل بەتتٸ ٶڭدەۋ ٷشٸن, تٶمەندەگٸ قوسىندىلاۋدى شەشٸڭٸز دە, نٵتيجەسٸن 
اۋماققا ەنگٸزٸڭٸز ([[{{ns:special}}:Captcha/help|كٶبٸرەك اقپارات]]):',
	'captcha-addurl' => 'تٷزەتۋٸڭٸزدە جاڭا سىرتقى سٸلتەمەلەر بار ەكەن. ٶزدٸكتٸك «سپام» جاسالۋىنان قورعانۋ ٷشٸن,
تٶمەندەگٸ قاراپايىم قوسىندىلاۋدى شەشٸڭٸز دە, نٵتيجەسٸن اۋماققا ەنگٸزٸڭٸز ([[{{ns:special}}:Captcha/help|كٶبٸرەك اقپارات]]):',
	'captcha-badlogin' => 'قۇپييا سٶزدٸ ٶزدٸكتٸك قيراتۋدان قورعانۋ ٷشٸن,
تٶمەندەگٸ قاراپايىم قوسىندىلاۋدى شەشٸڭٸز دە, نٵتيجەسٸن اۋماققا ەنگٸزٸڭٸز ([[{{ns:special}}:Captcha/help|كٶبٸرەك اقپارات]]):',
	'captcha-createaccount' => 'جاڭا تٸركەلگٸ ٶزدٸكتٸك جاسالۋىنان قورعانۋ ٷشٸن, تٶمەندەگٸ قاراپايىم قوسىندىلاۋدى
شەشٸڭٸز دە, نٵتيجەسٸن اۋماققا ەنگٸزٸڭٸز ([[{{ns:special}}:Captcha/help|كٶبٸرەك اقپارات]]):',
	'captcha-createaccount-fail' => "كۋٵلاندىرۋ كودى دۇرىس ەمەس نەمەسە جوق.",
	'captcha-create' => 'جاڭا بەتتٸ باستاۋ ٷشٸن, تٶمەندەگٸ قاراپايىم قوسىندىلاۋدى شەشٸڭٸز دە, 
نٵتيجەسٸن اۋماققا ەنگٸزٸڭٸز ([[{{ns:special}}:Captcha/help|كٶبٸرەك اقپارات]]):',
	'captchahelp-title'          => 'CAPTCHA انىقتاماسى',
	'captchahelp-cookies-needed' => "بۇل جۇمىس ٸستەۋ ٷشٸن, شولعىشىڭىزدا  «cookies»  دەگەندٸ ەندٸرٸڭٸز.",
	'captchahelp-text'           => "ٶزدٸكتٸك قۇرالدارى بار «سپاممەرلەر», بارشادان جٸبەرٸلگەن حاباردى قابىلدايتىن, بۇل ۋيكي سيياقتى, ۆەب-توراپتارعا سٸلتەمەلەرٸمەن جيٸ جاۋدىرادى. وسىنداي «سپام» سٸلتەمەلەرٸن الاستاۋ بولعاندا دا, بۇل مٵندٸ ىزا كەلتٸرەدٸ.

كەيدە, ٵسٸرەسە بەتكە جاڭا ۆەب سٸلتەمەسٸن قوسقاندا, ۋيكي ٶڭٸ ٶزگەرگەن نە قيسايعان مٵتٸندٸ كٶرسەتٸپ جٵنە سول سٶزدەردٸ ەنگٸزۋ سۇراۋى مٷمكٸن. بۇل تاپسىرىس ٶزدٸك تٷردە اتقارۋ ٶتە قيىن, سوندىقتان بۇل يماندى ادام كٶپشٸلٸگٸنە كەدەرگٸ بولمايدى, بٸراق «سپاممەرلەردٸ» جٵنە بۇزاقى بوتپەن باسقا شابۋىل جاساعانداردى توقتاتادى.

ٶكٸنٸشكە وراي, بۇل كٶرۋٸ تٶمەندەگەن, نەمەسە مٵتٸن نە داۋىس نەگٸزٸندەگٸ شولعىشتى قولداناتىن پايدالانۋشىعا ىڭعايسىزدىق كەلتٸرۋگە مٷمكٸن. وسى قازٸر بٸزدە دىبىستى بالاما جوق. ەگەر بۇل ادال جازۋىڭىزعا كەدەرگٸلەسە, توراپ باقىلاۋشىلارىنا قاتىناسىڭىز.

بۇل جۇمىستى ٸستەۋ ٷشٸن شولعىشىڭىزدا «cookies» دەگەندٸ ەندٸرۋ قاجەت.

بەت ٶڭدەۋٸنە قايتۋ بارۋ ٷشٸن «ارتقا» دەگەن تٷيمەسٸن باسىڭىز."
);
$messages['ko'] = array(
	'captcha-createaccount'       => '자동 가입을 막기 위해, 아래 문제의 답을 적어야만 가입이 가능합니다([[Special:Captcha/help|관련 도움말]]):',
);

$messages['la'] = array(
	'captcha-edit'                => 'Ad hanc paginam recensendum, necesse est tibi solvere calculationem subter et responsum in capsam inscribere ([[Special:Captcha/help|Quidst illud?]]):',
	'captcha-addurl'              => 'Emendatione tua insunt nexus externi; ut spam automaticum vitemus, necesse est tibi solvere calculationem subter et responsum in capsam inscribere ([[Special:Captcha/help|Quidst illud?]]):',
	'captcha-badlogin'             => 'Ut vitemus ne tesserae frangantur, necesse est tibi solvere calculationem subter et responsum in capsam inscribere ([[Special:Captcha/help|Quidst illud?]]):',
	'captcha-createaccount'       => 'Ut creationem rationum automaticam vitemus, necesse est tibi solvere calculationem subter et responsum in capsam inscribere ([[Special:Captcha/help|Quidst illud?]]):',
	'captcha-createaccount-fail'  => 'Codex affirmationis aut non scriptus est aut male.',
	'captcha-create'              => 'Ad paginam creandum, necesse est tibi solvere calculationem subter et responsum in capsam inscribere ([[Special:Captcha/help|Quidst illud?]]):',
	'captchahelp-title'           => 'Captcha auxilium',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'captcha-edit'               => "Fir dësen Artikel ze veränneren, léist w.e.g. déi folgend Rechenaufgab a gitt d'Resultat an d'Këscht ënnendrënner an ([[Special:Captcha/help|méi Informatiounen]]):",
	'captcha-addurl'             => 'An ärer Ännerung sinn nei extern Linken. Fir eis virun automatiséiertem Spamming ze schütze froe mir iech fir déi folgend einfach  Rechenaufgab ze léisen an d\'Resultat an d\'Feld ënnen anzedroën. Klickt duerno w.e.g. nach eng Kéier op "Säit ofspäicheren" [[Special:Captcha/help|méi Informatiounen]].',
	'captcha-badlogin'           => "Fir eis géint automatescht Hacke vu Passwierder ze schützen, léist w.e.g. déi einfach Additioun hei ënnendrënner an tippt d'Äntwert an d'Këscht ([[Special:Captcha/help|méi Informatiounen]]):",
	'captcha-createaccount'      => "Fir eis géint d'autamescht Uleeë vu Benotzerkonten ze schützen, léist w.e.g. déi einfach Additioun hei ënnendrënner an tippt d'Äntwert an d'Këscht ([[Special:Captcha/help|méi Informatiounen]]):",
	'captcha-createaccount-fail' => 'Falschen oder keen Konfirmatiouns-Code.',
	'captcha-create'             => "Fir eng Nei Säit unzleeën, léist w.e.g. déi einfach Additioun hei ënnendrënner an tippt d'Äntwert an d'Këscht ([[Special:Captcha/help|méi Informatiounen]]):",
	'captchahelp-title'          => 'Captcha-Hëllef',
	'captchahelp-cookies-needed' => 'Dir musst Cookieën an ärem Browser erlaben fir dat dëst fonktionéiert.',
	'captchahelp-text'           => "Websiten, déi jidferengem et erlaben Ännerunge virzehuelen, sou wéi op dëser wiki, ginn dacks vu sougenannte Spammer mëssbraucht, déi automatiséiert hir Linkën op vill Internetsitë setzen. Esou Spam-Linkë kënne wuel geläscht ginn, mee si sinn trotzdem e grousst Iergernëss.

Heiandsdo, besonnesch wann nei Internet-Linkën op eng Säit derbäigesat ginn, weist dës Wiki iech e Bild mat farwegëm oder verzerrtëm Text a freet iech fir déi gewise Wierder anzetipppen. Well dëst eng Aufgab ass déi schwéier ze automatiséieren ass, erlaabt dëst dat Mënschen hir Ännerunge kënnen agi wärend déi meescht Spammer an aner Roboter-Attacken kënnen ofgewiert ginn.

Leider kann dëst zu Schwierigkeete féiere fir Persounen déi net esou gutt gesinn oder déi text-baséiert oder sprooch-baséiert Browser benotzen. Zu dësem Zäitpunkt hu mir leider keng audio-Alternativ zu eiser Verfügung. Eng Léisung ass d'Umeldung als Benotzer oder kontaktéiert een Administrateur fir Hëllef wann dëst iech onerwarter Wäis vu legitimen Editen ofhält.

Dréckt op den 'Zréck' Knäppchen vun ärem Browser fir an d'Beaarbectungsfënster zréckzekommen.",
	'captcha-addurl-whitelist'   => '
  #<!-- leave this line exactly as it is --> <pre>  
#  Syntax:
#  * Alles mat engem #-Zeechen u bis zum Enn vun däer Zeil ass eng Bemierkung
#  * All Zeil déi net eidel ass, ass ee Regex-Fragment, dat nëmme mat Hosten bannent URLë fonktionéiert
  #</pre> <!-- leave this line exactly as it is -->',
);

$messages['lo'] = array(
	'captcha-edit'               => 'ການດັດແກ້ ຂອງ ທ່ານ ມີລິ້ງູຄ໌ພາຍນອກ. ເພື່ອ ເປັນການຊ່ອຍປ້ອງກັນ ສະແປມອັດຕະໂນມັດ, ກະລຸນາແກ້ເລກບວກ ງ່າຍໆຂ້າງລຸ່ມນີ້ ແລ້ວ ພິມຄຳຕອບໃສ່ໃນ ກັບ ([[Special:Captcha/help|more info]]):',
	'captcha-addurl'             => 'ການດັດແກ້ຂອງທ່ານ ມີ ການກາງລິ້ງຄ໌ຫາພາຍນອກ. ເພື່ອເປັນການຊ່ອຍປ້ອງກັນ ສະແປມອັດຕະໂນມັດ ກະລຸນາ ແກ້ເລກບວກງ່າຍໆຂ້າງລຸ່ມນີ້ ແລ້ວ ພິມຜົນບວກ ໃສ່ ກັບ ([[Special:Captcha/help|ຂໍ້ມູນເພີ່ມເຕີມ]]):',
	'captcha-createaccount'      => 'ເພື່ອປ້ອງກັນ ການສ້າງບັນຊີແບບອັດຕະໂນມັດ, ກະລຸນາ ແກ້ເລກບວກງ່າຍໆ ຂ້າງລຸ່ມ ແລ້ວ ພິມຄຳຕອບໃສ່ ກັບ ([[Special:Captcha/help|more info]]):',
	'captcha-createaccount-fail' => "ບໍ່ຖືກ ຫຼື ບໍ່ມີລະຫັດຢືນຢັນ.",
	'captcha-create'             => 'ກະລຸນາ ແກ້ເລກບວກງ່າຍໆລຸ່ມນີ້ ແລະ ພິມຜົນບວກໃສ່ໃນກັບ ເພື່ອ ສ້າງໜ້ານີ້ ([[Special:Captcha/help|ຂໍ້ມູນເພີ່ມເຕີມ]]):',
);

$messages['lv'] = array(
	'captcha-edit'               => "Tavas izmaiņas ietver jaunu URL saiti. Lai pasargātos no automātiskas mēstuļošanas, Tev ir jāieraksta vārds, kas redzams šajā attēlā: <br />([[Special:Captcha/help|Kāpēc tā?]])",
	'captcha-addurl'               => "Tavas izmaiņas ietver jaunu URL saiti. Lai pasargātos no automātiskas mēstuļošanas, Tev ir jāieraksta vārds, kas redzams šajā attēlā: <br />([[Special:Captcha/help|Kāpēc tā?]])",
	'captcha-create'               => "Tavas izmaiņas ietver jaunu URL saiti. Lai pasargātos no automātiskas mēstuļošanas, Tev ir jāieraksta vārds, kas redzams šajā attēlā: <br />([[Special:Captcha/help|Kāpēc tā?]])",
	'captchahelp-title'          => 'Captcha help',
	'captchahelp-text'           => "Interneta lapas, kurās iespējams pievienot tekstu, kā šajā wiki, bieži cieš no mēstuļotājiem, kuri izmanto automatizētus līdzekļus, lai pievienotu savus saites daudzās jo daudzās interneta lapās. Kaut arī šīs saites var viegli dzēst, tomēr tās ir nozīmīgs traucēklis. Reizēm, jo īpaši pievienojot jaunas interneta saites, wiki programmatūra var parādīt Tev attēlu, kurā ir krāsains vai sagrozīts teksts. Šis teksts ir jāpārraksta un to ir ļoti grūti izdarīt automātiski, tā apgrūtinot lielāko daļu mēstuļotāju, savukārt gandrīz visi parastie lietotāji to var izdarīt bez grūtībām. Diemžēl tas var apgrūtināt lietotājus, kuriem ir redzes traucējumi vai kuri izmanto teksta pārlūkus vai dzirdes pārlūkus. Šobrīd nav pieejama audio alternatīva, bet sazinies ar wiki administratoriem, ja tas liedz Tev veikt labi domātus papildinājumus. Spied pārlūka pogu \"Atpakaļ\" (''Back''), lai atgrieztos iepriekšējā lapā.",
	'captcha-createaccount'      => "Lai pasargātos no automātiskas mēstuļošanas, Tev reģistrējoties ir jāieraksta vārds, kas redzams šajā attēlā: <br />([[Special:Captcha/help|Kāpēc tā?]])",
	'captcha-createaccount-fail' => "Nepareizs apstiprinājuma kods vai arī tas nav ievadīts.",
);

$messages['nan'] = array(
	'captcha-createaccount'      => "Ūi beh ī-hông lâng iōng ke-si chū-tōng chù-chheh koh tah kóng-kò, chhiáⁿ lí kā chhut-hiān tī ang-á lāi-bīn ê jī phah 1 piàn (thang chèng-bêng lí m̄ sī ki-khì-lâng): <br />
([[Special:Captcha/help|Che sī siáⁿ-hòe?]])",
	'captcha-createaccount-fail' => "Khak-jīn-bé chhò-gō· iah-sī làu-kau.",
);
$messages['nds'] = array(
	'captcha-edit'                => 'In dien Text steiht en nee Lenk na buten dat Wiki. Dat hier keen automaatsch instellten Spam rinkummt, musst du disse lütte Rekenopgaav lösen ([[Special:Captcha/help|mehr dorto]]):',
	'captcha-createaccount'       => 'Dat hier nich Brukers automaatsch anleggt warrt, musst du disse lütte Rekenopgaav lösen ([[Special:Captcha/help|mehr dorto]]):',
	'captcha-createaccount-fail'  => 'Kood to’n Bestätigen is verkehrt oder fehlt.',
	'captchahelp-title'           => 'Help to Captchas',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre> 
# Op disse Siet staht de Websteden, bi de en Bruker,
# de nich anmellt is un en ne’en Lenk in de Siet infögen deit,
# keen Captcha utfüllen mutt.
# 
# Syntax is disse: 
#   * Allens vun en „#“-Teken bet na’t Enn vun de Reeg is en Kommentar
#   * All de annern Regen, de nich leddig sünd, warrt as regulären Utdruck bekeken,
#     de för Delen vun de Domään steiht.

 #</pre> <!-- leave this line exactly as it is -->',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'captcha-edit'               => "Uw bewerking bevat nieuwe externe links (URL's). Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn:<br />
([[Special:Captcha/help|Wat is dit?]])",
	'captcha-addurl'             => "Uw bewerking bevat nieuwe externe links (URL's). Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn:<br />
([[Special:Captcha/help|Wat is dit?]])",
	'captcha-badlogin'           => 'Los alstublieft de onderstaande eenvoudige som op en voer het antwoord in het invoervenster in ter bescherming tegen het automatisch kraken van wachtwoorden ([[Special:Captcha/help|meer informatie]]):',
	'captcha-createaccount'      => 'Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn om uw gebruiker aan te maken:<br />
([[Special:Captcha/help|Wat is dit?]])',
	'captcha-createaccount-fail' => 'Onjuiste of ontbrekende bevestigingscode.',
	'captcha-create'             => "Uw bewerking bevat nieuwe externe links (URL's). Voer ter bescherming tegen geautomatiseerde spam de woorden in die in de volgende afbeelding te zien zijn:<br />
([[Special:Captcha/help|Wat is dit?]])",
	'captchahelp-title'          => 'Captcha-hulp',
	'captchahelp-cookies-needed' => 'U dient in uw browser cookies ingeschakeld te hebben om dit te laten werken.',
	'captchahelp-text'           => "Websites die vrij te bewerken zijn, zoals deze wiki, worden vaak misbruikt door spammers die er met hun programma's automatisch links op zetten naar vele websites. Hoewel deze externe links weer verwijderd kunnen worden, leveren ze wel veel hinder en administratief werk op.

Soms, en in het bijzonder bij het toevoegen van externe links op pagina's, toont de wiki u een afbeelding met gekleurde of vervormde tekst en wordt u gevraagd de getoonde tekst in te voeren. Omdat dit proces lastig te automatiseren is, zijn vrijwel alleen mensen in staat dit proces succesvol te doorlopen en worden hiermee spammers en andere geautomatiseerde aanvallen geweerd.

Helaas levert deze bevestiging voor gebruikers met een visuele handicap of een tekst- of spraakgebaseerde browser problemen op. Op het moment is er geen alternatief met geluid beschikbaar. Vraag alstublieft assistentie van de sitebeheerders als dit proces u verhindert een nuttige bijdrage te leveren.

Klik op de knop 'terug' in uw browser om terug te gaan naar het tekstbewerkingsscherm.",
	'captcha-addurl-whitelist'   => ' #<!-- laat deze lijn zoals hij is --> <pre> 
# De syntaxis is als volgt: 
#   * Alle tekst vanaf het karakter "#" tot het einde van de regels wordt gezien als opmerking
#   * Iedere niet-lege regel is een fragment van een reguliere uitdrukking die alleen van toepassing is op hosts binnen URL\'s
 #</pre> <!-- laat deze lijn zoals hij is -->',
);

$messages['no'] = array(
	'captcha-edit'                => 'For å redigere denne artikkelen, vennligst skriv inn summen nedenfor i boksen ([[Special:Captcha/help|mer informasjon]]):',
	'captcha-addurl'              => 'Din redigering inneholder nye eksterne lenker. For å hjelpe oss å beskytte oss mot automatisk spam, vennligst skriv inn summen av dette enkle regnestykket i boksen nedenfor ([[Special:Captcha/help|mer informasjon]]):',
	'captcha-badlogin'             => 'For å hjelpe oss med å beskytte oss mot automatisk passordtyveri, vennligst løs det enkle regnestykket nedenfor og skriv inn svaret i bosken ([[Special:Captcha/help|mer informasjon]]):',
	'captcha-createaccount'       => 'For å hjelpe oss med å beskytte oss mot automatisk kontoopprettelse, vennligst skriv inn summen av det enkle regnestykket i boksen nedenfor ([[Special:Captcha/help|mer informasjon]]):',
	'captcha-createaccount-fail'  => 'Ukorrekt eller manglende bekreftelseskode.',
	'captcha-create'              => 'For å opprette siden, vennligst skriv inn summen av det enkle regnestyket i boksen nedenfor ([[Special:Captcha/help|mer informasjon]]):',
	'captchahelp-title'           => 'Captcha-hjelp',
	'captchahelp-cookies-needed'  => 'Du må slå på informasjonskapsler for at dette skal fungere.',
	'captchahelp-text'            => 'Internettsider som kan redigeres av alle, som denne wikien, blir ofte misbrukt av spammere som bruker roboter for å poste massive antall lenker. Selv om slike spamlenker kan fjernes er de til betydelig irritasjon.

Noen ganger, særlig hvis du vil legge til nye internettlenker til en side, kan wikien vise deg et bilde av en farvet eller ujevn tekst og be deg skrive inn ordene som vises. Siden det er vanskelig å automatisere denne oppgaven, vil funksjonen slippe de fleste virkelige mennesker igjennom, men stoppe spammere.

Dessverre finnes det i øyeblikket ikke noe audioalternativ for brukere med begrenset syn som som bruker tekst- eller talebaserte nettlesere. Vennlig kontakt administratorene hvis denne funksjonen forhindrer deg i å foreta legitime endringer.

Trykk på «tilbake»-knappen for å komme tilbake til redigeringssiden.',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre> 
# Syntaksen er som fløger:
#   * Alle linjer som begynner med «#» er kommentarer
#   * Alle linjer som ikke er blanke er fragmenter av regulære uttrykk som sjekker verter i URL-er
 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['nn'] = array(
	'captcha-edit'               => "Endringa di inkluderer nye lenkjer; som eit vern mot automatisert reklame (spam) er du nøydd til skrive inn orda i dette bildet: <br />([[Special:Captcha/help|Kva er dette?]])",
	'captcha-addurl'               => "Endringa di inkluderer nye lenkjer; som eit vern mot automatisert reklame (spam) er du nøydd til skrive inn orda i dette bildet: <br />([[Special:Captcha/help|Kva er dette?]])",
	'captcha-create'               => "Endringa di inkluderer nye lenkjer; som eit vern mot automatisert reklame (spam) er du nøydd til skrive inn orda i dette bildet: <br />([[Special:Captcha/help|Kva er dette?]])",
	'captchahelp-title'          => 'Captcha help',
	'captchahelp-text'           => "Internettsider som kan bli endra av alle, som denne wikien, blir ofte misbruka av reklameinnleggjarar (spammarar) som nyttar robotar til å poste store tal lenkjer. Sjølv om slike reklamelenkjer kan bli fjerna er dei til betydelig irritasjon. Nokon gonger, særleg viss du vil leggje til nye internettlenker til ei side, kan wikien vise deg eit bilete av ein farga eller ujamn tekst og be deg skrive inn orda som blir visa. Sidan det er vanskeleg å automatisere denne oppgåva, vil funksjonen slippe dei fleste verkelege menneska gjennom, men stoppe reklamerobotar. Diverre finst det i augeblikket ikkje noko lydalternativ for brukarar med nedsett syn som brukar tekst- eller talebaserte nettlesarar. Ver venleg å kontakte administratorane viss denne funksjonen hindrar deg i å foreta legitime endringar. Trykk på «tilbake»-knappen for å komma tilbake til redigeringssida.",
	'captcha-createaccount'      => "For å verne Wikipedia mot reklame (spam) må du skrive inn orda i biletet for å registrere ein konto. <br />([[Special:Captcha/help|Kva er dette?]])",
	'captcha-createaccount-fail' => "Feil eller manglande godkjenningskode.",
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'captcha-edit'               => "Vòstra modificacion inclutz de ligams URL novèla ; per empachar las connexions automatizadas, devètz picar los mots que s’afichan dins l’imatge que seguís : <br />([[Special:Captcha/help|Qu'es aquò?]])",
	'captcha-addurl'             => "Vòstra modificacion inclutz de ligams URL novèla ; per empachar las connexions automatizadas, devètz picar los mots que s’afichan dins l’imatge que seguís : <br />([[Special:Captcha/help|Qu'es aquò?]])",
	'captcha-badlogin'           => "Per ensajar de contornar las temptativas de cracatge de senhals automatizadas per de robòts, recopiatz lo tèxt çaijós dins la boita de tèxt plaçada al dejos d'aqueste. ([[Special:Captcha/help|Mai d’entre-senhas]])",
	'captcha-createaccount'      => 'Coma proteccion contra las creacions de compte abusivas, entratz lo resultat de l’addicion dins la boita çaijós:<br />
([[Special:Captcha/help|mai d’informacions]])',
	'captcha-createaccount-fail' => 'Còde de confirmacion mancant o erronèu.',
	'captcha-create'             => "Vòstra modificacion inclutz de ligams URL novèla ; per empachar las connexions automatizadas, devètz picar los mots que s’afichan dins l’imatge que seguís : <br />([[Special:Captcha/help|Qu'es aquò?]])",
	'captchahelp-title'          => 'Ajuda suls Captcha',
	'captchahelp-cookies-needed' => "Devètz aver los cookies activats dins vòstre navegaire per qu'aquò foncione.",
	'captchahelp-text'           => "Los sites webs que permeton al mai grand nombre de participar, coma aqueste wiki, son sovent atacats per de spammers qu'utilizan d'espleches automatizas per mandar lor ligams sus de fòrça sites sulcòp. Son fòrt aisits de suprimir mas avèm francament de causas mai risolièras de far. De còps quand ajustatz de ligams novèls vèrs lo web, lo wiki pòt vos mostrar un imatge amb un tèxt coloriat o torçut e vos demandar de lo picar. Es una tasca relativament complicada d'automatizar, çò que permet de diferenciar un uman real d'un logicial automatic malvolent. Malaürosament, aqueste sistèma es pas adaptat a d'utilizaires mal-vesents o utilizant de navigaires textuals o audiò. Actualament, prepausem pas d'alternativas adaptadas. Se avètz besonh d'ajuda esitetz pas a contactar los administrators del sit. Clicatz sul boton 'precedent' de vòstre navegaire per tornar a l'editor.",
	'captcha-addurl-whitelist'   => '#<!-- daissatz aquesta linha exactament tala coma es --> <pre> # La sintaxi es la seguenta: # * Tot caractèr seguissent "#" fins a la fin de la linha serà interpretat coma un comentari # * Tota linha non voida es un còde regex que serà utilizat unicament a l\'interior dels ligams hypertext. #</pre> <!-- daissatz aquesta linha exactament tala coma es -->',
);

$messages['pl'] = array(
	'captcha-edit'                => 'Aby edytować tę stronę musisz rozwiązać proste działanie matematyczne poniżej i wpisać wynik do pola tekstowego ([[Special:Captcha/help|wyjaśnienie]]):',
	'captcha-addurl'              => 'Twoja edycja zawiera nowe linki zewnętrzne. Ze względu na ochronę przed zautomatyzowanym spamem prosimy wykonać proste działanie matematyczne i wpisać wynik w pole tekstowe ([[Special:Captcha/help|więcej informacji]]):',
	'captcha-badlogin'            => 'Ze względu na zabezpieczenie przed automatycznym łamaniem haseł prosimy o rozwiązanie tego prostego zadania i wpisanie odwiedzi w pole obok ([[Special:Captcha/help|więcej informacji]])',
	'captcha-createaccount'       => 'Ze względu na ochronę przed automatycznym [[wikipedia:pl:spam|spamem]], aby zarejestrować się musisz wpisać w pole poniżej wynik prostego działania matematycznego ([[Special:Captcha/help|wyjaśnienie]]):',
	'captcha-createaccount-fail'  => 'Niepoprawny kod lub brak kodu potwierdzajacego.',
	'captcha-create'              => 'Aby utworzyć stronę wykonaj proste działanie i wpisz wynik w pole tekstowe ([[Special:Captcha/help|więcej informacji]]):',
	'captchahelp-title'           => 'Pomoc dla ochrony antyspamowej',
	'captchahelp-cookies-needed'  => 'Musisz mieć włączone w przeglądarce ciasteczka (cookies), aby ta opcja działała.',
	'captchahelp-text'            => 'Strony internetowe akceptujące edycje dokonywane przez każdego, jak to wiki, są często atakowane przez [[wikipedia:pl:spam|spammerów]], którzy używają automatycznych narzędzi, by dodawać linki do ich stron. Chociaż te linki mogą być usunięte, jest to uciążliwe. Czasami, zwłaszcza przy dodawaniu nowych linków do strony albo przy rejestracji, wiki może pokazać obrazek z kolorowym lub zniekształconym tekstem i poprosić cię o wpisanie pokazanych wyrazów. Może pojawić się też prośba o wpisanie wyniku prostego działania matematycznego. Ponieważ jest to zadanie, które ciężko jest zautomatyzować, pozwoli większości zwykłych ludzi dokonywać ich edycji, powstrzymując jednocześnie większość spammerów i inne automatyczne ataki. Niestety, może być to niewygodne dla użytkowników z wadą wzroku lub używających przeglądarek tekstowych lub głosowych. W tym momencie nie mamy dostępnej alternatywy audio. Skontaktuj się z administratorami strony by uzyskać pomoc, jeśli nie możesz z tego powodu dokonywać prawidłowych edycji. Zwróć uwagę, że musisz mieć włączone ciasteczka (cookies). Wciśnij przycisk "wstecz" w twojej przeglądarce by powrócić do edycji strony.',
	'captcha-addurl-whitelist'    => ' #<!-- zostaw tę linię dokładnie jak jest --> <pre> 
# Składnia jest następująca: 
#   * Linie zaczynające się od znaku "#" są komentarzami
#   * Każda linia, która nie jest pusta, jest fragmentem wyrażenia regularnego, które ma pasować do adresów wewnątrz adresów URL
 #</pre> <!-- zostaw tę linię dokładnie jak jest -->',
);
$messages['pms'] = array(
	'captcha-edit'                => 'Për fe-ie dle modìfiche ansima a st\'artìcol-sì, për piasì ch\'a fasa ël total ambelessì sota 
e ch\'a buta l\'arzulta ant ël quadrèt ([[Special:Captcha/help|për savejne dë pì]]):',
	'captcha-addurl'              => 'Soa modìfica a la gionta dj\'anliure esterne. Për giutene a vardesse da la reclam aotomatisà, për piasì ch\'a fasa ël total ambelessì sota e ch\'a buta l\'arzultà ant ël quadrèt ([[Special:Captcha/help|për savejne dë pì]]):',
	'captcha-badlogin'             => 'Për giutene a vardesse da \'nt ij programa ch\'a fan ciav fàosse n\'aotomàtich, për piasì ch\'a fasa ël total ambelessì sota e ch\'a buta l\'arzultà ant ël quadrèt ([[Special:Captcha/help|për savejne dë pì]]):',
	'captcha-createaccount'       => 'Për giutene a vardesse da ij programa ch\'a deurbo dij cont n\'aotomàtich, për piasì ch\'a fasa ël total ambelessì sota 
e ch\'a buta l\'arzultà ant ël quadrèt ([[Special:Captcha/help|për savejne dë pì]]):',
	'captcha-createaccount-fail'  => 'Ël còdes ëd verìfica ò ch\'a manca d\'autut ò ch\'a l\'é pa bon.',
	'captcha-create'              => 'Për creé d\'amblé sta pàgina-sì, për piasì ch\'a fasa ël total ambelessì sota e ch\'a buta l\'arzultà 
ant ël quadrèt ([[Special:Captcha/help|për savejne dë pì]]):',
	'captchahelp-title'           => 'Còs é-lo mai ës captcha?',
	'captchahelp-cookies-needed'  => 'Për podej dovré sossì a l\'ha da manca che sò navigator (browser) a pija ij cookies.',
	'captchahelp-text'            => 'Soèns a-i riva che ij sit dla Ragnà che la gent a peul dovré për ëscrive chèich-còs, coma sta wiki-sì, a resto ambërlifà ëd reclam da màchine che a carìo soa ròba dadsà e dadlà n\'aotomàtich. Për tant che sta reclam un a peula peuj gavela, a resta sempe un gran fastudi.

Dle vire, dzortut quand un a caria dj\'anliure esterne neuve ansime a na pàgina, la wiki a peul ësmon-je na figurin-a con dël test colora ò pura tut ëstòrt e ciameje d\'arbate lòn ch\'a-i é scrit andrinta. Da già ch\'a l\'é grama scrive un programa ch\'a lo fasa, a ven che la pì gran part dla gent a-i la fa a scrive, ma la ói part dle màchine a-i la fa pa.

Për maleur sossì a peul fastudié j\'uetnt ch\'a ës-ciàiro nen tant bin, col ch\'a dòvro dij navigator mach a test ò pura dij navigator vocaj. Për adess i l\'oma nen n\'altërnativa disponibila ch\'a fasa lese ël test a vos. Për piasì, ch\'a contata j\'aministrator dël sit se sossì a dovèissa mai nen lasseje carié dël test ch\'a sia legitim (visadì, nen dla reclam).

Ch\'a-i bata ansima al boton \'andré\' ant sò navigator për torné andré a l\'editor dla pàgina.',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre> 
# La sintassi a l\'é costa: 
#   * tut lòn ch\'a-i ven dapress a un caràter "#" (fin-a a la fin dla riga) a l\'é mach ëd coment
#   * minca riga nen veujda a l\'é un frament d\'espression regolar ch\'as dòvra për identifiché j\'adrësse dle màchine servente ant j\'anliure
 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['pt'] = array(
	'captcha-edit'                => 'Para editar esta página será necessário que você digite as palavras exibidas na seguinte imagem no box apropriado ([[Special:Captcha/help|o que é isto?]])',
	'captcha-addurl'              => 'Sua edição inclui novas ligações externas; como prevenção contra sistemas automatizados que inserem spam, será necessário que você digite as palavras exibidas na seguinte imagem no box apropriado ([[Special:Captcha/help|o que é isto?]])',
	'captcha-badlogin'             => 'Como prevenção contra formas automatizadas de pesquisa e descoberta de senhas, será necessário que você digite as palavras exibidas na seguinte imagem no box apropriado ([[Special:Captcha/help|o que é isto?]])',
	'captcha-createaccount'       => 'Como prevenção contra sistemas automatizados que inserem spam, será necessário que você digite as palavras exibidas na seguinte imagem para que possa se cadastrar:<br />
([[Special:Captcha/help|O que é isto?]])',
	'captcha-createaccount-fail'  => 'Código incorreto ou não preenchido.',
	'captcha-create'              => 'Como prevenção contra sistemas automatizados que inserem spam, será necessário que você digite as palavras exibidas na seguinte imagem no box apropriado ([[Special:Captcha/help|o que é isto?]])',
	'captchahelp-title'           => 'Ajuda com o Captcha',
	'captchahelp-cookies-needed'  => 'Você precisará ter cookies habilitados em seu navegador para que possa funcionar',
	'captchahelp-text'            => 'Sítios abertos a inserções públicas, como é o caso deste wiki, são vulneráveis a spammers que utilizem ferramentas automatizadas para inserir seus links em diversos locais. Remover tais links posteriormente poderá ser um significativo incômodo.

Algumas vezes, especialmente ao adicionar novos links externos em uma página, o sistema exibirá uma imagem com um texto colorido ou distorcido e pedirá que você digite as palavras exibidas. Uma vez que essa é uma tarefa um tanto difícil de ser automatizada, ela possibilita que vários humanos façam as suas inserções ao mesmo tempo que inibe as que forem feitas por spammers e mecanismos automatizados.

Infelizmente, isso pode ser dificultoso a utilizadores com limitações visuais ou que naveguem através de  mecanismos baseados em texto ou baseados em voz. No momento não há uma alternativa em áudio. Por gentileza, contacte os administradores do sítio em casos que seja necessária a assistência de alguém para que você possa fazer as suas inserções legítimas.

Pressione o botão \'voltar\' de seu navegador para retornar à página de edição.',
	'captcha-addurl-whitelist'    => ' #<!-- mantenha esta linha exatamente desta forma --> <pre> 
# Esta é a sintaxe: 
#   * Tudo o que estiver sendo exibido em uma linha após o caractere "#" é um comentário;
#   * Todas as linhas que não estiverem em branco são um fragmento de regex, as quais referem-se aos apenas através de URLs;
 #</pre> <!-- mantenha esta linha exatamente desta forma -->',
);
$messages['pt-br'] = $messages['pt'];

$messages['ro'] = array(
	'captcha-edit'                => 'Editarea include legături externe noi. Pentru a evita spam-ul automat, vă rugăm să rezolvaţi adunarea de mai jos şi introduceţi rezultatul în căsuţă ([[Special:Captcha/help|detalii]]):',
	'captcha-addurl'              => 'Editarea include legături externe noi. Pentru a evita spam-ul automat, vă rugăm să rezolvaţi adunarea de mai jos şi introduceţi rezultatul în căsuţă ([[Special:Captcha/help|detalii]]):',
	'captcha-badlogin'             => 'Ca măsură de protecţie împotriva spargerii de parole, vă rugăm să rezolvaţi adunarea de mai jos şi introduceţi rezultatul în căsuţă ([[Special:Captcha/help|detalii]]):',
	'captcha-createaccount'       => 'Pentru a evita crearea automată de conturi, vă rugăm să rezolvaţi adunarea de mai jos şi introduceţi rezultatul în căsuţă ([[Special:Captcha/help|detalii]]):',
	'captcha-createaccount-fail'  => 'Cod de confirmare incorect sau lipsă.',
	'captcha-create'              => 'Editarea include legături externe noi. Pentru a evita spam-ul automat, vă rugăm să rezolvaţi adunarea de mai jos şi introduceţi rezultatul în căsuţă ([[Special:Captcha/help|detalii]]):',
	'captchahelp-title'           => 'Despre „Captcha”',
	'captchahelp-cookies-needed'  => 'Trebuie ca browserul dumneavoastră să accepte cookie-uri pentru ca aceasta să funcţioneze.',
	'captchahelp-text'            => 'Siturile Web care acceptă postări din partea publicului, precum acest wiki, sunt de obicei abuzate de persoane care folosesc unelte automate pentru a introduce legături către multe situri. Deşi aceste legături de spam pot fi scoase, acest lucru reprezintă o muncă inconvenientă.

Uneori, mai ales la adăugarea de legături web noi într-o pagină, situl wiki vă poate arăta o imagine cu un text colorat sau distorsionat şi ruga să introduceţi cuvintele arătate. Deoarece aceasta este o sarcină greu de automatizat, va permite majorităţii persoanelor reale să posteze şi va opri majoritatea atacatorilor.

Din nefericire, această metodă îi poate deranja pe utilizatorii cu vedere limitată sau care folosesc browsere bazate pe text sau sunet. În acest moment nu avem o alternativă audio disponibilă. Vă rugăm, contactaţi administratorii sitului pentru asistenţă dacă metoda vă opreşte de la a face postări legitime.

Va fi nevoie ca browserul folosit să suporte module cookie.',
);

/** Russian (Русский)
 * @author .:Ajvol:.
 */
$messages['ru'] = array(
	'captcha-edit'               => 'Вы добавили ссылку на внешний сайт; в целях защиты от автоматического спама, введите буквы изображённые на картинке:<br />
([[{{ns:special}}:Captcha/help|Что это такое?]])',
	'captcha-addurl'             => 'Вы добавили ссылку на внешний сайт; в целях защиты от автоматического спама, введите буквы изображённые на картинке:<br />
([[{{ns:special}}:Captcha/help|Что это такое?]])',
	'captcha-badlogin'           => 'В целях защиты от автоматического взлома пароля, пожалуйста, выполните следующее простое арифметическое действие и введите ответ в текстовое поле ниже ([[Special:Captcha/help|подробнее]]):',
	'captcha-createaccount'      => 'В качестве меры против автоматического спама, вы должны ввести буквы, изображённые на картинке, чтобы зарегистрироваться в системе:<br />
([[{{ns:special}}:Captcha/help|Что это такое?]])',
	'captcha-createaccount-fail' => 'Код подтверждения отсутствует или неверен.',
	'captcha-create'             => 'Вы добавили ссылку на внешний сайт; в целях защиты от автоматического спама, введите буквы изображённые на картинке:<br />
([[{{ns:special}}:Captcha/help|Что это такое?]])',
	'captchahelp-title'          => 'Справка о CAPTCHA',
	'captchahelp-cookies-needed' => 'Вам нужно включить куки в браузере, чтобы эта функция заработала.',
	'captchahelp-text'           => 'Вебсайты позволяющие добавлять и изменять своё содержимое, в том числе вики, часто становятся целью спамеров, использующих программы для автоматического добавления ссылок. Хотя такие ссылки могут быть удалены, они являются существенной помехой.

Иногда, например при добавлении на страницу новой веб-ссылки, вики может показать вам картинку с цветным или искажённым текстом и предложить ввести текст, который вы видите. Так как подобная задача трудноавтоматизируема, это даёт возможность большинству людей разместить свои изменения, в то время как большинство спамерских и вандальных программ не могут это сделать.

К сожалению, подобная защита может причинить неудобства людям с ограничениями по зрению или тем, кто использует читающие браузеры. В настоящее время у нас нет звуковой альтернативы данной проверке. Пожалуйста, обратитесь за помощью к администраторам, если подобная проверка мешает вам добросовестно работать с сайтом.

Нажмите кнопку «Назад» в ваше браузере, чтобы вернуться к редактированию.',
	'captcha-addurl-whitelist'   => ' #<!-- leave this line exactly as it is --> <pre> 
# Описание синтаксиса:
#   * Всё, начиная с символа "#" и до конца строки считается комментарием
#   * Каждая непустая строка считается фрагментом регулярного выражения соответствующего имени узла в URL
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['sk'] = array(
	'captcha-edit'                => 'Vaša úprava obsahuje nové externé odkazy. Ako pomoc pri ochrane pred automatickým spamom vyriešte prosím tento jednoduchý súčet a zadajte výsledok do poľa ([[Special:Captcha/help|viac informácií]]):',
	'captcha-addurl'              => 'Vaša úprava obsahuje nové externé odkazy. Ako pomoc pri ochrane pred automatickým spamom vyriešte prosím tento jednoduchý súčet a zadajte výsledok do poľa ([[Special:Captcha/help|viac informácií]]):',
	'captcha-badlogin'             => 'Ako ochranu proti automatizovanému lámaniu hesiel, prosím vyriešte nasledujúci súčet a zadajte ho do poľa pre odpoveď ([[Special:Captcha/help|viac informácií]]):',
	'captcha-createaccount'       => 'Kvôli ochrane proti automatizovanému spamu je potrebné napísať slová zobrazené na tomto obrázku, až potom bude vytvorený nový účet:
<br />([[Special:Captcha/help|Čo je toto?]])',
	'captcha-createaccount-fail'  => 'Nesprávny alebo chýbajúci potvrdzovací kód.',
	'captcha-create'              => 'Vaša úprava obsahuje nové externé odkazy. Ako pomoc pri ochrane pred automatickým spamom vyriešte prosím tento jednoduchý súčet a zadajte výsledok do poľa ([[Special:Captcha/help|viac informácií]]):',
	'captchahelp-title'           => 'Pomocník ku captcha',
	'captchahelp-cookies-needed'  => 'Aby toto fungovalo, budete si musieť v prehliadači zapnúť koláčiky (cookies).',
	'captchahelp-text'            => 'Webstránky prijímajúce príspevky od verejnosti ako táto wiki sú často cieľom zneužitia spammemi, ktorí používajú automatizované nástroje na to, aby svoje odkazy umiestnili na množstvo stránok. Hoci je možné tieto odkazy odstrániť, zbytočne to zaťažuje používateľov.

Niekedy, obzvlášť keď pridávate webové odkazy k článkom, wiki Vám môže zobraziť obrázok so zafarbeným alebo pokriveným textom a požiadať Vás o prepísanie zobrazených slov. Keďže takúto úlohu je ťažké zautomatizovať a umožní skutočným ľuďom poslať svoje príspevky, zastaví to väčšinu spammerov a iných robotických útočníkov.

Naneštastie, môže to byť prekážkou pre používateľov so zrakovým postihnutím alebo tých, ktorí používajú textové alebo hovoriace prehliadače. Momentálne nemáme dostupnú audio zvukovú alternatívu. Kontaktujte prosím administratorov stránok, ak Vám to neočakávane komplikuje umiestňovanie oprávnených príspevkov.

Stlačením tlačidla "späť" vo Vašom prehliadači sa vrátite do editora stránky.',
	'captcha-addurl-whitelist'    => ' #<!-- leave this line exactly as it is --> <pre> 
# Syntax je nasledovná: 
#   * Všetko od znaku "#" do konca riadka je komentár
#   * Každý neprázdny riadok je fragment regulárneho výrazu, ktorého zhody budú iba stroje v rámci URL
 #</pre> <!-- leave this line exactly as it is -->',
);
$messages['sl'] = array(
	'captcha-edit'               => "Vaše urejanje vključuje nove URL-povezave; zaradi zaščite pred avtomatizirano navlako boste morali vpisati besede, ki se pojavijo v okencu: <br />([[{{ns:Special}}:Captcha/help|Kaj je to?]])",
	'captcha-addurl'               => "Vaše urejanje vključuje nove URL-povezave; zaradi zaščite pred avtomatizirano navlako boste morali vpisati besede, ki se pojavijo v okencu: <br />([[{{ns:Special}}:Captcha/help|Kaj je to?]])",
	'captcha-create'               => "Vaše urejanje vključuje nove URL-povezave; zaradi zaščite pred avtomatizirano navlako boste morali vpisati besede, ki se pojavijo v okencu: <br />([[{{ns:Special}}:Captcha/help|Kaj je to?]])",
	'captchahelp-title'          => 'Pomoč za captcha',
	'captchahelp-text'           => "Spletne strani, ki omogočajo objavljanje širši javnosti, kot na primer ta wiki, pogosto zlorabljajo spamerji, ki za objavo svojih povezav na mnogih straneh uporabljajo avtomatizirana orodja. Čeprav se te neželene povezave da odstraniti, so precejšnja nadloga.

Včasih, zlasti pri dodajanju novih spletnih povezav na stran, vam bo morda wiki prikazal sliko obarvanega ali popačenega besedila in zahteval vpis prikazanih besed. Ker je to opravilo težko avtomatizirati, bo s tem večini ljudi objavljanje dovoljeno, spamerji in druge robotski napadalci pa bodo ustavljeni.

Žal lahko to povzroči nevšečnosti uporabnikom s slabim vidom in tistim, ki uporabljajo besedilne ali govorne brskalnike. Glasovna možnost trenutno še ni na razpolago. Če vam to nepričakovano preprečuje legitimno objavo, se, prosimo, obrnite na administratorje spletišča.

Za vrnitev v urejevalnik izberite gumb 'nazaj' vašega brskalnika.",
	'captcha-createaccount'      => "Za registracijo je zaradi zaščite pred neželenimi reklamnimi sporočili treba vpisati prikazane besede: <br />([[{{ns:special}}:Captcha|Kaj je to?]])",
	'captcha-createaccount-fail' => "Nepravilna ali manjkajoča potrditvena koda.",
);
$messages['sq'] = array(
	'captcha-edit'               => 'Redaktimi juaj ka lidhje URL të reja dhe si mbrojtje kundër abuzimeve automatike duhet të shtypni çfarë shfaqet tek figura e mëposhtme:<br /> ([[Special:Captcha|Çfarë është kjo?]])',
	'captcha-addurl'             => 'Redaktimi juaj ka lidhje URL të reja dhe si mbrojtje kundër abuzimeve automatike duhet të shtypni çfarë shfaqet tek figura e mëposhtme:<br /> ([[Special:Captcha|Çfarë është kjo?]])',
	'captcha-create'             => 'Redaktimi juaj ka lidhje URL të reja dhe si mbrojtje kundër abuzimeve automatike duhet të shtypni çfarë shfaqet tek figura e mëposhtme:<br /> ([[Special:Captcha|Çfarë është kjo?]])',
	'captchahelp-title'          => 'Ndihmë rreth sistemit "Captcha"',
	'captchahelp-text'           => 'Faqet e rrjetit që pranojnë shkrime nga publiku, siç është edhe kjo wiki, shpesh abuzohen nga njerëz që duan të përfitojnë duke reklamuar ose promovuar lidhjet e tyre. Këto lloj abuzimesh mund të hiqen kollaj por janë një bezdi dhe shpenzim kohe i papranueshëm.

Ndonjëherë, sidomos kur po hapni një llogari të re apo kur po shtoni lidhje të reja nëpërmjet redaktimit tuaj, sistemi mund t\'ju shfaqi një figurë që përmban fjalë me gërma ose numra të shtrembruara ose me ngjyra të ndryshme të cilat ju duhet të shtypni para se të mund të kryeni veprimin në fjalë. Kjo bëhet pasi është shumë e vështirë për një robot ose mjet automatik të kryejë të njëjtën punë. Kështu mund të dallohet nëse jeni me të vërtetë një njeri apo një robot. Ky lloj sistemi s\'mund të ndalojë tërë abuzimet por ndalon një pjesë të mirë të tyre, sidomos ato që janë automatike dhe të shumta në numër.

Fatkeqësisht ky lloj sistemi mund të bezdisi përdoruesit me pamje të kufizuar ose ata që përdorin mjete teksti ose shfletues leximi me zë. Tani për tani nuk kemi mundësi për të ofruar një sistem me zë në vend të figurave. Ju lutem lidhuni me administruesit nëse ky sistem po ju ndalon të jepni kontribute të vlefshme.

Shtypni butonin "prapa" ("back") të shfletuesit tuaj për tu kthyer tek faqja e mëparshme.',
	'captcha-createaccount'      => 'Për mbrojtje kundër regjistrimeve automatike duhet të zgjidhni ekuacionin e mëposhtëm para se të hapni llogarinë:<br />([[Special:Captcha|Çfarë është kjo?]])',
	'captcha-createaccount-fail' => 'Mesazhi që duhej shtypur mungon ose nuk është shtypur siç duhet.',
);

/** Seeltersk (Seeltersk)
 * @author Pyt
 */
$messages['stq'] = array(
	'captcha-edit'               => 'Tou Beoarbaidenge fon ju Siede löös ätterfoulgjende Reekenapgoawe un dräch dät Resultoat in dät Fäild hierunner ien [[{{ns:special}}:Captcha/help|(Froagen of Probleme?)]].',
	'captcha-addurl'             => 'Dien Beoarbaidenge änthaalt näie externe Ferbiendengen. Toun Schuts foar automatisierde Spammenge löös ju ätterfoulgjende Reekenapgoawe un dräch dät Resultoat in dät Fäild hierunner ien. Klik dan fonnäien ap „Siede spiekerje“ [[{{ns:special}}:Captcha/help|(Froagen of Probleme?)]].',
	'captcha-badlogin'           => 'Toun Schuts foar ne Kompromittierenge fon dien Benutserkonto löös ju ätterfoulgjende Reekenapgoawe un dräch dät Resultoat in dät Fäild hierunner ien [[{{ns:special}}:Captcha/help|(Froagen of Probleme?)]]:',
	'captcha-createaccount'      => 'Toun Schuts foar automatisierden Anloage fon Benutserkonten löös ju ätterfoulgjende Reekenapgoawe un dräch dät Resultoat in dät Fäild hierunner ien [[{{ns:special}}:Captcha/help|(Froagen of Probleme?)]].',
	'captcha-createaccount-fail' => 'Falske of failjende Bestäätigengscode.',
	'captcha-create'             => 'Uum disse Siede tou moakjen, löös juu ätterfoulgjende Reekenapgoawe ap un dräch dät Resultoat in dät Fäild hier unner ien [[{{ns:special}}:Captcha/help|(Froagen of Probleme?)]].',
	'captchahelp-title'          => 'Captcha-Hälpe',
	'captchahelp-cookies-needed' => "'''Wichtige Waiwiesenge:''' Der mouten Cookies in dän Browser ferlööwed weese.",
	'captchahelp-text'           => 'Internetboode, do der foar Biedraage fon praktisk älkuneen eepen sunt - so as dät {{SITENAME}}-Wiki — wäide oafte fon Spammere misbruukt, do hiere Ferbiendengen automatisk ap fuul Websieden platzierje. Disse Spam-Ferbiendengen konnen wier wächhoald wäide, man jo sunt n groot Ferträit.

In fuul Falle, besunners bie dät Bietouföigjen fon näie Webferbiendengen tou ne Siede, kon dät foarkuume, dät dit Wiki ne Bielde mäd n faawigen un fertroalden Text anwiest un deertou apfoardert, do anwiesde Woude ientoutippen. Deer sun Apgoawe man stuur automatisk ouhonneld wäide kon, wäide deertruch do maaste Spammere, do der mäd automatiske Reewen oarbaidje, stopped, wierjuun moanskelke Benutsere hieren Biedraach seende konnen.

Spietelk genouch kon dät tou Meelasje foar Persoone fiere, do der minner goud sjo konnen of text- of sproakbasierde Browsere ferweende. Ne Löösenge is ju reguläre Anmäldenge as Benutser. Die „Tourääch“-Knoop fon dän Browser fiert tourääch in dät Beoarbaidengsfinster.',
	'captcha-addurl-whitelist'   => ' #<!-- leave this line exactly as it is --> <pre> 
#  Syntax:
#   * Alles fon n #-Teeken bit tou dän Eend fon ju Riege is n Kommentoar
#   * Älke nit-loose Riege is n Regex-Fragment, dät juunuur den Hostnoome fon ne URL wröiged wäd
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['su'] = array(
	'captcha-edit'                => 'Pikeun ngédit artikel ieu, mangga eusian itungan di handap ieu ([[Special:Captcha/help|émbaran lengkep]]):',
	'captcha-addurl'              => 'Éditan anjeun ngawengku tumbu kaluar anyar. Pikeun nyegah spam, mangga eusian itungan di handap ieu [[Special:Captcha/help|émbaran lengkep]]):',
	'captcha-createaccount'       => 'Pikeun nyegah dijieunna rekening sacara otomatis, mangga eusian itungan di handap ieu ([[Special:Captcha/help|émbaran lengkep]]):',
	'captcha-createaccount-fail'  => 'Sandi konfirmasina salah atawa can dieusian.',
	'captcha-create'              => 'Pikeun nyieun kacana, mangga eusian itungan di handap ieu ([[Special:Captcha/help|émbaran lengkep]]):',
	'captchahelp-title'           => 'Pitulung Captcha',
	'captchahelp-text'            => 'Ramatloka nu nampa tulisan ti masarakat umum kawas ieu wiki mindeng diganggu ku spammer nu maké pakakas otomatis pikeun midangkeun tumbu-tumbuna ka loba loka. Najan tumbu spam ieu bisa dihapus, ari loba-loba teuing mah matak nyapékeun.

Sometimes, especially when adding new web links to a page, the wiki may show you an image of colored or distorted text and ask you to type the words shown. Since this is a task that\'s hard to automate, it will allow most real humans to make their posts while stopping most spammers and other robotic attackers.

Unfortunately this may inconvenience users with limited vision or using text-based or speech-based browsers. At the moment we do not have an audio alternative available. Please contact the site administrators for assistance if this is unexpectedly preventing you from making legitimate posts.

You will need to have cookies enabled in your browser for this to work.

Hit the \'back\' button in your browser to return to the page editor.',
);
$messages['sv'] = array(
	'captcha-edit' => 'För att redigera den här sidan måste du först skriva svaret på följande
räkneuppgift i rutan ([[Special:Captcha/help|mer information]]):',
	'captcha-addurl' => 'Din ändring lägger till nya externa länkar i texten. För att skydda wikin mot
automatisk spam måste du skriva svaret på följande räkneuppgift i rutan ([[Special:Captcha/help|mer information]]):',
	'captcha-badlogin' => 'För att skydda mot wikin mot automatiserad lösenordsknäckning måste du skriva
svaret på följande räkneuppgift i rutan ([[Special:Captcha/help|mer information]]):',
	'captcha-createaccount' => 'För att skydda wikin mot automatiskt skapade användarkonton måste du
skriva svaret på följande räkneuppgift i rutan ([[Special:Captcha/help|mer information]]):',
	'captcha-createaccount-fail' => "Bekräftelsekoden är felaktig eller saknas.",
	'captcha-create' => 'För att skapa den här sidan måste du skriva svaret på följande räkneuppgift
i rutan ([[Special:Captcha/help|mer information]]):',
	'captchahelp-title'          => 'Captchahjälp',
	'captchahelp-cookies-needed' => "Du måste ha cookies aktiverade i din webbläsare för att det här ska fungera.",
	'captchahelp-text'           => "Webbplatser som tillåter inlägg från allmänheten, som den här wikin gör, kan
missbrukas av spammare. De använder ofta automatiserade verktyg för att lägga till länkar på många webbsajter. Även om
dessa spamlänkar kan tas bort så är de till stort besvär.

Ibland, speciellt då du lägger till nya externa länkar på en sida, visar wikin en bild på en färgad eller förvriden text
och ber dig skriva texten som visas. Den uppgiften är svår att automatisera, men oftast lätt för en människa att utföra.
På så sätt stoppas spammare och andra robotattacker, medan riktiga mäniskor kan göra sina redigeringar.

Tyvärr kan det här orsaka problem för användare med nedsatt syn eller som använder text- eller talbaserade webbläsare.
För tillfället finns inga ljudbaserade alternativ tillgängliga. Om det här hindrar dig från att göra legitima ändringar
kan du be någon av administratörerna om hjälp.

Tryck på bakåtknappen i din webbläsare för att gå tillbaks till sidredigeringsläget.",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
# Syntaxen är följande: 
#   * Allting från ett "#" till slutet av en rad är en kommentar
#   * Varje icketom rad är ett reguljärt uttryck som matchar domänen i en URL
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['uk'] = array(
	'captchahelp-text'           => "Вікіпедія застосовує техніку розрізнення людей від комп'ютерів, яка використовує розпізнавання образів, для захисту від  комп'ютерних  шкідливих програм, які автоматично реєструються  (найчастіше спамлять у статтях).

Для реєстрації у Вікіпедії та іноді й при редагуванні статей користувачеві потрібно ввести вказану контрольну послідовність символів, і яку вони, будучи людьми, а не комп'ютерними програмами, можуть легко розпізнати.

You will need to have cookies enabled in your browser for this to work.

Hit the 'back' button in your browser to return to the page editor.",
	'captcha-createaccount-fail' => 'Невірний або відсутній код підтвердження.',
);

/** Volapük (Volapük)
 * @author Malafaya
 */
$messages['vo'] = array(
	'captchahelp-title' => 'Yuf ela Captcha',
);

$messages['wa'] = array(
	'captcha-edit'  => 'Dins vos candjmints i gn a des novelès hårdêyes (URL); po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captcha-addurl'  => 'Dins vos candjmints i gn a des novelès hårdêyes (URL); po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captcha-create'  => 'Dins vos candjmints i gn a des novelès hårdêyes (URL); po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captchahelp-title' => 'Aidance passete d\' acertinaedje',
	'captchahelp-text' => 'Les waibes k\' acceptèt des messaedjes do publik, come ci wiki chal, sont sovint eployîs pa des må-fjhants spameus, po pleur mete, avou des usteyes otomatikes, des loyéns di rclame viè les sites da zels.
Bén seur, on pout todi les disfacer al mwin, mins c\' est on soyant ovraedje.

Adon, pa côps, copurade cwand vos radjoutez des hårdêyes a ene pådje, ou å moumint d\' ahiver on novea conte sol wiki, on eployrè ene passete d\' acertinaedje, dj\' ô bén k\' on vos mostere ene imådje avou on tecse kitoirdou eyet vs dimander di taper les mots so l\' imådje. Come li ricnoxhance di ç\' tecse la est målåjheye a fé otomaticmint pa on robot, çoula permete di leyî les vraiyès djins fé leus candjmints tot arestant l\' plupårt des spameus et des sfwaitès atakes pa robot.

Målureuzmint çoula apoite eto des målåjhminces po les cis k\' ont des problinmes po vey, ou k\' eployèt des betchteus e môde tecse ou båzés sol vwès. Pol moumint, nos n\' avans nén ene alternative odio. S\' i vs plait contactez les manaedjeus do site po d\' l\' aidance si çoula vos espaitche di fé vos candjmints ledjitimes.

Clitchîz sol boton «En erî» di vosse betchteu waibe po rivni al pådje di dvant.',
	'captcha-createaccount' => 'Po s\' mete a houte des robots di spam, nos vs dimandans d\' acertiner ki vos estoz bén ene djin po-z ahiver vosse conte, po çoula, tapez les mots k\' aparexhèt dins l\' imådje chal pa dzo:<br />([[{{ns:special}}:Captcha/help|Pocwè fjhans ns çoula?]])',
	'captcha-createaccount-fail' => 'Li côde d\' acertinaedje est incorek ou mancant.',
);

$messages['yue'] = array(
	'captcha-edit'               => "你編輯的內容中含有新的URL連結；為咗避免受到自動垃圾程式的侵擾，你需要輸入顯示喺下面圖片度嘅文字：<br />
([[Special:Captcha/help|呢個係乜嘢嚟？]])",
	'captcha-addurl'             => "你編輯的內容中含有新的URL連結；為咗避免受到自動垃圾程式的侵擾，你需要輸入顯示喺下面圖片度嘅文字：<br />
	([[Special:Captcha/help|呢個係乜嘢嚟？]])",
	'captcha-badlogin'            => '為咗防止程式自動破解密碼，請答出一個簡單嘅加法，跟住響個盒度打入 ([[Special:Captcha/help|更多資料]]):',
	'captcha-createaccount'      => "為咗防止程式自動註冊，你需要輸入以下圖片中顯示的文字先至能夠註冊得到個戶口：<br />
([[Special:Captcha/help|呢個係乜嘢嚟？]])",
	'captcha-createaccount-fail' => "驗證碼錯誤或者唔見咗。",
	'captcha-create'             => "你編輯的內容中含有新的URL連結；為咗避免受到自動垃圾程式的侵擾，你需要輸入顯示喺下面圖片度嘅文字：<br />
([[Special:Captcha/help|呢個係乜嘢嚟？]])",
'captchahelp-title'          => 'Captcha 幫助',
	'captchahelp-cookies-needed' => "你需要開咗響瀏覽器度嘅cookies先至可幾用呢樣嘢。",
	'captchahelp-text'           => "就好似呢個wiki咁，對公眾開放編輯嘅網站係會經常受到垃圾連結騷擾。嗰啲人利用自動化垃圾程序將佢哋嘅連結張貼到好多網站。雖然呢啲連結可以被清除，但係呢啲嘢確實令人十分之討厭。

有時，特別係當響一頁添加新嘅網頁連結嗰陣，呢個網站會畀你睇一幅有顏色的或者有變形文字嘅圖像，跟住要你輸入所顯示嘅文字。因為咁係難以自動完成嘅一項任務，它將允許人保存佢哋嘅編輯，同時亦阻止大多數發送垃圾郵件者同其它機械人嘅攻擊。

令人遺憾嘅係，咁會令到視力唔好嘅人，或者利用基於文本或者基於聲音嘅瀏覽器用戶感到不便。而目前我哋仲未能夠提供音頻嘅選擇。如果咁樣咁啱阻止到你進行正常嘅編輯，請同管理員聯繫以獲得幫助。

撳一下響瀏覽器度嘅「後退」掣返去你之前所編輯緊嘅頁面。",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
# 語法好似下面噉: 
#   * 所有由 "#" 字元之後嘅嘢到行尾係註解
#   * 所有非空白行係一個regex部份，只係會同裏面嘅URL主機相符
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['zh-hans'] = array(
	'captcha-edit'               => "你编辑的内容中含有一个新的URL链接；为了免受自动垃圾程序的侵扰，你需要输入显示在下面图片中的文字：<br />
([[Special:Captcha/help|这是什么？]])",
	'captcha-addurl'               => "你编辑的内容中含有一个新的URL链接；为了免受自动垃圾程序的侵扰，你需要输入显示在下面图片中的文字：<br />
	([[Special:Captcha/help|这是什么？]])",
	'captcha-badlogin'              => '为了防止程序自动破解密码，请答出一个简单的加法，然后在框内输入 ([[Special:Captcha/help|更多资料]]):',
	'captcha-createaccount'      => "为了防止程序自动注册，你需要输入以下图片中显示的文字才能注册帐户：<br />
([[Special:Captcha/help|这是什么？]])",
	'captcha-createaccount-fail' => "验证码错误或丢失。",
	'captcha-create'               => "你编辑的内容中含有一个新的URL链接；为了免受自动垃圾程序的侵扰，你需要输入显示在下面图片中的文字：<br />
	([[Special:Captcha/help|这是什么？]])",
	'captchahelp-cookies-needed' => "您需要开启浏览器上的cookies方可使用这个工具。",
	'captchahelp-title'          => 'Captcha 帮助',
	'captchahelp-text'           => "象本站一样，对公众开放编辑的站点经常被垃圾链接骚扰。那些人使用自动化垃圾程序将他们的链接张贴到很多站点。虽然这些链接可以被清除，但是这些东西确实令人十分讨厌。

有时，特别是当给一个页面添加新的网页链接时，本站会让你看一幅有颜色的或者有变形文字的图像，并且要你输入所显示的文字。因为这是难以自动完成的一项任务，它将允许人保存他们的编辑，同时阻止大多数发送垃圾邮件者和其他机器人的攻击。

令人遗憾是，这会使得视力不好的人，或者使用基于文本或者基于声音的浏览器的用户感到不便。而目前我们还没有提供的音频的选择。如果这正好阻止你进行正常的编辑，请和管理员联系获得帮助。

单击你浏览器中的“后退”按钮返回你所编辑的页面。",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
# 语法像下面这样: 
#   * 所有由 "#" 字元之后?字元至行尾是注解
#   * 所有非空白行是一个regex部份，只是跟在里面的URL主机相符
 #</pre> <!-- leave this line exactly as it is -->',
);

$messages['zh-hant'] = array(
	'captcha-edit'               => "你編輯的內容中含有一個新的URL連結；為了免受自動垃圾程式的侵擾，你需要輸入顯示在下面圖片中的文字：<br />
([[Special:Captcha/help|這是什麼？]])",
	'captcha-addurl'               => "你編輯的內容中含有一個新的URL連結；為了免受自動垃圾程式的侵擾，你需要輸入顯示在下面圖片中的文字：<br />
	([[Special:Captcha/help|這是什麼？]])",
	'captcha-badlogin'              => '為了防止程式自動破解密碼，請答出一個簡單的加法，然後在框內輸入 ([[Special:Captcha/help|更多資料]]):',
	'captcha-createaccount'      => "為了防止程式自動註冊，你需要輸入以下圖片中顯示的文字才能註冊帳戶：<br />
([[Special:Captcha/help|這是什麼？]])",
	'captcha-createaccount-fail' => "驗證碼錯誤或丟失。",
	'captcha-create'               => "你編輯的內容中含有一個新的URL連結；為了免受自動垃圾程式的侵擾，你需要輸入顯示在下面圖片中的文字：<br />
	([[Special:Captcha/help|這是什麼？]])",
	'captchahelp-cookies-needed' => "您需要開啟瀏覽器上的cookies方可使用這個工具。",
	'captchahelp-title'          => 'Captcha 說明',
	'captchahelp-text'           => "像本站一樣，對公眾開放編輯的網站經常被垃圾連結騷擾。那些人使用自動化垃圾程序將他們的連結張貼到很多網站。雖然這些連結可以被清除，但是這些東西確實令人十分討厭。

有時，特別是當給一個頁面添加新的網頁連結時，本站會讓你看一幅有顏色的或者有變形文字的圖像，並且要你輸入所顯示的文字。因為這是難以自動完成的一項任務，它將允許人保存他們的編輯，同時阻止大多數發送垃圾郵件者和其他機器人的攻擊。

令人遺憾是，這會使得視力不好的人，或者使用基於文本或者基於聲音的瀏覽器的用戶感到不便。而目前我們還沒有提供的音頻的選擇。如果這正好阻止你進行正常的編輯，請和管理員聯繫獲得幫助。

點擊瀏覽器中的「後退」按鈕返回你所編輯的頁面。",
	'captcha-addurl-whitelist' => '
 #<!-- leave this line exactly as it is --> <pre> 
# 語法像下面這樣: 
#   * 所有由 "#" 字元之後嘅字元至行尾是註解
#   * 所有非空白行是一個regex部份，只是跟在裏面的URL主機相符
 #</pre> <!-- leave this line exactly as it is -->',
);

# Kazakh fallbacks
$messages['kk-kz'] = $messages['kk-cyrl'];
$messages['kk-tr'] = $messages['kk-latn'];
$messages['kk-cn'] = $messages['kk-arab'];
$messages['kk'] = $messages['kk-cyrl'];

# Chinese fallbacks
$messages['zh'] = $messages['zh-hans'];
$messages['zh-cn'] = $messages['zh-hans'];
$messages['zh-hk'] = $messages['zh-hant'];
$messages['zh-min-nan'] = $messages['nan'];
$messages['zh-sg'] = $messages['zh-hans'];
$messages['zh-tw'] = $messages['zh-hant'];
$messages['zh-yue'] = $messages['yue'];
