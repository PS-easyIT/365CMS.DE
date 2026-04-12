# 365CMS – Backup & Restore

Kurzbeschreibung: Lokale Sicherungen, Datenbank-Backups und Verwaltungsabläufe für Wiederherstellung und Bereinigung.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

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

Die Oberfläche listet vorhandene Backups auf und erlaubt deren Verwaltung.

---

## Dokumentationshinweis

Verweise auf `admin/backup.php` sind veraltet. Die aktuelle Referenz ist `/admin/backups`.
