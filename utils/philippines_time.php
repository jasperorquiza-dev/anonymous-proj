<?php
/**
 * Philippines Time Utility Functions
 * Handles real-time date and time for Philippines Standard Time (GMT+8)
 */

class PhilippinesTime {
    const TIMEZONE = 'Asia/Manila';
    
    /**
     * Get current Philippines time
     * @return DateTime
     */
    public static function now() {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }
    
    /**
     * Get current Philippines time as formatted string
     * @param string $format Date format (default: 'Y-m-d H:i:s')
     * @return string
     */
    public static function getCurrentTime($format = 'Y-m-d H:i:s') {
        return self::now()->format($format);
    }
    
    /**
     * Get current Philippines time in human readable format
     * @return string
     */
    public static function getHumanReadableTime() {
        $now = self::now();
        return $now->format('F j, Y g:i A');
    }
    
    /**
     * Get current Philippines time for JavaScript
     * @return array
     */
    public static function getTimeForJS() {
        $now = self::now();
        return [
            'timestamp' => $now->getTimestamp(),
            'formatted' => $now->format('Y-m-d H:i:s'),
            'human' => $now->format('F j, Y g:i A'),
            'timezone' => self::TIMEZONE,
            'offset' => '+08:00',
            'utc_offset' => 8 * 3600, // 8 hours in seconds
            'iso' => $now->format('c')
        ];
    }
    
    /**
     * Get time difference from now
     * @param DateTime $pastTime
     * @return string
     */
    public static function getTimeAgo($pastTime) {
        $now = self::now();
        $diff = $now->diff($pastTime);
        
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
    
    /**
     * Check if it's a new day (for live discussions)
     * @return bool
     */
    public static function isNewDay() {
        $now = self::now();
        return $now->format('H:i:s') === '00:00:00';
    }
    
    /**
     * Get current day of week in Philippines
     * @return string
     */
    public static function getCurrentDay() {
        return self::now()->format('l');
    }
    
    /**
     * Get current date in Philippines
     * @return string
     */
    public static function getCurrentDate() {
        return self::now()->format('Y-m-d');
    }
}

// API endpoint for real-time updates
if (isset($_GET['action']) && $_GET['action'] === 'get_time') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    echo json_encode(PhilippinesTime::getTimeForJS());
    exit;
}
?>
