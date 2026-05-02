<?php
class Logger {
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    private static $logLevels = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];
    private static $logDir = null;
    private static $minLevel = self::LEVEL_INFO;
    public static function init($logDir = null, $minLevel = self::LEVEL_INFO) {
        self::$logDir = $logDir ?? (defined('LOGS_PATH') ? LOGS_PATH : __DIR__ . '/logs');
        self::$minLevel = $minLevel;
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
    }
    private static function log($level, $message, $context = [], $category = 'general') {
        if ($level < self::$minLevel) {
            return;
        }
        self::init();
        $timestamp = date('Y-m-d H:i:s');
        $levelName = self::$logLevels[$level] ?? 'UNKNOWN';
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$levelName} ({$category}): {$message}{$contextStr}\n";
        $logFile = self::$logDir . "/{$category}.log";
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        if ($level >= self::LEVEL_ERROR) {
            $mainLog = self::$logDir . "/error.log";
            @file_put_contents($mainLog, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
    public static function debug($message, $context = [], $category = 'general') {
        self::log(self::LEVEL_DEBUG, $message, $context, $category);
    }
    public static function info($message, $context = [], $category = 'general') {
        self::log(self::LEVEL_INFO, $message, $context, $category);
    }
    public static function warning($message, $context = [], $category = 'general') {
        self::log(self::LEVEL_WARNING, $message, $context, $category);
    }
    public static function error($message, $context = [], $category = 'general') {
        self::log(self::LEVEL_ERROR, $message, $context, $category);
    }
    public static function critical($message, $context = [], $category = 'general') {
        self::log(self::LEVEL_CRITICAL, $message, $context, $category);
    }
    public static function security($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context, 'security');
    }
    public static function auth($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, 'auth');
    }
    public static function database($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, 'database');
    }
    public static function admin($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, 'admin');
    }
    public static function activity($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, 'activity');
    }
    public static function api($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context, 'api');
    }
    public static function getRecentLogs($category = 'general', $lines = 100) {
        self::init();
        $logFile = self::$logDir . "/{$category}.log";
        if (!file_exists($logFile)) {
            return [];
        }
        $logs = [];
        $handle = @fopen($logFile, 'r');
        if ($handle) {
            $buffer = 4096;
            fseek($handle, -1, SEEK_END);
            if (fread($handle, 1) != "\n") {
                $logs[] = fgets($handle);
            }
            while (ftell($handle) > 0 && count($logs) < $lines) {
                $seek = min(ftell($handle), $buffer);
                fseek($handle, -$seek, SEEK_CUR);
                $chunk = fread($handle, $seek);
                fseek($handle, -$seek, SEEK_CUR);
                $logs = array_merge(explode("\n", $chunk), $logs);
                if (count($logs) >= $lines) {
                    break;
                }
            }
            fclose($handle);
        }
        return array_slice(array_filter($logs), -$lines);
    }
    public static function cleanOldLogs($days = 30) {
        self::init();
        $cutoff = time() - ($days * 24 * 60 * 60);
        $files = glob(self::$logDir . '/*.log');
        $cleaned = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $cleaned++;
            }
        }
        return $cleaned;
    }
    public static function getStats($category = 'general', $hours = 24) {
        self::init();
        $logFile = self::$logDir . "/{$category}.log";
        if (!file_exists($logFile)) {
            return [
                'total' => 0,
                'by_level' => [],
                'recent_errors' => []
            ];
        }
        $stats = [
            'total' => 0,
            'by_level' => [
                'DEBUG' => 0,
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0,
                'CRITICAL' => 0
            ],
            'recent_errors' => []
        ];
        $cutoff = time() - ($hours * 3600);
        $lines = self::getRecentLogs($category, 1000);
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $stats['total']++;
            foreach ($stats['by_level'] as $level => $count) {
                if (strpos($line, $level) !== false) {
                    $stats['by_level'][$level]++;
                    if (in_array($level, ['ERROR', 'CRITICAL'])) {
                        $stats['recent_errors'][] = $line;
                    }
                    break;
                }
            }
        }
        $stats['recent_errors'] = array_slice($stats['recent_errors'], -10);
        return $stats;
    }
}
function log_debug($message, $context = [], $category = 'general') {
    Logger::debug($message, $context, $category);
}
function log_info($message, $context = [], $category = 'general') {
    Logger::info($message, $context, $category);
}
function log_warning($message, $context = [], $category = 'general') {
    Logger::warning($message, $context, $category);
}
function log_error($message, $context = [], $category = 'general') {
    Logger::error($message, $context, $category);
}
function log_critical($message, $context = [], $category = 'general') {
    Logger::critical($message, $context, $category);
}
function log_security($message, $context = []) {
    Logger::security($message, $context);
}
function log_auth($message, $context = []) {
    Logger::auth($message, $context);
}
function log_admin($message, $context = []) {
    Logger::admin($message, $context);
}
?>