# REST-API

Namespace: **`/wp-json/texttune/v1/`**

| Methode | Route | Zweck | Berechtigung |
|---|---|---|---|
| POST | [`/optimize`](#post-optimize) | Text mit dem Prompt eines Inhaltstyps optimieren | `edit_posts` |
| POST | [`/analyze-image`](#post-analyze-image) | Bild analysieren, optional Metadaten speichern | `upload_files` **und** `edit_post` für den Anhang |

Beide Endpunkte werden vom Plugin selbst genutzt (Block-Editor, Classic Editor, Mediathek). Aus dem
Browser heraus authentifiziert man sich über den Login-Cookie plus Header `X-WP-Nonce`
(`wp_create_nonce('wp_rest')`), von außen z. B. über
[Anwendungspasswörter](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)
(**Benutzer → Profil → Anwendungspasswörter**).

Fehler kommen im üblichen WordPress-Format:

```json
{ "code": "texttune_no_api_key", "message": "Kein API-Schlüssel konfiguriert. …", "data": { "status": 400 } }
```

---

## POST /optimize

Optimiert einen Text mit dem gespeicherten Provider, Modell und dem Prompt des angegebenen
Inhaltstyps. **Speichert nichts** – das Ergebnis wird nur zurückgegeben.

### Parameter

| Parameter | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `content` | string | ja | Der Text bzw. das HTML. Wird mit `wp_kses_post` bereinigt (erlaubtes HTML wie in Beiträgen). |
| `post_type` | string | ja | Inhaltstyp, dessen Prompt verwendet wird, z. B. `post`. Wird mit `sanitize_key` bereinigt. Ohne gespeicherten Prompt für diesen Typ gilt der Standard-Prompt. |

### Ablauf

1. `content` leer → Fehler.
2. API-Schlüssel entschlüsseln; fehlt er → Fehler.
3. Prompt für `post_type` bestimmen.
4. Filter `texttune_ai_pre_optimize_content` und `texttune_ai_prompt` anwenden.
5. Anfrage an OpenAI oder Anthropic.
6. Antwort mit `wp_kses_post` bereinigen, Filter `texttune_ai_post_optimize_content` anwenden,
   Action `texttune_ai_optimized` auslösen.

Siehe [Hooks und Filter](Hooks-und-Filter).

### Antwort

```json
{ "success": true, "content": "<p>Optimierter Text …</p>" }
```

### Fehler

| Code | Status | Bedeutung |
|---|---|---|
| `texttune_forbidden` | 403 | Benutzer hat nicht `edit_posts` |
| `texttune_empty_content` | 400 | *„Der Inhalt darf nicht leer sein.“* |
| `texttune_no_api_key` | 400 | Kein API-Schlüssel gespeichert |
| `texttune_openai_request_failed` / `texttune_anthropic_request_failed` | 502 | Verbindung zum Anbieter fehlgeschlagen |
| `texttune_openai_api_error` / `texttune_anthropic_api_error` | 502 | Anbieter hat mit einem HTTP-Fehler geantwortet; die Meldung enthält Status und Text des Anbieters |
| `texttune_openai_invalid_response` / `texttune_anthropic_invalid_response` | 502 | Antwort ohne Text |

### Beispiel

```bash
curl -X POST https://example.com/wp-json/texttune/v1/optimize \
  -u 'benutzer:xxxx xxxx xxxx xxxx xxxx xxxx' \
  -H 'Content-Type: application/json' \
  -d '{"content":"<p>Das ist ein test text mit fehlern.</p>","post_type":"post"}'
```

---

## POST /analyze-image

Analysiert ein Bild der Mediathek und liefert Vorschläge für Alt-Text, Titel, Beschriftung und
Beschreibung. Mit `save: true` werden sie direkt am Anhang gespeichert.

### Parameter

| Parameter | Typ | Pflicht | Standard | Beschreibung |
|---|---|---|---|---|
| `attachment_id` | integer | ja | – | ID des Anhangs |
| `fields` | array | nein | alle vier | Teilmenge von `alt`, `title`, `caption`, `description`. Unbekannte Werte werden ignoriert; bleibt nichts übrig, gelten alle vier. |
| `save` | boolean | nein | `false` | `true` speichert die Vorschläge am Anhang (siehe `overwrite_map`) |
| `overwrite_map` | object | nein | – | Pro Feld `"overwrite"` oder `"keep"`. Nur bei `save: true` relevant. |
| `locale` | string | nein | Sprache des Benutzers | Sprachcode für die Platzhalter `{locale}` und `{language}` im Prompt, z. B. `en_US` |

**Speicherlogik je Feld** (nur bei `save: true` und nur, wenn die KI einen nicht-leeren Vorschlag
geliefert hat):

| `overwrite_map[feld]` | Wird gespeichert? |
|---|---|
| `"overwrite"` | ja, immer |
| `"keep"` | nein |
| fehlt | nur, wenn das Feld am Anhang bisher leer ist |

> Jeder Aufruf löst eine **neue** Analyse beim Anbieter aus – auch einer mit `save: true`. Die
> Mediathek-Oberfläche ruft den Endpunkt zweimal auf (Vorschau, dann Speichern).

### Antwort

```json
{
  "success": true,
  "attachment_id": 123,
  "generated": { "alt": "…", "title": "…", "caption": "…", "description": "…" },
  "current":   { "alt": "", "title": "IMG_1234", "caption": "", "description": "" },
  "saved": true,
  "applied": ["alt", "caption"]
}
```

| Feld | Bedeutung |
|---|---|
| `generated` | Vorschläge; nicht angefragte Felder sind leer |
| `current` | Werte am Anhang **vor** dem Speichern |
| `saved` | `true`, wenn mindestens ein Feld geschrieben wurde |
| `applied` | Liste der geschriebenen Felder |

### Fehler

| Code | Status | Bedeutung |
|---|---|---|
| `texttune_forbidden` | 403 | Keine Berechtigung `upload_files` oder keine Bearbeitungsrechte für den Anhang |
| `texttune_not_an_image` | 400 / 404 | Kein Anhang angegeben (400), Anhang nicht gefunden (404) oder kein Bild (400) |
| `texttune_no_api_key` | 400 | Kein API-Schlüssel gespeichert |
| `texttune_unsupported_mime` | 415 | Format nicht JPEG, PNG, GIF oder WebP |
| `texttune_image_fetch_failed` | 500 | Bilddatei weder lokal noch über die URL lesbar |
| `texttune_vision_image_too_large` | 413 | Auch nach Verkleinerung größer als 5 MB |
| `texttune_vision_rate_limited` | 429 | Rate-Limit des Anbieters erreicht |
| `texttune_openai_*` / `texttune_anthropic_*` | 502 | Verbindungs- oder API-Fehler des Anbieters (wie bei `/optimize`) |
| `texttune_vision_parse_failed` | 502 | Leere Antwort oder kein lesbares JSON |

### Beispiel: nur Alt-Text erzeugen und leere Felder befüllen

```bash
curl -X POST https://example.com/wp-json/texttune/v1/analyze-image \
  -u 'benutzer:xxxx xxxx xxxx xxxx xxxx xxxx' \
  -H 'Content-Type: application/json' \
  -d '{"attachment_id":123,"fields":["alt"],"save":true}'
```
