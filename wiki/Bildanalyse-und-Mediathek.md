# Bildanalyse und Mediathek

TextTune AI kann Bilder der Mediathek an die KI schicken und daraus **Alt-Text**, **Titel**,
**Beschriftung** und **Beschreibung** erzeugen lassen. Du siehst die Vorschläge neben den aktuellen
Werten und entscheidest pro Feld, ob überschrieben wird.

Verwendet werden derselbe Provider und API-Schlüssel wie für die Textoptimierung. Das Modell ist
standardmäßig ebenfalls dasselbe, lässt sich aber separat einstellen (Tab **Bilderkennung**, siehe
[Einstellungen](Einstellungen#tab-bilderkennung)).

> **Das Modell muss Bilder verstehen** (Vision). Reine Textmodelle lehnen die Anfrage ab.

---

## Wo der Button erscheint

| Ort | Bedienelement | Voraussetzung |
|---|---|---|
| **Anhang-Details** im Medien-Dialog (Mediathek-Raster, Medien-Dialog im Editor) | Feld **TextTune AI** mit Button **Bild analysieren** und dem Hinweis *„Generiert Alt-Text, Titel, Beschriftung und Beschreibung aus dem Bildinhalt.“* | Anhang ist ein Bild, Benutzer darf ihn bearbeiten |
| **Anhang bearbeiten** (eigene Bearbeitungsseite eines Bildes) | Gleiches Feld **TextTune AI → Bild analysieren** | wie oben |
| **Mediathek, Listenansicht** – Zeilenaktion beim Überfahren | Link **Mit TextTune AI analysieren** | wie oben |
| **Mediathek, Listenansicht** – Dropdown *Mehrfachaktionen* | **Mit TextTune AI analysieren** | Berechtigung `upload_files` |

---

## Einzelnes Bild analysieren

1. **Bild analysieren** bzw. **Mit TextTune AI analysieren** klicken. Neben dem Button steht
   *„Analysiere Bild…“*.
2. Es öffnet sich der Dialog **Vorgeschlagene Metadaten** mit einer Tabelle:

   | Feld | Aktuell | Vorschlag | Aktion |
   |---|---|---|---|
   | z. B. Alt-Text | aktueller Wert oder *(leer)* | Vorschlag der KI | ◉ Überschreiben ○ Behalten |

   - Angezeigt werden nur die [aktivierten Felder](Einstellungen#tab-bilderkennung), für die die KI
     einen Vorschlag geliefert hat.
   - **Vorauswahl:** Ist ein Feld leer, steht es auf *Überschreiben*, sonst auf *Behalten*.
   - Die Buttons **Alle überschreiben** und **Alle behalten** setzen alle Zeilen auf einmal.
   - Als *Aktuell* nimmt der Dialog die Werte aus dem geöffneten Formular – auch noch nicht
     gespeicherte Eingaben. Gibt es kein Formular (z. B. Zeilenaktion), die gespeicherten Werte.
3. **Übernehmen** speichert, **Abbrechen** (oder `Esc`, oder ein Klick neben den Dialog) verwirft.
4. Nach dem Speichern steht neben dem Button *„Gespeichert.“*, und geöffnete Formularfelder zeigen
   die neuen Werte.

> **Gut zu wissen:** Beim Klick auf **Übernehmen** analysiert der Server das Bild **erneut** und
> speichert das Ergebnis dieses zweiten Durchlaufs. Pro übernommenem Bild entstehen also zwei
> Anfragen beim KI-Anbieter, und die gespeicherten Texte können leicht von den im Dialog gezeigten
> abweichen.

---

## Viele Bilder auf einmal (Massenaktion)

1. **Medien → Mediathek** in der **Listenansicht** öffnen.
2. Bilder anhaken, im Dropdown **Mehrfachaktionen** den Eintrag **Mit TextTune AI analysieren**
   wählen und **Übernehmen** klicken.
3. Die Seite lädt neu und öffnet den Dialog **Bilder werden analysiert** mit der Frage
   *„Wie sollen bereits befüllte Felder behandelt werden?“*:

   | Strategie | Wirkung |
   |---|---|
   | **Alle bestehenden Werte überschreiben** | Jedes aktivierte Feld mit Vorschlag wird überschrieben |
   | **Leere Felder befüllen, bestehende behalten** (Standard) | Nur leere Felder werden befüllt |
   | **Pro Bild fragen** | Für jedes Bild erscheint der Vergleichsdialog wie bei der Einzelanalyse |

4. **Start** klicken. Die Bilder werden **nacheinander** bearbeitet, mit Fortschrittsbalken und
   *„Bild x von y“*. **Abbrechen** stoppt nach dem aktuellen Bild.
5. Am Ende: *„Fertig. N gespeichert, M übersprungen.“* Als *gespeichert* zählt ein Bild, bei dem
   mindestens ein Feld geschrieben wurde. Anhänge, die keine Bilder sind, und Bilder mit Fehler
   zählen als *übersprungen*.

Auch hier gilt: Pro Bild werden zwei Anfragen gestellt (Vorschlag abrufen, dann speichern).

---

## Welche Felder geschrieben werden

| Feld | Gespeichert in | Länge |
|---|---|---|
| Alt-Text | Post-Meta `_wp_attachment_image_alt` | max. 125 Zeichen |
| Titel | Titel des Anhangs (`post_title`) | max. 60 Zeichen |
| Beschriftung | Auszug des Anhangs (`post_excerpt`) | max. 200 Zeichen |
| Beschreibung | Inhalt des Anhangs (`post_content`) | ohne Kürzung, erlaubtes HTML wie in Beiträgen |

Längere Vorschläge der KI werden auf diese Längen gekürzt. Alt-Text, Titel und Beschriftung werden
als reiner Text gespeichert.

**Welche Felder überhaupt vorgeschlagen und gespeichert werden**, legst du unter
**Einstellungen → TextTune AI → Bilderkennung → Aktivierte Felder** fest. Ist dort kein einziges
Feld angehakt, verwendet TextTune AI alle vier.

---

## Was mit dem Bild passiert

1. **Format prüfen:** Unterstützt werden JPEG, PNG, GIF und WebP. Andere Formate (z. B. SVG, HEIC)
   werden mit *„Bildformat wird nicht unterstützt: …“* abgelehnt.
2. **Datei laden:** Zuerst von der Festplatte. Liegt die Datei nicht lokal vor (z. B. ausgelagerte
   Medien auf CDN/S3), lädt TextTune AI sie über die Anhang-URL (Timeout 30 Sekunden).
3. **Verkleinern:** Ist die längere Kante größer als die eingestellte **Max. Bildkante** (Standard
   1568 px), wird eine verkleinerte Kopie mit dem WordPress-Bildeditor erzeugt. PNG und GIF bleiben
   im Format, alle anderen werden als JPEG gesendet. Steht kein Bildeditor zur Verfügung, wird das
   Bild unverändert gesendet. **Das Original in der Mediathek bleibt immer unverändert.**
4. **Größenlimit:** Ist das Bild danach größer als 5 MB, versucht TextTune AI eine JPEG-Kodierung mit
   Qualität 70. Reicht auch das nicht: *„Das Bild ist auch nach Verkleinerung zu groß für die API.“*
5. **Senden:** Das Bild geht Base64-kodiert zusammen mit dem Prompt an den Anbieter. Der
   Dateiname wird als Kontext in den Prompt eingesetzt.
6. **Antwort lesen:** Erwartet wird ein JSON-Objekt mit `alt`, `title`, `caption`, `description`.
   Umgebende Code-Fences (```` ```json ````) werden entfernt; steht Text um das JSON herum, wird das
   erste vollständige `{…}`-Objekt verwendet.

Die Sprache der Metadaten richtet sich nach der **Sprache deines WordPress-Benutzers** (Platzhalter
`{language}` im Prompt) – siehe [Prompts](Prompts#bildanalyse-prompt).

---

## Fehlermeldungen

Fehler erscheinen neben dem Button und als Browser-Dialog *„Fehler: …“*. Tritt beim Übernehmen ein
Fehler auf, steht er oben im Dialog; fehlt der API-Schlüssel, gibt es dort einen Link
**Einstellungen öffnen**. Bedeutung der Meldungen: [Fehlerbehebung](Fehlerbehebung#bildanalyse).
