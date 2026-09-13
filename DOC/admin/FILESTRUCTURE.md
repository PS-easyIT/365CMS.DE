# 365CMS – Projektdokumentation | Abschnitt: Admin – Dateistruktur
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

```text
CMS/
├── admin/             # Entry points and compatibility shells
│   ├── modules/       # Feature modules
│   ├── partials/      # Shared admin fragments
│   └── views/         # Rendered admin views
├── core/
│   ├── Auth/          # Authentication support
│   ├── Routing/       # Admin, API, and member routing
│   └── Services/      # Settings and domain services
├── plugins/           # Runtime plugins
└── themes/            # Runtime themes
```

`AdminRouter.php` resolves a route to an entry file, a module page, or a legacy fallback. Keep persistence in modules and services; views should render validated data only.

## Deutsch

```text
CMS/
├── admin/             # Einstiege und Kompatibilitätsshells
│   ├── modules/       # Fachmodule
│   ├── partials/      # Gemeinsame Admin-Fragmente
│   └── views/         # Gerenderte Admin-Views
├── core/
│   ├── Auth/          # Authentifizierungsunterstützung
│   ├── Routing/       # Admin-, API- und Member-Routing
│   └── Services/      # Settings- und Fachdienste
├── plugins/           # Runtime-Plugins
└── themes/            # Runtime-Themes
```

`AdminRouter.php` löst eine Route auf einen Einstieg, eine Modul-Seite oder einen Legacy-Fallback auf. Persistenz bleibt in Modulen und Services; Views rendern ausschließlich geprüfte Daten.
