<?php
declare(strict_types=1);

namespace CMS;

/**
 * Debug-System für Admin-Bereiche
 * Zeigt detaillierte Fehler und Ablauf-Informationen
 */
class Debug {
    
    private static bool $enabled = false; // enable via Debug::enable(CMS_DEBUG)
    private static array $logs = [];
    private static float $start_time;
    
    /**
     * Debug-Modus aktivieren/deaktivieren
     */
    public static function enable(bool $enable = true): void {
        self::$enabled = $enable;
    }
    
    /**
     * Ist Debug-Modus aktiv?
     */
    public static function isEnabled(): bool {
        return self::$enabled;
    }
    
    /**
     * Timer starten
     */
    public static function startTimer(): void {
        self::$start_time = microtime(true);
    }
    
    /**
     * Verstrichene Zeit seit Timer-Start
     */
    public static function getElapsedTime(): float {
        if (!isset(self::$start_time)) {
            self::startTimer();
            return 0.0;
        }
        return microtime(true) - self::$start_time;
    }
    
    /**
     * Debug-Log hinzufügen
     */
    public static function log(string $message, string $type = 'info', mixed $data = null): void {
        if (!self::$enabled) {
            return;
        }
        
        $entry = [
            'time' => microtime(true),
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'memory' => memory_get_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        self::$logs[] = $entry;
        
        // Auch ins Error-Log schreiben
        $log_message = sprintf(
            '[CMS Debug %s] %s',
            strtoupper($type),
            $message
        );
        
        if ($data !== null) {
            $log_message .= ' | Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
    
    /**
     * Erfolg loggen
     */
    public static function success(string $message, mixed $data = null): void {
        self::log($message, 'success', $data);
    }
    
    /**
     * Warnung loggen
     */
    public static function warning(string $message, mixed $data = null): void {
        self::log($message, 'warning', $data);
    }
    
    /**
     * Fehler loggen
     */
    public static function error(string $message, mixed $data = null): void {
        self::log($message, 'error', $data);
    }
    
    /**
     * Exception loggen
     */
    public static function exception(\Throwable $e, string $context = ''): void {
        $message = $context ? "$context: {$e->getMessage()}" : $e->getMessage();
        
        self::log($message, 'exception', [
            'class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    /**
     * Alle Logs abrufen
     */
    public static function getLogs(): array {
        return self::$logs;
    }
    
    /**
     * Logs für JSON-Response vorbereiten
     */
    public static function getLogsForJson(): array {
        return array_map(function($entry) {
            return [
                'time' => round($entry['time'] - (self::$start_time ?? $entry['time']), 4),
                'type' => $entry['type'],
                'message' => $entry['message'],
                'data' => $entry['data'],
                'memory_mb' => round($entry['memory'] / 1024 / 1024, 2)
            ];
        }, self::$logs);
    }
    
    /**
     * Debug-Info als HTML ausgeben
     */
    public static function renderHtml(bool $collapsed = true): string {
        if (!self::$enabled || empty(self::$logs)) {
            return '';
        }
        
        $html = '<div class="debug-panel" style="' . ($collapsed ? 'max-height: 50px; overflow: hidden;' : '') . '">';
        $html .= '<div class="debug-header" onclick="this.parentElement.style.maxHeight=\'none\'; this.parentElement.style.overflow=\'visible\';" style="background: #1e293b; color: white; padding: 0.75rem; cursor: pointer; font-weight: 600;">';
        $html .= '<i class="fas fa-bug"></i> Debug-Informationen (' . count(self::$logs) . ' Einträge) - Klick zum Ausklappen';
        $html .= '</div>';
        $html .= '<div class="debug-content" style="background: #f8fafc; border: 2px solid #1e293b; padding: 1rem; font-family: monospace; font-size: 0.875rem;">';
        
        foreach (self::$logs as $entry) {
            $color = match($entry['type']) {
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'exception' => '#dc2626',
                default => '#64748b'
            };
            
            $html .= '<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: white; border-left: 4px solid ' . $color . ';">';
            $html .= '<strong style="color: ' . $color . ';">[' . strtoupper($entry['type']) . ']</strong> ';
            $html .= htmlspecialchars($entry['message']);
            
            if ($entry['data'] !== null) {
                $html .= '<pre style="margin: 0.5rem 0 0 0; padding: 0.5rem; background: #f1f5f9; overflow-x: auto;">';
                $html .= htmlspecialchars(print_r($entry['data'], true));
                $html .= '</pre>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * AJAX-Response mit Debug-Infos erweitern
     */
    public static function enhanceAjaxResponse(array $response): array {
        if (!self::$enabled) {
            return $response;
        }
        
        $response['_debug'] = [
            'enabled' => true,
            'logs' => self::getLogsForJson(),
            'elapsed_time' => round(self::getElapsedTime(), 4),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $response;
    }
    
    /**
     * Datenbank-Query loggen
     */
    public static function query(string $sql, ?array $params = null, float $execution_time = 0): void {
        self::log('SQL Query', 'info', [
            'sql' => $sql,
            'params' => $params,
            'execution_time_ms' => round($execution_time * 1000, 2)
        ]);
    }
    
    /**
     * Service-Initialisierung loggen
     */
    public static function serviceInit(string $service_name): void {
        self::success("Service initialisiert: $service_name");
    }
    
    /**
     * Service-Methoden-Aufruf loggen
     */
    public static function serviceCall(string $service_name, string $method, ?array $params = null): void {
        self::log("Service-Call: $service_name::$method", 'info', $params);
    }
    
    /**
     * Logs löschen
     */
    public static function clear(): void {
        self::$logs = [];
    }
}
