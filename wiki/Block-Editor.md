# Block-Editor (Gutenberg)

TextTune AI bietet im Block-Editor zwei Aktionen: den **ganzen Beitrag** optimieren oder **einen
einzelnen Block**. Beide schicken den Inhalt an den Endpunkt
[`POST /texttune/v1/optimize`](REST-API#post-optimize) und verwenden den Prompt des aktuellen
Inhaltstyps (siehe [Prompts](Prompts)).

Voraussetzung: Dein Benutzer hat die Berechtigung `edit_posts` und in den
[Einstellungen](Einstellungen) ist ein API-Schlüssel gespeichert.

---

## Ganzen Beitrag optimieren

1. Beitrag oder Seite im Block-Editor öffnen.
2. Oben rechts auf das **Drei-Punkte-Menü** (⋮, *Optionen*) klicken.
3. Im Bereich der Plugins **Text optimieren (TextTune AI)** wählen (Zauberstab-Icon).
4. Während der Anfrage lautet der Eintrag **Optimierung läuft…** und ist deaktiviert.
5. Danach ersetzt der optimierte Inhalt **alle Blöcke** des Beitrags. Unten erscheint die
   Meldung *„Der gesamte Text wurde optimiert!“*.

### Was genau gesendet wird

- Der komplette Beitragsinhalt **so wie er gerade im Editor steht** (auch ungespeicherte
  Änderungen), als serialisiertes Block-HTML – also inklusive der Block-Kommentare
  `<!-- wp:paragraph -->` usw. und inklusive Nicht-Text-Blöcken wie Bildern.
- **Nicht** gesendet werden Titel, Auszug, Kategorien oder andere Metadaten.
- Ist der Beitrag leer, kommt sofort die Meldung *„Der Beitrag hat keinen Inhalt zum Optimieren.“*

### Was mit der Antwort passiert

Die Antwort wird mit `wp.blocks.parse()` wieder in Blöcke zerlegt und ersetzt den gesamten Inhalt
(`resetBlocks`). Ergibt das Parsen keine Blöcke, erscheint *„Die KI-Antwort konnte nicht verarbeitet
werden.“* und der Inhalt bleibt unverändert.

> **Tipp:** Damit die Blockstruktur erhalten bleibt, muss die KI die Block-Kommentare
> unverändert zurückgeben. Der Standard-Prompt verlangt, die HTML-Formatierung beizubehalten. Wenn
> du einen eigenen Prompt schreibst, behalte diese Anweisung bei (siehe [Prompts](Prompts)).

---

## Einzelnen Block optimieren

1. Den Block anklicken, den du optimieren möchtest.
2. In der **Block-Toolbar** erscheint ein **Zauberstab-Button** mit dem Tooltip
   *Block optimieren (TextTune AI)*.
3. Klicken – während der Anfrage zeigt der Button einen Ladezustand (*Optimierung läuft…*).
4. Der Block wird durch die optimierte Version ersetzt (`replaceBlock`); alle anderen Blöcke bleiben
   unverändert. Meldung: *„Block wurde optimiert!“*

Gesendet wird nur das serialisierte HTML dieses einen Blocks. Liefert die KI mehrere Blöcke zurück,
ersetzen sie gemeinsam den ursprünglichen Block.

### Unterstützte Block-Typen

Der Button erscheint nur bei diesen Blöcken:

| Block | Name |
|---|---|
| Absatz | `core/paragraph` |
| Überschrift | `core/heading` |
| Liste | `core/list` |
| Zitat | `core/quote` |
| Hervorgehobenes Zitat | `core/pullquote` |
| Vers | `core/verse` |
| Vorformatiert | `core/preformatted` |

Andere Blöcke (z. B. Bild, Gruppe, Klassisch, Blöcke anderer Plugins) haben keinen Button. Sie werden
nur über **Ganzen Beitrag optimieren** mitgeschickt.

---

## Welcher Prompt wird verwendet?

Der Prompt des **Inhaltstyps** des geöffneten Beitrags (z. B. `post`, `page` oder ein Custom Post
Type). Ist für diesen Inhaltstyp kein oder ein leerer Prompt gespeichert, nimmt TextTune AI den
Standard-Prompt. Siehe [Prompts](Prompts).

## Fehlermeldungen

Fehler erscheinen als rote Hinweisleiste oben im Editor, z. B. *„Kein API-Schlüssel konfiguriert.
Bitte gehe zu Einstellungen → TextTune AI.“* oder *„OpenAI API Fehler (401): …“*. Was sie bedeuten:
[Fehlerbehebung](Fehlerbehebung).

## Wichtig: Ergebnis prüfen

Die Optimierung ändert nur den Inhalt im Editor. **Gespeichert wird erst, wenn du den Beitrag
speicherst oder aktualisierst.** Gefällt dir das Ergebnis nicht, verlässt du den Editor ohne zu
speichern oder stellst über die WordPress-**Revisionen** einen früheren Stand wieder her.
