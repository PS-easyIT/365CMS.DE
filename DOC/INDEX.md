 ---
## Sicheres, modulares und erweiterbares Content Management System => [WWW.365CMS.DE](HTTPS://365CMS.DE)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
## 

---

## Haupt-Dokumente

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [README.md](README.md) | Übersicht & Einstieg | Alle |
| [INSTALLATION.md](INSTALLATION.md) | Schritt-für-Schritt-Installation | Admins |

---

## core/ – Technische Kern-Dokumentation

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [ARCHITECTURE.md](core/ARCHITECTURE.md) | Systemschichten, Patterns, Request-Lifecycle | Entwickler |
| [CORE-CLASSES.md](core/CORE-CLASSES.md) | Alle 11 Core-Klassen mit Methoden & Beispielen | Entwickler |
| [DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) | 22 Tabellen mit SQL, Indizes und Beispiel-Queries | Devs/DBA |
| [HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) | Alle Actions & Filters mit Beispielen | Plugin-Devs |
| [API-REFERENCE.md](core/API-REFERENCE.md) | REST-API-Endpunkte | Entwickler |
| [SECURITY.md](core/SECURITY.md) | CSRF, XSS, SQL-Injection, Rate-Limiting, Header | Alle Devs |
| [SECURITY-ARCHITECTURE.md](core/SECURITY-ARCHITECTURE.md) | Vertiefte Security-Analyse | Senior Devs |
| [SERVICES.md](core/SERVICES.md) | Alle 11 Service-Klassen erklärt | Entwickler |
| [STATUS.md](core/STATUS.md) | Implementierungsstand & Roadmap | PM/Devs |
| [SYSTEM-DOCUMENTATION.md](core/SYSTEM-DOCUMENTATION.md) | Vollständige technische Dokumentation | Architekten |

---

## admin/ – Admin-Panel

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [README.md](admin/README.md) | Admin-Bereich komplett – alle 27 Seiten erklärt | Admins |
| [GUIDE.md](admin/GUIDE.md) | Schritt-für-Schritt Admin-Handbuch | Admins/Support |
| [FILESTRUCTURE.md](admin/FILESTRUCTURE.md) | Dateistruktur des Admin-Verzeichnisses | Entwickler |
| [PANEL-INTEGRATION.md](admin/PANEL-INTEGRATION.md) | Wie Plugins Admin-Seiten hinzufügen | Plugin-Devs |

---

## member/ – Mitglieder-Bereich

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [README.md](member/README.md) | Member-Bereich komplett – alle Seiten und Architektur | Alle |
| [CONTROLLERS.md](member/CONTROLLERS.md) | Controller-Klassen der Member-Seiten | Entwickler |
| [VIEWS.md](member/VIEWS.md) | Templates & Partials | Entwickler/Designer |
| [HOOKS.md](member/HOOKS.md) | Member-spezifische Hooks | Plugin-Devs |
| [SECURITY.md](member/SECURITY.md) | Sicherheit & DSGVO im Member-Bereich | Security/Devs |

---

## theme/ – Theme-Entwicklung

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [README.md](theme/README.md) | Theme-System Übersicht & verfügbare Themes | Alle |
| [THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) | Theme erstellen von Grund auf (Anfänger) | Designer/Devs |
| [DESIGN-SYSTEM.md](theme/DESIGN-SYSTEM.md) | CSS-Variablen, Farb-System, Typografie | Designer |
| [COMPONENTS.md](theme/COMPONENTS.md) | UI-Komponenten-Bibliothek | Designer/Devs |
| [JAVASCRIPT.md](theme/JAVASCRIPT.md) | JavaScript-Muster und Best Practices | Entwickler |
| [DEVELOPMENT.md](theme/DEVELOPMENT.md) | Entwicklungs-Workflow & Tools | Entwickler |
| [CHANGELOG.md](theme/CHANGELOG.md) | Theme-Versionshistorie | Alle |

---

## plugins/ – Plugin-Entwicklung

| Datei | Beschreibung | Zielgruppe |
|-------|-------------|------------|
| [GUIDE.md](plugins/GUIDE.md) | Plugin in 10 Minuten (Schnellstart) | Einsteiger |
| [PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) | Vollständiger Plugin-Leitfaden (alle Themen) | Entwickler |
| [PLUGIN-LIST.MD](plugins/PLUGIN-LIST.MD) | Alle verfügbaren Plugins | Alle |
| [PLUGINS-SUMMARY.md](plugins/PLUGINS-SUMMARY.md) | Plugin-Zusammenfassung & Empfehlungen | PM/Devs |

---

## feature/ – Feature-Planung (intern)

| Datei | Beschreibung |
|-------|-------------|
| [MARKETPLACE-KONZEPT.md](feature/MARKETPLACE-KONZEPT.md) | Marketplace-Feature-Konzept |
| [PROJEKT-STATUS.md](feature/PROJEKT-STATUS.md) | Projekt-Status & Roadmap |

---

## audits/ – Sicherheits-Audits

| Datei | Beschreibung |
|-------|-------------|
| [SECURITY-AUDIT-REPORT.md](audits/SECURITY-AUDIT-REPORT.md) | Vollständiger Security-Audit-Bericht |

---

## Direktlinks für häufige Aufgaben

| Aufgabe | Dokument |
|---------|----------|
| Erstinstallation | [INSTALLATION.md](INSTALLATION.md) |
| Admin-Login | [admin/README.md#1-zugang--login](admin/README.md) |
| Neuen User anlegen | [admin/GUIDE.md](admin/GUIDE.md) |
| Plugin 10min Quickstart | [plugins/GUIDE.md](plugins/GUIDE.md) |
| Vollständiger Plugin-Guide | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Theme erstellen | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |
| Hooks nutzen | [core/HOOKS-REFERENCE.md](core/HOOKS-REFERENCE.md) |
| DB-Queries schreiben | [core/CORE-CLASSES.md](core/CORE-CLASSES.md#2-database) |
| Sicherheit (CSRF/XSS) | [core/SECURITY.md](core/SECURITY.md) |
| Systemstatus prüfen | [core/STATUS.md](core/STATUS.md) |

