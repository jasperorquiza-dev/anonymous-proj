<?php
// profile.php - Individual user profile management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../core/auth.php';
require_once '../core/database_connection.php';
require_once '../admin/admin_functions.php';
require_once '../master/master_auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_master = isMaster();

// If profile ID is provided, check permissions
$profile_user_id = $user_id; // Default to current user
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $requested_id = (int)$_GET['id'];
    if ($is_master) {
        $profile_user_id = $requested_id;
    } elseif ($requested_id !== $user_id) {
        header('Location: messages.php');
        exit;
    }
}

// Initialize profile system
initializeUserProfiles();

// Get profile data
$profile = getUserProfile($profile_user_id);
$stats = getUserActivityStats($profile_user_id);
$recent_messages = getRecentUserMessages($profile_user_id, 10);

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $update_data = [
            'display_name' => $_POST['display_name'] ?? '',
            'bio' => $_POST['bio'] ?? '',
            'avatar' => $_POST['avatar'] ?? '',
            'profile_visibility' => $_POST['profile_visibility'] ?? 'public',
            'show_online_status' => isset($_POST['show_online_status']) ? 1 : 0,
            'allow_messages' => isset($_POST['allow_messages']) ? 1 : 0,
        ];

        if (updateUserProfile($profile_user_id, $update_data)) {
            $message = 'Profile updated successfully!';
            $profile = getUserProfile($profile_user_id); // Refresh data
        } else {
            $message = 'Error updating profile.';
        }
    }
}

// Get user info for display
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT name, username, is_admin, is_banned, is_muted, created_at FROM users WHERE id = ?");
    $stmt->execute([$profile_user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $user_info = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['display_name'] ?? $user_info['name'] ?? 'User'); ?> - Profile</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        :root {
            --master-blue: #001489;
            --master-red: #c8102e;
            --master-blue-light: #0019b8;
            --master-red-light: #e01e3a;
            --surface: #0b0b0c;
            --surface-2: #121214;
            --text: #e7e7ea;
            --muted: #9ca3af;
            --border: #1f1f23;
            --success: #0f6b4f;
            --warning: #7a5b00;
            --danger: #6f1d1b;
            --info: #001489;
        }

        html, body {
            background: var(--surface);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .forum-container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 20px;
        }

        /* Enhanced Header */
        .profile-header {
            background: linear-gradient(135deg, var(--surface-2), #1a1a1c);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 40px rgba(212, 175, 55, 0.1);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--master-blue), var(--master-red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }

        .profile-info h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .profile-username {
            color: var(--muted);
            font-size: 1.1rem;
            margin: 8px 0 0 0;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--surface-2), #1a1a1c);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--master-blue);
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Content Sections */
        .content-section {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .section-header {
            background: rgba(255, 255, 255, 0.02);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .section-content {
            padding: 24px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--master-blue);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            background: var(--surface);
            color: var(--text);
        }

        .form-checkbox {
            margin-right: 8px;
        }

        /* Messages Section */
        .messages-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .message-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .message-time {
            color: var(--muted);
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .message-content {
            line-height: 1.5;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text);
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            border-color: #2a2a31;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-primary {
            background: var(--master-blue);
            border-color: var(--master-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--master-blue-light);
        }

        .btn-success {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            border-color: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        .btn-block {
            width: 100%;
            margin-top: 16px;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active { background: var(--success); color: #eafff7; }
        .status-banned { background: var(--danger); color: #ffecec; }
        .status-muted { background: var(--warning); color: #fff3c4; }
        .status-admin { background: #d4af37; color: #111; }

        /* Alert messages */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
            color: #eafff7;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: #ffecec;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }

            .forum-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user_info['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($profile['display_name'] ?? $user_info['name'] ?? 'User'); ?></h1>
                <div class="profile-username">@<?php echo htmlspecialchars($user_info['username'] ?? ''); ?></div>
                <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php if (isset($user_info['is_admin']) && $user_info['is_admin'] == 1): ?>
                        <span class="status-badge status-admin">Administrator</span>
                    <?php endif; ?>
                    <?php if (isset($user_info['is_banned']) && $user_info['is_banned'] == 1): ?>
                        <span class="status-badge status-banned">Banned</span>
                    <?php elseif (isset($user_info['is_muted']) && $user_info['is_muted'] == 1): ?>
                        <span class="status-badge status-muted">Muted</span>
                    <?php else: ?>
                        <span class="status-badge status-active">Active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_messages'] ?? 0; ?></span>
                <span class="stat-label">Total Messages</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['weekly_messages'] ?? 0; ?></span>
                <span class="stat-label">This Week</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['monthly_messages'] ?? 0; ?></span>
                <span class="stat-label">This Month</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php echo $user_info['created_at'] ? date('M Y', strtotime($user_info['created_at'])) : 'Unknown'; ?>
                </span>
                <span class="stat-label">Member Since</span>
            </div>
        </div>

        <!-- Profile Settings (if viewing own profile or master) -->
        <?php if ($profile_user_id === $user_id || $is_master): ?>
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Profile Settings</h2>
                </div>
                <div class="section-content">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Display Name</label>
                            <input type="text" class="form-input" name="display_name"
                                   value="<?php echo htmlspecialchars($profile['display_name'] ?? ''); ?>"
                                   placeholder="Your display name">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bio</label>
                            <textarea class="form-input form-textarea" name="bio"
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Avatar URL</label>
                            <input type="url" class="form-input" name="avatar"
                                   value="<?php echo htmlspecialchars($profile['avatar'] ?? ''); ?>"
                                   placeholder="https://example.com/avatar.jpg">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Profile Visibility</label>
                            <select class="form-input form-select" name="profile_visibility">
                                <option value="public" <?php echo ($profile['profile_visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo ($profile['profile_visibility'] ?? 'public') === 'private' ? 'selected' : ''; ?>>Private</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" class="form-checkbox" name="show_online_status" value="1"
                                       <?php echo ($profile['show_online_status'] ?? 1) ? 'checked' : ''; ?>>
                                Show online status
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" class="form-checkbox" name="allow_messages" value="1"
                                       <?php echo ($profile['allow_messages'] ?? 1) ? 'checked' : ''; ?>>
                                Allow direct messages
                            </label>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary btn-block">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Messages -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Recent Messages</h2>
            </div>
            <div class="section-content">
                <?php if (empty($recent_messages)): ?>
                    <p style="color: var(--muted); text-align: center; padding: 40px;">
                        No messages yet.
                    </p>
                <?php else: ?>
                    <div class="messages-list">
                        <?php foreach ($recent_messages as $msg): ?>
                            <div class="message-item">
                                <div class="message-time">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                </div>
                                <div class="message-content">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div style="text-align: center; margin-top: 32px;">
            <a href="<?php echo $is_master ? 'user_profiles.php' : 'messages.php'; ?>" class="btn">
                <?php echo $is_master ? '← Back to Profiles' : '← Back to Forum'; ?>
            </a>
        </div>
    </div>
</body>
</html>
