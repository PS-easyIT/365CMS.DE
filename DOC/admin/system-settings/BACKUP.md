# Backup-Verwaltung


Das Backup-Modul ermöglicht die manuelle und automatisierte Sicherung der gesamten 365CMS-Installation inklusive Datenbank und Medien.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Backup erstellen](#2-backup-erstellen)
3. [Backup wiederherstellen](#3-backup-wiederherstellen)
4. [Automatische Backups](#4-automatische-backups)
5. [Backup-Speicherung](#5-backup-speicherung)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin/backup.php`

Backups bestehen aus zwei Komponenten:
- **Datenbank-Backup:** Vollständiger SQL-Dump aller CMS-Tabellen
- **Datei-Backup:** ZIP-Archiv des `/uploads/`-Verzeichnisses (Medien)

**Empfehlung:** Tägliche Datenbank-Backups, wöchentliche Vollbackups.

---

## 2. Backup erstellen

### Backup-Typen

| Typ | Inhalt | Typische Größe | Dauer |
|---|---|---|---|
| **Vollständig** | Datenbank + Uploads | Variabel | Lang |
| **Nur Datenbank** | SQL-Dump | Klein (KB–MB) | Schnell |
| **Nur Dateien** | /uploads/ als ZIP | Variabel | Mittel |
| **Konfigurations-Backup** | /config/ Dateien | Sehr klein | Sofort |

### Backup starten
1. Backup-Typ wählen
2. Optional: Backup-Bezeichnung eingeben (z.B. „Vor Update 0.26.13")
3. **„Backup erstellen"** klicken
4. Fortschrittsanzeige (für große Installationen: ggf. mehrere Minuten)
5. Download-Button erscheint nach Abschluss

**Ausgabe-Formate:**
- Datenbank: `.sql.gz` (gzip-komprimierter SQL-Dump)
- Dateien: `.tar.gz` oder `.zip`

---

## 3. Backup wiederherstellen

> ⚠️ **Achtung:** Überschreibt die aktuelle Installation vollständig!

### Datenbank-Restore
1. Backup-Datei (`.sql` oder `.sql.gz`) hochladen
2. Datenbankverbindung wird kurz getrennt
3. Alle existierenden CMS-Tabellen werden gelöscht
4. Backup wird eingespielt
5. System meldet Erfolg/Fehler

### Datei-Restore
1. ZIP/TAR.GZ hochladen
2. Ziel-Verzeichnis wählen (`/uploads/` oder komplett)
3. Überschreiben-Option: Nur neue Dateien / Alle überschreiben

**Empfehlung:** Vor dem Restore immer ein aktuelles Backup des Ist-Zustands erstellen!

---

## 4. Automatische Backups

Konfigurierbar unter `admin/backup.php` → Reiter „Zeitpläne":

| Einstellung | Optionen |
|---|---|
| **Häufigkeit** | Täglich, Wöchentlich, Monatlich |
| **Uhrzeit** | Uhrzeit (empfohlen: nachts, z.B. 03:00 Uhr) |
| **Backup-Typ** | Datenbank, Dateien, Vollständig |
| **Aufbewahrung** | Anzahl Backups behalten (älteste werden gelöscht) |
| **E-Mail-Bericht** | Admin-E-Mail bei Erfolg/Fehler |

**Voraussetzung:** Server-Cron muss konfiguriert sein:
```bash
# /etc/crontab oder via cPanel
0 3 * * * php /pfad/zu/365cms/cms/cron.php backup
```

---

## 5. Backup-Speicherung

### Lokale Speicherung (Standard)
- **Pfad:** `/cms/backup/` (außerhalb des Web-Roots empfohlen)
- Backups sind nicht via URL erreichbar
- Auflistung im Admin mit Download-Links

### Externe Speicherung (geplant)
- **FTP/SFTP-Upload:** Automatischer Upload nach Backup-Erstellung
- **Amazon S3:** Speicherung in S3-Bucket
- **Konfiguration:** Zugangsdaten in `admin/settings.php` → „Cloud-Backup"

⚠️ Backup-Dateien enthalten sensible Daten (Passwort-Hashes, E-Mails) – sicher aufbewahren!

---

## 6. Technische Details

**Service:** `CMS\Services\BackupService`

```php
$backup = BackupService::instance();

// Datenbank-Backup erstellen
$path = $backup->createDatabaseBackup([
    'compress'  => true,        // gzip-Komprimierung
    'label'     => 'vor-update', // Optional
]);
// Returns: /cms/backup/2026-02-21_03-00_db_vor-update.sql.gz

// Datei-Backup
$path = $backup->createFilesBackup([
    'source'   => UPLOADS_PATH,
    'compress' => true,
]);

// Backup wiederherstellen
$backup->restoreDatabase($sqlFilePath);
```

**Hooks:**
```php
do_action('cms_backup_created', $backupPath, $type, $size);
do_action('cms_backup_restored', $backupPath, $type);
do_action('cms_backup_failed', $type, $errorMessage);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
