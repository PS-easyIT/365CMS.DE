<?php
declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class VendorRegistry
{
    private static ?self $instance = null;

    /** @var array<string, bool> */
    private array $loadedPackages = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function getDiagnostics(): array
    {
        $autoload = $this->getAutoloadDiagnostics();
        $packages = $this->getPackageDiagnostics();
        $bundles = $this->getBundledLibraryDiagnostics();
        $platform = $this->getBundledPlatformDiagnostics();

        return [
            'autoload' => $autoload,
            'packages' => $packages,
            'bundles' => $bundles,
            'platform' => $platform,
            'summary' => [
                'managed_total' => count($packages),
                'managed_available' => count(array_filter($packages, static fn(array $package): bool => !empty($package['available']))),
                'managed_loaded' => count(array_filter($packages, static fn(array $package): bool => !empty($package['loaded']))),
                'bundle_total' => count($bundles),
                'bundle_available' => count(array_filter($bundles, static fn(array $bundle): bool => !empty($bundle['available']))),
                'bundle_ready' => count(array_filter($bundles, static fn(array $bundle): bool => !empty($bundle['runtime_ready']))),
                'platform_warning_count' => count(array_filter($platform, static fn(array $entry): bool => empty($entry['cms_compatible']) || empty($entry['runtime_compatible']))),
                'autoload_candidate_count' => count($autoload['candidates'] ?? []),
            ],
        ];
    }

    public function loadAssetsAutoloader(): bool
    {
        if (($this->loadedPackages['assets-autoload'] ?? false) === true) {
            return true;
        }

        foreach ($this->getAssetsAutoloadCandidates() as $autoloadPath) {
            if (!is_file($autoloadPath)) {
                continue;
            }

            require_once $autoloadPath;
            $this->loadedPackages['assets-autoload'] = true;
            return true;
        }

        $this->loadedPackages['assets-autoload'] = false;

        return false;
    }

    public function loadPackage(string $package): bool
    {
        if (array_key_exists($package, $this->loadedPackages)) {
            return $this->loadedPackages[$package];
        }

        $loaded = match ($package) {
            'assets-autoload' => $this->loadAssetsAutoloader(),
            'dompdf' => $this->loadDompdf(),
            'melbahja-seo' => $this->loadMelbahjaSeo(),
            'symfony-contracts' => $this->loadAssetsAutoloader(),
            default => false,
        };

        $this->loadedPackages[$package] = $loaded;

        return $loaded;
    }

    private function loadDompdf(): bool
    {
        $autoloadPath = ABSPATH . 'vendor' . DIRECTORY_SEPARATOR . 'dompdf' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (!is_file($autoloadPath)) {
            return false;
        }

        require_once $autoloadPath;

        return class_exists(\Dompdf\Dompdf::class);
    }

    private function loadMelbahjaSeo(): bool
    {
        $this->loadAssetsAutoloader();

        $baseDir = ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'melbahja-seo' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        if (!is_dir($baseDir)) {
            return false;
        }

        foreach ($this->getMelbahjaSeoRequiredFiles() as $relativePath) {
            $absolutePath = $baseDir . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (is_file($absolutePath)) {
                require_once $absolutePath;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getAssetsAutoloadCandidates(): array
    {
        return [
            ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'autoload.php',
            dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'autoload.php',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getAutoloadDiagnostics(): array
    {
        $activePath = null;
        $candidates = [];

        foreach ($this->getAssetsAutoloadCandidates() as $candidate) {
            $exists = is_file($candidate);
            if ($activePath === null && $exists) {
                $activePath = $candidate;
            }

            $candidates[] = [
                'path' => $this->normalizeDisplayedPath($candidate),
                'exists' => $exists,
                'active' => $activePath === $candidate,
            ];
        }

        return [
            'loaded' => defined('CMS_VENDOR_PATH') || (($this->loadedPackages['assets-autoload'] ?? false) === true),
            'active_path' => $activePath !== null ? $this->normalizeDisplayedPath($activePath) : null,
            'candidates' => $candidates,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPackageDiagnostics(): array
    {
        $packages = [];

        foreach ($this->getManagedPackageDefinitions() as $package => $definition) {
            $loadStatus = $this->getPackageLoadStatus($package);
            $packages[] = [
                'package' => $package,
                'label' => $definition['label'],
                'path' => $this->normalizeDisplayedPath($definition['path']),
                'available' => $this->pathExists($definition['path'], $definition['path_type']),
                'loaded' => $loadStatus['loaded'],
                'notes' => $definition['notes'],
                'runtime_error' => $loadStatus['error'],
            ];
        }

        return $packages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getBundledLibraryDiagnostics(): array
    {
        $diagnostics = [];

        foreach ($this->getBundledLibraryDefinitions() as $definition) {
            $available = true;
            foreach ($definition['paths'] as $path) {
                if (!$this->pathExists($path['path'], $path['type'])) {
                    $available = false;
                    break;
                }
            }

            $runtimeStatus = $available
                ? $this->getRuntimeSymbolStatus($definition['symbol'], $definition['symbol_type'])
                : $this->getDefaultRuntimeStatusFallback();

            $diagnostics[] = [
                'package' => $definition['package'],
                'label' => $definition['label'],
                'paths' => array_map(fn(array $path): string => $this->normalizeDisplayedPath($path['path']), $definition['paths']),
                'available' => $available,
                'runtime_ready' => $runtimeStatus['ready'],
                'notes' => $definition['notes'],
                'runtime_error' => $runtimeStatus['error'],
                'runtime_label' => $runtimeStatus['label'],
                'runtime_class' => $runtimeStatus['class'],
            ];
        }

        return $diagnostics;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getBundledPlatformDiagnostics(): array
    {
        $requiredPhpVersion = defined('CMS_MIN_PHP_VERSION') ? (string)CMS_MIN_PHP_VERSION : '8.4.0';
        $diagnostics = [];

        foreach ($this->getBundledPlatformManifestDefinitions() as $packageName => $manifestPath) {
            $platformStatus = $this->getPlatformManifestStatus($manifestPath);
            $bundlePhpVersion = $platformStatus['required_php'];
            $diagnostics[] = [
                'package' => $packageName,
                'manifest' => $this->normalizeDisplayedPath($manifestPath),
                'exists' => is_file($manifestPath),
                'required_php' => $bundlePhpVersion,
                'cms_required_php' => $requiredPhpVersion,
                'runtime_php' => PHP_VERSION,
                'cms_compatible' => $bundlePhpVersion === null ? null : version_compare($requiredPhpVersion, $bundlePhpVersion, '>='),
                'runtime_compatible' => $bundlePhpVersion === null ? null : version_compare(PHP_VERSION, $bundlePhpVersion, '>='),
                'runtime_error' => $platformStatus['error'],
            ];
        }

        return $diagnostics;
    }

    /**
     * @return array<string, array{label: string, path: string, path_type: string, notes: string}>
     */
    private function getManagedPackageDefinitions(): array
    {
        return [
            'assets-autoload' => [
                'label' => 'Zentraler Assets-Autoloader',
                'path' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'autoload.php',
                'path_type' => 'file',
                'notes' => 'Lädt die produktiven Bundles aus CMS/assets/.',
            ],
            'dompdf' => [
                'label' => 'Dompdf PDF-Renderer',
                'path' => ABSPATH . 'vendor' . DIRECTORY_SEPARATOR . 'dompdf' . DIRECTORY_SEPARATOR . 'autoload.php',
                'path_type' => 'file',
                'notes' => 'Sonderpfad für PDF-Rendering außerhalb des Assets-Autoloaders.',
            ],
            'melbahja-seo' => [
                'label' => 'melbahja/seo',
                'path' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'melbahja-seo' . DIRECTORY_SEPARATOR . 'src',
                'path_type' => 'dir',
                'notes' => 'Schema-, Sitemap- und Indexing-Bundle für SEO-Funktionen.',
            ],
            'symfony-contracts' => [
                'label' => 'Symfony Contracts (lokaler Runtime-Shim)',
                'path' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'symfony-contracts',
                'path_type' => 'dir',
                'notes' => 'Lokale Minimal-Contracts für Translation/Mailer, solange kein separates Contracts-Bundle gebündelt ist.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getBundledLibraryDefinitions(): array
    {
        $assets = ABSPATH . 'assets' . DIRECTORY_SEPARATOR;

        return [
            [
                'package' => 'htmlpurifier',
                'label' => 'HTMLPurifier',
                'paths' => [['path' => $assets . 'htmlpurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php', 'type' => 'file']],
                'symbol' => 'HTMLPurifier',
                'symbol_type' => 'class',
                'notes' => 'HTML-Sanitizing für Rich-Content-Pfade.',
            ],
            [
                'package' => 'tntsearch',
                'label' => 'TNTSearch',
                'paths' => [['path' => $assets . 'tntsearchsrc', 'type' => 'dir']],
                'symbol' => '\\TeamTNT\\TNTSearch\\TNTSearch',
                'symbol_type' => 'class',
                'notes' => 'Volltextsuche für SearchService.',
            ],
            [
                'package' => 'carbon',
                'label' => 'Carbon',
                'paths' => [['path' => $assets . 'Carbon', 'type' => 'dir']],
                'symbol' => '\\Carbon\\Carbon',
                'symbol_type' => 'class',
                'notes' => 'Datums-/Zeit-Helfer in Core- und Theme-Pfaden.',
            ],
            [
                'package' => 'symfony/translation',
                'label' => 'Symfony Translation',
                'paths' => [['path' => $assets . 'translation', 'type' => 'dir']],
                'symbol' => '\\Symfony\\Component\\Translation\\Translator',
                'symbol_type' => 'class',
                'notes' => 'I18n-/Übersetzungs-Bundle.',
            ],
            [
                'package' => 'lbuchs/webauthn',
                'label' => 'WebAuthn',
                'paths' => [['path' => $assets . 'webauthn', 'type' => 'dir']],
                'symbol' => '\\lbuchs\\WebAuthn\\WebAuthn',
                'symbol_type' => 'class',
                'notes' => 'Passkey-/FIDO2-Unterstützung.',
            ],
            [
                'package' => 'robthree/twofactorauth',
                'label' => 'TwoFactorAuth',
                'paths' => [['path' => $assets . 'twofactorauth', 'type' => 'dir']],
                'symbol' => '\\RobThree\\Auth\\TwoFactorAuth',
                'symbol_type' => 'class',
                'notes' => 'TOTP-/MFA-Bundle.',
            ],
            [
                'package' => 'ldaprecord',
                'label' => 'LdapRecord',
                'paths' => [['path' => $assets . 'ldaprecord', 'type' => 'dir']],
                'symbol' => '\\LdapRecord\\Connection',
                'symbol_type' => 'class',
                'notes' => 'LDAP-/Verzeichnisintegration.',
            ],
            [
                'package' => 'firebase/php-jwt',
                'label' => 'Firebase JWT',
                'paths' => [['path' => $assets . 'php-jwt', 'type' => 'dir']],
                'symbol' => '\\Firebase\\JWT\\JWT',
                'symbol_type' => 'class',
                'notes' => 'JWT-Unterstützung.',
            ],
            [
                'package' => 'psr/log',
                'label' => 'PSR Log',
                'paths' => [['path' => $assets . 'psr' . DIRECTORY_SEPARATOR . 'Log', 'type' => 'dir']],
                'symbol' => '\\Psr\\Log\\LoggerInterface',
                'symbol_type' => 'interface',
                'notes' => 'PSR-Kompatibilität für Logging.',
            ],
            [
                'package' => 'symfony/mime',
                'label' => 'Symfony Mime',
                'paths' => [['path' => $assets . 'mime', 'type' => 'dir']],
                'symbol' => '\\Symfony\\Component\\Mime\\Email',
                'symbol_type' => 'class',
                'notes' => 'Mime-Komponenten für Mail- und Upload-Pfade.',
            ],
            [
                'package' => 'symfony/mailer',
                'label' => 'Symfony Mailer',
                'paths' => [['path' => $assets . 'mailer', 'type' => 'dir']],
                'symbol' => '\\Symfony\\Component\\Mailer\\Mailer',
                'symbol_type' => 'class',
                'notes' => 'Mail-Transport im Core.',
            ],
            [
                'package' => 'psr/event-dispatcher',
                'label' => 'PSR Event Dispatcher',
                'paths' => [['path' => $assets . 'psr' . DIRECTORY_SEPARATOR . 'EventDispatcher', 'type' => 'dir']],
                'symbol' => '\\Psr\\EventDispatcher\\EventDispatcherInterface',
                'symbol_type' => 'interface',
                'notes' => 'PSR-Event-Dispatcher-Kompatibilität.',
            ],
            [
                'package' => 'editorjs',
                'label' => 'Editor.js',
                'paths' => [['path' => $assets . 'editorjs', 'type' => 'dir']],
                'symbol' => $assets . 'editorjs' . DIRECTORY_SEPARATOR . 'editorjs.umd.js',
                'symbol_type' => 'path',
                'notes' => 'Produktives Block-Editor-Assetset für Admin und Frontend.',
            ],
            [
                'package' => 'gridjs',
                'label' => 'Grid.js',
                'paths' => [['path' => $assets . 'gridjs', 'type' => 'dir']],
                'symbol' => $assets . 'gridjs' . DIRECTORY_SEPARATOR . 'gridjs.umd.js',
                'symbol_type' => 'path',
                'notes' => 'Tabellen- und Grid-Bundle im Admin.',
            ],
            [
                'package' => 'photoswipe',
                'label' => 'PhotoSwipe',
                'paths' => [['path' => $assets . 'photoswipe', 'type' => 'dir']],
                'symbol' => $assets . 'photoswipe' . DIRECTORY_SEPARATOR . 'photoswipe.esm.min.js',
                'symbol_type' => 'path',
                'notes' => 'Lightbox-/Galerie-Assets für Frontend-Medienansichten.',
            ],
            [
                'package' => 'suneditor',
                'label' => 'SunEditor',
                'paths' => [['path' => $assets . 'suneditor', 'type' => 'dir']],
                'symbol' => $assets . 'suneditor',
                'symbol_type' => 'path',
                'notes' => 'Legacy-WYSIWYG-Editor im Admin.',
            ],
            [
                'package' => 'tabler',
                'label' => 'Tabler',
                'paths' => [['path' => $assets . 'tabler', 'type' => 'dir']],
                'symbol' => $assets . 'tabler',
                'symbol_type' => 'path',
                'notes' => 'Primäres Admin-UI-Framework.',
            ],
            [
                'package' => 'cms-css',
                'label' => '365CMS CSS-Assets',
                'paths' => [['path' => $assets . 'css', 'type' => 'dir']],
                'symbol' => $assets . 'css',
                'symbol_type' => 'path',
                'notes' => 'Produktive Stylesheets für Admin, Frontend und Member-Bereich.',
            ],
            [
                'package' => 'cms-js',
                'label' => '365CMS JS-Assets',
                'paths' => [['path' => $assets . 'js', 'type' => 'dir']],
                'symbol' => $assets . 'js',
                'symbol_type' => 'path',
                'notes' => 'Produktive JavaScript-Helfer und Initializer.',
            ],
            [
                'package' => 'cms-images',
                'label' => '365CMS Bild-Assets',
                'paths' => [['path' => $assets . 'images', 'type' => 'dir']],
                'symbol' => $assets . 'images',
                'symbol_type' => 'path',
                'notes' => 'Produktive Dashboard-, Logo- und Branding-Bestände.',
            ],
            [
                'package' => 'simplepie',
                'label' => 'SimplePie (Legacy)',
                'paths' => [['path' => $assets . 'simplepielibrary', 'type' => 'dir']],
                'symbol' => $assets . 'simplepielibrary',
                'symbol_type' => 'legacy',
                'notes' => 'Dokumentierter Legacy-Bestand; aktuell kein aktives Runtime-Bundle.',
            ],
            [
                'package' => 'symfony/ai-platform',
                'label' => 'Symfony AI Platform (Staging)',
                'paths' => [['path' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'ai-platform-0.6.0', 'type' => 'dir']],
                'symbol' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'ai-platform-0.6.0',
                'symbol_type' => 'staging',
                'notes' => 'Außerhalb der produktiven Runtime dokumentierter Kandidat; aktuell noch nicht im Core verdrahtet.',
            ],
            [
                'package' => 'symfony/cache',
                'label' => 'Symfony Cache (Staging)',
                'paths' => [['path' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'cache-8.0.8', 'type' => 'dir']],
                'symbol' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'cache-8.0.8',
                'symbol_type' => 'staging',
                'notes' => 'Dokumentierter Kandidat außerhalb der aktiven Runtime.',
            ],
            [
                'package' => 'msgraph-sdk-php',
                'label' => 'MS Graph SDK (Referenz)',
                'paths' => [['path' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'msgraph-sdk-php-2.56.0', 'type' => 'dir']],
                'symbol' => dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'ASSETS' . DIRECTORY_SEPARATOR . 'msgraph-sdk-php-2.56.0',
                'symbol_type' => 'reference',
                'notes' => 'Referenzablage außerhalb der aktiven produktiven Verdrahtung.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getBundledPlatformManifestDefinitions(): array
    {
        return [
            'symfony/mailer' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'mailer' . DIRECTORY_SEPARATOR . 'composer.json',
            'symfony/mime' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'mime' . DIRECTORY_SEPARATOR . 'composer.json',
            'symfony/translation' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'translation' . DIRECTORY_SEPARATOR . 'composer.json',
            'nesbot/carbon' => ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'Carbon' . DIRECTORY_SEPARATOR . 'composer.json',
        ];
    }

    private function isPackageLoaded(string $package): bool
    {
        return match ($package) {
            'assets-autoload' => defined('CMS_VENDOR_PATH') || (($this->loadedPackages['assets-autoload'] ?? false) === true),
            'dompdf' => (($this->loadedPackages['dompdf'] ?? false) === true) || class_exists(\Dompdf\Dompdf::class, false),
            'melbahja-seo' => (($this->loadedPackages['melbahja-seo'] ?? false) === true) || class_exists(\Melbahja\Seo\Schema::class, true),
            'symfony-contracts' => is_dir(ABSPATH . 'assets' . DIRECTORY_SEPARATOR . 'symfony-contracts')
                && interface_exists(\Symfony\Contracts\Translation\TranslatorInterface::class, true),
            default => false,
        };
    }

    /**
     * @return array{loaded: bool, error: ?string}
     */
    private function getPackageLoadStatus(string $package): array
    {
        try {
            return [
                'loaded' => $this->isPackageLoaded($package),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'loaded' => false,
                'error' => $this->formatRuntimeError($e),
            ];
        }
    }

    private function isRuntimeSymbolReady(string $symbol, string $type): bool
    {
        return match ($type) {
            'interface' => interface_exists($symbol, true),
            'path' => file_exists($symbol),
            'legacy', 'staging', 'reference' => false,
            default => class_exists($symbol, true),
        };
    }

    /**
     * @return array{ready: bool, error: ?string, label: string, class: string}
     */
    private function getRuntimeSymbolStatus(string $symbol, string $type): array
    {
        try {
            if ($type === 'staging') {
                return [
                    'ready' => false,
                    'error' => null,
                    'label' => 'nur Staging',
                    'class' => 'secondary',
                ];
            }

            if ($type === 'reference') {
                return [
                    'ready' => false,
                    'error' => null,
                    'label' => 'Referenz',
                    'class' => 'secondary',
                ];
            }

            if ($type === 'legacy') {
                return [
                    'ready' => false,
                    'error' => null,
                    'label' => 'Legacy',
                    'class' => 'warning',
                ];
            }

            if ($type === 'path') {
                $ready = $this->isRuntimeSymbolReady($symbol, $type);

                return [
                    'ready' => $ready,
                    'error' => null,
                    'label' => $ready ? 'verfügbar' : 'fehlt',
                    'class' => $ready ? 'primary' : 'secondary',
                ];
            }

            $ready = $this->isRuntimeSymbolReady($symbol, $type);

            return [
                'ready' => $ready,
                'error' => null,
                'label' => $ready ? 'auflösbar' : 'nicht aufgelöst',
                'class' => $ready ? 'success' : 'secondary',
            ];
        } catch (\Throwable $e) {
            return [
                'ready' => false,
                'error' => $this->formatRuntimeError($e),
                'label' => 'Fehler',
                'class' => 'warning',
            ];
        }
    }

    /**
     * @return array{ready: bool, error: ?string, label: string, class: string}
     */
    private function getDefaultRuntimeStatusFallback(): array
    {
        return [
            'ready' => false,
            'error' => null,
            'label' => 'nicht aufgelöst',
            'class' => 'secondary',
        ];
    }

    private function pathExists(string $path, string $type): bool
    {
        return match ($type) {
            'dir' => is_dir($path),
            default => is_file($path),
        };
    }

    /**
     * @return array{required_php: ?string, error: ?string}
     */
    private function getPlatformManifestStatus(string $manifestPath): array
    {
        try {
            return [
                'required_php' => $this->extractMinimumPhpVersion($manifestPath),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'required_php' => null,
                'error' => $this->formatRuntimeError($e),
            ];
        }
    }

    private function normalizeDisplayedPath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalizedAbs = str_replace('\\', '/', ABSPATH);
        $normalizedRepo = str_replace('\\', '/', dirname(ABSPATH) . DIRECTORY_SEPARATOR);

        if (str_starts_with($normalized, $normalizedAbs)) {
            return 'CMS/' . ltrim(substr($normalized, strlen($normalizedAbs)), '/');
        }

        if (str_starts_with($normalized, $normalizedRepo)) {
            return ltrim(substr($normalized, strlen($normalizedRepo)), '/');
        }

        return $normalized;
    }

    private function extractMinimumPhpVersion(string $manifestPath): ?string
    {
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            return null;
        }

        $raw = file_get_contents($manifestPath);
        if ($raw === false) {
            return null;
        }

        $manifest = Json::decodeArray($raw, []);
        $phpConstraint = is_array($manifest) ? ($manifest['require']['php'] ?? null) : null;

        if (!is_string($phpConstraint) || trim($phpConstraint) === '') {
            return null;
        }

        if (preg_match('/>=\s*([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        if (preg_match('/\^\s*([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        return null;
    }

    private function normalizeVersion(string $version): string
    {
        $parts = explode('.', $version);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        return implode('.', array_slice($parts, 0, 3));
    }

    private function formatRuntimeError(\Throwable $e): string
    {
        return $e->getMessage() . ' @ ' . $this->normalizeDisplayedPath($e->getFile()) . ':' . $e->getLine();
    }

    /**
     * @return string[]
     */
    private function getMelbahjaSeoRequiredFiles(): array
    {
        return [
            'Interfaces/SeoInterface.php',
            'Interfaces/SchemaInterface.php',
            'Interfaces/SitemapInterface.php',
            'Interfaces/SitemapBuilderInterface.php',
            'Interfaces/SitemapSetupableInterface.php',
            'Exceptions/SeoException.php',
            'Exceptions/SitemapException.php',
            'Utils/Utils.php',
            'Utils/HttpClient.php',
            'Schema/Thing.php',
            'Schema.php',
            'Sitemap/OutputMode.php',
            'Sitemap/SitemapUrl.php',
            'Sitemap/IndexBuilder.php',
            'Sitemap/LinksBuilder.php',
            'Sitemap/NewsBuilder.php',
            'Sitemap.php',
            'Indexing/IndexNowEngine.php',
            'Indexing/URLIndexingType.php',
            'Indexing/IndexNowIndexer.php',
            'Indexing/GoogleIndexer.php',
        ];
    }
}