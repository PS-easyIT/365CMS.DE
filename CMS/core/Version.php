<?php
declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class Version
{
    public const CURRENT = '2.8.0';
    public const RELEASE_DATE = '2026-03-28';
    public const STATUS = 'stable';

    public static function current(): string
    {
        return self::CURRENT;
    }

    public static function releaseDate(): string
    {
        return self::RELEASE_DATE;
    }
}