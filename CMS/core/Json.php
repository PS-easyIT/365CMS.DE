<?php
declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class Json
{
    private function __construct()
    {
    }

    public static function decode(
        mixed $json,
        bool $associative = true,
        mixed $fallback = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        if ($json === null) {
            return $fallback;
        }

        if (is_string($json)) {
            $payload = trim($json);
        } elseif (is_scalar($json)) {
            $payload = trim((string) $json);
        } else {
            return $fallback;
        }

        if ($payload === '') {
            return $fallback;
        }

        try {
            return json_decode($payload, $associative, $depth, $flags | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $fallback;
        }
    }

    /**
     * @return array<mixed>
     */
    public static function decodeArray(mixed $json, array $fallback = [], int $depth = 512, int $flags = 0): array
    {
        $decoded = self::decode($json, true, $fallback, $depth, $flags);

        return is_array($decoded) ? $decoded : $fallback;
    }

    public static function decodeObject(mixed $json, ?object $fallback = null, int $depth = 512, int $flags = 0): object
    {
        $fallback ??= (object) [];
        $decoded = self::decode($json, false, $fallback, $depth, $flags);

        return is_object($decoded) ? $decoded : $fallback;
    }
}