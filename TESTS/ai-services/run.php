<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Services\AI\AiSettingsService;
use CMS\Services\AI\Providers\OpenAiCompatibleProvider;

/**
 * @throws RuntimeException
 */
function aiAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function aiReadFile(string $path): string
{
    aiAssert(is_file($path), 'Datei fehlt: ' . $path);
    $content = file_get_contents($path);
    aiAssert(is_string($content) && $content !== '', 'Datei ist leer oder nicht lesbar: ' . $path);

    return $content;
}

$root = dirname(__DIR__, 2);

$tests = [
    'AI Provider-Katalog enthält alle produktiven Zielprovider' => static function (): void {
        $expectedProviders = ['mock', 'ollama', 'azure_openai', 'openai', 'mistral', 'openrouter'];

        foreach ($expectedProviders as $providerType) {
            aiAssert(AiSettingsService::isKnownProviderType($providerType), 'Providertyp fehlt: ' . $providerType);
            aiAssert(AiSettingsService::isAddableProviderType($providerType), 'Providertyp ist im Admin nicht addable: ' . $providerType);
        }
    },
    'AI Settings erzwingen Single-Provider-Speicherung' => static function () use ($root): void {
        $settings = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiSettingsService.php');

        aiAssert(str_contains($settings, '$sanitizedEntries = [$selectedEntry];'), 'Settings reduzieren Provider-Einträge nicht auf genau einen aktiven Provider.');
        aiAssert(str_contains($settings, '$entries = [$activeEntry];'), 'Settings normalisieren geladene Provider nicht auf genau einen aktiven Provider.');
        aiAssert(str_contains($settings, "forget(self::GROUP_PROVIDERS, 'fallback_provider_id')"), 'Alte Fallback-Provider-Einstellungen werden nicht bereinigt.');
    },
    'AI Settings bewahren gespeicherte Secrets beim Providerwechsel' => static function () use ($root): void {
        $settings = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiSettingsService.php');

        aiAssert(str_contains($settings, 'foreach ($clearSecrets as $providerId)'), 'Explizites Secret-Löschen fehlt.');
        aiAssert(!str_contains($settings, 'foreach (self::PROVIDER_SLUGS as $providerSlug)'), 'Providerwechsel löscht weiterhin Secrets anderer Provider.');
        aiAssert(!str_contains($settings, '$providerSlug === $activeProviderId'), 'Sibling-Secret-Bereinigung ist weiterhin an den aktiven Provider gekoppelt.');
    },
    'AI Provider-Katalog markiert Live-Provider korrekt' => static function (): void {
        foreach (['ollama', 'azure_openai', 'openai', 'mistral', 'openrouter'] as $providerType) {
            $definition = AiSettingsService::getProviderTypeDefinition($providerType);
            aiAssert(!empty($definition['live_supported']), 'Live-Support fehlt für: ' . $providerType);
        }

        foreach (['azure_openai', 'openai', 'mistral', 'openrouter'] as $providerType) {
            $definition = AiSettingsService::getProviderTypeDefinition($providerType);
            aiAssert(!empty($definition['requires_secret']), 'Secret-Pflicht fehlt für: ' . $providerType);
            aiAssert(trim((string) ($definition['secret_label'] ?? '')) !== '', 'Secret-Label fehlt für: ' . $providerType);
        }
    },
    'AI Provider-Modellkatalog ist providerabhängig und ohne GPT-4.x' => static function () use ($root): void {
        $settings = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiSettingsService.php');
        $gateway = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiProviderGateway.php');

        aiAssert(!str_contains($settings, 'gpt-4'), 'Settings enthalten noch GPT-4.x-Modelle.');
        aiAssert(!str_contains($gateway, 'gpt-4'), 'Gateway enthält noch GPT-4.x-Fallbacks.');

        foreach (['gpt-5.3', 'gpt-5.4', 'gpt-5.5'] as $model) {
            aiAssert(isset(AiSettingsService::getProviderModelOptions('openai')[$model]), 'OpenAI-Modell fehlt: ' . $model);
            aiAssert(isset(AiSettingsService::getProviderModelOptions('azure_openai')[$model]), 'Azure-AI-Modell fehlt: ' . $model);
        }

        foreach (['mistral-small-latest', 'mistral-large-latest'] as $model) {
            aiAssert(isset(AiSettingsService::getProviderModelOptions('mistral')[$model]), 'Mistral-Modell fehlt: ' . $model);
        }

        aiAssert(AiSettingsService::normalizeProviderModel('openai', 'gpt-4.1-mini') === 'gpt-5.3', 'GPT-4.x wird nicht auf erlaubtes OpenAI-Modell zurückgesetzt.');
        aiAssert(AiSettingsService::normalizeProviderModel('mistral', 'gpt-4.1-mini') === 'mistral-small-latest', 'Falsches Provider-Modell wird nicht auf Mistral-Default zurückgesetzt.');
    },
    'OpenAI-kompatibler Live-Adapter ist autoloadbar' => static function (): void {
        aiAssert(class_exists(OpenAiCompatibleProvider::class), 'OpenAiCompatibleProvider ist nicht autoloadbar.');
    },
    'AI Provider unterstützen generische Content-/SEO-Generierung' => static function () use ($root): void {
        $interface = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiProviderInterface.php');
        aiAssert(str_contains($interface, 'generateText'), 'generateText fehlt im AI Provider Interface.');

        foreach (['MockAiProvider.php', 'OpenAiCompatibleProvider.php', 'AzureOpenAiProvider.php', 'OllamaAiProvider.php'] as $providerFile) {
            $provider = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $providerFile);
            aiAssert(str_contains($provider, 'function generateText'), 'generateText fehlt in ' . $providerFile);
        }
    },
    'Gateway verdrahtet Mistral/OpenAI/OpenRouter und Azure AI ohne Fallback' => static function () use ($root): void {
        $gateway = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiProviderGateway.php');

        aiAssert(str_contains($gateway, 'OpenAiCompatibleProvider'), 'OpenAiCompatibleProvider wird im Gateway nicht referenziert.');
        aiAssert(str_contains($gateway, "'openai', 'mistral', 'openrouter'"), 'OpenAI/Mistral/OpenRouter-Match fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'AzureOpenAiProvider'), 'AzureOpenAiProvider fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'defaultModelForOpenAiCompatibleProvider'), 'Default-Modell-Auflösung für kompatible Provider fehlt.');
        aiAssert(str_contains($gateway, 'generateContentDraft'), 'Content-Generator fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'generateSeoDraft'), 'SEO-Generator fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'resolveProviderForCapability'), 'Capability-basierte Provider-Auflösung fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'testActiveProvider'), 'Zentraler Provider-Test fehlt im Gateway.');
        aiAssert(!str_contains($gateway, 'auto-fallback'), 'Gateway enthält noch Auto-Fallback-Logik.');
        aiAssert(!str_contains($gateway, 'fallback_provider_id'), 'Gateway liest noch fallback_provider_id.');
        aiAssert(!str_contains($gateway, 'resolved_via'), 'Gateway meldet noch Fallback-Auflösung statt Single-Provider-Modus.');
    },
    'Content und SEO werden nicht durch Translation-Zielsprachen blockiert' => static function () use ($root): void {
        $gateway = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiProviderGateway.php');

        aiAssert(str_contains($gateway, "if (\$capabilityKey === 'translation_enabled')"), 'Provider-Allowed-Locales werden nicht auf Translation-Workflows begrenzt.');
        aiAssert(str_contains($gateway, "Zielsprache ' . strtoupper(\$targetLocale)"), 'Translation-Zielsprache wird nicht mehr explizit validiert.');
    },
    'Adminbereich hat logische AI-Unterseiten' => static function () use ($root): void {
        $aiPage = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'ai-page.php');
        $view = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ai-services.php');

        foreach (['/admin/ai-services', '/admin/ai-translation', '/admin/ai-content-creator', '/admin/ai-seo-creator', '/admin/ai-settings'] as $route) {
            aiAssert(str_contains($aiPage, $route) || str_contains($view, $route), 'AI-Route fehlt: ' . $route);
        }

        foreach (['Dashboard', 'Übersetzung', 'Inhaltsassistent', 'SEO-Assistent', 'Einstellungen'] as $label) {
            aiAssert(str_contains($view, $label), 'AI-Navigationslabel fehlt: ' . $label);
        }

        foreach (['generate_content', 'generate_seo', 'test_provider'] as $action) {
            aiAssert(str_contains($aiPage, $action), 'AI-Generator-Action fehlt im Router: ' . $action);
        }

        aiAssert(str_contains($view, 'test_provider'), 'Provider-Test-Button fehlt in der View.');

        foreach (['Content-Preview generieren', 'SEO-Preview generieren', 'Generierte Content-Preview', 'Generierte SEO-Preview'] as $label) {
            aiAssert(str_contains($view, $label), 'AI-Generator-UI fehlt: ' . $label);
        }
    },
    'Admin-Hinweise nennen Azure AI, Mistral AI, OpenAI, OpenRouter und Ollama' => static function () use ($root): void {
        $view = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ai-services.php');

        foreach (['Azure AI', 'Mistral AI', 'OpenAI', 'OpenRouter', 'Ollama'] as $label) {
            aiAssert(str_contains($view, $label), 'Provider-Hinweis fehlt im Admin: ' . $label);
        }
    },
    'Admin erzwingt Single Provider ohne Add/Delete/Fallback-UI' => static function () use ($root): void {
        $aiPage = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'ai-page.php');
        $module = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'AiServicesModule.php');
        $view = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ai-services.php');

        aiAssert(!str_contains($aiPage, "'add_provider'"), 'Router erlaubt noch add_provider.');
        aiAssert(!str_contains($aiPage, "'delete_provider'"), 'Router erlaubt noch delete_provider.');
        aiAssert(str_contains($module, 'Bitte genau einen AI-Provider konfigurieren.'), 'Modul validiert Single-Provider-Konfiguration nicht.');
        aiAssert(str_contains($module, '$providerId = $this->sanitizeProviderId($providerType);'), 'Provider-ID wird nicht deterministisch aus dem gewählten Typ abgeleitet.');
        aiAssert(str_contains($module, 'AiSettingsService::normalizeProviderModel'), 'Admin-Modul validiert Modelle nicht gegen den Provider-Katalog.');
        aiAssert(str_contains($view, 'Single AI Provider'), 'Single-Provider-UI fehlt.');
        aiAssert(str_contains($view, 'provider_secret_value'), 'Zentrales API-Key-Feld fehlt.');
        aiAssert(str_contains($view, 'aiProviderModelSelect'), 'Providerabhängiges Modell-Dropdown fehlt.');
        aiAssert(str_contains($view, 'data-ai-provider-field="deployment"'), 'Providerabhängige Azure-Deployment-Feldsteuerung fehlt.');
        aiAssert(str_contains($view, 'aiProviderCatalogJson'), 'Provider-Katalog wird nicht an die Admin-UI übergeben.');
        aiAssert(str_contains($view, 'aiActiveProviderIdInput') && str_contains($view, 'aiProviderEntryIdInput'), 'Providerwechsel synchronisiert Hidden-Provider-IDs nicht.');
        aiAssert(str_contains($view, 'activeProviderIdInput.value = providerType') && str_contains($view, 'providerEntryIdInput.value = providerType'), 'Providerwechsel setzt Hidden-IDs nicht auf den gewählten Providertyp.');
        aiAssert(!str_contains($view, 'gpt-4'), 'Admin-View enthält noch GPT-4.x-Hinweise.');
        aiAssert(!str_contains($view, 'Provider-Liste'), 'Alte Provider-Liste ist noch sichtbar.');
        aiAssert(!str_contains($view, 'Expliziter Fallback-Provider'), 'Fallback-UI ist noch sichtbar.');
    },
];

$output = [];
$failures = [];

foreach ($tests as $label => $test) {
    try {
        $test();
        $output[] = "[PASS] {$label}";
    } catch (Throwable $e) {
        $failures[] = "[FAIL] {$label}: {$e->getMessage()}";
        $output[] = end($failures);
    }
}

foreach ($output as $line) {
    echo $line . PHP_EOL;
}

if ($failures !== []) {
    exit(1);
}

echo 'Alle AI-Services-Smoke-Checks erfolgreich.' . PHP_EOL;
