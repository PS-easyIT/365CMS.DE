# 365CMS – Admin-Prüf-Checkliste
> **Stand:** 2026-05-09 | **Basis:** Laufzeit-Sidebar + Admin-Fachdoku | **Status:** Arbeitsdokument für Audit, Abnahme und Ausbau

## Zweck

Diese Checkliste dient als zentrale Arbeitsgrundlage, um den kompletten Adminbereich von 365CMS **systematisch, menüpunktweise und nachvollziehbar** zu prüfen.

Sie deckt ab:

- **Hauptbereiche** der Sidebar
- **Unterbereiche / Untermenüpunkte**
- **Kernfunktionen** je Unterbereich
- **wichtige Abhängigkeiten** zu anderen Modulen und Services
- **konkrete Prüfpunkte** für Audit, QA und Abnahme
- **Must-haves** als verbindliche Anforderungen
- **Nice-to-haves** als sinnvolle Ausbauoptionen

Die Sidebar in `CMS/admin/partials/sidebar.php` ist für die Menüstruktur führend. Für Detailfragen bleiben die Fachdokumente unter `CMS/DOC/admin/` maßgeblich.

---

## Empfohlene Abarbeitung

1. Zuerst die **globale Pflichtprüfung** einmal komplett durchgehen.
2. Danach die Hauptbereiche **von oben nach unten wie in der Sidebar** prüfen.
3. Pro Unterbereich immer dokumentieren:
   - Status
   - Fehlerbild
   - Reproduktionsweg
   - Abhängige Bereiche
   - offene Must-haves
   - optionale Nice-to-haves
4. Bei Schreibfunktionen immer zusätzlich prüfen:
   - CSRF
   - PRG-Redirect
   - Flash-/Session-Feedback
   - Logging/Audit-Trail
5. Bei Cross-Bereich-Funktionen immer Gegenprobe im Frontend oder im Zielbereich machen.

---

## Globale Pflichtprüfung für **alle** Admin-Seiten

### Architektur & Routing

- [ ] Route ist über die Sidebar erreichbar oder bewusst nur intern verlinkt.
- [ ] Entry-Point unter `CMS/admin/*.php` existiert und ist eindeutig zuständig.
- [ ] Fachlogik liegt im passenden Modul unter `CMS/admin/modules/`.
- [ ] Views werden über Wrapper/Module gerendert, nicht direkt aufrufbar.
- [ ] Legacy-Routen leiten korrekt um oder sind sauber stillgelegt.
- [ ] Query-Tab-Seiten wie `/admin/media?tab=...` oder `/admin/settings?tab=content` funktionieren stabil.

### Sicherheit

- [ ] `ABSPATH`-Guard vorhanden.
- [ ] Admin-Zugriff wird geprüft.
- [ ] Capability-/RBAC-Prüfung ist vorhanden, wo relevant.
- [ ] CSRF-Token wird bei jeder schreibenden Aktion geprüft.
- [ ] PRG-Pattern wird verwendet.
- [ ] Ausgaben sind escaped.
- [ ] Dateioperationen und Uploads sind serverseitig validiert.
- [ ] Kritische Änderungen werden geloggt oder im Audit-Trail festgehalten.

### Daten & Persistenz

- [ ] Prepared Statements statt SQL-String-Interpolation.
- [ ] Tabellenpräfix wird zentral bezogen.
- [ ] Fehlermeldungen sind admin-tauglich und leaken keine sensiblen Interna.
- [ ] Bulk-Aktionen sind transaktionssicher oder mindestens fail-soft umgesetzt.
- [ ] Erfolgreiche Teiloperationen erzeugen keinen nackten HTTP-500.

### UX & Betrieb

- [ ] Erfolgs-/Fehlhinweise sind verständlich.
- [ ] Leere Zustände sind sauber gelöst.
- [ ] Buttons, Tabs, Filter und Pagination funktionieren.
- [ ] Mobile/kleine Viewports brechen die Bedienung nicht.
- [ ] Lange Prozesse sind gegen Doppel-Submit geschützt.

### Dokumentation

- [ ] Route und Funktionsumfang sind in `CMS/DOC/admin/` dokumentiert.
- [ ] Besonderheiten, Legacy-Verträge und Abhängigkeiten sind dokumentiert.
- [ ] Neue Funktionen werden in der passenden Bereichsdoku ergänzt.

---

## Menü-Masterliste

| Hauptbereich | Unterbereiche |
|---|---|
| Dashboard | Dashboard |
| AI Services | AI Dashboard, Übersetzung, Content Creator, SEO Creator, Einstellungen |
| Seiten & Beiträge | Seiten, Beiträge, Kategorien, Tags, Kommentare, Inhaltsverzeichnis, Hub-Sites, Tabellen, Einstellungen |
| Medienverwaltung | Medien, Beitrags & Site Medien, Kategorien, Einstellungen |
| Benutzer & Gruppen | Benutzer, Gruppen, Rollen & Rechte, Einstellungen |
| Member Dashboard | Übersicht, Allgemein, Design & Farben, Frontend-Module, Dashboard Widgets, Plugin-Widgets, Profil-Felder, Benachrichtigungen, Mitglieder-Onboarding |
| Aboverwaltung | Pakete & Abo-Einstellungen, Bestellungen & Zuweisung, Einstellungen |
| Themes & Design | Theme-Verwaltung, Theme-Editor, Theme-Explorer, Theme-Menü, Landing Page, Font Manager, CMS Loginpage, Theme-Marketplace |
| SEO | SEO Dashboard, Analytics, SEO Audit, Meta-Daten, Social Media, Strukturierte Daten, Sitemap & robots.txt, Technisches SEO, Weiterleitungen, 404-Monitor |
| Performance | Übersicht, Cache-Verwaltung, Medien-Optimierung, Datenbank-Wartung, Performance-Einstellungen, Session-Verwaltung |
| Recht | Legal Sites, Cookie-Manager, Auskunft & Löschen |
| Sicherheit | AntiSpam, Firewall, Audit |
| Plugins | Plugins verwalten, Marketplace, dynamische Plugin-Submenüs |
| System & Doku | Einstellungen, Mail & Azure OAuth2, Module, Backup & Restore, Updates, Dokumentation |
| Diagnose | Übersicht, Datenbank, Assets, Response-Time, Cron-Job Status, Disk-Usage, Scheduled Tasks, Health-Check, E-Mail-Benachrichtigungen, Logs & Protokolle |

