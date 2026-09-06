> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Bedienungs- und Technikreferenz für die geschützten AI-Adminseiten, Provider-Policies, Quotas, Übersetzungen und Entwurfs-Pipelines.

# 365CMS Admin – AI Services

## English

### Administrator guide

Use `/admin/ai-services` for the overview, `/admin/ai-settings` for provider and policy settings, `/admin/ai-translation` for Editor.js translation, `/admin/ai-content-creator` for content drafts, and `/admin/ai-seo-creator` for SEO metadata drafts. Review generated text before copying it into publishable content; generation never replaces editorial approval.

Configure only approved providers and quotas. A failed readiness check, quota limit, or disabled feature gate is a safe stop. Do not paste credentials or confidential personal data into prompts.

### Technical reference

AI admin screens are implemented by the system modules under `CMS/admin/modules/system/` and use `CMS/core/Services/AI/` services. `AiProviderPolicyService` controls provider eligibility, `AiQuotaService` enforces limits, and `AiExecutionService` coordinates execution, retries, and bounded fallback. Translation and SEO generation use their dedicated pipeline classes.

All write requests use the admin authentication and CSRF contract. Provider credentials are stored through settings services and are excluded from logs. Monitoring records bounded metadata, not raw prompts or generated full text. External data transfer requires the configured policy gate.

## Deutsch

### Anwenderleitfaden

Die Übersicht liegt unter `/admin/ai-services`; Provider und Richtlinien werden unter `/admin/ai-settings` gepflegt. Übersetzungen öffnen `/admin/ai-translation`, Content-Entwürfe `/admin/ai-content-creator` und SEO-Entwürfe `/admin/ai-seo-creator`. Generierte Texte müssen vor Veröffentlichung fachlich geprüft werden.

Nur freigegebene Provider und Quotas verwenden. Readiness-, Quota- oder Feature-Gate-Fehler stoppen den Vorgang sicher. Zugangsdaten und vertrauliche personenbezogene Daten gehören nicht in Prompts.

### Technische Referenz

Die AI-Adminseiten registrieren die Systemmodule unter `CMS/admin/modules/system/` und verwenden die Services unter `CMS/core/Services/AI/`. `AiProviderPolicyService` prüft Provider, `AiQuotaService` begrenzt Nutzung und `AiExecutionService` steuert Ausführung, Retries und begrenzte Fallbacks. Übersetzung und SEO-Erzeugung verwenden eigene Pipeline-Klassen.

Schreibende Requests folgen Authentifizierung und CSRF-Vertrag des Admins. Provider-Zugangsdaten laufen über Settings-Services und werden nicht geloggt. Monitoring enthält nur begrenzte Metadaten, keine Rohprompts oder vollständigen generierten Texte. Externe Datenweitergabe erfordert das konfigurierte Policy-Gate.
