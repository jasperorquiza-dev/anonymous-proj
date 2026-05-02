<?php
// routes.php - simple router and URL helpers to avoid exposing filenames

function getMessagesSlug(): string {
    static $slug = null;
    if ($slug !== null) return $slug;
    // Deterministic pseudo-random slug (change the salt to rotate)
    $salt = 'icct_forum_route_salt_v1';
    $slug = substr(hash('sha256', $salt . 'messages'), 0, 20);
    return $slug;
}

function getBasePath(): string {
    // Directory path of the front controller (index.php), with leading slash, no trailing slash
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $dir === '/' ? '' : $dir; // at domain root => empty
}

function getMessagesUrl(): string {
    return getBasePath() . '/' . getMessagesSlug();
}

function handleFrontControllerRouting(): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $basePath = getBasePath();
    $messagesPath = $basePath . '/' . getMessagesSlug();
    
    // Normalize path for comparison
    $normalizedPath = rtrim($path, '/');
    if ($normalizedPath === '') {
        $normalizedPath = '/';
    }

    // Messages route
    if ($path === $messagesPath || rtrim($path, '/') === $messagesPath) {
        require_once __DIR__ . '/user_messages.php';
        exit;
    }

    // Home/root route
    if ($normalizedPath === $basePath || $normalizedPath === '/' || $path === $basePath . '/') {
        require_once __DIR__ . '/welcome.php';
        exit;
    }

    // No match found - trigger 404
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}
?>


