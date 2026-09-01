=== VIEW! Privacy ===
Contributors: view
Tags: cookies, gdpr, avg, privacy, consent, polylang
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 4.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lichtgewicht, privacy-first cookieconsent-plugin. Ontwikkeld met als uitgangspunt de AVG/GDPR- en ePrivacy-regelgeving, zonder externe cloudservices of abonnementen.

== Description ==

VIEW! Privacy is een cookiebanner- en consentbeheerplugin gebouwd voor webdesigners, WordPress-bureaus, zzp'ers en MKB die volledige controle willen zonder externe afhankelijkheden.

VIEW! Privacy is ontwikkeld met als uitgangspunt de AVG/GDPR- en ePrivacy-regelgeving. Of een website die de plugin gebruikt daadwerkelijk volledig compliant is, hangt mede af van de configuratie van de site en van de externe diensten (zoals Google Analytics, advertentiepixels of embeds) die daarop worden ingezet. VIEW! Privacy is geen juridisch advies en biedt geen garantie op volledige naleving.

**Kernpunten**

* Geen tracking door de plugin zelf
* Geen externe API's of cloudservices
* Geen abonnement, geen vendor lock-in
* Vanilla JavaScript, geen jQuery
* Automatische blokkering van scripts, iframes en embeds totdat toestemming is gegeven
* Ingebouwde ondersteuning voor Google Analytics 4, Google Tag Manager, Google Maps, YouTube, Meta Pixel, Hotjar en Microsoft Clarity — uitbreidbaar met eigen diensten
* Volledig instelbare banner: vaste positie linksonder, kleuren (inclusief knopkleuren), lettergrootte, border-radius, schaduw en animatie
* Voorkeurencentrum met categorieën: Noodzakelijk, Voorkeuren, Statistieken, Marketing
* Automatische herconsent bij wijziging van categorieën of banner (consentversiebeheer)
* Optionele logging van toestemming (datum/tijd, consentversie)
* Instellingen exporteren/importeren als JSON
* WCAG 2.2 AA toegankelijkheid
* Multisite-compatibel

== Installation ==

1. Upload de map `view-privacy` naar `/wp-content/plugins/`, of upload de zip via **Plugins → Nieuwe plugin toevoegen → Plugin uploaden**.
2. Activeer de plugin via het menu **Plugins**.
3. Ga naar **VIEW! Privacy → Instellingen** om de banner en categorieën te configureren.
4. Ga naar **VIEW! Privacy → Diensten** om te bepalen welke diensten automatisch geblokkeerd worden.

== Frequently Asked Questions ==

= Verstuurt deze plugin gegevens naar externe servers? =

Nee. Alle functionaliteit draait volledig op uw eigen WordPress-installatie. Er wordt geen data naar externe cloudservices gestuurd.

= Werkt de plugin zonder jQuery? =

Ja, de front-end is volledig geschreven in vanilla JavaScript.

= Hoe voeg ik een eigen dienst toe die niet in de standaardlijst staat? =

Ga naar **VIEW! Privacy → Diensten** en voeg een eigen dienst toe met herkenningspatronen, of gebruik het `view_privacy_services`-filter voor een programmatische aanpak.

= Wat gebeurt er als ik de categorieën wijzig? =

De consentversie wordt automatisch verhoogd en bezoekers krijgen opnieuw de banner te zien om hun toestemming te herbevestigen.

= Mijn site plaatst alleen functionele cookies, moet ik dan nog om toestemming vragen? =

Nee, voor strikt noodzakelijke/functionele cookies is onder de ePrivacy-richtlijn geen toestemming vereist. Schakel bij **Instellingen → Banner** de optie "Alleen functionele cookies" in: bezoekers krijgen dan een informatieve melding met één knop te zien, zonder toestemmingsvraag. Controleer voordat u dit inschakelt bij **Diensten** of er echt geen tracking-, statistiek- of marketingscripts actief zijn.

= In welke talen is VIEW! Privacy beschikbaar? =

Standaard Nederlands (brontaal). Sinds v3.1.3 is er ook een complete Duitse vertaling (de_DE en de_DE_formal) bijgevoegd, die automatisch wordt gebruikt zodra de WordPress-sitetaal op Duits staat (Instellingen → Algemeen → Sitetaal) — hiervoor is geen Polylang of andere meertaligheidsplugin nodig. Wilt u de vertaling zelf aanpassen of aanvullen, of andere talen toevoegen? Plaats uw eigen `view-privacy-{taalcode}.po`/`.mo`-bestanden (bijvoorbeeld bewerkt met Poedit of Loco Translate) bij voorkeur in `wp-content/languages/plugins/` in plaats van in de map van de plugin zelf: WordPress controleert die locatie automatisch als eerste, en bestanden die daar staan overleven toekomstige plugin-updates (bestanden binnen de pluginmap zelf worden bij een update overschreven).

= Waarom heeft VIEW! Privacy een eigen instelling voor de privacybeleidpagina, in plaats van WordPress' eigen Instellingen → Privacy? =

