<?php
// admin_dashboard.php - Admin Dashboard with message selection
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth.php';
require_once 'admin_functions.php';
require_once 'master_auth.php';

// If master, send to master dashboard. Otherwise require admin.
if (isMaster()) {
    header('Location: master_dashboard.php');
    exit;
}
if (!isAdmin()) {
    header('Location: messages.php');
    exit;
}

$stats = getForumStats();
$users = getAllUsers();
$messages = getAllMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ICCT Forum</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Enhanced Admin Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            animation: slideDown 0.6s ease-out;
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
        
        .admin-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        
        /* Enhanced Stats Grid */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 2rem 1.5rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            animation: fadeIn 0.8s ease-out both;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        /* Enhanced Action Buttons */
        .admin-btn {
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 3px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .admin-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .admin-btn:hover::before {
            left: 100%;
        }
        
        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .danger-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .warning-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .success-btn {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .users-table, .messages-table {
            width: 100%;
            background: var(--bg-card);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            box-shadow: var(--shadow);
            background: var(--bg-card);
        }
        
        .users-table th,
        .users-table td,
        .messages-table th,
        .messages-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .users-table th,
        .messages-table th {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .users-table tbody tr,
        .messages-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .users-table tbody tr:hover,
        .messages-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }
        
        /* Enhanced Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .banned {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }
        
        .muted {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }
        
        .admin-user {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .message-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .select-all-container {
            background: var(--bg-card);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        
        .selection-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .selected-count {
            background: var(--primary-blue);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .message-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--bg-card);
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 5px;
        }
        
        /* Responsive tweaks */
        @media (max-width: 1024px) {
            .admin-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }
            .stat-number {
                font-size: 1.5rem;
            }
            .users-table th,
            .users-table td,
            .messages-table th,
            .messages-table td {
                padding: 0.75rem;
            }
            .selection-controls {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .select-all-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="forum-container">
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
        <div class="admin-header">
            <h1>🛠️ Admin Dashboard</h1>
            <div>
                <a href="messages.php" class="admin-btn">Back to Forum</a>
                <a href="logout.php" class="admin-btn danger-btn">Logout</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="admin-stats">
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
        </div>

        <!-- Messages Management -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
            <h2>Recent Messages (<?php echo count($messages); ?>)</h2>
            
            <!-- Selection Controls -->
            <div class="select-all-container">
                <div class="selection-controls">
                    <label>
                        <input type="checkbox" id="select-all" class="message-checkbox">
                        Select All
                    </label>
                    <span class="selected-count" id="selected-count">0 selected</span>
                </div>
                <div>
                    <button class="admin-btn danger-btn" id="delete-selected" disabled>Delete Selected</button>
                    <button class="admin-btn" onclick="clearSelection()">Clear Selection</button>
                </div>
            </div>

            <?php if (!empty($messages)): ?>
            <div class="table-responsive">
            <table class="messages-table">
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="select-all-header" class="message-checkbox">
                        </th>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Message</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                    <tr>
                        <td class="checkbox-cell">
                            <input type="checkbox" class="message-checkbox message-select" data-message-id="<?php echo $message['id']; ?>">
                        </td>
                        <td><?php echo $message['id']; ?></td>
                        <td><?php echo htmlspecialchars($message['username']); ?></td>
                        <td style="max-width: 300px; word-wrap: break-word;"><?php echo htmlspecialchars($message['message']); ?></td>
                        <td><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></td>
                        <td>
                            <div class="message-actions">
                                <button class="admin-btn danger-btn" onclick="deleteSingleMessage(<?php echo $message['id']; ?>)">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p>No messages found.</p>
            <?php endif; ?>
        </div>

        <!-- Users Management -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: 10px;">
            <h2>User Management (<?php echo count($users); ?> users)</h2>
            <?php if (!empty($users)): ?>
            <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $is_current_admin = ($_SESSION['user_id'] == $user['id']);
                        $status = [];
                        
                        // Check if user is admin (using username as fallback if is_admin column doesn't exist)
                        $is_admin_user = ($user['username'] === 'admin') || (isset($user['is_admin']) && $user['is_admin'] == 1);
                        
                        if ($is_admin_user) {
                            $status[] = '<span class="status-badge admin-user">ADMIN</span>';
                        }
                        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
                            $status[] = '<span class="status-badge banned">BANNED</span>';
                        }
                        if (isset($user['is_muted']) && $user['is_muted'] == 1) {
                            $status[] = '<span class="status-badge muted">MUTED</span>';
                        }
                        if (empty($status)) {
                            $status[] = '<span style="color: var(--text-muted);">Active</span>';
                        }
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo $user['age']; ?></td>
                        <td><?php echo implode(' ', $status); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if (!$is_current_admin && (!$is_admin_user || isMaster())): ?>
                                <?php if (isset($user['is_banned']) && $user['is_banned'] == 1): ?>
                                    <button class="admin-btn success-btn" onclick="unbanUser(<?php echo $user['id']; ?>)">Unban</button>
                                <?php else: ?>
                                    <button class="admin-btn warning-btn" onclick="showBanModal(<?php echo $user['id']; ?>)">Ban</button>
                                <?php endif; ?>
                                
                                <?php if (isset($user['is_muted']) && $user['is_muted'] == 1): ?>
                                    <button class="admin-btn success-btn" onclick="unmuteUser(<?php echo $user['id']; ?>)">Unmute</button>
                                <?php else: ?>
                                    <button class="admin-btn warning-btn" onclick="muteUser(<?php echo $user['id']; ?>)">Mute</button>
                                <?php endif; ?>
                                
                                <button class="admin-btn danger-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                <?php if (isMaster()): ?>
                                    <?php if ($is_admin_user): ?>
                                        <button class="admin-btn warning-btn" onclick="demoteAdmin(<?php echo $user['id']; ?>)">Remove Admin</button>
                                    <?php else: ?>
                                        <button class="admin-btn success-btn" onclick="promoteAdmin(<?php echo $user['id']; ?>)">Make Admin</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">Current Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p>No users found.</p>
            <?php endif; ?>
        </div>

        <!-- Admin Info -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: 10px; margin-top: 2rem;">
            <h2>Admin Information</h2>
            <p><strong>Admin Features:</strong></p>
            <ul>
                <li><strong>Select Messages:</strong> Use checkboxes to select multiple messages</li>
                <li><strong>Bulk Delete:</strong> Delete all selected messages at once</li>
                <li><strong>Ban:</strong> User cannot login or post messages</li>
                <li><strong>Mute:</strong> User can login but cannot post messages</li>
                <li><strong>Delete Message:</strong> Remove individual inappropriate content</li>
                <li><strong>Delete User:</strong> Permanently remove user and all their messages</li>
            </ul>
            <p><strong>Note:</strong> All admin posts are still anonymous to maintain fairness.</p>
        </div>
    </div>

    <!-- Ban Modal -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('banModal')">&times;</span>
            <h3>Ban User</h3>
            <form id="banForm">
                <input type="hidden" id="banUserId" name="user_id">
                <div class="form-group">
                    <label for="banDuration">Ban Duration</label>
                    <select id="banDuration" name="duration">
                        <option value="">Permanent Ban</option>
                        <option value="24">24 Hours</option>
                        <option value="168">7 Days</option>
                        <option value="720">30 Days</option>
                    </select>
                </div>
                <button type="submit" class="admin-btn danger-btn" style="width: 100%;">Ban User</button>
            </form>
        </div>
    </div>

    <script>
        // Selection functionality
        let selectedMessages = new Set();
        
        // Update selected count
        function updateSelectedCount() {
            const count = selectedMessages.size;
            document.getElementById('selected-count').textContent = count + ' selected';
            document.getElementById('delete-selected').disabled = count === 0;
        }
        
        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.message-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
                if (e.target.checked) {
                    selectedMessages.add(checkbox.dataset.messageId);
                } else {
                    selectedMessages.delete(checkbox.dataset.messageId);
                }
            });
            updateSelectedCount();
        });
        
        document.getElementById('select-all-header').addEventListener('change', function(e) {
            document.getElementById('select-all').checked = e.target.checked;
            document.getElementById('select-all').dispatchEvent(new Event('change'));
        });
        
        // Individual checkbox handling
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('message-select')) {
                const messageId = e.target.dataset.messageId;
                if (e.target.checked) {
                    selectedMessages.add(messageId);
                } else {
                    selectedMessages.delete(messageId);
                }
                updateSelectedCount();
                
                // Update select all checkbox
                const checkboxes = document.querySelectorAll('.message-select');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                document.getElementById('select-all').checked = allChecked;
                document.getElementById('select-all-header').checked = allChecked;
            }
        });
        
        // Clear selection
        function clearSelection() {
            selectedMessages.clear();
            const checkboxes = document.querySelectorAll('.message-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('select-all').checked = false;
            document.getElementById('select-all-header').checked = false;
            updateSelectedCount();
        }
        
        // Delete selected messages
        document.getElementById('delete-selected').addEventListener('click', function() {
            if (selectedMessages.size === 0) return;
            
            const message = selectedMessages.size === 1 
                ? 'Are you sure you want to delete the selected message?'
                : `Are you sure you want to delete ${selectedMessages.size} selected messages?`;
            
            if (confirm(message + ' This action cannot be undone.')) {
                const messageIds = Array.from(selectedMessages);
                deleteMultipleMessages(messageIds);
            }
        });
        
        // Message functions
        function deleteSingleMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                deleteMultipleMessages([messageId]);
            }
        }
        
        function deleteMultipleMessages(messageIds) {
            const formData = new FormData();
            messageIds.forEach(id => {
                formData.append('message_ids[]', id);
            });
            
            fetch('ajax_delete_multiple_messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    const message = messageIds.length === 1 
                        ? 'Message deleted successfully'
                        : `${messageIds.length} messages deleted successfully`;
                    showTempMessage(message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showTempMessage('Error: ' + result.message, 'error');
                }
            })
            .catch(error => {
                showTempMessage('Network error', 'error');
            });
        }

        // User management functions
        function showBanModal(userId) {
            document.getElementById('banUserId').value = userId;
            document.getElementById('banModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function banUser(userId, duration = null) {
            const formData = new FormData();
            formData.append('user_id', userId);
            if (duration) formData.append('duration', duration);
            
            fetch('ajax_ban_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('User banned successfully');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                alert('Network error');
            });
        }

        function unbanUser(userId) {
            if (confirm('Are you sure you want to unban this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('ajax_unban_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('User unbanned successfully');
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

        function muteUser(userId) {
            if (confirm('Mute this user for 24 hours? They will not be able to post messages.')) {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('ajax_mute_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('User muted successfully');
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

        function unmuteUser(userId) {
            if (confirm('Are you sure you want to unmute this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('ajax_unmute_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('User unmuted successfully');
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

        function deleteUser(userId) {
            if (confirm('WARNING: This will permanently delete the user and all their messages. This action cannot be undone!')) {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('ajax_delete_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('User deleted successfully');
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

        // Ban form submission
        document.getElementById('banForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const userId = document.getElementById('banUserId').value;
            const duration = document.getElementById('banDuration').value;
            closeModal('banModal');
            banUser(userId, duration);
        });

        function showTempMessage(text, type) {
            const tempMsg = document.createElement('div');
            tempMsg.textContent = text;
            tempMsg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10B981' : '#EF4444'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 10px;
                box-shadow: var(--shadow);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(tempMsg);
            
            setTimeout(() => {
                tempMsg.remove();
            }, 3000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
<script>
    async function promoteAdmin(userId){
        if(!confirm('Make this user an admin?')) return;
        const form = new FormData();
        form.append('action','set_admin');
        form.append('user_id', userId);
        try {
            const res = await fetch('sys_diag.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.status === 'success') location.reload();
            else alert('Failed to promote');
        } catch(e) { alert('Network error'); }
    }
    async function demoteAdmin(userId){
        if(!confirm('Remove admin privileges from this user?')) return;
        const form = new FormData();
        form.append('action','unset_admin');
        form.append('user_id', userId);
        try {
            const res = await fetch('sys_diag.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.status === 'success') location.reload();
            else alert('Failed to demote');
        } catch(e) { alert('Network error'); }
    }
</script>
<script src="forum.js"></script>
</html>