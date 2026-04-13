# TextTune AI

KI-gestützte Textoptimierung direkt im WordPress-Editor. Optimiere deine Beiträge mit einem Klick — unterstützt **OpenAI** und **Anthropic**.

---

## Funktionen

- **Ein-Klick-Optimierung** — Gesamten Beitragstext per KI optimieren
- **Block-Optimierung** (Gutenberg) — Einzelne Blöcke gezielt optimieren
- **Auswahl-Optimierung** (Classic Editor) — Nur markierten Text optimieren
- **Zwei KI-Provider** — OpenAI (GPT-4o, GPT-4o Mini, GPT-4 Turbo) und Anthropic (Claude Sonnet 4, Claude Haiku 4.5)
- **Prompts pro Inhaltstyp** — Eigener Prompt für Beiträge, Seiten und Custom Post Types
- **Verschlüsselte API-Keys** — AES-256-CBC Verschlüsselung in der Datenbank
- **Gutenberg + Classic Editor** — Funktioniert in beiden Editoren
- **Undo-Support** — Änderungen lassen sich mit Strg+Z / Cmd+Z rückgängig machen

---

## Voraussetzungen

- WordPress **6.0** oder höher
- PHP **7.4** oder höher
- Ein API-Key von **OpenAI** oder **Anthropic**

---

## Installation

### Manuelle Installation

1. Lade das gesamte Repository als ZIP-Datei herunter oder klone es:
   ```
   git clone https://github.com/mrclksr2409/TextTune-AI.git
   ```
2. Kopiere den Ordner `TextTune-AI` in dein WordPress Plugin-Verzeichnis:
   ```
   wp-content/plugins/TextTune-AI/
   ```
3. Gehe im WordPress-Admin zu **Plugins → Installierte Plugins**
4. Suche **TextTune AI** und klicke auf **Aktivieren**

### Upload via WordPress-Admin

1. Lade das Repository als ZIP-Datei herunter
2. Gehe zu **Plugins → Installieren → Plugin hochladen**
3. Wähle die ZIP-Datei aus und klicke auf **Jetzt installieren**
4. Klicke auf **Plugin aktivieren**

---

## Einrichtung

Nach der Aktivierung muss das Plugin einmalig konfiguriert werden.

### 1. Einstellungsseite öffnen

Navigiere zu **Einstellungen → TextTune AI** im WordPress-Admin-Menü.

### 2. KI-Provider wählen

Wähle deinen bevorzugten KI-Provider:

| Provider | Beschreibung | API-Key beantragen |
|----------|-------------|-------------------|
| **OpenAI** | GPT-4o, GPT-4o Mini, GPT-4 Turbo | https://platform.openai.com/api-keys |
| **Anthropic** | Claude Sonnet 4, Claude Haiku 4.5 | https://console.anthropic.com/settings/keys |

### 3. API-Schlüssel eingeben

Gib deinen API-Schlüssel in das Passwort-Feld ein. Der Schlüssel wird **verschlüsselt** in der Datenbank gespeichert und ist im Klartext nicht einsehbar.

> **Hinweis:** Wenn du den Schlüssel ändern möchtest, gib einfach einen neuen ein. Lässt du das Feld leer, bleibt der bestehende Schlüssel erhalten.

### 4. Modell auswählen

Wähle das KI-Modell aus dem Dropdown. Die verfügbaren Modelle ändern sich je nach gewähltem Provider:

**OpenAI:**
| Modell | Beschreibung |
|--------|-------------|
| GPT-4o | Flaggschiff-Modell, beste Qualität |
| GPT-4o Mini | Schneller und günstiger, gute Qualität |
| GPT-4 Turbo | Schnelle Variante von GPT-4 |

**Anthropic:**
| Modell | Beschreibung |
|--------|-------------|
| Claude Sonnet 4 | Ausgewogen zwischen Geschwindigkeit und Qualität |
| Claude Haiku 4.5 | Schnellstes Modell, ideal für kurze Texte |

### 5. Prompts pro Inhaltstyp konfigurieren