Sinds v3.1.2 gebruikt VIEW! Privacy een eigen, verplichte instelling (Instellingen → Privacybeleid) in plaats van WordPress' 'wp_page_for_privacy_policy'. Die WP-optie bleek in combinatie met Polylang niet altijd betrouwbaar: op niet-standaardtaalpagina's kon deze leeg teruggegeven worden, waardoor de cookiebanner op die taalversies ten onrechte niet verscheen. Bij het bijwerken naar v3.1.2 wordt de eigen instelling automatisch gevuld met de destijds gekoppelde WP-privacypagina; controleer deze wel eenmalig.

= Werkt VIEW! Privacy samen met Polylang? =

Ja. VIEW! Privacy heeft een eigen, verplichte instelling voor de privacybeleidpagina (Instellingen → Privacybeleid), los van WordPress' eigen Instellingen → Privacy. Is Polylang actief, dan koppelt de banner automatisch aan de vertaling van díe bronpagina voor de taal die de bezoeker bekijkt. Ontbreekt een vertaling voor een bepaalde taal, dan blijft de banner gewoon zichtbaar (met een verwijzing naar de bronpagina) en verschijnt op het dashboard een waarschuwing welke taal nog een vertaling mist. Daarnaast zijn alle front-end teksten van de plugin (banner, knoppen, voorkeurencentrum, categorieën, placeholders, privacyknop) vertaalbaar via **Talen → Vertaling van strings** (groep "VIEW! Privacy") — geen aparte instellingen nodig, geen machinevertaling. Zonder Polylang gebruikt de plugin de bronpagina rechtstreeks.

= Waarom staat een dienst op "Nog niet gezien" terwijl ik hem heb geactiveerd? =

De statuskaarten op het dashboard (Analytics, YouTube, Google Maps) tonen niet alleen of een dienst is aangevinkt bij Instellingen, maar of deze daadwerkelijk op een bezochte pagina is aangetroffen en geblokkeerd. Staat een dienst pas sinds kort aan, of alleen op een pagina die nog niet bezocht is sinds het inschakelen? Dan is deze mogelijk nog niet "gezien". Bezoek de betreffende pagina eenmaal in de front-end om de status bij te werken (de plugin registreert dit met een rate-limit van maximaal één keer per 5 minuten).

== Changelog ==

= 4.0.1 =
* Betrouwbare native WordPress/GitHub-updateintegratie met expliciete release-assets, response/no_update-metadata en cacheverversing.
* Releasepakket wordt gevalideerd op de vaste mapstructuur `view-privacy/view-privacy.php`.
* Herstelt de 3.x-modus voor uitsluitend functionele cookies en versterkt multisite- en consentlogging-afhandeling.

= 4.0.0 =
* Banner staat links onderaan; deze positie is in 4.0.0 vastgezet.
* Knoppen hebben instelbare tekst-, achtergrond- en randkleuren voor primaire en secundaire knoppen.
* Branding op de pluginpagina: VIEW! by Zaal Design.
* De bestaande modus "Alleen functionele cookies" blijft beschikbaar voor installaties die deze in 3.x al gebruikten.


= 3.1.9 =
* Mobiele verbetering: de cookiebanner verschijnt na 5 seconden op schermen tot en met 767px breed, zodat bezoekers eerst de taalkeuze in het mobiele menu kunnen maken. Desktop blijft ongewijzigd.
* Branding: plugin-overzicht toont als auteur "VIEW! by Zaal Design"; de externe "Bezoek plugin site"-link is verwijderd.


= 3.1.8 =
* Added native WordPress update checks for public GitHub Releases.
* Updates use the release ZIP asset without changing existing plugin settings or consent data.
* GitHub release checks run through the WordPress update system; visitor consent data is not sent to GitHub.

= 3.1.6 =
* Bugfix: bij het openen van het voorkeurencentrum (vanuit de banner, de herstelknop, een placeholder van geblokkeerde content, of via de externe trigger/API) werd de oorspronkelijke banner niet gesloten en bleef dus naast het voorkeurencentrum zichtbaar staan.
* Bijbehorende correctie: sluit de bezoeker het voorkeurencentrum vervolgens zonder een keuze te maken (klik naast het venster), dan verschijnt de banner automatisch weer — anders zou de bezoeker met geen enkele zichtbare cookiemelding achterblijven. Bij het daadwerkelijk opslaan van een keuze (Alles accepteren/weigeren/Voorkeuren opslaan) verandert er niets: dat pad werkte al correct.
* Bugfix (Polylang): de privacybeleid-URL viel op sommige sites/configuraties altijd terug op de brontaalpagina, ook als er een geldige Polylang-vertaling bestond — bevestigd via gericht onderzoek op heme.nl (de vertaalkoppeling zelf bleek correct; `pll_current_language()` leverde op het moment van aanroepen echter niet altijd de daadwerkelijk bekeken taal op). De taalbepaling gebruikt nu een tweede, onafhankelijke controle (de taal van de daadwerkelijk opgevraagde pagina zelf) wanneer de eerste route geen resultaat geeft.