---

## 1. Dashboard

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Dashboard | `/admin` | Gesamtüberblick, KPIs, Schnellzugriffe, Statuskarten |

### Wichtige Abhängigkeiten

- Audit- und Aktivitätsdaten
- Monitoring-/Statusquellen aus Diagnose, Performance, Updates, Security
- Schnellzugriffe auf Kernmodule

### Prüfen

- [x] Dashboard lädt ohne statische PHP-/JS-Fehler.
- [x] KPI-Karten zeigen auf definierte Admin-Ziele und fail-soft Fallback-Werte.
- [x] Schnellzugriffe führen auf die korrekten Routen.
- [x] Widgets brechen bei Teilfehlern nicht die gesamte Seite.
- [x] Rollen sehen nur den admin-geschützten Dashboard-Einstieg.

### Must-haves

- [x] Fehlerisolierung pro Widget/Kachel.
- [x] RBAC-gesteuerte Sichtbarkeit sensibler Kennzahlen.
- [x] Audit-/Statusdaten ohne Full-Page-Fatal.

### Nice-to-haves

- [ ] Personalisierbare Widgets.
- [ ] Favoriten / zuletzt genutzte Admin-Funktionen.
- [ ] Kontextuelle Warnungen mit Deep-Links in Problemseiten.

### Audit-Stand – Dashboard · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.615`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Route:** `/admin`
- **Reproduziertes Fehlerbild:** Fiel eine einzelne Statistikquelle oder Tabelle im Dashboard-Stack aus, konnte `DashboardService::getAllStats()` den kompletten Dashboard-Request statt nur den betroffenen Block abreißen.
- **Umsetzung in diesem Durchlauf:** Dashboard-Statistiken werden jetzt segmentweise mit Fallback-Daten geladen; degradierte Bereiche erzeugen einen verständlichen Warnhinweis mit Deep-Link auf `CMS Logs`, statt in einem Full-Page-Fatal zu enden.
- **Abhängige Bereiche:** Diagnose, CMS Logs, Security Audit, Bestellungen, Sessions, Medien, Content-Statistiken
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** personalisierbare Widgets, Favoriten/Zuletzt genutzt, kontextuell priorisierte Warnungen
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/dashboard/README.md`, `CMS/DOC/admin/dashboard/DASHBOARD.md`

---

## 2. AI Services

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| AI Dashboard | `/admin/ai-services` | Überblick über KI-Dienste, Nutzung, Status |
| Übersetzung | `/admin/ai-translation` | KI-gestützte Übersetzung von Inhalten |
| Content Creator | `/admin/ai-content-creator` | KI-gestützte Erstellung von Texten |
| SEO Creator | `/admin/ai-seo-creator` | KI-Hilfen für Meta-Daten und SEO-Texte |
| Einstellungen | `/admin/ai-settings` | API-Schlüssel, Modelle, Limits, Provider |

### Wichtige Abhängigkeiten

- API-Konfigurationen und Secrets
- Content-/SEO-Module
- Lokalisierung / DE-EN-Workflows
- Protokollierung externer API-Fehler

### Prüfen

- [x] Fehlende oder ungültige API-Keys erzeugen verständliche Admin-Fehler.
- [x] Schreibaktionen sind CSRF-geschützt.
- [x] Übersetzungs-/Generierungsjobs überschreiben bestehende Inhalte nicht ungefragt.
- [x] Token-/Nutzungsgrenzen werden sauber kommuniziert.
- [x] Provider-/Modellwechsel wird korrekt gespeichert.

### Must-haves

- [x] Secrets niemals unmaskiert im UI ausgeben.
- [x] Rate-Limits, Timeouts und Fehlerpfade fail-soft behandeln.
- [x] Generierte Inhalte nur nach expliziter Bestätigung übernehmen.

### Nice-to-haves

- [ ] Prompt-/Vorlagenverwaltung je Bereich.
- [ ] Verlauf / Historie je Generierung.
- [ ] Kosten- oder Token-Monitoring im Dashboard.

### Audit-Stand – AI Services · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.616`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Routen:** `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, `/admin/ai-settings`, `/admin/ai-translate-editorjs`
- **Reproduziertes Fehlerbild:** Die Translation-Konfiguration bot einen abschaltbaren Preview-Schalter an, obwohl der Bereich laut Prüfkriterien generierte Inhalte nur nach expliziter Bestätigung übernehmen darf. Dadurch konnte der Vertrag „Review vor Übernahme“ im Settings-UI dekorativ aufgeweicht werden.
- **Umsetzung in diesem Durchlauf:** Der Review-/Preview-Schritt wird jetzt beim Laden und Speichern der Translation-Konfiguration serverseitig erzwungen; die Admin-UI dokumentiert den Schritt als festen Sicherheitsvertrag statt als frei abschaltbaren Toggle.
- **Abhängige Bereiche:** Editor.js-Übersetzung, Provider-Gateway, Logging/Audit, Quotas, Rechteprüfung im AI-Hauptbereich
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** Prompt-Vorlagen, Verlauf/Historie, Kosten-/Token-Monitoring
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/system-settings/AI-SERVICES.md`, `CMS/DOC/ai/AI-SERVICES.md`

---

## 3. Seiten & Beiträge

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Seiten | `/admin/pages` | Seiten anlegen, bearbeiten, lokalisieren, publizieren |
| Beiträge | `/admin/posts` | Blog-/News-Beiträge anlegen, bearbeiten, publizieren |
| Kategorien | `/admin/post-categories` | Beitragskategorien verwalten |
| Tags | `/admin/post-tags` | Tag-Verwaltung für Beiträge |
| Kommentare | `/admin/comments` | Kommentare moderieren |
| Inhaltsverzeichnis | `/admin/table-of-contents` | TOC-Logik und Überschriftsverhalten |
| Hub-Sites | `/admin/hub-sites` | zentrale thematische Hub-Seiten |
| Tabellen | `/admin/site-tables` | pflegbare Datentabellen für Inhalte |
| Einstellungen | `/admin/settings?tab=content` | globale Inhalts-/Editor-/Publikations-Einstellungen |

