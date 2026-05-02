<?php
/**
 * Automated Backup Management System
 * Creates and manages database backups
 */

require_once '../core/database_connection.php';
require_once '../core/logger.php';

class BackupManager {
    private static $backupDir = null;
    
    /**
     * Initialize backup system
     */
    public static function init($backupDir = null) {
        self::$backupDir = $backupDir ?? (defined('BACKUP_PATH') ? BACKUP_PATH : __DIR__ . '/backups');
        
        if (!is_dir(self::$backupDir)) {
            @mkdir(self::$backupDir, 0755, true);
        }
    }
    
    /**
     * Create full database backup
     */
    public static function createFullBackup() {
        self::init();
        
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_full_{$timestamp}.sql";
        $filepath = self::$backupDir . '/' . $filename;
        
        try {
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $output = "-- Database Backup\n";
            $output .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: " . (defined('DB_DATABASE') ? DB_DATABASE : 'unknown') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // Drop table
                $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
                
                // Create table
                $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $output .= $row[1] . ";\n\n";
                
                // Insert data
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $values = array_map(function($val) use ($pdo) {
                            return $val === null ? 'NULL' : $pdo->quote($val);
                        }, array_values($row));
                        
                        $output .= "INSERT INTO `{$table}` VALUES(" . implode(', ', $values) . ");\n";
                    }
                    $output .= "\n";
                }
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Save to file
            $result = file_put_contents($filepath, $output);
            
            if ($result !== false) {
                log_info("Database backup created: {$filename}", [
                    'size' => filesize($filepath),
                    'tables' => count($tables)
                ]);
                
                // Compress if possible
                if (function_exists('gzencode')) {
                    $compressed = gzencode($output, 9);
                    file_put_contents($filepath . '.gz', $compressed);
                    @unlink($filepath); // Remove uncompressed version
                    $filepath .= '.gz';
                    $filename .= '.gz';
                }
                
                return [
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath)
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to write backup file'];
            
        } catch (Exception $e) {
            log_error("Backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create table-specific backup
     */
    public static function createTableBackup($tableName) {
        self::init();
        
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$tableName}_{$timestamp}.sql";
        $filepath = self::$backupDir . '/' . $filename;
        
        try {
            $output = "-- Table Backup: {$tableName}\n";
            $output .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Create table
            $stmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $output .= $row[1] . ";\n\n";
            
            // Insert data
            $stmt = $pdo->query("SELECT * FROM `{$tableName}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $values = array_map(function($val) use ($pdo) {
                    return $val === null ? 'NULL' : $pdo->quote($val);
                }, array_values($row));
                
                $output .= "INSERT INTO `{$tableName}` VALUES(" . implode(', ', $values) . ");\n";
            }
            
            file_put_contents($filepath, $output);
            
            log_info("Table backup created: {$tableName}");
            
            return [
                'success' => true,
                'message' => 'Table backup created',
                'filename' => $filename,
                'filepath' => $filepath
            ];
            
        } catch (Exception $e) {
            log_error("Table backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * List all backups
     */
    public static function listBackups() {
        self::init();
        
        $backups = [];
        $files = glob(self::$backupDir . '/*.{sql,gz}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'size_formatted' => self::formatBytes(filesize($file)),
                'created' => filemtime($file),
                'created_formatted' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by newest first
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Delete old backups
     */
    public static function cleanOldBackups($keepDays = 30) {
        self::init();
        
        $cutoff = time() - ($keepDays * 86400);
        $files = glob(self::$backupDir . '/*.{sql,gz}', GLOB_BRACE);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
                log_info("Old backup deleted: " . basename($file));
            }
        }
        
        return $deleted;
    }
    
    /**
     * Restore from backup
     */
    public static function restoreBackup($filename) {
        self::init();
        
        $filepath = self::$backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }
        
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }
        
        try {
            // Read file
            if (substr($filename, -3) === '.gz') {
                $sql = gzdecode(file_get_contents($filepath));
            } else {
                $sql = file_get_contents($filepath);
            }
            
            // Execute SQL
            $pdo->exec($sql);
            
            log_info("Database restored from backup: {$filename}");
            
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
            
        } catch (Exception $e) {
            log_error("Restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Format bytes
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get backup statistics
     */
    public static function getStats() {
        self::init();
        
        $backups = self::listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));
        
        return [
            'total_backups' => count($backups),
            'total_size' => self::formatBytes($totalSize),
            'oldest_backup' => !empty($backups) ? end($backups)['created_formatted'] : 'None',
            'newest_backup' => !empty($backups) ? $backups[0]['created_formatted'] : 'None',
            'backup_dir' => self::$backupDir
        ];
    }
}
?>