= 3.1.5 =
* Bugfix: het voorkeurencentrum kon in bepaalde gevallen na een klik op "Privacy-instellingen" niet meer worden geopend, zonder enige foutmelding, waarbij de banner zichtbaar bleef staan. De opbouw van het voorkeurencentrum is nu beschermd tegen een onverwacht vastlopen: bij een fout wordt de status netjes gereset (in plaats van permanent "vast" te blijven staan) en verschijnt een duidelijke foutmelding in de browserconsole, zodat een volgende klik gewoon opnieuw wordt geprobeerd.
* Bugfix: de knop "Privacybeleid" stond in hoofdletters; staat nu in normale schrijfwijze, met exact dezelfde typografische behandeling (incl. hover) als de overige secundaire knoppen.

= 3.1.4 =
* Bugfix: door de beheerder instelbare bannerteksten (tagline, titel, bannertekst, eenvoudige-modus-tekst/knop) en categorielabels/-omschrijvingen (Noodzakelijk, Externe media, Statistieken, Marketing) zijn geen `__()`-aanroepen maar losse databasewaarden, en werden daardoor niet automatisch vertaald zonder Polylang — ze bleven na installatie permanent in de taal van eerste opslag staan. Onaangepaste standaardteksten worden nu, ook zonder Polylang, alsnog via de normale WordPress-vertaling omgezet; eigen aangepaste teksten van de beheerder blijven ongewijzigd staan (daar bestaat geen automatische vertaling voor).
* Bugfix: de knoppen, placeholders, bewaartermijntekst en overige front-end-strings uit het `i18n`-object waren al correct vertaalbaar; opnieuw geverifieerd tegen het gecompileerde .mo-bestand.
* Bugfix (interne tooling): de .pot-generator herkende `_n()`-meervoudsvormen niet, waardoor "%d dag geleden / %d dagen geleden" in het dashboard ontbrak in alle vertaalbestanden. Meervoudsvormen worden nu correct als `msgid_plural` geëxtraheerd; de Duitse vertaling ("vor %d Tag" / "vor %d Tagen") is toegevoegd.
* Compleet nieuw `de_DE`- en `de_DE_formal` .po/.mo-paar geleverd met bovenstaande correcties.

= 3.1.3 =
* Nieuw: complete Duitse vertaling (de_DE en de_DE_formal) toegevoegd — banner, alle knoppen, voorkeurencentrum, placeholders, bewaartermijn, copyright en alle overige front-end- én beheerteksten. Geleverd als standaard .po/.mo-bestanden via de normale WordPress i18n/l10n-structuur (geen aparte taalinstelling, geen afhankelijkheid van Polylang). WordPress schakelt hier automatisch naar over zodra de site-taal op Duits staat.
* Deze release bevat uitsluitend vertaalbestanden; er zijn geen functionele wijzigingen.

= 3.1.2 =
* Bugfix (root cause, bevestigd via debugtest): op niet-standaardtaalpagina's kan WordPress' eigen optie 'wp_page_for_privacy_policy' in combinatie met Polylang 0 (leeg) teruggeven, waardoor de cookiebanner op die taalversies niet initialiseerde — ook al was er wél een geldige, gekoppelde privacybeleidpagina.
* Wijziging: VIEW! Privacy heeft nu een eigen, verplichte instelling "Privacybeleidpagina" (Instellingen → Privacybeleid), volledig los van WordPress' 'wp_page_for_privacy_policy'. Bestaande installaties nemen bij deze update automatisch hun destijds gekoppelde WP-privacypagina over als startwaarde.
* Met Polylang actief: VIEW! Privacy zoekt vanaf de eigen bronpagina de vertaling voor de actief bekeken taal; ontbreekt die vertaling, dan blijft de banner zichtbaar via de bronpagina en verschijnt een waarschuwing op het dashboard (ongewijzigd t.o.v. 3.1.0/3.1.1, nu op een betrouwbare basis).
* De tijdelijke "VIEW! Privacy DEBUG"-diagnose uit het testtraject is volledig verwijderd.

= 3.1.1 =
* Nieuw: alle front-end teksten van VIEW! Privacy (cookiebanner, slogan, knoppen, privacybeleid-link, voorkeurencentrum, cookiecategorieën, dienstlabels, bewaartermijn-notitie, placeholders van geblokkeerde embeds, privacyknop/tooltip) zijn nu vertaalbaar via Polylang → Talen → Vertaling van strings (groep "VIEW! Privacy"), zonder machinevertaling. Nederlands blijft de bron- en standaardtaal; onvertaalde strings tonen gewoon de Nederlandse tekst.
* Zonder Polylang actief: geen enkele wijziging in gedrag (bestaande gettext-structuur via __()/esc_html__() blijft de enige vertaalroute).
* Bugfix (documentatie): `Project-Id-Version` in het `.pot`-bestand liep achter op het werkelijke versienummer; wordt voortaan automatisch uit de plugin-header overgenomen.

