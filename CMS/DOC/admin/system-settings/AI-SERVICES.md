# 365CMS – AI Services im Admin-Kontext

Kurzbeschreibung: Admin-spezifische Einordnung des Bereichs `AI Services` als **eigener Admin-Hauptbereich**. Die führende Fach- und Architektur-Dokumentation liegt unter [`../../ai/AI-SERVICES.md`](../../ai/AI-SERVICES.md); die Admin-Seiten `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator` und `/admin/ai-settings` sind als Settings- und Runtime-Steuerflächen im Core eingehängt.

Letzte Aktualisierung: 2026-04-08 · Version 2.9.2

> **Wichtig:** Diese Datei ist bewusst nur der **Admin- und Routing-Kontext**. Die vollständige Konzeption, Provider-Logik, Editor.js-Übersetzungsphase und offene Punkte werden kanonisch in [`../../ai/AI-SERVICES.md`](../../ai/AI-SERVICES.md) gepflegt.

## Admin-Einordnung

Aktuelle Position in der Sidebar:

- `AI Services`
   - `Dashboard`
   - `Übersetzung`
   - `Content Creator`
   - `SEO Creator`
   - `Einstellungen`

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

- `/admin/ai-services` bzw. `/admin/ai-settings` verwalten heute die Datenstruktur für `ai.providers`, `ai.features`, `ai.translation`, `ai.logging` und `ai.quotas`
- Provider werden als gezielt anlegbare Liste geführt; sichtbar sind damit nur die tatsächlich konfigurierten Einträge statt einer fest verdrahteten Komplettmatrix
- `/admin/ai-translate-editorjs` stellt heute einen geschützten Live-Endpoint für Editor.js-Übersetzungen bereit, inklusive Preview-/Diff-Schritt vor der EN-Übernahme
- echte Live-Provider sind aktuell für **Ollama** und **Azure AI** umgesetzt; weitere Bridge-Kandidaten wie OpenAI/OpenRouter bleiben vorbereitete Folgearbeit

Verwandte Admin-Dokumente:

- [README.md](README.md)
- [../README.md](../README.md)
- [../../ASSETS_NEW.md](../../ASSETS_NEW.md)
