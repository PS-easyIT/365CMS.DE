# Backup & Restore Workflow – 365CMS

> **Bereich:** Backup-System · **Version:** 1.6.14  
> **Service:** `core/Services/BackupService.php`  
> **Admin-Seite:** `admin/backup.php`

---

## Übersicht: Backup-Typen

| Typ | Enthält | Empfohlen für |
|---|---|---|
| **Vollbackup** | DB + Uploads + Themes + Plugins | Vor größeren Updates |
| **Datenbank-Backup** | Alle `cms_*` Tabellen + Daten | Täglich |
| **Datei-Backup** | `uploads/`, `themes/`, `plugins/` | Wöchentlich |
| **Konfigurations-Backup** | `config.php` (ohne Passwörter) | Bei Änderungen |

---

## Workflow 1: Manuelles Backup erstellen

### Via Admin-UI
1. Admin → `admin/backup.php` aufrufen
2. Backup-Typ wählen (DB / Dateien / Voll)
3. **Wichtig:** ⚠️ Kein `window.confirm()` – Modal-Bestätigung abwarten (nach Implementierung C-12)
4. Download-Link anklicken, Backup lokal speichern

### Via BackupService (programmatisch)
```php
$backup = new CMS\Services\BackupService();

// Datenbank-Backup
$path = $backup->createDatabaseBackup();
// → Gibt Pfad zur .sql.gz zurück, z.B. /uploads/backups/db_2026-02-22_140530.sql.gz

// Datei-Backup (Uploads-Verzeichnis)
$path = $backup->createFileBackup();
// → ZIP-Archiv aller Uploads

// Vollbackup
$path = $backup->createFullBackup();
```

---

## Workflow 2: Automatisches Backup (Cron)

**Status:** Tabelle `cms_cron_jobs` vorhanden – Cron-Jobs konfigurierbar.

```php
// Tägliches DB-Backup um 03:00 Uhr
// In config.php oder Admin-Einstellungen registrieren:
\CMS\Hooks::addAction('cms_cron_daily', function() {
    $backup = new \CMS\Services\BackupService();
    $path   = $backup->createDatabaseBackup();

    // Optional: Altes Backup löschen (> 7 Tage)
    $backup->pruneBackups(retainDays: 7);

    // Benachrichtigungs-E-Mail (wenn E-Mail-Plugin aktiv)
    \CMS\Hooks::doAction('backup_completed', $path);
});
```

**Cron-Job auf Server (empfohlen als Ergänzung):**
```bash
# /etc/cron.d/365cms
0 3 * * * www-data php /var/www/html/cms/index.php --action=cron_daily 2>/dev/null
```

---

## Workflow 3: Restore durchführen

### ⚠️ KRITISCH: Restore-Prozess ist destruktiv!

**Vor dem Restore:**
- [ ] Aktuellen Stand sichern (frisches Backup erstellen)
- [ ] Wartungsmodus aktivieren
- [ ] Zweiten Admin-Account sicherstellen (Fallback)

### Datenbank-Restore via CLI (empfohlen)

```bash
# .gz entpacken und importieren:
gunzip -c /pfad/zu/backup_2026-02-22.sql.gz | mysql -u DB_USER -p DB_NAME

# Oder direkt:
mysql -u DB_USER -p DB_NAME < /pfad/zu/backup_2026-02-22.sql
```

### Datenbank-Restore via Admin-UI

1. Admin → `admin/backup.php` → Tab "Wiederherstellen"
2. SQL-Datei hochladen oder aus gespeicherten Backups wählen
3. **Bestätigung via Modal** (Intent-Token + CSRF): "Ich verstehe, dass alle aktuellen Daten überschrieben werden"
4. Restore ausführen
5. Session erneuern (Re-Login erforderlich)

### Datei-Restore

```bash
# Uploads restaurieren:
cd /var/www/html/cms/
unzip -o backup_files_2026-02-22.zip -d uploads/
chown -R www-data:www-data uploads/
```

---

## Workflow 4: Notfall-Rollback (nach fehlgeschlagenem Update)

```
1. Wartungsmodus aktivieren:
   → Datei /cms/maintenance.html erstellen (Router leitet um)

2. Letztes Backup identifizieren:
   → ls -lt uploads/backups/ | head -5

3. DB-Restore (CLI):
   → mysql -u USER -p DB_NAME < backup_datum.sql

4. Theme/Plugin-Rollback (falls Datei-Backup vorhanden):
   → unzip backup_files.zip themes/ plugins/ -d /var/www/html/cms/

5. Wartungsmodus deaktivieren:
   → Datei /cms/maintenance.html löschen

6. Test:
   → Startseite laden, Admin-Login testen, Fehlerlog prüfen
```

---

## Backup-Aufbewahrung / Sicherheitspraktiken

```
AUFBEWAHRUNG:
✅ Täglich:   Letzte 7 Datenbank-Backups behalten
✅ Wöchentlich: Letzte 4 Vollbackups behalten
✅ Monatlich:  Letztes Backup 12 Monate aufbewahren

SPEICHERORTE:
✅ Lokal: /cms/uploads/backups/ (automatisch, schnell)
✅ Extern: Zweites Medium (SFTP, S3, Backblaze B2)
❌ NIEMALS: Backup nur an einem Ort!

SICHERHEIT:
✅ Backup-Dateien NICHT öffentlich erreichbar
   (.htaccess: Deny from all im backups/-Verzeichnis)
✅ DB-Backup enthält Passwörter → verschlüsselt aufbewahren
✅ Backup-Download via Admin: CSRF + isAdmin() Prüfung
```

**`.htaccess` für Backup-Verzeichnis:**
```apache
# /cms/uploads/backups/.htaccess
Require all denied
```

---

## Checkliste: Backup-System

```
TÄGLICH PRÜFEN:
[ ] Automatisches Backup ausgeführt? (Log prüfen)
[ ] Backup-Datei vorhanden und > 0 Bytes?

WÖCHENTLICH:
[ ] Restore-Test (auf Staging) aus aktuellem Backup
[ ] Alte Backups aufgeräumt?
[ ] Extern gesichertes Backup vorhanden?

VOR JEDEM UPDATE:
[ ] Manuelles Vollbackup erstellen
[ ] Backup lokal heruntergeladen (nicht nur Server)
[ ] Rollback-Plan schriftlich festgehalten
```

---

## Referenzen

- [admin/backup.php](../../CMS/admin/backup.php) – Admin-UI
- [core/Services/BackupService.php](../../CMS/core/Services/BackupService.php) – Service-Klasse
- [ROADMAP_FEB2026.md](../feature/ROADMAP_FEB2026.md) – C-12: Confirm-Modal für Backup/Restore
