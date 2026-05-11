# 365CMS – Offene Nice-to-haves · Konsolidierte Restliste
> **Stand:** 2026-05-11 | **Quelle:** PRUEF-CHECKLISTE.md | **Scope:** ohne Plugins und ohne weitere Theme-Erweiterungen

## Zweck

Diese Liste bündelt alle noch offenen Nice-to-haves aus der zentralen Prüfliste in eine
arbeitsfähige Reihenfolge. Plugin-spezifische Ausbauten (Bereich 13) und Theme-Marketplace-/
Theme-System-Erweiterungen (Bereich 8 außerhalb des Webbaukastens) sind bewusst
ausgeklammert. Reihenfolge folgt dem Wirkungspfad: zuerst Inhalte, dann Betrieb, dann
querschnittliche Komfortfunktionen.

Nachprüfung 2.9.754: Die zuletzt umgesetzten Nice-to-haves ab 2.9.725 wurden erneut mit Fokus auf bekannte Fehler, unvollständige Übernahmepfade, Security-/Token-Verträge, Best Practice und Performance geprüft. Konkret wurde der 404-Monitor-Übernahmeflow nachgehärtet, damit ungelöste 404-Logs wieder als neue Redirect-Regel gespeichert werden und keine 404-Log-ID als Redirect-ID missverstanden wird. Zusätzlich bleibt das Performance-Sicherheitsnetz auf reinen GET-Seiten fail-soft, weil Rollback-Verzeichnisse erst bei tatsächlichen Snapshot-Mutationen angelegt werden. Die SEO-Audit-Datenquelle ist nun serverseitig begrenzt, damit Dashboard, Trend-Live-Fallback und Broken-Link-Report auch bei großen Inhaltsbeständen nicht ungebremst alle Seiten und Beiträge synchron analysieren. Live-Log-Nacharbeit: MariaDB-kritische `SHOW TABLES LIKE ?`-Prüfungen wurden auf PDO-quotierte read-only Checks umgestellt, fehlende SEO-Trendtabellen erzeugen im Dashboard keine vorbereiteten SELECT-Fehler mehr, Paket-Historien nutzen ein robustes LIKE-Escape-Zeichen und die Medienbibliothek schützt ihre View-Helper gegen doppelte Includes.

---

## 1. Medienverwaltung – Restausbau

- [x] **Erweiterte Mediensuche mit gespeicherten Filtern**
  - [x] Filter-Presets pro Admin-Benutzer speicherbar (Typ, Kategorie, Datum, Verwendung, Größe)
  - [x] Permalink-Filter über Query-String, damit Filterzustände teilbar sind
- [x] **Bulk-Tagging und Bulk-Kategorisierung**
  - [x] Auswahl in Listen-/Grid-Ansicht, Aktion über bestehende Bulk-Toolbar
  - [x] Audit-Eintrag pro Bulk-Aktion mit Treffer- und Fehlerzahlen
- [x] **Verwaiste Medien erkennen**
  - [x] Read-only Liste „nirgends verwendet seit X Tagen" auf Basis der `MediaUsageService`-Daten
  - [x] Vorschlag zur manuellen Prüfung, kein automatisches Löschen
  - [x] Alt-Text-Bulk-Editor für SEO-/Accessibility-Aufräumen

## 2. SEO – offene Komfortfunktionen

- [x] **Snippet-/SERP-Preview pro Inhalt und global**
  - [x] Live-Vorschau im Editor für Google-Desktop, Google-Mobile und Social-OG
  - [x] Globaler Preview-Modus für Startseite, Archive und Taxonomien
- [x] **Broken-Link-Prüfung**
  - [x] Geplanter Cron-Lauf über Inhalte, Sitemap und Redirect-Manager
  - [x] Read-only Übersicht mit Treffer pro Quelle, Wiederholungsoption, Ignore-Liste
- [x] **Automatische SEO-Hinweise direkt im Editor**
  - [x] Realtime-Checks für Title, Description, H1-Eindeutigkeit, Keyphrase, Bild-Alt-Texte
  - [x] Hinweis-Badges nicht blockierend, nur empfehlend
- [x] **Trendansicht für 404, Redirects und SEO-Score**
  - [x] Sparkline-Karten im SEO-Dashboard
  - [x] Datenquelle: aggregierte Werte aus Redirect-Manager und 404-Monitor

## 3. Performance – Sicherheitsnetze für Massenoperationen

- [x] **Dry-Run und Rollback für Massenoptimierungen**
  - [x] Vorschau betroffener Datensätze pro Optimierungsaktion (Cache-Purge, Bildkonvertierung, DB-Wartung)
  - [x] Snapshot vor Ausführung, Rollback-Aktion innerhalb eines Zeitfensters
- [ ] **Historie der Performance-Maßnahmen**
  - [ ] Read-only Tabelle mit Zeitpunkt, Aktion, Auslöser, Ergebnis und Dauer
  - [ ] Anbindung an `audit_log` analog zur Update-Historie in `/admin/cms-logs`
- [ ] **Kapazitätswarnungen vor Optimierungsjobs**
  - [ ] Pre-Check für freien Speicher, Last und parallel laufende Cron-Jobs
  - [ ] Bestätigungsdialog mit konkreten Werten statt pauschalem „sind Sie sicher"

## 4. Recht – DSGVO-Workflow vervollständigen

- [ ] **Vorlagen / Profile für Rechtstexte**
  - [ ] Mitgelieferte DACH-Profile für Impressum, Datenschutz, Widerruf, AGB-Skelett
  - [ ] Versionierung pro Vorlage, Anwendung pro Legal-Site einzeln möglich
