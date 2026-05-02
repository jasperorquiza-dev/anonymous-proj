<?php
class PhilippinesTime {
    const TIMEZONE = 'Asia/Manila';
    public static function now() {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }
    public static function getCurrentTime($format = 'Y-m-d H:i:s') {
        return self::now()->format($format);
    }
    public static function getHumanReadableTime() {
        $now = self::now();
        return $now->format('F j, Y g:i A');
    }
    public static function getTimeForJS() {
        $now = self::now();
        return [
            'timestamp' => $now->getTimestamp(),
            'formatted' => $now->format('Y-m-d H:i:s'),
            'human' => $now->format('F j, Y g:i A'),
            'timezone' => self::TIMEZONE,
            'offset' => '+08:00',
            'utc_offset' => 8 * 3600,
            'iso' => $now->format('c')
        ];
    }
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
    public static function isNewDay() {
        $now = self::now();
        return $now->format('H:i:s') === '00:00:00';
    }
    public static function getCurrentDay() {
        return self::now()->format('l');
    }
    public static function getCurrentDate() {
        return self::now()->format('Y-m-d');
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'get_time') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    echo json_encode(PhilippinesTime::getTimeForJS());
    exit;
}
?>