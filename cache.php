<?php
/**
 * Simple File-Based Caching System
 * Improves performance by caching expensive operations
 */

class Cache {
    private static $cacheDir = null;
    private static $enabled = true;
    private static $defaultTTL = 3600; // 1 hour
    
    /**
     * Initialize cache
     */
    public static function init($cacheDir = null) {
        self::$cacheDir = $cacheDir ?? __DIR__ . '/cache';
        
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    /**
     * Enable caching
     */
    public static function enable() {
        self::$enabled = true;
    }
    
    /**
     * Disable caching
     */
    public static function disable() {
        self::$enabled = false;
    }
    
    /**
     * Get cache file path
     */
    private static function getCachePath($key) {
        self::init();
        $hash = md5($key);
        return self::$cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Store data in cache
     */
    public static function set($key, $value, $ttl = null) {
        if (!self::$enabled) {
            return false;
        }
        
        $ttl = $ttl ?? self::$defaultTTL;
        $cachePath = self::getCachePath($key);
        
        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return @file_put_contents($cachePath, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Get data from cache
     */
    public static function get($key, $default = null) {
        if (!self::$enabled) {
            return $default;
        }
        
        $cachePath = self::getCachePath($key);
        
        if (!file_exists($cachePath)) {
            return $default;
        }
        
        $data = @unserialize(file_get_contents($cachePath));
        
        if ($data === false || !is_array($data)) {
            return $default;
        }
        
        // Check if expired
        if ($data['expires'] < time()) {
            @unlink($cachePath);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Check if key exists and is not expired
     */
    public static function has($key) {
        if (!self::$enabled) {
            return false;
        }
        
        $cachePath = self::getCachePath($key);
        
        if (!file_exists($cachePath)) {
            return false;
        }
        
        $data = @unserialize(file_get_contents($cachePath));
        
        if ($data === false || !is_array($data)) {
            return false;
        }
        
        return $data['expires'] >= time();
    }
    
    /**
     * Delete cache entry
     */
    public static function delete($key) {
        $cachePath = self::getCachePath($key);
        
        if (file_exists($cachePath)) {
            return @unlink($cachePath);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public static function clear() {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Clear expired cache entries
     */
    public static function clearExpired() {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = @unserialize(file_get_contents($file));
            
            if ($data !== false && is_array($data) && $data['expires'] < time()) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Remember: Get from cache or execute callback and cache result
     */
    public static function remember($key, $callback, $ttl = null) {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = @unserialize(file_get_contents($file));
            
            if ($data !== false && is_array($data)) {
                if ($data['expires'] >= time()) {
                    $validCount++;
                } else {
                    $expiredCount++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_entries' => $validCount,
            'expired_entries' => $expiredCount,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'cache_dir' => self::$cacheDir
        ];
    }
}

// Global helper functions
function cache_set($key, $value, $ttl = null) {
    return Cache::set($key, $value, $ttl);
}

function cache_get($key, $default = null) {
    return Cache::get($key, $default);
}

function cache_has($key) {
    return Cache::has($key);
}

function cache_delete($key) {
    return Cache::delete($key);
}

function cache_remember($key, $callback, $ttl = null) {
    return Cache::remember($key, $callback, $ttl);
}
?>
