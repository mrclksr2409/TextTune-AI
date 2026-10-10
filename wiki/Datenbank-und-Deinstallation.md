# Datenbank und Deinstallation

TextTune AI legt **keine eigenen Tabellen** an. Es nutzt ausschließlich die WordPress-Optionen,
Transients und – bei der Bildanalyse – die normalen Felder der Anhänge.

## Optionen (`wp_options`)

| Option | Inhalt |
|---|---|
| `texttune_ai_settings` | Alle Einstellungen (Struktur unten) |
| `texttune_ai_last_error` | Letzter Ladefehler des Plugins (Meldung, Datei, Zeile). Wird nur geschrieben, wenn eine Plugin-Datei nicht geladen werden konnte. |
| `external_updates-texttune-ai` | Zwischengespeicherter Update-Status des Plugin Update Checkers (bei Multisite netzwerkweit) |

### Struktur von `texttune_ai_settings`

```php
array(
    'provider'     => 'openai',                 // 'openai' | 'anthropic'
    'api_key'      => '…',                      // verschlüsselt, Base64(IV + Chiffretext)
    'model'        => 'gpt-4o',                 // Modell-ID für die Textoptimierung
    'prompts'      => array(                    // ein Prompt pro Inhaltstyp
        'post' => 'Optimiere den folgenden Text. …',
        'page' => 'Optimiere den folgenden Text. …',
    ),
    'vision'       => array(
        'prompt'         => 'Du bist ein Bildanalyse-Assistent …',
        'enabled_fields' => array( 'alt', 'title', 'caption', 'description' ),
        'model'          => '',                 // leer = Modell der Textoptimierung
        'max_edge'       => 1568,               // 512–4096
    ),
    'beta_updates' => false,                    // erst nach dem ersten Speichern vorhanden
)
```

Was die einzelnen Werte bedeuten und wie sie geprüft werden: [Einstellungen](Einstellungen).

## Transients

| Transient | Inhalt | Lebensdauer |
|---|---|---|
| `texttune_models_openai`, `texttune_models_anthropic` | Modellliste, kurzer Schlüssel-Hash, Abrufzeit | 12 Stunden |
| `texttune_models_openai_failed`, `texttune_models_anthropic_failed` | Fehlermeldung des letzten fehlgeschlagenen Abrufs | 15 Minuten |

Siehe [KI-Anbieter und Modelle](KI-Anbieter-und-Modelle#zwischenspeicher).

## Felder an Anhängen (Bildanalyse)

TextTune AI schreibt nur in WordPress-eigene Felder, es gibt keine eigenen Post-Meta-Schlüssel:

| Feld | Speicherort |
|---|---|
| Alt-Text | Post-Meta `_wp_attachment_image_alt` |
| Titel | `post_title` des Anhangs |
| Beschriftung | `post_excerpt` des Anhangs |
| Beschreibung | `post_content` des Anhangs |

## Deinstallation

Beim **Löschen** des Plugins über **Plugins → Installierte Plugins** führt WordPress `uninstall.php`
aus. Entfernt werden:

- `texttune_ai_settings` (inklusive verschlüsseltem API-Schlüssel)
- `texttune_ai_last_error`

**Nicht** entfernt werden:

- die Modell-Transients (`texttune_models_*`) – sie laufen nach spätestens 12 Stunden von selbst ab,
- die Update-Option `external_updates-texttune-ai`,
- alle Inhalte: optimierte Beiträge und die per Bildanalyse gespeicherten Metadaten bleiben, wie sie
  sind.

**Deaktivieren** löscht nichts.
