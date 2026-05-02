<?php
// welcome.php - Welcome Page (No login required)
// If accessed directly, keep functionality but avoid exposing filename in links
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../core/database_connection.php';
require_once '../admin/admin_functions.php';
require_once '../core/maintenance_check.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user_messages.php');
    exit;
}

// Get forum settings
$forum_settings = getForumSettings();
$forum_title = $forum_settings['forum_title'] ?? 'ICCT Forum';
$forum_logo = $forum_settings['forum_logo'] ?? 'assets/img/icct.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($forum_title); ?> - Welcome</title>
    
    <!-- SEO & Trust Meta Tags -->
    <meta name="description" content="ICCT Forum - Official student discussion platform for ICCT College community. Join discussions, connect with peers, and stay updated.">
    <meta name="keywords" content="ICCT Forum, student forum, ICCT College, educational platform">
    <meta name="author" content="ICCT College">
    <meta name="robots" content="index, follow">
    
    <!-- Security & Trust -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; font-src 'self' https: data:;">
    <meta name="theme-color" content="#001489">
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <?php include '../core/system_messages.php'; ?>
    <style>
        /* Navigation removed - no padding needed */
        .forum-container { padding-top: 20px; }

        .preview-notice {
            background: #fef3cd;
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
            color: #92400e;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            overflow: auto;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-content {
            background: var(--bg-card);
            margin: 8% auto;
            padding: 0;
            border: none;
            border-radius: 20px;
            width: 90%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #001489, #c8102e);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-muted);
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .close:hover,
        .close:focus {
            background: rgba(200, 16, 46, 0.1);
            color: #c8102e;
            transform: rotate(90deg);
        }

        .modal-header {
            margin: 0;
            padding: 32px 32px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(0, 20, 137, 0.03), rgba(200, 16, 46, 0.03));
        }

        .modal-title {
            background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .form-group {
            padding: 0 32px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            background: var(--bg-card);
            color: var(--text-dark);
            box-sizing: border-box;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #001489;
            box-shadow: 0 0 0 4px rgba(0, 20, 137, 0.1);
            transform: translateY(-1px);
        }

        .auth-btn {
            width: calc(100% - 64px);
            margin: 24px 32px;
            padding: 16px;
            background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 20, 137, 0.3);
            position: relative;
            overflow: hidden;
        }

        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .auth-btn:hover::before {
            left: 100%;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 20, 137, 0.4);
        }

        .auth-btn:active {
            transform: translateY(0);
        }

        .auth-links {
            text-align: center;
            padding: 20px 32px 32px;
            border-top: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.02);
        }

        .auth-links a {
            color: #001489;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #fecaca;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #a7f3d0;
        }

        .compact-form .form-group {
            margin-bottom: 12px;
        }

        .compact-form .form-group input {
            padding: 8px 10px;
            font-size: 14px;
        }

        .compact-form .auth-btn {
            padding: 10px;
            font-size: 14px;
        }

        /* Simple Theme Toggle */
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
        
        /* Enhanced Preview Notice */
        .preview-notice {
            background: linear-gradient(135deg, rgba(0, 20, 137, 0.1), rgba(200, 16, 46, 0.1));
            border: 2px solid rgba(0, 20, 137, 0.3);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: center;
            animation: fadeIn 0.8s ease-out 0.4s both;
            backdrop-filter: blur(10px);
        }
        
        .preview-notice strong {
            background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- Header -->
        <header class="forum-header">
            <img src="<?php echo htmlspecialchars($forum_logo); ?>" alt="<?php echo htmlspecialchars($forum_title); ?> Logo" class="school-logo">
            <h1 class="forum-title">Welcome to <?php echo htmlspecialchars($forum_title); ?></h1>
            <p class="forum-subtitle">Live Discussion Preview • Share your thoughts anonymously</p>
            
            <div class="forum-stats">
                <div class="stat-item">
                    <div class="stat-number" id="total-messages">0</div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="online-users">0</div>
                    <div class="stat-label">Online Now</div>
                </div>
            </div>
        </header>

        <!-- Preview Notice -->
        <div class="preview-notice">
            <strong>Preview Mode:</strong> You're viewing discussions as a guest. 
            <a href="javascript:void(0)" onclick="openModal('registerModal')" style="color: var(--primary-blue); font-weight: bold;">Register</a> or 
            <a href="javascript:void(0)" onclick="openModal('loginModal')" style="color: var(--primary-blue); font-weight: bold;">Login</a> to join the conversation!
        </div>

        <!-- Main Content -->
        <div class="forum-content">
            <!-- Messages Section -->
            <main class="messages-section">
                <div class="messages-header">
                    <h2>Live Discussions</h2>
                    <button id="refresh-btn" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                        Refresh
                    </button>
                </div>
                
                <div id="messages-container">
                    <div class="loading">Loading discussions...</div>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="forum-sidebar">
                <div class="info-card">
                    <h3>Welcome Info</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        This welcome page shows real-time discussions from our community. 
                        Register to participate and share your thoughts!
                    </p>
                </div>
                
                <div class="info-card">
                    <h3>Why Join?</h3>
                    <ul class="rules-list">
                        <li>• Speak honestly and anonymously</li>
                        <li>• Share feedback about your classes and experiences</li>
                        <li>• Connect through real stories, not profiles</li>
                        <li>• Help improve the student community from your perspective</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('loginModal')">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Login to <?php echo htmlspecialchars($forum_title); ?></h3>
            </div>
            
            <div id="loginMessage"></div>
            
            <form id="loginForm" autocomplete="on">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <input type="text" id="loginUsername" name="username" autocomplete="username" required>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" autocomplete="current-password" required>
                </div>
                
                <button type="submit" class="auth-btn">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="javascript:void(0)" onclick="switchToRegister()">Register here</a></p>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('registerModal')">&times;</span>
            <div class="modal-header">
                <h3 class="modal-title">Join <?php echo htmlspecialchars($forum_title); ?></h3>
            </div>
            
            <div id="registerMessage"></div>
            
            <form id="registerForm" class="compact-form" autocomplete="on">
                <div class="form-group">
                    <label for="registerName">Full Name</label>
                    <input type="text" id="registerName" name="name" autocomplete="name" required>
                </div>
                
                <div class="form-group">
                    <label for="registerAge">Age</label>
                    <input type="number" id="registerAge" name="age" min="13" max="100" autocomplete="bday" required>
                </div>
                
                <div class="form-group">
                    <label for="registerUsername">Username</label>
                    <input type="text" id="registerUsername" name="username" autocomplete="username" required>
                </div>
                
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" autocomplete="new-password" required>
                </div>
                
                <div class="form-group">
                    <label for="registerConfirmPassword">Confirm Password</label>
                    <input type="password" id="registerConfirmPassword" name="confirm_password" autocomplete="new-password" required>
                </div>
                
                <button type="submit" class="auth-btn">Register</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="javascript:void(0)" onclick="switchToLogin()">Login here</a></p>
            </div>
        </div>
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
        // Load messages immediately when page loads
        async function loadDashboardMessages() {
            const messagesContainer = document.getElementById('messages-container');
            if (!messagesContainer) return;
            
            try {
                const response = await fetch('../ajax/load_messages.php');
                const data = await response.json();
                
                if (data.status === 'success' && data.messages && data.messages.length > 0) {
                    messagesContainer.innerHTML = '';
                    data.messages.forEach((message, index) => {
                        const messageElement = createMessageElement(message);
                        messagesContainer.appendChild(messageElement);
                    });
                    scrollToBottom();
                    
                    // Update message count
                    const totalMessages = document.getElementById('total-messages');
                    if (totalMessages) {
                        totalMessages.textContent = data.count || data.messages.length;
                    }
                } else {
                    messagesContainer.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">💬</div>
                            <h3>No messages yet</h3>
                            <p>Be the first to start a conversation!</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading messages:', error); // Debug log
                messagesContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">💬</div>
                        <h3>Live Discussions</h3>
                        <p>Join the conversation to see messages!</p>
                    </div>
                `;
            }
        }

        function createMessageElement(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message-item';
            
            // COPY EXACTLY from time_test.php working code
            let timeString = 'Just now';
            if (message.created_at) {
                try {
                    const messageTime = new Date(message.created_at);
                    // This is the EXACT code from time_test.php that works
                    timeString = messageTime.toLocaleString('en-US', {
                        timeZone: 'Asia/Manila',
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                } catch (e) {
                    console.error('Time conversion error:', e);
                }
            }
            
            messageDiv.innerHTML = `
                <div class="message-header">
                    <span class="message-username">${message.username || 'Anonymous'}</span>
                    <span class="message-time">${timeString}</span>
                </div>
                <div class="message-content">${message.message || ''}</div>
            `;
            
            return messageDiv;
        }

        // Message times are now static - they show the original posting time

        function scrollToBottom() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTo({
                    top: messagesContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }

        // Navigation removed - no scroll effects needed

        // Theme handled by forum.js

        // Simple modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear messages and forms
            document.getElementById('loginMessage').innerHTML = '';
            document.getElementById('registerMessage').innerHTML = '';
            document.getElementById('loginForm').reset();
            document.getElementById('registerForm').reset();
        }

        function switchToRegister() {
            closeModal('loginModal');
            setTimeout(() => openModal('registerModal'), 100);
        }

        function switchToLogin() {
            closeModal('registerModal');
            setTimeout(() => openModal('loginModal'), 100);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                // Clear messages and forms
                document.getElementById('loginMessage').innerHTML = '';
                document.getElementById('registerMessage').innerHTML = '';
                document.getElementById('loginForm').reset();
                document.getElementById('registerForm').reset();
            }
        }

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
            
            try {
                const response = await fetch('../ajax/ajax_login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('loginMessage').innerHTML = 
                        '<div class="success-message">' + result.message + '</div>';
                    
                    setTimeout(() => {
                        window.location.href = 'messages.php';
                    }, 1000);
                    
                } else {
                    document.getElementById('loginMessage').innerHTML = 
                        '<div class="error-message">' + result.message + '</div>';
                }
            } catch (error) {
                console.error('Login error:', error);
                document.getElementById('loginMessage').innerHTML = 
                    '<div class="error-message">Network error. Please try again.</div>';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        // Register form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';
            
            try {
                const response = await fetch('../ajax/ajax_register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('registerMessage').innerHTML = 
                        '<div class="success-message">' + result.message + '</div>';
                    
                    setTimeout(() => {
                        closeModal('registerModal');
                        openModal('loginModal');
                        document.getElementById('loginMessage').innerHTML = 
                            '<div class="success-message">Registration successful! Please login.</div>';
                    }, 1500);
                    
                } else {
                    document.getElementById('registerMessage').innerHTML = 
                        '<div class="error-message">' + result.message + '</div>';
                }
            } catch (error) {
                console.error('Register error:', error);
                document.getElementById('registerMessage').innerHTML = 
                    '<div class="error-message">Network error. Please try again.</div>';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        // Auto-refresh messages every 10 seconds for live preview
        function startLivePreview() {
            loadDashboardMessages(); // Load immediately
            setInterval(loadDashboardMessages, 10000); // Refresh every 10 seconds
        }

        // Refresh button
        document.getElementById('refresh-btn').addEventListener('click', function() {
            loadDashboardMessages();
        });

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Theme handled by forum.js
            // Start live preview
            startLivePreview();
            
            // Update online status
            fetch('../ajax/update_online_status.php');
        });
    </script>
</body>
</html>
