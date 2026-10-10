# Schnellstart

In fünf Schritten vom frisch aktivierten Plugin zum ersten optimierten Text.

## 1. API-Schlüssel besorgen

| Anbieter | Schlüssel erstellen |
|---|---|
| OpenAI | https://platform.openai.com/api-keys |
| Anthropic | https://console.anthropic.com/settings/keys |

Die Nutzung wird vom Anbieter pro Anfrage abgerechnet – das Plugin selbst ist kostenlos.

## 2. Provider, Schlüssel und Modell eintragen

1. **Einstellungen → TextTune AI** öffnen, Tab **Einstellungen**.
2. Bei **Provider** *OpenAI* oder *Anthropic* wählen.
3. Den Schlüssel in **API-Schlüssel** einfügen.
4. **Einstellungen speichern** klicken.
5. Die Seite lädt neu, und das Dropdown **Modell** zeigt jetzt die Modelle, die dein Schlüssel beim
   Anbieter nutzen darf. Ein Modell wählen und erneut speichern.

> Vor dem ersten Speichern zeigt das Dropdown nur eine kurze Standardliste (z. B. `GPT-4o`,
> `GPT-4o Mini`, `GPT-4 Turbo`). Die vollständige Liste lädt das Plugin erst mit gespeichertem
> Schlüssel – siehe [KI-Anbieter und Modelle](KI-Anbieter-und-Modelle).

## 3. Prompt prüfen (optional)

Im Tab **Prompts** steht für jeden öffentlichen Inhaltstyp (Beiträge, Seiten, Custom Post Types) ein
eigener Prompt. Vorbelegt ist:

```
Optimiere den folgenden Text. Verbessere Grammatik, Stil und Lesbarkeit. Behalte den Inhalt,
die Bedeutung und die HTML-Formatierung bei. Gib nur den optimierten Text zurück, ohne
zusätzliche Erklärungen.
```

Anpassen lohnt sich, sobald du einen bestimmten Ton willst – Beispiele unter [Prompts](Prompts).

## 4. Ersten Text optimieren

**Block-Editor:** Beitrag öffnen, einen Absatz anklicken und in der Block-Toolbar auf den
**Zauberstab** (*Block optimieren (TextTune AI)*) klicken. Für den ganzen Beitrag: Drei-Punkte-Menü
oben rechts → **Text optimieren (TextTune AI)**. Details: [Block-Editor](Block-Editor).

**Classic Editor:** In der zweiten Toolbar-Zeile auf den Zauberstab **TextTune AI** klicken und
**Gesamten Text optimieren** oder **Auswahl optimieren** wählen. Details: [Classic Editor](Classic-Editor).

Das Ergebnis ersetzt den Inhalt **direkt im Editor**, gespeichert wird aber erst, wenn du den
Beitrag speicherst. Prüfe den Text also vorher.

## 5. Bilder analysieren (optional)

In der Mediathek ein Bild öffnen und in den Anhang-Details auf **Bild analysieren** klicken. Die
KI schlägt Alt-Text, Titel, Beschriftung und Beschreibung vor; du entscheidest pro Feld, ob
überschrieben wird. Details: [Bildanalyse und Mediathek](Bildanalyse-und-Mediathek).

> Für die Bildanalyse muss das gewählte Modell Bilder verarbeiten können. Bei Bedarf im Tab
> **Bilderkennung** ein eigenes Modell als **Modell-Override** einstellen.
