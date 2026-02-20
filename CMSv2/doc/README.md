# CMSv2 - Dokumentations-Übersicht

Willkommen bei der CMSv2-Dokumentation! Diese Datei gibt Ihnen einen Überblick über alle verfügbaren Dokumentationen.

## 📚 Verfügbare Dokumentationen

### 🚀 Getting Started

#### [INSTALLATION.md](INSTALLATION.md)
**Für:** Administratoren, erste Installation  
**Inhalt:**
- System-Anforderungen
- Schritt-für-Schritt Installation
- Datenbank-Setup
- Webserver-Konfiguration (Apache/Nginx)
- Troubleshooting
- Performance-Optimierung

**Wann verwenden:** Bei der ersten Einrichtung des CMS

---

#### [STATUS.md](STATUS.md)
**Für:** Projektmanager, Entwickler  
**Inhalt:**
- Aktueller Projektstatus
- Implementierte Features (100% Übersicht)
- Dateistruktur
- Funktionale Anforderungen-Erfüllung
- Deployment-Bereitschaft
- Security-Score
- Nächste Schritte

**Wann verwenden:** Für Projektübersicht und Statusreport

---

### 👨‍💻 Entwickler-Dokumentation

#### [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md)
**Für:** Plugin-Entwickler  
**Inhalt:**
- Plugin-Grundstruktur
- Plugin-Header-Format
- Hook-System (Actions & Filters)
- Verfügbare Hooks-Liste
- Best Practices
- Code-Beispiele
- Checkliste für Plugin-Release

**Wann verwenden:** Beim Erstellen eigener Plugins

**Wichtige Abschnitte:**
- Singleton-Pattern
- Hook-Registrierung
- Datenbank-Erweiterungen
- Admin-Seiten hinzufügen

---

#### [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md)
**Für:** Theme-Entwickler, Designer  
**Inhalt:**
- Theme-Grundstruktur
- Template-Hierarchie
- CSS/JavaScript-Integration
- Theme-Functions
- Responsive Design
- Accessibility
- Best Practices

**Wann verwenden:** Beim Erstellen oder Anpassen von Themes

**Wichtige Abschnitte:**
- Template-Dateien (header.php, footer.php, etc.)
- CSS-Variablen-System
- Hook-Integration
- Performance-Optimierung

---

#### [ARCHITECTURE.md](ARCHITECTURE.md)
**Für:** Entwickler, Architekten  
**Inhalt:**
- System-Architektur-Übersicht
- Architektur-Prinzipien (Modularität, Sicherheit, Performance)
- System-Schichten (Presentation, Application, Business Logic, Data Access, Infrastructure)
- Core-Komponenten (Bootstrap, Database, Security, Auth, Router, Hooks, etc.)
- Request-Lifecycle
- Design-Patterns
- Performance-Metriken

**Wann verwenden:** Zum Verständnis der System-Architektur

---

#### [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md)
**Für:** Entwickler, DBA  
**Inhalt:**
- Vollständiges Datenbank-Schema (8 Core-Tabellen)
- SQL-CREATE-Statements mit Indizes
- ER-Diagramme und Beziehungen
- Performance-Optimierungen
- Wartungs-Queries
- Best Practices für Datenbank-Operationen

**Wann verwenden:** Bei Datenbank-Entwicklung und -Optimierung

---

#### [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md)
**Für:** Plugin-Entwickler  
**Inhalt:**
- Vollständige Hook-System-Referenz
- Actions (30+) mit Beschreibungen
- Filters (10+) mit Verwendungsbeispielen
- Prioritäten-System
- Best Practices für Hook-Nutzung
- Vollständiges Plugin-Beispiel

**Wann verwenden:** Als Nachschlagewerk für Plugin-Integration

---

#### [workflow/PLUGIN-REGISTRATION-WORKFLOW.MD](workflow/PLUGIN-REGISTRATION-WORKFLOW.MD)
**Für:** Entwickler, Projektmanager, Business Analysten  
**Inhalt:**
- Vollständiger Registrierungs-Workflow (Expert, Company, Event Agency, Speaker)
- Profil-Erstellung Step-by-Step
- Plugin-basierte Datenverwaltung
- Member-Menü Kategorien (6 Maximum)
- Feature-Freischaltung basierend auf Partner/Abo-Status
- Workflow-Diagramme (visuell)
- Technische Integration via Hooks
- Code-Beispiele für alle Schritte

**Wann verwenden:** Zum Verständnis der User-Journey und Plugin-Integration

---

#### [API-REFERENCE.md](API-REFERENCE.md)
**Für:** Fortgeschrittene Entwickler  
**Inhalt:**
- Vollständige API-Dokumentation aller Core-Klassen
- Methoden-Signaturen
- Parameter-Beschreibungen
- Return-Types
- Code-Beispiele
- Helper-Funktionen

