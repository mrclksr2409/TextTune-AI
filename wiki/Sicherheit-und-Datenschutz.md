# Sicherheit und Datenschutz

## Zugriffsrechte

| Bereich | Erforderliche Berechtigung |
|---|---|
| Einstellungsseite, **Modelle aktualisieren** | `manage_options` (Administratoren) |
| Textoptimierung (Block-Editor, Classic Editor, `POST /optimize`) | `edit_posts` (ab Rolle *Mitarbeiter*) |
| Button **Bild analysieren**, Zeilenaktion in der Mediathek | Bearbeitungsrecht für den jeweiligen Anhang (`edit_post`) |
| Massenaktion in der Mediathek | `upload_files` |
| `POST /analyze-image` | `upload_files` **und** `edit_post` für den Anhang |

Schutz gegen fremd ausgelöste Anfragen (CSRF):

- Das Einstellungsformular nutzt die WordPress-Settings-API mit ihrem Nonce.
- **Modelle aktualisieren** ist ein Link mit eigenem Nonce (`texttune_refresh_models`).
- Die REST-Aufrufe aus Editor und Mediathek senden den REST-Nonce (`X-WP-Nonce`).

> Hinweis: Jeder Benutzer mit `edit_posts` kann über TextTune AI Anfragen auf Kosten deines
> API-Kontos auslösen. Behalte das bei Websites mit vielen Autoren im Blick.

## API-Schlüssel

| Aspekt | Umsetzung |
|---|---|
| **Speicherort** | Option `texttune_ai_settings`, Schlüssel `api_key` |
| **Verschlüsselung** | AES-256-CBC mit zufälligem IV je Speichervorgang; gespeichert wird `Base64(IV + Chiffretext)` |
| **Schlüsselableitung** | SHA-256 über `AUTH_KEY` und `SECURE_AUTH_KEY` aus der `wp-config.php` |
| **Im Formular** | Wird nie wieder angezeigt; das Passwortfeld bleibt leer, der Platzhalter zeigt nur Punkte |
| **Ohne OpenSSL** | Nur Base64-kodiert (praktisch Klartext). Die Einstellungsseite warnt dann in Rot. |
| **Modell-Zwischenspeicher** | Enthält nur die ersten 8 Zeichen eines MD5-Hashs des Schlüssels, nie den Schlüssel selbst |

Was das bedeutet:

- Wer **nur die Datenbank** hat (z. B. ein Datenbank-Backup), kann den Schlüssel nicht direkt lesen.
- Wer Datenbank **und** `wp-config.php` hat, kann ihn entschlüsseln.
- **Änderst du die Salts** `AUTH_KEY` oder `SECURE_AUTH_KEY`, lässt sich der gespeicherte Schlüssel
  nicht mehr entschlüsseln. Dann den API-Schlüssel einfach neu eingeben – siehe
  [Fehlerbehebung](Fehlerbehebung#kein-api-schlüssel-konfiguriert-obwohl-einer-gespeichert-ist).
- Sind beide Konstanten nicht definiert, verwendet das Plugin einen festen Ersatzwert – die
  Verschlüsselung schützt dann kaum. Eine normale WordPress-Installation hat beide Konstanten.

## Welche Daten verlassen den Server?

| Empfänger | Was | Wann |
|---|---|---|
| **Gewählter KI-Anbieter** (OpenAI oder Anthropic) | Der zu optimierende Inhalt – je nach Aktion der **gesamte Beitragsinhalt**, **ein Block** oder die **Markierung** – als HTML, dazu der Prompt des Inhaltstyps | Klick auf eine Optimierungs-Aktion |
| **Gewählter KI-Anbieter** | Das **Bild** (ggf. verkleinert, Base64-kodiert), der **Dateiname** und die Sprache im Prompt | Bildanalyse, zweimal pro übernommenem Bild |
| **Gewählter KI-Anbieter** | Nur der API-Schlüssel, zum Abruf der Modellliste | Aufruf der Einstellungsseite (höchstens alle 12 Stunden bzw. nach **Modelle aktualisieren**) |
| **Dein Medien-Speicher** (z. B. CDN/S3) | Abruf der Bilddatei über ihre URL | Nur wenn die Datei nicht lokal liegt |
| **GitHub** | Versionsabfrage | Update-Prüfung, siehe [Updates](Updates) |

- Es wird **nichts automatisch** gesendet – jede KI-Anfrage beginnt mit einem Klick im Admin-Bereich.
- **Keine Besucherdaten** deiner Website werden übertragen. Im Frontend bindet TextTune AI selbst
  nichts ein und setzt keine Cookies.
- Titel, Auszug, Autor oder andere Metadaten eines Beitrags werden **nicht** gesendet.

### Datenschutz-Hinweise

- Was du optimierst, landet beim KI-Anbieter. Enthält ein Text **personenbezogene Daten**, gelten die
  Bedingungen des Anbieters (Auftragsverarbeitung, Speicherdauer, Serverstandort). Prüfe diese,
  bevor du solche Texte optimierst.
- Bilder können Personen zeigen. Dasselbe gilt für die Bildanalyse.

## Was speichert TextTune AI?

- Keine Kopien der gesendeten Texte und keine KI-Antworten. Ergebnisse landen nur in deinem Editor
  bzw. – bei der Bildanalyse – in den Feldern des Anhangs.
- Ist `WP_DEBUG` aktiv und kann eine Bildanalyse-Antwort nicht gelesen werden, schreibt das Plugin die
  ersten 200 Zeichen der Antwort ins PHP-Fehlerprotokoll.
- Vollständige Liste der Datenbank-Einträge: [Datenbank und Deinstallation](Datenbank-und-Deinstallation).

## Ausgabe und Bereinigung

- Text, der an `/optimize` geht, und die Antwort der KI werden mit `wp_kses_post` bereinigt – also auf
  das HTML beschränkt, das auch in Beiträgen erlaubt ist.
- Bild-Metadaten: Alt-Text, Titel und Beschriftung werden als reiner Text gespeichert
  (`sanitize_text_field`), die Beschreibung mit `wp_kses_post`.
- Prompts werden als reiner Text gespeichert (`sanitize_textarea_field`).
