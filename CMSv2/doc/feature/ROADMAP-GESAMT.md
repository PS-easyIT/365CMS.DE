# 365CMS – Master-Roadmap (Gesamt-Übersicht)

**Version:** 2.6.x → Zukunft  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 🔴 KRITISCHE FEATURES

### K-01 · Datenbank-Migrations-System
**Bereich:** Core / Database  
**Beschreibung:** Strukturiertes Versionierungssystem für Datenbank-Schema-Änderungen. Jede Schemaänderung erhält eine Versions-ID, wird in einer Tabelle protokolliert und kann bis/down gemigrated werden.

**Ausbaustufen:**
- **Stufe 1:** Einfaches Schema-Versions-Tracking (`cms_migrations`-Tabelle)
- **Stufe 2:** Up/Down-Migrations mit PHP-Klassen pro Änderung
- **Stufe 3:** Dry-Run-Modus (simuliert Migrationen ohne Ausführung)
- **Stufe 4:** Rollback-Mechanismus mit automatischer Datensicherung vor Migration
- **Stufe 5:** CI/CD-Integration (Auto-Migration bei Deploy)

**Technische Anforderungen:**
```php
// Beispiel-Migration
class Migration_2026_02_Add_Expert_Rating extends BaseMigration {
    public function up(): void { /* Schema-Änderung */ }
    public function down(): void { /* Rollback */ }
}
```

---

### K-02 · JWT-Authentifizierungssystem
**Bereich:** Security / Authentication  
**Beschreibung:** Vollständige Token-basierte Authentifizierung für API-Zugriffe mit Refresh-Token-Rotation, Blacklisting und Device-Tracking.

**Ausbaustufen:**
- **Stufe 1:** JWT-Generierung und -Validierung (Access-Token 15 min)
- **Stufe 2:** Refresh-Token mit Rotation (jeder Refresh generiert neuen Token)
- **Stufe 3:** Token-Blacklist (Redis/DB-basiert für invalidierte Tokens)
- **Stufe 4:** Device-Fingerprinting und Multi-Device-Management
- **Stufe 5:** Passwordless-Login (Magic-Link via E-Mail/SMS)
- **Stufe 6:** FIDO2/WebAuthn Hardware-Key-Support

---

### K-03 · Plugin-Dependency-Manager
**Bereich:** Plugin-System  
**Beschreibung:** Automatische Prüfung und Auflösung von Plugin-Abhängigkeiten. Verhindert Aktivierung von Plugins, deren Voraussetzungen nicht erfüllt sind.

**Ausbaustufen:**
- **Stufe 1:** `requires`-Header in Plugin-Manifest mit Version-Constraints
- **Stufe 2:** Automatische Abhängigkeitsresolution vor Aktivierung
- **Stufe 3:** Dependency-Graph-Visualisierung im Admin
- **Stufe 4:** Konflikterkennung (Plugin A inkompatibel mit Plugin B)
- **Stufe 5:** Auto-Update-Cascade (Abhängige Plugins bei Core-Update prüfen)

---

### K-04 · Zentrales Input-Validation-Framework
**Bereich:** Core / Security  
**Beschreibung:** Typsicheres, erweiterbares Validation-System für alle Formulare und API-Inputs.

**Ausbaustufen:**
- **Stufe 1:** Basis-Validator mit gängigen Typen (email, url, int, text)
- **Stufe 2:** Rule-Chaining (`required|email|max:255`)
- **Stufe 3:** Custom-Validator-Klassen via Hook registrierbar
- **Stufe 4:** Client-seitige Preview-Validierung (JS-Mirror der PHP-Regeln)
- **Stufe 5:** Automatische API-Dokumentation aus Validation-Rules

---

### K-05 · Backup & Recovery System
**Bereich:** Core / Operations  
**Beschreibung:** Automatisiertes Backup-System für Datenbank und Dateien mit wiederherstellbaren Snapshots.