= 3.1.0 =
* Nieuw: ondersteuning voor Polylang. Op meertalige sites toont de banner nu op iedere taalversie, met een link naar de privacybeleidpagina van de actief bekeken taal (in plaats van alleen de taal waarin de privacybeleidpagina oorspronkelijk is ingesteld).
* Nieuw: ontbreekt de Polylang-vertaling van de privacybeleidpagina voor een bepaalde taal, dan verdwijnt de banner niet stilzwijgend — de plugin valt terug op de oorspronkelijke pagina en toont een duidelijke waarschuwing op het dashboard met de betreffende taal/talen.
* Zonder Polylang actief: geen enkele wijziging in gedrag.

= 3.0.2 =
* Bugfix: de instellingen Achtergrond, Tekst en Accent (knoppen) hadden geen effect op de front-end. Banner, voorkeurencentrum en placeholders van geblokkeerde embeds gebruikten grotendeels vaste VIEW!-merkkleuren in plaats van de ingestelde kleuren.
* Bugfix: "Alles accepteren" was visueel niet te onderscheiden van de overige knoppen. Er is nu een vaste hiërarchie: "Alles accepteren" is de primaire (gevulde) knop; "Alles weigeren", "Privacy-instellingen" en "Privacybeleid" zijn secundaire (omlijnde) knoppen — zonder aparte kleurinstelling per knop.
* Wijziging: de accentkleur wordt nu ook toegepast op relevante accenten (tagline, categorie-iconen, toggle-switches, focusranden) in zowel banner als voorkeurencentrum.

= 3.0.1 =
* X-sluitknop verwijderd uit de cookiebanner: de banner kan alleen worden gesloten via een expliciete keuze (Alles accepteren / Alles weigeren / Voorkeuren).
* Bewaartermijn verduidelijkt en vastgezet op 365 dagen: de tekst in banner en voorkeurencentrum benoemt dit nu expliciet als de door VIEW! Privacy gehanteerde termijn, niet als een AVG-vereiste.
* Eigen afbeelding voor de herstelknop behouden en verder gecontroleerd: geüploade afbeeldingen gebruiken voortaan automatisch de kleine, door WordPress gegenereerde miniatuurversie, zodat een groot bestand de laadtijd niet beïnvloedt.
* AVG/GDPR/ePrivacy-claim in de plugin-omschrijving (readme.txt en plugin-header) zorgvuldiger geformuleerd: niet langer "volledig conform", maar "ontwikkeld met als uitgangspunt" de regelgeving, met een toelichting dat naleving mede afhankelijk is van de sitespecifieke configuratie en gebruikte diensten.

= 3.0.0 =
* Nieuw: banner en voorkeurencentrum tonen nu een korte notitie dat de keuze 365 dagen wordt bewaard en via de privacy-instellingen opnieuw kan worden ingesteld.
* Wijziging: de bewaartermijn van de toestemmingskeuze staat vast in de code op 365 dagen (voorheen 180).
* Verwijderd: het instelbare bewaartermijn-veld is uit de instellingenpagina gehaald; de logboek-bewaartermijn staat vast op 365 dagen.
* Wijziging: de knop "Privacybeleid" staat weer in hoofdletters, net als de overige knoppen destijds.
* Nieuw: het voorkeurencentrum toont nu dezelfde header (incl. logo) als de banner.
* Wijziging: het paneel met de stroopwafel en "Privacy-instellingen" wordt zwart bij hover; de tekst wordt dan wit voor de leesbaarheid.
* Nieuw: rechtsboven in de banner staat nu een sluit-X. Als deze wordt gebruikt, is er geen keuze vastgelegd en verschijnt de banner bij het volgende bezoek automatisch opnieuw.
* Nieuw: banner en voorkeurencentrum tonen onderaan een copyrightregel met het actuele jaartal en het pluginversienummer.
* Nieuw: naast de standaard stroopwafelfoto kan nu een eigen afbeelding voor de herstelknop worden geüpload via Instellingen → Banner.

= 2.0.0 =
* Nieuw: naast het stroopwafel-icoontje staat nu een klein tekstlabel "Privacy-instellingen".
* Nieuw: het dashboard toont rechtsonder, links van de kleurblokjes, het actuele versienummer (bv. "Versie 2.0.0 — Stable"). Dit werkt automatisch mee met elke pluginversie.
* Wijziging: de tekst in de placeholders van geblokkeerde inhoud (YouTube, Google Maps, etc.) is herschreven naar twee regels: een vaste zin "Uw privacy staat voorop." gevolgd door een dienst-specifieke zin, bv. "U bepaalt zelf wanneer deze video wordt geladen."

= 1.9.3 =
* Wijziging: de slagschaduw van banner en voorkeurencentrum is iets zwaarder gemaakt (verder uitgestoken en donkerder), voor een duidelijker "los van de pagina" effect.
* Wijziging: het VIEW!-logo is overal 10% vergroot — in de banner, het eenvoudige-modus-bericht, de admin-header en de logo-preview in de instellingen.