**Wann verwenden:** Als Nachschlagewerk während der Entwicklung

**Verfügbare Klassen:**
- Bootstrap
- Database
- Security
- Auth
- Router
- Hooks
- PluginManager
- ThemeManager

---

### 🔒 Sicherheit

#### [SECURITY.md](SECURITY.md)
**Für:** Security-Verantwortliche, Entwickler  
**Inhalt:**
- OWASP Top 10 Compliance
- Implementierte Security-Features
- Input-Validierung & Output-Escaping
- CSRF-Protection
- Password-Security
- Rate Limiting
- File Upload Security
- Security-Checkliste
- Incident Response

**Wann verwenden:** Für Security-Audits und Best Practices

**Wichtige Abschnitte:**
- Security-Checkliste
- Code-Beispiele für sichere Implementierung
- Penetration Testing
- Logging & Monitoring

---

#### [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md)
**Für:** Security-Verantwortliche, Management  
**Inhalt:**
- Umfassendes Security-Audit
- OWASP Top 10 (2026) Bewertung
- Security-Score: 9.2/10
- Funktions-Audit: 100%
- Priorisierte Empfehlungen
- Production-Checkliste
- Code-Review-Ergebnisse

**Wann verwenden:** Für Security-Audits und Compliance-Checks

---

### 📋 Projekt-Management

#### [CHANGELOG.md](CHANGELOG.md)
**Für:** Alle Stakeholder  
**Inhalt:**
- Versions-Historie
- Added/Changed/Fixed/Security
- Geplante Features (Roadmap)
- Versionierungs-Schema

**Wann verwenden:** Für Versions-Übersicht und Update-Informationen

---

#### [README.md](../README.md) *(Haupt-Verzeichnis)*
**Für:** Neue Benutzer, Quick-Start  
**Inhalt:**
- Projekt-Übersicht
- Feature-Liste
- Quick-Start-Anleitung
- Grundlegende Verwendung
- Plugin-Beispiel
- Support

**Wann verwenden:** Als Einstiegspunkt ins Projekt

---

## 🗺️ Navigations-Guide

### Ich möchte...

#### ...das CMS installieren
1. Start: [INSTALLATION.md](INSTALLATION.md)
2. Dann: [SECURITY.md](SECURITY.md) - Sicherheits-Checkliste
3. Optional: [README.md](../README.md) - Grundlegende Nutzung

#### ...ein Plugin entwickeln
1. Start: [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md)
2. Workflow: [workflow/PLUGIN-REGISTRATION-WORKFLOW.MD](workflow/PLUGIN-REGISTRATION-WORKFLOW.MD)
3. Referenz: [API-REFERENCE.md](API-REFERENCE.md)
4. Hooks: [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md)
5. Sicherheit: [SECURITY.md](SECURITY.md) - Input/Output-Handling

#### ...ein Theme erstellen
1. Start: [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md)
2. Beispiel: `themes/default/` - Default-Theme analysieren
3. Referenz: [API-REFERENCE.md](API-REFERENCE.md) - ThemeManager

#### ...den Projekt-Status prüfen
1. [STATUS.md](STATUS.md) - Vollständiger Überblick
2. [CHANGELOG.md](CHANGELOG.md) - Versions-Historie

#### ...ein Security-Audit durchführen
1. [SECURITY.md](SECURITY.md) - Security-Features & Checkliste
2. [STATUS.md](STATUS.md) - Security-Score
3. [INSTALLATION.md](INSTALLATION.md) - Produktions-Setup

#### ...die API verstehen
1. [API-REFERENCE.md](API-REFERENCE.md) - Vollständige Referenz
2. Code-Beispiele in anderen Docs

---

## 📊 Dokumentations-Matrix

| Dokument | Zielgruppe | Umfang | Schwierigkeit | Priorität |
|----------|------------|--------|---------------|-----------|
| README.md | Alle | Quick Start | Einfach | 🔴 Hoch |
| INSTALLATION.md | Admins | Setup | Mittel | 🔴 Hoch |
| STATUS.md | PM/Devs | Übersicht | Einfach | 🟡 Mittel |
| ARCHITECTURE.md | Devs/Architekten | Architektur | Hoch | 🟡 Mittel |
| DATABASE-SCHEMA.md | Devs/DBA | Datenbank | Hoch | 🟡 Mittel |
| HOOKS-REFERENCE.md | Plugin-Devs | Hooks | Mittel-Hoch | 🟡 Mittel |
| workflow/PLUGIN-REGISTRATION-WORKFLOW.MD | Devs/PM/BA | Workflow | Mittel | 🟡 Mittel |
| PLUGIN-DEVELOPMENT.md | Devs | Plugin | Mittel-Hoch | 🟡 Mittel |
| THEME-DEVELOPMENT.md | Devs/Designer | Theme | Mittel | 🟡 Mittel |
| API-REFERENCE.md | Devs | API | Hoch | 🟢 Niedrig |
| SECURITY.md | Security/Devs | Security | Hoch | 🔴 Hoch |
| SECURITY-AUDIT-REPORT.md | Security/PM | Audit | Hoch | 🔴 Hoch |
| CHANGELOG.md | Alle | Historie | Einfach | 🟢 Niedrig |

