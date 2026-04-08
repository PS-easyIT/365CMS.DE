# ASSETS NEW – Integrationsdoku für neue Kandidaten
> **Stand:** 2026-04-08 | **Version:** 2.9.1 | **Status:** Bewertet, noch nicht produktiv integriert

## Inhaltsverzeichnis
- <a>Überblick</a>
- <a>Symfony AI Platform</a>
- <a>Google Translate PHP</a>
- <a>Empfohlene Integrationsregeln für 365CMS</a>
- <a>Kurzfazit</a>

---

## Überblick <!-- UPDATED: 2026-04-08 -->

Diese Datei dokumentiert **neue Asset-Kandidaten, die bereits unter `/ASSETS` vorliegen, aber noch nicht in die produktive Runtime von 365CMS übernommen wurden**.

Aktuell bewertet:

| Paket | Quelle | Status | Empfehlung |
|---|---|---|---|
| `symfony/ai-platform` | `ASSETS/ai-platform-0.6.0/` | nicht runtime-aktiv | nur über klaren Core-Adapter und zusätzliche Provider-Bridges bewerten |
| `stichoza/google-translate-php` | `ASSETS/google-translate-php-5.3.0/` | nicht runtime-aktiv | nur als optionalen Fallback-Adapter, nicht als Kern-Runtime |

Grundsatz: **Nicht jedes Paket unter `/ASSETS` gehört automatisch nach `CMS/assets/` oder `CMS/vendor/`.** Neue Kandidaten müssen zuerst fachlich, technisch und betrieblich bewertet werden.

---

## Symfony AI Platform <!-- UPDATED: 2026-04-08 -->

### Paketprofil

- **Paket:** `symfony/ai-platform`
- **Quelle:** `ASSETS/ai-platform-0.6.0/`
- **Version im Workspace:** `0.6.0`
- **Pakettyp:** PHP-Library / AI-Abstraktionsschicht
- **Status laut README:** **experimentell**

### Was das Paket fachlich macht

`symfony/ai-platform` ist **keine einzelne Modellintegration**, sondern eine **Abstraktionsschicht für verschiedene AI-Plattformen, Modelle, Provider und Verträge**. Laut README ist das Paket nur die Basis; konkrete Provider werden über zusätzliche Bridge-Pakete eingebunden.

Damit ist das Paket für 365CMS prinzipiell interessant, wenn künftig Funktionen wie diese geplant sind:

- KI-gestützte Inhaltsvorschläge
- automatische Zusammenfassungen
- Prompt-basierte SEO-/Meta-Generierung
- Translation-/Rewrite-Helfer mit mehreren Providern
- moderierte Chat-/Assistenzfunktionen im Admin

### Warum das Paket **nicht** direkt in die Runtime kopiert werden sollte

Aus `composer.json` ergibt sich, dass bereits das Basis-Paket zusätzliche Symfony- und Infrastruktur-Abhängigkeiten verlangt, die aktuell **nicht** Teil der dokumentierten 365CMS-Runtime unter `CMS/assets/` sind:

- `symfony/clock`
- `symfony/event-dispatcher`
- `symfony/property-access`
- `symfony/property-info`
- `symfony/serializer`
- `symfony/type-info`
- `symfony/uid`
- außerdem weitere Hilfsbibliotheken wie `oskarstark/enum-helper`, `phpdocumentor/reflection-docblock` und `phpstan/phpdoc-parser`

Zusätzlich nennt das README zahlreiche **Provider-Bridges** wie OpenAI, Azure OpenAI, Anthropic, Gemini, Ollama, OpenRouter, Vertex AI, Bedrock und andere. Das bedeutet praktisch:

1. Das Basis-Paket allein reicht nicht.
2. Für jeden echten Provider wird weitere Vendor-Fläche benötigt.
3. Secrets, Modellwahl, Quotas, Rate-Limits und Logging müssen separat geregelt werden.
4. Das Paket ist experimentell und **nicht** von Symfonys normalem BC-Versprechen abgedeckt.

### Empfehlung für 365CMS

**Nicht direkt nach `CMS/assets/` kopieren.**

Stattdessen empfohlen:

- eine kleine interne Abstraktion wie `CMS/core/Services/AI/AiProviderInterface.php`
- ein zentraler `AiService` bzw. `AiGatewayService`
- Provider-spezifische Adapter erst bei echtem Bedarf
- Feature-Flags für Admin-only oder Beta-Funktionen
- getrennte Konfiguration für Provider, Modelle, Timeout, Retry und Logging
- Protokollierung ohne Secret- oder Prompt-Leaks

### Empfohlene Integrationsarchitektur

Sinnvolle Zielstruktur in 365CMS:

- `CMS/core/Services/AI/`
- `CMS/core/Services/AI/Contracts/`
- `CMS/core/Services/AI/Providers/`
- `CMS/core/Services/AI/Prompts/`

Sinnvolle Einbindungspunkte:

- Admin-Helfer für Inhaltsentwürfe
- SEO-Vorschläge im Backend
- redaktionelle Assistenz-Workflows
- optionale Hintergrundjobs statt synchroner Frontend-Requests

### Integrationsrisiken

- experimentelle API-Oberfläche
- zusätzliche Symfony-Komponenten nötig
- Provider-Bridges vervielfachen Wartung und Testmatrix
- Secret-Handling nötig
- Kosten-/Quota-Risiko je nach Provider
- Datenschutz-/Audit-Themen bei Prompt- und Content-Transport

### Entscheidung

**Derzeit nicht in die Runtime aufnehmen.**

Wenn 365CMS AI-Funktionen einführt, sollte `symfony/ai-platform` **nur über einen internen Service-Adapter** und mit bewusst gewähltem Provider-Scope integriert werden.

