# 365CMS – AI Services

Kurzbeschreibung: Kanonische Konzept- und Architektur-Dokumentation für einen geplanten Bereich **AI Services** in 365CMS. Der Fokus liegt auf Provider-Scope, Feature-Gates, Admin-Steuerung, Editor.js-Übersetzung und einem kontrollierten, ausbaufähigen KI-Betriebsmodell.

Letzte Aktualisierung: 2026-04-08 · Version 2.9.2

> **Wichtig:** Diese Datei beschreibt bewusst den **geplanten Zielzustand**. `AI Services` ist im aktuellen Stand **noch nicht runtime-aktiv**. Die Datei dient als führende Fach- und Architekturreferenz für spätere Umsetzungsschritte.

## Inhaltsverzeichnis
- [Ziel und Abgrenzung](#ziel-und-abgrenzung)
- [Warum ein eigener AI-Bereich sinnvoll ist](#warum-ein-eigener-ai-bereich-sinnvoll-ist)
- [Geplante Einordnung im Admin](#geplante-einordnung-im-admin)
- [Funktionssäulen von AI Services](#funktionssäulen-von-ai-services)
- [Provider-Scope und Feature-Gates](#provider-scope-und-feature-gates)
- [Capability- und Rollenmodell](#capability--und-rollenmodell)
- [Phase 1: Translate Service für Editorjs](#phase-1-translate-service-für-editorjs)
- [Editorjs-Datenfluss](#editorjs-datenfluss)
- [Provider-Matrix und Betriebsprofile](#provider-matrix-und-betriebsprofile)
- [Sicherheits-, Datenschutz- und Audit-Regeln](#sicherheits-datenschutz--und-audit-regeln)
- [Empfohlene Zielarchitektur](#empfohlene-zielarchitektur)
- [Empfohlene Admin-UI](#empfohlene-admin-ui)
- [Spätere Ausbaustufen](#spätere-ausbaustufen)
- [Nicht-Ziele im ersten Schritt](#nicht-ziele-im-ersten-schritt)
- [Offene Punkte / Was noch fehlt](#offene-punkte--was-noch-fehlt)
- [Verwandte Dokumente](#verwandte-dokumente)

---

## Ziel und Abgrenzung

365CMS soll KI-Funktionen **nicht** als lose Sammlung einzelner Experimente einführen, sondern als **kontrollierten Systembereich** mit klaren Betriebsgrenzen.

Das Ziel von `AI Services` ist:

- Provider zentral verwalten
- Funktionen einzeln aktivieren/deaktivieren
- Limits, Scope und Logging steuern
- redaktionelle Assistenz **nur dort** erlauben, wo sie fachlich sinnvoll ist
- Inhalte strukturerhaltend verarbeiten statt pauschal „magisch umzuschreiben"

### Was `AI Services` ausdrücklich nicht sein soll

- kein ungeprüfter Allzweck-Chat im Admin
- keine öffentliche Frontend-KI ohne explizite Produktentscheidung
- keine unkontrollierte Direktkopplung an einen einzelnen Anbieter
- keine heimliche Auto-Persistenz ohne Benutzerbestätigung
- keine Blackbox ohne Audit-, Scope- und Fehlerregeln

---

## Warum ein eigener AI-Bereich sinnvoll ist

AI-Funktionen betreffen in 365CMS mehrere Querschnittsthemen gleichzeitig:

- Provider und Modelle
- Secret- und API-Key-Verwaltung
- Kosten- und Quota-Steuerung
- Datenschutz und Inhaltsweitergabe
- redaktionelle Assistenz in Content-, SEO- und ggf. Member-/Support-Kontexten
- Logging, Fehlerbilder und Betriebsstatus

Würde man diese Punkte auf mehrere Fachmodule verteilen, würden schnell folgende Probleme entstehen:

- unterschiedliche Regeln pro Modul
- doppelte Provider-Konfiguration
- uneinheitliche Fehlermeldungen
- unklare Verantwortung für Datenschutz und Logging
- schwerer wartbare Prompt- und Scope-Logik

Ein eigener Bereich **„AI Services“** bündelt genau diese Querschnittsthemen an einer Stelle.

---

## Geplante Einordnung im Admin

### Zielposition

Empfohlene Einordnung unter dem bestehenden System-Umfeld:

- `System`
  - `Einstellungen`
  - `Mail & Azure OAuth2`
  - `AI Services` *(geplant)*
  - `Module`
  - `CMS Logs`
  - `Backups`
  - `Updates`

### Zielroute

- `/admin/ai-services`

### Warum unter `System` statt `SEO` oder `Content`

Die AI-Funktionen sind keine einzelne Fachseite, sondern eine **Systemfähigkeit** mit mehreren Fachanwendungen. Daher gehört die Konfiguration unter `System`, während die spätere Nutzung in einzelnen Modulen stattfinden kann.

---

## Funktionssäulen von AI Services

Der Bereich sollte langfristig vier fachliche Säulen steuern.

### 1. Provider-Verwaltung

- Provider aktivieren/deaktivieren
- Modelle pro Feature zuordnen
- Zeitlimits und Größenlimits definieren
- Betriebsprofil auswählen

### 2. Feature-Gates

- Übersetzung
- Rewrite
- Zusammenfassung
- SEO-/Meta-Generierung
- ggf. spätere Spezialfunktionen

### 3. Sicherheits- und Betriebsregeln

- Logging-Modus
- Datenschutzmodus
- maximale Block- und Zeichenanzahl
- Freigabe auf Beta-/Produktiv-Niveau

### 4. Redaktionsnahe Helfer

- Editor.js-Übersetzung
- spätere Zusammenfassungen
- spätere Meta-/SEO-Helfer
- spätere Rewrite-Varianten

---

## Provider-Scope und Feature-Gates

Der **Provider-Scope** bestimmt nicht nur, **welcher Provider aktiv** ist, sondern **welche Funktionen ein Provider in 365CMS überhaupt ausführen darf**.

### Kernidee

Eine Funktion ist nur dann verfügbar, wenn alle folgenden Bedingungen erfüllt sind:

1. Provider ist aktiviert
2. Provider unterstützt das gewünschte Feature
3. Feature ist global freigegeben
4. der aktuelle Benutzer darf es nutzen
5. Inhalts- und Grenzregeln werden eingehalten

### Empfohlene Scope-Felder

| Feld | Bedeutung |
|---|---|
| `provider_enabled` | Provider grundsätzlich aktiv |
| `translation_enabled` | Übersetzung erlaubt |
| `rewrite_enabled` | Umformulieren erlaubt |
| `summary_enabled` | Zusammenfassen erlaubt |
| `seo_meta_enabled` | SEO-/Meta-Hilfe erlaubt |
| `editorjs_enabled` | Nutzung auf Editor.js-Inhalten erlaubt |
| `allowed_locales` | erlaubte Zielsprachen |
| `max_chars_per_request` | Zeichenlimit pro Lauf |
| `max_blocks_per_request` | Blocklimit pro Lauf |
| `timeout_seconds` | technisches Timeout |
| `retry_count` | kontrollierte Wiederholungen |
| `beta_only` | nur Beta-/Pilotbetrieb |
| `logging_mode` | Minimal / technisch / Debug ohne Rohinhalt |
| `allow_sensitive_content` | nur nach bewusster Freigabe |

### Empfohlene globale Feature-Gates

| Gate | Zweck |
|---|---|
| `ai_services_enabled` | zentraler Master-Schalter |
| `ai_translation_enabled` | Übersetzungen global erlaubt |
| `ai_rewrite_enabled` | Rewrite global erlaubt |
| `ai_summary_enabled` | Zusammenfassungen global erlaubt |
| `ai_seo_meta_enabled` | SEO-/Meta-Generierung global erlaubt |
| `ai_editorjs_enabled` | Editor.js-Integration global erlaubt |

### Priorisierte Kombination für Phase 1

Für den ersten sinnvollen Start genügt:

- `ai_services_enabled = true`
- `ai_translation_enabled = true`
- `ai_editorjs_enabled = true`
- `allowed_locales = ['en']`
- konservative Limits für Zeichen und Blöcke

---

## Capability- und Rollenmodell

KI-Funktionen sollten nicht pauschal für alle Adminnutzer freigeschaltet werden.

### Empfohlene Capability-Stufen

| Capability | Bedeutung |
|---|---|
| `manage_ai_services` | Provider, Modelle, Scopes und Limits verwalten |
| `use_ai_translation` | Übersetzungsfunktion nutzen |
| `use_ai_rewrite` | Rewrite-Funktionen nutzen |
| `use_ai_summary` | Zusammenfassungen nutzen |
| `use_ai_seo_meta` | SEO-/Meta-Helfer nutzen |

### Warum getrennte Capabilities sinnvoll sind

- Administratoren steuern Provider und Keys
- Redakteure nutzen ggf. nur Übersetzungen oder Zusammenfassungen
- sensible Features können getrennt pilotiert werden
- Audit und Rechtevergabe bleiben nachvollziehbar

### Minimalkonzept für den ersten Schritt

- Systemkonfiguration nur für `manage_settings` oder später `manage_ai_services`
- Translate-Action nur für Admin/Redakteure mit expliziter AI-Nutzungscapability

---

## Phase 1: Translate Service für Editor.js

### Fachliches Ziel

Der erste reale Nutzen soll **kein Bot**, sondern ein **kontrollierter Translate Service für bestehende Editor.js-Inhalte** sein.

Anwendungsfall:

- Ein Benutzer hat Inhalte bereits erstellt oder hineinkopiert.
- Im Admin soll daraus kontrolliert eine **englische Fassung** erzeugt werden.
- Die Funktion bleibt vorerst auf **Admin + Editor.js + Englisch** begrenzt.

### Warum genau diese Phase 1 sinnvoll ist

- enger Scope
- sofort verständlicher Nutzen
- sauber testbar
- strukturiertes Inputformat
- gute Trennung zwischen Text und Nicht-Text

### Erwartetes Verhalten

1. Der Benutzer löst die Aktion bewusst aus.
2. 365CMS analysiert den aktuellen Editor.js-Inhalt.
3. Nur erlaubte Textfelder werden extrahiert.
4. Nicht-textliche oder sensible Strukturelemente bleiben unangetastet.
5. Das Ergebnis wird blockweise zurückgeführt.
6. Vor finaler Übernahme sieht der Benutzer eine Vorschau oder Statuszusammenfassung.

### Was erhalten bleiben muss

- Blockreihenfolge
- Blocktypen
- Medien und Referenzen
- Layoutbezüge
- IDs/Handles soweit für den Editorfluss relevant
- nicht unterstützte Blöcke in unveränderter Form

### Geeignete Blocktypen für Phase 1

| Blocktyp | Status | Hinweis |
|---|---|---|
| Paragraph | ja | Standardfall |
| Header | ja | Überschriften mit hoher Sichtbarkeit |
| List / Checklist | ja | strukturierter Text |
| Quote / Callout | ja | textnah |
| Caption / Alt-Text | optional ja | nur Textfelder |
| Table-Zellen | später | strukturierter Sonderfall |
| Code | nein | darf nicht still verändert werden |
| Mermaid | nein | keine automatische Interpretation |
| API Endpoint | nein | technische Daten getrennt behandeln |
| Raw HTML / Embed | nein | erst später mit strengem Guard |

### Nutzersicht im Editor

Empfohlene erste UI-Aktion:

- `Nach Englisch übersetzen`

Nur sichtbar, wenn:

- `AI Services` global aktiv sind
- ein Übersetzungsprovider aktiv ist
- `translation_enabled` und `editorjs_enabled` aktiv sind
- der Benutzer die Nutzungscapability besitzt

### Ergebnisoptionen

Nach einem Lauf sollte der Benutzer auswählen können:

- Übersetzung übernehmen
- Ergebnis verwerfen
- später erneut versuchen

Optional später:

- blockweise Wiederholung für fehlgeschlagene Teilmengen
- Übersetzung in ein separates EN-Feld statt Überschreiben der aktiven Sprache

---

## Editor.js-Datenfluss

### Empfohlene Verarbeitungskette

1. Editor.js-JSON lesen
2. Blocktypen klassifizieren
3. translatierbare Textfelder extrahieren
4. Request-Payload paketieren
5. Provider ansprechen
6. Antwort validieren
7. Zuordnung zu Blöcken wiederherstellen
8. Ergebnisstruktur erzeugen
9. Vorschau-/Übernahmezustand bereitstellen

### Was im Datenfluss wichtig ist

- keine stille Neusortierung von Blöcken
- keine Mutation technischer Blocktypen
- keine Vermischung von HTML-Rohinhalt und normalisiertem Text
- klare Fehlerbehandlung pro Block oder Request-Batch

### Empfohlene interne Ergebnisstruktur

Sinnvoll wäre ein technischer Rückgabetyp mit:

- Gesamtstatus
- Providername
- Modellname
- verarbeitete Blockanzahl
- verarbeitete Zeichenzahl
- Anzahl übersetzter Blöcke
- Anzahl übersprungener Blöcke
- Teilfehlerliste
- neue Editor.js-Struktur oder Preview-Struktur

---

## Provider-Matrix und Betriebsprofile

365CMS sollte nicht sofort alle Provider gleich behandeln, sondern mit klaren Profilen arbeiten.

### Beispielhafte Provider-Klassen

| Klasse | Beispiel | Einsatz |
|---|---|---|
| Cloud-Provider | OpenAI, Azure OpenAI, Gemini | produktiv denkbar, aber secret-/quota-gebunden |
| lokale Provider | Ollama, LM Studio | interessant für datensensible Testumgebungen |
| Bridge-/Router-Provider | OpenRouter | flexibel, aber komplexer in Governance |
| experimentelle Provider | neue Symfony-Bridge-Kandidaten | nur Pilot/Beta |

### Empfohlene Betriebsprofile

| Profil | Zweck |
|---|---|
| `disabled` | vollständig aus |
| `beta` | nur für Pilotnutzer / Tests |
| `editor-translation` | nur Übersetzung im Editor.js-Kontext |
| `content-assist` | Übersetzen + Rewrite + Zusammenfassung |
| `seo-assist` | SEO-/Meta-Funktionen zusätzlich aktiv |

### Warum Profile sinnvoll sind

So lässt sich ein Provider ohne großes Rechtechaos in klaren Stufen freischalten.

---

## Sicherheits-, Datenschutz- und Audit-Regeln

Jede spätere Runtime-Umsetzung sollte sich an folgenden Regeln orientieren.

### Sicherheit

- nur CSRF-gesicherte Admin-Aktionen
- keine Secret-Ausgabe in UI oder Logs
- Request-Größen begrenzen
- Zeitlimits strikt anwenden
- Provider-Fehler fail-safe behandeln

### Datenschutz

- Inhaltsweitergabe nur nach bewusster Produktentscheidung
- sensible Inhalte nicht pauschal an externe Provider schicken
- Logging ohne Volltextinhalt im Standardmodus
- klare Dokumentation, welche Inhalte einen Provider verlassen können

### Audit / Logging

Im Regelfall nur technisch loggen:

- Provider
- Feature
- Modell
- Blockanzahl
- Zeichenanzahl
- Laufzeit
- Status
- Fehlercode / Fehlerklasse

Nicht standardmäßig loggen:

- komplette Prompts
- vollständige Inhaltsblöcke
- Secrets / API-Keys / Header

### Quota / Kosten

Sinnvoll sind:

- Zeichenlimit pro Anfrage
- Blocklimit pro Anfrage
- optional Tages- oder Benutzerkontingente
- später evtl. Audit-KPI für AI-Nutzung

---

## Empfohlene Zielarchitektur

### Core

- `CMS/core/Services/AI/AiProviderInterface.php`
- `CMS/core/Services/AI/AiGatewayService.php`
- `CMS/core/Services/AI/AiProviderRegistry.php`
- `CMS/core/Services/AI/AiFeaturePolicy.php`
- `CMS/core/Services/AI/AiRequestContext.php`
- `CMS/core/Services/AI/AiResult.php`
- `CMS/core/Services/AI/EditorJsTranslationService.php`

### Admin

- `CMS/admin/ai-services.php`
- `CMS/admin/modules/system/AiServicesModule.php`
- `CMS/admin/views/system/ai-services.php`
- optional später `CMS/assets/js/admin-ai-services.js`

### Settings / Persistenz

Sinnvolle logische Gruppen:

- `ai.providers`
- `ai.features`
- `ai.translation`
- `ai.logging`
- `ai.quotas`

### Warum diese Schichtung sinnvoll ist

- Provider bleiben austauschbar
- Admin-Logik bleibt von Providerdetails getrennt
- Editor.js-spezifische Übersetzung bleibt eigener Service statt generischem Gemischtwarenladen
- spätere Features können dieselbe Scope-/Policy-Schicht wiederverwenden

---

## Empfohlene Admin-UI

### Unterseitenmodell

Ein späterer Bereich `AI Services` könnte in folgende Unterseiten zerfallen:

| Unterseite | Zweck |
|---|---|
| Übersicht | Systemstatus, aktive Provider, Warnungen |
| Provider | Provider ein-/ausschalten, Modelle und Limits |
| Translation | Sprachziele, Editor.js-Regeln, Größenlimits |
| Rewrite | spätere Tonalitäts- und Umformulierungsregeln |
| Summaries | spätere Zusammenfassungsprofile |
| SEO Assist | spätere Meta-/SEO-Freigaben |
| Logs & Status | technische Läufe, Fehlerbilder, letzte Ausführungen |

### Minimalvariante für den Start

Für einen ersten Umsetzungsbatch reicht auch eine einzige Seite mit drei Bereichen:

1. Provider Scope
2. Translation Settings
3. Status / Hinweise

---

## Spätere Ausbaustufen

### Automatische Zusammenfassungen

Spätere Einsätze:

- Teaser / Auszüge
- TL;DR-Blöcke
- redaktionelle Kurzfassungen
- interne Zusammenfassungen für lange Texte

### Prompt-basierte SEO-/Meta-Generierung

Spätere Einsätze:

- Seitentitel-Vorschläge
- Meta-Description-Vorschläge
- Social-/OG-Varianten
- strukturierte Redaktionshilfen für FAQ/Schema

### Translation-/Rewrite-Helfer mit mehreren Providern

Spätere Einsätze:

- DE → EN / EN → DE
- Umformulieren nach Tonalität
- Kürzen / Vereinfachen / Verdichten
- Kanalvarianten für Social, Snippets, Landing-Teaser

---

## Nicht-Ziele im ersten Schritt

Was `AI Services` am Anfang **nicht** sein soll:

- kein öffentlicher Chatbot
- keine automatische Website-Komplettübersetzung
- keine implizite Auto-Veröffentlichung von AI-Ergebnissen
- keine direkte Kopplung an eine einzelne Vendor-Library
- keine Crawling-basierte Übersetzungsinfrastruktur
- keine komplexe Multimodal-Plattform mit sofortiger Vollbreite

---

## Offene Punkte / Was noch fehlt

Folgende Punkte sind **noch nicht umgesetzt** und müssten für eine echte Runtime-Einführung später ergänzt werden:

1. **Admin-Route und Menüpunkt** für `/admin/ai-services`
2. **Modul- und View-Struktur** im Core/Admin
3. **Settings-Persistenz** für Provider, Feature-Gates und Limits
4. **Capability-Modell** für Nutzung vs. Verwaltung
5. **mindestens ein echter Provider-Adapter**
6. **Editor.js-Extraktions- und Rückführlogik** für Textblöcke
7. **Preview-/Übernahme-Workflow** im Editor
8. **Fehler- und Statusmodell** für Teilfehler pro Block/Batches
9. **Datenschutz- und Logging-Konzept** für produktiven Betrieb
10. **Tests / Smoke-Checks** für Scope, Limits und Blockerhaltung

Kurz gesagt: **Die Architektur ist jetzt dokumentiert, die eigentliche Runtime-Integration fehlt noch vollständig.**

---

## Verwandte Dokumente

- [../ASSETS_NEW.md](../ASSETS_NEW.md)
- [../ASSET.md](../ASSET.md)
- [../assets/README.md](../assets/README.md)
- [../admin/system-settings/README.md](../admin/system-settings/README.md)
- [../admin/README.md](../admin/README.md)
- [../README.md](../README.md)
- [../../README.md](../../README.md)