= 1.9.2 =
* Bugfix: de knop "Privacybeleid" stond nog in hoofdletters in zowel de banner als het voorkeurencentrum; nu gewone tekst, gelijk aan de overige knoppen.
* Nieuw: banner en voorkeurencentrum hebben nu een subtiele slagschaduw rechts- en onderzijde, zodat ze visueel loskomen van de achtergrond van de pagina.

= 1.9.1 =
* Bugfix: in het voorkeurencentrum pasten de vier knoppen (Voorkeuren opslaan, Alles accepteren, Alles weigeren, Privacybeleid) nog niet altijd naast elkaar. Breedte verder aangepast (760px → 900px).

= 1.9.0 =
* Wijziging: het stroopwafel-icoon van de herstelknop is teruggezet naar de originele foto (public/images/stroopwafel.png) i.p.v. het zelfgemaakte vector-icoon.
* Wijziging: de "hap"-klikanimatie is vervangen door een subtiele pulse-animatie op zowel hover als klik.
* Wijziging: banner (880px → 980px) en voorkeurencentrum (680px → 760px) zijn verder verbreed zodat ook de knop "Privacybeleid" naast de andere knoppen past.
* Nieuw: op kleine schermen (≤600px) staan alle knoppen — in zowel de banner als het voorkeurencentrum — nu onder elkaar met gelijke breedte, in plaats van los af te breken.

= 1.8.1 =
* Bugfix: het stroopwafel-icoon van de herstelknop was per abuis groen en zonder wafelpatroon, waardoor het niet als stroopwafel herkenbaar was. Opnieuw opgebouwd in de karakteristieke bruine kleur met een wafelruitpatroon en een duidelijke hap.
* Wijziging: banner en voorkeurencentrum zijn breder gemaakt (banner 720px → 880px, voorkeurencentrum 560px → 680px) zodat de knoppen naast elkaar passen in plaats van af te breken.

= 1.8.0 =
* Nieuw: de stroopwafel is terug, maar nu als eigen, gestileerd VIEW!-icoon (geen stockfoto meer) en specifiek als knop om het voorkeurencentrum te openen — het VIEW!-logo blijft de hoofdidentiteit in de banner zelf.
* Nieuw: subtiele "hap"-animatie (~150ms) op de stroopwafel-knop bij een klik.
* Nieuw: instelbare tagline boven de bannertitel (Instellingen → Banner), standaard "Uw privacy. Uw keuze."
* Wijziging: de knop op de placeholder-kaart toont nu een contextuele tekst ("Kaart laden", "Video laden") in plaats van de generieke "Cookies accepteren".
* Wijziging: banner heeft meer witruimte, grotere regelafstand en iets hogere knoppen.
* Wijziging: dashboard toont bovenaan een "VIEW! Privacy Status"-blok (Banner actief / Privacybeleid gekoppeld / Diensten beveiligd / Laatste controle) in plaats van de eerdere eenregelige AVG-statusbalk.

= 1.7.0 =
* Nieuw: AVG-statusindicator bovenaan het dashboard (🟢 Gereed / 🟡 Aandacht vereist / 🔴 Niet gereed), gebaseerd op de gekoppelde privacybeleidpagina en de scanresultaten.
* Nieuw: vijf statuskaarten (Privacybeleid, Analytics, YouTube, Google Maps, Laatste scan). De laatste vier zijn gekoppeld aan een echt scanmechanisme: de plugin registreert (met rate-limiting, max. 1x per 5 minuten) welke diensten daadwerkelijk op de site worden aangetroffen en geblokkeerd, in plaats van alleen te tonen of een dienst in de instellingen is aangevinkt.
* Wijziging: dashboard bevat aanzienlijk minder lopende tekst — de tabellen "Actieve diensten" en "Systeemcontrole" zijn vervangen door compacte badges, en de privacybeleid-waarschuwing is ingekort tot één regel.

= 1.6.0 =
* Wijziging: het herstelicoon linksonder (voorheen een foto van een stroopwafel, wat als "gewoon een cookie" oogde) is vervangen door een eigen, klein VIEW!-oogicoon (lijnstijl, in het VIEW!-groen) en verkleind voor een onopvallendere uitstraling.
* Nieuw: subtiele animaties — de banner faded rustiger in (langere, zachtere overgang), en geblokkeerde embeds worden bij toestemming vloeiend vervangen door de geladen inhoud in plaats van abrupt te wisselen.

= 1.5.0 =
* Nieuw: het voorkeurencentrum toont categorieën nu als accordeon — de uitleg per categorie is standaard ingeklapt en verschijnt pas na een klik op de categorierij.
* Nieuw: geblokkeerde embeds (YouTube, Google Maps, en overige diensten) tonen nu een nette kaart met dienstlogo, korte uitleg en een knop "Cookies accepteren" die direct alleen die ene dienst toestaat, plus een link naar het volledige voorkeurencentrum. Dit vervangt het eerdere grijze vlak.

