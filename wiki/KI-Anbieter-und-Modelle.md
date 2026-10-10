# KI-Anbieter und Modelle

TextTune AI unterstützt zwei Anbieter. Pro Installation ist immer **ein** Anbieter aktiv; er gilt für
die Textoptimierung und für die Bildanalyse.

| Anbieter | Schnittstelle | Authentifizierung |
|---|---|---|
| **OpenAI** | Chat Completions – `https://api.openai.com/v1/chat/completions` | Header `Authorization: Bearer <Schlüssel>` |
| **Anthropic** | Messages API – `https://api.anthropic.com/v1/messages` | Header `x-api-key`, `anthropic-version: 2023-06-01` |

---

## Wie die Anfragen aussehen

### Textoptimierung

| | OpenAI | Anthropic |
|---|---|---|
| Prompt | als `system`-Nachricht | als `system`-Parameter |
| Text | als `user`-Nachricht | als `user`-Nachricht |
| Weitere Parameter | `temperature: 0.7` | `max_tokens: 8192` |
| Timeout | 120 Sekunden | 120 Sekunden |

Zurückgegeben wird der Text der ersten Antwort, ohne führende und folgende Leerzeichen.

### Bildanalyse

| | OpenAI | Anthropic |
|---|---|---|
| Prompt | als Text in derselben `user`-Nachricht wie das Bild | als `system`-Parameter, dazu die feste Nutzer-Anweisung *„Analysiere dieses Bild gemäß der Anweisungen im System-Prompt. Antworte mit einem einzigen JSON-Objekt.“* |
| Bild | `image_url` als `data:`-URL (Base64) | `image`-Block mit Base64-Quelle |
| Weitere Parameter | `response_format: json_object`, `temperature: 0.3` | `max_tokens: 2048` |
| Timeout | 120 Sekunden | 120 Sekunden |

> Manche Modelle akzeptieren nicht alle dieser Parameter oder keine Bilder. Lehnt der Anbieter die
> Anfrage ab, erscheint dessen Fehlermeldung (z. B. *„OpenAI API Fehler (400): …“*). Dann ein
> anderes Modell wählen – siehe [Fehlerbehebung](Fehlerbehebung).

---

## Wie die Modellliste entsteht

### Eingebaute Standardliste

Ohne gespeicherten Schlüssel – oder wenn der Abruf beim Anbieter fehlschlägt – zeigt das Dropdown
diese Liste:

| Anbieter | Modell-ID | Anzeige |
|---|---|---|
| OpenAI | `gpt-4o` | GPT-4o |
| OpenAI | `gpt-4o-mini` | GPT-4o Mini |
| OpenAI | `gpt-4-turbo` | GPT-4 Turbo |
| Anthropic | `claude-sonnet-4-20250514` | Claude Sonnet 4 |
| Anthropic | `claude-haiku-4-5-20251001` | Claude Haiku 4.5 |

### Dynamische Liste vom Anbieter

Ist ein Schlüssel gespeichert, lädt die Einstellungsseite die Modelle **mit diesem Schlüssel direkt
beim Anbieter** – allerdings nur für den **gespeicherten** Provider (es gibt nur einen Schlüssel).
Wählst du einen anderen Provider, siehst du dessen Standardliste, bis du mit passendem Schlüssel
gespeichert hast.

| | OpenAI | Anthropic |
|---|---|---|
| Abruf | `GET https://api.openai.com/v1/models` | `GET https://api.anthropic.com/v1/models?limit=100` |
| Filter | Nur Chat-Modelle: ID beginnt mit `gpt-4`, `gpt-4o`, `gpt-4.1`, `gpt-5`, `chatgpt-4o`, `o1`, `o3` oder `o4` **und** enthält keines von `embed`, `whisper`, `tts`, `dall-e`, `audio`, `realtime`, `transcribe`, `moderation`, `search`, `instruct`, `davinci`, `babbage`, `codex`, `computer-use`, `image`, `deep-research` | alle Modelle |
| Sortierung | neueste zuerst | neueste zuerst |
| Anzeigename | aus der ID gebildet, z. B. `gpt-4o-mini` → *GPT-4o Mini* | `display_name` des Anbieters |
| Timeout | 15 Sekunden | 15 Sekunden |

### Zwischenspeicher

| Fall | Dauer |
|---|---|
| Erfolgreicher Abruf | **12 Stunden** (Transient `texttune_models_openai` bzw. `texttune_models_anthropic`) |
| Fehlgeschlagener Abruf | **15 Minuten** (Transient `…_failed`) – in dieser Zeit wird nicht erneut angefragt und die Standardliste gezeigt |

Der Zwischenspeicher wird geleert

- durch **Modelle aktualisieren** auf der Einstellungsseite,
- beim Speichern eines **neuen API-Schlüssels**,
- beim **Wechsel des Providers**.

Zusätzlich merkt sich der Zwischenspeicher einen kurzen Hash des Schlüssels; passt er nicht mehr zum
gespeicherten Schlüssel, wird neu geladen. Der Schlüssel selbst steht nie im Zwischenspeicher.

### Gespeicherte Modelle bleiben erhalten

- Taucht das gespeicherte Modell nicht mehr in der Liste auf, bleibt es trotzdem auswählbar – mit dem
  Zusatz **(gespeichert)**. Ein fehlgeschlagener Abruf ändert also nie still deine Auswahl.
- Beim Speichern gilt ein Modell als gültig, wenn es in der dynamischen Liste, in der Standardliste
  oder unter den bisher gespeicherten Modellen (Text- und Bildmodell) steht.

---

## Welches Modell?

- **Textoptimierung:** Jedes Chat-Modell des Anbieters funktioniert. Kleinere Modelle sind
  schneller und günstiger, größere liefern meist feinere Formulierungen.
- **Bildanalyse:** Das Modell muss **Bilder** verarbeiten können. Bei Bedarf unter
  **Bilderkennung → Modell-Override** ein eigenes Modell einstellen.
- **Lange Beiträge:** Bei Anthropic ist die Antwort auf 8192 Tokens begrenzt. Sehr lange Beiträge
  besser blockweise optimieren (siehe [Block-Editor](Block-Editor)).

Aktuelle Preise und Fähigkeiten stehen beim jeweiligen Anbieter.
