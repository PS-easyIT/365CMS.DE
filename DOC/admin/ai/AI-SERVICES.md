> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Bedienungs- und Technikreferenz für die geschützten AI-Adminseiten, Provider-Policies, Quotas, Übersetzungen und Entwurfs-Pipelines.

# 365CMS Admin – AI Services

## English

### Administrator guide

Use `/admin/ai-services` for the overview, `/admin/ai-settings` for provider and policy settings, `/admin/ai-translation` for Editor.js translation, `/admin/ai-content-creator` for content drafts, and `/admin/ai-seo-creator` for SEO metadata drafts. Review generated text before copying it into publishable content; generation never replaces editorial approval.

Configure only approved providers and quotas. A failed readiness check, quota limit, or disabled feature gate is a safe stop. Do not paste credentials or confidential personal data into prompts.

### Technical reference

The five AI screens share `CMS/admin/ai-page.php`: `overview`, `translation`, `content_creator`, `seo_creator`, and `settings`. The entry shims `CMS/admin/ai-services.php`, `ai-translation.php`, `ai-content-creator.php`, `ai-seo-creator.php`, and `ai-settings.php` select the section and render `CMS/admin/views/system/ai-services.php` through the shared shell. The routes are `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, and `/admin/ai-settings`.

Read access requires an administrator plus one of `manage_settings`, `manage_system`, or `manage_ai_services`; write access additionally requires `manage_settings`. Actions are section-scoped: translation saves translation settings and prompt templates, content creator saves content prompts or runs `generate_content_draft`, SEO creator saves SEO prompts, and settings manages providers, features, logging, quotas, and provider health. Unknown actions and actions sent to the wrong section are rejected.

AI admin screens use the system module under `CMS/admin/modules/system/AiServicesModule.php` and services under `CMS/core/Services/AI/`. `AiProviderPolicyService` controls provider eligibility, `AiQuotaService` enforces limits, and `AiExecutionService` coordinates execution, retries, and bounded fallback. Content drafts receive task, brief, context, tone, and locale inputs, are returned inline as unsaved suggestions, and are never published automatically. Translation and SEO generation use their dedicated pipeline classes.

All write requests use the admin authentication and CSRF contract. Provider credentials are stored through settings services and are excluded from logs. Monitoring records bounded metadata, not raw prompts or generated full text. External data transfer requires the configured policy gate.

## Deutsch

### Anwenderleitfaden

Die Übersicht liegt unter `/admin/ai-services`; Provider und Richtlinien werden unter `/admin/ai-settings` gepflegt. Übersetzungen öffnen `/admin/ai-translation`, Content-Entwürfe `/admin/ai-content-creator` und SEO-Entwürfe `/admin/ai-seo-creator`. Generierte Texte müssen vor Veröffentlichung fachlich geprüft werden.

Nur freigegebene Provider und Quotas verwenden. Readiness-, Quota- oder Feature-Gate-Fehler stoppen den Vorgang sicher. Zugangsdaten und vertrauliche personenbezogene Daten gehören nicht in Prompts.

### Technische Referenz

Die fünf AI-Seiten verwenden gemeinsam `CMS/admin/ai-page.php` mit den Bereichen `overview`, `translation`, `content_creator`, `seo_creator` und `settings`. Die Einstiegsshims `CMS/admin/ai-services.php`, `ai-translation.php`, `ai-content-creator.php`, `ai-seo-creator.php` und `ai-settings.php` wählen den Bereich; die Darstellung läuft über `CMS/admin/views/system/ai-services.php` und die gemeinsame Shell. Die Routen sind `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator` und `/admin/ai-settings`.

Für den Lesezugriff ist ein Administrator mit mindestens einer Capability aus `manage_settings`, `manage_system` oder `manage_ai_services` erforderlich; Schreibzugriffe benötigen zusätzlich `manage_settings`. Aktionen sind bereichsgebunden: Übersetzung speichert Übersetzungssettings und Promptvorlagen, der Inhaltsassistent speichert Content-Prompts oder führt `generate_content_draft` aus, der SEO-Assistent speichert SEO-Prompts und die Einstellungen verwalten Provider, Features, Logging, Quotas und Provider-Health. Unbekannte oder falsch zugeordnete Aktionen werden abgewiesen.

Die AI-Seiten verwenden das Systemmodul unter `CMS/admin/modules/system/AiServicesModule.php` und Services unter `CMS/core/Services/AI/`. `AiProviderPolicyService` prüft Provider, `AiQuotaService` begrenzt Nutzung und `AiExecutionService` steuert Ausführung, Retries und begrenzte Fallbacks. Content-Entwürfe verarbeiten Aufgabe, Briefing, Kontext, Tonalität und Sprache, werden inline als ungespeicherte Vorschläge zurückgegeben und niemals automatisch veröffentlicht. Übersetzung und SEO-Erzeugung verwenden eigene Pipeline-Klassen.

Schreibende Requests verwenden den Authentifizierungs- und CSRF-Vertrag des Admins. Provider-Zugangsdaten laufen über Settings-Services und werden nicht geloggt. Monitoring enthält nur begrenzte Metadaten, keine Rohprompts oder vollständigen generierten Texte. Externe Datenweitergabe erfordert das konfigurierte Policy-Gate.
