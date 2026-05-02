<?php
// real_time.php - Returns fresh posts HTML for real-time polling
require_once 'database_connection.php';

try {
    $pdo = getPDO();
    if (!$pdo) {
        echo "<!-- Database connection failed -->";
        exit;
    }
    
    // Fetch latest posts from posts table
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT 20");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($posts)) {
        echo '<div class="empty-state">
                <div class="empty-icon">💬</div>
                <h3>No posts yet</h3>
                <p>Be the first to start a conversation!</p>
              </div>';
    } else {
        foreach ($posts as $post) {
            echo '<div class="post">';
            echo '<p>' . htmlspecialchars($post['content']) . '</p>';
            echo '<span class="post-time" data-timestamp="' . $post['created_at'] . '"></span>';
            echo '</div>';
        }
    }
    
} catch (Exception $e) {
    echo "<!-- Error loading posts: " . htmlspecialchars($e->getMessage()) . " -->";
}
?>
