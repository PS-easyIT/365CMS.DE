# 365CMS – Projektdokumentation | Abschnitt: CMS login page
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
Branding and content for the CMS login page are maintained at `/admin/cms-loginpage`.

### Implementation
- Entry/view: `CMS/admin/cms-loginpage.php`, `CMS/admin/views/themes/cms-loginpage.php`
- Service: `CMS/core/Services/CmsAuthPageService.php`
- Routing: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`

### Administration
Use approved image and text fields only. Save requests must pass capability and CSRF/nonce checks; sanitize inputs and escape the rendered context. Verify the unauthenticated page after saving without exposing credentials or recovery details.

## Deutsch
### Zweck
Branding und Inhalte der CMS-Loginseite werden unter `/admin/cms-loginpage` gepflegt.

### Implementierung
- Einstieg/View: `CMS/admin/cms-loginpage.php`, `CMS/admin/views/themes/cms-loginpage.php`
- Service: `CMS/core/Services/CmsAuthPageService.php`
- Routing: `CMS/core/Routing/AdminRouter.php`, `CMS/core/Router.php`

### Administration
Nur freigegebene Bild- und Textfelder verwenden. Speichern erfordert Capability und CSRF/Nonce; Eingaben sanitizen und kontextgerecht escapen. Die nicht angemeldete Seite nach dem Speichern prüfen, ohne Zugangsdaten oder Wiederherstellungsdetails offenzulegen.
