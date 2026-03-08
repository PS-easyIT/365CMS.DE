<?php
/**
 * Content Localization Service
 *
 * Verwaltet sprachspezifische Content-Varianten für Seiten, Beiträge und Hub-Sites.
 * Die Standardsprache bleibt Deutsch; zusätzliche Sprachvarianten werden suffix-basiert
 * über Felder wie `title_en` bzw. `content_en` gespeichert.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

final class ContentLocalizationService
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * @return string[]
     */
    public function getContentLocales(): array
    {
        $locales = ['en'];

        $filtered = Hooks::applyFilters('cms_content_supported_locales', $locales);
        if (!is_array($filtered)) {
            return $locales;
        }

        $normalized = [];
        foreach ($filtered as $locale) {
            if (!is_string($locale)) {
                continue;
            }

            $locale = $this->normalizeLocale($locale);
            if ($locale === '' || $locale === 'de') {
                continue;
            }

            $normalized[] = $locale;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized !== [] ? $normalized : $locales;
    }

    public function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        return preg_match('/^[a-z]{2}(?:-[a-z]{2})?$/', $locale) === 1 ? $locale : '';
    }

    /**
     * @return array{uri:string,base_uri:string,locale:string,is_localized:bool}
     */
    public function resolveRequestContext(string $uri): array
    {
        $uri = '/' . trim($uri, '/');
        if ($uri === '//') {
            $uri = '/';
        }

        $context = [
            'uri' => $uri,
            'base_uri' => $uri,
            'locale' => 'de',
            'is_localized' => false,
        ];

        foreach ($this->getContentLocales() as $locale) {
            $needle = '/' . $locale;
            if (!str_ends_with($uri, $needle)) {
                continue;
            }

            $baseUri = substr($uri, 0, -strlen($needle));
            if ($baseUri === false || $baseUri === '') {
                $baseUri = '/';
            }

            if ($baseUri === '/' || $baseUri === '') {
                continue;
            }

            $context['base_uri'] = $baseUri;
            $context['locale'] = $locale;
            $context['is_localized'] = true;
            break;
        }

        $filtered = Hooks::applyFilters('cms_content_request_context', $context, $uri);
        return is_array($filtered) ? array_merge($context, $filtered) : $context;
    }

    public function localizePage(array $page, string $locale): array
    {
        return $this->localizeArrayPayload($page, ['title', 'content', 'excerpt', 'meta_title', 'meta_description'], $locale, 'page');
    }

    public function localizePost(array $post, string $locale): array
    {
        return $this->localizeArrayPayload($post, ['title', 'content', 'excerpt', 'meta_title', 'meta_description'], $locale, 'post');
    }

    public function localizeHubSettings(array $settings, string $locale, array $context = []): array
    {
        if ($locale !== 'de') {
            foreach (['hub_badge', 'hub_hero_title', 'hub_hero_text', 'hub_cta_label', 'hub_meta_audience', 'hub_meta_owner', 'hub_meta_update_cycle', 'hub_meta_focus', 'hub_meta_kpi'] as $key) {
                $localizedKey = $key . '_' . $locale;
                if (array_key_exists($localizedKey, $settings) && trim((string)($settings[$localizedKey] ?? '')) !== '') {
                    $settings[$key] = (string)$settings[$localizedKey];
                }
            }
        }

        $settings['content_locale'] = $locale;
        $settings['content_base_locale'] = 'de';

        $settings = Hooks::applyFilters('cms_localized_hub_settings', $settings, $locale, $context);
        $settings = Hooks::applyFilters('cms_localized_content_payload', $settings, 'hub_settings', $locale, $context);

        return is_array($settings) ? $settings : [];
    }

    public function localizeHubCards(array $cards, string $locale, array $context = []): array
    {
        if ($locale !== 'de') {
            foreach ($cards as $index => $card) {
                if (!is_array($card)) {
                    continue;
                }

                foreach (['title', 'summary', 'badge', 'meta', 'meta_left', 'meta_right', 'image_alt', 'button_text'] as $key) {
                    $localizedKey = $key . '_' . $locale;
                    if (array_key_exists($localizedKey, $card) && trim((string)($card[$localizedKey] ?? '')) !== '') {
                        $card[$key] = (string)$card[$localizedKey];
                    }
                }

                $cards[$index] = $card;
            }
        }

        $cards = Hooks::applyFilters('cms_localized_hub_cards', $cards, $locale, $context);
        $cards = Hooks::applyFilters('cms_localized_content_payload', $cards, 'hub_cards', $locale, $context);

        return is_array($cards) ? $cards : [];
    }

    private function localizeArrayPayload(array $payload, array $fields, string $locale, string $type): array
    {
        if ($locale !== 'de') {
            foreach ($fields as $field) {
                $localizedKey = $field . '_' . $locale;
                if (array_key_exists($localizedKey, $payload) && trim((string)($payload[$localizedKey] ?? '')) !== '') {
                    $payload[$field] = (string)$payload[$localizedKey];
                }
            }
        }

        $payload['content_locale'] = $locale;
        $payload['content_base_locale'] = 'de';

        $payload = Hooks::applyFilters('cms_localized_' . $type . '_payload', $payload, $locale);
        $payload = Hooks::applyFilters('cms_localized_content_payload', $payload, $type, $locale);

        return is_array($payload) ? $payload : [];
    }
}
