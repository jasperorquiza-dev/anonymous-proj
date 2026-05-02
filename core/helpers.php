<?php
function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit;
}
function redirect_back($fallback = '/') {
    $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
    redirect($referer);
}
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return "{$protocol}://{$host}{$uri}";
}
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = ltrim($path, '/');
    return "{$protocol}://{$host}/{$path}";
}
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function json_success($message = 'Success', $data = null, $statusCode = 200) {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    json_response($response, $statusCode);
}
function json_error($message = 'Error', $errors = null, $statusCode = 400) {
    $response = ['status' => 'error', 'message' => $message];
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    json_response($response, $statusCode);
}
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function e_nl2br($text) {
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}
function input($key, $default = null) {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}
function all_inputs() {
    return array_merge($_GET, $_POST);
}
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    return $ip;
}
function get_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
function time_ago($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}
function random_string($length = 16) {
    return bin2hex(random_bytes($length / 2));
}
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}
function ends_with($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}
function contains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}
function array_only($array, $keys) {
    return array_intersect_key($array, array_flip($keys));
}
function array_except($array, $keys) {
    return array_diff_key($array, array_flip($keys));
}
function dd(...$vars) {
    echo '<pre style="background: #1a1a1a; color: #00ff00; padding: 20px; margin: 10px; border-radius: 5px; font-family: monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n\n";
    }
    echo '</pre>';
    die();
}
function dump(...$vars) {
    echo '<pre style="background: #1a1a1a; color: #00ff00; padding: 20px; margin: 10px; border-radius: 5px; font-family: monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n\n";
    }
    echo '</pre>';
}
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}
function is_empty($value) {
    if ($value === 0 || $value === '0') {
        return false;
    }
    return empty($value);
}
function config($key, $default = null) {
    static $config = null;
    if ($config === null) {
        $config = [];
        if (file_exists(__DIR__ . '/config.php')) {
            require __DIR__ . '/config.php';
            $constants = get_defined_constants(true)['user'] ?? [];
            foreach ($constants as $name => $value) {
                if (starts_with($name, 'APP_') || starts_with($name, 'DB_')) {
                    $config[$name] = $value;
                }
            }
        }
    }
    return $config[$key] ?? $default;
}
function memory_usage() {
    return format_bytes(memory_get_usage(true));
}
function peak_memory_usage() {
    return format_bytes(memory_get_peak_usage(true));
}
function execution_time() {
    static $start_time = null;
    if ($start_time === null) {
        $start_time = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }
    return round((microtime(true) - $start_time) * 1000, 2) . ' ms';
}
?>