# 365CMS – Security-Audit

Kurzbeschreibung: Dokumentiert die Admin-Seite für manuelle Sicherheitsprüfungen, KPI-Auswertung und Audit-Log-Einsicht.

Letzte Aktualisierung: 2026-05-02 · Version 2.9.248

**Admin-Route:** `/admin/security-audit`

---

## Technische Einordnung

| Baustein | Datei |
|---|---|
| Entry Point | `CMS/admin/security-audit.php` |
| Modul | `CMS/admin/modules/security/SecurityAuditModule.php` |
| View | `CMS/admin/views/security/audit.php` |

Die Seite prüft Admin-Rechte, verarbeitet POST-Aktionen CSRF-geschützt und rendert anschließend Prüfstatus und Audit-Log.

---

## Verfügbare Aktionen

| Aktion | Bedeutung |
|---|---|
| `run_audit` | Prüfungen erneut ausführen und Ergebnis protokollieren |
| `clear_log` | ältere Audit-Log-Einträge bereinigen |

Als CSRF-Action-Slug wird `admin_sec_audit` verwendet.

---

## Prüfkategorien im aktuellen Stand

Das Modul bewertet derzeit unter anderem:

- HTTPS-Aktivierung
- PHP-Version
- Dateiberechtigungen von `config.php`
- verbliebene Installer-Datei `install.php`
- Status von `CMS_DEBUG`
- Schutz des Upload-Verzeichnisses via `.htaccess`
- Passwort-Policy-Verfügbarkeit
- CSRF-Token-System
- nonce-basierte CSP, Trusted Types, HSTS und `.htaccess`-Fallback
- Runtime-Verdrahtung von Firewall und AntiSpam
- Fremdassets in Public-Runtime und Editor.js-Assetliste
- Alter vorhandener Backups
- auffällige Admin-Passwort-Hashes

Die Ergebnisse werden je Check als `ok`, `warning` oder `critical` dargestellt.

---

## KPI-Karten und Anzeige

Die View zeigt vier kompakte Kennzahlen:

- Prüfungen gesamt
- bestanden
- Warnungen
- kritisch

Darunter folgt eine tabellarische Liste aller Checks mit Status-Badge und Detailtext.

---

## Audit-Log

Zusätzlich werden bis zu 50 Einträge aus `audit_log` angezeigt. Dazu gehören Datum, Aktion, Benutzerbezug, Details, Kategorie, Severity und IP-Adresse.

Das Modul schreibt selbst protokollierte Einträge über `CMS\AuditLogger`, etwa wenn ein Audit manuell gestartet oder alte Logs bereinigt werden.

---

## Hinweise für Betrieb und Dokumentation

- Die Seite ersetzt keine externe Härtungsanalyse oder einen Penetrationstest.
- Sie ist als operative Frühwarn- und Kontrolloberfläche gedacht.
- Das Audit erkennt typische Fehlkonfigurationen, aber keine vollständige Supply-Chain- oder Dependency-SBOM-Prüfung.
- Aussagen über Sicherheitslage sollten immer zusammen mit [../../core/SECURITY.md](../../core/SECURITY.md) und den Audit-Berichten unter `DOC/audits/` gelesen werden.

---

## Verwandte Dokumente

- [README.md](README.md)
- [ANTISPAM.md](ANTISPAM.md)
- [FIREWALL.md](FIREWALL.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)