<?php
/**
 * Hooks System - WordPress-like Action/Filter System
 * 
 * Allows plugins to hook into CMS functionality
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Hooks
{
    private static array $actions = [];
    private static array $filters = [];
    
    /**
     * Add action hook
     */
    public static function addAction(string $tag, callable $callback, int $priority = 10): void
    {
        self::$actions[$tag][$priority][] = $callback;
    }
    
    /**
     * Do action hook
     */
    public static function doAction(string $tag, ...$args): void
    {
        if (!isset(self::$actions[$tag])) {
            return;
        }
        
        // Sort by priority
        ksort(self::$actions[$tag]);
        
        foreach (self::$actions[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Prüft, ob für einen Action-Tag bereits Callbacks registriert sind.
     *
     * Wird ein Callback angegeben, erfolgt ein exakter Abgleich auf Prioritätsebene.
     */
    public static function hasAction(string $tag, ?callable $callback = null, ?int $priority = null): bool
    {
        if (!isset(self::$actions[$tag])) {
            return false;
        }

        if ($callback === null) {
            if ($priority === null) {
                return self::$actions[$tag] !== [];
            }

            return !empty(self::$actions[$tag][$priority]);
        }

        if ($priority !== null) {
            if (!isset(self::$actions[$tag][$priority])) {
                return false;
            }

            return in_array($callback, self::$actions[$tag][$priority], true);
        }

        foreach (self::$actions[$tag] as $callbacks) {
            if (in_array($callback, $callbacks, true)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Add filter hook
     */
    public static function addFilter(string $tag, callable $callback, int $priority = 10): void
    {
        self::$filters[$tag][$priority][] = $callback;
    }
    
    /**
     * Apply filters
     */
    public static function applyFilters(string $tag, $value, ...$args): mixed
    {
        if (!isset(self::$filters[$tag])) {
            return $value;
        }
        
        // Sort by priority
        ksort(self::$filters[$tag]);
        
        foreach (self::$filters[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        
        return $value;
    }
    
    /**
     * Remove action
     */
    public static function removeAction(string $tag, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$actions[$tag][$priority])) {
            return false;
        }
        
        $key = array_search($callback, self::$actions[$tag][$priority], true);
        if ($key !== false) {
            unset(self::$actions[$tag][$priority][$key]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove filter
     */
    public static function removeFilter(string $tag, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$filters[$tag][$priority])) {
            return false;
        }
        
        $key = array_search($callback, self::$filters[$tag][$priority], true);
        if ($key !== false) {
            unset(self::$filters[$tag][$priority][$key]);
            return true;
        }
        
        return false;
    }
}
