<?php
// master_dashboard_enhanced.php - Enhanced Master Dashboard with comprehensive features
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../master/master_auth.php';
require_once '../admin/admin_functions.php';
require_once 'philippines_time.php';

if (!isMaster()) {
    header('Location: ../pages/messages.php');
    exit;
}

// Get data for dashboard
$stats = getForumStats();
$users = getAllUsers();
$messages = getAllMessages();
$recent_activity = getRecentActivity();
$admin_actions = getAdminActions();
$reports = getReports();
$forum_settings = getForumSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Master Dashboard</title>
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
            max-width: 1400px; 
            margin: 24px auto; 
            padding: 20px; 
        }
        
        /* Enhanced Admin Header */
        .admin-header {
            background: linear-gradient(135deg, var(--surface-2), #1a1a1c);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 40px rgba(212, 175, 55, 0.1);
            animation: slideDown 0.6s ease-out;
            position: relative;
            overflow: hidden;
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
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--master-blue), var(--master-red), var(--master-blue));
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .admin-header h1 { 
            font-weight: 700; 
            letter-spacing: 0.3px; 
            margin: 0;
            font-size: 1.875rem;
        }
        
        .admin-header h1::after { 
            content: "  •  MASTER"; 
            background: linear-gradient(135deg, var(--master-blue), var(--master-red));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800; 
            letter-spacing: 1.5px; 
            text-shadow: none;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        /* Enhanced Navigation Tabs */
        .nav-tabs {
            display: flex;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 28px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .nav-tab {
            padding: 14px 24px;
            background: transparent;
            border: none;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            font-size: 0.95rem;
            position: relative;
        }
        
        .nav-tab::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--master-blue), var(--master-red));
            transition: all 0.3s ease;
            transform: translateX(-50%);
            box-shadow: 0 0 10px var(--master-blue);
        }
        
        .nav-tab.active {
            background: linear-gradient(135deg, rgba(0, 20, 137, 0.15), rgba(200, 16, 46, 0.15));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-tab.active::before {
            width: 100%;
        }
        
        .nav-tab:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Enhanced Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--surface-2), #1a1a1c);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px 24px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out both;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--master-blue), var(--master-red), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 20, 137, 0.3);
            border-color: var(--master-blue);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--master-blue), var(--master-red));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Section Cards */
        .section {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .section-header {
            background: rgba(255, 255, 255, 0.02);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .section-content {
            padding: 20px;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: rgba(255, 255, 255, 0.05);
            color: var(--muted);
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: transparent;
            color: var(--text);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            border-color: #2a2a31;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .btn-primary {
            background: var(--master-gold);
            border-color: var(--master-gold);
            color: #111;
        }
        
        .btn-primary:hover {
            background: var(--master-gold-600);
            border-color: var(--master-gold-600);
        }
        
        .btn-success {
            background: var(--success);
            border-color: var(--success);
            color: #eafff7;
        }
        
        .btn-warning {
            background: var(--warning);
            border-color: var(--warning);
            color: #fff3c4;
        }
        
        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: #ffecec;
        }
        
        .btn-info {
            background: var(--info);
            border-color: var(--info);
            color: #e0f2fe;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            color: var(--text);
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--surface);
            color: var(--text);
            font-size: 0.9rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--master-gold);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Status badges */
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active { background: var(--success); color: #eafff7; }
        .status-banned { background: var(--danger); color: #ffecec; }
        .status-muted { background: var(--warning); color: #fff3c4; }
        .status-admin { background: var(--master-gold); color: #111; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-tabs {
                flex-wrap: wrap;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>👑 Enhanced Master Dashboard</h1>
            <div class="header-actions">
                <a href="messages.php" class="btn">Back to Forum</a>
                <button class="btn btn-info" onclick="showModal('backupModal')">Backup</button>
                <button class="btn btn-warning" onclick="showModal('maintenanceModal')">Maintenance</button>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('overview')">📊 Overview</button>
            <button class="nav-tab" onclick="showTab('users')">👥 Users & Admins</button>
            <button class="nav-tab" onclick="showTab('posts')">📝 Posts & Forum</button>
            <button class="nav-tab" onclick="showTab('moderation')">🛡️ Moderation</button>
            <button class="nav-tab" onclick="showTab('settings')">⚙️ Settings</button>
            <button class="nav-tab" onclick="showTab('security')">🔒 Security</button>
            <button class="nav-tab" onclick="showTab('analytics')">📈 Analytics</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_messages'] ?? 0; ?></span>
                    <span class="stat-label">Total Messages</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['banned_users'] ?? 0; ?></span>
                    <span class="stat-label">Banned Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['muted_users'] ?? 0; ?></span>
                    <span class="stat-label">Muted Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['admin_count'] ?? 0; ?></span>
                    <span class="stat-label">Admins</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['reports_count'] ?? 0; ?></span>
                    <span class="stat-label">Reports</span>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Recent Activity</h3>
                    <button class="btn btn-sm" onclick="refreshActivity()">Refresh</button>
                </div>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="activityTable">
                                <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        // Use Philippine time for activity timestamps
                                        $activity_time = new DateTime($activity['created_at']);
                                        $activity_time->setTimezone(new DateTimeZone('Asia/Manila'));
                                        echo $activity_time->format('M j, g:i A');
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users & Admins Tab -->
        <div id="users" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">User Management</h3>
                    <div>
                        <button class="btn btn-sm" onclick="refreshUsers()">Refresh Users</button>
                        <button class="btn btn-sm btn-primary" onclick="showModal('promoteModal')">Promote to Admin</button>
                        <button class="btn btn-sm btn-warning" onclick="bulkAction('mute')">Bulk Mute</button>
                        <button class="btn btn-sm btn-danger" onclick="bulkAction('ban')">Bulk Ban</button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Admin</th>
                                    <th>Joined</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><input type="checkbox" class="user-checkbox" value="<?php echo $u['id']; ?>"></td>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td>
                                        <?php if (isset($u['is_banned']) && $u['is_banned'] == 1): ?>
                                            <span class="status-badge status-banned">Banned</span>
                                        <?php elseif (isset($u['is_muted']) && $u['is_muted'] == 1): ?>
                                            <span class="status-badge status-muted">Muted</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($u['is_admin']) && $u['is_admin'] == 1): ?>
                                            <span class="status-badge status-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="status-badge">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Use Philippine time for join date
                                        $join_time = new DateTime($u['created_at']);
                                        $join_time->setTimezone(new DateTimeZone('Asia/Manila'));
                                        echo $join_time->format('M j, Y');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($u['is_online']) && $u['is_online'] == 1): ?>
                                            <span style="color: #10B981; font-weight: bold;">🟢 Online</span>
                                        <?php elseif (isset($u['last_activity']) && $u['last_activity']): ?>
                                            <?php 
                                            // Use Philippine time for last activity
                                            $last_time = new DateTime($u['last_activity']);
                                            $last_time->setTimezone(new DateTimeZone('Asia/Manila'));
                                            ?>
                                            <span style="color: #9CA3AF;">⚫ <?php echo $last_time->format('M j, g:i A'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #9CA3AF;">⚫ Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <?php if (isset($u['is_admin']) && $u['is_admin'] == 1): ?>
                                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $u['id']): ?>
                                                    <span class="btn btn-sm" style="background: #d4af37; color: #111; cursor: default;">Master Account</span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning" onclick="demoteAdmin(<?php echo $u['id']; ?>)">Demote</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="promoteAdmin(<?php echo $u['id']; ?>)">Promote</button>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($u['is_muted']) && $u['is_muted'] == 1): ?>
                                                <button class="btn btn-sm btn-success" onclick="unmuteUser(<?php echo $u['id']; ?>)">Unmute</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning" onclick="muteUser(<?php echo $u['id']; ?>)">Mute</button>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($u['is_banned']) && $u['is_banned'] == 1): ?>
                                                <button class="btn btn-sm btn-success" onclick="unbanUser(<?php echo $u['id']; ?>)">Unban</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning" onclick="banUser(<?php echo $u['id']; ?>)">Ban</button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-info" onclick="resetPassword(<?php echo $u['id']; ?>)">Reset PW</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $u['id']; ?>)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts & Forum Tab -->
        <div id="posts" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Message Management</h3>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="bulkPinMessages()">Pin Selected</button>
                        <button class="btn btn-sm btn-warning" onclick="bulkDeleteMessages()">Bulk Delete</button>
                        <button class="btn btn-sm btn-info" onclick="clearSpam()">Clear Spam</button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllMessages" onchange="toggleSelectAllMessages()"></th>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><input type="checkbox" class="message-checkbox" value="<?php echo $m['id']; ?>"></td>
                                    <td><?php echo $m['id']; ?></td>
                                    <td><?php echo htmlspecialchars($m['username']); ?></td>
                                    <td style="max-width: 300px; word-wrap: break-word;">
                                        <?php echo htmlspecialchars(substr($m['message'], 0, 100)) . (strlen($m['message']) > 100 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <?php if (isset($m['is_pinned']) && $m['is_pinned'] == 1): ?>
                                            <span class="status-badge status-admin">Pinned</span>
                                        <?php elseif (isset($m['is_deleted']) && $m['is_deleted'] == 1): ?>
                                            <span class="status-badge status-banned">Deleted</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Use Philippine time for message timestamps
                                        $message_time = new DateTime($m['created_at']);
                                        $message_time->setTimezone(new DateTimeZone('Asia/Manila'));
                                        echo $message_time->format('M j, g:i A');
                                        ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <?php if (!isset($m['is_pinned']) || $m['is_pinned'] != 1): ?>
                                                <button class="btn btn-sm btn-primary" onclick="pinMessage(<?php echo $m['id']; ?>)">Pin</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning" onclick="unpinMessage(<?php echo $m['id']; ?>)">Unpin</button>
                                            <?php endif; ?>
                                            
                                            <?php if (!isset($m['is_deleted']) || $m['is_deleted'] != 1): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo $m['id']; ?>)">Delete</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="restoreMessage(<?php echo $m['id']; ?>)">Restore</button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $m['id']; ?>)">View</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Moderation Tab -->
        <div id="moderation" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Reports & Moderation</h3>
                    <button class="btn btn-sm btn-primary" onclick="refreshReports()">Refresh</button>
                </div>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Reporter</th>
                                    <th>Target</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['id']; ?></td>
                                    <td><?php echo htmlspecialchars($report['type']); ?></td>
                                    <td><?php echo htmlspecialchars($report['reporter']); ?></td>
                                    <td><?php echo htmlspecialchars($report['target']); ?></td>
                                    <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                    <td>
                                        <?php if ($report['status'] == 'pending'): ?>
                                            <span class="status-badge status-warning">Pending</span>
                                        <?php elseif ($report['status'] == 'resolved'): ?>
                                            <span class="status-badge status-success">Resolved</span>
                                        <?php else: ?>
                                            <span class="status-badge status-banned">Dismissed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Use Philippine time for report timestamps
                                        $report_time = new DateTime($report['created_at']);
                                        $report_time->setTimezone(new DateTimeZone('Asia/Manila'));
                                        echo $report_time->format('M j, g:i A');
                                        ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <button class="btn btn-sm btn-success" onclick="resolveReport(<?php echo $report['id']; ?>)">Resolve</button>
                                            <button class="btn btn-sm btn-warning" onclick="dismissReport(<?php echo $report['id']; ?>)">Dismiss</button>
                                            <button class="btn btn-sm btn-info" onclick="viewReport(<?php echo $report['id']; ?>)">View</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Forum Configuration</h3>
                    <button class="btn btn-sm btn-primary" onclick="saveSettings()">Save Settings</button>
                </div>
                <div class="section-content">
                    <form id="settingsForm">
                        <div class="form-group">
                            <label class="form-label">Forum Title (Master Only)</label>
                            <input type="text" class="form-input" name="forum_title" value="<?php echo htmlspecialchars($forum_settings['forum_title'] ?? 'ICCT Forum'); ?>">
                            <small style="color: var(--muted);">This is the main title displayed on the forum</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Forum Logo (Master Only)</label>
                            <input type="text" class="form-input" name="forum_logo" value="<?php echo htmlspecialchars($forum_settings['forum_logo'] ?? 'assets/img/icct.jpg'); ?>" placeholder="e.g., assets/img/icct.jpg">
                            <small style="color: var(--muted);">Enter the filename of the logo image (must be uploaded to the root directory)</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Forum Name</label>
                            <input type="text" class="form-input" name="forum_name" value="<?php echo htmlspecialchars($forum_settings['forum_name'] ?? 'ICCT Forum'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Forum Description</label>
                            <textarea class="form-input form-textarea" name="forum_description"><?php echo htmlspecialchars($forum_settings['forum_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Live Preview Mode (Master Only)</label>
                            <select class="form-input" name="live_preview_enabled">
                                <option value="1" <?php echo ($forum_settings['live_preview_enabled'] ?? 1) == 1 ? 'selected' : ''; ?>>Enabled - Users can see messages in real-time</option>
                                <option value="0" <?php echo ($forum_settings['live_preview_enabled'] ?? 1) == 0 ? 'selected' : ''; ?>>Disabled - Messages are hidden from users</option>
                            </select>
                            <small style="color: var(--muted);">Control whether users can see messages live or not</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Maintenance Mode</label>
                            <select class="form-input" name="maintenance_mode">
                                <option value="0" <?php echo ($forum_settings['maintenance_mode'] ?? 0) == 0 ? 'selected' : ''; ?>>Disabled</option>
                                <option value="1" <?php echo ($forum_settings['maintenance_mode'] ?? 0) == 1 ? 'selected' : ''; ?>>Enabled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Max Message Length</label>
                            <input type="number" class="form-input" name="max_message_length" value="<?php echo $forum_settings['max_message_length'] ?? 500; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Allow User Posts (Master Only)</label>
                            <select class="form-input" name="allow_guest_posts">
                                <option value="1" <?php echo ($forum_settings['allow_guest_posts'] ?? 1) == 1 ? 'selected' : ''; ?>>Enabled - All users can post messages</option>
                                <option value="0" <?php echo ($forum_settings['allow_guest_posts'] ?? 1) == 0 ? 'selected' : ''; ?>>Disabled - Only Master & Admin can post</option>
                            </select>
                            <small style="color: var(--muted);">When disabled, regular users cannot send messages. Master and Admin accounts can always post.</small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Message Broadcast -->
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">System Message Broadcast</h3>
                    <button class="btn btn-sm btn-primary" onclick="showModal('broadcastModal')">Broadcast Message</button>
                </div>
                <div class="section-content">
                    <p style="color: var(--muted); margin-bottom: 16px;">
                        Send system-wide messages to all users. Messages will appear as notifications and can be dismissed by users.
                    </p>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Security & Access Control</h3>
                    <button class="btn btn-sm btn-primary" onclick="showModal('ipBanModal')">IP Ban</button>
                </div>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="accessLogTable">
                                <!-- Access logs will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Analytics & Statistics</h3>
                    <button class="btn btn-sm btn-primary" onclick="exportAnalytics()">Export Data</button>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number" id="dailyUsers">0</span>
                            <span class="stat-label">Daily Active Users</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="dailyMessages">0</span>
                            <span class="stat-label">Daily Messages</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="avgSessionTime">0</span>
                            <span class="stat-label">Avg Session Time</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number" id="bounceRate">0%</span>
                            <span class="stat-label">Bounce Rate</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="promoteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Promote User to Admin</h3>
                <button class="modal-close" onclick="hideModal('promoteModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Select User</label>
                <select class="form-input" id="promoteUserId">
                    <?php foreach ($users as $u): ?>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button class="btn btn-primary" onclick="promoteSelectedUser()">Promote to Admin</button>
                <button class="btn" onclick="hideModal('promoteModal')">Cancel</button>
            </div>
        </div>
    </div>

    <div id="backupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Database Backup</h3>
                <button class="modal-close" onclick="hideModal('backupModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Backup Type</label>
                <select class="form-input" id="backupType">
                    <option value="full">Full Database</option>
                    <option value="users">Users Only</option>
                    <option value="messages">Messages Only</option>
                </select>
            </div>
            <div class="form-group">
                <button class="btn btn-primary" onclick="createBackup()">Create Backup</button>
                <button class="btn btn-info" onclick="restoreBackup()">Restore Backup</button>
            </div>
        </div>
    </div>

    <div id="maintenanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Maintenance Mode</h3>
                <button class="modal-close" onclick="hideModal('maintenanceModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Maintenance Message</label>
                <textarea class="form-input form-textarea" id="maintenanceMessage" placeholder="Forum is under maintenance. Please check back later."></textarea>
            </div>
            <div class="form-group">
                <button class="btn btn-warning" onclick="enableMaintenance()">Enable Maintenance</button>
                <button class="btn btn-success" onclick="disableMaintenance()">Disable Maintenance</button>
            </div>
        </div>
    </div>

    <div id="broadcastModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Broadcast System Message</h3>
                <button class="modal-close" onclick="hideModal('broadcastModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Message Type</label>
                <select class="form-input" id="broadcastType">
                    <option value="info">ℹ️ Information</option>
                    <option value="success">✅ Success</option>
                    <option value="warning">⚠️ Warning</option>
                    <option value="error">❌ Error</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea class="form-input form-textarea" id="broadcastMessage" placeholder="Enter your system message here..." maxlength="500"></textarea>
                <small style="color: var(--muted);">Maximum 500 characters</small>
            </div>
            <div class="form-group">
                <label class="form-label">Duration (hours)</label>
                <select class="form-input" id="broadcastDuration">
                    <option value="1">1 hour</option>
                    <option value="6">6 hours</option>
                    <option value="12">12 hours</option>
                    <option value="24" selected>24 hours</option>
                    <option value="48">48 hours</option>
                    <option value="168">1 week</option>
                </select>
            </div>
            <div class="form-group">
                <button class="btn btn-primary" onclick="broadcastMessage()">Broadcast Message</button>
                <button class="btn" onclick="hideModal('broadcastModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Toast Notification System
        function showToast(message, type = 'success', duration = 3000) {
            // Create toast container if it doesn't exist
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            // Icons for different types
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            // Titles for different types
            const titles = {
                success: 'Success!',
                error: 'Error!',
                warning: 'Warning!',
                info: 'Info'
            };
            
            toast.innerHTML = `
                <div class="toast-icon">${icons[type] || icons.success}</div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type] || titles.success}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
                <div class="toast-progress"></div>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 400);
            }, duration);
        }

        // Safe JSON fetch helper: reads text first, then parses JSON, and includes cookies
        async function fetchJSON(url, options = {}) {
            const opts = Object.assign({ credentials: 'same-origin', headers: { 'Accept': 'application/json' } }, options || {});
            const res = await fetch(url, opts);
            const text = await res.text();
            try {
                const data = JSON.parse(text || '{}');
                return data;
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text?.slice(0, 300)}`);
            }
        }

        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked nav tab
            event.target.classList.add('active');
        }

        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // User management functions
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function bulkAction(action) {
            const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
            if (selectedUsers.length === 0) {
                showToast('Please select users first', 'warning');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selectedUsers.length} users?`)) {
                const form = new FormData();
                form.append('action', action);
                form.append('user_ids', JSON.stringify(selectedUsers));
                
                fetchJSON('ajax_bulk_actions.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                })
                .then(data => {
                    if (data.status === 'success') {
                        showToast(`${selectedUsers.length} users ${action}ed successfully!`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error: ' + error.message, 'error');
                });
            }
        }

        function bulkDeleteMessages() {
            const selectedMessages = Array.from(document.querySelectorAll('.message-checkbox:checked')).map(cb => cb.value);
            if (selectedMessages.length === 0) {
                showToast('Please select messages first', 'warning');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedMessages.length} messages?`)) {
                const form = new FormData();
                form.append('message_ids', JSON.stringify(selectedMessages));
                
                fetchJSON('ajax_delete_multiple_messages.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                })
                .then(data => {
                    if (data.status === 'success') {
                        showToast(`${selectedMessages.length} messages deleted successfully!`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error: ' + error.message, 'error');
                });
            }
        }

        function bulkPinMessages() {
            const selectedMessages = Array.from(document.querySelectorAll('.message-checkbox:checked')).map(cb => cb.value);
            if (selectedMessages.length === 0) {
                showToast('Please select messages to pin', 'warning');
                return;
            }
            
            if (confirm(`Pin ${selectedMessages.length} selected messages?`)) {
                const form = new FormData();
                form.append('action', 'pin_messages');
                form.append('message_ids', JSON.stringify(selectedMessages));
                
                fetchJSON('ajax_bulk_actions.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                })
                .then(data => {
                    if (data.status === 'success') {
                        showToast(`${selectedMessages.length} messages pinned!`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Network error: ' + error.message, 'error');
                });
            }
        }

        // Toggle select all messages
        function toggleSelectAllMessages() {
            const selectAll = document.getElementById('selectAllMessages');
            const checkboxes = document.querySelectorAll('.message-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        // Individual action functions
        function promoteAdmin(userId) {
            if (!confirm('Make this user an admin?')) return;
            performAction('promote_admin', { user_id: userId });
        }

        function demoteAdmin(userId) {
            if (!confirm('Remove admin privileges?')) return;
            performAction('demote_admin', { user_id: userId });
        }

        function muteUser(userId) {
            if (!confirm('Mute this user for 24 hours?')) return;
            performAction('mute_user', { user_id: userId });
        }

        function unmuteUser(userId) {
            if (!confirm('Unmute this user?')) return;
            performAction('unmute_user', { user_id: userId });
        }

        function banUser(userId) {
            if (!confirm('Ban this user?')) return;
            performAction('ban_user', { user_id: userId });
        }

        function unbanUser(userId) {
            if (!confirm('Unban this user?')) return;
            performAction('unban_user', { user_id: userId });
        }

        function deleteUser(userId) {
            if (!confirm('Delete user and all messages?')) return;
            performAction('delete_user', { user_id: userId });
        }

        function resetPassword(userId) {
            if (!confirm('Reset password for this user?')) return;
            performAction('reset_password', { user_id: userId });
        }

        function pinMessage(messageId) {
            performAction('pin_message', { message_id: messageId });
        }

        function unpinMessage(messageId) {
            performAction('unpin_message', { message_id: messageId });
        }

        function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;
            performAction('delete_message', { message_id: messageId });
        }

        function restoreMessage(messageId) {
            performAction('restore_message', { message_id: messageId });
        }

        function viewMessage(messageId) {
            // Open message in new window or modal
            window.open(`view_message.php?id=${messageId}`, '_blank');
        }

        function resolveReport(reportId) {
            performAction('resolve_report', { report_id: reportId });
        }

        function dismissReport(reportId) {
            performAction('dismiss_report', { report_id: reportId });
        }

        function viewReport(reportId) {
            window.open(`view_report.php?id=${reportId}`, '_blank');
        }

        function clearSpam() {
            if (!confirm('Clear all spam messages? This will delete all flagged spam.')) return;
            
            const form = new FormData();
            form.append('action', 'clear_spam');
            
            fetchJSON('ajax_bulk_actions.php', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
            .then(data => {
                if (data.status === 'success') {
                    showToast('Spam messages cleared successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network error: ' + error.message, 'error');
            });
        }

        function refreshActivity() {
            fetch('../ajax/ajax_get_activity.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateActivityTable(data.activity);
                    }
                });
        }

        function refreshReports() {
            fetch('../ajax/ajax_get_reports.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    }
                });
        }

        function refreshUsers() {
            fetch('../ajax/ajax_get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') return;
                    const tbody = document.querySelector('#users tbody');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    data.users.forEach(u => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><input type="checkbox" class="user-checkbox" value="${u.id}"></td>
                            <td>${u.id}</td>
                            <td>${escapeHtml(u.name || '')}</td>
                            <td>${escapeHtml(u.username || '')}</td>
                            <td>
                                ${u.is_banned == 1 ? '<span class="status-badge status-banned">Banned</span>' : (u.is_muted == 1 ? '<span class="status-badge status-muted">Muted</span>' : '<span class="status-badge status-active">Active</span>')}
                            </td>
                            <td>${u.is_admin == 1 ? '<span class="status-badge status-admin">Admin</span>' : '<span class="status-badge">User</span>'}</td>
                            <td>${formatPHDate(u.created_at, 'date')}</td>
                            <td>${u.is_online == 1 ? '<span style="color:#10B981;font-weight:bold;">🟢 Online</span>' : (u.last_activity ? '⚫ ' + formatPHDate(u.last_activity, 'datetime') : '<span style="color:#9CA3AF;">⚫ Offline</span>')}</td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                    ${u.is_admin == 1 ? (window.currentUserId == u.id ? '<span class="btn btn-sm" style="background:#d4af37;color:#111;cursor:default;">Master Account</span>' : `<button class="btn btn-sm btn-warning" onclick="demoteAdmin(${u.id})">Demote</button>`) : `<button class=\"btn btn-sm btn-success\" onclick=\"promoteAdmin(${u.id})\">Promote</button>`}
                                    ${u.is_muted == 1 ? `<button class="btn btn-sm btn-success" onclick="unmuteUser(${u.id})">Unmute</button>` : `<button class="btn btn-sm btn-warning" onclick="muteUser(${u.id})">Mute</button>`}
                                    ${u.is_banned == 1 ? `<button class="btn btn-sm btn-success" onclick="unbanUser(${u.id})">Unban</button>` : `<button class="btn btn-sm btn-warning" onclick="banUser(${u.id})">Ban</button>`}
                                    <button class="btn btn-sm btn-info" onclick="resetPassword(${u.id})">Reset PW</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.id})">Delete</button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        }

        function escapeHtml(str){
            return (str||'').replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
        }

        function formatPHDate(dateStr, mode){
            try {
                const d = new Date(dateStr);
                const opts = mode==='date' ? {timeZone:'Asia/Manila', month:'short', day:'numeric', year:'numeric'} : {
                    timeZone:'Asia/Manila', month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true
                };
                return d.toLocaleString('en-US', opts);
            } catch(e){ return dateStr || ''; }
        }

        function saveSettings() {
            const form = document.getElementById('settingsForm');
            const formData = new FormData(form);
            
            fetch('../ajax/ajax_save_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Settings saved successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function createBackup() {
            const backupType = document.getElementById('backupType').value;
            performAction('create_backup', { type: backupType });
        }

        function restoreBackup() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.sql';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('backup_file', file);
                    
                    fetch('../ajax/ajax_restore_backup.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Backup restored successfully');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            };
            input.click();
        }

        function enableMaintenance() {
            const message = document.getElementById('maintenanceMessage').value;
            performAction('enable_maintenance', { message: message });
        }

        function disableMaintenance() {
            performAction('disable_maintenance', {});
        }

        function exportAnalytics() {
            window.open('export_analytics.php', '_blank');
        }

        function broadcastMessage() {
            const message = document.getElementById('broadcastMessage').value.trim();
            const type = document.getElementById('broadcastType').value;
            const duration = document.getElementById('broadcastDuration').value;

            if (!message) {
                alert('Please enter a message');
                return;
            }

            if (!confirm('Are you sure you want to broadcast this message to all users?')) {
                return;
            }

            const form = new FormData();
            form.append('message', message);
            form.append('type', type);
            form.append('duration', duration);

            fetch('../ajax/ajax_broadcast_message.php', {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Message broadcasted successfully');
                    hideModal('broadcastModal');
                    document.getElementById('broadcastMessage').value = '';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error: ' + error);
            });
        }

        // Generic action performer
        function performAction(action, data) {
            const form = new FormData();
            form.append('action', action);
            
            for (const [key, value] of Object.entries(data)) {
                form.append(key, value);
            }
            
            fetchJSON('ajax_master_actions.php', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
            .then(data => {
                if (data.status === 'success') {
                    showToast('Action completed successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network error: ' + error.message, 'error');
            });
        }

        // Additional missing functions
        function promoteSelectedUser() {
            const userId = document.getElementById('promoteUserId').value;
            if (userId) {
                promoteAdmin(userId);
                hideModal('promoteModal');
            }
        }

        function refreshUsers() {
            showToast('Refreshing users...', 'info');
            setTimeout(() => location.reload(), 1000);
        }

        function refreshReports() {
            showToast('Refreshing reports...', 'info');
            setTimeout(() => location.reload(), 1000);
        }

        function pinMessage(messageId) {
            if (!confirm('Pin this message?')) return;
            performAction('pin_message', { message_id: messageId });
        }

        function unpinMessage(messageId) {
            if (!confirm('Unpin this message?')) return;
            performAction('unpin_message', { message_id: messageId });
        }

        function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;
            performAction('delete_message', { message_id: messageId });
        }

        function restoreMessage(messageId) {
            if (!confirm('Restore this message?')) return;
            performAction('restore_message', { message_id: messageId });
        }

        function viewMessage(messageId) {
            window.open(`view_message.php?id=${messageId}`, '_blank');
        }

        function resetPassword(userId) {
            if (!confirm('Reset password for this user?')) return;
            performAction('reset_password', { user_id: userId });
        }

        function deleteUser(userId) {
            if (!confirm('Permanently delete this user?')) return;
            performAction('delete_user', { user_id: userId });
        }

        function resolveReport(reportId) {
            if (!confirm('Mark this report as resolved?')) return;
            performAction('resolve_report', { report_id: reportId });
        }

        function createBackup() {
            const backupType = document.getElementById('backupType').value;
            showToast('Creating backup...', 'info');
            performAction('create_backup', { type: backupType });
        }

        function restoreBackup() {
            if (!confirm('Restore from backup? This will overwrite current data!')) return;
            showToast('Restoring backup...', 'warning');
            performAction('restore_backup', {});
        }

        function enableMaintenance() {
            const message = document.getElementById('maintenanceMessage').value;
            performAction('enable_maintenance', { message: message });
        }

        function disableMaintenance() {
            performAction('disable_maintenance', {});
        }

        function broadcastMessage() {
            const type = document.getElementById('broadcastType').value;
            const message = document.getElementById('broadcastMessage').value;
            const duration = document.getElementById('broadcastDuration').value;
            
            if (!message) {
                showToast('Please enter a message', 'warning');
                return;
            }
            
            performAction('broadcast_message', { 
                type: type, 
                message: message, 
                duration: duration 
            });
            hideModal('broadcastModal');
        }

        // Load analytics on tab switch
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial analytics
            loadAnalytics();
        });

        function loadAnalytics() {
            fetch('../ajax/ajax_get_analytics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('dailyUsers').textContent = data.analytics.daily_users || 0;
                        document.getElementById('dailyMessages').textContent = data.analytics.daily_messages || 0;
                        document.getElementById('avgSessionTime').textContent = data.analytics.avg_session_time || '0m';
                        document.getElementById('bounceRate').textContent = (data.analytics.bounce_rate || 0) + '%';
                    }
                });
        }

        // Load access logs when security tab is shown
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked nav tab
            event.target.classList.add('active');
            
            // Load specific data for certain tabs
            if (tabName === 'security') {
                loadAccessLogs();
            } else if (tabName === 'analytics') {
                loadAnalytics();
            }
        }

        function loadAccessLogs() {
            fetch('../ajax/ajax_get_access_logs.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateAccessLogTable(data.logs);
                    }
                });
        }

        function updateAccessLogTable(logs) {
            const tbody = document.getElementById('accessLogTable');
            tbody.innerHTML = '';
            
            logs.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${log.ip_address}</td>
                    <td>${log.user_agent.substring(0, 50)}...</td>
                    <td>${new Date(log.last_activity).toLocaleString()}</td>
                    <td><span class="status-badge ${log.is_banned ? 'status-banned' : 'status-active'}">${log.is_banned ? 'Banned' : 'Active'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="banIP('${log.ip_address}')">Ban IP</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function banIP(ipAddress) {
            if (!confirm(`Ban IP address ${ipAddress}?`)) return;
            performAction('ban_ip', { ip_address: ipAddress });
        }
    </script>
</body>
</html>
