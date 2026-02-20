# Backup Management

**Datei:** `admin/backup.php`

Das Backup-Modul ermöglicht die Sicherung der gesamten CMS-Installation inklusive Datenbank und Dateien.

## Funktionen

### 1. Backup erstellen
- **Vollständiges Backup:** Sichert Datenbank (.sql) und alle Dateien im `uploads/` Ordner.
- **Datenbank-Only:** Erstellt nur einen SQL-Dump der aktuellen Datenbank.
- **Dateien-Only:** Erstellt ein ZIP-Archiv des `uploads/` Ordners.

### 2. Wiederherstellung (Restore)
- Hochladen von Backup-Dateien.
- Einspielen bestehender Backups.
- **Achtung:** Überschreibt aktuelle Daten!

### 3. Zeitpläne (Geplant)
- Automatische Backups (täglich/wöchentlich).
- Speicherung auf externen Servern (FTP/S3) - *Zukünftiges Feature*.

## Technische Details
- **Speicherort:** `/wp-content/backups/` oder `/cms_content/backups/`.
- **Format:** ZIP-Komprimierung für Dateien, SQL für Datenbanken.
