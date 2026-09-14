# 365CMS – Projektdokumentation | Abschnitt: Structure
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### Live code structure
The relevant runtime code is organized as follows under [CMS](../../CMS/):

```text
CMS/
├── core/
│   ├── Bootstrap.php
│   ├── Container.php
│   ├── Database.php
│   ├── Router.php
│   ├── Security.php
│   ├── Auth.php
│   ├── Hooks.php
│   ├── Version.php
│   ├── Routing/
│   │   ├── ApiRouter.php
│   │   ├── AdminRouter.php
│   │   ├── MemberRouter.php
│   │   ├── PublicRouter.php
│   │   └── ThemeRouter.php
│   └── ...
├── config/
├── public/
├── resources/
├── storage/
├── vendor/
└── ...
```

### What the runtime actually does
- `Bootstrap.php` detects the execution mode (`cli`, `api`, `admin`, `web`).
- `Router.php` registers default route groups by path prefix.
- `ApiRouter.php` defines the active API surface.
- `Security.php` and `Auth.php` implement request and session enforcement.
- `Database.php` provides the PDO connection and prepared-statement abstraction.

### Scope discipline
This structure is authoritative for documentation. Files outside the shipped runtime are not added to the core reference unless they are directly present in the implemented codebase.

## Deutsch

### Reale Code-Struktur
Die relevante Laufzeit ist unter [CMS](../../CMS/) wie folgt organisiert:

```text
CMS/
├── core/
│   ├── Bootstrap.php
│   ├── Container.php
│   ├── Database.php
│   ├── Router.php
│   ├── Security.php
│   ├── Auth.php
│   ├── Hooks.php
│   ├── Version.php
│   ├── Routing/
│   │   ├── ApiRouter.php
│   │   ├── AdminRouter.php
│   │   ├── MemberRouter.php
│   │   ├── PublicRouter.php
│   │   └── ThemeRouter.php
│   └── ...
├── config/
├── public/
├── resources/
├── storage/
├── vendor/
└── ...
```

### Was die Laufzeit tatsächlich macht
- `Bootstrap.php` erkennt den Ausführungsmodus (`cli`, `api`, `admin`, `web`).
- `Router.php` registriert Standard-Route-Gruppen nach Pfad-Präfix.
- `ApiRouter.php` definiert die aktive API-Oberfläche.
- `Security.php` und `Auth.php` implementieren Request- und Session-Validierung.
- `Database.php` stellt die PDO-Verbindung und Prepared-Statement-Abstraktion bereit.

### Disziplin bei der Dokumentation
Diese Struktur ist für die Dokumentation maßgeblich. Dateien außerhalb der ausgelieferten Laufzeit werden nur aufgenommen, wenn sie direkt im implementierten Codebase vorhanden sind.
