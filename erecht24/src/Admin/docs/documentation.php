<?php
/**
 * Help documentation content.
 *
 * @package ERecht24LegalText
 */

defined( 'ABSPATH' ) || exit;

return <<<'MARKDOWN'
Anleitung zum eRecht24 Rechtstexte Plugin
==========================================

Hinweis zur Nutzung des Plugins ohne API-Schlüssel
--------------------------------------------------
Die Nutzung des Plugins ist generell auch ohne API-Schlüssel möglich. Nutzer können sich z.B. bei [eRecht24](https://www.e-recht24.de) ein kostenlosen Rechtstext generieren lassen.
Der Text wird dann u.a. auch als HTML-Ausgabe bereitgestellt. Diesen HTML-Text können Sie im Plugin hinterlegen. Im Frontend können Sie die exemprarisch dargestellten Shortcode nutzen.

### Beispiel: Erstellung eines Impressums ohne API-Schlüssel
 
1.  Erstellen Sie Ihr Impressum auf der Seite von [eRecht24](https://www.e-recht24.de/impressum-generator.html). (Sie können natürlich auch ein eigenes Impressum selber schreiben, oder anderwertig erstellen). 
2.  Kopieren Sie aus dem generierten pdf-Dokument die _HTML_\-Ansicht Ihres (deutschsprachigen) Impressums in die Zwischenablage.
3.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Impressum_.
4.  Wählen Sie bei Datenquelle die Option _Lokale Version_
5.  Fügen Sie den HTML-Code aus der Zwischenablage in das Feld _Lokales HTML (DE)_
6.  Klicken Sie auf _Rechtstext-Einstellungen speichern_.
7.  Wiederholen Sie ggf. die Schritte 2 -6 für das englischsprachige Impressum, mit dem Unterschied, dass sie den HTML-Quellcode dann in das Feld _Lokales HTML (EN)_ einfügen.
8.  Schließen Sie die Bearbeitungsansicht des eRecht24 Rechtstexte Plugins.

Die Integration auf einer Seite erfolgt wie unten beschrieben im Kapitel _Integrieren des Impressum in eine Seite_

Die Erstellung der anderen Rechtstexte erfolgt analog.


Hinterlegen des API-Schlüssels
------------------------------
Für [eRecht24 Premium Nutzern](https://www.e-recht24.de/mitglieder/) bietet dieses Plugin die Möglichkeit die Rechtstexte aus dem eRecht24 Projekt Manager in das WordPress zu übertragen. Damit die dafür nötige Kommunikation zwischen beiden Seiten stattfinden kann, ist die Hinterlegung eines API-Schlüssels notwendig. Dafür gehen Sie wie folgt vor:

1.  Legen Sie im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/) ein Projekt für Ihre Website an, sofern es noch keines gibt.
2.  Klicken Sie dort neben dem Projektnamen auf das Symbol Einstellungen (Zahnradsymbol).
3.  Klicken Sie auf die Schaltfläche "Neuen API-Schlüssel erzeugen".
4.  Kopieren Sie den API-Schlüssel in die Zwischenablage.
5.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin und fügen Sie den API-Schlüssel in das zugehörige Feld hinter dem Reiter _API-Schlüssel_ Klicken Sie auf _API-Einstellungen speichern_, um die Einstellung zu übernehmen.

Ihr eRecht24 Rechtstexte Plugin kann ab sofort mit dem eRecht24 Projekt Manager kommunizieren.

**Hinweis**: Sie können das Plugin auch ohne API-Schlüssel nutzen. Sie müssen dann aber bei jeder Änderung eines Rechtstextes in eRecht24 Premium den HTML-Code manuell aus dem eRecht24 Projekt Manager in die die Plugin-Konfiguration hier übertragen und speichern (siehe Übertragung durch Kopieren und Einfügen bei den einzelnen Rechtstexten in der nachfolgenden Anleitung).

Hinterlegung des Impressums
---------------------------

### Erstellen des Impressums

#### Übertragung per Klick

Diese Übertragungsform setzt einen hinterlegten und gültigen API-Schlüssel voraus.

1.  Erstellen Sie Ihr Impressum im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Impressum_.
3.  Wählen Sie bei Datenquelle die Option _eRecht24 Projekt Manager_
4.  Klicken Sie darunter auf die Schaltfläche _Diesen Rechtstext synchronisieren und speichern_, um Ihr Impressum aus dem eRecht24 Projekt Manager zu übernehmen. Ihr Impressum ist nun in der Konfiguration Ihres eRecht24 Rechtstexte Plugins gespeichert.
    **Hinweis**: Wiederholen Sie Schritt 4 künftig nach jeder Änderung des Impressums im eRecht24 Projekt Manager. Aus Haftungsgründen muss jede Aktualisierung des Impressums manuell durch den Websitebetreiber erfolgen und geprüft werden. Ein automatisches Einspielen ist daher nicht vorgesehen.
5.  Schließen Sie die Konfigurationsansicht des eRecht24 Rechtstexte Plugins.

#### Übertragung durch Kopieren und Einfügen

1.  Erstellen Sie Ihr Impressum im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Öffnen Sie dort die _HTML_\-Ansicht Ihres (deutschsprachigen) Impressums und klicken Sie auf _HTML-Code in die Zwischenablage kopieren_.
3.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Impressum_.
4.  Wählen Sie bei Datenquelle die Option _Lokale Version_
5.  Fügen Sie den HTML-Code aus der Zwischenablage in das Feld _HTML-Code (DE) – Lokal_
6.  Klicken Sie auf _Rechtstext-Einstellungen speichern_.
7.  Wiederholen Sie ggf. die Schritte 2 -6 für das englischsprachige Impressum, mit dem Unterschied, dass sie den HTML-Quellcode dann in das Feld _Lokales HTML (EN)_ einfügen.
8.  Schließen Sie die Bearbeitungsansicht des eRecht24 Rechtstexte Plugins.

### Integrieren des Impressum in eine Seite

Für WordPress gibt es inzwischen eine große Zahl von Editoren zum Einpflegen von Inhalten. Wenn Sie Ihren Eingabeeditor hier nicht in der nachfolgenden Liste finden, verwenden Sie bitte den Shortcode des Classic Editors. Kopieren Sie den Shortcode dann einfach in ein Textelement Ihres Editors.

**Hinweis**: Fügen Sie Impressum und Datenschutzerklärung immer auf separaten Seiten ein, da der Gesetzgeber dies so verlangt. Machen Sie beide Seiten von jeder Unterseite der Website aus per Link erreichbar.

#### Wenn Sie den WordPress Classic Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite auf, in welchem das Impressum künftig angezeigt werden soll.
2.  Setzen Sie den Mauscursor im Texteditor an die Stelle, an welcher der Text des Impressums platziert werden soll.
3.  Klicken Sie auf den Button _eRecht24_ in der Toolbar HTML-Editors. Achten Sie darauf, dass im Editor die Ansicht _Visuell_ ausgewählt ist.
4.  Wählen Sie in dem angezeigten Dialog bei Rechtstext in der Auswahlliste den Eintrag _Impressum_ in Ihrer gewünschten Sprache aus und klicken Sie darauf.
5.  Für das deutsche Impressum wird nun der Shortcode `[erecht24 type="imprint" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="imprint" lang="en" strip_title="false"]`.
6.  Speichern und veröffentlichen Sie Ihre Seite.
7.  Der Shortcode wird dann, sofern das eRecht24 Rechtstexte Plugin aktiviert ist, auf der Website beim Anzeigen Ihrer Seite durch das eigentliche Impressum ersetzt.

**Hinweis**: Das Impressum wird im eRecht24 Projekt Manager mit der Überschrift „Impressum" erstellt. Sofern Ihre Seite ebenfalls die Überschrift „Impressum" hat, könnte es hier zu einer Dopplung kommen. Ändern Sie den Wert im Shortcode hinter _strip\_title_ von _false_ auf _true_. Dann wird die Überschrift aus dem Impressumtext in der Anzeige entfernt.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den WordPress Gutenberg (Block) Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Block-Editor auf, in welchem das Impressum künftig angezeigt werden soll.
2.  Klicken Sie oben links auf die Schaltfläche _Block hinzufügen_.
3.  Wählen Sie in der Liste _Allgemeine Blöcke_ den Block _eRecht24_ durch Anklicken aus. Ein neuer Block wird nun eingefügt.
4.  Klicken Sie oben rechts auf das Symbol _Einstellungen_ (Zahnrad), so dass am rechten Fensterrand die Spalte mit den Einstellungen des Blocks sichtbar wird.
5.  Achten Sie darauf darauf, dass der eRecht24-Block angewählt, damit in der rechten Spalte die Einstellungen für den Block _eRecht24 Rechtstexte_ sichtbar sind.
6.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Impressum_
7.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
8.  In der Vorschau sehen Sie nun bereits Ihr Impressum.
9.  Sofern sich der Titel doppelt, da ja Ihre Seite ebenfalls eine eigene Überschrift hat, aktivieren Sie in der Auswahlliste den Schalter _Titel entfernen_. Damit wird der Titel aus dem HTML-Code des Impressums ausgeblendet.
10.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Block-Editor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den Elementor verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Elementor auf, in welchem das Impressum künftig angezeigt werden soll.
2.  Öffnen Sie die Liste der Elemente durch Klick auf das entsprechende Icon oben links.
3.  Im Bereich _Generell_ finden Sie das Element _eRecht24_. Ziehen Sie das Element mit gedrückter Maustaste rechts in die Vorschau an die Stelle in Ihren Inhalten, wo künftig das Impressum erscheinen soll.
4.  Wählen Sie das Element dann in der Vorschau an, so dass sich links in der Spalte die Bearbeitungsansicht für das Element _eRecht24_ öffnet.
5.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Impressum_
6.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
7.  In der Vorschau sehen Sie nun bereits Ihr Impressum. Sofern sich der Titel doppelt, da ja Ihre Seite ebenfalls eine eigene Überschrift hat, aktivieren Sie in der Auswahlliste den Schalter _Titel entfernen_. Damit wird der Titel aus dem HTML-Code des Impressums ausgeblendet.
8.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Elementor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie Divi Builder verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Divi Builder auf, in welchem das Impressum künftig angezeigt werden soll.
2.  Klicken Sie auf ein Inhaltselement der Seite und wählen darunter das _+_-Symbol zum Einfügen eines neuen Elementes aus.
3.  Im Bereich _New Module_ wählen Sie das Modul _Text_ und fügen es ein.
4.  Entfernen Sie nun den voreingefügten Platzhaltertext.
5.  Für das deutsche Impressum wird nun der Shortcode `[erecht24 type="imprint" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="imprint" lang="en" strip_title="false"]`.
6.  Klicken Sie unten rechts auf den Haken zum Speichern.
7.  Veröffentlichen Sie anschließend Ihre Änderungen über den _Save_-Button unten rechts auf der Seite.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Divi Builder und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

Hinterlegung der Datenschutzerklärung
-------------------------------------

### Erstellen der Datenschutzerklärung

#### Übertragung per Klick

Diese Übertragungsform setzt einen hinterlegten und gültigen API-Schlüssel voraus.

1.  Erstellen Sie Ihre Datenschutzerklärung im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Datenschutzerklärung_.
3.  Wählen Sie bei Datenquelle die Option _eRecht24 Projekt Manager_
4.  Klicken Sie darunter auf die Schaltfläche _Diesen Rechtstext synchronisieren und speichern_, um Ihre Datenschutzerklärung aus dem eRecht24 Projekt Manager zu übernehmen. Ihre Datenschutzerklärung ist nun in der Konfiguration Ihres eRecht24 Rechtstexte Plugins gespeichert.
    **Hinweis**: Wiederholen Sie Schritt 4 künftig nach jeder Änderung der Datenschutzerklärung im eRecht24 Projekt Manager. Aus Haftungsgründen muss jede Aktualisierung der Datenschutzerklärung manuell durch den Websitebetreiber erfolgen und geprüft werden. Ein automatisches Einspielen ist daher nicht vorgesehen.
5.  Schließen Sie die Konfigurationsansicht des eRecht24 Rechtstexte Plugins.

#### Übertragung durch Kopieren und Einfügen

1.  Erstellen Sie Ihre Datenschutzerklärung im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Öffnen Sie dort die _HTML_\-Ansicht Ihrer (deutschsprachigen) Datenschutzerklärung und klicken Sie auf _HTML-Code in die Zwischenablage kopieren_.
3.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Datenschutzerklärung_.
4.  Wählen Sie bei Datenquelle die Option _Lokale Version_
5.  Fügen Sie den HTML-Code aus der Zwischenablage in das Feld _Lokales HTML (DE)_
6.  Klicken Sie auf _Rechtstext-Einstellungen speichern_.
7.  Wiederholen Sie ggf. die Schritte 2 -6 für die englischsprachige Datenschutzerklärung, mit dem Unterschied, dass sie den HTML-Quellcode dann in das Feld _Lokales HTML (EN)_ einfügen.
8.  Schließen Sie die Bearbeitungsansicht des eRecht24 Rechtstexte Plugins.

### Integrieren der Datenschutzerklärung in eine Seite

Für WordPress gibt es inzwischen eine große Zahl von Editoren zum Einpflegen von Inhalten. Wenn Sie Ihren Eingabeeditor hier nicht in der nachfolgenden Liste finden, verwenden Sie bitte den Shortcode des Classic Editors. Kopieren Sie den Shortcode dann einfach in ein Textelement Ihres Editors.

**Hinweis**: Fügen Sie Impressum und Datenschutzerklärung immer auf separaten Seiten ein, da der Gesetzgeber dies so verlangt. Machen Sie beide Seiten von jeder Unterseite der Website aus per Link erreichbar.

#### Wenn Sie den WordPress Classic Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite auf, in welchem die Datenschutzerklärung künftig angezeigt werden soll.
2.  Setzen Sie den Mauscursor im Texteditor an die Stelle, an welcher der Text der Datenschutzerklärung platziert werden soll.
3.  Klicken Sie auf den Button _eRecht24_ in der Toolbar HTML-Editors. Achten Sie darauf, dass im Editor die Ansicht _Visuell_ ausgewählt ist.
4.  Wählen Sie in dem angezeigten Dialog bei Rechtstext in der Auswahlliste den Eintrag _Datenschutzerklärung_ in Ihrer gewünschten Sprache aus und klicken Sie darauf.
5.  Für die deutsche Datenschutzerklärung wird nun der Shortcode `[erecht24 type="privacy_policy" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="privacy_policy" lang="en" strip_title="false"]`.
6.  Speichern und veröffentlichen Sie Ihre Seite.
7.  Der Shortcode wird dann, sofern das eRecht24 Rechtstexte Plugin aktiviert ist, auf der Website beim Anzeigen Ihrer Seite durch die eigentliche Datenschutzerklärung ersetzt.

**Hinweis**: Die Datenschutzerklärung wird im eRecht24 Projekt Manager mit der Überschrift „Datenschutzerklärung" erstellt. Sofern Ihre Seite ebenfalls die Überschrift „Datenschutzerklärung" hat, könnte es hier zu einer Dopplung kommen. Ändern Sie den Wert im Shortcode hinter _strip\_title_ von _false_ auf _true_. Dann wird die Überschrift aus dem Datenschutzerklärungstext in der Anzeige entfernt.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den WordPress Gutenberg (Block) Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Block-Editor auf, in welchem die Datenschutzerklärung künftig angezeigt werden soll.
2.  Klicken Sie oben links auf die Schaltfläche _Block hinzufügen_.
3.  Wählen Sie in der Liste _Allgemeine Blöcke_ den Block _eRecht24_ durch Anklicken aus. Ein neuer Block wird nun eingefügt.
4.  Klicken Sie oben rechts auf das Symbol _Einstellungen_ (Zahnrad), so dass am rechten Fensterrand die Spalte mit den Einstellungen des Blocks sichtbar wird.
5.  Achten Sie darauf darauf, dass der eRecht24-Block angewählt, damit in der rechten Spalte die Einstellungen für den Block _eRecht24 Rechtstexte_ sichtbar sind.
6.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Datenschutzerklärung_
7.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
8.  In der Vorschau sehen Sie nun bereits Ihre Datenschutzerklärung.
9.  Sofern sich der Titel doppelt, da ja Ihre Seite ebenfalls eine eigene Überschrift hat, aktivieren Sie in der Auswahlliste den Schalter _Titel entfernen_. Damit wird der Titel aus dem HTML-Code der Datenschutzerklärung ausgeblendet.
10.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Block-Editor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den Elementor verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Elementor auf, in welchem die Datenschutzerklärung künftig angezeigt werden soll.
2.  Öffnen Sie die Liste der Elemente durch Klick auf das entsprechende Icon oben links.
3.  Im Bereich _Generell_ finden Sie das Element _eRecht24_. Ziehen Sie das Element mit gedrückter Maustaste rechts in die Vorschau an die Stelle in Ihren Inhalten, wo künftig die Datenschutzerklärung erscheinen soll.
4.  Wählen Sie das Element dann in der Vorschau an, so dass sich links in der Spalte die Bearbeitungsansicht für das Element _eRecht24_ öffnet.
5.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Datenschutzerklärung_
6.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
7.  In der Vorschau sehen Sie nun bereits Ihre Datenschutzerklärung. Sofern sich der Titel doppelt, da ja Ihre Seite ebenfalls eine eigene Überschrift hat, aktivieren Sie in der Auswahlliste den Schalter _Titel entfernen_. Damit wird der Titel aus dem HTML-Code der Datenschutzerklärung ausgeblendet.
8.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Elementor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie Divi Builder verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Divi Builder auf, in welchem das Impressum künftig angezeigt werden soll.
2.  Klicken Sie auf ein Inhaltselement der Seite und wählen darunter das _+_-Symbol zum Einfügen eines neuen Elementes aus.
3.  Im Bereich _New Module_ wählen Sie das Modul _Text_ und fügen es ein.
4.  Entfernen Sie nun den voreingefügten Platzhaltertext.
5.  Für die deutsche Datenschutzerklärung wird nun der Shortcode `[erecht24 type="privacy_policy" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="privacy_policy" lang="en" strip_title="false"]`.
6.  Klicken Sie unten rechts auf den Haken zum Speichern.
7.  Veröffentlichen Sie anschließend Ihre Änderungen über den _Save_-Button unten rechts auf der Seite.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Divi Builder und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

Hinterlegung der Datenschutzerklärung für Social-Media Profile
--------------------------------------------------------------

### Erstellen der Datenschutzerklärung für Social-Media-Profile

#### Übertragung per Klick

Diese Übertragungsform setzt einen hinterlegten und gültigen API-Schlüssel voraus.

1.  Erstellen Sie Ihre Datenschutzerklärung für Social-Media-Profile im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Datenschutzerklärung für Social-Media-Profile_.
3.  Wählen Sie bei Datenquelle die Option _eRecht24 Projekt Manager_
4.  Klicken Sie darunter auf die Schaltfläche _Diesen Rechtstext synchronisieren und speichern_, um Ihre Datenschutzerklärung für Social-Media-Profile aus dem eRecht24 Projekt Manager zu übernehmen. Ihre Datenschutzerklärung für Social-Media-Profile ist nun in der Konfiguration Ihres eRecht24 Rechtstexte Plugins gespeichert.
    **Hinweis**: Wiederholen Sie Schritt 4 künftig nach jeder Änderung der Datenschutzerklärung für Social-Media-Profile im eRecht24 Projekt Manager. Aus Haftungsgründen muss jede Aktualisierung der Datenschutzerklärung für Social-Media-Profile manuell durch den Websitebetreiber erfolgen und geprüft werden. Ein automatisches Einspielen ist daher nicht vorgesehen.
5.  Schließen Sie die Konfigurationsansicht des eRecht24 Rechtstexte Plugins.

#### Übertragung durch Kopieren und Einfügen

1.  Erstellen Sie Ihre Datenschutzerklärung für Social-Media-Profile im [eRecht24 Projekt Manager](https://www.e-recht24.de/mitglieder/tools/projekt-manager/).
2.  Öffnen Sie dort die _HTML_\-Ansicht Ihrer (deutschsprachigen) Datenschutzerklärung für Social-Media-Profile und klicken Sie auf _HTML-Code in die Zwischenablage kopieren_.
3.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter _Datenschutzerklärung für Social-Media-Profile_.
4.  Wählen Sie bei Datenquelle die Option _Lokale Version_
5.  Fügen Sie den HTML-Code aus der Zwischenablage in das Feld _Lokales HTML (DE)_
6.  Klicken Sie auf _Rechtstext-Einstellungen speichern_.
7.  Wiederholen Sie ggf. die Schritte 2 -6 für die englischsprachige Datenschutzerklärung für Social-Media-Profile, mit dem Unterschied, dass sie den HTML-Quellcode dann in das Feld _Lokales HTML (EN)_ einfügen.
8.  Schließen Sie die Bearbeitungsansicht des eRecht24 Rechtstexte Plugins.

### Integrieren der Datenschutzerklärung für Social-Media-Profile in eine Seite

Für WordPress gibt es inzwischen eine große Zahl von Editoren zum Einpflegen von Inhalten. Wenn Sie Ihren Eingabeeditor hier nicht in der nachfolgenden Liste finden, verwenden Sie bitte den Shortcode des Classic Editors. Kopieren Sie den Shortcode dann einfach in ein Textelement Ihres Editors.

**Wichtig**: Wenn Sie aus Ihrem Social-Media-Profil auf die Seite Ihrer Datenschutzerklärung verlinken, fügen Sie bitte nach dem Kopieren der URL zu Ihrer Datenschutzerklärung in Ihr Social-Media-Profil am Ende der URL zusätzlich den Ankerpunkt _#socialmediaprofile_ an. So springt die Seitenansicht nach dem Aufrufen des Links gleich zum Passus für die Social-Media-Profile.

#### Wenn Sie den WordPress Classic Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite auf, in welchem die Datenschutzerklärung für Social-Media-Profile künftig angezeigt werden soll. **Wir empfehlen Ihnen, den Text unterhalb ihrer bestehenden Datenschutzerklärung einzufügen.**
2.  Setzen Sie den Mauscursor im Texteditor an die Stelle, an welcher der Text der Datenschutzerklärung für Social-Media-Profile platziert werden soll.
3.  Klicken Sie auf den Button _eRecht24_ in der Toolbar HTML-Editors. Achten Sie darauf, dass im Editor die Ansicht _Visuell_ ausgewählt ist.
4.  Wählen Sie in dem angezeigten Dialog bei Rechtstext in der Auswahlliste den Eintrag _Datenschutzerklärung für Social-Media-Profile_ in Ihrer gewünschten Sprache aus und klicken Sie darauf.
5.  Für die deutsche Datenschutzerklärung wird nun der Shortcode `[erecht24 type="privacy_policy_social_media" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="privacy_policy_social_media" lang="en" strip_title="false"]`.
6.  Speichern und veröffentlichen Sie Ihre Seite.
7.  Der Shortcode wird dann, sofern das eRecht24 Rechtstexte Plugin aktiviert ist, auf der Website beim Anzeigen Ihrer Seite durch die eigentliche Datenschutzerklärung für Social-Media-Profile ersetzt.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung für Social-Media-Profile in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den WordPress Gutenberg (Block) Editor nutzen

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Block-Editor auf, in welchem die Datenschutzerklärung für Social-Media-Profile künftig angezeigt werden soll.
2.  Klicken Sie oben links auf die Schaltfläche _Block hinzufügen_.
3.  Wählen Sie in der Liste _Allgemeine Blöcke_ den Block _eRecht24_ durch Anklicken aus. Ein neuer Block wird nun eingefügt.
4.  Klicken Sie oben rechts auf das Symbol _Einstellungen_ (Zahnrad), so dass am rechten Fensterrand die Spalte mit den Einstellungen des Blocks sichtbar wird.
5.  Achten Sie darauf darauf, dass der eRecht24-Block angewählt, damit in der rechten Spalte die Einstellungen für den Block _eRecht24 Rechtstexte_ sichtbar sind.
6.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Datenschutzerklärung für Social-Media-Profile_
7.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
8.  In der Vorschau sehen Sie nun bereits Ihre Datenschutzerklärung für Social-Media-Profile.
9.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Block-Editor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie den Elementor verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Elementor auf, in welchem die Datenschutzerklärung künftig angezeigt werden soll.
2.  Öffnen Sie die Liste der Elemente durch Klick auf das entsprechende Icon oben links.
3.  Im Bereich _Generell_ finden Sie das Element _eRecht24_. Ziehen Sie das Element mit gedrückter Maustaste rechts in die Vorschau an die Stelle in Ihren Inhalten, wo künftig die Datenschutzerklärung erscheinen soll.
4.  Wählen Sie das Element dann in der Vorschau an, so dass sich links in der Spalte die Bearbeitungsansicht für das Element _eRecht24_ öffnet.
5.  Wählen Sie in der Auswahlliste _Typ_ den Eintrag _Datenschutzerklärung für Social-Media-Profile_
6.  Wählen Sie in der Auswahlliste _Sprache_ den Eintrag der gewünschten Sprache, z.B. _Deutsch_
7.  Speichern und veröffentlichen Sie Ihre Seite.

**Hinweis**: Sollte der HTML-Code der Datenschutzerklärung für Social-Media-Profile in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Elementor und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

#### Wenn Sie Divi Builder verwenden

1.  Rufen Sie die Bearbeitungsansicht Ihrer Seite im Divi Builder auf, in welchem das Datenschutzerklärung künftig angezeigt werden soll.
2.  Klicken Sie auf ein Inhaltselement der Seite und wählen darunter das _+_-Symbol zum Einfügen eines neuen Elementes aus.
3.  Im Bereich _New Module_ wählen Sie das Modul _Text_ und fügen es ein.
4.  Entfernen Sie nun den voreingefügten Platzhaltertext.
5.  Für die deutsche Datenschutzerklärung wird nun der Shortcode `[erecht24 type="privacy_policy_social_media" lang="de" strip_title="false"]` eingefügt. Für die englische Version lautet der Shortcode `[erecht24 type="privacy_policy_social_media" lang="en" strip_title="false"]`.
6.  Klicken Sie unten rechts auf den Haken zum Speichern.
7.  Veröffentlichen Sie anschließend Ihre Änderungen über den _Save_-Button unten rechts auf der Seite.

**Hinweis**: Sollte der HTML-Code des Impressums in der Konfiguration des eRecht24 Rechtstexte Plugins fehlen, wird im Divi Builder und auf der Seite im Frontend eine Fehlermeldung ausgeliefert.

Künftige Aktualisierung des Impressums und der Datenschutzerklärung
--------------------------------

Die Texte Ihres Impressums und Ihrer Datenschutzerklärung müssen gelegentlich aktualisiert werden, weil sich beispielsweise Formulierungen geändert haben oder neue Punkte aufgenommen wurden.

### Überarbeitung des Rechtstexts

Hierzu durchlaufen Sie wie gewohnt zunächst den entsprechenden Generator im eRecht24 Projekt Manager. Danach haben Sie folgende Möglichkeiten, Ihre Rechtstexte in Ihr Plugin zu übertragen:

#### Methode 1: Aktualisierung direkt aus dem eRecht24 Projekt Manager

Klicken Sie direkt im eRecht24 Projekt Manager auf das Synchronisieren-Symbol in der Zeile mit Ihrem Rechtstext. Der eRecht24 Projekt Manager baut eine Verbindung zu Ihrem eRecht24 Rechtstexte Plugin auf, welches dann den aktualisierten Rechtstext abruft.

**Wichtig**: Eine Synchronisierung der Rechtstexte kann nur erfolgen, wenn Sie in der Konfiguration Ihres Plugins bei den jeweiligen Rechtstexten bei der Option _Datenquelle_ die Auswahl auf _eRecht24 Projekt Manager_ gesetzt haben.

#### Methode 2: Abruf des geänderten Rechtstexts im eRecht24 Rechtstexte Plugin

Zudem haben Sie die Möglichkeit auch direkt aus dem eRecht24 Rechtstexte Plugin heraus, Impressum und Datenschutzerklärung aus dem eRecht24 Projekt Manager abzurufen und die bisher gespeicherte Version der Texte im Plugin zu überschreiben. Nutzen Sie dafür in der Pluginkonfiguration, die Schaltfläche Alle _Rechtstexte synchronisieren und speichern_ oder jeweils hinter dem Reiter des einzelnen Rechtstext die Schaltfläche _Diesen Rechtstext synchronisieren und speichern_.

**Wichtig**: Eine Synchronisierung der Rechtstexte kann nur erfolgen, wenn Sie in der Konfiguration Ihres Plugins bei den jeweiligen Rechtstexten bei der Option _Datenquelle_ die Auswahl auf _eRecht24 Projekt Manager_ gesetzt haben.

Wenn Sie die Datenquelle _Lokaler Text_ verwenden, können Sie im Reiter des jeweiligen Rechtstextes die Schaltfläche _Lokale Daten mit den zuletzt synchronisierten Texten überschreiben_ nutzen. Dadurch werden die zuletzt abgerufenen eRecht24-Texte in die lokalen Textfelder übernommen.

#### Methode 3: Manuelles Kopieren in Ihr eRecht24 Rechtstexte Plugin

Klicken Sie im eRecht24 Projekt Manager in der Zeile mit Ihrem Rechtstext auf den Link _HTML_ und im sich öffnenden Dialog unten auf die Schaltfläche _HTML-Code in die Zwischenablage kopieren_.

Öffnen Sie anschließend im eRecht24 Rechtstexte Plugin auf Ihrer Website die Konfiguration. Rufen Sie den Reiter für den zugehörigen Rechtstext auf. Stellen Sie sicher, dass dort die Auswahl bei _Datenquelle_ auf _Lokale Version_ gesetzt ist, entfernen Sie im Textfeld den bisherigen Rechtstext und fügen Sie Ihren aktualisierten Rechtstext aus der Zwischenablage ein. Schließen Sie den Vorgang mit Klick auf den Speichern-Button ab.

#### Hinweis für alle v. g. Methoden

Sofern Sie in Ihrer Website ein Caching einsetzen, prüfen Sie bitte, ob der Cache für die betreffenden Seiten mit den Rechtstexten evtl. nochmal geleert werden muss, damit die aktualisierten Inhalte angezeigt werden.

Einrichtung von Google Analytics
--------------------------------

1.  Sofern Sie noch keine Google Analytics Tracking ID für Ihre Seite haben, erstellen Sie mit Ihrem Google Account einen Tracking-Code für Ihre Website ([Hier finden Sie die Anleitung](https://support.google.com/analytics/answer/1008015?hl=de))
2.  Wechseln Sie hier in das eRecht24 Rechtstexte Plugin, klicken Sie auf den Reiter Google Analytics.
3.  Kopieren Sie anschließend die ID des Tracking-Codes (Beispiel: UA-1234567-1) in das Feld Google Analytics Tracking ID.
4.  Wenn der rechtssichere Google Analytics Tracking Code durch das eRecht24 Rechtstexte Plugin eingefügt werden soll, aktivieren Sie die Option Tracking-Code einbinden.
5.  Wenn Sie auch den für eine rechtlich korrekte Google-Analytics-Einbindung notwendigen Code für das Setzen des Opt-Out-Cookies über das eRecht24 Rechtstexte Plugin integrieren möchten, aktivieren Sie die Option Opt-Out-Code einbinden.
6.  Speichern Sie die Einstellung.
7.  Sofern Sie für Ihre Cookie-Einwilligung Usercentrics nutzen, setzen Sie die Option *Usercentrics Cookie-Einwilligung* auf *Ja*, damit Ihr von diesem Plugin eingefügter Google Analytics Tracking Code zusammen mit Usercentrics funktioniert.

**Wichtig**: Wenn Sie den Google Analytics Tracking Code und / oder den Opt-Out-Code über das eRecht24 Rechtstexte Plugin integrieren, achten Sie bitte darauf, dass dieser Code nicht auch durch das Template oder andere Erweiterungen von WordPress integriert wird. Es kann sonst zu Funktionsfehlern, fehlerhaftem Tracking oder zur Beeinträchtigung der Rechtssicherheit kommen.

Deinstallation
--------------

Zum Deinstallieren des eRecht24 Rechtexte Plugins gehen Sie wie folgt vor:

1.  Rufen Sie die Liste der installierten Plugins durch Klick auf _Plugins_ -> _Installierte Plugins_
2.  Suchen Sie in der Liste den Eintrag _eRecht24 Rechtstexte_.
3.  Klicken Sie dort auf den Link _Deaktivieren_.
4.  Klicken Sie beim selben Eintrag dann auf den nun sichtbaren Link _Löschen_.
5.  Entfernen Sie anschließend die Shortcodes bzw. zugehörigen Editor-Elemente aus Ihren Seiten mit den Rechtstexten.

**Hinweis**: Mit diesem Prozess ist sichergestellt, dass auch alle gespeicherten Daten des Plugins aus der Datenbank gelöscht werden. Ein einfaches Löschen des Plugin-Verzeichnisses führt dazu, dass die gespeicherten Daten weiterhin in der Datenbank bleiben.
MARKDOWN;
