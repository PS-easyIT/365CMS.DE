# 365CMS – Projektdokumentation | Abschnitt: Administration
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The `CMS/admin/` directory contains the authenticated administration area. Administrative entry points must load the application configuration and autoloader, reject direct access, and enforce the central administrator check before rendering or mutating data.

### Structure

```text
admin/
├── modules/    # Feature modules
├── views/      # Administration views
├── partials/   # Shared shell and UI fragments
└── *.php       # Administrative entry points
```

### Security contract

Administrative code must:

- use the shared bootstrap and autoloader;
- enforce authentication and capability checks server-side;
- validate CSRF tokens for state-changing requests;
- sanitize input and escape output;
- return explicit errors instead of silently accepting invalid input.

Use the public admin documentation under [`../../DOC/admin/`](../../DOC/admin/) for module-specific behavior. The runtime stylesheet is located under [`../assets/css/`](../assets/css/); do not introduce inline styles or generic cross-plugin selectors.

## Deutsch

Das Verzeichnis `CMS/admin/` enthält den authentifizierten Administrationsbereich. Jede administrative Einstiegsdatei muss Konfiguration und Autoloader laden, Direktzugriff verhindern und vor Ausgabe oder Zustandsänderungen zentrale Authentifizierungs- und Berechtigungsprüfungen ausführen.

### Sicherheitsvertrag

Admin-Code muss den gemeinsamen Bootstrap verwenden, serverseitig Authentifizierung und Capabilities prüfen, Nonces beziehungsweise CSRF-Tokens für zustandsändernde Requests validieren, Eingaben sanitizen und Ausgaben escapen. Fehler dürfen nicht stillschweigend verschluckt werden.

Modulspezifische Hinweise stehen unter [`../../DOC/admin/`](../../DOC/admin/). Assets werden aus der Runtime geladen; Inline-CSS und generische, pluginübergreifende Selektoren sind zu vermeiden.
