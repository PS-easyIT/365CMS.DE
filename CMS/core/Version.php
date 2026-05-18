<?php
declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class Version
{
    public const CURRENT = '3.0.12';
    public const RELEASE_DATE = '2026-05-18';
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