- [ ] **Fristen- und Bearbeitungsstatus für Datenschutzanfragen**
  - [ ] Eingegangen → in Bearbeitung → erledigt/abgelehnt mit Pflichtfrist
  - [ ] Warnung bei näherrückender Frist (Default 30 Tage), Eskalation an Admin-Mail
- [ ] **Prüfung auf fehlende Pflichtseiten im Dashboard**
  - [ ] Kontextuelle Warnung im Hauptdashboard mit Deep-Link zu Legal Sites
  - [ ] Mindestens Impressum, Datenschutz, Cookie-Hinweis als Pflichtprüfung

## 5. Sicherheit – proaktive Härtung

- [ ] **Simulationsmodus für Firewall-Regeln**
  - [ ] Neue Regel zunächst nur loggen, nicht blockieren
  - [ ] Read-only Treffervorschau über X Stunden, dann scharfschalten
- [ ] **Alarmierung bei sicherheitsrelevanten Ereignissen**
  - [ ] Schwellenwert-basierte Mail-Alerts für Login-Brute-Force, AntiSpam-Spitzen, Firewall-Blocks
  - [ ] Wiederverwendung der bestehenden Monitoring-Mail-Pipeline
- [ ] **Sicherheitsbaseline / Härtungsprofil pro Umgebung**
  - [ ] Profile „Entwicklung", „Staging", „Produktion" mit empfohlenen Einstellungen
  - [ ] Diff-Ansicht zwischen aktivem Zustand und Profil, Anwendung optional

## 6. System & Doku – Konfigurationsdisziplin

- [ ] **Konfigurations-Diff vor dem Speichern**
  - [ ] Side-by-Side-Vergleich „aktuell vs. neu" für alle Settings-Tabs
  - [ ] Hervorhebung sicherheitsrelevanter Felder (Auth, Security, Mail, AI-Provider)
- [ ] **Backup-Validierung / Restore-Check im Trockentest**
  - [ ] Hash-Verifikation der Sicherung, Probe-Lesen der wichtigsten Tabellen
  - [ ] Optionaler Restore in temporäre Datenbank, Vergleichsbericht als read-only Ergebnis
- [ ] **Update-Vorabprüfung auf Abhängigkeiten und Schreibrechte**
  - [ ] PHP-Version, Erweiterungen, Disk-Space, Schreibrechte für `cache/`, `backups/`, `logs/`, `assets/`
  - [ ] Blockierender Pre-Flight-Check mit klarer Anweisung pro Befund

## 7. Diagnose – Beobachtbarkeit ausbauen

- [ ] **Trendhistorie für Response-Time, Cron und Speicherverbrauch**
  - [ ] Aggregation über 24 h, 7 d, 30 d mit Sparklines
  - [ ] Datenquelle: bestehende Monitoring-Sammler, Persistenz in eigener Trend-Tabelle
- [ ] **Export/Download für Diagnoseberichte**
  - [ ] Bündelt Systeminfo, Health-Check, letzte Logs, Asset-Status, Cron-Status als ZIP
  - [ ] Sensible Werte (Keys, DB-Passwort, Mail-Credentials) werden serverseitig redacted
- [ ] **Sammelansicht für kritische Systemwarnungen**
  - [ ] Eine Seite mit allen aktiven Warnungen aus Performance, Security, Diagnose, Updates, Recht
  - [ ] Direkt-Action pro Warnung (lösen, ignorieren mit Begründung, später erinnern)
- [ ] **Sprechende Benutzeranzeige in der Update-Historie**
  - [ ] User-ID auflösen auf `display_name` plus Rolle, Fallback auf ID bei gelöschten Benutzern
  - [ ] Konsistent in `/admin/cms-logs` und Update-Center

## 8. Cross-Bereich · Inhalte ↔ Medien ↔ SEO

- [ ] **Featured-Image-Konsistenz prüfen**
  - [ ] Read-only Liste der Inhalte mit fehlendem oder gebrochenem Featured Image
  - [ ] Vorschlag zur Direktauswahl aus der Medienbibliothek
- [ ] **SEO-Felder vs. globale Templates**
  - [ ] Erkennung, wenn lokale Felder das globale Template ungewollt überschreiben
  - [ ] Hinweis im Editor mit Option „auf globalen Default zurücksetzen"
- [ ] **Kategorie-/Tag-Filter und Redirects gemeinsam pflegen**
  - [ ] Ein Verwaltungspfad für Slug-Änderungen, der Redirects automatisch erzeugt und alte Filter-Links auflöst
  - [ ] Bereits in 2.9.617 grundgelegt, hier als sichtbare Admin-Funktion finalisieren

## 9. Cross-Bereich · Benutzer ↔ Rollen ↔ Gruppen ↔ Member

- [ ] **Wirkungsvorschau bei Rollenänderungen**
  - [ ] Anzeige, welche Member-Bereiche, Plugin-Widgets und Pakete sich für einen Benutzer ändern
  - [ ] Vor dem Speichern als read-only Diff
- [ ] **Gruppen-/Paketbezüge sichtbar machen**
  - [ ] Pro Benutzer und pro Gruppe eine Zeile mit aktiven Paketen, Member-Modulen und ablaufenden Verträgen
  - [ ] Reduziert Suchaufwand bei Support-Fällen
- [ ] **Profilfeld-Kompatibilität bei Auth-Settings-Änderungen**
  - [ ] Pflichtfeld-Änderungen warnen vorab, welche Benutzer dadurch unvollständig werden
  - [ ] Optionaler Onboarding-Re-Trigger für betroffene Benutzer

