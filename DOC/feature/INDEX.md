# 365CMS v2 – Feature-Dokumentation Index

**Herausgegeben:** 19. Februar 2026  
**Gültig für:** 365CMS v2.6.x und folgende  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 📁 Dokumentations-Übersicht

| Datei | Inhalt | Schwerpunkt |
|---|---|---|
| [ROADMAP-GESAMT.md](ROADMAP-GESAMT.md) | Master-Roadmap aller Bereiche | Gesamtüberblick |
| [CORE-SYSTEM-FEATURES.md](CORE-SYSTEM-FEATURES.md) | CMS-Core, Architektur, Engine | System-Kern |
| [PLUGINS-ROADMAP.md](PLUGINS-ROADMAP.md) | Plugin-Ökosystem, Ausbaustufen | Erweiterungen |
| [THEME-VARIANTEN.md](THEME-VARIANTEN.md) | 6 Branchen-Themes mit Design-Specs | Design & Branchen |
| [EDITOR-ROADMAP.md](EDITOR-ROADMAP.md) | Content-Editor, Page-Builder | Inhalts-Erstellung |
| [MEDIA-ROADMAP.md](MEDIA-ROADMAP.md) | Medien-Verwaltung, DAM | Dateien & Bilder |
| [SECURITY-ROADMAP.md](SECURITY-ROADMAP.md) | Sicherheit, Compliance, Datenschutz | Sicherheit |
| [API-ROADMAP.md](API-ROADMAP.md) | REST-API, Headless, Webhooks | Schnittstellen |
| [PERFORMANCE-ROADMAP.md](PERFORMANCE-ROADMAP.md) | Caching, Optimierung, Skalierung | Performance |
| [COMMERCE-ROADMAP.md](COMMERCE-ROADMAP.md) | Shop, Payments, Monetarisierung | E-Commerce |
| [COMMUNITY-ROADMAP.md](COMMUNITY-ROADMAP.md) | Community, Netzwerk, Social | Nutzer & Gemeinschaft |
| [AI-FEATURES-ROADMAP.md](AI-FEATURES-ROADMAP.md) | KI-Integration, Automatisierung | Künstliche Intelligenz |
| [MULTISITE-ROADMAP.md](MULTISITE-ROADMAP.md) | Multi-Tenant, SaaS, Hosting | Betrieb & Skalierung |
| [PLUGIN-STELLENANZEIGEN-MANAGER.md](PLUGIN-STELLENANZEIGEN-MANAGER.md) | Stellenanzeigen-Plugin, Agentur- & Firmen-Workflow, Benefits, Rahmenbedingungen, Gewerke | Plugin-Spezifikation |

### 📦 cms-jobads · 5-Phasen-Implementierungsplan

| Datei | Inhalt | Ausbaustufe |
|---|---|---|
| [cms-jobads/PHASE-1-FUNDAMENT.md](cms-jobads/PHASE-1-FUNDAMENT.md) | Plugin-Skeleton, 8 DB-Tabellen, 4 Basis-Rollen, 30 System-Profile, Audit-Log | Phase 1 · 0 → 20 % |
| [cms-jobads/PHASE-2-PROFILE-VERERBUNG.md](cms-jobads/PHASE-2-PROFILE-VERERBUNG.md) | Vererbungs-Engine (forced/default/optional), Skill/Benefit/Kondition-Profile, vollständiges RBAC | Phase 2 · 20 → 40 % |
| [cms-jobads/PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md](cms-jobads/PHASE-3-WORKFLOW-VEROEFFENTLICHUNG.md) | Genehmigungs-Workflow, Publisher, Frontend-Jobboard, Indeed/BA/Google-Feed, Bewerbungsformular, DSGVO | Phase 3 · 40 → 60 % |
| [cms-jobads/PHASE-4-PROFI-FEATURES.md](cms-jobads/PHASE-4-PROFI-FEATURES.md) | Kanban-Board, StepStone/LinkedIn/XING APIs, Profil-Versionierung, Analytics, Mehrsprachigkeit, REST-API-Feed | Phase 4 · 60 → 80 % |
| [cms-jobads/PHASE-5-VOLLSTAENDIG.md](cms-jobads/PHASE-5-VOLLSTAENDIG.md) | Multisite/Mandanten, Agentur-Portal, OpenAPI-Spezifikation, Webhooks, Personio/softgarden-Import, Test-Suite | Phase 5 · 80 → 100 % |

---

## 🎯 Prioritäten-Legende

| Symbol | Priorität | Bedeutung |
|---|---|---|
| 🔴 | **Kritisch** | Blockiert andere Features, Sicherheitsrelevant, Produktionsreife |
| 🟠 | **High** | Wichtig für Kernfunktionalität, Wettbewerbsfähigkeit |
| 🟡 | **Mittel** | Ergänzt Funktionalität, Nutzwert-steigernd |
| 🟢 | **Low** | Nice-to-have, Differenzierungsmerkmal |

---

## 📊 Feature-Übersicht nach Priorität

### 🔴 Kritische Features (sofort angehen)
- JWT-Authentifizierung mit Refresh-Token-Rotation
- Datenbank-Migrations-System (Schema-Versionierung)
- Plugin-Dependency-Manager (Abhängigkeitsprüfung)
- Input-Validation-Framework (zentral, typsicher)
- Error-Handling-System mit Stack-Traces
- Backup & Recovery System
- Rate-Limiting für alle Public-Endpoints
- DSGVO-Compliance-Modul (vollständig)

### 🟠 High-Priority Features
- Visual Page-Builder (Drag & Drop)
- REST-API v2 (vollständig dokumentiert, versioniert)
- Multi-Language-Support (i18n)
- Advanced Media Manager mit CDN-Integration
- E-Mail-Template-System mit visuellem Editor
- Webhook-System für externe Integrationen
- Theme-Customizer v2 (Live-Preview)
- Advanced-Search mit Facets und Elasticsearch

### 🟡 Mittlere Features
- AI-gestützte Content-Vorschläge
- PWA-Support (Service Worker, Offline-Modus)
- GraphQL-Endpoint als Alternative zu REST
- A/B-Testing-Framework
- Advanced-Analytics-Dashboard
- Multi-Site / Multi-Tenant-System
- Social-Login (Google, Microsoft, GitHub)
- Kommentar-System mit Moderation

### 🟢 Low-Priority Features
- Augmented-Reality-Medienvorschau
- Blockchain-basierte Content-Zertifizierung
- Sprachsteuerung (Voice-Commands)
- NFT-Integration für digitale Assets
- Gamification-Engine (Punkte, Ranglisten)
- KI-generierte Bildunterschriften
- 3D-Asset-Preview im Medien-Manager
- Barrierefreiheits-Checker (WCAG 2.2 AAA)

---

## 🏗️ Architektur-Prinzipien (gelten für alle Features)

1. **Single Responsibility** – Jedes Plugin/Modul eine Funktion
2. **Hook-First** – Alle Erweiterungen via Actions & Filters
3. **Security by Default** – Prepared Statements, Nonces, Escaping immer
4. **PHP 8.3+ Strict** – Typed Properties, Union Types, Readonly Classes
5. **Mobile First** – Alle UI-Komponenten responsiv von Grund auf
6. **DSGVO-Konform** – Privacy by Design für alle Nutzerdaten
7. **Performance Budget** – Core Web Vitals als Qualitätssicherung
8. **Plugin-Isolation** – Kein direkter Plugin-zu-Plugin-Aufruf, nur Hooks

---

*Letzte Aktualisierung: 19. Februar 2026*
