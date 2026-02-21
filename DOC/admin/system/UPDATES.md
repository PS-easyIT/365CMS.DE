# Update Management
**Datei:** `admin/updates.php`

Zentrales Update-Center für das CMS.

## Quellen
- **Core:** Updates via GitHub API (`PS-easyIT/WordPress-365network`).
- **Plugins/Themes:** Updates via `plugin.json` / `theme.json` Metadaten.

## Prozess

### 1. Check
- Prüft regelmäßig auf neue Versionen.
- Vergleicht lokale Version mit Remote-Tags/Releases.

### 2. System-Check
- Prüft vor dem Update die Kompatibilität (PHP-Version, Extensions).
- Warnt bei fehlenden Schreibrechten.

### 3. Installation
1. Wartungsmodus aktivieren.
2. Backup erstellen (optional aber empfohlen).
3. Paket herunterladen und entpacken.
4. Dateien überschreiben.
5. Datenbank-Migrationen ausführen (`install.php` Logik).
6. Wartungsmodus deaktivieren.
