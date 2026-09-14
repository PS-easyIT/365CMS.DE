# 365CMS – Projektdokumentation | Abschnitt: Workflow – API-Integration
> **Stand:** 2026-09-14 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-14

## English

Use the API router and API service shipped under `CMS/core/Routing/ApiRouter.php` and `CMS/core/Api.php`. Confirm the route and request method in the current runtime before integrating; do not infer endpoints from this workflow alone. Protect state-changing requests with the existing authentication, capability, CSRF, validation, and escaping rules.

## Deutsch

Verwenden Sie den API-Router und API-Service unter `CMS/core/Routing/ApiRouter.php` und `CMS/core/Api.php`. Prüfen Sie Route und HTTP-Methode vor einer Integration in der aktuellen Runtime; dieser Workflow ist keine zusätzliche Endpunktliste. Zustandsändernde Anfragen müssen die vorhandenen Regeln für Authentifizierung, Capabilities, CSRF, Validierung und Escaping einhalten.
