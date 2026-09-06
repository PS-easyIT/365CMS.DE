# Microsoft Graph SDK / Referenzablage
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Referenz, nicht aktiv verdrahtet

`msgraph/` in `CMS/assets/` ist derzeit eine Referenz-/Ablageposition für Microsoft-Graph-nahe Artefakte. Eine produktive Verdrahtung im Core ist im Stand `2.8.0 RC` nicht aktiv dokumentiert.

## Quellordner

- `CMS/assets/msgraph/`
- ergänzende Import-/Downloadstände im Repo-Root `ASSETS/`:
  - `ASSETS/msgraph-sdk-php-2.56.0/`
  - `ASSETS/msgraph-training-php-main/`

## Verwendung in 365CMS

- Produktivstatus: aktuell nicht aktiv verdrahtet
- Zweck: Referenz, spätere Integrationen, Experimentier- oder Importbasis
- Empfehlung: künftige Graph-Anbindung ausschließlich über einen eigenen Core-Service kapseln, nie direkt aus Themes oder Einzelviews heraus

## Hinweise für spätere Aktivierung

- Authentifizierung, Token-Refresh und Scope-Handling müssen zuerst in einen dedizierten Service wandern
- Externe HTTP-/Graph-Aufrufe gehören in Monitoring, Rate-Limit- und Error-Handling-Pfade
- Vor Aktivierung zusätzlich `DOC/assets/VENDOR-NETWORK-PATHS.md` prüfen/ergänzen

## Verwandte Doku

- [../../ASSETS_OwnAssets.md](../../ASSETS_OwnAssets.md)
- [../VENDOR-NETWORK-PATHS.md](../VENDOR-NETWORK-PATHS.md)