= 1.4.0 =
* Wijziging: rustigere, minder "blokkerige" banner en voorkeurencentrum — meer witruimte, zachtere schaduw, categorieën als afgeronde kaartjes in plaats van harde scheidingslijnen.
* Nieuw: lijniconen (in #009c3a) per categorie in het voorkeurencentrum: Noodzakelijk, Statistiek, Marketing en Externe media.
* Nieuw: de schakelaars in het voorkeurencentrum zijn modernere schuifknoppen, vastgezet op #009c3a (was voorheen instelbaar via de algemene accentkleur).
* Wijziging: de categorie "Voorkeuren" heet nu "Externe media", met een passende omschrijving. Let op: bestaande installaties die hun instellingen al hebben opgeslagen, behouden de oude labeltekst totdat deze handmatig wordt bijgewerkt bij Instellingen → Cookiecategorieën (nieuwe installaties krijgen automatisch de nieuwe tekst).

= 1.3.0 =
* Bugfix (Prio 1): Google Maps-embeds via het formaat `google.com/maps/embed?pb=...` (de meestgebruikte "Kaart insluiten"-link van Google) werden niet herkend en dus niet geblokkeerd. Toegevoegd aan de herkenningspatronen. Embeds via `maps.google.com` en de Maps JavaScript API (`maps.googleapis.com`, o.a. gebruikt door de Themify Map-module) waren al gedekt.
* Bugfix (Prio 2): het intrekken van eerder gegeven toestemming liet een reeds geladen embed (bv. een afgespeelde YouTube-video) zichtbaar staan. De pagina ververst nu automatisch zodra een categorie wordt ingetrokken, zodat geladen content daadwerkelijk verdwijnt.
* Wijziging (Prio 3): Google Tag Manager wordt niet langer als losse statistiekdienst behandeld, maar als "container" die laadt zodra minstens één niet-noodzakelijke categorie is toegestaan. Daarnaast worden nu Google Consent Mode v2-signalen verstuurd (`gtag('consent', ...)`), zodat losse tags binnen GTM zelf ook rekening houden met de gekozen categorieën. Let op: dit vereist dat de triggers in de GTM-container zelf zijn ingesteld om naar Consent Mode te luisteren — dat kan de plugin niet afdwingen.
* Onderhoud (Prio 4): het `.pot`-vertaalbestand is geregenereerd en bevat nu alle 142 vertaalbare teksten (PHP en front-end) als voorbereiding op Engelse en Duitse vertalingen. Er zijn nog geen `en_US`/`de_DE`-vertaalbestanden meegeleverd.

= 1.2.2 =
* Wijziging: tekst van de globale melding bij een ontbrekende privacybeleidpagina aangepast naar: "Er is geen privacybeleidpagina ingesteld. Daarom wordt de cookiebanner niet weergegeven. Ga naar Instellingen → Privacy en wijs eerst een privacybeleidpagina toe."

= 1.2.1 =
* Nieuw: als er geen privacybeleidpagina is gekoppeld, verschijnt er nu ook een melding bovenaan élke WordPress-beheerpagina (niet alleen op het plugin-dashboard): "Er is geen privacybeleidpagina ingesteld. De cookiebanner is daarom uitgeschakeld." met een knop "Open Instellingen → Privacy".

= 1.2.0 =
* Wijziging: het veld "Logo" en het handmatige veld "Link naar privacy-/cookiebeleid" zijn verwijderd uit Instellingen → Banner.
* Nieuw: de link naar het privacybeleid wordt nu automatisch overgenomen van de privacybeleidpagina die WordPress zelf kent (Instellingen → Privacy, `wp_page_for_privacy_policy`).
* Belangrijk: als er geen (gepubliceerde) privacybeleidpagina is gekoppeld, wordt de cookiebanner niet getoond en wordt er ook niets meer geblokkeerd — de site draait dan zonder consentmechanisme totdat dit is ingesteld. Het dashboard toont hiervoor een duidelijke waarschuwing met directe link naar Instellingen → Privacy.

= 1.1.1 =
* Wijziging: de optie om een eigen logo te uploaden is verwijderd uit Instellingen → Banner. De banner toont voortaan altijd het vaste VIEW!-beeldmerk.

= 1.1.0 =
* Nieuw: "Alleen functionele cookies"-modus (Instellingen → Banner). Voor sites die uitsluitend noodzakelijke/functionele cookies plaatsen: toont een eenvoudige melding met instelbare tekst en één knop (bv. "Ga verder naar de site") in plaats van de volledige toestemmingsvraag met categorieën.
* Nieuw: dashboardkaart die toont welke bannermodus actief is.
* Wijziging: het copyrightjaartal in de dashboardfooter wordt nu automatisch bijgewerkt op basis van de actuele datum, in plaats van hardgecodeerd te zijn.

= 1.0.6 =
* Bugfix: de stroopwafel-herstelknop had een witte cirkel als achtergrond die op hover zwart kon worden door standaard browser-buttonstyling; de achtergrond is nu volledig transparant.
* Wijziging: de 4 knoppen (Alles accepteren, Alles weigeren, Voorkeuren, Privacybeleid) hebben geen kleurverloop meer, maar een witte achtergrond met zwarte tekst en een blauw kader dat bij hover groen wordt.

= 1.0.5 =
* Bugfix: de tekstkleur van de Privacybeleid-knop (een link-element) viel op hover soms terug naar de standaard blauwe/paarse linkkleur van de browser in plaats van wit te blijven. Tekstkleur is nu voor alle knopstatussen (inclusief hover en bezocht) expliciet op wit vastgezet.

= 1.0.4 =
* Wijziging: alle knoppen in de banner en het voorkeurencentrum (Alles accepteren, Alles weigeren, Voorkeuren, Privacybeleid) hebben nu een verloop van blauw naar groen, dat bij hover omkeert naar groen-naar-blauw.
* Wijziging: de tekst van de Privacybeleid-knop wordt in hoofdletters weergegeven.

= 1.0.3 =
* Verbeterd: nieuwe stroopwafel-afbeelding met correcte transparantie verwerkt voor de herstelknop.
* Wijziging: verticale streep links van het VIEW!-logo in de paginaheaders verwijderd.
* Wijziging: primaire ("opslaan") knoppen in het dashboard zijn nu groen in plaats van blauw.
* Wijziging: de Privacybeleid-link in de banner en het voorkeurencentrum is nu een knop van dezelfde grootte als de andere drie knoppen.

= 1.0.2 =
* Nieuw: VIEW!-beeldmerk in de paginatitels van het dashboard vervangt de tekstuele "VIEW!".
* Nieuw: zwevende "stroopwafel"-knop linksonder in beeld waarmee bezoekers, ná het geven van hun eerste toestemming, hun cookievoorkeuren opnieuw kunnen openen.
* Wijziging: kaarten op het dashboard hebben weer een witte achtergrond in plaats van grijs.

= 1.0.1 =
* Nieuw: instelbare link naar de privacy-/cookiebeleidpagina, zichtbaar in de banner en het voorkeurencentrum (Instellingen → Banner).
* Nieuw: dashboardfooter met copyrightvermelding en het officiële VIEW!-kleurenpalet (Zaal Design).
* Dashboard, knoppen en badges gestyled met het VIEW!-kleurenpalet: blauw #261b62, groen #009c3a, zwart #1d1d1b, grijs #f5f4f4.

= 1.0.0 =
* Eerste release.
* Cookiebanner met "Alles accepteren", "Alles weigeren" en "Voorkeuren" (geen sluitknop).
* Voorkeurencentrum met vier standaardcategorieën.
* Automatische scriptblokkering voor inline scripts, externe scripts en iframes/embeds.
* Ingebouwde ondersteuning voor 7 veelgebruikte diensten, uitbreidbaar via UI en filter.
* Placeholder voor geblokkeerde embeds (YouTube, Google Maps) met "Cookies aanpassen"-knop.
* Modern dashboard met status, actieve diensten, consentversie en systeemcontrole.
* Volledig instelbare banner (kleuren, posities, typografie, animatie).
* Optionele consent-logging met automatische opschoning.
* Consentversiebeheer met automatische herconsent bij wijzigingen.
* Instellingen import/export als JSON.
* Multisite-ondersteuning.
* WCAG 2.2 AA toegankelijkheid (focus-trap, aria-labels, toetsenbordnavigatie).

== Upgrade Notice ==

= 3.1.7 =
Added native WordPress update checks for the public GitHub Releases. Existing plugin settings and visitor consent are retained; no new consent version is required.


= 3.1.6 =
Bugfix: banner sluit nu correct bij het openen van het voorkeurencentrum (en verschijnt terug als er zonder keuze wordt gesloten); privacybeleid-URL volgt nu betrouwbaar de Polylang-taal. Geen herconsent vereist.

= 3.1.5 =
Bugfix: voorkeurencentrum kan niet meer permanent vastlopen bij een onverwachte fout; "Privacybeleid"-knop niet meer in hoofdletters. Geen herconsent vereist.

= 3.1.4 =
Belangrijke bugfix: bannerteksten en categorielabels worden nu ook zonder Polylang correct vertaald (bv. Duits); plus de "dag/dagen geleden"-meervoudsvorm. Geen herconsent vereist.

= 3.1.3 =
Nieuw: complete Duitse vertaling (de_DE / de_DE_formal). Geen functionele wijzigingen, geen herconsent vereist.

= 3.1.2 =
Belangrijke bugfix: privacybeleidpagina is nu een eigen VIEW! Privacy-instelling (Instellingen → Privacybeleid), los van WP's privacy-instelling — lost het Polylang-probleem definitief op. Controleer na het bijwerken de instelling Instellingen → Privacybeleid.

= 3.1.1 =
Nieuw: alle front-end teksten zijn nu vertaalbaar via Polylang (Talen → Vertaling van strings). Geen herconsent vereist.

= 3.1.0 =
Nieuw: Polylang-ondersteuning — de banner verschijnt nu op iedere taalversie met de juiste privacybeleidlink. Geen herconsent vereist.

= 3.0.2 =
Belangrijke bugfix: Achtergrond/Tekst/Accent-instellingen werken nu daadwerkelijk, en knoppen hebben een duidelijke primair/secundair-hiërarchie. Geen herconsent vereist.

= 3.0.1 =
X-sluitknop verwijderd (banner vereist een expliciete keuze), bewaartermijntekst en AVG-claim juridisch zorgvuldiger geformuleerd, upload-afbeelding nu automatisch verkleind. Geen herconsent vereist.

= 3.0.0 =
Bewaartermijn-notitie, sluit-X op de banner, gelijke header bij Voorkeuren, copyright/versie in banner en voorkeuren, en eigen herstelknop-afbeelding mogelijk. Geen herconsent vereist tenzij u zelf tekst/categorieën wijzigt.

= 2.0.0 =
Label bij de stroopwafel, versienummer op het dashboard, en nieuwe placeholder-teksten voor geblokkeerde inhoud. Geen herconsent vereist.

= 1.9.3 =
Zwaardere slagschaduw en 10% groter VIEW!-logo overal. Geen herconsent vereist.

= 1.9.2 =
Bugfix: privacybeleid-knop niet meer in hoofdletters. Plus een subtiele slagschaduw aan banner/voorkeurencentrum. Geen herconsent vereist.

= 1.9.1 =
Bugfix: knoppen in het voorkeurencentrum passen nu echt naast elkaar. Geen herconsent vereist.

= 1.9.0 =
Stroopwafel is weer de originele foto, met een pulse i.p.v. hap-animatie. Bredere banner/voorkeurencentrum en nette knoppenstack op mobiel. Geen herconsent vereist.

= 1.8.1 =
Bugfix: stroopwafel-icoon is nu echt bruin met wafelpatroon. Banner en voorkeurencentrum zijn breder voor knoppen naast elkaar. Geen herconsent vereist.

= 1.8.0 =
Stroopwafel terug als eigen icoon voor het voorkeurencentrum, tagline boven de titel, luchtigere banner en een vriendelijker dashboardstatusblok. Geen herconsent vereist.

= 1.7.0 =
Nieuw dashboard met AVG-statusindicator en vijf statuskaarten op basis van een echt scanmechanisme. Geen herconsent vereist.

= 1.6.0 =
Nieuw, klein VIEW!-oogicoon i.p.v. de stroopwafel-foto, plus rustigere animaties. Geen herconsent vereist.

= 1.5.0 =
Voorkeurencentrum nu als accordeon en geblokkeerde embeds tonen een kaart i.p.v. een grijs vlak. Geen herconsent vereist.

= 1.4.0 =
Visuele update: rustigere banner, categorie-iconen en modernere schakelaars. Controleer na de update de categorielabels bij Instellingen → Cookiecategorieën. Geen herconsent vereist.

= 1.3.0 =
Belangrijke bugfixes: Google Maps-embeds worden nu correct geblokkeerd, ingetrokken toestemming ververst de pagina, en GTM gedraagt zich als container met Consent Mode v2. Geen herconsent vereist.

= 1.2.2 =
Tekstwijziging in de privacybeleid-waarschuwing. Geen herconsent vereist.

= 1.2.1 =
Extra melding op alle beheerpagina's als er geen privacybeleidpagina is gekoppeld. Geen herconsent vereist.

= 1.2.0 =
Belangrijk: controleer na deze update meteen of bij Instellingen → Privacy een privacybeleidpagina is gekoppeld. Zonder die koppeling toont de plugin de cookiebanner niet en blokkeert deze geen scripts meer.

= 1.1.1 =
Logo is niet langer instelbaar; de banner toont voortaan altijd het VIEW!-beeldmerk. Geen herconsent vereist.

= 1.1.0 =
Nieuw: optionele eenvoudige modus voor sites zonder consentplichtige cookies, en een automatisch bijgewerkt copyrightjaartal. Bestaande bezoekersconsent blijft ongewijzigd.

= 1.0.6 =
Bugfix stroopwafel-knop (transparante achtergrond) en nieuwe knopstijl: wit met blauw/groen kader. Geen herconsent vereist.

= 1.0.5 =
Bugfix: witte tekstkleur op de Privacybeleid-knop blijft nu consistent, ook op hover.

= 1.0.4 =
Nieuwe knopstijl: blauw-naar-groen verloop met hover-effect, en hoofdletters op de Privacybeleid-knop. Geen herconsent vereist.

= 1.0.3 =
Visuele verbeteringen: nieuwe stroopwafel-afbeelding, groene opslaanknoppen, consistente knopgroottes. Geen herconsent vereist.

= 1.0.2 =
Voegt het VIEW!-logo in de adminheaders toe en een stroopwafel-herstelknop voor bezoekers. Geen herconsent vereist.

= 1.0.1 =
Voegt een privacy-/cookiebeleidlink en dashboard-branding toe. Geen herconsent van bezoekers vereist.

= 1.0.0 =
Eerste release van VIEW! Privacy.

