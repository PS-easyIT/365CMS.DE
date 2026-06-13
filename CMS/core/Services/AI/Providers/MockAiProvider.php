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
    public function generateText(string $systemPrompt, string $userPrompt, array $context = []): string
    {
        $task = strtolower(trim((string) ($context['task'] ?? 'content')));
        $locale = strtolower(trim((string) ($context['locale'] ?? 'de')));

        if ($task === 'seo_meta') {
            return (string) json_encode([
                'meta_title' => '[MOCK] Prägnanter SEO-Titel für den geprüften Entwurf',
                'meta_description' => '[MOCK] Kurze, nutzerorientierte Meta Description mit klarem Nutzenversprechen und redaktioneller Prüffreigabe.',
                'social_title' => '[MOCK] Social Snippet Titel',
                'social_description' => '[MOCK] Social Description für Vorschau und manuelle Kontrolle.',
                'keywords' => ['mock', 'cms', 'seo'],
                'schema_hints' => ['Article', 'WebPage'],
                'warnings' => ['Mock-Ausgabe: Bitte echte Provider-Konfiguration für produktive Vorschläge nutzen.'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) json_encode([
            'title' => '[MOCK] Content-Vorschlag',
            'summary' => '[MOCK] Kompakte Zusammenfassung für redaktionelle Prüfung.',
            'draft' => '[MOCK] Ausformulierter Vorschlag auf Basis des Briefings. Diese Ausgabe ist bewusst als Preview markiert und wird nicht automatisch veröffentlicht.',
            'variants' => ['[MOCK] Variante A', '[MOCK] Variante B'],
            'rationale' => 'Mock-Ausgabe für ' . strtoupper($locale) . ': Provider-Flow, Prompting und Admin-Vorschau sind funktional verdrahtet.',
            'warnings' => ['Mock-Ausgabe: keine produktive KI-Antwort.'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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