<?php
declare(strict_types=1);

namespace CMS\Services;

use DateTimeZone;
use Poliander\Cron\CronExpression;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for the bundled external cron expression library.
 *
 * Keeps cron scheduling logic isolated, so callers can use a stable
 * CMS-facing API with safe fallbacks if the external class is unavailable.
 */
final class CronExpressionAdapter
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function isLibraryAvailable(): bool
    {
        return class_exists(CronExpression::class);
    }

    public function isValid(string $expression): bool
    {
        $cron = $this->createExpression($expression);

        return $cron?->isValid() ?? false;
    }

    public function isDue(string $expression, int $timestamp): bool
    {
        $cron = $this->createExpression($expression);
        if ($cron === null || !$cron->isValid()) {
            return false;
        }

        try {
            return $cron->isMatching($timestamp);
        } catch (\Throwable) {
            return false;
        }
    }

    public function getNextRunTimestamp(string $expression, ?int $startTimestamp = null): ?int
    {
        $cron = $this->createExpression($expression);
        if ($cron === null || !$cron->isValid()) {
            return null;
        }

        try {
            $next = $cron->getNext($startTimestamp);
        } catch (\Throwable) {
            return null;
        }

        return is_int($next) && $next > 0 ? $next : null;
    }

    private function createExpression(string $expression): ?CronExpression
    {
        $expression = trim($expression);
        if ($expression === '' || !$this->isLibraryAvailable()) {
            return null;
        }

        try {
            $tz = new DateTimeZone(date_default_timezone_get());
            return new CronExpression($expression, $tz);
        } catch (\Throwable) {
            return null;
        }
    }
}
