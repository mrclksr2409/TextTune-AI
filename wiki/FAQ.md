# FAQ

### Ändert TextTune AI meine Beiträge automatisch?
Nein. Jede Optimierung startet mit einem Klick im Editor, und das Ergebnis steht zunächst nur im
Editor. **Dauerhaft wird es erst, wenn du den Beitrag speicherst.** Auch die Bildanalyse speichert
nur, was du im Dialog mit **Übernehmen** bestätigst (bzw. was die gewählte Strategie der
Massenaktion vorgibt).

### Kann ich eine Optimierung rückgängig machen?
Im Classic Editor mit `Strg+Z` / `Cmd+Z` – die Optimierung ist ein einzelner Rückgängig-Schritt. Im
Block-Editor gilt: vor dem Speichern prüfen; ein bereits gespeicherter Stand lässt sich über die
WordPress-Revisionen wiederherstellen.

### Was kostet das?
Das Plugin ist kostenlos. Kosten entstehen beim KI-Anbieter pro Anfrage, abhängig von Modell und
Textlänge. Jede Optimierung ist eine Anfrage, jede übernommene Bildanalyse zwei. Aktuelle Preise
stehen beim Anbieter.

### Welcher Anbieter ist besser, OpenAI oder Anthropic?
Beide funktionieren. Probiere mit demselben Prompt beide aus. Es ist immer nur **ein** Anbieter mit
**einem** API-Schlüssel aktiv; beim Wechsel musst du den Schlüssel des anderen Anbieters eintragen.

### Kann ich verschiedene Modelle für Text und Bilder nutzen?
Ja. **Bilderkennung → Modell-Override** legt ein eigenes Modell für die Bildanalyse fest – allerdings
vom selben Anbieter.

### Kann ich unterschiedliche Prompts für Beiträge und Seiten nutzen?
Ja, genau dafür gibt es den Tab **Prompts**: ein Prompt pro öffentlichem Inhaltstyp, auch für Custom
Post Types. Siehe [Prompts](Prompts).

### Gibt es Modi wie „kürzen“, „formeller“ oder „übersetzen“?
Nicht als eigene Buttons. Was die KI tut, bestimmt der Prompt des Inhaltstyps. Du kannst ihn
beliebig formulieren – auch als Übersetzungs- oder Kürzungsanweisung.

### Werden Titel, Auszug oder SEO-Felder optimiert?
Nein. Gesendet wird nur der Inhalt (ganzer Beitrag, ein Block oder die Markierung).

### Funktioniert TextTune AI mit Page-Buildern?
Unterstützt werden der Block-Editor (mit den Text-Blöcken von WordPress) und der Classic Editor
(TinyMCE). Andere Editoren sind nicht eingebunden.

### Welche Bilder kann die Bildanalyse verarbeiten?
JPEG, PNG, GIF und WebP. Große Bilder werden vor dem Versand verkleinert; das Original bleibt
unverändert. Siehe [Bildanalyse und Mediathek](Bildanalyse-und-Mediathek).

### In welcher Sprache entstehen die Bild-Metadaten?
In der Sprache deines WordPress-Benutzers. Anpassbar über die Prompt-Vorlage – siehe
[Prompts](Prompts#bildanalyse-prompt).

### Wer darf TextTune AI benutzen?
Optimieren darf jeder mit `edit_posts`, Bilder analysieren jeder, der den Anhang bearbeiten darf.
Die Einstellungen sind Administratoren vorbehalten. Siehe
[Sicherheit und Datenschutz](Sicherheit-und-Datenschutz).

### Ist der API-Schlüssel sicher gespeichert?
Er wird mit AES-256-CBC verschlüsselt in der Datenbank abgelegt; der Schlüssel dafür stammt aus den
Salts deiner `wp-config.php`. Details: [Sicherheit und Datenschutz](Sicherheit-und-Datenschutz#api-schlüssel).

### Ist die Oberfläche übersetzbar?
Alle Texte nutzen die Textdomain `texttune-ai`. Mitgeliefert wird nur die deutsche Oberfläche.

### Wo melde ich Fehler oder Wünsche?
Als [Issue auf GitHub](https://github.com/mrclksr2409/TextTune-AI/issues).
