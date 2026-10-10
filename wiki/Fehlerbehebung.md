# Fehlerbehebung

Viele Meldungen enthalten den HTTP-Status und den Originaltext des KI-Anbieters, z. B.
*„OpenAI API Fehler (401): Incorrect API key provided …“*. Der Status in Klammern ist der wichtigste
Hinweis.

Bei Problemen mit dem Laden des Plugins hilft das PHP-Fehlerprotokoll: In der `wp-config.php`
`define( 'WP_DEBUG', true );` und `define( 'WP_DEBUG_LOG', true );` setzen, dann steht alles in
`wp-content/debug.log`. TextTune-AI-Einträge beginnen mit `TEXTTUNE-AI`.

---

## Plugin lädt nicht

### Es sind zwei Kopien des Plugins installiert

> *„TextTune AI: Es sind zwei Kopien des Plugins installiert. Bitte deaktiviere und lösche die alte
> Kopie unter „Plugins → Installierte Plugins“.“*

Typisch, wenn neben einer bestehenden Installation eine frisch von GitHub geladene ZIP in einem
Ordner wie `TextTune-AI-main` hochgeladen wurde. Die zweite Kopie lädt bewusst nicht, weil doppelt
definierte Klassen und Funktionen die Website lahmlegen würden.

**Lösung:** Unter **Plugins → Installierte Plugins** die ältere Kopie deaktivieren und löschen. Die
Einstellungen bleiben erhalten, solange du nicht *beide* Kopien löschst (beim Löschen werden die
Optionen entfernt – siehe [Datenbank und Deinstallation](Datenbank-und-Deinstallation)).

### TextTune AI benötigt PHP 7.4 oder höher

> *„TextTune AI benötigt PHP 7.4 oder höher. Diese Website verwendet PHP x.y.“*

Das Plugin prüft die PHP-Version, bevor es seine Dateien lädt, und bleibt auf älteren Versionen
inaktiv – die Website läuft weiter. **Lösung:** PHP beim Hoster auf mindestens 7.4 anheben.

Bei der **Aktivierung** wird zusätzlich WordPress geprüft: Unter WordPress 6.0 erscheint
*„TextTune AI benötigt WordPress 6.0 oder höher.“* und das Plugin wird wieder deaktiviert.

### TextTune AI konnte nicht geladen werden

> *„TextTune AI konnte nicht geladen werden: … in …:Zeile“*

Eine Plugin-Datei fehlt oder ist beschädigt (z. B. nach einem abgebrochenen Upload). Die Meldung nennt
Datei und Zeile, sie steht auch im Fehlerprotokoll (`TEXTTUNE-AI LOAD ERROR`) und in der Option
`texttune_ai_last_error`. **Lösung:** Plugin neu hochladen.

### TextTune AI Aktivierungsfehler

> *„TextTune AI Aktivierungsfehler: … Siehe wp-content/debug.log (mit WP_DEBUG_LOG) für Details.“*

Details inklusive Stacktrace stehen im Fehlerprotokoll unter `TEXTTUNE-AI ACTIVATION ERROR`.
Fehler beim späteren Initialisieren stehen unter `TEXTTUNE-AI INIT ERROR`.

---

## API-Schlüssel und Anbieter

### Kein API-Schlüssel konfiguriert

> *„Kein API-Schlüssel konfiguriert. Bitte gehe zu Einstellungen → TextTune AI.“*

**Einstellungen → TextTune AI → Einstellungen → API-Schlüssel** eintragen und speichern.

### Kein API-Schlüssel konfiguriert, obwohl einer gespeichert ist