### Wichtige Abhängigkeiten

- Medienverwaltung für Featured Images und Inhaltsmedien
- SEO-Konfiguration und SEO-Karten im Editor
- Lokalisierung / EN-Präfix-Routing
- Kategorien/Tags und Blog-Routing
- Redirects bei Slug-Änderungen
- Comments-Service, Tabellen-Renderer, Hub-Site-Routing

### Spezialprüfungen je Unterbereich

- **Seiten**
  - [x] DE-/EN-Bearbeitung funktioniert stabil.
  - [x] Slug-Änderungen erzeugen korrekte lokalisierte Redirects mit `/en/<slug>`.
  - [x] Featured-Image-Speichern erzeugt keinen 500 bei erfolgreicher Übernahme.
- **Beiträge**
  - [x] Nur eine primäre Kategorie ist wählbar.
  - [x] Tags sind mehrfach pflegbar.
  - [x] Featured-Image-Speichern bleibt fail-soft.
- **Kategorien / Tags**
  - [x] Blog-Filter funktionieren weiter über Query-Parameter.
  - [x] Zähler und Zuordnungen basieren auf Relationstabellen.
- **Kommentare**
  - [x] Kommentare referenzieren veröffentlichte Beiträge korrekt.
  - [x] Moderationsaktionen sind nachvollziehbar und CSRF-geschützt.
- **Inhaltsverzeichnis**
  - [x] TOC-Konfiguration wirkt im Frontend.
- **Hub-Sites**
  - [x] Reservierte Public-Routen können nicht als Hub-Slug gespeichert werden.
- **Tabellen**
  - [x] Editor-Toggles wirken auch im öffentlichen Renderer.
  - [x] Exporte bleiben auf unterstützte Formate begrenzt.
- **Einstellungen**
  - [x] Content-Settings liegen tatsächlich unter `/admin/settings?tab=content`.

### Bereichsweite Prüfpunkte

- [x] CRUD für Seiten und Beiträge funktioniert inkl. Entwurf/Veröffentlichung.
- [x] PRG-Flow und Flash-Meldungen funktionieren.
- [x] Editor-Inhalte, Uploads und Featured Images bleiben konsistent.
- [x] SEO-Felder werden korrekt gespeichert.
- [x] Slug-/Permalink-Änderungen erzeugen keine kaputten Links.
- [x] Frontend-Gegenprobe für Seite, Beitrag, Kategorie, Tag und Kommentar durchführen.

### Must-haves

- [x] Fail-soft Save-Flow für temporäre Medienverschiebung.
- [x] Lokalisierte Redirects über Präfix-Locale statt Suffix-Altpfad.
- [x] Query-basierte Kategorie-/Tag-Navigation bleibt kompatibel.
- [x] Reservierte Slugs für Hub-Sites serverseitig sperren.
- [x] Tabellen-Frontend respektiert Editor-Schalter.

### Nice-to-haves

- [ ] Revisionsvergleich / Diff für Seiten und Beiträge.
- [ ] Bulk-Aktionen für Kategorien/Tags.
- [ ] Kommentarmoderation mit Schnellfiltern und Massenaktionen.
- [ ] Inhaltsqualitätsprüfungen direkt im Editor.

