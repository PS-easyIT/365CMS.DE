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