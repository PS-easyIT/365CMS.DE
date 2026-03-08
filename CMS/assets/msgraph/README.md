# Microsoft Graph – lokale Notizen

Dieses Verzeichnis markiert die lokale Microsoft-Graph-Integration im produktiven `CMS/assets/`-Baum.

## Hintergrund

Die aktuelle 365CMS-Integration nutzt bewusst einen schlanken cURL-basierten Graph-Client in `CMS/core/Services/GraphApiService.php`, damit Shared-Hosting-Deployments ohne zusätzliche Composer-/Kiota-Abhängigkeiten stabil bleiben.

## Quellreferenzen aus der Entwicklungsablage

Die ursprünglichen Referenzquellen liegen im Repo-Root unter:

- `ASSETS/msgraph-sdk-php-2.56.0/`
- `ASSETS/msgraph-training-php-main/`

Diese Ordner dienen als Upstream-Referenz für eine spätere Voll-SDK-Integration.