---

## Google Translate PHP <!-- UPDATED: 2026-04-08 -->

### Paketprofil

- **Paket:** `stichoza/google-translate-php`
- **Quelle:** `ASSETS/google-translate-php-5.3.0/`
- **Version im Workspace:** `5.3.0`
- **Pakettyp:** PHP-Library für inoffizielle Google-Translate-Nutzung
- **Status:** technisch verwendbar, aber betrieblich riskant

### Was das Paket fachlich macht

Das Paket bietet eine einfache PHP-Schnittstelle für Übersetzungen über Google Translate. Für 365CMS wäre das grundsätzlich denkbar für:

- redaktionelle Schnellübersetzungen im Admin
- Vorübersetzungen von Beiträgen oder Seiten
- experimentelle Übersetzungswerkzeuge für Backoffice-Workflows

### Was `composer.json` über die Integration sagt

Direkte Anforderungen des Pakets:

- `php ^8.0`
- `guzzlehttp/guzzle ^7.0`
- `ext-dom`
- `ext-json`
- `ext-mbstring`

Damit ist bereits klar: **eine saubere Integration würde nicht nur dieses Paket, sondern auch `guzzle` samt Runtime-Vertrag erfordern.**

### Wichtige Risiken laut README

Das README ist ungewöhnlich deutlich und für die Integrationsentscheidung entscheidend:

- das Paket nutzt **Guzzle** für HTTP-Anfragen
- bei `503` oder `429` kann Google die externe IP blockieren oder eine **CAPTCHA** verlangen
- Google senkt laut README offenbar die erlaubten Request-Zahlen pro IP weiter ab
- eine Sperre kann Minuten bis 12–24 Stunden oder länger anhalten
- längere Texte stoßen an eine Grenze von **5000 Zeichen pro Request**
- im **Disclaimer** steht ausdrücklich, dass das Paket nur für **educational purposes** entwickelt wurde und jederzeit brechen kann, weil es auf dem **Crawling der Google-Translate-Website** basiert

Das ist für eine stabile Kernfunktion ein ziemlich lautes „Bitte nicht blind in Produktion werfen“.

### Empfehlung für 365CMS

**Nicht als harte Core-Abhängigkeit einführen.**

Wenn 365CMS maschinelle Übersetzung anbieten möchte, dann nur so:

- optionaler Admin-Helfer, nicht Pflichtbestandteil der Runtime
- Rate-Limiting und Backoff
- Zwischenspeicherung von Übersetzungen
- Batch-/Queue-Verarbeitung statt ungedrosselter Direktaufrufe
- klare Fehlermeldungen und Fallback auf „Übersetzung derzeit nicht verfügbar“
- langfristig bevorzugt über einen offiziellen Provider bzw. austauschbaren Translation-Adapter

### Sinnvolle Zielarchitektur

- `CMS/core/Services/Translation/TranslationProviderInterface.php`
- `CMS/core/Services/Translation/GoogleTranslateAdapter.php`
- optional weitere Adapter für offizielle APIs
- Aufrufer im Admin nur gegen die interne Schnittstelle, nie direkt gegen Vendor-Code

### Integrationsrisiken

- inoffizielle, websitebasierte API-Nutzung
- IP-Bans / CAPTCHAs / 429 / 503
- zusätzliche Guzzle-Abhängigkeit
- Zeichenlimit pro Request
- potenziell instabile Langzeitwartung

### Entscheidung

**Derzeit nicht direkt in die Runtime aufnehmen.**

Wenn überhaupt, dann nur als **optionaler Adapter mit klarer Drosselung, Caching und Fallback-Verhalten** – nicht als verlässliche Kerninfrastruktur für Pflicht-Workflows.

---

## Empfohlene Integrationsregeln für 365CMS <!-- UPDATED: 2026-04-08 -->

Für neue Pakete aus `/ASSETS` sollte künftig dieselbe Checkliste gelten:

1. **Runtime-Relevanz prüfen** – wird das Paket wirklich produktiv gebraucht?
2. **Abhängigkeiten vollständig inventarisieren** – nicht nur das Hauptpaket, auch transitive Komponenten
3. **Service-/Adapter-Schicht definieren** – keine Direktkopplung quer durchs System
4. **Provider-/Security-/Quota-Risiken dokumentieren**
5. **Nur den echten Runtime-Scope übernehmen** – nicht die komplette Upstream-Struktur
6. **Dokumentation synchron halten** – `DOC/ASSET.md`, `DOC/assets/README.md`, `DOC/ASSETS_OwnAssets.md`, `README.md`, `Changelog.md`

Kurzform für Entscheidungen:

- **UI-Bundle mit klarer Oberfläche** → potenziell lokal bundlebar oder ersetzbar
- **Security-/Auth-/Sanitizer-Bibliothek** → behalten und kapseln
- **Provider-/SDK-/AI-/Cloud-Paket** → nur über Adapter und mit klarer Betriebsentscheidung
- **inoffizielle Web-Crawling-Library** → höchstens optional, niemals als Pflichtpfad

---

## Kurzfazit <!-- UPDATED: 2026-04-08 -->

Die beiden neuen Kandidaten sind fachlich interessant, aber technisch sehr unterschiedlich riskant:

- `symfony/ai-platform` ist stark, aber groß, experimentell und bridge-/provider-lastig
- `google-translate-php` ist bequem, aber betrieblich fragil und ausdrücklich nicht für belastbare Kernnutzung gedacht

Für 365CMS lautet die saubere Linie daher:

- **noch nicht in die Runtime übernehmen**
- **erst interne Service-Schnittstellen definieren**
- **nur bei echtem Produktbedarf gezielt und adapterbasiert integrieren**
