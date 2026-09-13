# 365CMS – Projektdokumentation | Abschnitt: Release-Pakete 3.4.00
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

This release directory documents the distributable `3.4.00` packages. The update and full packages contain deployable code only; installation-specific configuration, uploads, caches, logs and backups are intentionally excluded.

## Deutsch

Dieses Release-Verzeichnis dokumentiert die auslieferbaren Pakete `3.4.00`. Update- und Vollpaket enthalten ausschließlich auslieferbaren Code; installationsspezifische Konfiguration, Uploads, Cache, Logs und Backups sind bewusst ausgeschlossen.

- `365CMS-3.4.0-update.zip`: vollständiges Core-Updatepaket für den automatischen Core-Updater. Enthält den austauschbaren CMS-Code, aber bewusst keine installationsspezifische Konfiguration, Uploads, Caches, Logs oder Backups.
- `365CMS-3.4.0-full.zip`: sauberes Gesamtpaket für eine Neuinstallation. Nach dem Entpacken `CMS/install.php` aufrufen.
- `365CMS-3.4.0-update.json`: Update-Manifest inklusive SHA-256 für das Updatepaket.
- `365CMS-3.4.0-SHA256SUMS.txt`: Prüfsummen beider ZIP-Dateien.

## Bereitstellung des automatischen Updates

Das Updatepaket muss unter folgendem Pfad erreichbar sein:

`https://365cms.de/marketplace/core/365cms/365CMS-3.4.0-update.zip`

Die Datei `CMS/marketplace/core/365cms/update.json` muss am selben Marketplace-Pfad liegen. Nach einem Core-Update den Adminbereich neu laden und unter **Admin → Updates** bei Bedarf das idempotente Datenbankschema-Update auf `v22` ausführen.

Die automatische Installation setzt eine gültige SHA-256-Prüfsumme im Manifest voraus; Pakete ohne Prüfsumme werden bewusst nur zur manuellen Installation angeboten. Der Core-Swap bewahrt `config/`, `config.php`, `uploads/`, `cache/`, `logs/` und `backups/`. Bei einer unvollständigen automatischen Wiederherstellung bleibt das Recovery-Verzeichnis erhalten und wird im CMS-Log dokumentiert.