Der gespeicherte Schlüssel lässt sich nicht mehr entschlüsseln. Das passiert, wenn die Salts
`AUTH_KEY` oder `SECURE_AUTH_KEY` in der `wp-config.php` geändert wurden (z. B. nach einem
Sicherheitsvorfall oder Umzug). **Lösung:** Den API-Schlüssel neu eingeben und speichern. Siehe
[Sicherheit und Datenschutz](Sicherheit-und-Datenschutz#api-schlüssel).

### Warnung „OpenSSL ist nicht verfügbar“

Die PHP-Erweiterung OpenSSL fehlt; der API-Schlüssel wird nur Base64-kodiert gespeichert. Den Hoster
bitten, OpenSSL zu aktivieren, danach den Schlüssel neu eingeben.

### HTTP 401 – Schlüssel abgelehnt

- Schlüssel und **Provider passen nicht zusammen**: Ein OpenAI-Schlüssel funktioniert nicht mit
  *Anthropic* und umgekehrt. Es wird nur ein Schlüssel gespeichert – nach einem Providerwechsel den
  passenden Schlüssel eintragen.
- Schlüssel wurde beim Anbieter widerrufen oder enthält einen Tippfehler.

### HTTP 429 – Rate-Limit / Guthaben

Zu viele Anfragen oder kein Guthaben beim Anbieter. Bei der Bildanalyse lautet die Meldung
*„OpenAI Rate-Limit erreicht. Bitte später erneut versuchen.“* bzw. *„Anthropic Rate-Limit erreicht …“*.
Im Konto des Anbieters Guthaben und Limits prüfen, kurz warten und erneut versuchen. Bei der
Massenanalyse vieler Bilder lieber in kleineren Portionen arbeiten.

### HTTP 400 / 404 – Modellproblem

Meist existiert das Modell nicht (mehr) oder akzeptiert die Anfrage nicht. TextTune AI sendet bei
OpenAI immer `temperature` (0.7 für Text, 0.3 für Bilder) und bei der Bildanalyse zusätzlich
`response_format: json_object`; bei Anthropic ein festes `max_tokens`. Modelle, die das nicht
unterstützen, lehnen die Anfrage ab.

**Lösung:** **Modelle aktualisieren** klicken und ein anderes, gelistetes Modell wählen. Steht hinter
dem Modell **(gespeichert)**, listet der Anbieter es nicht mehr.

### HTTP 500 / 502 / 503

Der Dienst des Anbieters ist vorübergehend gestört. In einigen Minuten erneut versuchen.

### OpenAI / Anthropic Anfrage fehlgeschlagen

> *„OpenAI Anfrage fehlgeschlagen: cURL error 28: …“*

Netzwerkproblem zwischen deinem Server und der API:

- Der Server darf ausgehende HTTPS-Verbindungen (Port 443) zu `api.openai.com` bzw.
  `api.anthropic.com` nicht aufbauen (Firewall des Hosters).
- **Timeout:** TextTune AI wartet bis zu 120 Sekunden. Lange Beiträge mit großen Modellen können
  länger brauchen, oder PHP bzw. der Webserver/Proxy brechen vorher ab (`max_execution_time`,
  `fastcgi_read_timeout`, Cloudflare). Abhilfe: blockweise optimieren oder ein schnelleres Modell.

### Unerwartete Antwort von OpenAI / Anthropic

Der Anbieter hat geantwortet, aber ohne Text. Erneut versuchen; tritt es dauerhaft auf, ein anderes
Modell wählen.

### Modellliste lädt nicht

> *„Dynamische Modell-Liste konnte nicht geladen werden – Standardliste wird angezeigt. (…)“*

- Schlüssel prüfen (siehe 401).
- Server erreicht den Anbieter nicht (siehe oben).
- Nach einem Fehler wird **15 Minuten** lang nicht erneut gefragt. **Modelle aktualisieren** erzwingt
  einen neuen Versuch sofort.
- Das gespeicherte Modell bleibt trotzdem nutzbar.

Die dynamische Liste gibt es nur für den **gespeicherten** Provider – nach einem Providerwechsel erst
speichern. Siehe [KI-Anbieter und Modelle](KI-Anbieter-und-Modelle).

---

## Editor

### Der Button erscheint nicht

**Block-Editor:**
- Der Eintrag **Text optimieren (TextTune AI)** steht im **Drei-Punkte-Menü** oben rechts, nicht in
  der Werkzeugleiste.
- Der Zauberstab in der Block-Toolbar erscheint nur bei Absatz, Überschrift, Liste, Zitat, Pullquote,
  Vers und Vorformatiert – siehe [Block-Editor](Block-Editor#unterstützte-block-typen).

**Classic Editor:**
- Der Button sitzt in der **zweiten Toolbar-Zeile** – mit **Werkzeugleiste umschalten** einblenden.
- Im eigenen Profil darf *Beim Schreiben den visuellen Editor deaktivieren* nicht angehakt sein.
- Dein Benutzer braucht `edit_posts`.

Allgemein: Plugin aktiv? Browser-Cache leeren und neu laden.

### Die KI-Antwort konnte nicht verarbeitet werden

Im Block-Editor ließ sich die Antwort nicht in Blöcke umwandeln. Erneut versuchen. Tritt es häufig auf,
den Prompt prüfen: Er muss verlangen, nur den Text zurückzugeben und die HTML-Formatierung
beizubehalten (siehe [Prompts](Prompts#regeln-für-eigene-prompts)).

### Blöcke sind nach der Optimierung verändert oder „Klassisch“

Die KI hat die Block-Kommentare (`<!-- wp:… -->`) nicht vollständig zurückgegeben. Den Prompt
ergänzen, z. B. *„Behalte alle HTML-Kommentare exakt bei.“*, oder blockweise optimieren. Vor dem
Speichern prüfen – die Änderung ist erst nach dem Speichern dauerhaft.

### Die KI schreibt Erklärungen in den Text

Der Prompt erlaubt es. *„Gib nur den optimierten Text zurück, ohne zusätzliche Erklärungen.“* im Prompt
behalten (siehe [Prompts](Prompts)).

### Text wird bei sehr langen Beiträgen abgeschnitten

Bei Anthropic ist die Antwort auf 8192 Tokens begrenzt. Lange Beiträge blockweise optimieren.

---

## Bildanalyse

| Meldung | Ursache | Lösung |
|---|---|---|
| *Der Anhang ist kein Bild.* / *Der Anhang wurde nicht gefunden.* | Kein Bild-Anhang | – |
| *Bildformat wird nicht unterstützt: …* | Nicht JPEG, PNG, GIF oder WebP | Bild in eines dieser Formate umwandeln |
| *Die Bilddatei konnte nicht geladen werden.* | Datei fehlt lokal und ist über ihre URL nicht abrufbar | Datei bzw. Offload-Plugin prüfen |
| *Das Bild ist auch nach Verkleinerung zu groß für die API.* | Auch verkleinert über 5 MB, oder kein Bildeditor (GD/Imagick) zum Verkleinern verfügbar | **Max. Bildkante** senken; GD oder Imagick auf dem Server aktivieren |
| *Die Antwort des KI-Dienstes konnte nicht als JSON gelesen werden.* / *Leere Antwort vom KI-Dienst.* | Das Modell hat kein JSON geliefert | Bildfähiges Modell wählen (**Modell-Override**); eigene Prompt-Vorlage auf das JSON-Format prüfen. Mit `WP_DEBUG` steht der Anfang der Antwort im Fehlerprotokoll. |
| *… API Fehler (400): …* | Modell kann keine Bilder verarbeiten oder akzeptiert die Parameter nicht | Bildfähiges Modell unter **Bilderkennung → Modell-Override** wählen |
| *Du hast keine Berechtigung, diesen Anhang zu bearbeiten.* | Fehlendes `edit_post` für den Anhang | Rolle/Rechte prüfen |

### Vorschlag und gespeicherter Text unterscheiden sich

Beim **Übernehmen** analysiert der Server das Bild erneut und speichert das neue Ergebnis. Kleine
Abweichungen zum Dialog sind daher normal – siehe
[Bildanalyse und Mediathek](Bildanalyse-und-Mediathek#einzelnes-bild-analysieren).

### Die Massenaktion fehlt

Sie steht nur in der **Listenansicht** der Mediathek im Dropdown *Mehrfachaktionen* und erfordert
`upload_files`.

### Metadaten in der falschen Sprache

Die Sprache folgt der Sprache deines Benutzerprofils. Siehe
[Prompts](Prompts#sprachnamen-für-language).

---

## Updates

Siehe [Updates](Updates#update-wird-nicht-angezeigt).
