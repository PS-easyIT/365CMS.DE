# 365CMS – Backup & Restore

Kurzbeschreibung: Lokale Sicherungen, Datenbank-Backups und Verwaltungsabläufe für Wiederherstellung und Bereinigung.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Das Backup-Modul wird aktuell über `CMS/admin/backups.php` bereitgestellt und verwendet `CMS\Services\BackupService`.

Der Standard-Speicherpfad des Services ist `CMS/backups/`.

---

## Typische Aktionen

| Aktion | Bedeutung |
|---|---|
| `create_full` | vollständiges Backup erzeugen |
| `create_db` | reines Datenbank-Backup erzeugen |
| `delete` | vorhandenes Backup löschen |

Die Oberfläche listet vorhandene Backups auf und erlaubt deren Verwaltung.

---

## Dokumentationshinweis

Verweise auf `admin/backup.php` sind veraltet. Die aktuelle Referenz ist `/admin/backups`.
