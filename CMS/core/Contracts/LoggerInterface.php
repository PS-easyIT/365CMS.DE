<?php
/**
 * LoggerInterface – PSR-3-kompatibler Contract für die Logger-Abstraktionsschicht
 *
 * Orientiert sich an PSR-3 (https://www.php-fig.org/psr/psr-3/), ohne die
 * Composer-Abhängigkeit zu erzwingen. Ermöglicht Mocking in Tests und
 * alternative Log-Backends (Datei, Datenbank, externen Dienst).
 *
 * @package CMSv2\Core\Contracts
 */

declare(strict_types=1);

namespace CMS\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

interface LoggerInterface
{
    /**
     * System ist nicht verwendbar.
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Sofortiger Handlungsbedarf (z. B. gesamte Datenbank nicht verfügbar).
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Kritische Bedingungen (z. B. unerwartete Exception).
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Laufzeitfehler, die keine sofortige Aktion erfordern, aber protokolliert werden müssen.
     */
    public function error(string $message, array $context = []): void;

    /**
     * Außergewöhnliche Ereignisse, die keine Fehler sind (z. B. veraltete API-Verwendung).
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Normale, aber signifikante Ereignisse.
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Interessante Ereignisse (z. B. User-Login, SQL-Logs).
     */
    public function info(string $message, array $context = []): void;

    /**
     * Detaillierte Debug-Informationen.
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Protokolliert eine Meldung mit beliebigem Level.
     *
     * @param string $level   Eines der PSR-3-Levels (emergency|alert|critical|error|warning|notice|info|debug)
     * @param string $message Log-Nachricht (unterstützt {placeholder}-Interpolation aus $context)
     * @param array  $context Schlüssel-Wert-Paare für Interpolation und strukturierte Metadaten
     */
    public function log(string $level, string $message, array $context = []): void;
}