### Audit-Stand – Seiten & Beiträge · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.617`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Routen:** `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/table-of-contents`, `/admin/hub-sites`, `/admin/site-tables`, `/admin/settings?tab=content`
- **Reproduziertes Fehlerbild:** Nach Slug-Änderungen von Kategorien oder Tags blieben alte Blog-Filterwerte wie `/blog?category=alter-slug` bzw. `/blog?tag=alter-slug` ohne Auflösung auf den neuen Slug zurück und konnten im Public-Flow in 404 enden.
- **Umsetzung in diesem Durchlauf:** Taxonomie-Slug-Änderungen erzeugen jetzt automatische Archiv-Redirects; der Blog-Dispatcher kann alte Query-Slugs über diese Redirect-Spur auf den aktuellen Kategorie-/Tag-Slug auflösen und bleibt damit kompatibel zu bestehenden Theme-Links und Altverweisen.
- **Abhängige Bereiche:** Theme-Routing, Default-Theme-Blogfilter, Redirect-Manager, Kategorien/Tags, Hub-Sites, Site-Tables, Kommentare
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** Revisionsvergleich, Bulk-Aktionen für Kategorien/Tags, erweiterte Kommentarmoderation, Inhaltsqualitätsprüfungen
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/pages-posts/README.md`, `CMS/DOC/admin/pages-posts/POSTS.md`

---

## 4. Medienverwaltung

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Medien | `/admin/media` | Bibliothek, Upload, Suche, Rename, Move, Delete |
| Beitrags & Site Medien | `/admin/media?tab=featured` | verwendete Featured Images anzeigen und in place ersetzen |
| Kategorien | `/admin/media?tab=categories` | Medien-Kategorien verwalten |
| Einstellungen | `/admin/media?tab=settings` | Upload-Limits, Typen, Auto-WebP, EXIF, Thumbnails |

### Wichtige Abhängigkeiten

- `MediaService`, `MediaUsageService`, `MediaDeliveryService`
- Editor-/Featured-Image-Upload-Pipeline
- ContentMediaPlacementService für Temp-zu-Slug-Relocation
- Konfigurationswerte aus Settings/Dateikonfiguration
- Frontend-Auslieferung und Cache-Busting

### Prüfen

- [x] Upload erlaubt nur serverseitig unterstützte Formate.
- [x] Browser-`accept` und Server-Allowlist passen zueinander.
- [x] Featured-Image-Ersetzung akzeptiert nur tatsächlich verwendete Zielpfade.
- [x] Replace-in-place hält relative Pfade stabil.
- [x] Authentifizierung für interne Upload-Endpunkte ist verpflichtend.
- [x] Suche, Grid-/Listenansicht, Rename, Move und Bulk-Aktionen funktionieren.
- [x] Auto-WebP, EXIF-Strip und Thumbnail-Generierung respektieren die Einstellungen.
- [x] Geschützte Member-Pfade sind korrekt abgesichert.

### Must-haves

- [x] Erlaubte Bildformate konsistent: JPG, JPEG, PNG, GIF, WebP, BMP, ICO.
- [x] SVG und andere blockierte Typen werden serverseitig abgewiesen.
- [x] Replace-in-place bleibt auf die Featured-Map beschränkt.
- [x] Erfolgreiche Bildübernahmen erzeugen keinen 500.
- [x] Keine anonyme Upload-Nutzung für interne Admin-/Member-Uploads.

### Nice-to-haves

- [ ] Duplikat-Erkennung nach Hash.
- [ ] Mediensuche mit erweiterten Filtern (nicht nur Name/Pfad).
- [ ] Hintergrundverarbeitung für WebP-/Thumbnail-Jobs mit Fortschritt.
- [ ] Verwendungsanzeige pro Medium direkt in der Bibliothek.

### Audit-Stand – Medienverwaltung · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.618`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Routen:** `/admin/media`, `/admin/media?tab=featured`, `/admin/media?tab=categories`, `/admin/media?tab=settings`, `/api/upload`
- **Reproduziertes Fehlerbild:** Der Spezial-Flow zum Ersetzen von Beitrags-/Seitenbildern hing serverseitig an den allgemeinen Bibliotheks-Einstellungen `allowed_types`. Dadurch konnte die Featured-Replace-Oberfläche feste Bildformate wie JPG/JPEG, PNG, GIF, WebP, BMP und ICO bewerben, während der Server dieselben Dateien je nach globaler Medienkonfiguration ablehnte.
- **Umsetzung in diesem Durchlauf:** Der Replace-in-place-Flow erzwingt jetzt serverseitig eine feste Bild-Allowlist nur für Featured Images und bleibt damit konsistent zur UI, zur Doku und zum Sicherheitsvertrag des Bereichs; clientseitige Fehlhinweise nennen dieselben Formate nun ebenfalls explizit.
- **Abhängige Bereiche:** MediaService, MediaUsageService, FileUploadService, Beitrags-/Seiten-Featured-Images, Media-Settings, Admin-JavaScript
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** Duplikat-Erkennung, erweiterte Suche, Hintergrundjobs, Verwendungsanzeige in der Bibliothek
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`

---

## 5. Benutzer & Gruppen

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Benutzer | `/admin/users` | Benutzerkonten, Rollen, Status, Profildaten |
| Gruppen | `/admin/groups` | Gruppen, Mitgliedschaften, Paketbezug |
| Rollen & Rechte | `/admin/roles` | RBAC-Matrix und Rollenverwaltung |
| Einstellungen | `/admin/user-settings` | Registrierung, Passwort-Policy, Auth-Provider |

### Wichtige Abhängigkeiten

- `CMS\Auth`, UserService, RBAC/role_permissions
- Gruppen- und Paketlogik
- Öffentliche Registrierung und Passwort-Reset
- Externe Auth-Provider (z. B. LDAP, Passkeys, TOTP)

### Prüfen

- [x] Benutzer anlegen, bearbeiten, deaktivieren, löschen funktioniert.
- [x] Rollen stammen aus der dynamischen Rechte-Matrix und nicht aus Hardcodes.
- [x] Öffentliche Registrierung nutzt die konfigurierte Default-Rolle.
- [x] Default-Rolle fail-closed auf registrierungssichere Rolle.
- [x] Passwort-Policy gilt konsistent für Registrierung, Reset und Admin-CRUD.
- [x] Gruppenverwaltung verbindet Nutzer, Slugs und Paketbezug korrekt.
- [x] Auth-Provider lassen sich sicher konfigurieren.

### Must-haves

- [x] Einheitliche Passwort-Policy in allen Einstiegen.
- [x] Keine administrativen Rollen als öffentliche Standardrolle.
- [x] Capability-Prüfung zentral und nachvollziehbar.
- [x] Auditierbarkeit für kritische Nutzer- und Rollenänderungen.

### Nice-to-haves

- [ ] Rollenvergleich / Capability-Diff.
- [ ] Gruppen-Massenaktionen.
- [ ] Passwort-Policy-Tester im UI.
- [ ] Login-/Sicherheitsereignisse pro Benutzer im Profil.

### Audit-Stand – Benutzer & Gruppen · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.619`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Routen:** `/admin/users`, `/admin/groups`, `/admin/roles`, `/admin/user-settings`, `/register`, `/forgot-password`, `/cms-register`, `/cms-password-forgot`
- **Reproduziertes Fehlerbild:** Nicht-Admin-Capability-Prüfungen fielen für Legacy-Core-Rechte wie `manage_settings`, `manage_users`, `manage_pages`, `edit_all_posts` oder `manage_media` noch auf lokale Hardcodes in `Auth::hasCapability()` zurück, statt die gemeinsame Rollenmatrix als alleinige Quelle zu nutzen. Zusätzlich war der öffentliche Passwortvertrag uneinheitlich: Default-Theme-Register- und Reset-Formulare warben noch mit einem 8-Zeichen-Minimum, und das Legacy-Reset-Template validierte schwächer als die globale Core-Policy.
- **Umsetzung in diesem Durchlauf:** Die Rollen- und Rechteverwaltung enthält jetzt auch die weiterhin produktiv genutzten Legacy-Core-Capabilities in derselben Matrix wie moderne `pages.*`-/`settings.*`-Rechte und AI-Capabilities; `Auth::hasCapability()` löst Nicht-Admin-Rechte darüber zentral auf, statt auf lokale Rollenhartcodes zurückzufallen. Parallel spiegeln Default-Theme- und Core-Auth-Formulare für Registrierung und Passwort-Reset denselben 12-Zeichen-/Komplexitätsvertrag wie `Auth::validatePasswordPolicy()`.
- **Abhängige Bereiche:** `CMS\Auth`, `role_permissions`, Rollen & Rechte, Benutzerverwaltung, öffentliche Registrierung, Passwort-Reset, Default-Theme-Auth, CMS-Auth-Page, AI-/SEO-/Settings-/Media-Entrys mit Legacy-Core-Capabilities
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** Capability-Diff, Gruppen-Massenaktionen, Policy-Tester, Sicherheitsereignisse pro Benutzerprofil
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/users-groups/README.md`, `CMS/DOC/admin/users-groups/RBAC.md`, `CMS/DOC/admin/users-groups/AUTH-SETTINGS.md`

---

## 6. Member Dashboard

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Übersicht | `/admin/member-dashboard` | Status und Schnellzugriffe |
| Allgemein | `/admin/member-dashboard-general` | globale Member-Settings |
| Design & Farben | `/admin/member-dashboard-design` | Design-Konfiguration |
| Frontend-Module | `/admin/member-dashboard-frontend-modules` | sichtbare Module und Seiten |
| Dashboard Widgets | `/admin/member-dashboard-widgets` | Kern-Widgets verwalten |
| Plugin-Widgets | `/admin/member-dashboard-plugin-widgets` | Plugin-Widgets einbinden |
| Profil-Felder | `/admin/member-dashboard-profile-fields` | benutzerdefinierte Profildaten |
| Benachrichtigungen | `/admin/member-dashboard-notifications` | Kommunikations- und Hinweislogik |
| Mitglieder-Onboarding | `/admin/member-dashboard-onboarding` | Onboarding-Prozesse |

### Wichtige Abhängigkeiten

- Member-Frontend und Theme-Ausgabe
- Plugin-Widgets / Hook-Integration
- Benutzerprofile, Rollen und Benachrichtigungssystem

### Prüfen

- [x] Änderungen wirken im Mitgliederbereich sichtbar.
- [x] Nicht verfügbare Plugin-Widgets brechen die Seite nicht.
- [x] Profilfelder werden sauber gespeichert und ausgegeben.
- [x] Onboarding-Schritte greifen in der richtigen Reihenfolge.
- [x] Benachrichtigungsoptionen führen nicht zu doppelten oder verlorenen Events.

### Must-haves

- [x] Klare Trennung zwischen Admin-Konfiguration und Member-Runtime.
- [x] Fail-soft bei fehlenden Plugin-Widgets.
- [x] Sichere Speicherung benutzerdefinierter Profilfelder.

### Nice-to-haves

- [ ] Preview-Modus für Member-Dashboard-Konfiguration.
- [ ] Widget-Sortierung per Drag & Drop mit Persistenz.
- [ ] Onboarding-Analytics / Abschlussrate.

### Audit-Stand – Member Dashboard · Durchlauf 1

- **Status:** abgeschlossen auf Code-/Vertragsbasis · Release `2.9.620`
- **Prüfer:** GitHub Copilot
- **Datum:** 2026-05-09
- **Geprüfte Routen:** `/admin/member-dashboard`, `/admin/member-dashboard-general`, `/admin/member-dashboard-widgets`, `/admin/member-dashboard-profile-fields`, `/admin/member-dashboard-design`, `/admin/member-dashboard-frontend-modules`, `/admin/member-dashboard-notifications`, `/admin/member-dashboard-onboarding`, `/admin/member-dashboard-plugin-widgets`, `/member/dashboard`
- **Reproduziertes Fehlerbild:** Die Member-Runtime zog ihre Einstellungen über `MemberDashboardModule::getData()` aus dem admin-geschützten Konfigurationsmodul. Für normale Mitglieder lieferte dieser Read-Pfad bewusst leere Daten, sodass der öffentliche `/member/dashboard`-Pfad gespeicherte Einstellungen wie `dashboard_enabled`, Frontend-Module, Onboarding und Notification-Center effektiv verlor und Mitglieder trotz aktivierter Admin-Konfiguration auf `/member/profile` umgeleitet werden konnten.
- **Umsetzung in diesem Durchlauf:** `MemberDashboardModule` stellt jetzt einen eigenen Runtime-Lesepfad für persistierte Member-Settings bereit, der nicht an Admin-Read-Capabilities hängt; `MemberController` nutzt diesen Pfad für den öffentlichen Mitgliederbereich und hält nur die Modul-Aktivierung selbst zusätzlich als Laufzeit-Gate. Dadurch wirken Admin-Änderungen wieder im echten Member-Frontend, ohne die Admin-Konfigurationsoberfläche für Nicht-Admins zu öffnen.
- **Abhängige Bereiche:** Member-Frontend `/member/*`, `MemberController`, Plugin-Widgets, Profil-Fortschritt, Onboarding, Benachrichtigungszentrale, Core-Module-Service
- **Offene Must-haves:** keine
- **Offene Nice-to-haves:** Preview-Modus, Drag-&-Drop-Widgetsortierung, Onboarding-Analytics
- **Doku aktualisiert:** `Changelog.md`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/member/README.md`, `CMS/DOC/member/README.md`

---

## 7. Aboverwaltung

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Pakete & Abo-Einstellungen | `/admin/packages` | Tarife, Features, Grenzen |
| Bestellungen & Zuweisung | `/admin/orders` | Order-Lifecycle und Zuordnung |
| Einstellungen | `/admin/subscription-settings` | globale Abo-Logik |

### Wichtige Abhängigkeiten

- Benutzer, Gruppen, Rollen
- Bestell-/Paketdaten
- ggf. Plugin- oder Zahlungsintegration

### Prüfen

- [ ] Pakete sind vollständig pflegbar.
- [ ] Zuweisungen an Benutzer/Gruppen funktionieren.
- [ ] Statuswechsel von Orders sind nachvollziehbar.
- [ ] Globale Subscription-Settings wirken auf neue Vorgänge.

### Must-haves

- [ ] Konsistente Zuordnung Paket ↔ Benutzer/Gruppe.
- [ ] Sichere Statuswechsel mit Audit-Log.
- [ ] Keine stillen Inkonsistenzen bei deaktivierten Paketen.

### Nice-to-haves

- [ ] Ablaufwarnungen / Renewal-Hinweise.
- [ ] Export für Orders und Paketnutzung.
- [ ] Historie pro Paket und Bestellung.

---

## 8. Themes & Design

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Theme-Verwaltung | `/admin/themes` | Themes aktivieren, wechseln, prüfen |
| Theme-Editor | `/admin/theme-editor` | Theme-Customizer und Design-Tokens |
| Theme-Explorer | `/admin/theme-explorer` | Theme-Dateien und Struktur analysieren |
| Theme-Menü | `/admin/menu-editor` | Navigationsmenüs verwalten |
| Landing Page | `/admin/landing-page` | Homepage-/Landingpage-Aufbau |
| Font Manager | `/admin/font-manager` | Fonts scannen, laden, verwalten |
| CMS Loginpage | `/admin/cms-loginpage` | Loginpage-Branding |
| Theme-Marketplace | `/admin/theme-marketplace` | Theme-Pakete aus vertrauenswürdigen Quellen |

### Wichtige Abhängigkeiten

- ThemeManager / ThemeCustomizer
- Theme-Dateisystemzugriffe
- Menüdaten / Frontend-Navigation
- Font-Assets und Uploads
- Login-/Auth-Seiten
- Marketplace-Downloads, Host-Allowlist, Paketprüfung

### Prüfen

- [ ] Theme-Wechsel läuft ohne Frontend-Ausfall.
- [ ] Theme-Editor lädt nur sichere und zulässige Customizer-Inhalte.
- [ ] Theme-Explorer begrenzt Pfade, Dateitypen und Schreibzugriffe.
- [ ] Menü-Editor speichert Navigation korrekt.
- [ ] Landing-Page-Änderungen werden sichtbar.
- [ ] Font-Manager validiert Dateitypen, Größen und Quellen.
- [ ] Loginpage-Branding beeinflusst Auth-Flows nicht negativ.
- [ ] Theme-Marketplace prüft Host, ZIP, Hash und Mindeststruktur.

### Must-haves

- [ ] Theme-Operationen mit Locking/Health-Check/Audit-Log.
- [ ] Atomisches Schreiben bei Dateiänderungen.
- [ ] Pfad-Whitelist und Syntaxprüfung bei editierbaren Dateien.
- [ ] Remote-Downloads nur per HTTPS und Allowlist.

### Nice-to-haves

- [ ] Theme-Vergleich / Änderungsdiff.
- [ ] Vorschau-Modus vor Aktivierung.
- [ ] Font-Nutzungsanalyse.
- [ ] Komponentenbibliothek für Landing-Page-Bausteine.

---

## 9. SEO

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| SEO Dashboard | `/admin/seo-dashboard` | Überblick und Health-Score |
| Analytics | `/admin/analytics` | Kennzahlen, Tracking, Reports |
| SEO Audit | `/admin/seo-audit` | Optimierungspotenziale |
| Meta-Daten | `/admin/seo-meta` | globale Meta-Templates |
| Social Media | `/admin/seo-social` | Open Graph / Social Tags |
| Strukturierte Daten | `/admin/seo-schema` | Schema.org-Konfiguration |
| Sitemap & robots.txt | `/admin/seo-sitemap` | Sitemap-Generierung und robots.txt |
| Technisches SEO | `/admin/seo-technical` | Canonical, Index, technische Defaults |
| Weiterleitungen | `/admin/redirect-manager` | Redirect-Regeln |
| 404-Monitor | `/admin/not-found-monitor` | 404-Erkennung und Auswertung |

### Wichtige Abhängigkeiten

- Seiten/Beiträge und deren Meta-Daten
- Lokalisierung und Redirects
- Frontend-Routing und Theme-Ausgabe
- Analytics-/Tracking-Konfiguration

### Prüfen

- [ ] SEO-Dashboard aggregiert plausible Werte.
- [ ] Meta-Templates greifen für Inhalte korrekt.
- [ ] Social-/Schema-Daten erscheinen im Frontend.
- [ ] Sitemap und robots.txt sind generierbar und gültig.
- [ ] Redirects funktionieren für alte und neue Slugs.
- [ ] 404-Monitor protokolliert und entlastet die Hauptseite.
- [ ] Kategorie-/Tag-Routing bleibt query-basiert kompatibel.

### Must-haves

- [ ] Redirects für lokalisierte Seiten nutzen `/en/<slug>`.
- [ ] Alte Suffix-Redirects bleiben kompatibel.
- [ ] SEO-Einstellungen und Editor-Karten greifen konsistent ineinander.
- [ ] Keine stillen Routing-Brüche bei Kategorie-/Tag-Filtern.

### Nice-to-haves

- [ ] Snippet-/SERP-Preview pro Inhalt und global.
- [ ] Broken-Link-Prüfung.
- [ ] Automatische SEO-Hinweise direkt im Editor.
- [ ] Trendansicht für 404, Redirects und SEO-Score.

---

## 10. Performance

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Übersicht | `/admin/performance` | Score, KPIs, Status |
| Cache-Verwaltung | `/admin/performance-cache` | Cache-Status und Bereinigung |
| Medien-Optimierung | `/admin/performance-media` | WebP, EXIF, Bildoptimierung |
| Datenbank-Wartung | `/admin/performance-database` | Cleanup, Optimize, Repair |
| Performance-Einstellungen | `/admin/performance-settings` | globale Tuning-Optionen |
| Session-Verwaltung | `/admin/performance-sessions` | Session-Übersicht und Cleanup |

### Wichtige Abhängigkeiten

- Cache-Subsystem und HTTP-Header
- Medien-Settings und Upload-Pipeline
- Datenbank-Engine-Eigenheiten
- Session-Speicher und Cron-/Cleanup-Jobs

### Prüfen

- [ ] Cache lässt sich gezielt leeren.
- [ ] Medienoptimierung respektiert Auto-WebP-/EXIF-Schalter.
- [ ] Performance-Settings speichern nur ihre eigenen Felder.
- [ ] OPTIMIZE/REPAIR wird nur auf unterstützte Engines angewendet.
- [ ] Session-Übersicht ist konsistent und Cleanup wirksam.

### Must-haves

- [ ] Kein InnoDB-Repair.
- [ ] Cache-Header und UI-Schalter bleiben konsistent.
- [ ] Lange Optimierungsjobs sind robust und unterbrechungssicher.

### Nice-to-haves

- [ ] Dry-Run und Rollback für Massenoptimierungen.
- [ ] Historie der Performance-Maßnahmen.
- [ ] Kapazitätswarnungen vor Optimierungsjobs.

---

## 11. Recht

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Legal Sites | `/admin/legal-sites` | Impressum, Datenschutz, AGB, Widerruf |
| Cookie-Manager | `/admin/cookie-manager` | Consent-Banner und Kategorien |
| Auskunft & Löschen | `/admin/data-requests` | DSGVO-Export- und Löschanfragen |

### Wichtige Abhängigkeiten

- Seiten-/Content-System
- Consent-/Tracking-Logik
- Export-/Löschroutinen und personenbezogene Daten in Plugins

### Prüfen

- [ ] Legal-Seiten lassen sich erzeugen und pflegen.
- [ ] Cookie-Manager wirkt im Frontend korrekt.
- [ ] Auskunfts- und Löschanfragen werden vollständig verarbeitet.
- [ ] Relevante Aktionen landen im Audit-Log.

### Must-haves

- [ ] DSGVO-Workflows müssen nachvollziehbar und protokolliert sein.
- [ ] Export/Löschung berücksichtigt Plugin-Hooks.
- [ ] Consent-Einstellungen und Frontend-Banner dürfen nicht auseinanderlaufen.

### Nice-to-haves

- [ ] Vorlagen / Profile für Rechtstexte.
- [ ] Fristen- und Bearbeitungsstatus für Datenschutzanfragen.
- [ ] Prüfung auf fehlende Pflichtseiten im Dashboard.

---

## 12. Sicherheit

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| AntiSpam | `/admin/antispam` | Formular- und Kommentar-Spamschutz |
| Firewall | `/admin/firewall` | Regeln, Sperren, Filter |
| Audit | `/admin/security-audit` | Sicherheitsbewertung und Härtungshinweise |

### Wichtige Abhängigkeiten

- Öffentliche Formulare, Kommentare, Login
- Audit-/Log-System
- IP-/Pattern-Regelwerk

### Prüfen

- [ ] AntiSpam-Regeln wirken auf alle relevanten öffentlichen Formulare.
- [ ] Firewall-Regeln können erstellt, aktiviert und entfernt werden.
- [ ] Security-Audit liefert plausible Befunde.
- [ ] Kritische Aktionen sind geschützt und protokolliert.

### Must-haves

- [ ] Keine Sicherheitsseite ohne Auth, CSRF und Logging.
- [ ] Öffentliche Angriffspunkte sind mit AntiSpam/Firewall verknüpft.
- [ ] Audit-Hinweise sind umsetzbar und technisch belastbar.

### Nice-to-haves

- [ ] Simulationsmodus für Firewall-Regeln.
- [ ] Alarmierung bei sicherheitsrelevanten Ereignissen.
- [ ] Sicherheitsbaseline / Härtungsprofil pro Umgebung.

---

## 13. Plugins

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Plugins verwalten | `/admin/plugins` | Plugin-Lifecycle verwalten |
| Marketplace | `/admin/plugin-marketplace` | neue Plugins installieren |
| Dynamische Plugin-Submenüs | `/admin/plugins/{plugin}/{submenu}` | plugin-spezifische Admin-Seiten |

### Wichtige Abhängigkeiten

- PluginManager / Lifecycle-Hooks
- `cms_admin_menu` Hook für Sidebar-Integration
- plugin-spezifische Module, Views und Datenbanken

### Prüfen

- [ ] Aktivieren/Deaktivieren/Löschen funktioniert stabil.
- [ ] Dynamische Plugin-Menüs erscheinen korrekt in der Sidebar.
- [ ] Plugin-Seiten sind sauber in RBAC, CSRF und PRG eingebunden.
- [ ] Cross-Plugin-Zugriffe prüfen vorab `PluginManager::isPluginActive()`.

### Must-haves

- [ ] Keine Hardcodes für Plugin-Menüs in der Sidebar.
- [ ] Plugin-Lifecycle darf das Adminpanel nicht destabilisieren.
- [ ] Plugin-Hauptdateien und `update.json` müssen vollständig sein.

### Nice-to-haves

- [ ] Plugin-Abhängigkeitsanzeige.
- [ ] Health-Checks vor Aktivierung.
- [ ] Plugin-Konfigurations-Export.

### Zusätzliche Prüfschleife für **jedes** Plugin-Menü

- [ ] Menü wird korrekt über Hook registriert.
- [ ] Übersicht und Child-Menüs sind erreichbar.
- [ ] Plugin-spezifische Rechteprüfung ist vorhanden.
- [ ] Deaktiviertes Plugin hinterlässt keine defekten Sidebar-Einträge.
- [ ] Plugin-Doku unter `365CMS.DE-PLUGINS/DOC/` ist aktuell.

---

## 14. System & Doku

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Einstellungen | `/admin/settings` | zentrale Core-Einstellungen |
| Mail & Azure OAuth2 | `/admin/mail-settings` | Mailversand und OAuth2 |
| Module | `/admin/modules` | Core-Module schalten |
| Backup & Restore | `/admin/backups` | Sicherung und Wiederherstellung |
| Updates | `/admin/updates` | Core-/Theme-/Plugin-Updates |
| Dokumentation | `/admin/documentation` | lokale Dokuansicht |

### Wichtige Abhängigkeiten

- Settings-Speicher und Konfigurationsdateien
- Mail-/OAuth2-Provider
- Backup-/Update-Services
- Modul-Registry und Dokumentationsdateien

### Prüfen

- [ ] Settings werden korrekt geladen und gespeichert.
- [ ] Mail-Tests und OAuth2-Konfiguration liefern verständliche Rückmeldungen.
- [ ] Modulschalter wirken nur auf vorgesehene Core-Module.
- [ ] Backup-Erstellung, Liste, Download und Restore sind robust.
- [ ] Update-Prüfung funktioniert für Core, Themes und Plugins.
- [ ] Dokumentationsansicht zeigt vorhandene Doku ohne Pfadprobleme.

### Must-haves

- [ ] Konfigurationsänderungen sind CSRF-geschützt und nachvollziehbar.
- [ ] Backups und Updates laufen mit klaren Fehlermeldungen und Sperren/Locks.
- [ ] Remote-Update-Prüfungen nutzen sichere TLS-/Host-Prüfung.

### Nice-to-haves

- [ ] Konfigurations-Diff vor dem Speichern.
- [ ] Backup-Validierung / Restore-Check im Trockentest.
- [ ] Update-Vorabprüfung auf Abhängigkeiten und Schreibrechte.

---

## 15. Diagnose

### Unterbereiche

| Unterbereich | Route | Kernfunktion |
|---|---|---|
| Übersicht | `/admin/info` | Systeminformationen |
| Datenbank | `/admin/diagnose` | Datenbankdiagnose |
| Assets | `/admin/monitor-assets` | Asset-Prüfungen |
| Response-Time | `/admin/monitor-response-time` | Antwortzeiten und Trends |
| Cron-Job Status | `/admin/monitor-cron-status` | Cron-Laufzeiten und Status |
| Disk-Usage | `/admin/monitor-disk-usage` | Speicherverbrauch |
| Scheduled Tasks | `/admin/monitor-scheduled-tasks` | geplante Aufgaben |
| Health-Check | `/admin/monitor-health-check` | allgemeine Systemgesundheit |
| E-Mail-Benachrichtigungen | `/admin/monitor-email-alerts` | Alerts und Testversand |
| Logs & Protokolle | `/admin/cms-logs` | zentrale Logs und Protokolle |

### Wichtige Abhängigkeiten

- Datenbank, Dateisystem, CronRunner, MailQueue, Logger
- Monitoring-/Statistiksammler
- externe Dienste und Systemumgebung

### Prüfen

- [ ] Alle Monitoring-Seiten laden ohne Timeouts oder harte Fehler.
- [ ] Diagnose zeigt echte Systemdaten statt Platzhalter.
- [ ] Cron-Status spiegelt die reale Ausführung wider.
- [ ] Mail-Alerts können getestet werden.
- [ ] Logs sind erreichbar, aber ausreichend geschützt.
- [ ] Disk- und Health-Werte sind plausibel.

### Must-haves

- [ ] Diagnosefunktionen dürfen selbst keine Gefahr für den Betrieb erzeugen.
- [ ] Logs und Systeminfos nur für berechtigte Admins.
- [ ] Cron- und Mail-Queue-Zustand nachvollziehbar, insbesondere vor/nach Hourly-Hooks.

### Nice-to-haves

- [ ] Trendhistorie für Response-Time, Cron und Speicherverbrauch.
- [ ] Export/Download für Diagnoseberichte.
- [ ] Sammelansicht für kritische Systemwarnungen.

---

## Cross-Bereichs-Abhängigkeiten, die immer mitzuprüfen sind

### Inhalte ↔ Medien ↔ SEO

- [ ] Featured Images werden korrekt hochgeladen, verschoben und im Inhalt referenziert.
- [ ] SEO-Felder und globale Templates ergänzen sich ohne Konflikte.
- [ ] Kategorie-/Tag-Filter und Redirects bleiben intakt.

### Benutzer ↔ Rollen ↔ Gruppen ↔ Member Dashboard

- [ ] Rollenänderungen wirken korrekt im Admin und im Member-Bereich.
- [ ] Gruppen- und Paketbezüge beeinflussen Member-Funktionen nachvollziehbar.
- [ ] Profilfelder und Auth-Settings bleiben kompatibel.

### System ↔ Diagnose ↔ Performance

- [ ] Caches, Sessions, Cron und Logs greifen sichtbar ineinander.
- [ ] Diagnose zeigt die Effekte von Performance- oder Systemänderungen.
- [ ] Backups/Updates werden in Monitoring und Logs nachvollziehbar.

### Plugins ↔ alle anderen Bereiche

- [ ] Aktivierte Plugins erweitern Menüs und Funktionen sauber.
- [ ] Deaktivierte Plugins hinterlassen keine defekten UI- oder Routing-Reste.
- [ ] Cross-Plugin-Integrationen sind abgesichert.

---

## Abschluss pro geprüftem Bereich

Nach jedem Hauptbereich dokumentieren:

- [ ] Verantwortlicher / Prüfer
- [ ] Datum
- [ ] geprüfte Routes
- [ ] reproduzierte Fehler
- [ ] offene Must-haves
- [ ] offene Nice-to-haves
- [ ] Doku aktualisiert
- [ ] Follow-up-Tickets angelegt

---

## Zugehörige Fachdokumente

- `CMS/DOC/admin/README.md`
- `CMS/DOC/admin/FILESTRUCTURE.md`
- `CMS/DOC/admin/GUIDE.md`
- `CMS/DOC/admin/pages-posts/README.md`
- `CMS/DOC/admin/media/README.md`
- `CMS/DOC/admin/users-groups/README.md`
- `CMS/DOC/admin/member/README.md`
- `CMS/DOC/admin/subscription/README.md`
- `CMS/DOC/admin/themes-design/README.md`
- `CMS/DOC/admin/seo/README.md`
- `CMS/DOC/admin/performance/README.md`
- `CMS/DOC/admin/legal/README.md`
- `CMS/DOC/admin/security/README.md`
- `CMS/DOC/admin/system-settings/README.md`
- `CMS/DOC/admin/diagnose/README.md`

Diese Datei ist bewusst als **laufende Arbeitscheckliste** formuliert. Sie soll nicht nur beschreiben, was da ist, sondern auch festhalten, **was verbindlich geprüft werden muss** und **welche sinnvollen Ausbaustufen** noch eingeplant werden können.
