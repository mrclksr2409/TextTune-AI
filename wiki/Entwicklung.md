# Entwicklung

## Architektur

```
Block-Editor (texttune-editor.js) ─┐
                                   ├─► POST /texttune/v1/optimize ──► TextTune_REST_API
Classic Editor (TinyMCE-Plugin)  ──┘                                     │
                                                                         ├─► TextTune_OpenAI::optimize()
                                                                         └─► TextTune_Anthropic::optimize()

Mediathek (texttune-media.js) ──► POST /texttune/v1/analyze-image ──► TextTune_REST_API_Vision
                                                                         ├─► TextTune_Image_Analyzer (laden, verkleinern, Base64, JSON lesen)
                                                                         └─► TextTune_OpenAI / TextTune_Anthropic ::analyze_image()

Einstellungsseite ──► TextTune_Settings ──► TextTune_Models (Modellliste, Transients)
                                        └─► TextTune_Encryption (API-Schlüssel)
```

## Verzeichnisstruktur

```
TextTune-AI/
├── texttune-ai.php                           Bootstrap: Header, Guards, Konstanten, Editor-Einbindung, Updates
├── uninstall.php                             Entfernt die Optionen beim Löschen
├── includes/
│   ├── class-texttune-activator.php          Aktivierung, Standardwerte, Standard-Prompts
│   ├── class-texttune-encryption.php         AES-256-CBC für den API-Schlüssel
│   ├── class-texttune-models.php             Modellliste von OpenAI/Anthropic, Zwischenspeicher
│   ├── class-texttune-settings.php           Einstellungsseite (Settings-API), Validierung
│   ├── class-texttune-openai.php             OpenAI-Client (Text + Bild)
│   ├── class-texttune-anthropic.php          Anthropic-Client (Text + Bild)
│   ├── class-texttune-rest-api.php           POST /texttune/v1/optimize
│   ├── class-texttune-image-analyzer.php     Bild laden/verkleinern, Antwort parsen, Sprachnamen
│   ├── class-texttune-rest-api-vision.php    POST /texttune/v1/analyze-image
│   └── class-texttune-media-integration.php  Button, Zeilen- und Massenaktion in der Mediathek
├── assets/
│   ├── js/texttune-editor.js                 Block-Editor: Drei-Punkte-Menü + Block-Toolbar
│   ├── js/texttune-classic-editor.js         TinyMCE-Plugin „texttune_ai“
│   ├── js/texttune-admin.js                  Einstellungsseite: Provider/Modell-Umschaltung, aktiver Tab
│   ├── js/texttune-media.js                  Mediathek: Dialoge, Massenablauf
│   └── css/texttune-media.css                Styles für die Mediathek-Dialoge
├── lib/
│   ├── plugin-update-checker/                Plugin Update Checker 5.7 (gebündelt)
│   └── wp-backend-ui/                        Admin-Designsystem WP-Backend UI 1.0.2 (gebündelt, nicht ändern)
└── wiki/                                     Quelle dieses Wikis
```

## Konventionen

- **PHP 7.4+**, klassische Klassen mit Präfix `TextTune_`, Funktionen mit Präfix `texttune_ai_`,
  Konstanten `TEXTTUNE_VERSION`, `TEXTTUNE_PLUGIN_DIR`, `TEXTTUNE_PLUGIN_URL`,
  `TEXTTUNE_PLUGIN_BASENAME`.
- **Doppel-Lade-Schutz:** Die Hauptdatei bricht ab, wenn `TEXTTUNE_VERSION` schon definiert ist;
  jede Klassendatei prüft `class_exists()`, jede Funktion `function_exists()`. Neue Dateien und
  Funktionen bitte genauso absichern.
- **Fehler sichtbar machen statt Fatal:** Includes und Initialisierung laufen in `try/catch`; Fehler
  landen im Fehlerprotokoll (`TEXTTUNE-AI …`) und als Admin-Hinweis.
- **Einstellungen:** Neuer Schlüssel immer in `TextTune_Activator::activate()` (Standardwert) **und**
  in `TextTune_Settings::sanitize_settings()`; beim Lesen stets mit Fallback, weil bestehende
  Installationen neue Schlüssel nicht haben.
- **Standard-Prompts** stehen als Konstanten in `TextTune_Activator` und zusätzlich in
  `TextTune_REST_API::DEFAULT_PROMPT` bzw. `TextTune_REST_API_Vision::DEFAULT_PROMPT` – bei Änderungen
  alle Stellen anpassen.
- Oberfläche auf Deutsch (Textdomain `texttune-ai`), Code und Kommentare auf Englisch.
- Die JavaScript-Dateien sind **ohne Build-Schritt** geschrieben (ES5, `wp.element.createElement`).
- JS/CSS-Änderungen immer mit Versionssprung ausliefern (Cache-Busting über `TEXTTUNE_VERSION`).
- `lib/` nicht von Hand ändern, sondern als Ganzes aktualisieren.

## Lokal testen

- PHP-Syntax: `find includes -name '*.php' -exec php -l {} \; && php -l texttune-ai.php`
- JavaScript-Syntax: `for f in assets/js/*.js; do node --check "$f"; done`
- REST-Endpunkte direkt aufrufen: siehe [REST-API](REST-API)
- Für Fehlersuche `WP_DEBUG` und `WP_DEBUG_LOG` einschalten

## Release

1. Version in `texttune-ai.php` erhöhen – im Header (`Version:`) **und** in `TEXTTUNE_VERSION`.
2. Changelog im README ergänzen (und [Changelog](Changelog) im Wiki).
3. Auf `beta` pushen – Installationen mit aktivierten Beta-Updates erhalten das Update.
4. `beta` nach `main` übernehmen – alle Installationen erhalten das Update automatisch
   (ein GitHub-Release ist dafür nicht nötig, Releases und Tags werden ignoriert).

## Dieses Wiki bearbeiten

Die Wiki-Seiten liegen im Ordner [`wiki/`](https://github.com/mrclksr2409/TextTune-AI/tree/main/wiki)
des Repositorys. Eine GitHub Action (`.github/workflows/wiki-sync.yml`) spiegelt sie bei jedem Push
auf `main` ins [Wiki](https://github.com/mrclksr2409/TextTune-AI/wiki). **Änderungen direkt im Wiki
werden dabei überschrieben** – bitte immer im Repository bearbeiten.

- Dateiname = Seitenname (`Bildanalyse-und-Mediathek.md` → Seite *Bildanalyse und Mediathek*)
- Links ohne `.md`: `[Text](Seitenname)`
- `_Sidebar.md` und `_Footer.md` sind Navigation und Fußzeile
- Einmalig nötig: Wiki in den Repository-Einstellungen aktivieren und im Web eine erste Seite
  anlegen – erst dann existiert das Wiki-Repository, in das die Action schreibt.
