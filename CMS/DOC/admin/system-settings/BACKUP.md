# 365CMS – Backup & Restore

Kurzbeschreibung: Lokale Sicherungen, Datenbank-Backups und Verwaltungsabläufe für Wiederherstellung und Bereinigung.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.628

---

## Überblick

Das Backup-Modul wird aktuell über `CMS/admin/backups.php` bereitgestellt und arbeitet mit `BackupsModule` sowie `CMS\Services\BackupService`.

Der Standard-Speicherpfad des Services ist `CMS/backups/`.

---

## Typische Aktionen

| Aktion | Bedeutung |
|---|---|
| `create_full` | vollständiges Backup erzeugen |
| `create_db` | reines Datenbank-Backup erzeugen |
| `restore` | gesicherten Stand wiederherstellen |
| `delete` | vorhandenes Backup löschen |
| Download | DB-Dumps und Datei-Archive gezielt herunterladen |

Die Oberfläche listet vorhandene Backups auf und erlaubt deren Verwaltung. Datenbank-Dumps und Datei-Archive können direkt aus dem Admin heruntergeladen werden. Vor jeder Wiederherstellung erstellt der Runtime-Pfad automatisch einen Rollback-Snapshot des aktuellen Zustands, bevor Datenbank und optionale Datei-Artefakte eingespielt werden.

---

## Dokumentationshinweis

Verweise auf `admin/backup.php` sind veraltet. Die aktuelle Referenz ist `/admin/backups`.
