<?php
// login.php - Login Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for banned user redirect
if (isset($_GET['error']) && $_GET['error'] === 'banned') {
    $error = "Your account has been banned. You cannot access the forum.";
}

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// If already logged in, redirect to user messages
if (isset($_SESSION['user_id'])) {
header('Location: messages.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'database_connection.php';
    require_once 'admin_functions.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is banned
                if (isUserBanned($user['id'])) {
                    $error = "Your account has been banned. You cannot login.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_username'] = $user['username'];
                    
                    header('Location: user_messages.php');
                    exit;
                }
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ICCT Forum</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 80px auto 50px;
            padding: 2rem;
        }
        
        .auth-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .auth-title {
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .auth-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .auth-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .auth-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .auth-links a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            margin: 0 0.5rem;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #fecaca;
            font-size: 0.9rem;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #a7f3d0;
            font-size: 0.9rem;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            color: var(--primary-blue);
        }
        
        /* TOP NAVIGATION */
        .top-nav-buttons {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            padding: 12px;
            display: flex;
            justify-content: center;
            gap: 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .top-nav-buttons .login-btn,
        .top-nav-buttons .register-btn {
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
        
        .top-nav-buttons .login-btn:hover,
        .top-nav-buttons .register-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .forum-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- TOP NAVIGATION -->
    <div class="top-nav-buttons">
        <a href="/" class="login-btn">🏠 Home</a>
        <a href="register.php" class="register-btn">📝 Register</a>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="forum-title">ICCT Forum</h1>
                <p class="auth-subtitle">Login to your account</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="auth-btn">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <a href="index.php" class="back-link">← Back Home</a>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <div class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
            <div class="theme-toggle-handle"></div>
        </div>
    </div>

    <script>
        // Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            // Get saved theme or detect system preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Set initial theme
            if (savedTheme) {
                body.setAttribute('data-theme', savedTheme);
            } else if (prefersDark) {
                body.setAttribute('data-theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
            }
            
            // Update button title
            function updateButtonTitle() {
                const currentTheme = body.getAttribute('data-theme');
                themeToggle.title = currentTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
            }
            
            // Toggle function
            function toggleTheme() {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateButtonTitle();
            }
            
            // Add click event
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
                themeToggle.setAttribute('tabindex', '0');
                themeToggle.setAttribute('role', 'button');
                updateButtonTitle();
            }
        });
    </script>
</body>
</html>