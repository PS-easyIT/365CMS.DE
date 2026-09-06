# 365CMS – Admin-Prüf-Checkliste

> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Stabiler Dokumentations- und Übergabeanker

Diese kurze Checkliste ergänzt die ausführlichen Audit-Dokumente. Sie führt keine Admin-Funktion ein und ersetzt keine fachliche Sicherheitsprüfung.

## Vorprüfung

- [ ] aktuelle Route statt Legacy-Dateiname verwendet
- [ ] Rolle und Capability geklärt
- [ ] zuständiges Core-Modul aktiviert
- [ ] Wartungs-/Rollbackplan vorhanden
- [ ] Backupbedarf geprüft

## Zugriff und Request

- [ ] `ABSPATH`-Guard vorhanden
- [ ] Admin-/Capability-Prüfung serverseitig
- [ ] POST für Zustandsänderungen
- [ ] CSRF-Token einmalig erzeugt und serverseitig verifiziert
- [ ] Action/Section-Allowlist geprüft
- [ ] POST/Redirect/GET umgesetzt
- [ ] Redirectziel intern und validiert

## Eingaben und Ausgaben

- [ ] Text, IDs, URLs und Arrays typgerecht normalisiert
- [ ] Längen- und Mengenlimits gesetzt
- [ ] Datenbankzugriffe vorbereitet/parametrisiert
- [ ] HTML, Attribute und URLs kontextbezogen escaped
- [ ] keine rohen Exception-Texte im Frontend
- [ ] leere Zustände als echte Empty States behandelt

## Oberfläche und Navigation

- [ ] Menüeintrag über den aktuellen Admin-Hook registriert
- [ ] eindeutiger Slug verwendet
- [ ] Sub-View nur über ihren Wrapper erreichbar
- [ ] keine eigene parallele Sidebar
- [ ] Callback wird nicht doppelt gewrappt
- [ ] aktive Sidebar-Seite korrekt gesetzt
- [ ] Desktop und kleine Viewports geprüft

## AI-spezifische Prüfung

- [ ] globales AI-Gate und Provider-Scope geprüft
- [ ] Capability für Verwaltung/Nutzung geprüft
- [ ] externe Datenweitergabe bewusst freigegeben
- [ ] Provider-Endpoint, Secret, Locale und Profil geprüft
- [ ] Quotas atomar berücksichtigt
- [ ] Retry/Fallback ebenfalls policy- und quota-geprüft
- [ ] Prompt-Vertrag und JSON-Ausgabe strikt
- [ ] Rohprompts, Secrets und Volltexte nicht geloggt
- [ ] Preview/Review vor Übernahme oder Veröffentlichung

## Abschluss

- [ ] Erfolg, Fehler, Warnung und Empty State getestet
- [ ] direkte URL ohne Berechtigung getestet
- [ ] ungültiger/fehlender Token getestet
- [ ] Cache/Rewrite-Auswirkungen geprüft
- [ ] Betriebs-/Auditlog ohne sensible Daten geprüft
- [ ] betroffene Dokumentation aktualisiert

Weiterführend: [../audit/NiceToHave-CHECKLISTE.md](../audit/NiceToHave-CHECKLISTE.md), [../audit/MustHave-CHECKLISTE.md](../audit/MustHave-CHECKLISTE.md).
