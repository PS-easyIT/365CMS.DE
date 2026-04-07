# 365CMS – DEVLIST

> Stand: 2026-04-07  
> Zielgruppe: Entwickler, Integratoren, Auditoren, Betreiber  
> Version: passend zum aktuellen 365CMS-Stand 2.9.0

Diese Datei ist die zentrale technische Entwicklerreferenz für das laufende 365CMS. Sie soll möglichst viel Wissen an einer Stelle bündeln. Als begleitende Einstiegspunkte sind nur die Doku-Übersicht unter [`DOC/INDEX.md`](INDEX.md) und die Strukturübersicht unter [`DOC/FILELIST.md`](FILELIST.md) vorgesehen.

---

## Inhaltsverzeichnis

- [0. Abhängigkeiten, Plattformen und Betriebsgrundlagen](#0-abhängigkeiten-plattformen-und-betriebsgrundlagen)
- [1. Systembild in einem Satz](#1-systembild-in-einem-satz)
- [2. Repository- und Laufzeitmodell](#2-repository--und-laufzeitmodell)
- [3. Bootstrap, Betriebsmodi und Startpfad](#3-bootstrap-betriebsmodi-und-startpfad)
- [4. Konfiguration, Konstanten und feste Werte](#4-konfiguration-konstanten-und-feste-werte)
- [5. Dependency Injection und zentrale Core-Komponenten](#5-dependency-injection-und-zentrale-core-komponenten)
- [6. Sicherheitsarchitektur](#6-sicherheitsarchitektur)
- [7. Authentifizierung, MFA, Passkeys, LDAP, JWT](#7-authentifizierung-mfa-passkeys-ldap-jwt)
- [8. Datenbank, Schema und Migrationen](#8-datenbank-schema-und-migrationen)
- [9. Content-Modell und Mehrsprachigkeit](#9-content-modell-und-mehrsprachigkeit)
- [10. Routing und Request-Fluss](#10-routing-und-request-fluss)
- [11. Service-Layer](#11-service-layer)
- [12. SEO, Redirects, 404 und Sichtbarkeit](#12-seo-redirects-404-und-sichtbarkeit)
- [13. Plugin-System](#13-plugin-system)
- [14. Theme-System](#14-theme-system)
- [15. Admin-Architektur](#15-admin-architektur)
- [16. Member-Bereich](#16-member-bereich)
- [17. Hooks- und Event-System](#17-hooks--und-event-system)
- [18. Performance, Cache und Monitoring](#18-performance-cache-und-monitoring)
- [19. Cron, Hintergrundjobs und Betriebsautomation](#19-cron-hintergrundjobs-und-betriebsautomation)
- [20. Medien, Dateien und Assets](#20-medien-dateien-und-assets)
- [21. Logging, Fehlerbehandlung und Diagnose](#21-logging-fehlerbehandlung-und-diagnose)
- [22. Best Practices für Entwicklung im 365CMS](#22-best-practices-für-entwicklung-im-365cms)
- [23. Audit-Erkenntnisse, die nicht wieder verloren gehen dürfen](#23-audit-erkenntnisse-die-nicht-wieder-verloren-gehen-dürfen)
- [24. Konkrete Regressionen aus dem Changelog, die nie wieder auftauchen dürfen](#24-konkrete-regressionen-aus-dem-changelog-die-nie-wieder-auftauchen-dürfen)
- [25. Audit-Fokuszonen mit dauerhaft erhöhter Aufmerksamkeit](#25-audit-fokuszonen-mit-dauerhaft-erhöhter-aufmerksamkeit)
- [26. Live-Betrieb und Audit-Readiness](#26-live-betrieb-und-audit-readiness)
- [27. Erweiterte Nicht-wieder-tun-Liste](#27-erweiterte-nicht-wieder-tun-liste)
- [28. Häufige Stolperfallen](#28-häufige-stolperfallen)
- [29. Technische Checkliste vor Änderungen](#29-technische-checkliste-vor-änderungen)
- [30. Abschlussbild](#30-abschlussbild)

---

## 0. Abhängigkeiten, Plattformen und Betriebsgrundlagen

### 0.1 Primäre Plattformanforderungen

365CMS basiert im aktuellen Stand im Kern auf:

- **PHP 8.4+** als Zielplattform
- **MySQL oder MariaDB** als relationale Datenbankbasis
- **Apache 2.4+** mit Rewrite-Unterstützung als typischer Webserver-Kontext
- **PDO** für Datenbankzugriffe
- **Sessions, Cookies, Security Headers, CSP und HSTS** als Sicherheitsgrundlage

### 0.2 Relevante PHP-Fähigkeiten und typische Extensions

Für den vollständigen Produktivbetrieb sind je nach Featurepfad insbesondere diese Laufzeitfähigkeiten relevant:

- `pdo_mysql`
- `mbstring`
- `json`
- `openssl`
- `curl`
- `fileinfo`
- `zip`
- Bildverarbeitung wie `gd` bzw. äquivalente unterstützte Kontexte
- Session-/Cookie-Support

### 0.3 Funktionsbezogene optionale Integrationen

Je nach aktivem Umfang kommen zusätzlich zum Tragen:

- SMTP / Mail-Credentials / OAuth2
- Microsoft-Graph- oder Azure-nahe Mail-/Tokenpfade
- LDAP
- Passkeys / WebAuthn
- Cron-Ausführung
- Dateisystem-Schreibrechte für Cache, Logs, Uploads, Temp- und Updatepfade

### 0.4 Bibliotheks- und Bundle-Kontext

Im Projektkontext relevant sind unter anderem:

- Editor.js
- SunEditor
- Grid.js
- PhotoSwipe
- HTMLPurifier
- Dompdf / TCPDF
- JWT-Komponenten
- WebAuthn-Komponenten
- Mail-/Mime-/Translation-Komponenten

Wichtig ist die Unterscheidung zwischen:

- aktiv genutzter Runtime-Fläche
- gebündelter Vendor-Fläche
- optionalen oder featureabhängigen Integrationen

### 0.5 Betriebsgrundlagen für Entwicklung und Produktion

Mindestens sauber funktionieren müssen:

- Rewrite- und Routing-Verhalten
- Session-Cookies
- Datenbankverbindung
- Schreibrechte für Runtime-Verzeichnisse
- Mail- und Queue-Pfade, sofern genutzt
- Cron oder eine funktional äquivalente Ausführungsschicht

### 0.6 Projektkonkreter Deployment-Kontext

Für dieses Projekt gilt organisatorisch und praktisch:

- Änderungen werden unmittelbar nach Anpassungen per FTP hochgeladen
- das Repository soll den hochgeladenen Stand widerspiegeln
- das Live-System wird nicht aus einer separaten externen Sync-Quelle gespeist

Das ist Teil der technischen Realität und muss bei Doku, Review und Fehlersuche immer mitgedacht werden.

### 0.7 Minimale Betriebs-Checkliste für einen plausiblen Produktivzustand

Ein 365CMS-System ist betrieblich erst dann in einem glaubwürdigen Grundzustand, wenn mindestens diese Fragen positiv beantwortbar sind:

- startet der Bootstrap ohne Fallback- oder Kompatibilitätsfehler?
- sind Datenbank, Sessions und Security-Header aktiv?
- sind Schreibpfade für Logs, Uploads, Cache und temporäre Dateien korrekt gesetzt?
- funktionieren Routing, Auth-Einstieg und mindestens eine zentrale Public-Seite?
- stimmen Runtime-Dateien, hochgeladener Stand und Repo bewusst überein?

Diese Liste ersetzt keinen vollständigen Audit- oder Release-Check, ist aber die kleinste sinnvolle Realitätsschranke gegen „läuft doch irgendwie“.

---

## 1. Systembild in einem Satz

365CMS ist ein modulbasiertes PHP-CMS mit klarer Trennung zwischen Core, Admin, Member-Bereich, Theme-Laufzeit, Plugin-Laufzeit, Service-Layer, Routing, Audit/Security, SEO sowie Performance-/Monitoring-Funktionen; die produktive Laufzeit wird aus dem Verzeichnis `CMS/` gebootet.

### 1.1 Was das praktisch bedeutet

Für die tägliche Entwicklung heißt das:

- fast jede sichtbare Änderung hat mehr als nur eine Dateiebene
- UI-Probleme sind oft Verträge zwischen Entry, Modul, View und Asset
- Security-Themen sitzen nicht isoliert in `Security.php`, sondern quer über Routing, Services, Templates und Betriebslogik
- Releases sind nicht nur Codeänderungen, sondern auch Doku-, Routing-, Rechte-, Asset- und Betriebsänderungen

365CMS sollte daher eher wie eine kleine Anwendungsplattform behandelt werden als wie ein klassisches „Template plus ein paar PHP-Dateien“-System.

### 1.2 Denkschema für Änderungen

Jede Änderung sollte mindestens gegen diese fünf Fragen geprüft werden:

1. Wo liegt die kanonische Runtime?
2. Welcher Entry oder Service ist die eigentliche Wahrheit?
3. Welche gemeinsamen Assets, Hooks oder Wrapper hängen mit dran?
4. Welche Sicherheits- oder Audit-Grenzen werden berührt?
5. Hat die Änderung Auswirkungen auf Live-Betrieb, Doku oder Deployment?

### 1.3 365CMS als Plattform statt Einzelfile-System

365CMS sollte gedanklich als Plattform mit mehreren gleichzeitig aktiven Teilsystemen gelesen werden:

- Core
- Admin
- Public Frontend
- Member-Bereich
- Theme-Runtime
- Plugin-Runtime
- Diagnose-/Cron-/Betriebslogik

Viele Fehler entstehen genau dann, wenn nur ein Teilbereich betrachtet wird, obwohl der eigentliche Vertrag über mehrere dieser Schichten läuft.

### 1.4 Folgen dieser Sichtweise für Reviews und Änderungen

Wer an 365CMS arbeitet, sollte bei jeder Änderung automatisch zwischen drei Ebenen unterscheiden:

- **lokale Ursache** – die Datei oder Funktion, die direkt geändert wird
- **systemische Wirkung** – die betroffene Kette aus Routing, Service, View, Asset, Auth oder Hook
- **betriebliche Wirkung** – Live-Pfade, Deployment, Doku, Logs, Audit und Support-Fähigkeit

Je größer die Differenz zwischen lokaler Ursache und systemischer Wirkung ist, desto höher ist die Regressiongefahr. Genau deshalb brauchen scheinbar kleine Änderungen in 365CMS oft erstaunlich viel Kontext.

---

## 2. Repository- und Laufzeitmodell

### 2.1 Maßgebliche Laufzeit

Die relevante Runtime liegt im Repository `365CMS.DE` unter `CMS/`.

Wichtig:

- `365CMS.DE-THEME/` ist ein Quell-/Pflege-Repository für Themes, aber **nicht automatisch** die aktive Laufzeitquelle.
- `365CMS.DE-PLUGINS/` ist ein Quell-/Pflege-Repository für Plugins, aber **nicht automatisch** die aktive Laufzeitquelle.
- Die produktive Theme-Laufzeit nutzt `CMS/themes/<slug>/`.
- Die produktive Plugin-Laufzeit nutzt `CMS/plugins/<slug>/`.
- Der Bootstrap lädt Theme und Plugins nur aus diesen Runtime-Pfaden.

### 2.2 Relevante Top-Level-Bereiche

- `CMS/` – produktive Anwendung
- `DOC/` – Projektdokumentation
- `ASSETS/` – Entwicklungs-/Vendor-Kontext außerhalb der produktiven `CMS/assets/`-Laufzeit
- `tests/` – Testkontext
- `var/` – Laufzeitnahe Artefakte und Logs außerhalb des Webroots
- `STAGING/`, `BACKUP/`, `IMAGES/` – Betriebs-/Migrations-/Ablagekontext

### 2.3 Architektur-Schichten

Die Systemlogik ist grob in folgende Schichten gegliedert:

1. **Konfiguration** – Konstanten, Pfade, Betriebsparameter
2. **Bootstrap/Core** – Startlogik, Container, DB, Auth, Security, Hooks, Router
3. **Services** – SEO, Suche, Mail, Upload, Medien, Analytics, Tracking, PDF usw.
4. **Module** – Admin-Funktionen nach Domänen organisiert
5. **Themes** – Template-Rendering, Menüs, Customizer-nahe Ausgabe
6. **Plugins** – zusätzliche, aktivierbare Erweiterungen
7. **Member & Public UI** – Frontend- und Benutzerfunktionen

### 2.4 Tatsächlicher Deployment-Vertrag in diesem Projekt

Wichtig für dieses konkrete Projektsetup:

- das Quellrepo ist hier nicht bloß Dokumentation oder Zwischenspeicher, sondern soll immer dem real hochgeladenen Stand entsprechen
- Änderungen werden nach Anpassungen unmittelbar per FTP ausgerollt
- es gibt **keinen** separaten externen Live-Sync, der unabhängig vom Repo einen anderen Stand „nachzieht“
- daraus folgt: Das Repository soll den tatsächlichen FTP-Upload-Zustand widerspiegeln, nicht einen hypothetischen Soll-Zustand

Das ist für Fehlersuche wichtig, weil dadurch Repo-Stand und deployter Stand bewusst eng gekoppelt sind. Wenn es Unterschiede gibt, sind diese ein Sonderfall und nicht der Normalbetrieb.

### 2.5 Konsequenz für Wartung und Review

Bei Änderungen immer unterscheiden zwischen:

- **Quellpflege**: Was wurde im Repo geändert?
- **Runtime-Wirkung**: Welche konkrete Datei unter `CMS/` ist wirklich aktiv?
- **Deployment-Realität**: Ist die geänderte Runtime-Datei bereits hochgeladen worden oder steht der Upload noch aus?

Gerade bei Theme- und Plugin-Arbeit verhindert diese Denkweise die klassische Verwechslung „im Repo geändert“ versus „im Live-System wirksam“.

### 2.6 Typische Irrtümer im Arbeitsalltag

- „Im Quellrepo geändert = automatisch live“ → falsch
- „Im Plugin-Repo vorhanden = automatisch geladen“ → falsch
- „Repo sieht richtig aus = Runtime ist sicher identisch“ → nur dann richtig, wenn die aktive Datei unter `CMS/` gemeint ist und der FTP-Upload erfolgt ist

### 2.7 Praktische Regel für jede Fehlersuche

Vor tieferem Debugging immer zuerst eindeutig bestimmen:

1. welcher Request-Pfad betroffen ist,
2. welche Runtime-Datei ihn tatsächlich bedient,
3. welche Daten-/Setting-Quelle dafür maßgeblich ist,
4. ob genau diese Runtime-Datei bereits dem per FTP hochgeladenen Stand entspricht.

Diese Viererprüfung spart regelmäßig mehr Zeit als jede frühe Hypothese über Framework, Browser oder „mystisches Cache-Verhalten“.

---

## 3. Bootstrap, Betriebsmodi und Startpfad

### 3.1 Zentrale Bootstrap-Klasse

Der Kernstart erfolgt über `CMS/core/Bootstrap.php`.

Die Bootstrap-Klasse übernimmt unter anderem:

- Erkennung des Betriebsmodus
- Laden der Core-Abhängigkeiten
- Prüfung auf Plattform-/PHP-Kompatibilität gebündelter Bibliotheken
- Aufbau des DI-Containers
- Start von Datenbank, Security, Auth, Cache, Loggern
- Ausführung der Migrationen
- Laden der aktiven Plugins
- Laden des aktiven Themes im Web-Modus
- Initialisierung der Hooks und Routing-Phase

### 3.2 Betriebsmodi

`Bootstrap::detectMode()` unterscheidet aktuell folgende Modi:

- `cli` – wenn `PHP_SAPI === 'cli'`
- `api` – wenn der Request mit `/api/` beginnt
- `admin` – wenn der Request mit `/admin/` beginnt oder exakt `/admin` ist
- `web` – Standard für normale Frontend-Anfragen

### 3.3 Modusabhängiges Laden

Nicht alles wird in jedem Modus geladen:

- Router nur außerhalb von `cli`
- ThemeManager nur in `web` und `admin`
- tatsächliches `loadTheme()` nur im `web`-Modus
- einige Frontend-spezifische Services nur im `web`-Modus
- Analytics nur gezielt im `admin`-Modus

### 3.4 Plattformprüfung

Beim Booten wird geprüft, ob gebündelte Libraries eine höhere PHP-Version verlangen als die deklarierte Zielplattform. Bei Inkonsistenzen bricht 365CMS fail-closed mit `503` oder CLI-Fehler ab.

Das schützt vor halb startenden Installationen mit „läuft irgendwie, bis es knallt“-Charakter.

### 3.5 Boot-Reihenfolge mit Seiteneffekten

Die Reihenfolge des Bootstraps ist fachlich relevant. Fehler entstehen häufig dann, wenn Code zwar „irgendwo“ funktioniert, aber zu früh oder im falschen Modus ausgeführt wird.

Kritische Reihenfolgen sind insbesondere:

- Config vor Service-Instanziierung
- Security/Auth vor Admin- oder Member-Mutationen
- Plugin-Laden vor Hook-abhängigen Erweiterungen
- Theme-Laden nur dort, wo Template-Rendering tatsächlich vorgesehen ist
- Router-Dispatch erst nach sauberer Initialisierung der registrierenden Komponenten

### 3.6 Was nicht in den Bootstrap gehört

Nicht in den Bootstrap gehören:

- teure Vollscans ohne klaren Modusbezug
- UI-nahe Logik
- große, nicht lazy geladene Servicebäume ohne tatsächlichen Bedarf
- fachliche Speziallogik einzelner Module
- lose Debug-Helfer oder Betriebs-Workarounds, die nur für einen Sonderfall gedacht sind

Wenn eine Änderung nur für einen einzelnen Admin-Bereich relevant ist, ist der Bootstrap fast nie der richtige erste Angriffspunkt.

### 3.7 Bootstrap-Änderungen sind fast immer systemweit

Schon kleine Anpassungen im Bootstrap können gleichzeitig beeinflussen:

- CLI-Pfade
- API-Pfade
- Admin
- Frontend
- Theme-Laden
- Plugin-Hooks
- Fehlerverhalten

Bootstrap-Arbeit ist deshalb fast nie eine lokale Änderung, sondern immer eine Änderung mit hoher Reichweite.

### 3.8 Typische Bootstrap-Warnsignale

Alarmzeichen bei Bootstrap-nahen Änderungen sind insbesondere:

- zusätzliche Datei- oder Verzeichnis-Scans ohne Modusbegrenzung
- Service-Erzeugung mit sofortigem Remote-I/O
- stille Catch-Blöcke, die nur Fehler verschlucken
- fachliche Spezialfälle, die eigentlich in Router, Modul oder Service gehören
- neue globale Zustände, die später außerhalb des Bootstrap kaum noch nachvollziehbar sind

Wenn eine Änderung eines dieser Muster einführt, ist sie fast immer zu breit angesetzt.

---

## 4. Konfiguration, Konstanten und feste Werte

Die zentrale Konfiguration liegt in `CMS/config/app.php`.

### 4.1 Wichtige Konstanten

| Konstante | Wert / Bedeutung |
|---|---|
| `ABSPATH` | Basis von `CMS/` |
| `CORE_PATH` | `ABSPATH . 'core/'` |
| `THEME_PATH` | `ABSPATH . 'themes/'` |
| `PLUGIN_PATH` | `ABSPATH . 'plugins/'` |
| `UPLOAD_PATH` | `ABSPATH . 'uploads/'` |
| `ASSETS_PATH` | `ABSPATH . 'assets/'` |
| `LOG_PATH` | bevorzugt `var/logs/`, sonst Fallback auf Temp-Pfad |
| `CMS_ERROR_LOG` | `${LOG_PATH}/error.log` |
| `DEFAULT_THEME` | `cms-default` |
| `DB_PREFIX` | Standard `cms_` |
| `SESSIONS_LIFETIME` | `7200` Sekunden = 2 Stunden |
| `MAX_LOGIN_ATTEMPTS` | `5` |
| `LOGIN_TIMEOUT` | `300` Sekunden = 5 Minuten |
| `CMS_HTTPS_REDIRECT_STRATEGY` | Standard `upstream` |
| `CMS_HSTS_MODE` | Standard `https-only` |
| `CMS_HSTS_MAX_AGE` | `31536000` Sekunden |
| `JWT_TTL` | `3600` Sekunden |
| `LDAP_DEFAULT_ROLE` | `member` |

### 4.2 Konfigurationsprinzipien

- `config/app.php` ist als Template vorbereitet und wird durch den Installer vervollständigt.
- Security-Keys sollen per `random_bytes()` generiert werden.
- Keine echten Zugangsdaten oder Secrets gehören in die Versionsverwaltung.
- Logging wird, wenn möglich, außerhalb des Webroots in `var/logs/` abgelegt.
- `SITE_URL` darf nicht mit einem Unterverzeichnis definiert werden.

### 4.3 Debug-Verhalten

- `CMS_DEBUG = false` ist der sichere Produktivzustand.
- Im Debug-Modus werden Error Reporting und sichtbare Fehler aktiviert.
- CSP läuft im Debug-Modus in `Report-Only` statt enforced.
- HSTS wird im Debug-Modus nicht aggressiv erzwungen.

### 4.4 Konfigurationsquellen und Vertrauensebenen

Im System existieren mehrere Arten von Konfiguration:

- **statische Grundkonfiguration** in `config/app.php`
- **persistierte Laufzeitkonfiguration** in `cms_settings`
- **situative Request-Konfiguration** durch Routing, Query, Session und Modus
- **Theme-/Plugin-nahe Konfiguration** in `theme.json`, Plugin-Metadaten oder Modulsettings

Diese Ebenen dürfen nicht beliebig vermischt werden. Besonders wichtig:

- globale Sicherheits- und Pfadparameter gehören nicht in Theme- oder UI-Settings
- Admin-Formulare dürfen Konfigwerte nicht stillschweigend in andere Schichten „übersetzen“, ohne den Vertrag zu dokumentieren
- bei Schlüsselwerten wie Theme, Plugin-Status, Mail, SEO, Auth und Cron immer prüfen, welche Quelle die kanonische Wahrheit ist

### 4.5 Konfigänderungen mit Seiteneffekt

Ein Konfigwechsel kann Folgearbeiten auslösen, z. B.:

- Cache-Invalidierung
- Redirect-/Routing-Änderungen
- Reindexing
- andere Theme-/Plugin-Ladepfade
- neue Sicherheitsheader oder Auth-Verhalten

Konfigänderungen sind daher nie nur „Wert speichern“, sondern oft Systemzustandsänderungen.

### 4.6 Konfigurationshygiene

Neue Konfigschlüssel sollten:

- klar benannt sein
- genau einer Schicht gehören
- nicht still bestehende Schlüssel semantisch umdeuten
- in Doku, UI und Runtime möglichst dieselbe Sprache sprechen

### 4.7 Konfigurationen brauchen Eigentümer

Jeder relevante Konfigwert sollte gedanklich einen klaren Eigentümer haben:

- Core-Sicherheitsparameter → Core
- Theme-nahe Darstellung → Theme/Customizer
- modulbezogene UI-Einstellungen → betroffener Admin-/Modulbereich
- Betriebsparameter wie Mail, Cron, Logging → System-/Betriebskontext

Fehlt dieser Eigentümer, endet die Einstellung oft als schwer wartbarer Mischwert zwischen UI, Settings-Tabelle und stiller Runtime-Annahme.

---

## 5. Dependency Injection und zentrale Core-Komponenten

### 5.1 Container

`CMS/core/Container.php` bildet den zentralen Service-Container.

Typische Muster:

- `bindInstance()` für konkrete Instanzen
- `singleton()` für lazy Services
- zentrale Alias-Namen wie `db`, `logger`, `cache`, `mail`, `search`, `seo`

### 5.2 Kernkomponenten mit hoher Relevanz

- `Database`
- `Security`
- `Auth`
- `Logger`
- `AuditLogger`
- `Hooks`
- `Router`
- `PluginManager`
- `ThemeManager`
- `CacheManager`
- `MigrationManager`
- `SchemaManager`

### 5.3 Lazy-Load-Strategie

Viele Services werden erst bei erstem Zugriff instanziiert. Das reduziert den Initialisierungs-Overhead und verhindert unnötige Arbeit pro Request.

Beispiele:

- `PurifierService`
- `MailService`
- `MailQueueService`
- `SearchService`
- `ImageService`
- `SEOService`
- `ThemeCustomizer`
- `PdfService`

### 5.4 Container-Regeln für spätere Erweiterungen

Wenn neue Services hinzukommen, sollten sie möglichst:

- eine klare Fachverantwortung haben
- lazy registriert werden, wenn sie nicht immer pro Request nötig sind
- keine versteckten Seiteneffekte im Konstruktor auslösen
- keine implizite Abhängigkeit von globalen Zuständen haben, die außerhalb des Containers nicht sichtbar ist

### 5.5 Typische Anti-Patterns

Ungünstig sind:

- Service-Locator-Missbrauch aus tiefen Views heraus
- globale Singleton-Aufrufe für reine View-Helfer ohne klare Notwendigkeit
- Klassen, die gleichzeitig Daten laden, rendern, loggen und redirecten
- Container-Einträge, die beim ersten Zugriff bereits teure Remote- oder Dateisystemarbeit starten, obwohl nur ein kleiner Aspekt gebraucht wird

### 5.6 Kandidaten für weitere Trennung

Sobald eine Klasse gleichzeitig Request-Normalisierung, Fachlogik, Persistenz, Alert-Aufbereitung und ViewModel-Bau übernimmt, ist sie ein guter Kandidat für spätere Aufteilung.

### 5.7 Gute Container-Nutzung im Alltag

Im Alltag heißt gute Container-Nutzung vor allem:

- Abhängigkeiten möglichst explizit statt heimlich aus globalem Zustand beziehen
- Services so bauen, dass sie in Admin, Cron und Public gleichermaßen verständlich funktionieren
- Views nicht zu versteckten Service-Locator-Kunden machen
- neue Service-Aliase nur anlegen, wenn sie auch fachlich sinnvoll und langfristig verständlich sind

Der Container soll Komplexität ordnen, nicht sie hübscher verstecken.

---

## 6. Sicherheitsarchitektur

365CMS arbeitet nach einem klaren Defense-in-Depth-Ansatz. Es gibt nicht „den einen Schutz“, sondern mehrere Schutzschichten.

### 6.1 Sicherheitsziele

- Schutz vor CSRF
- Schutz vor XSS
- Schutz vor SQL Injection
- Schutz vor Session Fixation und Session Hijacking
- Schutz vor unsicheren Theme-/Plugin-Bootstraps
- Schutz vor Rate-Limit-basierten Login-Angriffen
- Schutz durch Security Headers und Transporthärtung
- Nachvollziehbarkeit durch Audit Logging

### 6.2 CSRF

Die CSRF-Logik sitzt in `CMS/core/Security.php`.

Eigenschaften:

- Token-Generierung pro Aktion
- Standardgültigkeit: 1 Stunde
- One-shot-Invaliderung nach erfolgreicher Prüfung
- zusätzliche persistente Prüfvariante für Spezialfälle mit vielen Folge-Requests

Wichtig für Entwickler:

- Erfolgreich verifizierte Standard-CSRF-Tokens werden gelöscht.
- Wenn eingebettete Admin-Shells bereits verifiziert haben, darf die Unterkomponente nicht blind denselben Token erneut prüfen.
- Formulare und AJAX-Endpunkte brauchen immer einen sauberen CSRF-Vertrag.

### 6.3 Sessions

Beim Session-Start werden u. a. gesetzt:

- `session.cookie_httponly = 1`
- `session.cookie_secure = 1` bei HTTPS
- `session.use_strict_mode = 1`
- Session-ID-Regeneration beim Initialisieren der Session

Das minimiert Session-Fixation-Risiken.

### 6.4 Password Hashing

Passwörter werden mit `PASSWORD_BCRYPT` und Cost `12` behandelt. Historisch schwache Verfahren wie MD5 oder SHA1/SHA256 als Passwortspeicher sind nicht vorgesehen.

### 6.5 Rate Limiting

Es gibt zwei Ebenen:

1. Session-basiertes Fallback-Rate-Limit
2. Datenbankbasiertes Rate-Limit über `login_attempts`

Das DB-basierte Rate-Limit berücksichtigt:

- IP-Adresse
- Aktion, z. B. `login`
- Zeitfenster
- erlaubte Maximalversuche

Standardwerte:

- `MAX_LOGIN_ATTEMPTS = 5`
- `LOGIN_TIMEOUT = 300`

### 6.6 Security Headers

Ausgegeben werden unter anderem:

- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- `Cross-Origin-Opener-Policy: same-origin`
- `Cross-Origin-Resource-Policy: same-site`
- `Strict-Transport-Security` bei aktivem HTTPS und Nicht-Debug
- `Content-Security-Policy` bzw. `Content-Security-Policy-Report-Only`

### 6.7 CSP und Nonce-Modell

Die CSP arbeitet nonce-basiert.

Merkmale:

- pro Request generierte Nonce
- Einbau in Header und Templates
- keine pauschalen `unsafe-inline`-Freifahrtscheine
- Trusted-Types-Regeln sind vorgesehen
- im Debug-Modus Report-Only

### 6.8 XSS-Schutz

Die XSS-Abwehr ist mehrschichtig:

- konsequentes `htmlspecialchars()` in Templates
- `Security::escape()` als Hilfsweg
- Sanitizing von Eingaben je nach Typ
- spezialisierte HTML-Reinigung über `PurifierService`
- CSP als zusätzliche Laufzeitbarriere

### 6.9 SQL-Injection-Schutz

Die DB-Schicht ist PDO-basiert und erwartet Prepared Statements statt String-Bastelei.

Regeln:

- niemals Benutzereingaben direkt in SQL interpolieren
- immer `prepare()` + `execute()`
- Tabellennamen nur aus vertrauenswürdigem, internem Kontext ableiten

### 6.10 Theme- und Plugin-Härtung

**Themes**

- Theme-Pfade werden via `realpath()` gegen Path Traversal validiert.
- `functions.php` eines Themes wird auf gefährliche Funktionen geprüft.
- Bei Ladefehlern kann ein Rollback auf `DEFAULT_THEME` erfolgen.

**Plugins**

- Aktivierung prüft Bootstrap-Datei, Abhängigkeiten und Sicherheitsmuster.
- Sicherheits-Scan blockiert u. a. nackte Aufrufe von `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `pcntl_exec`.
- ZIP-Installationen prüfen auf unsichere Archivpfade.

### 6.11 Upload-Sicherheit

Die Upload-Pipeline wird durch Services abgesichert. Typische Sicherungen sind:

- MIME-/Dateitypprüfung
- kontrollierte Zielpfade
- sichere Dateinamen bzw. Umbenennungen
- Trennung in Upload-Verzeichnisse
- keine Vertrauensannahme nur anhand der Dateiendung

### 6.12 Audit Logging

Sicherheitsrelevante Vorgänge werden über `AuditLogger` protokolliert.

Typische Kategorien:

- Security
- Plugin
- Theme
- Login/Auth
- Systemaktionen

Das Audit Log ist essenziell für Fehleranalyse, Compliance und Incident Review.

### 6.13 Sicherheitsfolgefragen bei jeder Änderung

Bei jeder nicht-trivialen Änderung sollten diese Fragen beantwortbar sein:

- kann Benutzereingabe hier in HTML, SQL, Dateipfade, Header, JSON oder Redirect-Ziele gelangen?
- wird eine Mutation durch Capability und CSRF geschützt?
- entsteht eine neue Remote-Abhängigkeit oder Downloadkante?
- wird ein bestehender Schutzvertrag geschwächt, z. B. durch Fallbacks oder stillere Fehlerbehandlung?
- landen technische Details in UI-Ausgaben statt in Logs?

### 6.14 Typische Security-Hotspots in 365CMS

Besonders sensibel sind:

- Marketplace- und Update-Pfade
- Theme-Editor und Theme-Explorer
- Media-Upload, Member-Medien und Dateiauslieferung
- Redirect- und Auth-Weiterleitungen
- HTML- oder Editor-basierte Content-Senken
- Mail-, Feed- und andere Remote-Integrationen

Diese Stellen sollten grundsätzlich mit höherem Misstrauen behandelt werden als reine Read-only-Views.

### 6.15 Admin-Security ist keine Nebenklasse

Admin-Pfade sind oft gefährlicher als Public-Pfade, weil sie Mutationen, Dateisystemzugriff, privilegierte Rollen und Remote-Integration kombinieren. Deshalb dürfen Admin-XSS, schwache Redirects oder Upload-/Marketplace-Probleme nie als „nur intern“ verharmlost werden.

### 6.16 Sicherheitsdenken für neue Features

Neue Features sollten immer mindestens gegen diese Angriffsklassen durchdacht werden:

- Eingabemissbrauch
- Rollen-/Capability-Umgehung
- Pfad- oder Dateisystemmissbrauch
- unkontrollierte Remote-Inhalte
- offene Redirects oder Hostwechsel
- Informationsleck über Fehlermeldungen, JSON oder Debugpfade

Wenn ein Feature zu keiner dieser Klassen Stellung nehmen kann, ist die Sicherheitsbetrachtung fast sicher noch zu oberflächlich.

---

## 7. Authentifizierung, MFA, Passkeys, LDAP, JWT

### 7.1 Auth-Stack

365CMS unterstützt mehrere Authentifizierungsbausteine:

- klassische Session-/Passwort-Anmeldung
- MFA/TOTP
- Backup-Codes
- Passkeys/WebAuthn
- LDAP
- JWT für API-nahe Szenarien

### 7.2 Kanonische Auth-Routen

Die Core-Authentifizierung läuft aktuell über:

- `/cms-login`
- `/cms-register`
- `/cms-password-forgot`

Diese Routen sind core-seitig verankert und nicht an ein einzelnes Frontend-Theme gekoppelt.

### 7.3 Routing der Auth-Seiten

Die Public-Router-Registrierung umfasst GET/POST für Login, Register und Forgot Password. Damit ist die Auth-Strecke nicht bloß ein Template-Trick, sondern fester Teil des Public-Routings.

### 7.4 Passkeys/WebAuthn

Passkeys werden über eigene Auth-Komponenten verwaltet. Das System unterstützt damit moderne phish-resistente Anmeldepfade.

### 7.5 LDAP

LDAP ist optional und konfigurierbar über:

- `LDAP_HOST`
- `LDAP_PORT`
- `LDAP_BASE_DN`
- `LDAP_USERNAME`
- `LDAP_PASSWORD`
- `LDAP_USE_SSL`
- `LDAP_USE_TLS`
- `LDAP_FILTER`
- `LDAP_DEFAULT_ROLE`

### 7.6 JWT

JWT-Konstanten:

- `JWT_SECRET`
- `JWT_TTL`
- `JWT_ISSUER`

Wenn `JWT_SECRET` leer ist, kann ein Fallback auf `AUTH_KEY` erfolgen. Für produktive API-Nutzung ist ein dediziertes Secret sauberer.

### 7.7 Auth-Regressionen, gegen die immer geprüft werden sollte

Bei Änderungen an Login-, Register-, MFA-, Passkey-, LDAP- oder Sessionpfaden immer testen:

- erfolgreicher Login ohne MFA
- erfolgreicher Login mit MFA
- Remember-Me-Verhalten
- Rücksprungpfade nach Auth
- Passwort-Reset-Ende-zu-Ende
- sprachabhängige Auth-Pfade
- Verhalten ohne aktives Theme-Spezialtemplate

### 7.8 Auth ist Prozess, nicht nur Formular

Der gefährlichste Denkfehler ist, Auth nur als Seitenformular zu sehen. In Wahrheit besteht der Vertrag aus:

- Routing
- Formular-/POST-Handling
- Session-Abschluss
- MFA-/Passkey-Zwischenschritten
- Redirect-Regeln
- Rate-Limits
- UI- und Doku-Konsistenz

Wenn nur ein Teil davon geändert wird, ohne die Kette zu prüfen, entstehen klassische „Login klappt irgendwie, aber am Ende landet man falsch oder fliegt wieder raus“-Fehler.

### 7.9 Auth immer live-nah prüfen

Auth-Pfade gehören zu den Bereichen, die nach Änderungen und Uploads besonders konservativ gegengeprüft werden sollten:

- lädt die Seite?
- funktioniert POST?
- bleibt der Rücksprung intern?
- bleibt MFA/Passkey im selben Session-Vertrag?
- leaken Fehlertexte nicht?

### 7.10 Auth-Änderungen haben Vertrauenseffekt

Fehler in Auth-Pfaden sind nicht nur technische Defekte, sondern Vertrauensschäden:

- Nutzer verlieren Sitzungen oder gelangen in Loops
- MFA wirkt unzuverlässig
- Passwort-Reset erscheint unsicher
- Login-Routen verhalten sich inkonsistent je nach Theme, Sprache oder Host

Darum gehören Änderungen an Auth, MFA, Passkeys, LDAP oder Session-Handling zu den Bereichen, die besonders konservativ entwickelt und besonders direkt live geprüft werden sollten.

---

## 8. Datenbank, Schema und Migrationen

### 8.1 Grundmodell

365CMS verwendet ein relationales Schema mit `utf8mb4` und InnoDB-orientiertem Design.

Wichtige Eigenschaften:

- Key-Value-Konfiguration über `cms_settings`
- Sessions und Login-Attempts in Tabellenform
- Content in separaten Tabellen für Seiten, Beiträge, Revisionen, Taxonomien
- SEO-/Redirect-/404-Daten in eigenen Tabellen
- Audit-/Analyse-/Systemdaten in dedizierten Strukturen

### 8.2 SchemaManager vs. MigrationManager

- `SchemaManager` kümmert sich um idempotente Grundstrukturen
- `MigrationManager` führt inkrementelle Versionsmigrationen aus

Das Ziel ist, Neuinstallation und Bestandsmigration nicht zu vermischen.

### 8.3 Installer-Fallback

`install.php` enthält zusätzlich eine Tabelleninitialisierung als Installationspfad. Diese Logik muss mit dem eigentlichen Schema-/Migrationsmodell synchron bleiben.

### 8.4 Kritische Tabellenklassen

Typische Kernobjekte:

- `cms_settings`
- `cms_sessions`
- `cms_users`
- `cms_user_meta`
- `cms_pages`
- `cms_posts`
- `cms_comments`
- `cms_audit_log`
- `cms_redirect_rules`
- `cms_not_found_logs`
- `cms_cache`
- `login_attempts`

### 8.5 Einstellungen als Persistenzzentrum

`cms_settings` ist eine zentrale Schaltstelle für:

- aktives Theme
- aktive Plugins
- Menükonfigurationen
- Theme-/Site-Optionen
- SEO-Einstellungen
- Systemoptionen

Das ist bequem, aber auch ein Grund, Änderungen an Settings mit Respekt zu behandeln. Ein einzelner falscher Key kann überraschend viel Wirkung entfalten.

### 8.6 Fremdschlüssel und Namenskonventionen

Bei Plugin- oder Modultabellen müssen Foreign-Key-Namen schemaweit eindeutig sein. Generische Constraint-Namen sind fehleranfällig, speziell bei frischen Installationen oder mehrfachen Deployments.

### 8.7 Migrationsregeln für künftige Änderungen

Migrationen sollten:

- idempotent sein
- bestehende Daten respektieren
- neue Spalten oder Indizes explizit prüfen, bevor sie angelegt werden
- keine Annahmen über bereits perfekte Altzustände machen
- Fehler so loggen, dass produktive Probleme nachvollziehbar bleiben

### 8.8 Datenmodell-Fallen im Alltag

Häufige Fehlerquellen im 365CMS-Datenmodell sind:

- Settings als Schatten-Datenbank für zu viele fachliche Zustände
- implizite String- statt Bool-/Int-Verträge
- Alt- und Neuinstallationslogik, die auseinanderdriften
- Foreign-Key-Namen, die lokal harmlos wirken, aber schemaweit kollidieren
- Löschpfade, die Suchindex, Caches oder Beziehungstabellen nicht nachziehen

### 8.9 Schemaarbeit ist auch Betriebsarbeit

Tabellen- und Migrationsänderungen wirken nie nur auf SQL, sondern auch auf Installer, Updates, Diagnose, Backup/Restore, Suchindex und bestehende Produktivdaten.

### 8.10 Datenbanksicherheit und Datenqualität zusammen denken

Sauberes Schema-Design dient nicht nur Performance oder Schönheit, sondern auch Sicherheit und Diagnose:

- eindeutige Constraints reduzieren Schattenzustände
- sinnvolle Indizes stabilisieren produktive Listen- und Admin-Pfade
- explizite Datentypen begrenzen implizite Fehlinterpretationen
- klar benannte Tabellen und FKs verbessern Support, Logs und Migrationsfehleranalyse

Ein schwer lesbares Schema erzeugt fast immer irgendwann auch schwer lesbare Betriebsprobleme.

---

## 9. Content-Modell und Mehrsprachigkeit

### 9.1 Seiten und Beiträge

Content wird im Kern vor allem über `cms_pages` und `cms_posts` verwaltet.

### 9.2 Lokalisierung

Das System verwendet für Englisch zusätzliche Felder mit `_en`-Suffix, z. B.:

- `title_en`
- `slug_en`
- `content_en`

Das ist keine voll generische Translation Engine pro Feldfamilie, sondern ein pragmatisches zweisprachiges Schema mit starker DE/EN-Ausrichtung.

### 9.3 Revisionen

Revisionen werden gesondert gespeichert, typischerweise JSON-basiert. Das ermöglicht Wiederherstellung und Nachvollziehbarkeit.

### 9.4 Kategorien, Tags und Beziehungen

Beiträge arbeiten mit Kategorisierung/Tagging über Beziehungstabellen und Admin-Module. Diese Logik ist relevant für Listings, SEO, Suche und Archive.

### 9.5 Editoren

Es gibt mehrere Editor-Kontexte:

- `Editor.js` für Block-Editing
- `SunEditor`/EditorService-Kontext für weitere Content-Pfade

Wichtig:

- sichtbare Block-Buttons im Live-Admin-Editor müssen nicht nur in Asset-Services, sondern auch im Live-Admin-JavaScript gepflegt werden
- Editor-UI und Render-Logik sind nicht automatisch dasselbe

### 9.6 Publikations- und Sichtbarkeitslogik

Content ist nicht nur „gespeichert oder nicht gespeichert“, sondern hat Sichtbarkeitszustände.

Besonders relevant:

- `draft`
- `published`
- `scheduled`
- `private`

Diese Zustände müssen in Admin, Routing, SEO, Suche, Archive, Sitemap und Analytics gleich gedacht werden. Sobald unterschiedliche Schattenlogik entsteht, tauchen typische Inkonsistenzen auf.

### 9.7 Mehrsprachige Content-Pflege praktisch gedacht

Die Zweisprachigkeit verlangt bei Änderungen an Content-Pfaden immer zusätzliche Kontrolle:

- Werden `_en`-Felder korrekt mitgeladen?
- Wird bei Save, Delete oder Reindexing beide Sprachseiten mitgedacht?
- Ist das Frontend wirklich sprachkonsistent oder mischt es Strings, Labels oder Links?
- Sind EN-Pflichtseiten und Gegenpfade real vorhanden?

Gerade in Live-Systemen werden diese Fehler oft erst sichtbar, wenn Nutzer die englische Seite tatsächlich benutzen.

### 9.8 Content gilt erst als fertig, wenn die ganze Kette stimmt

Gespeicherter Inhalt ist erst dann wirklich korrekt integriert, wenn er auch:

- im richtigen Status sichtbar ist
- korrekt rendert
- in Suche und SEO konsistent auftaucht
- sprachlich sauber bleibt
- in Archiven, Sitemaps und Gegenpfaden sinnvoll erscheint

### 9.9 Content-Arbeit ist immer auch Redaktions-UX

Selbst fachlich korrekter Content-Code ist unvollständig, wenn Redakteure ihn nicht zuverlässig bedienen können. Deshalb immer mitdenken:

- bleibt der Editor nachvollziehbar?
- sind Block- oder Feldzustände sichtbar und konsistent?
- bleiben Vorschau, Speichern, Rücksprung und Statuswechsel verständlich?
- verhält sich DE/EN im Bearbeitungsfluss konsistent?

Viele spätere Inhaltsfehler beginnen nicht im Renderer, sondern in einem unklaren Bearbeitungsfluss.

---

## 10. Routing und Request-Fluss

### 10.1 Router-Familien

Das Routing ist aufgeteilt in:

- `AdminRouter`
- `PublicRouter`
- `ThemeRouter`
- `MemberRouter`
- `ApiRouter`

### 10.2 Grober Ablauf

1. Bootstrap erkennt Modus
2. Core und Services werden geladen
3. Plugins registrieren Hooks/Routen
4. Router dispatcht Anfrage
5. Theme- bzw. Public-/Member-/Admin-Logik rendert Ergebnis

### 10.3 ThemeRouter

Der ThemeRouter entscheidet, welche Templates für Seiten, Beiträge, Archive und Sonderfälle gezogen werden.

### 10.4 Admin-Pfade

Admin-Pfade leben unter `/admin/*`. Der Admin-Bereich arbeitet stark mit Modulen, View-Dateien und Shell-/Partial-Strukturen.

### 10.5 Member-Pfade

Member-Funktionalität liegt unter `/member/*`.

### 10.6 API-Pfade

API-Pfade hängen unter `/api/*`.

### 10.7 Routing-Prioritäten und Fallstricke

Routingfehler wirken oft wie Inhalts- oder UI-Fehler, sind aber strukturell tiefer.

Typische Fallstricke:

- Redirects, die aus rohen Request-Parametern entstehen
- Theme-Templates, die Core-Routen überschreiben oder implizit nachbilden
- Sprachpfade, die nicht deterministisch normalisiert werden
- Admin-Links, die intern absolute Hosts statt relative Pfade benutzen
- Such-, Archiv- und Spezialpfade, die andere Sichtbarkeitsregeln anwenden als Listen oder Sitemaps

### 10.8 Routing-Testdenken

Bei Änderungen an Router, Redirects oder URL-Generierung sollte mindestens geprüft werden:

- funktioniert der Happy Path?
- funktioniert derselbe Pfad unter anderem Host/Proxy/Rewrite-Setup?
- bleiben Redirects intern?
- bleiben Query und Sprache konsistent?
- landen Fehlerzustände auf kontrollierten Zielen statt in Loops oder Fremdhosts?

### 10.9 Routing ist auch Sicherheits- und Betriebslogik

Routing entscheidet in 365CMS nicht nur über URL-Auflösung, sondern auch über:

- Capability- und Kontextgrenzen
- Redirect-Sicherheit
- Locale-Verhalten
- Theme-/Template-Pfade
- Live-Erreichbarkeit produktionskritischer Seiten

### 10.10 Routing-Änderungen brauchen Gegenprüfung in Nachbarbereichen

Nach Routing-Anpassungen sollten nie nur die direkt betroffenen URLs geprüft werden, sondern mindestens auch:

- Auth-Rücksprünge
- Footer- und Pflichtseitenlinks
- Sprachwechsel und Gegenpfade
- Sitemaps, Redirects und 404-Verhalten
- Admin-Links, falls Hosts, Basispfade oder Rewrite-Regeln berührt wurden

Routing ist die Art von Änderung, die ihren Schaden gern an ganz anderer Stelle sichtbar macht.

---

## 11. Service-Layer

Der Service-Layer in `CMS/core/Services/` ist einer der wichtigsten Stabilitäts- und Erweiterungspunkte.

### 11.1 Service-Familien

**Mail & Kommunikation**

- `MailService`
- `MailQueueService`
- `MailLogService`
- `AzureMailTokenProvider`
- `GraphApiService`

**SEO & Indexing**

- `SEOService`
- `SeoAnalysisService`
- `RedirectService`
- `IndexingService`
- SEO-Unterservices

**Suche**

- `SearchService`

**Medien & Upload**

- `MediaService`
- `MediaDeliveryService`
- `ImageService`
- `FileUploadService`
- Media-Unterservices

**Editoren & Content**

- `EditorJsService`
- `EditorJsRenderer`
- `EditorService`
- `CommentService`
- `PdfService`

**Performance & Betrieb**

- `CoreWebVitalsService`
- `OpcacheWarmupService`
- `BackupService`
- `SystemService`
- `StatusService`
- `UpdateService`

**Member / Messaging / Tracking**

- `MemberService`
- `MessageService`
- `TrackingService`
- `FeatureUsageService`
- `AnalyticsService`

### 11.2 Service-Prinzipien

- möglichst kapselnde Fachlogik statt verstreuter Controller-Logik
- lazy instanziert, wenn sinnvoll
- zentrale Verwendung über Container oder `getInstance()`-Muster
- wiederverwendbar zwischen Admin, Public, Member und Plugins

### 11.3 Service-Design-Regeln

Ein guter 365CMS-Service:

- kapselt genau einen fachlichen Verantwortungsbereich oder einen klaren Teil davon
- nimmt normalisierte Daten entgegen statt rohe `$_POST`-/`$_GET`-Strukturen
- produziert nachvollziehbare, geloggte Fehlerzustände
- trennt Read-, Write- und Render-nahen Zustand möglichst sauber
- bleibt auch dann brauchbar, wenn er aus Admin, Cron, API oder Frontend aufgerufen wird

### 11.4 Service-Hotspots

Erfahrungsgemäß besonders sensibel sind Services mit mindestens einem dieser Merkmale:

- Remote-I/O
- Dateisystem-I/O
- große Payloads
- HTML- oder Binary-Ausgabe
- Auth-/Redirect-/Mail-/Indexing-Verhalten
- implizite Zustandsänderung nach Save/Delete

Solche Services brauchen stärkere Guards, klarere Fehlerverträge und häufiger Regressionstests.

### 11.5 Service-Verträge explizit halten

Ein Service sollte nach außen möglichst klar erkennen lassen:

- welche Eingaben er erwartet,
- welche Seiteneffekte er haben kann,
- welche Fehlerarten entstehen können,
- ob er für Admin, Public, Cron und API gleichermaßen geeignet ist,
- ob er Dateien, Netzwerk, Settings oder Datenbankzustand verändert.

Je expliziter dieser Vertrag ist, desto leichter lassen sich spätere Module, Hooks und Views stabil darauf aufbauen.

---

## 12. SEO, Redirects, 404 und Sichtbarkeit

### 12.1 SEO-Bereich

365CMS enthält eine ernstzunehmende SEO-Schicht und nicht nur ein Metabox-Alibi.

### 12.2 Kernfunktionen

- Meta-Daten-Verwaltung
- Social/OpenGraph/Twitter-Kontext
- Schema.org-Ausgabe
- Sitemap-Erzeugung
- technische SEO-Analyse
- Redirect-Management
- 404-Erfassung
- IndexNow-/Indexing-Unterstützung

### 12.3 Redirect-Management

Das Redirect-System verwaltet Regeln für z. B. 301/302-Weiterleitungen.

Wichtig für Weiterentwicklungen:

- Redirect-Logik und 404-Monitoring teilen sich Frontend-/Admin-JavaScript
- Bugfixes dürfen nicht nur in einer einzelnen Admin-Seite landen, wenn die tatsächliche Funktionalität im gemeinsamen Script sitzt

### 12.4 404-Monitor

404er werden protokolliert, damit häufige Fehlpfade erkannt und in Redirect-Regeln überführt werden können.

### 12.5 Robots und Sitemap

Die Robots-/Sitemap-Logik ist systemweit relevant. Änderungen an URL-Regeln, Publishing oder Visibility-Einstellungen müssen diese Pfade mitdenken.

### 12.6 Suchindex

Die interne Suche muss bei mehrsprachigem Content sowohl Standard- als auch `_en`-Felder berücksichtigen. Außerdem müssen die korrekten Save-Hooks verwendet werden, damit der Index nach Änderungen aktuell bleibt.

### 12.7 SEO ist kein isoliertes Modul

SEO hängt in 365CMS an vielen Querschnittsstellen:

- Content-Sichtbarkeit
- Routing
- Redirects
- 404-Monitoring
- Sitemap
- Suchindex
- Head-Rendering
- Rechtliche und vertrauensbildende Seiten

Eine vermeintlich kleine Änderung an Permalinks, Sprachen, Status oder Theme-Templates kann deshalb unerwartet SEO-Nebenwirkungen haben.

### 12.8 SEO-Release-Checks

Vor Releases oder größeren Routing-/Content-Änderungen sinnvoll prüfen:

- liefern wichtige Seiten saubere Statuscodes?
- werden keine geplanten oder privaten Inhalte versehentlich öffentlich gezählt oder indexiert?
- bleiben Weiterleitungen intern, eindeutig und loopfrei?
- sind Sitemap, Robots und IndexNow-Keydatei konsistent?
- funktionieren 404-Übernahme und Redirect-Anlage im Admin weiter robust?

### 12.9 Sichtbarkeit als systemweiter Vertrag

Sichtbarkeit entsteht in 365CMS nie nur durch einen einzelnen Schalter. Sie hängt gleichzeitig an:

- Content-Status
- Routing
- Theme-Ausgabe
- SEO-Metadaten
- Sitemap-/Robots-Regeln
- Redirects und historischen Fehlpfaden

Deshalb sind SEO-Bugs oft in Wahrheit Konsistenzbugs zwischen mehreren Schichten.

---

## 13. Plugin-System

### 13.1 Grundprinzip

Aktive Plugins werden über den `PluginManager` geladen.

### 13.2 Quelle aktiver Plugins

Die Liste aktiver Plugins kommt aus `cms_settings`, Key `active_plugins`.

### 13.3 Bootstrap-Vertrag

Für ein Plugin wird eine Bootstrap-Datei im Muster erwartet:

- `CMS/plugins/<slug>/<slug>.php`

### 13.4 Protected Plugins

Aktuell ist mindestens `cms-importer` als geschütztes Plugin hinterlegt und darf nicht beliebig gelöscht werden.

### 13.5 Aktivierungslogik

Bei Aktivierung werden geprüft:

- Existenz der Bootstrap-Datei
- CMS-Versionserfordernisse
- Plugin-Abhängigkeiten
- Sicherheitsmuster im Code

### 13.6 Deaktivierung fehlender Plugins

Wenn ein Plugin als aktiv gespeichert ist, aber im Runtime-Pfad fehlt, wird es automatisch aus dem aktiven Satz entfernt und auditiert.

### 13.7 Plugin-Installation per ZIP

Installationen berücksichtigen:

- MIME-/Typprüfung
- Größenlimit
- ZIP-Öffnung
- Prüfung gegen Path Traversal / ZIP Slip

### 13.8 Konsequenz für Entwicklung

Ein Plugin im Quellrepo `365CMS.DE-PLUGINS/` ist **nicht** automatisch live. Es muss in die Runtime `CMS/plugins/` gelangen, bevor der Core es bootet.

### 13.9 Plugin-Lifecycle praktisch

Ein Plugin sollte idealerweise diese Stationen sauber bestehen:

1. Metadaten und Bootstrap-Datei sind vorhanden
2. Sicherheits- und Abhängigkeitsprüfung bestehen
3. Aktivierung trägt sich konsistent in die Settings ein
4. Hooks und optionale Installer laufen deterministisch
5. Deaktivierung und ggf. Entfernen hinterlassen keinen kaputten aktiven Zustand

### 13.10 Plugin-Qualitätsregeln

Bei neuen oder überarbeiteten Plugins immer beachten:

- keine Annahme, dass das Plugin-Quellrepo bereits Runtime ist
- keine stillen Fallbacks, wenn Abhängigkeiten fehlen
- Admin-Pfade, Assets und Hooks klar dokumentieren
- Dateisystem- und ZIP-Pfade streng begrenzen
- Install-/Update-/Delete-Verhalten auditierbar halten

### 13.11 Plugins als Laufzeitbürger behandeln

Ein gutes Plugin verhält sich wie ein sauberer Laufzeitbürger:

- es erweitert bestehende Verträge statt sie heimlich zu überschreiben
- es respektiert Hook-, Routing- und Capability-Grenzen des Core
- es kann bei Fehlern sauber deaktiviert, ignoriert oder diagnostiziert werden
- es macht seine Abhängigkeiten sichtbar, statt sie still vorauszusetzen

Plugins sollen das System ergänzen, nicht die Grundannahmen des Systems heimlich austauschen.

---

## 14. Theme-System

### 14.1 Grundprinzip

Der `ThemeManager` verwaltet aktives Theme, Theme-Wechsel, Rendering, Menüs und bestimmte Site-/Customizer-bezogene Ausgaben.

### 14.2 Aktives Theme

Das aktive Theme kommt primär aus `cms_settings`, Key `active_theme`.

### 14.3 Laufzeitpfad

Kanonisch ist:

- `CMS/themes/<slug>/`

Nicht kanonisch für die Live-Laufzeit:

- `365CMS.DE-THEME/<slug>/`

### 14.4 Theme-Sicherheitschecks

Vor Laden/Wechsel werden u. a. berücksichtigt:

- `realpath()`-Prüfung
- Syntaxprüfung von PHP-Dateien
- Scan auf gefährliche Funktionen
- Rollback auf `DEFAULT_THEME` bei Fehlern

### 14.5 Rendering

Der ThemeManager rendert:

- Header
- Haupttemplate
- Footer

zusammen mit Hooks wie:

- `before_header`
- `after_header`
- `before_footer`
- `body_end`
- `after_footer`

### 14.6 Theme-Settings und Menüs

Theme-nahe Einstellungen werden lazy geladen. Menüs kommen aus Settings und teilweise aus `theme.json`-Fallbacks.

### 14.7 Favicon und Custom Styles

Der ThemeManager rendert globale Favicons und Customizer-nahe CSS-Variablen auf Basis gespeicherter Optionen.

### 14.8 Theme-Entwicklung in der Praxis

Themes sind in 365CMS nicht nur Optik, sondern Laufzeitpartner für:

- Template-Auswahl
- Hook-Ausgabe
- Menüs
- Login-/Public-UX
- rechtliche und strukturelle Seiten
- Customizer-nahe Styles und Branding

Ein Theme kann daher funktionale Fehler sichtbar machen oder verstecken, auch wenn der Core fachlich korrekt arbeitet.

### 14.9 Theme-Arbeit ohne Runtime-Verwechslung

Besonders wichtig:

- Änderungen im Theme-Quellrepo sind erst nach Übernahme in die Runtime plus FTP-Upload live wirksam
- Theme-Editor und Customizer arbeiten gegen deployte Runtime-Themes
- beim Debuggen immer zuerst prüfen, welche Theme-Datei tatsächlich aus `CMS/themes/` gerendert wird

### 14.10 Themes sind auch Integrationsflächen

Themes integrieren in 365CMS nicht nur HTML und CSS, sondern auch:

- Menü- und Navigationsverträge
- rechtliche Pflichtlinks
- Lokalisierungssichtbarkeit
- Hook-Ausgabepunkte
- Login- und Vertrauenspfade

Darum können Theme-Änderungen reale Betriebs- und Compliance-Auswirkungen haben, selbst wenn der Core unverändert bleibt.

---

## 15. Admin-Architektur

### 15.1 Aufbau

Der Admin-Bereich besteht aus:

- Einstiegsdateien unter `CMS/admin/*.php`
- Modulen unter `CMS/admin/modules/*`
- Views unter `CMS/admin/views/*`
- Partials unter `CMS/admin/partials/*`

### 15.2 Große Modulgruppen

- Dashboard
- Kommentare
- Hub-Sites
- Landing-Pages
- Recht / DSGVO / Cookies
- Medien
- Member-Dashboard-Konfiguration
- Menüs
- Seiten
- Beiträge
- Plugins / Marketplace
- SEO / Redirects / Performance / Analytics
- Sicherheit / Audit / Firewall / Antispam
- System / Updates / Diagnose / Doku / Mail / Support
- Themes / Fonts / Design / Editor / Marketplace
- Benutzer / Gruppen / Rollen / Settings
- Subscriptions / Orders / Packages

### 15.3 Shell-Muster

Wichtige Shell-/Routing-Teile sind z. B.:

- `section-page-shell`
- `post-action-shell`
- `redirect-alias-shell`

Diese Shells kapseln wiederkehrende Logik wie Routing, Form-Handling, CSRF-Verifikation, View-Einbettung und Redirects.

### 15.4 Konsequenz für Erweiterungen

Neue Admin-Funktionalität sollte möglichst als Modul plus View sauber integriert werden, nicht als monolithische Einzeldatei mit Inline-Komplettlogik.

### 15.5 Qualitätsvertrag für Admin-Seiten

Eine gute Admin-Seite in 365CMS erfüllt idealerweise diese Punkte:

- Einstieg über kanonischen Entry oder gemeinsamen Wrapper
- klare Capability-Gates
- normalisierte Actions und IDs
- definierter PRG- oder Inline-Fehlerpfad
- View mit vorbereiteten Daten statt Controller-Logik
- dediziertes Asset statt großem Inline-JS
- Alerts und Hinweise über gemeinsame Partials

### 15.6 Besonders regressionsanfällige Admin-Muster

Historisch problematisch waren besonders:

- große Multi-Tab-Seiten
- Asset-getriebene Modale mit Dropdown-Triggern
- Bulk-Aktionen
- Marketplace-/Update-Pfade
- Medien- und Theme-Dateiverwaltung
- Formulare mit mehreren potenziellen Submit-Zielen

Genau dort lohnt sich immer eine zusätzliche Runde Skepsis und Gegenprüfung.

### 15.7 Admin heißt Orchestrierung, nicht Dateien sammeln

Der Admin-Bereich ist in 365CMS im Idealfall eine Orchestrierungsschicht:

- Entries schützen und normalisieren
- Module kapseln Fachlogik
- Views rendern vorbereitete Zustände
- Assets übernehmen wiederverwendbare Interaktion
- Partials halten Alerts, Shells und Rückmeldeverträge konsistent

Sobald diese Trennung aufweicht, steigen Supportaufwand, Regressionen und Audit-Risiken spürbar an.

---

## 16. Member-Bereich

### 16.1 Grundbereiche

Der Member-Bereich enthält u. a.:

- Dashboard
- Profil
- Sicherheit
- Subscription
- Medien
- Nachrichten
- Benachrichtigungen
- Datenschutz
- Favoriten

### 16.2 Member-Medien

Persönliche Medien leben benutzerbezogen und sind relevant für Datenschutz, Speicherbudget und Zugriffslogik.

### 16.3 Member-Sicherheit

Der Member-Bereich ist nicht nur „Profilseite mit netter Farbe“, sondern enthält sicherheitsrelevante Funktionen wie Passwort, MFA, Sessions und Privatsphäreoptionen.

### 16.4 Datenschutz- und Vertrauensvertrag im Member-Bereich

Der Member-Bereich verarbeitet typischerweise besonders sensible Daten:

- Identität
- Profilfelder
- Sicherheitsoptionen
- private Medien
- Nachrichten
- Favoriten und Präferenzen

Deshalb müssen dort Eingabevalidierung, URL-/Pfadgrenzen, Ausgabe-Escaping und Rechteprüfung besonders konsequent sein.

### 16.5 Member-Regressionsdenken

Bei Änderungen an Member-Pfaden immer mitdenken:

- bleibt der Nutzer sicher innerhalb seines erlaubten Datenraums?
- werden fremde Medien, Ordner oder Links zuverlässig ausgeschlossen?
- bleiben gespeicherte URLs, Farben, Widgetdaten oder Profilwerte sauber normalisiert?
- werden private Zustände nicht versehentlich in öffentliche Views gespiegelt?

### 16.6 Member-UX ist Sicherheitsoberfläche

Im Member-Bereich ist die Oberfläche nicht bloß Komfort, sondern Teil der Sicherheitswirkung:

- unklare Formulare erzeugen riskante Fehlbedienung
- unsichtbare Limits führen zu widersprüchlichen Erwartungen
- unpräzise Rückmeldungen untergraben Vertrauen bei Passwort-, Sicherheits- oder Medienaktionen
- fehlende Trennung zwischen privat und öffentlich führt schnell zu Datenpannen

Gerade deshalb sollten Member-Views fachlich zurückhaltend, sprachlich klar und technisch strikt gebaut sein.

---

## 17. Hooks- und Event-System

### 17.1 Hook-Kern

`CMS/core/Hooks.php` liefert das Erweiterungsrückgrat.

### 17.2 Wichtige Hook-Klassen

- Lifecycle-Hooks
- Content-Hooks
- User-/Auth-Hooks
- Admin-Hooks
- Theme-/Render-Hooks
- Cron-/Queue-Hooks

### 17.3 Beispiele wichtiger Events

- `cms_init`
- `cms_init_<mode>`
- `plugins_loaded`
- `plugin_loaded`
- `plugin_activated`
- `plugin_deactivated`
- `theme_loaded`
- `cms_before_route`
- `register_routes`
- `cms_after_route`
- `cms_after_page_save`
- `cms_after_post_save`
- `cms_cron_mail_queue`

### 17.4 Entwicklungsregel

Wenn Folgefunktionen an Content- oder Auth-Vorgänge gekoppelt sind, müssen exakt die real ausgelösten Hooks verwendet werden. Falsche Hook-Namen führen gern zu „funktioniert lokal nicht reproduzierbar, aber fühlt sich kaputt an“.

### 17.5 Hook-Disziplin

Bei Hook-basierter Erweiterung gelten diese Regeln:

- nur auf echte, im Code belegte Hooks aufsetzen
- keine fachkritische Logik an experimentelle oder zufällig mitlaufende Stellen hängen
- Hook-Namen dokumentieren, wenn neue öffentliche Verträge entstehen
- Seiteneffekte klar halten: Save-Hook ist nicht gleich Render-Hook ist nicht gleich Cron-Hook

### 17.6 Hook-Fehlersuche praktisch

Wenn etwas „nicht reagiert“, zuerst prüfen:

- wird der erwartete Hook wirklich ausgelöst?
- läuft der Code im richtigen Modus?
- ist das Plugin/Theme/Modul zur Hook-Zeit schon geladen?
- wurde eventuell der falsche Save- oder Routing-Hook verwendet?

### 17.7 Hook-Architektur braucht Ereignisdisziplin

Hooks sind stark, aber nur dann, wenn sie diszipliniert benutzt werden:

- Ereignisnamen müssen fachlich verständlich bleiben
- Hooks sollten keine versteckten Pflichtpfade sein, die nirgends dokumentiert sind
- Seiteneffekte müssen zur Ereignisart passen
- Hook-Reihenfolge darf nicht stillschweigend zum einzigen Integrationsmechanismus mutieren

Anders gesagt: Hooks sollen Erweiterung ermöglichen, nicht die eigentliche Geschäftslogik unlesbar machen.

---

## 18. Performance, Cache und Monitoring

### 18.1 Cache-Strategie

`CacheManager` ist die zentrale Cache-Schicht.

Genannt werden:

- L1 APCu, wenn verfügbar
- L2 Dateisystem
- optionale Integrationslogik für performante Cache-Pfade

### 18.2 OPcache-Warmup

`OpcacheWarmupService` kann nach Deployments relevante PHP-Dateien vorwärmen.

### 18.3 Core Web Vitals

`CoreWebVitalsService` unterstützt Performance-/Metrik-Erfassung im Frontend.

### 18.4 Monitoring-Endpunkte und Admin-Monitoring

Im Admin existieren separate Monitoring-/Diagnosepfade, u. a. für:

- Cron-Status
- Disk Usage
- Email Alerts
- Health Checks
- Response Time
- Scheduled Tasks

### 18.5 Mail Queue

Die Mailqueue ist asynchron gedacht und wird über Cron-/Hook-Logik verarbeitet. Das ist wichtig für Stabilität, Nutzererlebnis und Retry-Verhalten.

### 18.6 Performance-Denke für Entwickler

- keine unnötigen DB-Abfragen im Bootstrap
- lazy Loading nutzen
- wiederkehrende Settings bündeln
- große UI- oder Editor-Skripte zentral pflegen
- Caches nach strukturellen Änderungen sauber invalidieren

### 18.7 Typische Performance-Fallen im Projekt

Besonders häufig kritisch:

- Vollscans im Dateisystem für Themes, Fonts, Medien oder Updates
- komplette Datensätze in Admin-GET-Pfaden, obwohl nur Teilmengen gebraucht werden
- doppelte Aggregationen im selben Request
- Inline-Initialisierung großer JS-Konfigurationen direkt im Template
- Remote-Checks im synchronen Seitenaufbau ohne Cache/Fallback

### 18.8 Performance-Vertrag für neue Features

Neue Features sollten idealerweise beantworten können:

- was wird pro Request garantiert gebraucht?
- was kann lazy oder gecacht sein?
- was ist Remote und darf den Request nicht unnötig blockieren?
- welche Daten können vorbereitet statt im Template berechnet werden?

### 18.9 Performance ist auch Vorhersagbarkeit

Neben reiner Geschwindigkeit ist in 365CMS besonders wichtig, dass Verhalten vorhersagbar bleibt:

- keine zufälligen Laufzeitspitzen durch Hintergrund-Scans im Seitenaufbau
- keine Modulpfade, die bei kleiner Datenmenge schnell und bei echter Datenlast unbenutzbar werden
- keine Caches, die nur funktionieren, solange niemand Inhalte, Menüs oder Einstellungen ändert

Eine stabile, vorhersagbare Antwortzeit ist im Betrieb oft wertvoller als ein einzelner Benchmark-Traumwert.

---

## 19. Cron, Hintergrundjobs und Betriebsautomation

### 19.1 Cron-Grundidee

365CMS besitzt eine Cron-/Scheduled-Task-Schicht für wiederkehrende Arbeiten.

### 19.2 Typische Aufgaben

- Verarbeitung der Mailqueue
- Health-/Monitoring-Prüfungen
- Wartung und Aufräumjobs
- potenzielle Reindex-/Analyse-/Synchronisationsaufgaben

### 19.3 Wichtige Entwicklerregel

Cron-Logik muss idempotent und fehlertolerant sein. Hintergrundjobs dürfen bei Teilfehlern nicht das Gesamtsystem blockieren.

### 19.4 Beobachtbarkeit

Cron-Status und verwandte Systemzustände sollten immer über Audit/Logs/Admin-Diagnose nachvollziehbar bleiben.

### 19.5 Cron-Vertrag für neue Jobs

Neue Hintergrundjobs sollten:

- idempotent sein
- mit Timeouts oder Teilfehlern kontrolliert umgehen
- keine UI-spezifischen Annahmen treffen
- klare Logs/Auditspuren hinterlassen
- bei Bedarf auch manuell oder diagnostisch auslösbar sein

### 19.6 Cron und Deployment

Weil das Projekt unmittelbar per FTP aktualisiert wird, ist bei Cron-Jobs wichtig:

- keine Annahmen über externe Live-Sync-Layer
- Jobs müssen mit dem tatsächlich hochgeladenen Codezustand konsistent laufen
- nach Deployments mit Strukturänderungen prüfen, ob Cron-Pfade, Queue-Worker und Dateipfade weiter passen

### 19.7 Hintergrundjobs brauchen fachliche Grenzen

Cron- und Queue-Logik sollte fachlich klar begrenzt sein:

- keine UI-spezifischen Nachrichten- oder Redirect-Annahmen
- keine Abhängigkeit von Browserzustand, Tabs oder Formularresten
- keine stillen Massenoperationen ohne Logging oder Begrenzung
- keine „nur nachts klappt das schon“-Logik ohne Diagnoseweg

Hintergrundjobs müssen auch dann verständlich bleiben, wenn sie isoliert über Logs oder Diagnose-Seiten betrachtet werden.

---

## 20. Medien, Dateien und Assets

### 20.1 Asset-Struktur

Die produktive Asset-Laufzeit liegt unter `CMS/assets/`.

Dort liegen u. a.:

- CSS-Dateien für Admin, Member, Hub, Consent
- JavaScript für Admin-Workflows, SEO, Menüs, Benutzer, Editoren
- gebündelte Drittbibliotheken

### 20.2 Externe Libraries

Im Projektkontext tauchen u. a. Bibliotheken auf für:

- Editor.js
- SunEditor
- Grid.js
- PhotoSwipe
- Dompdf / TCPDF
- HTMLPurifier
- JWT
- WebAuthn
- LDAP
- Symfony Mailer/Mime/Translation

### 20.3 Wichtige Unterscheidung

`CMS/assets/` ist die Runtime-Basis. Das Top-Level-`ASSETS/` außerhalb von `CMS/` ist Entwicklungs-/Quellkontext und wird im Bootstrap nur als Fallback-Autoloader berücksichtigt, wenn `CMS/assets/autoload.php` fehlt.

### 20.4 Asset-Regeln für Änderungen

Bei Asset-Änderungen immer mitdenken:

- läuft das Skript wirklich auf der echten Zielseite?
- gibt es ein gemeinsames Asset, das mehrere Views beeinflusst?
- braucht die Aktion einen Pending-State oder Fallback?
- werden Daten per `data-*`/JSON sauber vorbereitet statt im Script zusammengeraten?
- bleibt die UI auch ohne optimistische Browser-Sonderlogik funktionsfähig?

### 20.5 Umgang mit gebündelten Fremdbibliotheken

Gebündelte Bibliotheken sind praktisch, aber auditrelevant.

Darum:

- Vendor-Code klar vom First-Party-Code gedanklich trennen
- aktive Runtime-Fläche klein halten
- wenn möglich Sicherheits- oder Laufzeitkritisches kapseln statt tief im Projekt zu verteilen
- bei Problemen zuerst prüfen, ob ein eigener Wrapper oder die Bibliothek selbst verantwortlich ist

### 20.6 Asset- und Medienarbeit hat immer Seiteneffekte

Änderungen an Medien- oder Asset-Pfaden betreffen oft gleichzeitig:

- Browser-Caching
- Admin-Interaktion
- Theme-Ausgabe
- Upload- und Delivery-Regeln
- Dateisystemgrenzen
- Performance und Sichtbarkeit

Deshalb sollten Asset- oder Medienfixes nie nur optisch bewertet werden, sondern auch gegen Delivery, Sicherheit und echte Bedienpfade geprüft werden.

---

## 21. Logging, Fehlerbehandlung und Diagnose

### 21.1 Fehlerlog

`CMS_ERROR_LOG` zeigt auf das zentrale Fehlerlog. Bevorzugt wird `var/logs/error.log`.

### 21.2 Logger

Der zentrale Logger ergänzt klassische PHP-Fehlerpfade um strukturiertere Anwendungssicht.

### 21.3 AuditLogger

Der AuditLogger dokumentiert sicherheits- und betriebsrelevante Aktionen unabhängig vom klassischen Fehlerlog.

### 21.4 Diagnose im Admin

Der Admin-Bereich bietet eigene Diagnose-/Support-/Systeminfo-Seiten. Diese sind für Betrieb und Fehlersuche relevant und sollten nicht als optionaler Deko-Bereich betrachtet werden.

### 21.5 Fehlerklassen sauber trennen

Für saubere Analyse ist hilfreich, zwischen diesen Fehlerarten zu unterscheiden:

- **Benutzerfehler**: valide erklärbar, UI-tauglich rückmeldbar
- **Validierungsfehler**: Eingabe fachlich oder technisch unzulässig
- **Betriebsfehler**: Remote, Dateisystem, Datenbank, Timeout, fehlende Ressource
- **Programmierfehler**: unerwartete Exceptions, kaputte Zustände, unvollständige Verträge

Jede Klasse braucht andere Behandlung. Was in Logs wertvoll ist, ist oft in der UI zu viel.

### 21.6 Diagnosefähigkeit als Qualitätsmerkmal

Eine Änderung ist nicht nur dann gut, wenn sie „funktioniert“, sondern auch dann, wenn sie bei Problemen:

- nachvollziehbar fehlschlägt
- nicht still hängt
- klar loggt
- im Admin sichtbar einen sinnvollen Zustand hinterlässt
- keinen falschen Erfolg suggeriert

### 21.7 Gute Fehlerbilder schlagen stilles Scheitern

Ein gutes Fehlerbild in 365CMS erfüllt idealerweise vier Eigenschaften:

- der Nutzer bekommt eine verständliche Reaktion,
- der Admin bzw. Betreiber bekommt einen prüfbaren Hinweis,
- das Log enthält technische Tiefe,
- der Systemzustand bleibt kontrolliert statt halb kaputt.

Still geschluckte Fehler wirken kurzfristig freundlich, machen aber Support, Audit und spätere Ursachenanalyse erheblich teurer.

---

## 22. Best Practices für Entwicklung im 365CMS

### 22.1 Runtime vor Quellrepo denken

Wenn eine Änderung im Live-System wirken soll, muss sie in der Runtime landen:

- Themes unter `CMS/themes/`
- Plugins unter `CMS/plugins/`

### 22.2 Hooks exakt verwenden

Nicht vermutete Hook-Namen benutzen, sondern die tatsächlich im Code ausgelösten. Das spart Stunden auf der Suche nach „Warum läuft mein Indexer nie?“.

### 22.3 Geteilte Admin-Skripte ernst nehmen

Viele Admin-Funktionen hängen an gemeinsamen JS-Dateien. Ein Fix in einer View reicht oft nicht, wenn die echte Interaktion im gemeinsamen Asset lebt.

### 22.4 One-shot-CSRF respektieren

One-shot-Tokens werden nach erfolgreicher Prüfung invalidiert. Shell- und Embedded-Komponenten dürfen denselben Token nicht doppelt verbrauchen.

### 22.5 Theme-/Plugin-Checks nicht umgehen

Die Security-Scans und Pfadvalidierungen sind keine Schikane, sondern verhindern spätere Katastrophen mit Anlauf.

### 22.6 Mehrsprachigkeit vollständig mitdenken

Bei Suche, SEO, Save-Hooks, Routing und Editorlogik müssen `_en`-Felder berücksichtigt werden, wenn das Feature mehrsprachig sein soll.

### 22.7 Einstellungen zentral behandeln

Direkte Wildwuchs-Settings ohne Namenskonzept erschweren Diagnose, Migration und UI-Integration.

### 22.8 Fehlertoleranz bei Hintergrundjobs

Cron-/Queue-Funktionen müssen robust gegen Einzelprobleme sein und brauchbare Logs hinterlassen.

### 22.9 Admin-Pfade intern konsistent halten

Interne Redirects und Formularziele sollten sauber auf die tatsächliche Admin-Routing-Struktur abgestimmt sein.

### 22.10 Keine Security durch Hoffnung

- niemals rohes HTML aus Nutzereingaben ausgeben
- niemals SQL mit Input zusammensetzen
- niemals Dateipfade ungeprüft übernehmen
- niemals ZIP-Inhalte blind entpacken
- niemals annehmen, dass Theme-/Plugin-Code harmlos ist, nur weil er hübsch kommentiert wurde

### 22.11 Release- und Doku-Synchronität als Pflicht

Historisch traten mehrfach Zustände auf, in denen README, Changelog, `Version.php`, `update.json` oder Live-Auslieferung nicht denselben Stand kommunizierten.

Deshalb gilt:

- Release-Stand immer gleichzeitig in sichtbarer Version, Update-Metadaten und Doku synchronisieren
- neue Core-Auth-, Routing- oder Admin-Verträge immer auch in der technischen Doku nachziehen
- sichtbare Live-Version nicht als Wahrheit behandeln, wenn Repo und Deployment auseinanderlaufen können
- bei Änderungen an Produkt- oder Rechtspfaden immer auch öffentliche Navigation, Footer und Vertrauensseiten mitprüfen

### 22.12 UI-Hinweise gehören an dieselben Verträge wie die Logik

Viele Audit-Batches zeigen, dass Probleme nicht nur in der Fachlogik, sondern in unklaren UI-Verträgen steckten.

Darum:

- Grenzwerte, Limits, erlaubte Endungen, Hostregeln und Sperrgründe nicht nur serverseitig verstecken, sondern im UI sichtbar machen
- Erfolg, Warnung und Fehler möglichst über gemeinsame Partials/Alert-Verträge rendern
- Formulare, Tabs, Modale und Redirect-Ziele so bauen, dass nach Fehlern derselbe Bedienkontext erhalten bleibt
- keine Sonderlogik pro View erfinden, wenn bereits ein gemeinsamer Shell-, Flash- oder Asset-Vertrag existiert

### 22.13 JS darf niemals die einzige Wahrheit sein

Mehrere frühere Fehler betrafen Buttons, Modale oder Confirm-Flows, die optisch da waren, aber funktional nicht feuerten.

Deshalb:

- kritische Mutationen bevorzugt über echte POST-Formulare absichern
- JavaScript als Komfortschicht sehen, nicht als einzigen Auslöser fachkritischer Aktionen
- Delete-, Install-, Save- und Bulk-Aktionen gegen Doppel-Submits absichern
- bei Dropdown-/Modal-Interaktionen Fallbacks für Timing-Probleme und fehlende Trigger mitdenken

### 22.14 Fail-closed vor bequem

Die erfolgreichsten Härtungen der letzten Releases folgten fast immer demselben Muster: lieber sauber blockieren als „irgendwie versuchen“.

Das gilt besonders für:

- Redirect-Ziele
- Dateisystemzugriffe
- ZIP-/Marketplace-Installationen
- Root-Dateien wie SEO-Keydateien
- Remote-Downloads
- Auth-Weiterleitungen
- Theme-/Plugin-Laufzeitpfade

Wenn Lesbarkeit, Host, Größe, Zielpfad, Dateityp oder Capability nicht eindeutig passen, wird der Pfad geschlossen statt erraten.

### 22.15 Repo- und FTP-Stand bewusst synchron halten

Für dieses Projekt gilt zusätzlich als Arbeitsregel:

- das Quellrepo soll dem Stand des FTP-Uploads entsprechen
- Änderungen werden unmittelbar nach Anpassungen hochgeladen
- das Live-System wird **nicht** aus einer dritten, externen Synchronisationsquelle gespeist
- Repo und deployter Stand sollen also absichtlich deckungsgleich bleiben

Für Entwicklung und Review bedeutet das:

- Wenn eine Runtime-Datei geändert wurde, ist sie auch als reale Live-Änderung zu denken
- „Das ist nur lokal im Repo“ ist hier normalerweise **kein** gültiger Denkrahmen
- Abweichungen zwischen Repo und FTP-Stand sind Sonderfälle und sollten explizit benannt werden

### 22.16 Änderungen immer als Kette statt als Punkt denken

Eine belastbare 365CMS-Änderung beantwortet am Ende nicht nur „welche Datei wurde geändert?“, sondern auch:

- welcher Vertrag wurde angepasst?
- welche Nachbarsysteme sind mitbetroffen?
- wie wurde die Runtime-Wirkung gegengeprüft?
- welche Doku, Logs oder Release-Hinweise müssen mitziehen?

Diese Denkweise ist die beste Versicherung gegen die typische Falle, einen lokalen Fix für eine systemische Aufgabe zu halten.

---

## 23. Audit-Erkenntnisse, die nicht wieder verloren gehen dürfen

Die folgenden Punkte sind nicht theoretisch, sondern aus realen Fixes, Audit-Batches und regressionsanfälligen Stellen abgeleitet.

### 23.1 Admin-Entries müssen dünn bleiben

Viele Admin-Dateien wurden schrittweise auf gemeinsame Shells und kleine Action-Verträge umgebaut.

Nicht wieder einreißen:

- Entry-Dateien normalisieren Request-Daten, prüfen Capability/CSRF und dispatchen gezielt
- Fachlogik gehört in Module/Services, nicht in lange `if/elseif`-Ketten im Entry
- Redirects, Flash-Meldungen und Asset-Einbindung sollen über gemeinsame Wrapper laufen
- Inline-Sonderpfade pro Entry erhöhen Regressionen massiv

Kurzform:

**Entry = Guard + Normalisierung + Dispatch + definierter Rückweg**

### 23.2 Shared Shells und Partials sind Stabilitätsgewinn, kein Overhead

Die Einführung bzw. Nutzung gemeinsamer Admin-Shells, Flash-Partials und Redirect-Wrapper war einer der größten Stabilisierungshebel.

Darum künftig:

- `section-page-shell` nicht umgehen, wenn eine Seite denselben Musterfall hat
- `flash-alert` für Status-/Fehleranzeigen bevorzugen
- Alias-Entrys möglichst als kleine Redirect-Wrapper halten
- neue Sonderlayouts nur dann einführen, wenn der bestehende Shell-Vertrag fachlich wirklich nicht reicht

### 23.3 View-Templates dürfen keine heimlichen Controller sein

Ein wiederkehrendes Audit-Muster war zu viel Laufzeit- und Zustandscode direkt im Template.

Nicht wiederholen:

- keine großen Inline-Skripte in Views, wenn es ein dediziertes Asset geben kann
- keine komplexen Status-, Rechte- oder Mapping-Entscheidungen verstreut im Markup
- vorbereitete ViewModels bevorzugen statt lose Array-Mischungen und Hilfsfunktionen im Template
- Confirm-Texte, Status-Badges, Grenzen und Hilfetexte serverseitig sauber vorbereiten

### 23.4 Remote-Funktionen sind Premium-Risiko

Marketplace, Feed-Verarbeitung, Git-/ZIP-Sync, Mail-/Graph-Anbindungen und andere Remote-Pfade sind historisch die riskantesten Stellen.

Pflichtregeln:

- Hosts allowlisten
- Redirects neu validieren
- Dateigrößen und Content-Typen begrenzen
- Timeouts und Retry-Verhalten bewusst definieren
- Antworten cachen, aber Fallbacks klar kennzeichnen
- keine blinden Downloads oder Extraktionen in Live-Zielverzeichnisse
- wenn möglich Staging-/Swap-Modell statt Direkt-Overwrite

### 23.5 Dateisystem-Pfade sind immer Feindgebiet

Theme-Explorer, Font-Manager, Indexing, Media, Marketplace, Customizer und Download-Pfade zeigten immer wieder, wie schnell Dateizugriffe unsauber werden.

Deshalb:

- nur kanonische Realpaths verwenden
- Root-Verzeichnisse strikt begrenzen
- Skip-Segmente wie `vendor`, `node_modules`, `dist` und ähnliche Hotspots bewusst aussparen, wenn Bearbeitung dort nicht nötig ist
- Größen-, Tiefen- und Dateianzahl-Limits setzen
- schreibgeschützte oder unzulässige Dateien im UI früh sichtbar sperren

### 23.6 Logs ja, Leaks nein

Mehrere Fixes entfernten rohe Exception-Texte aus öffentlichen oder Admin-nahen Antworten.

Faustregel:

- technische Details ins Log oder Audit-Log
- UI zeigt verständliche, aber generische Fehlermeldungen
- keine Stacktraces, Pfade, SQL-Fragmente oder Remote-Fehler roh an Nutzer oder Admin-Views durchreichen
- Debug-Ausgaben nur lokal bzw. kontrolliert

### 23.7 Capability vor Route vor Aktion

Viele spätere Audit-Batches bestanden im Grunde darin, lose Admin-Zugriffe wieder enger an Capabilities zu binden.

Reihenfolge für Mutationen:

1. Capability prüfen
2. Aktion allowlisten
3. IDs/Slugs/Parameter normalisieren
4. CSRF prüfen
5. Modul/Service aufrufen

Nicht umgekehrt. Erst recht nicht „erst mal handeln, später schauen“.

### 23.8 Gemeinsame Frontend-/Admin-Verträge sichtbar halten

Viele reale Fehler entstanden dort, wo zwei Seiten oder Module denselben technischen Vertrag teilten, ohne dass das beim Bearbeiten sofort sichtbar war.

Typische Beispiele:

- Redirect-Manager und 404-Monitor teilen JS und Modal-Logik
- Live-Editor und Asset-Service teilen Editor.js-Kontrakte
- Wrapper, Flash-Partials und Alias-Entrys teilen Redirect- und Fehlermuster

Deshalb sollten gemeinsame Verträge entweder dokumentiert oder im Code klar erkennbar gebündelt werden.

### 23.9 Audit-Learnings sind Betriebsregeln, nicht Randnotizen

Audit-Erkenntnisse sollten nicht als temporäre Nacharbeit behandelt werden. In 365CMS sind sie praktisch:

- Architekturhinweise,
- Priorisierungswerkzeuge,
- Release-Filter,
- und Lernspeicher für reale Fehlermuster.

Wenn Audit-Ergebnisse nicht in Doku, Codevertrag und Review-Denke einwandern, kommen dieselben Fehler mit erstaunlicher Treue zurück.

---

## 24. Konkrete Regressionen aus dem Changelog, die nie wieder auftauchen dürfen

### 24.1 Auth darf nicht themeabhängig werden

Die Einführung der CMS-eigenen Auth-Strecke hat klargestellt:

- Login, Registrierung und Passwort-Reset sind Core-Funktionalität
- Theme-Templates dürfen die UX gestalten, aber nicht den kanonischen Auth-Pfad definieren
- MFA, Passkey, LDAP und klassische Passwort-Logins müssen denselben Session-Vertrag abschließen
- Remember-Me darf nicht auf Teilpfaden verloren gehen

Wenn neue Auth-Features entstehen, müssen sie gegen denselben Abschlussvertrag geprüft werden.

### 24.2 Redirects müssen same-origin und locale-aware bleiben

Mehrere Fixes zielten auf offene Redirect-Kanten, falsche MFA-Sprünge oder Hostwechsel.

Dauerregel:

- Redirects nur auf interne, normalisierte Zielpfade
- keine Host-/Schema-Sprünge aus Benutzerinput übernehmen
- `//`-Ziele, `..`-Segmente und Auth-Loops blockieren
- Sprach-/Locale-Pfade explizit und deterministisch behandeln

### 24.3 User-Delete-Flows dürfen nicht an UI-Tricks hängen

Der Benutzer-Löschpfad war ein Paradebeispiel für fragile Admin-Interaktion.

Nie wieder:

- nested Forms
- Delete-Buttons, die nur über indirekte HTML-/Browser-Sonderlogik feuern
- Form-Targets, die hart auf `SITE_URL` oder fremde Hosts zeigen
- `REQUEST_URI` blind als absolute URL vertrauen

Stattdessen:

- native POST-Formulare
- hostneutrale interne Admin-Pfade
- sauber normalisierte Rückleitungen
- sichtbarer Danger-Bereich mit echtem Submit-Vertrag

### 24.4 Editor.js braucht Live-Admin-Parität

Historisch wurden Features oder Fixes an Editor.js teils nur an einer Stelle nachgezogen.

Deshalb bei jeder Editor-Änderung prüfen:

- Asset-Service-Konfiguration
- Live-Admin-Editor-Skript
- Renderer
- Sanitizer
- Toolbar-/Block-Sichtbarkeit im echten Edit-Flow

Sonst entstehen „Feature existiert, aber Redakteur sieht es nicht“-Fehler.

### 24.5 Mehrsprachigkeit ist kein Nachgedanke

Mehrere Fehler betrafen DE/EN-Sync, `_en`-Felder, falsche Sprachpfade oder gemischte Ausgabe.

Nicht wiederholen:

- EN-Editor nicht leer oder kaputt initialisieren
- Suchindex nur auf DE-Felder hören lassen
- falsche Save-Hooks für Reindexing nutzen
- Sprachwechsel oder Gegenpfade lose aus Slugs erraten
- englische Pflichtseiten, Footer oder UI-Strings halb deutsch ausliefern

### 24.6 404- und Redirect-Tools müssen schmutzige Daten aushalten

UTF-8-Probleme, Referrer-/User-Agent-Müll und fragile Modal-Initialisierung führten bereits zu stillen No-ops.

Daher:

- JSON-Übergaben robust gegen ungültige Zeichen machen
- Modale über delegierte Events und Fallbacks initialisieren
- 404-Übernahmefunktionen gegen kaputte Logdaten härten
- gemeinsame SEO-JS-Pfade immer als zentrale Quelle behandeln

### 24.7 Menüs brauchen semantische Root-Logik

Die Startseite ist kein exotischer Sonderfall, sondern das häufigste Ziel.

Darum:

- `home`, `homepage`, `startseite`, `index.php` und ähnliche Aliasse konsistent auf `/` normalisieren
- leere Home-Einträge fachlich sinnvoll als Root interpretieren, wenn Titel und Struktur klar sind
- slugbasierte interne Pfade ohne unnötige Strenge akzeptieren, wenn sie gültig normalisierbar sind

### 24.8 Theme-Customizer und Shell-CSRF dürfen sich nicht gegenseitig erschießen

Ein klassischer Fehler war der doppelte Verbrauch eines One-shot-Tokens.

Merksatz:

- wenn die Admin-Shell einen CSRF-Kontext bereits sicher geprüft hat, darf die eingebettete Komponente denselben Einweg-Token nicht erneut verbrauchen
- eingebettete Customizer brauchen einen klar dokumentierten CSRF-Vertrag mit der Shell

### 24.9 Medienaktionen müssen auch ohne Magie belastbar sein

Löschen, Umbenennen, Verschieben, Bulk-Aktionen und Member-Medien waren mehrfach regressionsanfällig.

Regeln:

- Aktionen serverseitig vollständig unterstützen
- Dropdown-/Modal-UI nur als Bedienoberfläche, nicht als Fachlogik
- Ordnerpfade strikt auf erlaubte Roots begrenzen
- Member-Unterordner nicht fälschlich als Systempfade behandeln
- Bulk-Aktionen müssen doppelte Unterpfad-Operationen vermeiden

### 24.10 Legal-/Trust-Pfade sind produktionskritisch

Live-Audits zeigten, dass Footer- oder Formularlinks zu `Impressum`, `Datenschutz` oder `AGB` real in 404 laufen können.

Deshalb gelten Rechtsseiten als Release-Blocker:

- aktiv verlinkte Pflichtseiten müssen erreichbar sein
- DE/EN-Pflichtseiten dürfen nicht sprachlich inkonsistent sein
- Footer-, Login-, Register- und Kontaktpfade immer gegen Live-Routing prüfen

### 24.11 Marketplace- und Update-Pfade dürfen nicht „schnell mal direkt entpacken“

Historisch war ein großes Thema, dass Remote-Pakete und Archive besonders kontrolliert behandelt werden müssen.

Nie wieder:

- Direkt-Extraktion in Zielverzeichnisse ohne klare Vorprüfung
- lose Slug- oder Manifest-Vertrauensannahmen
- unklare Host- oder Redirect-Herkunft
- fehlende Größen- oder Strukturgrenzen im Archiv

Bevorzugt:

- Staging-/Swap-Logik
- Hash-/Integritätskontext
- klarer Quellenstatus (`remote`, `cache`, `local`, `none`)
- explizit sichtbare Sperrgründe für Auto-Installationen

### 24.12 Alerts, Modale und Pending-States sind keine Kosmetik

Viele Bugfixes drehten sich am Ende darum, dass UI-Zustände fachlich falsch oder unzuverlässig waren.

Darum nie als „nur UI“ abtun:

- Pending-State fehlt → Doppel-Submit
- falscher Modal-Trigger → Aktion läuft leer
- falscher Rücksprung-Tab → Save wirkt kaputt
- uneinheitlicher Alert-Vertrag → Fehler wird missverständlich oder gar nicht sichtbar

### 24.13 Wiederkehrendes Muster hinter fast allen Regressionen

Hinter vielen Changelog-Regressionsfällen steckt immer wieder dieselbe Grundform:

- zwei Stellen teilen sich einen Vertrag, aber nur eine wurde geändert,
- UI und Server erwarten unterschiedliche Wahrheiten,
- Runtime- und Quellpfad wurden verwechselt,
- oder ein Randfall wurde als „wird schon passen“ behandelt.

Wer diese vier Muster aktiv sucht, findet zukünftige Regressionen oft, bevor sie live sichtbar werden.

---

## 25. Audit-Fokuszonen mit dauerhaft erhöhter Aufmerksamkeit

Bestimmte Bereiche haben sich als strukturelle Hotspots gezeigt. Änderungen dort brauchen automatisch mehr Skepsis, mehr Gegenprüfungen und meistens mehr Regressionstests.

### 25.1 Kritische Hotspots

- `CMS/admin/theme-editor.php`
- `CMS/admin/theme-marketplace.php`
- `CMS/admin/plugin-marketplace.php`
- `CMS/admin/media.php`
- `CMS/admin/views/posts/edit.php`
- `CMS/assets/js/admin-content-editor.js`
- `CMS/assets/js/admin-media-integrations.js`
- `CMS/core/Services/BackupService.php`
- `CMS/core/Services/UpdateService.php`
- `CMS/core/Services/FileUploadService.php`
- `CMS/core/Services/PdfService.php`
- `CMS/member/includes/class-member-controller.php`

Diese Bereiche vereinen oft mehrere Risiken gleichzeitig:

- Dateisystem
- Remote-I/O
- große Payloads
- Rollen-/Berechtigungslogik
- komplexe UI
- hohe Seiteneffekte

### 25.2 Typische Audit-Muster in Hotspots

- zu breite Request-Payloads
- zu viele Verantwortlichkeiten in einer Klasse/Datei
- doppelte oder divergierende View-/JS-/Service-Verträge
- unklare Redirect- und Rücksprungziele
- leaky Fehlermeldungen
- fehlende Pending-/Submit-Guards
- teure Vollscans oder unnötige Komplettdaten in GET-Pfaden

### 25.3 Was bei Hotspots immer mitgedacht werden muss

- Capability und CSRF
- Datei-/Pfadgrenzen
- Größenlimits
- dedizierte Fehlermeldungen im Log statt in der UI
- PRG- oder klarer Rücksprungpfad
- DE/EN-/Locale-Verhalten
- Hostneutralität und Same-Origin
- Bulk- und Parallelaufrufe

### 25.4 Hotspot-Arbeitsmodus

Für Hotspots empfiehlt sich ein strengeres Vorgehen als für normale Doku- oder Read-only-Anpassungen:

- zuerst Vertrag und Seiteneffekte klären
- dann klein und inkrementell ändern
- direkt danach prüfen, ob gemeinsame Assets/Wrapper mit betroffen sind
- anschließend gezielt Regressionen testen statt nur Syntax oder happy path zu prüfen

Hotspots bestrafen große blinde Umbauten zuverlässig.

### 25.5 Hotspots brauchen dokumentierte Vorsicht

Je kritischer ein Hotspot ist, desto wichtiger ist es, dass die Vorsicht nicht nur im Kopf einzelner Entwickler existiert. Gute Praxis ist daher:

- Hotspot-Regeln in Doku und Review-Kommentaren festhalten
- Seiteneffekte im Code erkennbar machen
- riskante Pfade durch Wrapper, Shells oder dedizierte Services sichtbar kapseln

So bleibt das Wissen nicht an Personen gebunden, sondern im System erhalten.

---

## 26. Live-Betrieb und Audit-Readiness

### 26.1 Repo-Qualität ist nicht automatisch Live-Qualität

Ein sauberer Core-Snapshot garantiert nicht automatisch, dass jede öffentliche Seite denselben Qualitätsgrad transportiert.

Trotzdem gilt in diesem Projekt organisatorisch:

- das Repo soll dem unmittelbar per FTP ausgerollten Stand entsprechen
- es gibt keinen separaten Upstream-Live-Sync außerhalb dieser Arbeitsweise
- Live-Abweichungen sind deshalb eher durch Routing, Content, Daten oder fehlende Runtime-Übernahme erklärbar als durch einen „anderen geheimen Deploy-Stand“

### 26.2 FTP-Realität als Teil der Systemdoku

Für künftige Entwickler und Auditoren wichtig:

- Änderungen werden nach Anpassung unmittelbar per FTP hochgeladen
- die Quelle der Wahrheit für Codeänderungen bleibt das Repo in Verbindung mit dem hochgeladenen Runtime-Stand
- der Produktivstand wird hier also nicht periodisch aus einer externen CI/CD- oder Mirror-Quelle nachgezogen
- wenn live etwas anders aussieht als im Repo, sollte zuerst nach Runtime-Pfad, Cache, Datenzustand oder Host-/Rewrite-Kontext gesucht werden

### 26.3 Live-Audits sollen reale Nutzerpfade bewerten

Nicht jede 404 ist schlimm. Kritisch sind:

- aktiv verlinkte Footer-Ziele
- Formular-Weiterleitungen
- Hauptnavigation
- Sprachumschalter
- Trust- und Compliance-Seiten

---

### 26.4 Live-Check nach FTP-Upload

Da Deployments direkt per FTP erfolgen, ist nach Änderungen besonders sinnvoll:

- genau die geänderten Pfade live aufzurufen
- Pflichtseiten, Redirects und Auth-Flows kurz gegenzuprüfen
- bei Asset-Änderungen Browser-/Server-Cache mitzudenken
- bei Theme-/Plugin-Änderungen die aktive Runtime-Datei zu verifizieren

### 26.5 Audit-Readiness bedeutet mehr als „Scan war grün“

Ein wirklich auditfähiger Zustand bedeutet:

- Codepfade sind nachvollziehbar
- Runtime-Pfade sind klar
- Live-Ziele funktionieren
- Fehlermeldungen leaken nicht
- Doku beschreibt reale Verträge statt Wunschbilder
- Deploymentpraxis ist explizit und nicht implizit erraten

Audit-Readiness ist damit nicht nur ein Sicherheitslabel, sondern die Kombination aus belastbarer Runtime, nachvollziehbarer Änderungskette, verständlicher Diagnose und realitätsnaher Dokumentation.

### 26.6 Live-Betrieb ist die härteste Wahrheitsschicht

Das Repo kann sauber sein, die Doku kann gut aussehen und ein lokaler Test kann grün sein — trotzdem entscheidet am Ende der echte Live-Pfad. Für 365CMS heißt das:

- reale Zielseiten schlagen Theorie
- echte Redirects schlagen angenommene Routinglogik
- tatsächlich hochgeladene Runtime-Dateien schlagen bloße Quellannahmen
- beobachtbare Nutzerpfade schlagen abstrakte Vollständigkeitsgefühle

Diese Nüchternheit ist kein Pessimismus, sondern professioneller Betrieb.

---

## 27. Erweiterte Nicht-wieder-tun-Liste

- keine verschachtelten Formulare in Admin-Views
- keine absoluten Fremd-Hosts in internen Admin-Posts/Redirects
- keine Inline-Event-Handler als primären Interaktionsvertrag
- keine stillen Leer-Arrays als Fehlerersatz für Remote-Failures
- keine zweite Wahrheit für Limits in View und Modul
- keine rohen Exceptions in JSON-, Checkout-, Media- oder Admin-Antworten
- keine Dateilese- oder Downloadpfade ohne Realpath-/Root-Vertrag
- keine Installation von Plugin-/Theme-Paketen per Direkt-Entpacken ohne klaren Staging-/Prüfpfad
- keine Annahme, dass ein Asset-Fix ohne Prüfung der echten Live-Initialisierung reicht
- keine Release-Freigabe mit defekten Pflichtlinks im Footer oder in Auth-/Kontaktpfaden
- keine Annahme, dass ein Live-Unterschied automatisch von einem externen Sync stammt
- keine Repo-Änderung als „nur theoretisch“ behandeln, wenn sie direkt per FTP ausgeliefert wird

### 27.1 Ergänzende Anti-Pattern-Sammlung

- keine UI-Limits pflegen, die serverseitig anders sind
- keine breite Sammelpayload an Module geben, wenn nur drei Felder gebraucht werden
- keine View mit eigener Status-, Filter- und Routinglogik überladen
- keine Save- oder Delete-Aktion ohne klaren Rücksprungpfad bauen
- keine Erfolgsmeldung ausgeben, wenn fachlich nur „nichts geändert“ oder „nicht ausgeführt“ vorliegt

### 27.2 Weitere Dinge, die man sich aktiv abgewöhnen sollte

- keine implizite Abhängigkeit von Tab-Reihenfolge oder DOM-Zufällen aufbauen
- keine technische Fehlersprache 1:1 an Redakteure oder Mitglieder weiterreichen
- keine stillen Fallbacks einbauen, die echte Betriebsprobleme nur verzögern
- keine Admin-Sonderlogik erfinden, wenn bereits Shell-, Partial- oder Alert-Verträge existieren

---

## 28. Häufige Stolperfallen

### 28.1 Theme-Pfad-Verwechslung

Änderungen im Theme-Quellrepo wirken nicht automatisch auf das laufende CMS. Live zählt der Pfad `CMS/themes/<slug>/`.

### 28.2 Plugin-Pfad-Verwechslung

Ein Plugin im Repo `365CMS.DE-PLUGINS/` ist noch nicht aktiv, solange es nicht im Runtime-Ordner liegt.

### 28.3 Redirect-/404-UI nur halb gefixt

Wenn Redirect- und 404-Dialoge auf gemeinsames JavaScript setzen, muss der Fix dort ansetzen, nicht nur in einer einzelnen View.

### 28.4 Editor.js nur an einer Stelle geändert

Die sichtbare Live-Editor-Konfiguration kann an anderer Stelle sitzen als der Asset-Service. Beides muss bei Bedarf angepasst werden.

### 28.5 Suche hört auf falsche Hooks

Wenn Suchindexierung an falschen Events hängt, bleibt Content unsichtbar oder veraltet, besonders bei mehrsprachigen Feldern.

### 28.6 Menü-Startseite nicht normalisiert

Home-/Startseitenpfade müssen als Root logisch behandelt werden. Sonst erzeugt ein eigentlich korrekter Menüeintrag unnötige Validierungsfehler.

### 28.7 Fremdschlüssel generisch benannt

Constraint-Namen müssen schemaweit eindeutig sein, sonst scheitern Installer/Migrationen auf realen Datenbanken.

### 28.8 Repo-Stand mit Runtime verwechselt

Auch wenn in diesem Projekt Repo und FTP-Stand bewusst eng gekoppelt sind, bleibt die Runtime-Datei unter `CMS/` entscheidend. Wer nur eine Quellkopie anfasst, aber nicht die echte aktive Datei im Blick hat, debuggt schnell den falschen Ort.

### 28.9 Live-Probleme nur als Codeproblem gelesen

Nicht jeder Live-Fehler ist ein reiner Codefehler. Häufige Alternativen sind:

- falscher Runtime-Pfad
- nicht übernommene Theme-/Plugin-Datei
- Cache
- Datenzustand
- Rewrite-/Proxy-/Host-Effekt
- nicht geprüfte Pflichtseite oder Navigation nach FTP-Upload

### 28.10 Stolperfallen entstehen oft an Übergängen

Die meisten harten Fehler sitzen nicht mitten in einer klar abgegrenzten Fachfunktion, sondern an Übergängen zwischen:

- Entry und Modul
- Modul und View
- View und Asset
- Save und Folgeprozess
- Quellrepo und Runtime
- Doku und realem Betrieb

Wer Übergänge prüft, findet oft die Fehler, die man in der Einzeldatei stundenlang übersieht.

---

## 29. Technische Checkliste vor Änderungen

Vor größeren Eingriffen prüfen:

- Arbeite ich in der Runtime oder nur im Quellrepo?
- Greife ich an Hook, Router, Shell oder View wirklich an der richtigen Stelle an?
- Betreffe ich DE/EN-Contentpfade gleichzeitig?
- Wird ein gemeinsam genutztes JavaScript oder Service-Modul beeinflusst?
- Sind CSRF, Escape und Prepared Statements eingehalten?
- Müssen Settings, Cache, Index oder Cron-Verhalten mit aktualisiert werden?
- Sind Theme-/Plugin-Laufzeitverträge weiterhin erfüllt?
- Entsteht zusätzlicher Audit- oder Logging-Bedarf?

Zusätzlich bei historisch regressionsanfälligen Änderungen:

- Ändere ich einen Pfad, der auch in gemeinsamem Admin-JavaScript verwendet wird?
- Greift ein Shell-/Wrapper-/Partial-Vertrag, den ich nicht versehentlich umgehe?
- Könnte die Änderung DE/EN, Auth, Redirects oder Live-Pflichtseiten indirekt beeinflussen?
- Verliert die UI bei Fehlern den Tab-, Filter- oder Edit-Kontext?
- Braucht die Änderung ein sichtbares Limit, Warning oder Pending-State im UI?

Zusätzlich für dieses Projektsetup:

- Ist die geänderte Runtime-Datei auch die Datei, die per FTP hochgeladen wird?
- Entspricht der Repo-Stand nach der Änderung dem beabsichtigten Live-Stand?
- Muss nach dem FTP-Upload ein konkreter Live-Pfad direkt gegengeprüft werden?

### 29.1 Checklisten sind Mindestmaß, nicht Ersatz für Denken

Die Checkliste schützt gegen bekannte Versäumnisse. Sie ersetzt aber nicht:

- Architekturverständnis,
- Lesen gemeinsamer Verträge,
- gezielte Regressionstests,
- und die ehrliche Prüfung, ob eine Änderung systemisch größer ist als sie lokal wirkt.

Gerade in 365CMS beginnt Qualität oft genau dort, wo man nach der Checkliste noch einmal kurz misstrauisch wird.

---

## 30. Abschlussbild

365CMS ist kein kleines Ein-Datei-CMS mehr, sondern ein verteiltes Anwendungssystem mit Security-, Service-, Routing-, Theme-, Plugin-, SEO-, Monitoring- und Member-Schichten. Wer daran entwickelt, sollte nicht nur „die betroffene Datei“ sehen, sondern immer die Laufzeitkette mitdenken:

**Konfiguration → Bootstrap → Security/Auth → Hooks → Services → Router → Module/Theme/Plugin → Logging/Audit → Betrieb**

Wenn diese Kette sauber bleibt, bleibt das System stabil. Wenn man irgendwo quer schneidet, rächt sich das meist später, nachts oder kurz vor einem Demo-Termin — also zu den traditionellen Öffnungszeiten des Chaos.

Zusätzlich gilt für dieses Projekt ganz praktisch:

**Repo-Stand ↔ Runtime-Datei ↔ FTP-Upload ↔ Live-Prüfung**

Diese vier Ebenen sollen hier bewusst nah beieinander bleiben. Das ist kein Nebenhinweis, sondern Teil des Arbeitsmodells. Wer 365CMS in diesem Projekt pflegt, pflegt nicht einen abstrakten Codebestand, sondern einen Codebestand, der nach Änderungen unmittelbar in denselben Zustand hochgeladen wird. Genau deshalb muss die Doku nicht nur erklären, wie 365CMS technisch aufgebaut ist, sondern auch, wie dieser Aufbau real betrieben und kontrolliert wird.

### 30.1 Das eigentliche Leitmotiv dieser DEVLIST

Die DEVLIST soll deshalb nicht nur Wissen sammeln, sondern Denkfehler verhindern. Ihr Kernmotiv lautet:

- Runtime vor Annahme,
- Vertrag vor Zufall,
- Diagnose vor Bauchgefühl,
- und Live-Realität vor Papierwahrheit.

Wenn diese vier Prioritäten erhalten bleiben, bleibt 365CMS auch dann wartbar, wenn Umfang, Module und Betriebsdruck weiter wachsen.
