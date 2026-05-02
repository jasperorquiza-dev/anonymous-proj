<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../admin/admin_functions.php';
function getActiveSystemMessages() {
    try {
        $pdo = getPDO();
        if (!$pdo) return [];
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
                duration_hours INT DEFAULT 24,
                is_active TINYINT(1) DEFAULT 1,
                created_by VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL
            )
        ");
        $stmt = $pdo->prepare("
            SELECT * FROM system_messages
            WHERE is_active = 1
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
$system_messages = getActiveSystemMessages();
?>
<?php if (!empty($system_messages)): ?>
<div id="system-messages-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;">
    <?php foreach ($system_messages as $msg): ?>
    <div class="system-message system-message-<?php echo $msg['type']; ?>"
         style="background: <?php
             switch($msg['type']) {
                 case 'success': echo '#d4edda'; break;
                 case 'warning': echo '#fff3cd'; break;
                 case 'error': echo '#f8d7da'; break;
                 default: echo '#d1ecf1'; break;
             }
         ?>;
         border: 1px solid <?php
             switch($msg['type']) {
                 case 'success': echo '#c3e6cb'; break;
                 case 'warning': echo '#ffeaa7'; break;
                 case 'error': echo '#f5c6cb'; break;
                 default: echo '#bee5eb'; break;
             }
         ?>;
         color: <?php
             switch($msg['type']) {
                 case 'success': echo '#155724'; break;
                 case 'warning': echo '#856404'; break;
                 case 'error': echo '#721c24'; break;
                 default: echo '#0c5460'; break;
             }
         ?>;
         padding: 12px 16px;
         margin-bottom: 8px;
         border-radius: 6px;
         box-shadow: 0 2px 8px rgba(0,0,0,0.1);
         position: relative;
         animation: slideIn 0.3s ease-out;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1; margin-right: 12px;">
                <strong style="display: block; margin-bottom: 4px;">
                    <?php
                    switch($msg['type']) {
                        case 'success': echo '✅ Success'; break;
                        case 'warning': echo '⚠️ Warning'; break;
                        case 'error': echo '❌ Error'; break;
                        default: echo 'ℹ️ Notice'; break;
                    }
                    ?>
                </strong>
                <div style="font-size: 0.9rem; line-height: 1.4;">
                    <?php echo htmlspecialchars($msg['message']); ?>
                </div>
            </div>
            <button onclick="dismissSystemMessage(<?php echo $msg['id']; ?>)"
                    style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem; padding: 0; margin-left: 8px; opacity: 0.7;"
                    title="Dismiss">
                ×
            </button>
        </div>
        <?php if ($msg['expires_at']): ?>
        <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 8px;">
            Expires: <?php echo date('M j, g:i A', strtotime($msg['expires_at'])); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<style>
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
.system-message {
    transition: all 0.3s ease;
}
.system-message:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
<script>
function dismissSystemMessage(messageId) {
    fetch('ajax/ajax_dismiss_system_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message_id=' + messageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const messageElement = document.querySelector(`[onclick="dismissSystemMessage(${messageId})"]`).closest('.system-message');
            if (messageElement) {
                messageElement.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    messageElement.remove();
                    const container = document.getElementById('system-messages-container');
                    if (container && container.children.length === 0) {
                        container.style.display = 'none';
                    }
                }, 300);
            }
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.system-message');
    messages.forEach(message => {
        setTimeout(() => {
            const dismissBtn = message.querySelector('button[onclick*="dismissSystemMessage"]');
            if (dismissBtn) {
                dismissBtn.click();
            }
        }, 10000);
    });
});
</script>
<style>
@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>
<?php endif; ?>