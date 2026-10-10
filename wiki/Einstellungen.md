# Einstellungen

**Einstellungen → TextTune AI** (`wp-admin/options-general.php?page=texttune-ai`) ist in drei Tabs
gegliedert. Die Seite erfordert die Berechtigung `manage_options` (Administratoren).

Alle Tabs liegen in **einem** Formular: Ein Klick auf **Einstellungen speichern** speichert alle
drei Tabs gleichzeitig. Danach kehrt die Seite zum zuletzt geöffneten Tab zurück. Einen Tab kannst
du auch direkt verlinken, z. B. `…&tab=vision`.

| Tab | Inhalt |
|---|---|
| [Einstellungen](#tab-einstellungen) | Provider, API-Schlüssel, Modell, Beta-Updates |
| [Prompts](#tab-prompts) | Ein Prompt pro öffentlichem Inhaltstyp |
| [Bilderkennung](#tab-bilderkennung) | Prompt-Vorlage, Felder, Modell-Override und Bildgröße für die Mediathek |

Alle Werte liegen in der Option `texttune_ai_settings` (siehe
[Datenbank und Deinstallation](Datenbank-und-Deinstallation)).

---

## Tab: Einstellungen

### Abschnitt „KI-Provider“

| Einstellung | Beschreibung | Standard | Validierung beim Speichern |
|---|---|---|---|
| **Provider** | *OpenAI* oder *Anthropic*. Gilt für Textoptimierung und Bildanalyse. | OpenAI | Nur `openai` oder `anthropic`, sonst `openai` |
| **API-Schlüssel** | Passwortfeld, Button **Anzeigen**/**Verbergen** blendet die Eingabe ein. Ist ein Schlüssel gespeichert, zeigt das Feld nur Punkte als Platzhalter. **Leer lassen = gespeicherten Schlüssel behalten.** | leer | Wird vor dem Speichern verschlüsselt (siehe [Sicherheit und Datenschutz](Sicherheit-und-Datenschutz)) |
| **Modell** | Dropdown mit den Modellen des gewählten Providers. Button **Modelle aktualisieren** lädt die Liste sofort neu. | `gpt-4o` | Muss in der aktuellen Modellliste, der eingebauten Standardliste oder unter den bisher gespeicherten Modellen stehen; sonst wird das erste Modell der Liste gesetzt |

Es wird **nur ein** API-Schlüssel gespeichert. Wechselst du den Provider, trage auch den passenden
Schlüssel des neuen Anbieters ein.

Beim Umschalten des Providers blendet die Seite sofort das Modell-Dropdown des anderen Anbieters ein
(ohne Neuladen). Wie die Liste entsteht und was **Modelle aktualisieren** genau tut:
[KI-Anbieter und Modelle](KI-Anbieter-und-Modelle).

Konnte die Liste nicht vom Anbieter geladen werden, steht unter dem Dropdown in Rot
*„Dynamische Modell-Liste konnte nicht geladen werden – Standardliste wird angezeigt. (…)“* mit der
Fehlermeldung des Anbieters.

Fehlt OpenSSL auf dem Server, erscheint unter dem Schlüsselfeld die Warnung
*„OpenSSL ist nicht verfügbar. Der API-Schlüssel wird nur Base64-kodiert gespeichert.“*

### Abschnitt „Updates“

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Beta-Updates** | *Beta-Versionen installieren (Branch „beta“ statt „main“)*. Beim Umschalten wird der zwischengespeicherte Update-Status verworfen, damit die nächste Prüfung sofort den anderen Branch liest. | aus |

Mehr dazu unter [Updates](Updates).

---

## Tab: Prompts

Für **jeden öffentlichen Inhaltstyp** (außer Medien/`attachment`) gibt es ein Textfeld
*Prompt für „Beiträge“*, *Prompt für „Seiten“* usw. – auch für öffentliche Custom Post Types wie
WooCommerce-Produkte.

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Prompt für „…“** | Anweisung an die KI für diesen Inhaltstyp | Standard-Prompt (siehe [Prompts](Prompts)) |

- Gespeichert wird als einfacher Text (`sanitize_textarea_field`): HTML-Tags im Prompt werden
  entfernt, Zeilenumbrüche bleiben erhalten.
- Ein **leer** gespeichertes Feld bedeutet: Es gilt der Standard-Prompt.
- Für nicht-öffentliche Inhaltstypen gibt es kein Feld; sie verwenden immer den Standard-Prompt.

---

## Tab: Bilderkennung

Abschnitt **„Bilderkennung für die Mediathek“**. Wie die Funktion in der Mediathek aussieht:
[Bildanalyse und Mediathek](Bildanalyse-und-Mediathek).

| Einstellung | Beschreibung | Standard | Validierung beim Speichern |
|---|---|---|---|
| **Prompt-Vorlage** | Anweisung für die Bildanalyse mit den Platzhaltern `{language}`, `{locale}`, `{filename}`, `{fields}`. Die Antwort muss ein reines JSON-Objekt mit `alt`, `title`, `caption`, `description` sein. | Standard-Vorlage (siehe [Prompts](Prompts#bildanalyse-prompt)) | Als Text gespeichert; ein leeres Feld behält die bisherige Vorlage |
| **Aktivierte Felder** | Checkboxen *Alt-Text*, *Titel*, *Beschriftung*, *Beschreibung*. Nur ausgewählte Felder werden vorgeschlagen und gespeichert. | alle vier | Nur diese vier Werte; ist keines angehakt, gelten in der Praxis alle vier |
| **Modell-Override** | Eigenes Modell nur für die Bildanalyse. *„— Gleiches Modell wie Textoptimierung —“* übernimmt das Textmodell. Die Auswahl entspricht der Modellliste des aktiven Providers. | leer (= Textmodell) | Muss ein gültiges Modell sein, sonst wird leer gespeichert |
| **Max. Bildkante (px)** | Längere Bildkante, auf die das Bild vor dem Versand verkleinert wird. Das Original bleibt unverändert. | 1568 | Ganzzahl, begrenzt auf **512–4096** |

> **Tipp:** Ist dein Textmodell ein reines Textmodell, stelle hier ein bildfähiges Modell ein.

---

## Hinweise im Admin-Bereich

| Hinweis | Wann |
|---|---|
| *„TextTune AI: Bitte konfiguriere deinen API-Schlüssel in den Einstellungen.“* (gelb) | Auf allen Admin-Seiten, solange kein Schlüssel gespeichert ist |
| *„TextTune AI: Modell-Liste wurde aktualisiert.“* (grün) | Nach Klick auf **Modelle aktualisieren** |
