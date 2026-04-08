# 365CMS – AI Services im Admin-Kontext

Kurzbeschreibung: Admin-spezifische Einordnung des Bereichs `AI Services` unter **System / Einstellungen**. Die führende Fach- und Architektur-Dokumentation liegt unter [`../../ai/AI-SERVICES.md`](../../ai/AI-SERVICES.md); die Admin-Seite `/admin/ai-services` ist seit `2.9.2` als Settings- und Runtime-Steuerfläche im Core eingehängt.

Letzte Aktualisierung: 2026-04-08 · Version 2.9.2

> **Wichtig:** Diese Datei ist bewusst nur der **Admin- und Routing-Kontext**. Die vollständige Konzeption, Provider-Logik, Editor.js-Übersetzungsphase und offene Punkte werden kanonisch in [`../../ai/AI-SERVICES.md`](../../ai/AI-SERVICES.md) gepflegt.

## Admin-Einordnung

Aktuelle Position in der Sidebar:

- `System`
   - `Einstellungen`
   - `Mail & Azure OAuth2`
   - `AI Services`
   - `Module`
   - `CMS Logs`
   - `Backups`
   - `Updates`

Aktive Route:

- `/admin/ai-services`

## Fachliche Kurzfassung

Der Bereich bündelt bereits als Settings-Hülle drei Dinge:

1. **Provider-Scope und Feature-Gates**
2. **erste redaktionelle AI-Helfer im Admin**
3. **Betriebs-, Datenschutz- und Logging-Regeln**

Der erste sinnvolle Umsetzungsfokus bleibt fachlich weiterhin:

- **Translate Service für bestehende Editor.js-Inhalte**
- zunächst **nur nach Englisch**
- nur im **Adminbereich**
- ohne automatische Frontend-Übersetzung

## Führende Dokumentation

Die vollständige Fach- und Architekturdoku liegt hier:

- [../../ai/AI-SERVICES.md](../../ai/AI-SERVICES.md)

Aktueller Runtime-Hinweis:

- `/admin/ai-services` verwaltet heute bereits die Datenstruktur für `ai.providers`, `ai.features`, `ai.translation`, `ai.logging` und `ai.quotas`
- `/admin/ai-translate-editorjs` stellt heute bereits einen geschützten Mock-Endpoint für Editor.js-Übersetzungen in Post-/Page-Editoren bereit
- `CMS/assets/js/admin-content-editor.js` erzwingt heute bereits einen Preview-/Diff-Schritt, bevor der AI-Vorschlag in die EN-Bearbeitung übernommen wird
- echte externe Provider-Calls sind weiterhin noch **nicht** implementiert

Verwandte Admin-Dokumente:

- [README.md](README.md)
- [../README.md](../README.md)
- [../../ASSETS_NEW.md](../../ASSETS_NEW.md)
