# Classic Editor

Im Classic Editor (TinyMCE) fügt TextTune AI einen **Zauberstab-Button „TextTune AI“** in die
**zweite Toolbar-Zeile** ein. Er öffnet ein Menü mit zwei Einträgen.

## Voraussetzungen

Der Button wird nur geladen, wenn

- dein Benutzer die Berechtigung `edit_posts` hat **und**
- in deinem Benutzerprofil der **visuelle Editor** aktiv ist (Option *Beim Schreiben den visuellen
  Editor deaktivieren* ist **nicht** angehakt).

Die nötigen Daten (REST-URL, Nonce, Inhaltstyp) stellt das Plugin auf den Seiten
**Beitrag bearbeiten** und **Neuen Beitrag erstellen** bereit (`post.php`, `post-new.php`), wenn dort
der Classic Editor statt des Block-Editors verwendet wird.

> Die zweite Toolbar-Zeile ist anfangs oft eingeklappt. Mit dem Button
> **Werkzeugleiste umschalten** in der ersten Zeile blendest du sie ein.

## Gesamten Text optimieren

1. Auf **TextTune AI** (Zauberstab) klicken.
2. **Gesamten Text optimieren** wählen.
3. Der Editor zeigt einen Ladezustand, der Button ist so lange gesperrt.
4. Der komplette Editor-Inhalt wird durch die optimierte Version ersetzt. Oben erscheint drei
   Sekunden lang *„Der gesamte Text wurde optimiert!“*.

Ist der Editor leer, kommt die Meldung *„Der Beitrag hat keinen Inhalt zum Optimieren.“*

## Auswahl optimieren

1. Den Text markieren, der optimiert werden soll.
2. **TextTune AI → Auswahl optimieren** wählen.
3. Nur die Markierung wird durch die optimierte Version ersetzt. Meldung:
   *„Der ausgewählte Text wurde optimiert!“*

Ohne Markierung erscheint *„Bitte wähle zuerst Text aus, den du optimieren möchtest.“*

## Rückgängig machen

Beide Aktionen werden als **ein Rückgängig-Schritt** im TinyMCE-Verlauf gespeichert. Mit
`Strg+Z` / `Cmd+Z` oder dem Rückgängig-Button der Toolbar stellst du den vorherigen Text wieder her.
Gespeichert wird ohnehin erst, wenn du den Beitrag speicherst.

## Welcher Prompt wird verwendet?

Der Prompt des Inhaltstyps, den du gerade bearbeitest – siehe [Prompts](Prompts).

## Fehlermeldungen

Fehler zeigt der Classic Editor als Browser-Dialog *„TextTune AI Fehler: …“*. Mögliche Texte sind die
Meldungen des Servers (z. B. fehlender API-Schlüssel, API-Fehler des Anbieters) sowie
*„Netzwerkfehler. Bitte versuche es erneut.“* und *„Fehler beim Verarbeiten der Antwort.“* –
siehe [Fehlerbehebung](Fehlerbehebung).