**Ausbaustufen:**
- **Stufe 1:** Manuelle DB-Dump-Funktion im Admin
- **Stufe 2:** Geplante Backups (täglich/wöchentlich, Cron-basiert)
- **Stufe 3:** Inkrementelle File-Backups (nur geänderte Dateien)
- **Stufe 4:** Remote-Storage-Integration (S3, Dropbox, SFTP)
- **Stufe 5:** One-Click-Restore mit Vorher-Nachher-Vergleich
- **Stufe 6:** Point-in-Time-Recovery (bis zu 30 Tage Backup-Historie)

---

### K-06 · DSGVO-Compliance-Modul (Vollständig)
**Bereich:** Legal / Privacy  
**Beschreibung:** Vollumfängliche DSGVO-Implementierung als eigenständiges Plugin-Cluster.

**Ausbaustufen:**
- **Stufe 1:** Cookie-Consent-Banner mit granularer Kategorien-Auswahl
- **Stufe 2:** Datenauskunfts-Assistent (Artikel 15 DSGVO)
- **Stufe 3:** Recht auf Löschung (Artikel 17 – Automatisierter Prozess)
- **Stufe 4:** Daten-Portabilität (JSON/CSV-Export aller Nutzerdaten)
- **Stufe 5:** Verarbeitungsverzeichnis (Artikel 30 – Auto-generiert)
- **Stufe 6:** Datenschutz-Folgenabschätzung (DPIA-Assistent)
- **Stufe 7:** Consent-Management-Platform (CMP) mit Nachweis-Protokoll

---

### K-07 · Globales Rate-Limiting
**Bereich:** Security / Performance  
**Beschreibung:** Zentrales Rate-Limiting für alle Endpoints mit konfigurierbaren Regeln pro Route, IP und User.

**Ausbaustufen:**
- **Stufe 1:** IP-basiertes Rate-Limiting (Fixed Window)
- **Stufe 2:** User-basiertes Limiting (authentifizierte Requests)
- **Stufe 3:** Sliding-Window-Algorithmus für präzisere Kontrolle
- **Stufe 4:** Distributed Rate-Limiting via Redis (Multi-Server)
- **Stufe 5:** Adaptive Throttling (automatische Anpassung bei Server-Last)
- **Stufe 6:** Dashboard zur Echtzeit-Visualisierung von Rate-Limit-Ereignissen

---

### K-08 · Error-Handling & Logging Framework
**Bereich:** Core / DevOps  
**Beschreibung:** Zentrales Fehlerbehandlungs- und Protokollierungssystem.

