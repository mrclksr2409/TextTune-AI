# Hooks und Filter

Eigener Code gehört in ein kleines eigenes Plugin oder die `functions.php` des (Child-)Themes –
nicht in die TextTune-AI-Dateien, sonst ist er beim nächsten Update weg.

## Textoptimierung

Alle Hooks laufen im Endpunkt [`POST /texttune/v1/optimize`](REST-API#post-optimize), in dieser
Reihenfolge:

| Hook | Typ | Parameter | Zweck |
|---|---|---|---|
| `texttune_ai_pre_optimize_content` | Filter | `string $content`, `string $post_type` | Inhalt vor dem Senden an die KI ändern |
| `texttune_ai_prompt` | Filter | `string $prompt`, `string $post_type` | Prompt vor dem Senden ändern |
| `texttune_ai_post_optimize_content` | Filter | `string $result`, `string $content`, `string $post_type` | KI-Antwort vor der Rückgabe an den Editor ändern (`$result` ist bereits mit `wp_kses_post` bereinigt) |
| `texttune_ai_optimized` | Action | `string $result`, `string $content`, `string $post_type` | Nach erfolgreicher Optimierung, z. B. für Logging |

`$content` ist in den beiden letzten Hooks der Inhalt **nach** dem Filter
`texttune_ai_pre_optimize_content`.

### Beispiel: Prompt für alle Inhaltstypen ergänzen

```php
add_filter( 'texttune_ai_prompt', function ( $prompt, $post_type ) {
    return $prompt . ' Verwende die Du-Anrede.';
}, 10, 2 );
```

### Beispiel: Optimierungen protokollieren

```php
add_action( 'texttune_ai_optimized', function ( $result, $content, $post_type ) {
    error_log( sprintf(
        'TextTune AI: %s optimiert von Benutzer %d (%d → %d Zeichen)',
        $post_type,
        get_current_user_id(),
        strlen( $content ),
        strlen( $result )
    ) );
}, 10, 3 );
```

## Bildanalyse

Alle Hooks laufen im Endpunkt [`POST /texttune/v1/analyze-image`](REST-API#post-analyze-image):

| Hook | Typ | Parameter | Zweck |
|---|---|---|---|
| `texttune_ai_vision_max_edge` | Filter | `int $max_edge` (Standard 1568) | Maximale Bildkante – greift nur, wenn in den Einstellungen kein Wert gespeichert ist |
| `texttune_ai_vision_max_bytes` | Filter | `int $max_bytes` (Standard 5242880 = 5 MB) | Größenlimit des gesendeten Bildes |
| `texttune_ai_pre_analyze_image` | Filter | `array $payload` (`mime`, `base64`, `filename`), `int $attachment_id` | Bilddaten vor dem Senden ändern |
| `texttune_ai_image_prompt` | Filter | `string $prompt`, `int $attachment_id`, `array $fields` | Fertigen Prompt (Platzhalter bereits ersetzt) ändern |
| `texttune_ai_post_analyze_image` | Filter | `array $parsed` (`alt`, `title`, `caption`, `description`), `int $attachment_id`, `string $raw` | Gelesene Vorschläge ändern; `$raw` ist die Rohantwort der KI |
| `texttune_ai_image_field_value` | Filter | `string $value`, `string $field`, `int $attachment_id` | Wert eines Feldes direkt vor dem Speichern ändern (nur bei `save: true`) |
| `texttune_ai_image_analyzed` | Action | `int $attachment_id`, `array $generated`, `bool $saved` | Nach jeder Analyse, gespeichert oder nicht |

Hinweise:

- Ist ein Bild größer als `texttune_ai_vision_max_bytes`, versucht das Plugin noch eine JPEG-Kodierung
  mit Qualität 70, bevor es mit *„Das Bild ist auch nach Verkleinerung zu groß für die API.“*
  abbricht.
- Ein Wert unter 256 aus `texttune_ai_vision_max_edge` wird durch 1568 ersetzt.
- `texttune_ai_image_field_value` läuft nur für Felder, die tatsächlich geschrieben werden. Danach
  bereinigt das Plugin den Wert noch einmal (`sanitize_text_field` bzw. `wp_kses_post`).

### Beispiel: Alt-Text mit Firmennamen ergänzen

```php
add_filter( 'texttune_ai_image_field_value', function ( $value, $field, $attachment_id ) {
    if ( 'alt' === $field ) {
        $value .= ' – Beispiel GmbH';
    }
    return $value;
}, 10, 3 );
```

### Beispiel: Bildanalyse immer auf Englisch

```php
add_filter( 'texttune_ai_image_prompt', function ( $prompt, $attachment_id, $fields ) {
    return $prompt . "\n\nWrite all values in English.";
}, 10, 3 );
```

## WordPress-Hooks, die TextTune AI selbst nutzt

Zur Orientierung, z. B. wenn du eine Funktion gezielt entfernen willst:

| Hook | Funktion |
|---|---|
| `plugins_loaded` | `texttune_ai_init` – Einstellungen, REST-API, Mediathek-Integration, Update-Prüfung |
| `enqueue_block_editor_assets` | `texttune_ai_enqueue_editor_assets` – Block-Editor-Skript |
| `admin_init` | `texttune_ai_classic_editor_init` – TinyMCE-Button (`mce_external_plugins`, `mce_buttons_2`) |
| `admin_enqueue_scripts` | `texttune_ai_classic_editor_enqueue` – Daten für den Classic Editor |
| `plugin_row_meta` | `texttune_ai_row_meta` – Link **Wiki** in der Plugin-Liste |
| `attachment_fields_to_edit`, `media_row_actions`, `bulk_actions-upload`, `handle_bulk_actions-upload`, `wp_enqueue_media` | Mediathek-Integration (Klasse `TextTune_Media_Integration`) |

Im Block-Editor registriert das Plugin außerdem den JavaScript-Filter `editor.BlockEdit` mit dem
Namespace `texttune-ai/block-optimize` (Zauberstab in der Block-Toolbar) und das Editor-Plugin
`texttune-ai` (Eintrag im Drei-Punkte-Menü).
