# Changelog

Die maßgebliche Fassung steht im [README](https://github.com/mrclksr2409/TextTune-AI#changelog).
Für Versionen vor 1.0.5 gibt es keinen gepflegten Changelog.

## 1.2.1 — 2026-10-10

- **Neu:** Link **Wiki** in der Plugin-Zeile unter **Plugins → Installierte Plugins**.
- **Geändert:** Die Autor-URL zeigt auf das GitHub-Profil.

## 1.2.0 — 2026-10-09

- **Neu:** Beta-Update-Kanal – Option **Einstellungen → Updates → Beta-Updates**. Ist sie aktiv, lädt
  der Updater Updates vom Branch `beta` statt `main`.
- **Geändert:** Beim Umschalten des Update-Kanals wird der zwischengespeicherte Update-Status
  verworfen, damit die nächste Prüfung sofort den gewählten Branch verwendet.

## 1.1.0 — 2026-10-09

- **Geändert:** Einheitliches Admin-Design – die Einstellungsseite nutzt das gebündelte Designsystem
  WP-Backend UI 1.0.2 (`lib/wp-backend-ui/`): Seitenkopf mit Icon und Version, moderne Tabs,
  Einstellungen als Karten, überarbeitete Eingabefelder und Buttons.
- **Geändert:** Tab-Wechsel (Einstellungen / Prompts / Bilderkennung) und der Anzeigen-Button des
  API-Schlüssels kommen von WP-Backend UI (`data-wpb-tabs`, `data-wpb-reveal`); nach dem Speichern
  kehrt die Seite weiterhin zum aktiven Tab zurück.
- **Geändert:** Die Mediathek-Styles nutzen die gemeinsamen `--wpb-*`-Design-Tokens, mit den
  bisherigen WordPress-Farben als Rückfall.
- **Entfernt:** `assets/css/texttune-admin.css` (vollständig durch WP-Backend UI abgedeckt).

## 1.0.5 — 2026-10-03

- **Geändert:** Updates kommen direkt vom Branch `main`; GitHub-Releases und Tags werden ignoriert.
- **Geändert:** Gebündelter Plugin Update Checker auf v5.7 aktualisiert.
