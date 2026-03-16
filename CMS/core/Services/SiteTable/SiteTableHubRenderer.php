<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

use CMS\Database;
use CMS\Hooks;
use CMS\Services\ContentLocalizationService;
use CMS\Services\PurifierService;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableHubRenderer
{
    public static function isHubRequestUri(string $requestUri): bool
    {
        $path = self::normalizeRequestPath($requestUri);

        if ($path === '/' || self::isExcludedRequestPath($path) || self::isPostRequestPath($path)) {
            return false;
        }

        $slug = trim($path, '/');
        if ($slug === '' || str_contains($slug, '/')) {
            return false;
        }

        try {
            $db = Database::instance();
            $contentType = $db->get_var(
                "SELECT content_type FROM {$db->prefix()}pages WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );

            return (string) $contentType === 'hub';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function __construct(private SiteTableTemplateRegistry $templateRegistry)
    {
    }

    private static function normalizeRequestPath(string $requestUri): string
    {
        $path = (string) (strtok($requestUri !== '' ? $requestUri : '/', '?') ?: '/');

        try {
            $context = ContentLocalizationService::getInstance()->resolveRequestContext($path);
            $baseUri = trim((string) ($context['base_uri'] ?? ''));

            return $baseUri !== '' ? $baseUri : $path;
        } catch (\Throwable $e) {
            return $path;
        }
    }

    private static function isExcludedRequestPath(string $path): bool
    {
        return in_array($path, ['/login', '/register', '/search', '/404', '/error', '/blog'], true)
            || str_starts_with($path, '/member')
            || str_starts_with($path, '/dashboard')
            || str_starts_with($path, '/kategorie/')
            || str_starts_with($path, '/tag/')
            || str_starts_with($path, '/author/');
    }

    private static function isPostRequestPath(string $path): bool
    {
        return preg_match('#^/blog/[^/]+$#', $path) === 1;
    }

    public function buildHubPage(array $table, string $slug, string $locale = 'de'): array
    {
        $settings = array_merge($this->templateRegistry->getDefaultSettings(), $table['settings'] ?? []);
        $localizedSettings = ContentLocalizationService::getInstance()->localizeHubSettings($settings, $locale, [
            'slug' => $slug,
            'table' => $table,
        ]);

        return [
            'id' => (int) ($table['id'] ?? 0),
            'title' => trim((string) ($localizedSettings['hub_hero_title'] ?? '')) !== ''
                ? (string) $localizedSettings['hub_hero_title']
                : (string) ($table['name'] ?? 'Hub Site'),
            'slug' => $slug,
            'content_type' => 'hub',
            'content_locale' => $locale,
            'content' => $this->renderHubMarkup($table, $locale),
            'meta_description' => trim((string) ($localizedSettings['hub_hero_text'] ?? '')) !== ''
                ? trim((string) $localizedSettings['hub_hero_text'])
                : (string) ($table['description'] ?? ''),
            'updated_at' => (string) ($table['updated_at'] ?? ''),
        ];
    }

    public function renderHubMarkup(array $table, string $locale = 'de'): string
    {
        $settings = array_merge($this->templateRegistry->getDefaultSettings(), $table['settings'] ?? []);
        $settings = ContentLocalizationService::getInstance()->localizeHubSettings($settings, $locale, ['table' => $table]);
        $templateKey = (string) ($settings['hub_template'] ?? 'general-it');
        $templateProfile = $this->templateRegistry->getTemplateProfile($templateKey);
        $templateProfile = Hooks::applyFilters('cms_hub_template_profile', $templateProfile, $templateKey, $locale, $table);
        if (!is_array($templateProfile)) {
            $templateProfile = $this->templateRegistry->getTemplateProfile($templateKey);
        }

        $template = (string) ($templateProfile['base_template'] ?? $templateKey ?: 'general-it');
        $pageSlug = trim((string) ($settings['hub_slug'] ?? ''));
        $heroTitle = trim((string) ($settings['hub_hero_title'] ?? '')) ?: (string) ($table['name'] ?? 'Hub Site');
        $heroText = trim((string) ($settings['hub_hero_text'] ?? '')) ?: trim((string) ($table['description'] ?? ''));
        $heroBadge = trim((string) ($settings['hub_badge'] ?? ''));
        $ctaLabel = trim((string) ($settings['hub_cta_label'] ?? ''));
        $ctaUrl = trim((string) ($settings['hub_cta_url'] ?? ''));
        $cards = ContentLocalizationService::getInstance()->localizeHubCards($this->normalizeHubCards($table['rows'] ?? []), $locale, ['table' => $table]);
        $quickLinks = $this->templateRegistry->resolveHubLinks($settings, $templateProfile, $template, $locale);
        $sections = $this->templateRegistry->resolveHubSections($settings, $templateProfile, $template, $locale);
        $metaSettings = array_merge([
            'hub_meta_audience' => (string) ($templateProfile['meta']['audience'] ?? ''),
            'hub_meta_owner' => (string) ($templateProfile['meta']['owner'] ?? ''),
            'hub_meta_update_cycle' => (string) ($templateProfile['meta']['update_cycle'] ?? ''),
            'hub_meta_focus' => (string) ($templateProfile['meta']['focus'] ?? ''),
            'hub_meta_kpi' => (string) ($templateProfile['meta']['kpi'] ?? ''),
        ], $settings);
        foreach (['hub_meta_audience', 'hub_meta_owner', 'hub_meta_update_cycle', 'hub_meta_focus', 'hub_meta_kpi'] as $metaKey) {
            if (trim((string) ($metaSettings[$metaKey] ?? '')) === '') {
                $metaSettings[$metaKey] = (string) ($templateProfile['meta'][str_replace('hub_meta_', '', $metaKey)] ?? $templateProfile['meta'][match ($metaKey) {
                    'hub_meta_update_cycle' => 'update_cycle',
                    default => str_replace('hub_meta_', '', $metaKey),
                }] ?? '');
            }
        }
        $metaItems = $this->templateRegistry->buildHubMetaItems($metaSettings, $template, $templateProfile, $locale);
        $cardDesign = $this->templateRegistry->resolveHubCardDesign($settings, $templateProfile, $template);
        $cardSchema = is_array($templateProfile['card_schema'] ?? null) ? $templateProfile['card_schema'] : [];
        $colorSettings = is_array($templateProfile['colors'] ?? null) ? $templateProfile['colors'] : [];
        $styleVariables = $this->templateRegistry->buildHubStyleVariables($colorSettings);
        $contentLanguage = $this->templateRegistry->getTemplateContentLanguage($template, $locale);

        $html = '<section class="cms-hub-site cms-hub-site--' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '"';
        if ($pageSlug !== '') {
            $html .= ' data-hub-slug="' . htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($styleVariables !== '') {
            $html .= ' style="' . htmlspecialchars($styleVariables, ENT_QUOTES, 'UTF-8') . '"';
        }
        $html .= '>';
        $html .= $this->renderHero($heroBadge, $heroTitle, $heroText, $metaItems, $ctaLabel, $ctaUrl);
        $html .= $this->renderQuickLinks($quickLinks, $contentLanguage);
        $html .= $this->renderSections($sections, $template, $contentLanguage);
        $html .= $this->renderCards($cards, $cardDesign, $cardSchema, (int) ($table['id'] ?? 0));
        $html .= '</section>';

        return $html;
    }

    private function renderHero(string $heroBadge, string $heroTitle, string $heroText, array $metaItems, string $ctaLabel, string $ctaUrl): string
    {
        $html = '<div class="cms-hub-site__hero"><div class="cms-hub-site__hero-inner">';
        if ($heroBadge !== '') {
            $html .= '<span class="cms-hub-site__badge">' . htmlspecialchars($heroBadge, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '<h2 class="cms-hub-site__title">' . htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') . '</h2>';
        if ($heroText !== '') {
            $html .= '<p class="cms-hub-site__lead">' . nl2br(htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        if ($metaItems !== []) {
            $html .= '<div class="cms-hub-site__meta">';
            foreach ($metaItems as $metaItem) {
                $html .= '<span class="cms-hub-site__meta-chip cms-hub-site__meta-chip--' . htmlspecialchars((string) ($metaItem['key'] ?? 'meta'), ENT_QUOTES, 'UTF-8') . '">';
                if (!empty($metaItem['icon'])) {
                    $html .= '<span class="cms-hub-site__meta-chip-icon" aria-hidden="true">' . htmlspecialchars((string) $metaItem['icon'], ENT_QUOTES, 'UTF-8') . '</span>';
                }
                $html .= '<span class="cms-hub-site__meta-chip-label">' . htmlspecialchars((string) $metaItem['label'], ENT_QUOTES, 'UTF-8') . ':</span>';
                $html .= '<span class="cms-hub-site__meta-chip-value">' . htmlspecialchars((string) $metaItem['value'], ENT_QUOTES, 'UTF-8') . '</span>';
                $html .= '</span>';
            }
            $html .= '</div>';
        }
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $html .= '<a class="cms-hub-site__cta" href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        return $html . '</div></div>';
    }

    private function renderQuickLinks(array $quickLinks, array $contentLanguage): string
    {
        if ($quickLinks === []) {
            return '';
        }

        $html = '<nav class="cms-hub-site__quicklinks" aria-label="Hub-Navigation">';
        foreach ($quickLinks as $index => $link) {
            $icon = $contentLanguage['quicklink_icons'][$index] ?? '•';
            $html .= '<a class="cms-hub-site__quicklink" href="' . htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<span class="cms-hub-site__quicklink-icon" aria-hidden="true">' . htmlspecialchars((string) $icon, ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '<span class="cms-hub-site__quicklink-label">' . htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '</a>';
        }

        return $html . '</nav>';
    }

    private function renderSections(array $sections, string $template, array $contentLanguage): string
    {
        if ($sections === []) {
            return '';
        }

        $html = '<div class="cms-hub-site__sections cms-hub-site__sections--' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($sections as $index => $section) {
            $sectionModifier = $contentLanguage['section_modifiers'][$index] ?? 'default';
            $sectionEyebrow = $contentLanguage['section_eyebrows'][$index] ?? 'Section';
            $sectionIcon = $contentLanguage['section_icons'][$index] ?? '◆';
            $sectionNote = $contentLanguage['section_notes'][$index] ?? '';

            $html .= '<article class="cms-hub-site__section-card cms-hub-site__section-card--' . htmlspecialchars($sectionModifier, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<div class="cms-hub-site__section-head">';
            $html .= '<span class="cms-hub-site__section-eyebrow">' . htmlspecialchars((string) $sectionEyebrow, ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '<span class="cms-hub-site__section-icon" aria-hidden="true">' . htmlspecialchars((string) $sectionIcon, ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '</div>';
            $html .= '<h3 class="cms-hub-site__section-title">' . htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8') . '</h3>';
            if ((string) ($section['text'] ?? '') !== '') {
                $html .= '<p class="cms-hub-site__section-text">' . nl2br(htmlspecialchars((string) $section['text'], ENT_QUOTES, 'UTF-8')) . '</p>';
            }
            if ((string) ($section['actionLabel'] ?? '') !== '' && (string) ($section['actionUrl'] ?? '') !== '') {
                $html .= '<a class="cms-hub-site__section-link" href="' . htmlspecialchars((string) $section['actionUrl'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $section['actionLabel'], ENT_QUOTES, 'UTF-8') . '</a>';
            }
            if ($sectionNote !== '') {
                $html .= '<div class="cms-hub-site__section-note">' . htmlspecialchars((string) $sectionNote, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $html .= '</article>';
        }

        return $html . '</div>';
    }

    private function renderCards(array $cards, array $cardDesign, array $cardSchema, int $currentTableId): string
    {
        if ($cards === []) {
            return '';
        }

        $cardLayout = $this->normalizeOption((string) ($cardDesign['layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard');
        $cardImagePosition = $this->normalizeOption((string) ($cardDesign['image_position'] ?? 'top'), ['top', 'left', 'right'], 'top');
        $cardImageFit = $this->normalizeOption((string) ($cardDesign['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover');
        $cardImageRatio = $this->normalizeOption((string) ($cardDesign['image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide');
        $cardMetaLayout = $this->normalizeOption((string) ($cardDesign['meta_layout'] ?? 'split'), ['split', 'stacked'], 'split');
        $cardColumns = max(1, min(3, (int) ($cardSchema['columns'] ?? 2)));

        $html = '<div class="cms-hub-site__grid cms-hub-site__grid--' . htmlspecialchars($cardLayout, ENT_QUOTES, 'UTF-8') . ' cms-hub-site__grid--cols-' . $cardColumns . '">';
        foreach ($cards as $card) {
            $url = htmlspecialchars((string) ($card['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars((string) ($card['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $summary = $this->renderCardSummary((string) ($card['summary'] ?? ''), $currentTableId);
            $badge = htmlspecialchars((string) ($card['badge'] ?? ''), ENT_QUOTES, 'UTF-8');
            $meta = htmlspecialchars((string) ($card['meta'] ?? ''), ENT_QUOTES, 'UTF-8');
            $metaLeft = htmlspecialchars((string) ($card['meta_left'] ?? ''), ENT_QUOTES, 'UTF-8');
            $metaRight = htmlspecialchars((string) ($card['meta_right'] ?? ''), ENT_QUOTES, 'UTF-8');
            $buttonText = htmlspecialchars((string) ($card['button_text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $buttonLink = trim((string) ($card['button_link'] ?? ''));
            $imageUrl = trim((string) ($card['image_url'] ?? ''));
            $imageAlt = htmlspecialchars((string) ($card['image_alt'] ?? $card['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $hasImage = $imageUrl !== '';
            $cardArticleClass = 'cms-hub-site__card';
            $cardLinkClass = 'cms-hub-site__card-link';

            if ($hasImage) {
                $cardArticleClass .= ' cms-hub-site__card--image-' . htmlspecialchars($cardImagePosition, ENT_QUOTES, 'UTF-8');
                $cardLinkClass .= ' cms-hub-site__card-link--image-' . htmlspecialchars($cardImagePosition, ENT_QUOTES, 'UTF-8');
            }
            $cardArticleClass .= ' cms-hub-site__card--meta-' . htmlspecialchars($cardMetaLayout, ENT_QUOTES, 'UTF-8');

            $html .= '<article class="' . $cardArticleClass . '">';
            $html .= '<div class="' . $cardLinkClass . '">';
            if ($hasImage) {
                if ($url !== '#') {
                    $html .= '<a class="cms-hub-site__card-media-link" href="' . $url . '">';
                }
                $html .= '<div class="cms-hub-site__card-media cms-hub-site__card-media--' . htmlspecialchars($cardImageRatio, ENT_QUOTES, 'UTF-8') . ' cms-hub-site__card-media--fit-' . htmlspecialchars($cardImageFit, ENT_QUOTES, 'UTF-8') . '">';
                $html .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $imageAlt . '" loading="lazy">';
                $html .= '</div>';
                if ($url !== '#') {
                    $html .= '</a>';
                }
            }
            $html .= '<div class="cms-hub-site__card-content">';
            if ($badge !== '') {
                $html .= '<span class="cms-hub-site__card-badge">' . $badge . '</span>';
            }
            $html .= '<h3 class="cms-hub-site__card-title">';
            if ($url !== '#') {
                $html .= '<a class="cms-hub-site__card-title-link" href="' . $url . '">' . $title . '</a>';
            } else {
                $html .= $title;
            }
            $html .= '</h3>';
            if ($summary !== '') {
                $html .= '<div class="cms-hub-site__card-summary">' . $summary . '</div>';
            }
            $html .= '<div class="cms-hub-site__card-footer cms-hub-site__card-footer--' . htmlspecialchars($cardMetaLayout, ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<div class="cms-hub-site__card-meta-row">';
            if ($metaLeft !== '') {
                $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--left">' . $metaLeft . '</span>';
            } elseif ($meta !== '') {
                $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--left">' . $meta . '</span>';
            }
            if ($metaRight !== '') {
                $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--right">' . $metaRight . '</span>';
            }
            $html .= '</div>';
            if ($url !== '#') {
                $html .= '<span class="cms-hub-site__card-arrow" aria-hidden="true">→</span>';
            }
            $html .= '</div>';
            if ($buttonText !== '') {
                $buttonHref = $buttonLink !== '' ? htmlspecialchars($buttonLink, ENT_QUOTES, 'UTF-8') : $url;
                if ($buttonHref !== '#') {
                    $html .= '<a class="cms-hub-site__card-button" href="' . $buttonHref . '">' . $buttonText . '</a>';
                } else {
                    $html .= '<span class="cms-hub-site__card-button">' . $buttonText . '</span>';
                }
            }
            $html .= '</div></div></article>';
        }

        return $html . '</div>';
    }

    private function renderCardSummary(string $value, int $currentTableId): string
    {
        if ($value === '') {
            return '';
        }

        $placeholders = [];
        $prepared = (string) preg_replace_callback(
            '/\[(?:site-table|table|hub-site)\s+id\s*=\s*["\']?(\d+)["\']?\s*\/?\]/i',
            static function (array $matches) use (&$placeholders, $currentTableId): string {
                $targetTableId = (int) ($matches[1] ?? 0);
                if ($targetTableId <= 0 || $targetTableId === $currentTableId) {
                    return '';
                }

                $token = '%%CMS_HUB_CARD_CONTENT_' . count($placeholders) . '%%';
                $placeholders[$token] = \CMS\Services\SiteTableService::getInstance()->renderTableById($targetTableId);

                return $token;
            },
            $value
        );

        $containsHtml = $this->containsHtml($prepared);
        $html = $containsHtml
            ? $this->sanitizeRichHtml($prepared)
            : nl2br(htmlspecialchars($prepared, ENT_QUOTES, 'UTF-8'));

        foreach ($placeholders as $token => $replacement) {
            $html = str_replace(
                $containsHtml ? $token : htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
                $replacement,
                $html
            );
        }

        return trim($html);
    }

    private function normalizeHubCards(array $rows): array
    {
        $cards = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? $row['Titel'] ?? ''));
            $url = trim((string) ($row['url'] ?? $row['URL'] ?? '#'));
            if ($title === '') {
                continue;
            }

            $cards[] = [
                'title' => mb_substr($title, 0, 160),
                'title_en' => mb_substr(trim((string) ($row['title_en'] ?? $row['titleEn'] ?? '')), 0, 160),
                'url' => $url !== '' ? $url : '#',
                'summary' => mb_substr(trim((string) ($row['summary'] ?? $row['Beschreibung'] ?? '')), 0, 600),
                'summary_en' => mb_substr(trim((string) ($row['summary_en'] ?? $row['summaryEn'] ?? '')), 0, 600),
                'badge' => mb_substr(trim((string) ($row['badge'] ?? $row['Kategorie'] ?? '')), 0, 80),
                'badge_en' => mb_substr(trim((string) ($row['badge_en'] ?? $row['badgeEn'] ?? '')), 0, 80),
                'meta' => mb_substr(trim((string) ($row['meta'] ?? $row['Meta'] ?? '')), 0, 120),
                'meta_en' => mb_substr(trim((string) ($row['meta_en'] ?? $row['metaEn'] ?? '')), 0, 120),
                'meta_left' => mb_substr(trim((string) ($row['meta_left'] ?? $row['metaLeft'] ?? $row['Meta links'] ?? $row['meta'] ?? '')), 0, 120),
                'meta_left_en' => mb_substr(trim((string) ($row['meta_left_en'] ?? $row['metaLeftEn'] ?? '')), 0, 120),
                'meta_right' => mb_substr(trim((string) ($row['meta_right'] ?? $row['metaRight'] ?? $row['Meta rechts'] ?? '')), 0, 120),
                'meta_right_en' => mb_substr(trim((string) ($row['meta_right_en'] ?? $row['metaRightEn'] ?? '')), 0, 120),
                'image_url' => mb_substr(trim((string) ($row['image_url'] ?? $row['imageUrl'] ?? $row['Bild'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim((string) ($row['image_alt'] ?? $row['imageAlt'] ?? '')), 0, 160),
                'image_alt_en' => mb_substr(trim((string) ($row['image_alt_en'] ?? $row['imageAltEn'] ?? '')), 0, 160),
                'button_text' => mb_substr(trim((string) ($row['button_text'] ?? $row['buttonText'] ?? $row['Button-Text'] ?? '')), 0, 80),
                'button_text_en' => mb_substr(trim((string) ($row['button_text_en'] ?? $row['buttonTextEn'] ?? '')), 0, 80),
                'button_link' => mb_substr(trim((string) ($row['button_link'] ?? $row['buttonLink'] ?? $row['Button-Link'] ?? '')), 0, 500),
            ];
        }

        return $cards;
    }

    private function normalizeOption(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function containsHtml(string $value): bool
    {
        return preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $value) === 1;
    }

    private function sanitizeRichHtml(string $value): string
    {
        return PurifierService::getInstance()->purify($value, 'table');
    }
}
