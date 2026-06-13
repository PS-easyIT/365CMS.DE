<?php
declare(strict_types=1);

/**
 * Zentrales Manifest für modulnahe 365CMS-Test-Suites.
 *
 * Neue Suites sollten hier registriert werden, damit lokale Runs und CI denselben
 * reproduzierbaren Entry-Point nutzen.
 *
 * @return array<string, array{script:string,description:string,required?:bool}>
 */
return [
    'release-smoke' => [
        'script' => __DIR__ . DIRECTORY_SEPARATOR . 'release-smoke' . DIRECTORY_SEPARATOR . 'run.php',
        'description' => 'Release-Smoke-Prüfungen für Dokumentation, CI-Hinweise und Basisartefakte.',
        'required' => true,
    ],
    'pdf-service' => [
        'script' => __DIR__ . DIRECTORY_SEPARATOR . 'pdf-service' . DIRECTORY_SEPARATOR . 'run.php',
        'description' => 'PDF-Service-/Dompdf-Verfügbarkeitsprüfung mit Runtime-SKIPs.',
        'required' => true,
    ],
    'sitemap-service' => [
        'script' => __DIR__ . DIRECTORY_SEPARATOR . 'sitemap-service' . DIRECTORY_SEPARATOR . 'run.php',
        'description' => 'SitemapService-Smoke-Test für Index, Teil-Sitemaps und kanonische Felder.',
        'required' => true,
    ],
    'dependency-governance' => [
        'script' => __DIR__ . DIRECTORY_SEPARATOR . 'dependency-governance' . DIRECTORY_SEPARATOR . 'run.php',
        'description' => 'Prüft Dependency-/Vendor-Inventar, Manifest-Dateien und dokumentierte Vendor-Roots.',
        'required' => true,
    ],
    'security-baseline' => [
        'script' => __DIR__ . DIRECTORY_SEPARATOR . 'security-baseline' . DIRECTORY_SEPARATOR . 'run.php',
        'description' => 'Security-Baseline für Upload-/Import-Härtung, SQL-Identifier, Cron-Token und Request-Migration.',
        'required' => true,
    ],
];
