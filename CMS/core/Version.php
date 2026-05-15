<?php
declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class Version
{
    public const CURRENT = '3.0.3';
    public const RELEASE_DATE = '2026-05-15';
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