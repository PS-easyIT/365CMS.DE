<?php
declare(strict_types=1);

namespace CMS\Services\AI\Providers;

use CMS\Services\AI\AiProviderInterface;

if (!defined('ABSPATH')) {
    exit;
}

final class MockAiProvider implements AiProviderInterface
{
    private string $providerId;
    private string $label;
    private string $defaultModel;

    public function __construct(string $providerId = 'mock', string $label = 'Mock Provider', string $defaultModel = 'mock-local-v1')
    {
        $this->providerId = trim($providerId) !== '' ? trim($providerId) : 'mock';
        $this->label = trim($label) !== '' ? trim($label) : 'Mock Provider';
        $this->defaultModel = trim($defaultModel) !== '' ? trim($defaultModel) : 'mock-local-v1';
    }

    public function getSlug(): string
    {
        return $this->providerId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isMock(): bool
    {
        return true;
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * @param list<string> $segments
     * @param array<string, mixed> $context
     * @return list<string>
     */
    public function translateBatch(array $segments, array $context = []): array
    {
        $translated = [];

        foreach ($segments as $segment) {
            $translated[] = $this->translateString((string) $segment, $context);
        }

        return $translated;
    }

    /** @param array<string, mixed> $context */
    private function translateString(string $text, array $context): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $text;
        }

        $targetLocale = strtoupper((string) ($context['target_locale'] ?? 'EN'));
        $prefix = '[' . $targetLocale . ' MOCK] ';

        $translated = str_ireplace(
            [
                'übersetzung',
                'zusammenfassung',
                'einleitung',
                'abschnitt',
                'beispiel',
                'hinweis',
                'beitrag',
                'seite',
                'inhalt',
                'kontakt',
                'deutsch',
                'englisch',
                'mit',
                'und',
                'oder',
                'für',
                'ohne',
                'über',
            ],
            [
                'translation',
                'summary',
                'introduction',
                'section',
                'example',
                'note',
                'post',
                'page',
                'content',
                'contact',
                'German',
                'English',
                'with',
                'and',
                'or',
                'for',
                'without',
                'about',
            ],
            $trimmed
        );

        if (preg_match('/^\[[A-Z0-9 _-]+\]\s/u', $translated) === 1) {
            return $translated;
        }

        if (preg_match('/^(\s*(?:<[^>]+>\s*)*)(.*)$/su', $translated, $matches) === 1) {
            $leadingMarkup = (string) ($matches[1] ?? '');
            $content = (string) ($matches[2] ?? '');

            return $leadingMarkup . $prefix . $content;
        }

        return $prefix . $translated;
    }
}