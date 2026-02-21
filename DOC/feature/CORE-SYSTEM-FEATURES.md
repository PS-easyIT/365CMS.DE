# 365CMS – Core-System Features & Ausbaustufen

**Bereich:** Kern-Architektur, Engine, System-Services  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 1. Engine & Architektur

### 🔴 1.1 · Dependency Injection Container (DIC)
**Aktuell:** Singleton-Pattern überall, enge Kopplung  
**Ziel:** PSR-11-konformer IoC-Container für alle Services

| Stufe | Feature |
|---|---|
| Stufe 1 | Einfacher Service-Container mit `bind()` und `resolve()` |
| Stufe 2 | Auto-Wiring via Reflection (Konstruktor-Analyse) |
| Stufe 3 | Scoped Bindings (Singleton, Transient, Scoped) |
| Stufe 4 | Lazy Loading (Services erst bei erstem Aufruf instanziert) |
| Stufe 5 | Interface-zu-Implementierung-Binding konfigurierbar |
| Stufe 6 | Container-Debugging (welche Services wurden wann aufgelöst) |

**Technische Basis:**
```php
// Angestrebte API
$container->bind(SearchInterface::class, ElasticsearchService::class);
$search = $container->resolve(SearchInterface::class);
```

---

### 🔴 1.2 · Event-Bus / Message-Bus
**Aktuell:** Einfaches Hook-System (Actions & Filters)  
**Ziel:** Asynchroner Event-Bus mit Queue-Unterstützung

| Stufe | Feature |
|---|---|
| Stufe 1 | Synchroner Event-Bus (wie aktuell, typsichere Events) |
| Stufe 2 | Typed Event-Klassen (`ExpertRegisteredEvent`, `PostPublishedEvent`) |
| Stufe 3 | Event-Listener-Registration via Attribut (`#[ListensTo(ExpertRegisteredEvent::class)]`) |
| Stufe 4 | Asynchrone Events via Job-Queue (Datenbank-basiert) |
| Stufe 5 | Redis-Queue für High-Throughput-Szenarien |
| Stufe 6 | Dead-Letter-Queue für fehlgeschlagene Event-Handler |
| Stufe 7 | Event-Sourcing-Grundlagen (Event-Log als Source of Truth) |

---

### 🟠 1.3 · PSR-4 Autoloading & Namespace-Reorganisation
**Aktuell:** Manuelles `require_once` in vielen Bereichen  
**Ziel:** Vollständiges Composer-Autoloading, saubere Namespace-Hierarchie

