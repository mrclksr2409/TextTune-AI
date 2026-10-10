# Updates

TextTune AI aktualisiert sich über den mitgelieferten
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (Version 5.7) direkt aus
dem GitHub-Repository [mrclksr2409/TextTune-AI](https://github.com/mrclksr2409/TextTune-AI) – wie ein
Plugin aus dem offiziellen Verzeichnis.

## Stabile Updates (Standard)

WordPress prüft den **`main`-Branch**: Steht dort im Plugin-Header eine höhere Versionsnummer als
installiert, erscheint das Update unter **Dashboard → Aktualisierungen** und in der Plugin-Liste und
lässt sich mit einem Klick installieren (oder automatisch, wenn Auto-Updates für das Plugin aktiviert
sind).

GitHub-**Releases und Tags werden ignoriert** – maßgeblich ist allein der aktuelle Stand des Branches.

## Beta-Updates

**Einstellungen → TextTune AI → Einstellungen → Updates → Beta-Updates**

Mit dem Häkchen *„Beta-Versionen installieren (Branch „beta“ statt „main“)“* folgt das Plugin dem
**`beta`-Branch**. Jede höhere Versionsnummer dort wird als Update angeboten. Beta-Versionen enthalten
neue Funktionen vor dem offiziellen Release und können Fehler enthalten – sinnvoll für Testseiten,
**nicht** für Produktivseiten.

Beim Umschalten verwirft TextTune AI den zwischengespeicherten Update-Status (Option
`external_updates-texttune-ai` und den WordPress-Transient `update_plugins`), damit die nächste Prüfung
sofort den gewählten Branch liest.

**Zurück zu stabil:** Häkchen entfernen und speichern. Ein Downgrade findet nicht statt – die Seite
bleibt auf ihrer Beta-Version, bis auf `main` eine **höhere** Versionsnummer als die installierte Beta
erscheint.

## Vor einem Update

- Einstellungen und API-Schlüssel bleiben bei einem Update erhalten.
- Den Browser-Cache musst du nicht leeren: Skripte und Styles tragen die Versionsnummer.

## Änderungen nachlesen

Siehe [Changelog](Changelog) bzw. den Abschnitt *Changelog* im
[README](https://github.com/mrclksr2409/TextTune-AI#changelog).

## Update wird nicht angezeigt

1. **Dashboard → Aktualisierungen → Erneut prüfen** klicken.
2. Der Server muss `github.com` und `api.github.com` erreichen können.
3. GitHub begrenzt anonyme API-Anfragen; bei vielen Seiten hinter einer IP kann die Prüfung
   vorübergehend scheitern – später erneut versuchen.
4. Bei aktivem Beta-Kanal: Ist die Version auf `beta` überhaupt höher als die installierte?
5. Sind zwei Kopien des Plugins installiert, lädt nur eine – siehe
   [Fehlerbehebung](Fehlerbehebung#es-sind-zwei-kopien-des-plugins-installiert).