Für jeden registrierten öffentlichen Inhaltstyp (z. B. „Beiträge", „Seiten") gibt es ein eigenes Textarea-Feld. Hier definierst du die Anweisung, die an die KI gesendet wird.

**Standard-Prompt (vorkonfiguriert):**
```
Optimiere den folgenden Text. Verbessere Grammatik, Stil und Lesbarkeit.
Behalte den Inhalt, die Bedeutung und die HTML-Formatierung bei.
Gib nur den optimierten Text zurück, ohne zusätzliche Erklärungen.
```

**Beispiele für eigene Prompts:**

Für einen Blog:
```
Optimiere den folgenden Blogbeitrag. Mache ihn lebendiger und
ansprechender für die Leser. Verwende eine lockere, persönliche
Schreibweise. Behalte die HTML-Formatierung bei.
```

Für eine Unternehmensseite:
```
Optimiere den folgenden Text für eine professionelle Unternehmenswebsite.
Verwende eine formelle, vertrauenswürdige Sprache. Achte auf klare
Strukturierung und Lesbarkeit. Behalte die HTML-Formatierung bei.
```

Für ein Produkt (WooCommerce):
```
Optimiere die folgende Produktbeschreibung. Hebe Vorteile hervor,
verwende überzeugende Sprache und sorge für eine klare Struktur.
Behalte die HTML-Formatierung bei.
```

### 6. Einstellungen speichern

Klicke auf **Einstellungen speichern**. Die Konfiguration wird sofort wirksam.

---

## Nutzung

### Im Gutenberg Block-Editor

#### Gesamten Beitrag optimieren

1. Öffne einen Beitrag oder eine Seite im Block-Editor
2. Klicke oben rechts auf das **Drei-Punkte-Menü** (⋮)
3. Wähle **„Text optimieren (TextTune AI)"** (Zauberstab-Icon)
4. Warte, bis die Optimierung abgeschlossen ist (Ladebalken wird angezeigt)
5. Der gesamte Inhalt wird durch die optimierte Version ersetzt
6. Eine Erfolgsmeldung erscheint unten im Editor

#### Einzelnen Block optimieren

1. Klicke auf den Block, den du optimieren möchtest (z. B. einen Absatz, eine Überschrift)
2. In der Block-Toolbar erscheint der **Zauberstab-Button** (TextTune AI)
3. Klicke darauf — nur dieser Block wird optimiert
4. Die restlichen Blöcke bleiben unverändert

**Unterstützte Block-Typen:**
- Absatz (Paragraph)
- Überschrift (Heading)
- Liste (List)
- Zitat (Quote)
- Hervorgehobenes Zitat (Pullquote)
- Vers (Verse)
- Vorformatiert (Preformatted)

### Im Classic Editor

#### Gesamten Text optimieren

1. Öffne einen Beitrag im Classic Editor
2. Suche den **Zauberstab-Button „TextTune AI"** in der zweiten Toolbar-Zeile
3. Klicke auf den Button — ein Dropdown-Menü öffnet sich
4. Wähle **„Gesamten Text optimieren"**
5. Der gesamte Editor-Inhalt wird durch die optimierte Version ersetzt

#### Ausgewählten Text optimieren

1. Markiere den Text, den du optimieren möchtest
2. Klicke auf den **TextTune AI**-Button in der Toolbar
3. Wähle **„Auswahl optimieren"**
4. Nur der markierte Bereich wird durch die optimierte Version ersetzt

> **Tipp:** Falls die zweite Toolbar-Zeile nicht sichtbar ist, klicke auf den Button „Werkzeugleiste umschalten" (⌄) in der ersten Zeile.

---

## Rückgängig machen

Alle Optimierungen können rückgängig gemacht werden:

- **Gutenberg:** `Strg+Z` (Windows) oder `Cmd+Z` (Mac) drücken
- **Classic Editor:** `Strg+Z` (Windows) oder `Cmd+Z` (Mac) drücken oder den Rückgängig-Button in der Toolbar verwenden

---

## Fehlerbehebung

### „Kein API-Schlüssel konfiguriert"
Gehe zu **Einstellungen → TextTune AI** und gib deinen API-Schlüssel ein.

