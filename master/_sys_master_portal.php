<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../master/master_auth.php';
require_once '../admin/admin_functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_master']);
    header('Location: ../pages/welcome.php');
    exit;
}

// Login form
if (!isMaster()) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="forum-container">
        <div class="input-section" style="max-width:420px;margin:60px auto;">
            <h2 style="margin-bottom:1rem;">Access</h2>
            <div id="masterMessage" style="margin-bottom:0.75rem;"></div>
            <form id="masterForm" autocomplete="on">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="auth-btn">Sign in</button>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('masterForm').addEventListener('submit', async function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const btn = this.querySelector('button[type="submit"]');
        const msg = document.getElementById('masterMessage');
        btn.disabled = true; btn.textContent = 'Signing in...';
        try {
            const res = await fetch('res_bundle.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'sys_diag.php';
            } else {
                msg.textContent = data.message || 'Invalid credentials';
            }
        } catch(e){ msg.textContent = 'Network error'; }
        finally { btn.disabled = false; btn.textContent = 'Sign in'; }
    });
    </script>
</body>
</html>
<?php
    exit;
}

// Actions for admin promotion/demotion
$action = $_POST['action'] ?? '';
if ($action === 'set_admin' && isset($_POST['user_id'])) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    } catch (Throwable $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed']);
        exit;
    }
}

if ($action === 'unset_admin' && isset($_POST['user_id'])) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    } catch (Throwable $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed']);
        exit;
    }
}

// Load users for display
try {
    $pdo = getPDO();
    $users = $pdo->query("SELECT id, name, username, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="forum-container">
        <div class="admin-header">
            <h1>Access Control</h1>
            <div>
                <a href="sys_diag.php?logout=1" class="admin-btn danger-btn">Logout</a>
            </div>
        </div>

        <div class="info-card">
            <h3>Users (<?php echo count($users); ?>)</h3>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo ($u['is_admin'] == 1) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <?php if ($u['is_admin'] == 1): ?>
                                    <button class="admin-btn warning-btn" onclick="toggleAdmin(<?php echo $u['id']; ?>, false)">Remove admin</button>
                                <?php else: ?>
                                    <button class="admin-btn success-btn" onclick="toggleAdmin(<?php echo $u['id']; ?>, true)">Make admin</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    async function toggleAdmin(userId, makeAdmin){
        const form = new FormData();
        form.append('action', makeAdmin ? 'set_admin' : 'unset_admin');
        form.append('user_id', userId);
        const res = await fetch('sys_diag.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.status === 'success') location.reload();
        else alert('Operation failed');
    }
    </script>
</body>
</html>

