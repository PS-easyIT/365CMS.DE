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
    'OpenAI-kompatibler Live-Adapter ist autoloadbar' => static function (): void {
        aiAssert(class_exists(OpenAiCompatibleProvider::class), 'OpenAiCompatibleProvider ist nicht autoloadbar.');
    },
    'Gateway verdrahtet Mistral/OpenAI/OpenRouter und Azure AI' => static function () use ($root): void {
        $gateway = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AI' . DIRECTORY_SEPARATOR . 'AiProviderGateway.php');

        aiAssert(str_contains($gateway, 'OpenAiCompatibleProvider'), 'OpenAiCompatibleProvider wird im Gateway nicht referenziert.');
        aiAssert(str_contains($gateway, "'openai', 'mistral', 'openrouter'"), 'OpenAI/Mistral/OpenRouter-Match fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'AzureOpenAiProvider'), 'AzureOpenAiProvider fehlt im Gateway.');
        aiAssert(str_contains($gateway, 'defaultModelForOpenAiCompatibleProvider'), 'Default-Modell-Auflösung für kompatible Provider fehlt.');
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
    },
    'Admin-Hinweise nennen Azure AI, Mistral AI, OpenAI, OpenRouter und Ollama' => static function () use ($root): void {
        $view = aiReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ai-services.php');

        foreach (['Azure AI', 'Mistral AI', 'OpenAI', 'OpenRouter', 'Ollama'] as $label) {
            aiAssert(str_contains($view, $label), 'Provider-Hinweis fehlt im Admin: ' . $label);
        }
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