| Stufe | Feature |
|---|---|
| Stufe 1 | Composer-Autoloading für alle Core-Klassen (`CMS365\Core\`) |
| Stufe 2 | Plugin-Namespaces (`CMS365\Plugins\Experts\`) |
| Stufe 3 | PSR-4-konforme Verzeichnisstruktur |
| Stufe 4 | Dev-Dependency-Trennung (phpunit, phpstan nur in dev) |
| Stufe 5 | Optimierter Autoloader-Cache für Production |

---

### 🟠 1.4 · Configuration Management
**Aktuell:** Einzelne `config.php`-Datei  
**Ziel:** Umgebungs-aware Konfiguration, versionierbare Settings

| Stufe | Feature |
|---|---|
| Stufe 1 | `.env`-File-Support (dotenv) |
| Stufe 2 | Umgebungs-Hierarchie (dev → staging → production) |
| Stufe 3 | Typed Config-Klassen mit Validierung |
| Stufe 4 | Secrets-Management (HashiCorp Vault / AWS Secrets Manager) |
| Stufe 5 | Feature-Flags (Ein/Ausschalten von Features per Konfig) |
| Stufe 6 | Remote-Config via Admin-API (JSON-basiert) |

---

### 🟠 1.5 · Command Line Interface (CLI)
**Aktuell:** Kein CLI vorhanden  
**Ziel:** `cms365` CLI-Tool für administrative Aufgaben

| Stufe | Feature |
|---|---|
| Stufe 1 | Basis-CLI-Framework (Symfony Console oder eigene Impl.) |
| Stufe 2 | `cms365 migrate` – Datenbank-Migrationen ausführen |
| Stufe 3 | `cms365 cache:clear` – Cache leeren |
| Stufe 4 | `cms365 plugin:install/activate/deactivate` |
| Stufe 5 | `cms365 user:create` – Admin-User via CLI anlegen |
| Stufe 6 | `cms365 backup:create/restore` |
| Stufe 7 | `cms365 cron:run` – Geplante Aufgaben manuell auslösen |
| Stufe 8 | WP-CLI-Kompatibilitäts-Shim (schrittweise Migration) |

---

## 2. Router & HTTP-Layer

### 🔴 2.1 · Robusterer Router
**Aktuell:** Einfacher Pattern-Matching-Router  
**Ziel:** Vollständiger HTTP-Router mit Middleware-Stack

| Stufe | Feature |
|---|---|
| Stufe 1 | Named Routes (`route('expert.show', ['id' => 5])`) |
| Stufe 2 | Route-Groups mit Prefix und Middleware |
| Stufe 3 | Middleware-Stack (Auth, CORS, Rate-Limit, Cache) |
| Stufe 4 | Route-Parameter-Constraints (regex, type) |
| Stufe 5 | Subdomain-Routing (`api.example.com`) |
| Stufe 6 | Route-Caching für Production-Performance |
| Stufe 7 | PSR-15-Middleware-Kompatibilität |

---

### 🟠 2.2 · HTTP-Request/Response-Objekte
**Ziel:** PSR-7-konforme Immutable HTTP-Messages

| Stufe | Feature |
|---|---|
| Stufe 1 | `Request`-Klasse (URL, Method, Headers, Body, Files) |
| Stufe 2 | `Response`-Klasse (Status, Headers, Body) |
| Stufe 3 | PSR-7-Kompatibilität (Interop mit Drittanbieter-Bibliotheken) |
| Stufe 4 | Streaming-Responses für große Dateien |
| Stufe 5 | Server-Sent Events (SSE) für Echtzeit-Updates |

---

## 3. Caching-System

### 🔴 3.1 · Mehrstufiges Cache-System
**Aktuell:** Einfaches Datei-Caching  
**Ziel:** Flexibles PSR-16-konformes Cache-System mit Backends

| Stufe | Feature |
|---|---|
| Stufe 1 | PSR-16 Cache-Interface für alle Cache-Operationen |
| Stufe 2 | Backend-Treiber: File, APCu, Redis, Memcached |
| Stufe 3 | Cache-Tags (Gruppen invalidieren: alle Experten-Caches löschen) |
| Stufe 4 | Page-Caching (vollständige HTML-Seiten-Caches) |
| Stufe 5 | Fragment-Caching (Teile von Seiten separat cachen) |
| Stufe 6 | Cache-Warming (Kritische Seiten nach Deploy aufwärmen) |
| Stufe 7 | Distributed Cache (mehrere Server, Redis-Cluster) |
| Stufe 8 | Cache-Dashboard (Trefferquote, Speichernutzung, Top-Keys) |

---

### 🟠 3.2 · Query-Caching & ORM
**Aktuell:** Direktes PDO/`$wpdb`-ähnliches Pattern  
**Ziel:** Query-Builder mit automatischem Query-Caching

| Stufe | Feature |
|---|---|
| Stufe 1 | Fluent Query-Builder (`$db->table('experts')->where('active', 1)->get()`) |
| Stufe 2 | Automatisches Query-Result-Caching (konfigurierbare TTL) |
| Stufe 3 | Einfaches Active-Record-Pattern für Entitäten |
| Stufe 4 | Beziehungen (hasMany, belongsTo, manyToMany) |
| Stufe 5 | Eager-Loading (N+1-Problem aufgelöst) |
| Stufe 6 | Cache-Invalidierung bei Schreiboperationen (automatisch) |

---

## 4. Queue & Job-System

### 🟠 4.1 · Background-Job-Queue
**Aktuell:** Kein Job-System  
**Ziel:** Asynchrone Aufgaben für Performance und Skalierung

| Stufe | Feature |
|---|---|
| Stufe 1 | Datenbank-Queue (einfache `cms_jobs`-Tabelle) |
| Stufe 2 | Cron-Worker (verarbeitet Queue jede Minute) |
| Stufe 3 | Job-Prioritäten (High/Normal/Low) |
| Stufe 4 | Job-Retry mit konfigurierbaren Versuchen |
| Stufe 5 | Failed-Jobs-Queue mit Admin-Ansicht |
| Stufe 6 | Redis-Queue für Performance (sub-second Latenz) |
| Stufe 7 | Job-Scheduling (wiederkehrende Jobs mit Cron-Syntax) |
| Stufe 8 | Horizontale Skalierung (mehrere Worker-Prozesse) |

**Anwendungsfälle:**
- E-Mail-Versand
- Thumbnail-Generierung
- Sitemaps-Erstellung
- Analytics-Aggregation
- Import/Export-Jobs

---

## 5. Template-Engine

### 🟠 5.1 · Template-Engine-Verbesserungen
**Aktuell:** PHP als Template-Sprache  
**Ziel:** Optionaler Template-Compiler mit Caching

| Stufe | Feature |
|---|---|
| Stufe 1 | Template-Inheritance (`@extends`, `@section`, `@yield`) |
| Stufe 2 | Template-Partials (`@include`) mit Variablen-Passing |
| Stufe 3 | Template-Slots (Component-ähnliche Wiederverwendung) |
| Stufe 4 | Auto-Escaping (XSS-Schutz by Default in Templates) |
| Stufe 5 | Template-Kompilierung und PHP-Bytecode-Cache |
| Stufe 6 | Optionale Blade/Twig-Kompatibilität |

---

## 6. Cron & Scheduler

### 🟡 6.1 · Task-Scheduler
**Aktuell:** Einfache PHP-Cron-Jobs  
**Ziel:** Zuverlässiger, administrierbarer Task-Scheduler

| Stufe | Feature |
|---|---|
| Stufe 1 | Plugin-registrierbare Cron-Tasks via Hook |
| Stufe 2 | Admin-UI zum Anzeigen/Triggern geplanter Tasks |
| Stufe 3 | Cron-Expressions (Standard `*/5 * * * *`-Syntax) |
| Stufe 4 | Task-Locking (verhindert parallele Ausführung) |
| Stufe 5 | Ausführungs-Protokoll (Start, Ende, Dauer, Status) |
| Stufe 6 | Benachrichtigung bei fehlgeschlagenen Tasks (E-Mail) |

---

## 7. Update-System

### 🔴 7.1 · Auto-Update & Rollback
**Aktuell:** GitHub-basiertes One-Click-Update  
**Ziel:** Sicheres Update-System mit Staging und Rollback

| Stufe | Feature |
|---|---|
| Stufe 1 | Pre-Update-Backup (automatisch vor jedem Update) |
| Stufe 2 | Update-Kanäle (Stable, Beta, Nightly) |
| Stufe 3 | Changelog-Ansicht vor Update-Installation |
| Stufe 4 | Staging-Update (erst Test-Umgebung, dann Production) |
| Stufe 5 | Automatisches Rollback nach fehlgeschlagenem Update |
| Stufe 6 | Plugin/Theme-Kompatibilitäts-Check vor Update |
| Stufe 7 | Delta-Updates (nur geänderte Dateien übertragen) |

---

## 8. Internationalisierung (i18n / L10n)

### 🟠 8.1 · i18n-Framework
**Aktuell:** Einfache gettext-Strings  
**Ziel:** Vollständiges i18n-Framework mit pluralen Formen und ICU

| Stufe | Feature |
|---|---|
| Stufe 1 | ICU-Message-Format für komplexe Übersetzungen |
| Stufe 2 | Pluralformen (1 Experte / 5 Experten) |
| Stufe 3 | Datum-, Zeit-, Zahl- und Währungsformatierung nach Locale |
| Stufe 4 | RTL-Layout-Unterstützung (automatisches Spiegeln von UI) |
| Stufe 5 | In-Context-Translation (Übersetzungs-Overlay direkt im Frontend) |
| Stufe 6 | Translation-Memory (gleiche Strings wiederverwendet) |

---

*Letzte Aktualisierung: 19. Februar 2026*