### „OpenAI/Anthropic API Fehler (401)"
Der API-Schlüssel ist ungültig oder abgelaufen. Erstelle einen neuen Schlüssel beim jeweiligen Provider.

### „OpenAI/Anthropic API Fehler (429)"
Du hast das Rate-Limit deines API-Plans erreicht. Warte einige Sekunden und versuche es erneut, oder upgrade deinen API-Plan.

### „OpenAI/Anthropic API Fehler (500/502/503)"
Der KI-Dienst ist vorübergehend nicht erreichbar. Versuche es in einigen Minuten erneut.

### „Anfrage fehlgeschlagen"
Es gibt ein Netzwerkproblem zwischen deinem Server und der KI-API. Prüfe, ob dein Server ausgehende HTTPS-Verbindungen erlaubt (Port 443).

### Der Button erscheint nicht im Editor
- Stelle sicher, dass das Plugin aktiviert ist (**Plugins → Installierte Plugins**)
- **Classic Editor:** Prüfe, ob die zweite Toolbar-Zeile eingeblendet ist
- **Gutenberg:** Prüfe, ob du das Drei-Punkte-Menü (⋮) oben rechts geöffnet hast
- Leere den Browser-Cache und lade die Seite neu

### Warnung „OpenSSL ist nicht verfügbar"
Dein Server hat die PHP OpenSSL-Extension nicht installiert. Der API-Schlüssel wird nur Base64-kodiert gespeichert (weniger sicher). Bitte deinen Hoster, OpenSSL zu aktivieren.

---

## Für Entwickler

### Hooks und Filter

Das Plugin bietet mehrere Hooks für Erweiterungen:

```php
// Inhalt vor dem Senden an die KI anpassen.
add_filter( 'texttune_ai_pre_optimize_content', function( $content, $post_type ) {
    // $content ändern...
    return $content;
}, 10, 2 );

// Prompt vor dem Senden anpassen.
add_filter( 'texttune_ai_prompt', function( $prompt, $post_type ) {
    // $prompt ändern...
    return $prompt;
}, 10, 2 );

// KI-Antwort vor der Rückgabe an den Editor anpassen.
add_filter( 'texttune_ai_post_optimize_content', function( $result, $original_content, $post_type ) {
    // $result ändern...
    return $result;
}, 10, 3 );

// Aktion nach erfolgreicher Optimierung (z. B. für Logging).
add_action( 'texttune_ai_optimized', function( $result, $original_content, $post_type ) {
    // Logging, Analytics, etc.
}, 10, 3 );
```

### REST API Endpoint

```
POST /wp-json/texttune/v1/optimize

Header:  X-WP-Nonce: <nonce>
Body:    { "content": "<html>", "post_type": "post" }
Antwort: { "success": true, "content": "<optimized html>" }
```

Erfordert die WordPress-Berechtigung `edit_posts`.

---

## Dateistruktur

```
TextTune-AI/
├── texttune-ai.php                      # Haupt-Plugin-Datei
├── uninstall.php                        # Aufräumen bei Deinstallation
├── includes/
│   ├── class-texttune-activator.php     # Plugin-Aktivierung
│   ├── class-texttune-encryption.php    # API-Key-Verschlüsselung
│   ├── class-texttune-settings.php      # Einstellungsseite
│   ├── class-texttune-rest-api.php      # REST API Endpoint
│   ├── class-texttune-openai.php        # OpenAI Client
│   └── class-texttune-anthropic.php     # Anthropic Client
├── assets/
│   ├── js/
│   │   ├── texttune-editor.js           # Gutenberg-Integration
│   │   ├── texttune-classic-editor.js   # Classic Editor-Integration
│   │   └── texttune-admin.js            # Einstellungsseite JS
│   └── css/
│       └── texttune-admin.css           # Einstellungsseite Styles
└── languages/                           # Übersetzungsdateien
```

---

## Lizenz

GPL-2.0-or-later — [Lizenztext](https://www.gnu.org/licenses/gpl-2.0.html)
