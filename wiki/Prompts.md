# Prompts

TextTune AI kennt zwei Arten von Prompts:

| Prompt | Wo | Wofür |
|---|---|---|
| **Prompt pro Inhaltstyp** | **Einstellungen → TextTune AI → Prompts** | Textoptimierung im Block- und Classic Editor |
| **Prompt-Vorlage Bildanalyse** | **Einstellungen → TextTune AI → Bilderkennung** | Metadaten für Bilder der Mediathek |

Es gibt keine festen „Modi“ wie *kürzen* oder *umformulieren* – was die KI mit dem Text macht,
bestimmt allein der Prompt des jeweiligen Inhaltstyps.

---

## Prompt pro Inhaltstyp

### Standard-Prompt

```
Optimiere den folgenden Text. Verbessere Grammatik, Stil und Lesbarkeit. Behalte den Inhalt,
die Bedeutung und die HTML-Formatierung bei. Gib nur den optimierten Text zurück, ohne
zusätzliche Erklärungen.
```

Er wird bei der Aktivierung für `post` und `page` eingetragen und gilt immer dann, wenn für einen
Inhaltstyp kein oder ein leerer Prompt gespeichert ist.

### Wie der Prompt verwendet wird

1. Der Editor sendet den Inhalt und den **Inhaltstyp** des geöffneten Beitrags (z. B. `post`).
2. TextTune AI nimmt den Prompt dieses Inhaltstyps – oder den Standard-Prompt.
3. Der Prompt geht als **System-Anweisung** an die KI, der Text als Nutzer-Nachricht.
4. Die Antwort ersetzt den Text im Editor **1:1**. Alles, was die KI zurückgibt, landet also im
   Beitrag – auch Einleitungen wie „Hier ist der optimierte Text:“.

Entwickler können den Prompt zur Laufzeit mit dem Filter
[`texttune_ai_prompt`](Hooks-und-Filter) ändern.

### Regeln für eigene Prompts

- **„Gib nur den optimierten Text zurück“** beibehalten – sonst landen Erklärungen im Beitrag.
- **„Behalte die HTML-Formatierung bei“** beibehalten. Im Block-Editor enthält der Text die
  Block-Kommentare (`<!-- wp:paragraph -->` …); gehen sie verloren, kann der Editor die Blöcke nicht
  wiederherstellen. Siehe [Block-Editor](Block-Editor#was-mit-der-antwort-passiert).
- Der Prompt wird als reiner Text gespeichert; HTML-Tags im Prompt selbst werden entfernt.

### Beispiele

Für einen Blog:

```
Optimiere den folgenden Blogbeitrag. Mache ihn lebendiger und ansprechender für die Leser.
Verwende eine lockere, persönliche Schreibweise. Behalte den Inhalt und die HTML-Formatierung
inklusive aller HTML-Kommentare exakt bei. Gib nur den optimierten Text zurück, ohne
zusätzliche Erklärungen.
```

Für eine Unternehmensseite:

```
Optimiere den folgenden Text für eine professionelle Unternehmenswebsite. Verwende eine formelle,
vertrauenswürdige Sprache und klare Struktur. Behalte die HTML-Formatierung inklusive aller
HTML-Kommentare exakt bei. Gib nur den optimierten Text zurück, ohne zusätzliche Erklärungen.
```

Für Produkte (z. B. WooCommerce):

```
Optimiere die folgende Produktbeschreibung. Hebe Vorteile hervor, verwende überzeugende Sprache
und sorge für eine klare Struktur. Behalte die HTML-Formatierung bei. Gib nur den optimierten
Text zurück, ohne zusätzliche Erklärungen.
```

Nur Rechtschreibung korrigieren:

```
Korrigiere ausschließlich Rechtschreibung, Grammatik und Zeichensetzung im folgenden Text.
Ändere weder Wortwahl noch Satzbau. Behalte die HTML-Formatierung inklusive aller
HTML-Kommentare exakt bei. Gib nur den korrigierten Text zurück, ohne Erklärungen.
```

---

## Bildanalyse-Prompt

### Standard-Vorlage

```
Du bist ein Bildanalyse-Assistent für eine WordPress-Mediathek. Analysiere das bereitgestellte
Bild und erzeuge Metadaten in der Sprache: {language}.

Dateiname (nur als Kontext, ignoriere generische Kamera-Namen wie IMG_1234):
{filename}

Gib ausschließlich ein einzelnes JSON-Objekt mit genau diesen vier Schlüsseln zurück:
- "alt":         kurzer beschreibender Alt-Text (max. 125 Zeichen, kein "Bild von ...")
- "title":       prägnanter Titel (max. 60 Zeichen)
- "caption":     ein Satz als Bildunterschrift
- "description": 2-4 Sätze ausführliche Beschreibung

Keine Einleitung, kein Markdown, keine Code-Fences. Nur das JSON-Objekt.
```

### Platzhalter

| Platzhalter | Wird ersetzt durch |
|---|---|
| `{language}` | Sprachname zur Sprache des angemeldeten Benutzers, z. B. *Deutsch* (Tabelle unten) |
| `{locale}` | Der WordPress-Sprachcode, z. B. `de_DE` |
| `{filename}` | Dateiname des Bildes, z. B. `strand-sonnenuntergang.jpg` |
| `{fields}` | Die angefragten Felder, kommagetrennt, z. B. `alt, title, caption, description` |

### Pflicht: Antwortformat

Egal wie du die Vorlage formulierst – die KI muss ein **JSON-Objekt** mit den Schlüsseln `alt`,
`title`, `caption` und `description` liefern. Fehlende Schlüssel ergeben leere Vorschläge.
Unabhängig vom Prompt kürzt TextTune AI Alt-Text auf 125, Titel auf 60 und Beschriftung auf
200 Zeichen.

Ein leeres Feld beim Speichern behält die bisherige Vorlage.

### Sprachnamen für `{language}`

| Sprachcode | `{language}` |
|---|---|
| `de_DE` | Deutsch |
| `de_AT` | Deutsch (Österreich) |
| `de_CH` | Deutsch (Schweiz) |
| `en_US` | English |
| `en_GB` | English (British) |
| `fr_FR` | Français |
| `es_ES` | Español |
| `it_IT` | Italiano |
| `nl_NL` | Nederlands |
| `pt_BR` | Português (Brasil) |
| `pt_PT` | Português |
| `pl_PL` | Polski |
| `sv_SE` | Svenska |
| `da_DK` | Dansk |
| `nb_NO` | Norsk bokmål |
| `fi` | Suomi |

Andere Sprachcodes werden über die ersten zwei Buchstaben zugeordnet (`de`, `en`, `fr`, `es`, `it`,
`nl`, `pt`, `pl`, `sv`, `da`); passt auch das nicht, wird der Sprachcode selbst eingesetzt.

> Die Sprache kommt aus der Benutzersprache (**Benutzer → Profil → Sprache**) bzw. der
> Website-Sprache. Wer die Metadaten immer auf Deutsch will, schreibt in der Vorlage statt
> `{language}` einfach „Deutsch“.

Entwickler können den fertigen Prompt mit dem Filter
[`texttune_ai_image_prompt`](Hooks-und-Filter) ändern.
