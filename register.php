<?php
// register.php - Registration Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to user messages
if (isset($_SESSION['user_id'])) {
header('Location: messages.php');
    exit;
}

// Include database connection
require_once 'core/database_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($age < 13 || $age > 100) {
        $error = "Age must be between 13 and 100";
    } else {
        try {
            // Check if username exists
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, age, username, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $age, $username, $hashed_password]);
                
                // Redirect to login page with success message
                $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";
                header('Location: login.php');
                exit;
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
    <title>Register - ICCT Forum</title>
    <link rel="icon" type="image/png" href="assets/img/assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .auth-container {
            max-width: 450px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <!-- TOP NAVIGATION -->
    <div class="top-nav-buttons">
        <a href="/" class="login-btn">🏠 Home</a>
        <a href="login.php" class="register-btn">🔐 Login</a>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="forum-title">ICCT Forum</h1>
                <p class="auth-subtitle">Create your account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="13" max="100" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="auth-btn">Register</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <a href="/" class="back-link">← Back Home</a>
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
