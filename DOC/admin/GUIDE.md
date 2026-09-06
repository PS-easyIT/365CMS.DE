# 365CMS – Admin-Handbuch

> **Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Zielgruppe:** Administratoren, Redakteure und Support

Dieses Handbuch beschreibt die wichtigsten Bedienabläufe im aktuellen 365CMS-Admin. Die genaue Sichtbarkeit hängt von Rolle, Capability, aktivierten Core-Modulen und Plugin-Registrierungen ab.

## Inhaltsverzeichnis

- [Anmelden und sicher arbeiten](#anmelden-und-sicher-arbeiten)
- [Dashboard](#dashboard)
- [AI Services](#ai-services)
- [Benutzer und Gruppen](#benutzer-und-gruppen)
- [Seiten, Beiträge und Kommentare](#seiten-beiträge-und-kommentare)
- [Medien](#medien)
- [Member Dashboard](#member-dashboard)
- [Pakete, Abos und Bestellungen](#pakete-abos-und-bestellungen)
- [Themes und Design](#themes-und-design)
- [SEO und Redirects](#seo-und-redirects)
- [Performance](#performance)
- [Recht und Datenschutz](#recht-und-datenschutz)
- [Sicherheit](#sicherheit)
- [Plugins und Marketplace](#plugins-und-marketplace)
- [System, Backup und Updates](#system-backup-und-updates)
- [Diagnose und Monitoring](#diagnose-und-monitoring)
- [Post-Request-Checkliste](#post-request-checkliste)

## Anmelden und sicher arbeiten

1. `/admin` öffnen und anmelden.
2. Nur Funktionen verwenden, die in der Sidebar sichtbar sind.
3. Vor kritischen Änderungen ein Backup- oder Rollback-Szenario klären.
4. Formulare normal speichern; Tokens nicht kopieren oder mehrfach neu erzeugen.
5. Nach jeder schreibenden Aktion die Erfolgsmeldung und den Zielzustand prüfen.

## Dashboard

**Route:** `/admin`

Das Dashboard bündelt KPIs, Arbeits-Widgets, Warnungen und persönliche Favoriten. Widgets können, sofern freigeschaltet, per Drag-and-Drop oder Pfeil-Fallback angeordnet werden. Bei leeren Datenbeständen erscheinen erklärende Empty States statt erfundener Kennzahlen.

## AI Services

**Routen:** `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, `/admin/ai-settings`

### Translation

1. Seite oder Beitrag im Editor öffnen.
2. Ausgangstext prüfen und als Entwurf sichern.
3. Übersetzung nach Englisch starten.
4. Batch-/Warnungsstatistik abwarten.
5. Preview/Diff prüfen.
6. Übersetzung nur nach fachlicher Prüfung in das EN-Feld übernehmen.

### Content Creator

1. Content-Creator-Seite öffnen.
2. Aufgabe `Summary`, `Outline` oder `CTA` wählen.
3. Briefing und optionalen Kontext eingeben.
4. Tonalität und Locale angeben.
5. Entwurf erzeugen und manuell prüfen.

Der Entwurf wird nicht automatisch gespeichert oder veröffentlicht.

### SEO Creator und Einstellungen

SEO-Entwürfe werden aus dem Haupttext erzeugt. Titel, Slug, Canonical- und Bild-URLs werden nicht automatisch durch AI geändert. Provider, Feature-Gates, Prompt-Vorlagen, Logging, Quotas und Healthchecks befinden sich unter `/admin/ai-settings`.

Details: [AI Services](../ai/AI-SERVICES.md).

## Benutzer und Gruppen

### Benutzer

**Route:** `/admin/users`

1. Benutzer öffnen oder neu anlegen.
2. Benutzername, E-Mail, Status und Rolle prüfen.
3. nur erforderliche Rechte vergeben.
4. speichern und Anmeldung/Capability bei Bedarf testen.

### Gruppen und Rollen

**Routen:** `/admin/groups`, `/admin/roles`, `/admin/rbac`

Gruppen bündeln Benutzer; Rollen und Capabilities bestimmen den Zugriff. Bei kritischen Rollen Änderungen mit der Wirkungsvorschau beziehungsweise Capability-Diff prüfen.

### Auth-Einstellungen

**Route:** `/admin/user-settings`

Passwort-, Session- und Authentifizierungseinstellungen nur mit dokumentiertem Änderungsgrund anpassen. Die aktuelle Passwort-Policy verlangt mindestens 12 Zeichen.

## Seiten, Beiträge und Kommentare

### Seite

**Route:** `/admin/pages`

1. Seite öffnen oder neu erstellen.
2. Titel und Slug prüfen.
3. Editor.js-Inhalt pflegen.
4. Auszug, SEO und Medien ergänzen.
5. Entwurf speichern, Vorschau prüfen und erst danach veröffentlichen.

### Beitrag

**Route:** `/admin/posts`

1. Titel, Inhalt, Auszug und Featured Image pflegen.
2. Kategorien und Tags auswählen.
3. SEO- und Lesbarkeitsprüfung beachten.
4. Entwurf, Vorschau oder Veröffentlichung ausführen.

### Kommentare

**Route:** `/admin/comments`

Kommentare moderieren, Spam prüfen und nur erforderliche Löschungen ausführen. Bei aktivem AntiSpam zusätzlich dessen zentrale Regeln prüfen.

## Medien

**Route:** `/admin/media`

Tabs:

- `?tab=featured` — Beitrags-/Site-Medien;
- `?tab=categories` — Medienkategorien;
- `?tab=settings` — Medieneinstellungen.

Unterstützt werden Filter, Presets, Kategorien, Tags, Alt-Texte, Bulk-Aktionen, Nutzungsanzeigen, Orphan-Prüfung, begrenzte Duplikaterkennung sowie chunkbasierte WebP-/Thumbnail-Jobs. Vor Löschung die angezeigten Verwendungen prüfen.

## Member Dashboard

**Route:** `/admin/member-dashboard`

Die Folgeseiten konfigurieren Runtime, Frontend-Module, Widgets, Design, Benachrichtigungen, Onboarding und Profilfelder. `?preview=1` ist eine read-only Vorschau und ändert keine Runtime-Einstellungen.

Wichtige Folgeseiten:

- `/admin/member-dashboard-general`
- `/admin/member-dashboard-frontend-modules`
- `/admin/member-dashboard-plugin-widgets`
- `/admin/member-dashboard-widgets`
- `/admin/member-dashboard-design`
- `/admin/member-dashboard-notifications`
- `/admin/member-dashboard-onboarding`
- `/admin/member-dashboard-profile-fields`

## Pakete, Abos und Bestellungen

| Route | Zweck |
|---|---|
| `/admin/packages` | Paketdefinitionen und Planparameter |
| `/admin/orders` | Bestellungen, Zuweisungen und Exporte |
| `/admin/subscription-settings` | globale Abo- und Standardpaketregeln |

Vertragsfristen, Renewal-Hinweise und Paketnutzung vor manuellen Änderungen prüfen. CSV-Exporte sicher behandeln.

## Themes und Design

| Route | Zweck |
|---|---|
| `/admin/themes` | Theme aktivieren |
| `/admin/theme-editor` | Farben, Typografie und Layout |
| `/admin/theme-explorer` | verfügbare Theme-Bereiche |
| `/admin/theme-marketplace` | Theme-Angebote |
| `/admin/menu-editor` | Navigation |
| `/admin/landing-page` | Landing-Page-Zuweisungen |
| `/admin/font-manager` | Fonts und Nutzung |
| `/admin/design-settings` | zentrale Designwerte |

Nach Aktivierung oder Designänderung Frontend, mobile Darstellung, Menüs und wichtige Templates prüfen.

## SEO und Redirects

| Route | Zweck |
|---|---|
| `/admin/seo-dashboard` | SEO-Überblick und Score |
| `/admin/analytics` | Analytics |
| `/admin/seo-audit` | Audit |
| `/admin/seo-meta` | globale Meta-Vorlagen |
| `/admin/seo-social` | Social-Fallbacks |
| `/admin/seo-schema` | strukturierte Daten |
| `/admin/seo-sitemap` | Sitemap und robots.txt |
| `/admin/seo-technical` | technisches SEO |
| `/admin/redirect-manager` | Weiterleitungen und Broken Links |

Nach Templateänderungen Vorschau für Desktop/Mobile und Social-Open-Graph prüfen. Redirects immer auf interne, validierte Ziele und mögliche Schleifen prüfen.

## Performance

**Routen:** `/admin/performance`, `/admin/performance-cache`, `/admin/performance-media`, `/admin/performance-database`, `/admin/performance-settings`, `/admin/performance-sessions`

Bei trägem System:

1. Gesamtstatus öffnen.
2. Cache-Status und Cache-Clear prüfen.
3. Medienjobs und Bildgrößen kontrollieren.
4. Datenbank-Wartung nur nach Backup ausführen.
5. Sessions und Performance-Settings auf auffällige Werte prüfen.

## Recht und Datenschutz

| Route | Aufgabe |
|---|---|
| `/admin/legal-sites` | Impressum, Datenschutz, AGB, Widerruf |
| `/admin/cookie-manager` | Consent- und Cookie-Regeln |
| `/admin/data-requests` | Auskunfts- und Löschanfragen |

Ablehnungen bei Datenschutzanfragen benötigen eine Begründung. Frühere Einzelrouten wie `data-access.php` und `data-deletion.php` sind Legacy-Kontext; die gebündelte Oberfläche ist führend.

## Sicherheit

| Route | Aufgabe |
|---|---|
| `/admin/antispam` | Honeypot, Mindestzeit und Blacklists |
| `/admin/firewall` | Regeln für IP, Bereiche und Muster |
| `/admin/security-audit` | Sicherheitsprüfung |

Änderungen an Firewall oder AntiSpam nach dem Speichern mit einem erlaubten und einem erwarteten blockierten Szenario testen.

## Plugins und Marketplace

**Routen:** `/admin/plugins`, `/admin/plugin-marketplace`

1. Pluginstatus prüfen.
2. Aktivierung/Deaktivierung nur mit kompatiblem Backup- und Rollbackplan.
3. Plugin-Unterseiten über die Sidebar öffnen.
4. Plugin-spezifische Dokumentation beachten.

## System, Backup und Updates

| Route | Aufgabe |
|---|---|
| `/admin/settings` | CMS-Konfiguration |
| `/admin/backups` | Voll- und Datenbankbackup, Download/Restore |
| `/admin/updates` | Core-, Theme- und Pluginupdates |
| `/admin/cms-logs` | Betriebs-, Update- und Auditlogs |
| `/admin/mail-settings` | Mail und Azure OAuth2 |

Vor Updates und Restore-Vorgängen Backup, Wartungsfenster, Speicherplatz und Rollback klären. Backups außerhalb des Webroots sichern.

## Diagnose und Monitoring

| Route | Aufgabe |
|---|---|
| `/admin/info` | Systeminformationen |
| `/admin/documentation` | lokale Dokumentationsansicht |
| `/admin/diagnose` | Diagnoseübersicht |
| `/admin/monitor-health-check` | lokale Health-Prüfungen |
| `/admin/monitor-response-time` | Antwortzeiten |
| `/admin/monitor-cron-status` | Cronstatus |
| `/admin/monitor-cron-runner` | Cron-Ausführung |
| `/admin/monitor-disk-usage` | Speicherplatz |
| `/admin/monitor-scheduled-tasks` | geplante Aufgaben |
| `/admin/monitor-assets` | Assetstatus |
| `/admin/monitor-email-alerts` | E-Mail-Warnungen |
| `/admin/monitor-warnings` | Warnungsübersicht |

## Post-Request-Checkliste

- Erfolgsmeldung gelesen?
- Zielzustand nach Redirect sichtbar?
- Keine unerwartete öffentliche Veröffentlichung?
- Cache oder Rewrite-Regeln betroffen?
- Audit-/Betriebslog plausibel?
- sensible Daten nicht versehentlich exportiert?
- Fachdokumentation oder Changelog erforderlich?
