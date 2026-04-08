# 365CMS – AI Services

Kurzbeschreibung: Konzeptdokument für einen geplanten Admin-Bereich `AI Services` unter **System / Einstellungen** mit Provider-Scope, Feature-Gates und einer ersten Phase für kontrollierte Editor.js-Übersetzung nach Englisch.

Letzte Aktualisierung: 2026-04-08 · Version 2.9.2

> **Wichtig:** Diese Seite beschreibt den geplanten Zielzustand. `AI Services` ist im aktuellen Stand **noch nicht runtime-aktiv** und soll hier bewusst nur fachlich, architektonisch und betrieblich sauber vorbereitet werden.

## Inhaltsverzeichnis
- [Zielbild](#zielbild)
- [Einordnung im Admin](#einordnung-im-admin)
- [Provider-Scope](#provider-scope)
- [Phase 1: Translate Service für Editor.js](#phase-1-translate-service-für-editorjs)
- [Spätere Unterpunkte](#spätere-unterpunkte)
- [Sicherheits- und Betriebsregeln](#sicherheits--und-betriebsregeln)
- [Empfohlene Zielarchitektur](#empfohlene-zielarchitektur)
- [Nicht-Ziele im ersten Schritt](#nicht-ziele-im-ersten-schritt)

---

## Zielbild

365CMS soll AI-Funktionen **nicht** als lose Sammlung einzelner Buttons oder punktueller Vendor-Abhängigkeiten einführen, sondern als **klaren Admin-Bereich mit kontrollierbarer Betriebslogik**.

Der Bereich `AI Services` soll langfristig:

- mehrere Provider verwalten können
- pro Feature aktivierbar/deaktivierbar sein
- Limits und Betriebsregeln zentral steuern
- redaktionelle Hilfe **im Admin** bereitstellen
- Prompt-/Content-Verarbeitung nachvollziehbar, aber datensparsam behandeln

Das Ziel ist **keine unkontrollierte Vollautomatik**, sondern ein kleiner, nachvollziehbarer Assistenzbereich.

---

## Einordnung im Admin

### Geplante Position

Empfohlene Einordnung unter dem bestehenden System-/Einstellungsumfeld:

- `System`
  - `Einstellungen`
  - `Mail & Azure OAuth2`
  - `AI Services` *(geplant)*
  - `Module`
  - `CMS Logs`
  - `Backups`
  - `Updates`

### Geplante Route

- `/admin/ai-services`

### Warum nicht unter SEO oder Content?

AI-Funktionen betreffen mehrere Bereiche gleichzeitig:

- Content-Assistenz
- Übersetzungen / Rewrite
- SEO-/Meta-Vorschläge
- Provider-, Secret- und Modellverwaltung
- Logging, Kosten, Datenschutz und Limits

Damit sind sie fachlich näher an **System / Einstellungen** als an einem einzelnen Fachmodul.

---

## Provider-Scope

Der **Provider-Scope** ist das Kernstück des Konzepts. Er bestimmt:

1. **welcher Provider aktiv ist**
2. **welche Funktionen** dieser Provider ausführen darf
3. **welche Grenzen** dabei gelten

### Warum der Scope nötig ist

Ein aktivierter Provider darf nicht automatisch alles. Sonst würden Kosten, Datenschutz und UI-Verhalten zu unklar.

Die effektive Verfügbarkeit einer Funktion soll aus der Schnittmenge von Folgendem entstehen:

- Provider aktiv?
- unterstützt der Provider das Feature?
- ist das Feature im Admin freigeschaltet?
- darf der aktuelle Benutzer es nutzen?
- passen Sprache, Blocktyp, Textmenge und Richtlinien?

### Empfohlene Scope-Felder

| Feld | Bedeutung |
|---|---|
| `provider_enabled` | Provider grundsätzlich aktiv |
| `translation_enabled` | Übersetzungen erlaubt |
| `rewrite_enabled` | Umformulieren erlaubt |
| `summary_enabled` | Zusammenfassungen erlaubt |
| `seo_meta_enabled` | SEO-/Meta-Vorschläge erlaubt |
| `editorjs_enabled` | Nutzung auf Editor.js-Inhalten erlaubt |
| `allowed_locales` | erlaubte Zielsprachen |
| `max_chars_per_request` | Zeichenlimit pro Anfrage |
| `max_blocks_per_request` | Blocklimit pro Anfrage |
| `timeout_seconds` | technisches Timeout |
| `beta_only` | nur für Test-/Pilotbetrieb |
| `logging_mode` | z. B. minimal / technisch / debug-ohne-content |

### Was im Admin steuerbar sein sollte

- Provider aktiv / deaktiviert
- Standardmodell pro Feature
- Zielsprachen pro Feature
- Zeichen- und Blocklimits
- Retry-/Timeout-Verhalten
- Fallback-Regeln bei Fehlern
- Feature-Freigabe pro Provider

---

## Phase 1: Translate Service für Editor.js

### Fachliches Ziel

Als erste echte Funktion ist ein **Translate Service für vorhandene Editor.js-Inhalte** sinnvoll.

Anwendungsfall:

- Ein Redakteur hat bereits Inhalte im Editor.js-Bereich erstellt oder hineinkopiert.
- Im Admin soll daraus kontrolliert eine **englische Fassung** erzeugt werden können.
- Die Funktion bleibt zunächst auf **Admin + Editor.js + Englisch** begrenzt.

### Warum genau diese Phase 1 sinnvoll ist

- klarer, enger Scope
- hoher direkter Nutzen im Redaktionsalltag
- kein Chat-/Bot-Overhead
- strukturiertes Eingabeformat durch Editor.js
- gute Kontrollierbarkeit von Blocktypen und Limits

### Geplante UX

Empfohlener Bedienpfad:

1. Im Editor erscheint eine Aktion wie `Nach Englisch übersetzen`.
2. Vor Ausführung zeigt 365CMS:
   - Anzahl der Blöcke
   - geschätzte Textmenge
   - Zielsprache
   - Provider/Modell
3. Der Übersetzungslauf verarbeitet nur unterstützte Textblöcke.
4. Nach Abschluss erhält der Benutzer:
   - Kurzstatus
   - Teilfehler, falls vorhanden
   - Vorschau oder Zusammenfassung der Änderungen
5. Erst nach Bestätigung wird das Ergebnis übernommen.

### Was erhalten bleiben muss

Beim Übersetzen darf **nicht** die Blockstruktur zerstört werden. Erhalten bleiben sollen:

- Reihenfolge der Blöcke
- IDs/Handles soweit intern nötig
- Medien, Bilddateien und URLs
- Layout-Informationen
- nicht-textliche Spezialblöcke

### Empfohlene Übersetzungslogik

1. Editor.js-JSON lesen
2. translatierbare Felder pro Block extrahieren
3. pro Blocktyp Normalisierung durchführen
4. Texte paketieren
5. an Provider senden
6. Antworten validieren
7. Ergebnis wieder in dieselbe Blockstruktur zurückführen

### Geeignete Blocktypen für Phase 1

| Blocktyp | Phase 1 |
|---|---|
| Paragraph | ja |
| Header | ja |
| List / Checklist | ja |
| Quote / Callout | ja |
| Caption / Alt-Text | optional ja |
| Table | eher später |
| Code | nein |
| Mermaid | nein |
| API Endpoint | nein |
| Raw HTML / Embed | nein |

### Ergebnisziel

Phase 1 soll **nicht** das gesamte CMS übersetzen, sondern nur einen klaren Redaktionsworkflow ermöglichen:

- bestehende Editor.js-Inhalte
- kontrolliert nach Englisch
- mit Vorschau und Bestätigung
- ohne harte Bindung an genau einen Provider

---

## Spätere Unterpunkte

Diese Funktionen sind sinnvoll, sollen aber zunächst **nur dokumentiert**, nicht implementiert werden.

### Automatische Zusammenfassungen

Mögliche spätere Einsätze:

- Kurzfassungen aus langen Artikeln
- TL;DR-Blöcke
- Teaser für Listenansichten
- redaktionelle interne Zusammenfassungen

### Prompt-basierte SEO-/Meta-Generierung

Mögliche spätere Einsätze:

- Title-Vorschläge
- Meta-Description-Vorschläge
- Open-Graph-Textvarianten
- strukturierte FAQ-/Schema-Vorlagen als redaktionelle Hilfe

### Translation-/Rewrite-Helfer mit mehreren Providern

Mögliche spätere Einsätze:

- DE → EN / EN → DE
- formeller / technischer / marketingorientierter Rewrite
- Kürzen, Vereinfachen oder Schärfen bestehender Texte
- kanalabhängige Varianten für Social / Snippets / Teaser

---

## Sicherheits- und Betriebsregeln

Jede spätere Runtime-Umsetzung sollte folgende Regeln einhalten:

- nur für berechtigte Admin-/Redaktionsrollen
- CSRF-gesicherte Aktionen
- keine Secret-Ausgabe in UI oder Logs
- keine rohen Prompts oder vollständigen Inhalte im Standard-Log
- klare Größen-, Block- und Zeitlimits
- nachvollziehbare Fehlercodes statt diffuser Ausfälle
- Teilfehler müssen blockweise reportbar bleiben
- keine automatische Überschreibung ohne Bestätigung

Zusätzlich wichtig:

- sensible Inhalte nur nach klarer Produktentscheidung an externe Provider senden
- Datenschutz- und Betriebsdoku vor produktiver Aktivierung ergänzen
- Kosten-/Quota-Verhalten administrativ steuerbar halten

---

## Empfohlene Zielarchitektur

### Core

- `CMS/core/Services/AI/AiProviderInterface.php`
- `CMS/core/Services/AI/AiGatewayService.php`
- `CMS/core/Services/AI/AiProviderRegistry.php`
- `CMS/core/Services/AI/AiFeaturePolicy.php`
- `CMS/core/Services/AI/EditorJsTranslationService.php`

### Admin

- `CMS/admin/modules/system/AiServicesModule.php`
- `CMS/admin/views/system/ai-services.php`
- optional später `CMS/assets/js/admin-ai-services.js`

### Einstellungen / Persistenz

Sinnvoll wären getrennte Settings-Bereiche wie:

- `ai.providers`
- `ai.features`
- `ai.translation`
- `ai.logging`

Wichtig: Die Dokumentation legt hier nur **ein sinnvolles Zielbild** fest, nicht schon eine feste Datenbank- oder Dateistruktur.

---

## Nicht-Ziele im ersten Schritt

Was `AI Services` anfangs **nicht** sein soll:

- kein öffentlicher Frontend-Chat
- keine automatische Website-Komplettübersetzung
- keine stille Auto-Veröffentlichung von KI-Ergebnissen
- keine harte Kopplung an einen einzelnen Provider
- kein inoffizielles Website-Crawling als Kerninfrastruktur
- keine undurchsichtige Blackbox ohne Scope- und Betriebsgrenzen

---

## Verwandte Dokumente

- [../../ASSETS_NEW.md](../../ASSETS_NEW.md)
- [../../ASSET.md](../../ASSET.md)
- [README.md](README.md)
- [../README.md](../README.md)
- [../../../README.md](../../../README.md)