**Ausbaustufen:**
- **Stufe 1:** PSR-3-konformes Logger-Interface
- **Stufe 2:** Log-Level-Steuerung (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- **Stufe 3:** Strukturiertes Logging (JSON-Format für Log-Aggregatoren)
- **Stufe 4:** Exception-Handler mit Stacktrace und Kontext-Capture
- **Stufe 5:** Integration mit externen Diensten (Sentry, Papertrail, ELK)
- **Stufe 6:** Admin-UI für Log-Viewer mit Filter und Suche

---

## 🟠 HIGH-PRIORITY FEATURES

### H-01 · Visual Page-Builder (Drag & Drop)
**Bereich:** Editor  
**Beschreibung:** Visueller Seiteneditor mit Block-basiertem Aufbau, ähnlich Elementor/Gutenberg, aber als natives CMS-Feature.

**Ausbaustufen:**
- **Stufe 1:** Block-System mit 15 Basis-Blöcken (Text, Bild, Button, Spalten, Trenner, Video, Karte, Liste, Zitat, Tabelle, Formular, HTML, Code, Spacer, Map)
- **Stufe 2:** Drag & Drop Interface mit Live-Preview
- **Stufe 3:** Block-Templates und Block-Bibliothek
- **Stufe 4:** Responsive-Ansicht (Mobile/Tablet/Desktop-Preview)
- **Stufe 5:** Custom-CSS per Block, globale Styles
- **Stufe 6:** Block-Kombinationen als wiederverwendbare „Patterns" speichern
- **Stufe 7:** Landing-Page-Vorlagen (20+ Branchen-Templates)
- **Stufe 8:** Gradient-, Animation- und Effekt-Optionen pro Block

---

### H-02 · REST-API v2 (Vollständig versioniert)
**Bereich:** API  
**Beschreibung:** Komplette REST-API-Implementierung für alle CMS-Entitäten mit automatischer API-Dokumentation.

**Ausbaustufen:**
- **Stufe 1:** CRUD-Endpoints für alle Core-Entitäten (Posts, Pages, Users, Media)
- **Stufe 2:** Plugin-Endpoints (Experts, Companies, Events, Speakers)
- **Stufe 3:** OpenAPI 3.0 / Swagger-Dokumentation (auto-generiert)
- **Stufe 4:** API-Versioning (`/api/v1/`, `/api/v2/`)
- **Stufe 5:** Hypermedia-Links (HATEOAS)
- **Stufe 6:** Batch-Requests (mehrere Operations in einem HTTP-Call)
- **Stufe 7:** API-Key-Verwaltung im Admin (Erstellen, Widerrufen, Statistiken)
- **Stufe 8:** SDK-Generierung (PHP, JavaScript, Python)

---

### H-03 · Multi-Language / i18n
**Bereich:** Core / Content  
**Beschreibung:** Mehrsprachige Inhalte mit URL-Struktur, Übersetzungs-Workflow und Locale-Management.

**Ausbaustufen:**
- **Stufe 1:** Basis-Locale-Verwaltung, String-Translations (.po/.mo)
- **Stufe 2:** Inhalts-Übersetzungen (Post/Page in mehreren Sprachen)
- **Stufe 3:** URL-Struktur (`/de/`, `/en/`, Subdomain oder TLD)
- **Stufe 4:** Sprach-Switcher (Frontend-Widget)
- **Stufe 5:** RTL-Support (Arabisch, Hebräisch)
- **Stufe 6:** Automatische Übersetzungs-Vorschläge via DeepL-API
- **Stufe 7:** Übersetzungs-Workflow (Freigabe-Prozess für Übersetzer-Rolle)
- **Stufe 8:** Hreflang-Tags für SEO

---

### H-04 · Advanced Media Manager
**Bereich:** Media  
**Beschreibung:** Professionelle Medienverwaltung mit Ordner-Struktur, Massen-Operationen und CDN-Integration.

**Ausbaustufen:**
- **Stufe 1:** Virtuelle Ordner-Struktur für Mediendateien
- **Stufe 2:** Bulk-Upload mit Fortschrittsanzeige
- **Stufe 3:** Bildbearbeitung in-Browser (Crop, Rotate, Resize, Filter)
- **Stufe 4:** Automatische Thumbnail-Generierung (mehrere Größen)
- **Stufe 5:** CDN-Integration (Cloudflare R2, AWS S3, IONOS Object Storage)
- **Stufe 6:** EXIF-Daten-Verwaltung und Entfernung (Datenschutz)
- **Stufe 7:** SVG-Sanitizer und sicherer SVG-Upload
- **Stufe 8:** Video-Thumbnail-Generierung (FFmpeg-Integration)
- **Stufe 9:** Digital Asset Management (DAM) mit Tags und Kollektionen

---

### H-05 · E-Mail-Template-System
**Bereich:** Communication  
**Beschreibung:** Visueller E-Mail-Editor für Transaktions- und Marketing-Mails mit Template-Verwaltung.

**Ausbaustufen:**
- **Stufe 1:** MJML/HTML-E-Mail-Templates für Systemnachrichten
- **Stufe 2:** Template-Variablen-System (`{{user.name}}`, `{{site.name}}`)
- **Stufe 3:** Visueller Template-Editor (Drag & Drop Blöcke)
- **Stufe 4:** E-Mail-Preview (Desktop/Mobile, Dark Mode)
- **Stufe 5:** Branchen-spezifische Template-Pakete (6 Themes)
- **Stufe 6:** E-Mail-Marketing-Integration (Mailchimp, Brevo, Sendinblue)
- **Stufe 7:** Automatisierungs-Trigger (Welcome, Follow-up, Re-Engagement)

---

### H-06 · Advanced Search mit Facets
**Bereich:** Search  
**Beschreibung:** Leistungsstarke Suche über alle Entitäten mit Facetten-Navigation und Autovervollständigung.

**Ausbaustufen:**
- **Stufe 1:** Volltext-Suche über Posts, Pages, Experts, Companies
- **Stufe 2:** Facetten-Filter (Kategorie, Schlagwort, Datum, Typ)
- **Stufe 3:** Autovervollständigung (Typeahead via AJAX)
- **Stufe 4:** Gewichtete Relevanz (Titel > Excerpt > Content)
- **Stufe 5:** Synonyme und Stopwörter konfigurierbar
- **Stufe 6:** Elasticsearch/OpenSearch-Integration
- **Stufe 7:** KI-semantische Suche (Embedding-basiert)
- **Stufe 8:** Suchstatistiken und häufige Suchanfragen-Auswertung

---

### H-07 · Webhook-System
**Bereich:** Integration  
**Beschreibung:** Ausgehende Webhooks für alle CMS-Events mit Retry-Logik und Protokollierung.

**Ausbaustufen:**
- **Stufe 1:** Webhook-Endpunkt-Verwaltung im Admin
- **Stufe 2:** Event-Subscription (auswählen, welche Events getriggert werden)
- **Stufe 3:** Payload-Format (JSON, Form-Data)
- **Stufe 4:** Signatur-Verifizierung (HMAC-SHA256 Header)
- **Stufe 5:** Retry-Logik (exponentielles Backoff, max. 5 Versuche)
- **Stufe 6:** Delivery-Log mit Status, Response-Code, Latenz
- **Stufe 7:** Webhook-Tester im Admin (Test-Payload senden)

---

### H-08 · Theme-Customizer v2
**Bereich:** Theme / Design  
**Beschreibung:** Erweiterter Theme-Customizer mit Live-Preview, Design-Token-System und Preset-Verwaltung.

**Ausbaustufen:**
- **Stufe 1:** Farb-Palette (Primär, Sekundär, Akzent, Neutral, Semantisch)
- **Stufe 2:** Typografie-System (Font-Family, Size-Scale, Line-Height, Weight)
- **Stufe 3:** Spacing-System (Basis-Unit, Scale-Faktor)
- **Stufe 4:** Live-CSS-Preview ohne Seitenneuladen
- **Stufe 5:** Design-Presets (speichere/lade komplette Design-Konfigurationen)
- **Stufe 6:** Dark-Mode-Konfiguration mit eigener Farbpalette
- **Stufe 7:** CSS-Custom-Properties-Export (Design-Token → CSS-Vars)
- **Stufe 8:** Tailwind-Config-Export für Headless-Frontend-Nutzung

---

## 🟡 MITTLERE FEATURES

### M-01 · PWA-Support
**Bereich:** Frontend / Mobile  
**Ausbaustufen:**
- **Stufe 1:** Web-App-Manifest (Name, Icons, Display-Mode)
- **Stufe 2:** Service-Worker (Caching-Strategie für statische Assets)
- **Stufe 3:** Offline-Fallback-Seite
- **Stufe 4:** Push-Notifications via Web-Push-API
- **Stufe 5:** Background-Sync (Formulare offline speichern, später senden)
- **Stufe 6:** App-Installation-Prompt (A2HS)

---

### M-02 · Social-Login
**Bereich:** Authentication  
**Ausbaustufen:**
- **Stufe 1:** Google OAuth 2.0
- **Stufe 2:** Microsoft/Azure AD
- **Stufe 3:** GitHub (für Entwickler-Portale)
- **Stufe 4:** LinkedIn (für Professional-Netzwerke)
- **Stufe 5:** Apple ID
- **Stufe 6:** Discord, Slack (für Community-Plattformen)
- **Stufe 7:** SAML 2.0 / OpenID Connect für Enterprise SSO

---

### M-03 · A/B-Testing-Framework
**Bereich:** Marketing / Analytics  
**Ausbaustufen:**
- **Stufe 1:** Varianten-Erstellung für Seiten/Blöcke
- **Stufe 2:** Traffic-Split (50/50 oder gewichtet)
- **Stufe 3:** Konversions-Tracking (Klick auf Ziel-Element)
- **Stufe 4:** Statistische Signifikanz-Auswertung
- **Stufe 5:** Automatischer Gewinner-Auswahl nach Schwellenwert

---

### M-04 · Advanced Analytics Dashboard
**Bereich:** Analytics  
**Ausbaustufen:**
- **Stufe 1:** Echtzeit-Besucher-Counter
- **Stufe 2:** Seitenaufrufe, Absprungrate, Verweildauer
- **Stufe 3:** Traffic-Quellen-Analyse (direkt, organisch, social, referral)
- **Stufe 4:** Nutzer-Fluss-Visualisierung (Sankey-Diagramm)
- **Stufe 5:** Konversions-Funnels (mehrstufige Ziele)
- **Stufe 6:** Heatmaps (Scroll- und Klick-Heatmap)
- **Stufe 7:** Custom-Reports mit Export (PDF, CSV, XLSX)
- **Stufe 8:** Benchmarking gegen eigene historische Daten

---

### M-05 · Gamification-Engine
**Bereich:** Community  
**Ausbaustufen:**
- **Stufe 1:** Punkte-System (Aktionen verdienen Punkte)
- **Stufe 2:** Badge-System (Achievements für Meilensteine)
- **Stufe 3:** Ranglisten (Global, Monatlich, Nach Kategorie)
- **Stufe 4:** Level-System (mit Vorteilen pro Level)
- **Stufe 5:** Challenges (zeitlich begrenzte Aufgaben)
- **Stufe 6:** Virtuelle Währung / Credits-System

---

### M-06 · GraphQL-Endpoint
**Bereich:** API  
**Ausbaustufen:**
- **Stufe 1:** Schema-Definition für alle CMS-Entitäten
- **Stufe 2:** Queries (lesen) und Mutations (schreiben)
- **Stufe 3:** Subscriptions (Echtzeit via WebSocket)
- **Stufe 4:** DataLoader (N+1-Problem vermeiden)
- **Stufe 5:** Persisted Queries für Performance
- **Stufe 6:** GraphQL-Playground im Admin

---

### M-07 · Kommentar-System
**Bereich:** Content / Community  
**Ausbaustufen:**
- **Stufe 1:** Basis-Kommentare für Posts/Events
- **Stufe 2:** Threaded Comments (Antworten auf Kommentare)
- **Stufe 3:** Reaktionen (👍 👎 ❤️ 😂)
- **Stufe 4:** Moderations-Queue mit KI-Spam-Erkennung
- **Stufe 5:** Mentions (@username-Benachrichtigung)
- **Stufe 6:** Rich-Text in Kommentaren (Markdown)

---

## 🟢 LOW-PRIORITY FEATURES

### L-01 · Blockchain Content-Zertifizierung  
Nachweis der Urheberschaft für Artikel und Medien via NFT-Minting oder IPFS-Hash-Ankerung.

### L-02 · Voice-Commands  
Sprachgesteuerte Navigation im Admin (Voice-to-Suche, Diktat für Content-Erstellung).

### L-03 · Augmented Reality Medienvorschau  
AR-Overlay für Produkte und 3D-Assets im eingebetteten Viewer.

### L-04 · 3D-Asset-Manager  
GLTF/GLB-Upload und Three.js-basierter Preview-Viewer im Medien-Manager.

### L-05 · Barrierefreiheits-Checker  
Automatischer WCAG 2.2 AAA Audit mit Verbesserungs-Vorschlägen für jeden Seiteninhalt.

### L-06 · KI-Bildunterschriften  
Automatische Alt-Text-Generierung für hochgeladene Bilder via Vision-API.

### L-07 · Biometrische Authentifizierung  
Fingerabdruck / Face-ID Login via WebAuthn für Mobile-Browser.

---

*Letzte Aktualisierung: 19. Februar 2026*
