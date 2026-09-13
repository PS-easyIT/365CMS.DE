# 365CMS – Projektdokumentation | Abschnitt: AI services
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English
### Purpose
AI provider configuration and service status are exposed at `/admin/ai-services`.

### Implementation
- Entry: `CMS/admin/ai-services.php`
- View: `CMS/admin/views/system/ai-services.php`
- Services: `CMS/core/Services/AI/AiService.php`, `CMS/core/Services/AI/AiSettingsService.php`, `CMS/core/Services/AI/AiProviderFactory.php`

### Administration
Configure only supported providers and review quota or health warnings before saving. Protect changes with the shared admin capability and CSRF/nonce checks. Never expose API keys, tokens, raw prompts, or provider responses in UI or logs; use `CMS/core/Logger.php` and `CMS/core/AuditLogger.php` for bounded diagnostics.

## Deutsch
### Zweck
KI-Provider-Konfiguration und Dienststatus stehen unter `/admin/ai-services` bereit.

### Implementierung
- Einstieg: `CMS/admin/ai-services.php`
- View: `CMS/admin/views/system/ai-services.php`
- Services: `CMS/core/Services/AI/AiService.php`, `CMS/core/Services/AI/AiSettingsService.php`, `CMS/core/Services/AI/AiProviderFactory.php`

### Administration
Nur unterstützte Provider konfigurieren und Quota- oder Gesundheitswarnungen vor dem Speichern prüfen. Änderungen verwenden Capability- und CSRF-/Nonce-Prüfungen. API-Schlüssel, Tokens, Rohprompts und Providerantworten niemals in UI oder Logs ausgeben; Diagnosen über `CMS/core/Logger.php` und `CMS/core/AuditLogger.php` begrenzen.