---

## 🔍 Schnellsuche

### Nach Thema

**Installation & Setup**
- [INSTALLATION.md](INSTALLATION.md) - Vollständige Installation
- [README.md](../README.md) - Quick Start

**Entwicklung**
- [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md) - Plugins
- [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md) - Themes
- [API-REFERENCE.md](API-REFERENCE.md) - API-Calls
- [ARCHITECTURE.md](ARCHITECTURE.md) - System-Architektur
- [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md) - Datenbank-Schema
- [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md) - Hook-System
- [workflow/PLUGIN-REGISTRATION-WORKFLOW.MD](workflow/PLUGIN-REGISTRATION-WORKFLOW.MD) - User-Workflow

**Sicherheit**
- [SECURITY.md](SECURITY.md) - Security-Guide
- [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) - Audit-Bericht
- [INSTALLATION.md](INSTALLATION.md) - Production-Setup

**Management**
- [STATUS.md](STATUS.md) - Projekt-Status
- [CHANGELOG.md](CHANGELOG.md) - Versions-Historie

### Nach Code-Beispielen

**Datenbank-Queries:**
- [API-REFERENCE.md](API-REFERENCE.md#database) - Database-Klasse
- [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md#beispiel-3-datenbank-erweiterung) - Custom Tables

**Hook-System:**
- [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md#hook-system) - Actions & Filters
- [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md#best-practices) - Template-Hooks

**Sicherheit:**
- [SECURITY.md](SECURITY.md#csrf-protection) - CSRF-Tokens
- [SECURITY.md](SECURITY.md#input-validierung) - Sanitization

**Templates:**
- [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md#template-dateien) - Alle Templates
- Default-Theme: `themes/default/` - Live-Beispiele

---

## 📝 Dokumentations-Standards

Alle Dokumentationen folgen diesen Standards:

- **Format:** Markdown (.md)
- **Encoding:** UTF-8
- **Sprache:** Deutsch
- **Code-Beispiele:** PHP mit Syntax-Highlighting
- **Struktur:** Inhaltsverzeichnis + Abschnitte
- **Updates:** Bei jedem Release aktualisiert

---

## 🆘 Hilfe & Support

### Bei Problemen:

1. **Installationsprobleme:** → [INSTALLATION.md](INSTALLATION.md#troubleshooting)
2. **Entwicklungsfragen:** → [API-REFERENCE.md](API-REFERENCE.md)
3. **Security-Fragen:** → [SECURITY.md](SECURITY.md)
4. **Allgemeine Fragen:** → [README.md](../README.md)

### Weitere Ressourcen:

- **Code-Kommentare:** Alle Core-Dateien haben PHPDoc
- **Beispiel-Code:** `plugins/example-plugin/`
- **Default-Theme:** `themes/default/` - Vollständiges Theme-Beispiel

---

## ✅ Dokumentations-Vollständigkeit

- ✅ Installation
- ✅ Projekt-Status
- ✅ Plugin-Entwicklung
- ✅ Theme-Entwicklung
- ✅ API-Referenz
- ✅ Sicherheit
- ✅ Changelog
- ✅ Architektur (NEU)
- ✅ Datenbank-Schema (NEU)
- ✅ Hooks-Referenz (NEU)
- ✅ Security-Audit (NEU)
- ✅ Workflow-Dokumentation (NEU)
- ✅ **Member-Bereich** (`member/`) mit 5 Dokumenten (NEU in v2.0.3)
- ✅ Übersicht (diese Datei)

**Status:** Volltzständig dokumentiert 🎉

---

### Member-Bereich (`member/`)

| Dokument | Inhalt |
|----------|--------|
| [member/README.md](member/README.md) | Übersicht, Struktur, URLs, Lifecycle |
| [member/CONTROLLERS.md](member/CONTROLLERS.md) | Alle Controller mit vollständiger API |
| [member/VIEWS.md](member/VIEWS.md) | Views mit Variablen-Referenz |
| [member/HOOKS.md](member/HOOKS.md) | Plugin-Hooks mit Codebeispielen |
| [member/SECURITY.md](member/SECURITY.md) | Sicherheitsmodell & DSGVO |

---

**Letzte Aktualisierung:** 18. Februar 2026  
**CMSv2 Version:** 2.0.3
