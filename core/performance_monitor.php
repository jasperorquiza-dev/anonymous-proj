<?php
class PerformanceMonitor {
    private static $startTime = null;
    private static $checkpoints = [];
    private static $queries = [];
    private static $memoryStart = null;
    public static function start() {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage(true);
        self::$checkpoints = [];
        self::$queries = [];
    }
    public static function checkpoint($name) {
        if (self::$startTime === null) {
            self::start();
        }
        self::$checkpoints[] = [
            'name' => $name,
            'time' => microtime(true) - self::$startTime,
            'memory' => memory_get_usage(true) - self::$memoryStart
        ];
    }
    public static function logQuery($query, $duration) {
        self::$queries[] = [
            'query' => $query,
            'duration' => $duration,
            'time' => microtime(true)
        ];
    }
    public static function getExecutionTime() {
        if (self::$startTime === null) {
            return 0;
        }
        return microtime(true) - self::$startTime;
    }
    public static function getMemoryUsage() {
        if (self::$memoryStart === null) {
            return memory_get_usage(true);
        }
        return memory_get_usage(true) - self::$memoryStart;
    }
    public static function getPeakMemoryUsage() {
        return memory_get_peak_usage(true);
    }
    public static function getStats() {
        return [
            'execution_time' => round(self::getExecutionTime() * 1000, 2) . ' ms',
            'memory_usage' => self::formatBytes(self::getMemoryUsage()),
            'peak_memory' => self::formatBytes(self::getPeakMemoryUsage()),
            'total_queries' => count(self::$queries),
            'checkpoints' => self::$checkpoints,
            'queries' => self::$queries,
            'slow_queries' => self::getSlowQueries()
        ];
    }
    private static function getSlowQueries($threshold = 0.1) {
        return array_filter(self::$queries, function($query) use ($threshold) {
            return $query['duration'] > $threshold;
        });
    }
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    public static function report($return = false) {
        $stats = self::getStats();
        $output = "\n<!-- Performance Report -->\n";
        $output .= "<!-- Execution Time: {$stats['execution_time']} -->\n";
        $output .= "<!-- Memory Usage: {$stats['memory_usage']} -->\n";
        $output .= "<!-- Peak Memory: {$stats['peak_memory']} -->\n";
        $output .= "<!-- Total Queries: {$stats['total_queries']} -->\n";
        if (!empty($stats['slow_queries'])) {
            $output .= "<!-- Slow Queries: " . count($stats['slow_queries']) . " -->\n";
        }
        if (!empty(self::$checkpoints)) {
            $output .= "<!-- Checkpoints:\n";
            foreach (self::$checkpoints as $cp) {
                $output .= "     {$cp['name']}: " . round($cp['time'] * 1000, 2) . "ms\n";
            }
            $output .= "-->\n";
        }
        if ($return) {
            return $output;
        }
        echo $output;
    }
    public static function saveLog($category = 'performance') {
        require_once '../core/logger.php';
        $stats = self::getStats();
        Logger::info('Performance metrics', $stats, $category);
    }
}
if (defined('APP_ENV') && APP_ENV === 'development') {
    PerformanceMonitor::start();
    register_shutdown_function(function() {
        if (defined('APP_ENV') && APP_ENV === 'development') {
            PerformanceMonitor::report();
        }
    });
}
?>