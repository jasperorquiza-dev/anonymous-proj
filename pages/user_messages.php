<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../core/auth.php';
require_once '../core/database_connection.php';
require_once '../admin/admin_functions.php';
require_once '../core/maintenance_check.php';
require_once '../master/master_auth.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
checkIfBanned();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_username = $_SESSION['user_username'];
$anonymous_username = '';
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT anonymous_username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    $anonymous_username = $user_data['anonymous_username'] ?? '';
} catch(PDOException $e) {
}
$messages = [];
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, username, message, created_at FROM messages ORDER BY created_at ASC LIMIT 50");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
}
$is_admin = false;
try {
    $is_admin = isAdmin();
} catch(Exception $e) {
    error_log("Admin check error: " . $e->getMessage());
}
$forum_settings = getForumSettings();
$forum_title = $forum_settings['forum_title'] ?? 'ICCT Forum';
$forum_logo = $forum_settings['forum_logo'] ?? 'assets/img/icct.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($forum_title); ?> - Welcome <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <?php include '../core/system_messages.php'; ?>
    <style>
        .top-nav-buttons {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            padding: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .top-nav-buttons .user-info {
            color: white;
            font-weight: 500;
            margin: 0;
        }
        .top-nav-buttons .admin-btn,
        .top-nav-buttons .logout-btn {
            padding: 8px 16px;
            border: 2px solid white;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .top-nav-buttons .admin-btn:hover,
        .top-nav-buttons .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .admin-badge {
            background: #10B981;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 8px;
        }
        .forum-container {
            padding-top: 70px;
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            transition: all 0.4s ease;
        }
        .anonymous-card {
            background: #dbeafe;
            color: #1e40af;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #93c5fd;
            transition: all 0.4s ease;
        }
        [data-theme="dark"] .anonymous-card {
            background: #1e3a8a;
            color: #dbeafe;
            border-color: #3b82f6;
        }
        .anonymous-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        .anonymous-card p {
            margin: 0;
        }
        .message-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
        }
        .delete-btn {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .delete-btn:hover {
            background: var(--red-light);
        }
        .anonymous-notice {
            background: #fef3c7;
            color: #92400e;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #fcd34d;
            transition: all 0.4s ease;
        }
        [data-theme="dark"] .anonymous-notice {
            background: #451a03;
            color: #fef3c7;
            border-color: #d97706;
        }
        .theme-toggle-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .theme-toggle {
            display: flex;
            background: var(--bg-card);
            border-radius: 25px;
            padding: 5px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .theme-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: transparent;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-btn.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            transform: scale(1.1);
        }
        .theme-btn:hover {
            transform: scale(1.1);
        }
        .top-nav-buttons {
            backdrop-filter: blur(20px);
            animation: slideDown 0.5s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .top-nav-buttons .admin-btn,
        .top-nav-buttons .logout-btn {
            position: relative;
            overflow: hidden;
        }
        .top-nav-buttons .admin-btn::before,
        .top-nav-buttons .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        .top-nav-buttons .admin-btn:hover::before,
        .top-nav-buttons .logout-btn:hover::before {
            left: 100%;
        }
        .welcome-card {
            animation: fadeIn 0.8s ease-out 0.2s both;
            box-shadow: 0 10px 30px rgba(0, 20, 137, 0.3);
        }
        .welcome-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .anonymous-card {
            animation: fadeIn 0.8s ease-out 0.4s both;
            box-shadow: 0 4px 15px rgba(0, 20, 137, 0.2);
        }
        .anonymous-card strong {
            background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.1rem;
        }
        .typing-indicator {
            display: none;
            padding: 0.5rem 1rem;
            background: rgba(0, 20, 137, 0.1);
            border-radius: 20px;
            margin: 0.5rem 0;
            font-size: 0.9rem;
            color: var(--primary-blue);
            animation: pulse 1.5s ease-in-out infinite;
        }
        .typing-indicator.show {
            display: block;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- TOP NAVIGATION FOR LOGGED IN USERS -->
        <div class="top-nav-buttons">
            <?php if ($is_admin && !isMaster()): ?>
            <a href="admin_dashboard.php" class="admin-btn">Admin Panel</a>
            <?php endif; ?>
            <?php if (isMaster()): ?>
            <a href="master_dashboard_enhanced.php" class="admin-btn" style="background: linear-gradient(135deg, #d4af37, #c9a233); color: #111; font-weight: 700;">👑 Master Panel</a>
            <?php endif; ?>
            <span class="user-info">
                Welcome, <?php echo htmlspecialchars($user_name); ?>!
                <?php if ($is_admin): ?>
                <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
            </span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <!-- Header -->
        <header class="forum-header">
            <img src="<?php echo htmlspecialchars($forum_logo); ?>" alt="<?php echo htmlspecialchars($forum_title); ?> Logo" class="school-logo">
            <h1 class="forum-title"><?php echo htmlspecialchars($forum_title); ?></h1>
            <p class="forum-subtitle">Share your thoughts • Connect with peers</p>
            <div class="forum-stats">
                <div class="stat-item">
                    <div class="stat-number" id="total-messages"><?php echo count($messages); ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="online-users">0</div>
                    <div class="stat-label">Online Now</div>
                </div>
            </div>
        </header>
        <!-- Welcome Message -->
        <div class="welcome-card">
            <h3>
                <?php if (isMaster()): ?>
                Welcome to <?php echo htmlspecialchars($forum_title); ?>, Master <?php echo htmlspecialchars($user_name); ?>! 👑
                <?php elseif ($is_admin): ?>
                Welcome to <?php echo htmlspecialchars($forum_title); ?>, Admin <?php echo htmlspecialchars($user_name); ?>!
                <?php else: ?>
                Welcome to <?php echo htmlspecialchars($forum_title); ?>, <?php echo htmlspecialchars($user_name); ?>!
                <?php endif; ?>
            </h3>
            <p>
                <?php if (isMaster()): ?>
                You have full master privileges. Access the Master Panel for complete forum control and management.
                <?php elseif ($is_admin): ?>
                You have administrator privileges. All your posts will be anonymous, but you can manage the forum.
                <?php else: ?>
                You're now part of our community. Start sharing your thoughts anonymously!
                <?php endif; ?>
            </p>
        </div>
        <!-- Anonymous Username Card -->
        <?php if (!empty($anonymous_username)): ?>
        <div class="anonymous-card">
            <h3>Your Anonymous Identity</h3>
            <p>You appear in the forum as: <strong><?php echo htmlspecialchars(preg_match('/^Anonymous\d+$/', (string)$anonymous_username) ? $anonymous_username : ('Anonymous' . substr((string)$user_id, -3))); ?></strong></p>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.5rem;">
                This username stays the same for all your messages to maintain consistency.
            </p>
        </div>
        <?php else: ?>
        <div class="anonymous-notice">
            <strong>All posts are anonymous!</strong> Your first message will generate a random username that stays with your account.
        </div>
        <?php endif; ?>
        <!-- Main Content -->
        <div class="forum-content">
            <!-- Messages Section -->
            <main class="messages-section">
                <div class="messages-header">
                    <h2>Community Discussions</h2>
                    <button id="refresh-btn" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                        Refresh
                    </button>
                </div>
                <div id="messages-container">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">💬</div>
                            <h3>No messages yet</h3>
                            <p>Be the first to start a conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <span class="message-username"><?php echo htmlspecialchars($message['username']); ?></span>
                                    <span class="message-time">
                                        <?php
                                        $time = new DateTime($message['created_at']);
                                        $time->setTimezone(new DateTimeZone('Asia/Manila'));
                                        echo $time->format('D, M j, Y g:i A');
                                        ?>
                                    </span>
                                </div>
                                <div class="message-content">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                </div>
                                <?php if ($is_admin || isMaster()): ?>
                                <div class="message-actions">
                                    <button class="delete-btn" onclick="deleteMessage(<?php echo $message['id']; ?>)">Delete Message</button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
            <!-- Sidebar -->
            <aside class="forum-sidebar">
                <div class="info-card">
                    <h3>Welcome!</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        You're logged in as <strong><?php echo htmlspecialchars($user_username); ?></strong>.
                        <?php if (isMaster()): ?>
                        <strong style="color: #d4af37;">(Master Account)</strong>
                        <?php elseif ($is_admin): ?>
                        <strong style="color: #10B981;">(Administrator)</strong>
                        <?php endif; ?>
                        <br><br>
                        <?php if (!empty($anonymous_username)): ?>
                        <strong>Your anonymous identity:</strong> <?php echo htmlspecialchars($anonymous_username); ?>
                        <?php else: ?>
                        <strong>Your first post will create your anonymous identity!</strong>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="info-card">
                    <h3>Forum Rules</h3>
                    <ul class="rules-list">
                        <li>• Be respectful to others</li>
                        <li>• No personal attacks</li>
                        <li>• Keep discussions relevant</li>
                        <li>• No spam or advertising</li>
                        <li>• Enjoy the conversation!</li>
                    </ul>
                </div>
                <?php if (isMaster()): ?>
                <div class="info-card">
                    <h3>Master Tools</h3>
                    <ul class="rules-list">
                        <li>• Full forum control & management</li>
                        <li>• User & admin management</li>
                        <li>• System configuration</li>
                        <li>• Security & backup tools</li>
                        <li>• Analytics & reporting</li>
                    </ul>
                </div>
                <?php elseif ($is_admin): ?>
                <div class="info-card">
                    <h3>Admin Tools</h3>
                    <ul class="rules-list">
                        <li>• Delete inappropriate messages</li>
                        <li>• Monitor forum activity</li>
                        <li>• Keep community safe</li>
                        <li>• Lead by example</li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="info-card">
                    <h3>Anonymous Posting</h3>
                    <ul class="rules-list">
                        <li>• All posts are anonymous</li>
                        <li>• Consistent random username</li>
                        <li>• Your identity is protected</li>
                        <li>• Focus on ideas, not identities</li>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>
        </div>
        <!-- Input Section -->
        <section class="input-section">
            <form id="message-form">
                <textarea
                    id="message-input"
                    name="message"
                    placeholder="Share your thoughts, questions, or feedback anonymously..."
                    maxlength="<?php echo $forum_settings['max_message_length'] ?? 500; ?>"
                    required
                ></textarea>
                <div class="form-actions">
                    <span class="char-count">
                        <span id="char-count">0</span>/<?php echo $forum_settings['max_message_length'] ?? 500; ?> characters
                    </span>
                    <button type="submit" id="submit-btn">
                        Post Anonymously
                    </button>
                </div>
            </form>
        </section>
    </div>
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <div class="theme-toggle">
            <button class="theme-toggle" id="theme-toggle" title="Toggles light & dark" aria-label="auto" aria-live="polite">
                <svg class="sun-and-moon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24">
                    <mask class="moon" id="moon-mask">
                        <rect x="0" y="0" width="100%" height="100%" fill="white" />
                        <circle cx="24" cy="10" r="6" fill="black" />
                    </mask>
                    <circle class="sun" cx="12" cy="12" r="6" mask="url(#moon-mask)" fill="currentColor" />
                    <g class="sun-beams" stroke="currentColor">
                        <line x1="12" y1="1" x2="12" y2="3" />
                        <line x1="12" y1="21" x2="12" y2="23" />
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                        <line x1="1" y1="12" x2="3" y2="12" />
                        <line x1="21" y1="12" x2="23" y2="12" />
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                    </g>
                </svg>
            </button>
        </div>
    </div>
    <script src="../assets/js/forum.js"></script>
    <script>
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('message_id', messageId);
                fetch('../ajax/ajax_delete_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('Message deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('Network error');
                });
            }
        }
        document.getElementById('refresh-btn').addEventListener('click', function() {
            location.reload();
        });
        function initScrollEffects() {
            const topNav = document.querySelector('.top-nav-buttons');
            if (!topNav) return;
            let lastScrollTop = 0;
            let isHidden = false;
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollTop > lastScrollTop && scrollTop > 100 && !isHidden) {
                    topNav.style.transform = 'translateY(-100%)';
                    topNav.style.opacity = '0';
                    topNav.style.pointerEvents = 'none';
                    isHidden = true;
                } else if (scrollTop < lastScrollTop && isHidden) {
                    topNav.style.transform = 'translateY(0)';
                    topNav.style.opacity = '1';
                    topNav.style.pointerEvents = 'auto';
                    isHidden = false;
                }
                if (scrollTop === 0) {
                    topNav.style.transform = 'translateY(0)';
                    topNav.style.opacity = '1';
                    topNav.style.pointerEvents = 'auto';
                    isHidden = false;
                }
                lastScrollTop = scrollTop;
            }, { passive: true });
        }
        document.addEventListener('DOMContentLoaded', function() {
            initScrollEffects();
            fetch('../ajax/update_online_status.php');
        });
    </script>
</body>
</html>