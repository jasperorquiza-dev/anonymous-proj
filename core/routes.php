<?php
function getMessagesSlug(): string {
    static $slug = null;
    if ($slug !== null) return $slug;
    $salt = 'icct_forum_route_salt_v1';
    $slug = substr(hash('sha256', $salt . 'messages'), 0, 20);
    return $slug;
}
function getBasePath(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $dir === '/' ? '' : $dir;
}
function getMessagesUrl(): string {
    return getBasePath() . '/' . getMessagesSlug();
}
function handleFrontControllerRouting(): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $basePath = getBasePath();
    $messagesPath = $basePath . '/' . getMessagesSlug();
    $normalizedPath = rtrim($path, '/');
    if ($normalizedPath === '') {
        $normalizedPath = '/';
    }
    if ($path === $messagesPath || rtrim($path, '/') === $messagesPath) {
        require_once __DIR__ . '/user_messages.php';
        exit;
    }
    if ($normalizedPath === $basePath || $normalizedPath === '/' || $path === $basePath . '/') {
        require_once __DIR__ . '/welcome.php';
        exit;
    }
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}
?>