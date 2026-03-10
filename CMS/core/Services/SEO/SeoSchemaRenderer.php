<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\VendorRegistry;
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

final class SeoSchemaRenderer
{
    public function __construct(private readonly SeoSettingsStore $settings)
    {
    }

    public function generateOrganizationSchema(): string
    {
        return $this->renderSchemaGraph([$this->buildOrganizationThing()]);
    }

    public function generateWebSiteSchema(): string
    {
        $schema = new Thing(
            type: 'WebSite',
            props: [
                'name' => SITE_NAME,
                'url' => SITE_URL,
                'potentialAction' => new Thing(
                    type: 'SearchAction',
                    props: [
                        'target' => SITE_URL . '/search?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ]
                ),
            ]
        );

        return $this->renderSchemaGraph([$schema]);
    }

    public function generateWebPageSchema(string $title, string $description, string $url): string
    {
        return $this->renderSchemaGraph([
            $this->buildWebPageThing([
                'title' => $title,
                'description' => $description,
                'canonical_url' => $url,
                'url' => $url,
            ]),
        ]);
    }

    public function renderSchemaForPayload(array $payload): string
    {
        $schemaType = $this->normalizeSchemaType((string) ($payload['schema_type'] ?? 'WebPage'));
        $things = [];

        if ($schemaType === 'BreadcrumbList') {
            $breadcrumb = $this->buildBreadcrumbThing(
                (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL),
                (string) ($payload['title'] ?? SITE_NAME)
            );
            if ($breadcrumb !== null) {
                $things[] = $breadcrumb;
            }
        } else {
            $primary = $this->buildPrimarySchemaThing($schemaType, $payload);
            if ($primary !== null) {
                $things[] = $primary;
            }

            if ($this->shouldIncludeBreadcrumbSchema()) {
                $breadcrumb = $this->buildBreadcrumbThing(
                    (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL),
                    (string) ($payload['title'] ?? SITE_NAME)
                );
                if ($breadcrumb !== null) {
                    $things[] = $breadcrumb;
                }
            }
        }

        if ($schemaType !== 'Organization' && $this->isOrganizationSchemaEnabled()) {
            $things[] = $this->buildOrganizationThing();
        }

        return $this->renderSchemaGraph($things);
    }

    private function buildPrimarySchemaThing(string $schemaType, array $payload): ?Thing
    {
        return match ($schemaType) {
            'Article', 'BlogPosting', 'NewsArticle' => $this->buildArticleThing($payload, $schemaType),
            'Organization' => $this->buildOrganizationThing(),
            default => $this->buildWebPageThing($payload, $schemaType),
        };
    }

    private function buildWebPageThing(array $payload, string $type = 'WebPage'): Thing
    {
        $props = [
            'url' => $payload['canonical_url'] ?? ($payload['url'] ?? SITE_URL),
            'name' => $payload['title'] ?? SITE_NAME,
            'description' => $payload['description'] ?? '',
            'inLanguage' => 'de-DE',
            'isPartOf' => new Thing(
                type: 'WebSite',
                props: [
                    'name' => SITE_NAME,
                    'url' => SITE_URL,
                ]
            ),
        ];

        if (!empty($payload['og_image'])) {
            $props['primaryImageOfPage'] = new Thing(
                type: 'ImageObject',
                props: [
                    'url' => (string) $payload['og_image'],
                ]
            );
        }

        return new Thing(type: $type, props: $this->filterEmptyProps($props));
    }

    private function buildArticleThing(array $payload, string $type = 'Article'): Thing
    {
        $url = (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL);
        $props = [
            'headline' => $payload['title'] ?? SITE_NAME,
            'name' => $payload['title'] ?? SITE_NAME,
            'description' => $payload['description'] ?? '',
            'url' => $url,
            'dateModified' => $this->normalizeSchemaDate((string) ($payload['updated_at'] ?? date(DATE_W3C))),
            'mainEntityOfPage' => new Thing(
                type: 'WebPage',
                props: [
                    'url' => $url,
                    'name' => $payload['title'] ?? SITE_NAME,
                ]
            ),
            'isPartOf' => new Thing(
                type: 'WebSite',
                props: [
                    'name' => SITE_NAME,
                    'url' => SITE_URL,
                ]
            ),
            'publisher' => $this->buildOrganizationThing(),
        ];

        if (!empty($payload['og_image'])) {
            $props['image'] = [(string) $payload['og_image']];
        }

        return new Thing(type: $type, props: $this->filterEmptyProps($props));
    }

    private function buildOrganizationThing(): Thing
    {
        $name = $this->settings->getSetting('schema_org_name', defined('SITE_NAME') ? SITE_NAME : '365CMS');
        $logo = $this->settings->getSetting('schema_org_logo', SITE_URL . '/assets/images/logo.png');
        $twitter = $this->settings->getSetting('twitter_site', '');
        $sameAs = [];

        if ($twitter !== '') {
            $sameAs[] = 'https://twitter.com/' . ltrim($twitter, '@');
        }

        $props = [
            'name' => $name !== '' ? $name : SITE_NAME,
            'url' => SITE_URL,
            'logo' => $logo,
            'description' => $this->settings->getSetting('meta_description', '365CMS SEO Integration'),
            'email' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'example.com'),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ];

        return new Thing(type: 'Organization', props: $this->filterEmptyProps($props));
    }

    private function buildBreadcrumbThing(string $url, string $title = ''): ?Thing
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn(string $segment): bool => $segment !== ''));

        $items = [
            new Thing(
                type: 'ListItem',
                props: [
                    'position' => 1,
                    'name' => SITE_NAME,
                    'item' => SITE_URL . '/',
                ]
            ),
        ];

        $position = 2;
        $currentPath = '';
        foreach ($segments as $index => $segment) {
            $currentPath .= '/' . $segment;
            $name = $index === array_key_last($segments) && $title !== ''
                ? $title
                : ucwords(str_replace(['-', '_'], ' ', $segment));

            $items[] = new Thing(
                type: 'ListItem',
                props: [
                    'position' => $position++,
                    'name' => $name,
                    'item' => SITE_URL . $currentPath,
                ]
            );
        }

        return new Thing(type: 'BreadcrumbList', props: ['itemListElement' => $items]);
    }

    /**
     * @param array<int, Thing> $things
     */
    private function renderSchemaGraph(array $things): string
    {
        if ($things === []) {
            return '';
        }

        return (string) new Schema(...$things);
    }

    private function normalizeSchemaType(string $schemaType): string
    {
        $schemaType = trim($schemaType);
        if ($schemaType === '') {
            return 'WebPage';
        }

        return match ($schemaType) {
            'BlogPosting', 'NewsArticle', 'Article', 'WebPage', 'BreadcrumbList', 'Organization' => $schemaType,
            default => 'WebPage',
        };
    }

    private function shouldIncludeBreadcrumbSchema(): bool
    {
        return $this->settings->getSetting('schema_breadcrumb_enabled', '1') === '1';
    }

    private function isOrganizationSchemaEnabled(): bool
    {
        return $this->settings->getSetting('schema_organization_enabled', '1') === '1';
    }

    private function normalizeSchemaDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp !== false ? date(DATE_W3C, $timestamp) : date(DATE_W3C);
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function filterEmptyProps(array $props): array
    {
        return array_filter($props, static function (mixed $value): bool {
            if ($value === null) {
                return false;
            }

            if (is_string($value)) {
                return trim($value) !== '';
            }

            if (is_array($value)) {
                return $value !== [];
            }

            return true;
        });
    }
}
