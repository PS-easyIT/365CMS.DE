<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translation Helper: __()
 */
function __(string $text, string $domain = 'default'): string {
    return \CMS\Services\TranslationService::getInstance()->translate($text, $domain);
}

/**
 * Translation Helper: _e()
 */
function _e(string $text, string $domain = 'default'): void {
    echo __($text, $domain);
}

/**
 * Translation Helper: _x()
 */
function _x(string $text, string $context, string $domain = 'default'): string {
    return __($text, $domain);
}

/**
 * Translation Helper: _n()
 */
function _n(string $single, string $plural, int $number, string $domain = 'default'): string {
    return \CMS\Services\TranslationService::getInstance()->translatePlural($single, $plural, $number, $domain);
}

/**
 * Translation Helper: _ex()
 */
function _ex(string $text, string $context, string $domain = 'default'): void {
    echo _x($text, $context, $domain);
}
