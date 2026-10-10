# Installation

## Voraussetzungen

| Komponente | Mindestens | Hinweis |
|---|---|---|
| WordPress | 6.0 | Wird bei der Aktivierung geprüft |
| PHP | 7.4 | Wird beim Laden und bei der Aktivierung geprüft |
| PHP-Erweiterung OpenSSL | – | Empfohlen. Ohne OpenSSL wird der API-Schlüssel nur Base64-kodiert gespeichert (siehe [Sicherheit und Datenschutz](Sicherheit-und-Datenschutz)) |
| HTTPS-Verbindungen nach außen | – | Der Server muss `api.openai.com` bzw. `api.anthropic.com` erreichen, für Updates außerdem GitHub |
| KI-API-Schlüssel | – | OpenAI **oder** Anthropic, kostenpflichtig beim Anbieter |
| Bildbearbeitung (GD oder Imagick) | – | Nur für die [Bildanalyse](Bildanalyse-und-Mediathek): Große Bilder werden vor dem Versand über den WordPress-Bildeditor verkleinert |

Mitgelieferte Bibliotheken (im Ordner `lib/`, keine separate Installation nötig):

- **[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) 5.7** – Updates direkt aus GitHub
- **WP-Backend UI 1.0.2** – gemeinsames Admin-Designsystem (Seitenkopf, Tabs, Formulare, Buttons).
  Liefern mehrere Plugins eine Kopie mit, wird automatisch die neueste geladen.

## Plugin installieren

### Variante A: ZIP über den WordPress-Admin

1. Auf [GitHub](https://github.com/mrclksr2409/TextTune-AI) über **Code → Download ZIP** das
   Repository herunterladen.
2. In WordPress **Plugins → Installieren → Plugin hochladen** wählen, die ZIP-Datei auswählen,
   **Jetzt installieren**, danach **Plugin aktivieren**.

### Variante B: Manuell per Git, FTP oder SSH

```bash
cd wp-content/plugins
git clone https://github.com/mrclksr2409/TextTune-AI.git
```

Alternativ den entpackten Ordner per FTP nach `wp-content/plugins/TextTune-AI/` kopieren. Danach
unter **Plugins → Installierte Plugins** bei **TextTune AI** auf **Aktivieren** klicken.

> **Keine zweite Kopie installieren.** Eine GitHub-ZIP entpackt sich in einen Ordner wie
> `TextTune-AI-main`. Liegt daneben noch eine ältere Installation in einem anderen Ordner, sind zwei
> Kopien aktiv. TextTune AI erkennt das, lädt die zweite Kopie nicht und zeigt den Hinweis
> *„Es sind zwei Kopien des Plugins installiert …“*. Dann die alte Kopie deaktivieren und löschen –
> siehe [Fehlerbehebung](Fehlerbehebung#es-sind-zwei-kopien-des-plugins-installiert).
> Für spätere Versionen brauchst du keine ZIP mehr: Updates kommen automatisch, siehe [Updates](Updates).

## Was beim Aktivieren passiert

1. **PHP- und WordPress-Version werden geprüft.** Ist PHP älter als 7.4 oder WordPress älter als 6.0,
   wird das Plugin wieder deaktiviert und eine Fehlermeldung angezeigt.
2. **Standard-Einstellungen werden angelegt**, sofern die Option `texttune_ai_settings` noch nicht
   existiert:

   | Schlüssel | Standardwert |
   |---|---|
   | Provider | `openai` |
   | API-Schlüssel | leer |
   | Modell | `gpt-4o` |
   | Prompts | Standard-Prompt für `post` und `page` |
   | Bildanalyse | Standard-Vorlage, alle vier Felder aktiv, kein Modell-Override, max. Bildkante 1568 px |

3. **Upgrade älterer Installationen:** Existieren die Einstellungen bereits, aber ohne
   Bildanalyse-Teil, werden die Bildanalyse-Standardwerte ergänzt. Alles andere bleibt unverändert.

Tritt bei der Aktivierung ein Fehler auf, zeigt WordPress *„TextTune AI Aktivierungsfehler: …“*;
Details stehen dann in `wp-content/debug.log` (mit `WP_DEBUG_LOG`).

## Nach der Aktivierung

- Im Menü **Einstellungen** erscheint der Eintrag **TextTune AI** (erfordert `manage_options`).
- Solange kein API-Schlüssel gespeichert ist, zeigt WordPress im gesamten Admin-Bereich den Hinweis
  *„TextTune AI: Bitte konfiguriere deinen API-Schlüssel in den Einstellungen.“*
- In der Plugin-Zeile unter **Plugins** gibt es einen Link **Wiki** auf diese Dokumentation.

## Deaktivieren und Deinstallieren

| Aktion | Folge |
|---|---|
| **Deaktivieren** | Die Buttons in Editor und Mediathek verschwinden. Einstellungen und API-Schlüssel bleiben erhalten. |
| **Löschen** | Entfernt die Optionen `texttune_ai_settings` und `texttune_ai_last_error`. Deine Beiträge und die bereits gespeicherten Bild-Metadaten bleiben unverändert. Details: [Datenbank und Deinstallation](Datenbank-und-Deinstallation). |

Weiter mit dem **[Schnellstart](Schnellstart)**.
