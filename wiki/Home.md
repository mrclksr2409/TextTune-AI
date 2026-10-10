# TextTune AI Wiki

**TextTune AI** ist ein WordPress-Plugin für KI-gestützte Textoptimierung direkt im
**Block-Editor (Gutenberg)** und im **Classic Editor**. Zusätzlich analysiert es Bilder in der
**Mediathek** und schlägt Alt-Text, Titel, Beschriftung und Beschreibung vor. Als KI-Anbieter
stehen **OpenAI** und **Anthropic (Claude)** zur Wahl.

> Aktuelle Version: **1.2.1** · Voraussetzungen: WordPress 6.0+, PHP 7.4+, ein API-Schlüssel von
> OpenAI oder Anthropic · Autor: [Marcel Kaiser](https://github.com/mrclksr2409)

---

## So funktioniert TextTune AI in einem Satz

```
Text im Editor ──► Klick auf den Zauberstab ──► Text + Prompt deines Inhaltstyps an die KI
               ──► optimierter Text ersetzt den Inhalt im Editor ──► du prüfst und speicherst

Bild in der Mediathek ──► „Bild analysieren“ ──► KI erzeugt Metadaten
                      ──► du entscheidest pro Feld ──► Übernehmen
```

## Einstieg

| Seite | Wofür |
|---|---|
| [Installation](Installation) | Plugin hochladen, aktivieren, Voraussetzungen |
| [Schnellstart](Schnellstart) | In 5 Minuten zum ersten optimierten Text |

## Bedienung

| Seite | Wofür |
|---|---|
| [Block-Editor](Block-Editor) | Ganzen Beitrag oder einzelnen Block optimieren |
| [Classic Editor](Classic-Editor) | Gesamten Text oder Auswahl optimieren |
| [Bildanalyse und Mediathek](Bildanalyse-und-Mediathek) | Alt-Text & Co. per KI, einzeln oder als Massenaktion |
| [Einstellungen](Einstellungen) | Alle drei Tabs im Detail |
| [KI-Anbieter und Modelle](KI-Anbieter-und-Modelle) | OpenAI/Anthropic, wie die Modellliste entsteht |
| [Prompts](Prompts) | Prompts pro Inhaltstyp und die Bildanalyse-Vorlage |

## Referenz

| Seite | Wofür |
|---|---|
| [Sicherheit und Datenschutz](Sicherheit-und-Datenschutz) | Verschlüsselung, Rechte, welche Daten wohin gehen |
| [REST-API](REST-API) | Beide Endpunkte mit Parametern und Antworten |
| [Hooks und Filter](Hooks-und-Filter) | Erweitern ohne Code-Änderung |
| [Datenbank und Deinstallation](Datenbank-und-Deinstallation) | Optionen, Transients, was beim Löschen passiert |
| [Updates](Updates) | Updates vom `main`-Branch, Beta-Kanal |
| [Fehlerbehebung](Fehlerbehebung) | Typische Fehlermeldungen und Lösungen |
| [FAQ](FAQ) | Häufige Fragen |
| [Entwicklung](Entwicklung) | Architektur, Dateien, Mitarbeit |
| [Changelog](Changelog) | Änderungen je Version |

## Funktionen im Überblick

- **Ganzen Beitrag optimieren** – im Block-Editor über das Drei-Punkte-Menü, im Classic Editor über den Toolbar-Button
- **Einzelnen Block optimieren** – Zauberstab in der Block-Toolbar von Absatz, Überschrift, Liste, Zitat, Pullquote, Vers und Vorformatiert
- **Auswahl optimieren** – im Classic Editor nur den markierten Text
- **Prompts pro Inhaltstyp** – eigener Prompt für Beiträge, Seiten und jeden öffentlichen Custom Post Type
- **Bildanalyse (Vision)** – Alt-Text, Titel, Beschriftung und Beschreibung aus dem Bildinhalt, mit Vergleichsdialog „Aktuell ↔ Vorschlag“
- **Massenaktion in der Mediathek** – viele Bilder nacheinander analysieren, mit wählbarer Strategie für bereits befüllte Felder
- **OpenAI & Anthropic** – Modellliste wird direkt beim Anbieter abgerufen und 12 Stunden zwischengespeichert
- **Verschlüsselter API-Schlüssel** – AES-256-CBC, Schlüssel abgeleitet aus den WordPress-Salts
- **Automatische Updates** direkt vom GitHub-Branch `main`, optional vom Branch `beta`

Der Link **Wiki** in der Plugin-Zeile unter **Plugins → Installierte Plugins** führt seit Version
1.2.1 direkt hierher.
