# 365CMS – Backup & Restore

Kurzbeschreibung: Lokale Sicherungen, Datenbank-Backups und Verwaltungsabläufe für Wiederherstellung und Bereinigung.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.768

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

Seit `2.9.767` ergänzt `/admin/backups` zusätzlich eine echte Backup-Validierung per POST-/PRG-Flow. Pro Sicherung können Integritätschecks, SHA-256-Prüfsummen, ein Probe-Lesen wichtiger Tabellen aus dem SQL-Dump und optional ein Restore-Dry-Run in eine temporäre Datenbank durchgeführt werden. Der Bericht bleibt read-only sichtbar im Admin, erzeugt keine Token-URLs und führt keine neue öffentliche GET-Mutation ein.

Seit `2.9.768` erzeugt und verarbeitet derselbe Backup-Service große SQL-Dumps robuster im Streaming-Verfahren statt sie vollständig im Arbeitsspeicher zu sammeln. Das reduziert Fehler bei größeren Datenbanken sowohl beim Erstellen als auch beim Restore merklich; zusätzlich werden Servicefehler im Admin jetzt konkreter zurückgemeldet.

Neue Prüfpfade:

| Aktion | Bedeutung |
|---|---|
| `validate` | prüft Manifest, Prüfsummen, SQL-Dump und Datei-Archiv |
| `validate` + `include_restore_dry_run=1` | führt zusätzlich einen temporären Restore-Test in eine Wegwerf-Datenbank aus |

---

## Dokumentationshinweis

Verweise auf `admin/backup.php` sind veraltet. Die aktuelle Referenz ist `/admin/backups`.
