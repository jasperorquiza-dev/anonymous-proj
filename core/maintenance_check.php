<?php
// maintenance_check.php - Check if forum is in maintenance mode
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../admin/admin_functions.php';

function isMaintenanceMode() {
    try {
        $pdo = getPDO();
        if (!$pdo) return false;
        
        // Create forum_settings table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS forum_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $pdo->prepare("SELECT setting_value FROM forum_settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result && $result['setting_value'] == '1';
    } catch(PDOException $e) {
        return false;
    }
}

function getMaintenanceMessage() {
    try {
        $pdo = getPDO();
        if (!$pdo) return 'Forum is under maintenance. Please check back later.';
        
        $stmt = $pdo->prepare("SELECT setting_value FROM forum_settings WHERE setting_key = 'maintenance_message'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : 'Forum is under maintenance. Please check back later.';
    } catch(PDOException $e) {
        return 'Forum is under maintenance. Please check back later.';
    }
}

// Check maintenance mode and redirect if needed
if (isMaintenanceMode() && !isMaster()) {
    // Force logout all non-master users during maintenance
    if (isset($_SESSION['user_id'])) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
    $message = getMaintenanceMessage();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode - ICCT Forum</title>
        <link rel="icon" type="image/png" href="../assets/img/favicon.png">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: #0a0e27;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
            }
            
            /* Animated background */
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: 
                    radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                    radial-gradient(circle at 80% 80%, rgba(88, 86, 214, 0.3) 0%, transparent 50%),
                    radial-gradient(circle at 40% 20%, rgba(72, 149, 239, 0.2) 0%, transparent 50%);
                animation: gradientShift 15s ease infinite;
            }
            
            @keyframes gradientShift {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.8; transform: scale(1.1); }
            }
            
            .maintenance-container {
                background: rgba(15, 23, 42, 0.9);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(148, 163, 184, 0.1);
                padding: 4rem 3rem;
                border-radius: 24px;
                box-shadow: 
                    0 25px 50px -12px rgba(0, 0, 0, 0.5),
                    0 0 0 1px rgba(255, 255, 255, 0.05),
                    inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
                text-align: center;
                max-width: 600px;
                margin: 2rem;
                position: relative;
                z-index: 10;
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .maintenance-icon {
                width: 120px;
                height: 120px;
                margin: 0 auto 2rem;
                position: relative;
            }
            
            .icon-circle {
                width: 100%;
                height: 100%;
                border-radius: 50%;
                background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                animation: pulse 2s ease-in-out infinite;
                box-shadow: 0 0 40px rgba(0, 20, 137, 0.4);
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); box-shadow: 0 0 40px rgba(0, 20, 137, 0.4); }
                50% { transform: scale(1.05); box-shadow: 0 0 60px rgba(0, 20, 137, 0.6); }
            }
            
            .icon-circle::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
                border: 4px solid white;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                to { transform: translate(-50%, -50%) rotate(360deg); }
            }
            
            .icon-circle::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 8px;
                height: 8px;
                background: white;
                border-radius: 50%;
            }
            
            .maintenance-title {
                font-size: 2.5rem;
                font-weight: 700;
                background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin-bottom: 1rem;
                letter-spacing: -0.02em;
            }
            
            .maintenance-subtitle {
                font-size: 1.1rem;
                color: #94a3b8;
                margin-bottom: 2rem;
                font-weight: 500;
                letter-spacing: 0.02em;
                text-transform: uppercase;
            }
            
            .maintenance-message {
                color: #cbd5e1;
                line-height: 1.8;
                margin-bottom: 2.5rem;
                font-size: 1.05rem;
                max-width: 450px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .button-group {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .refresh-btn {
                background: linear-gradient(135deg, #001489 0%, #c8102e 100%);
                color: white;
                border: none;
                padding: 14px 32px;
                border-radius: 12px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 20, 137, 0.4);
                position: relative;
                overflow: hidden;
            }
            
            .refresh-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            .refresh-btn:hover::before {
                left: 100%;
            }
            
            .refresh-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 25px rgba(0, 20, 137, 0.6);
            }
            
            .refresh-btn:active {
                transform: translateY(0);
            }
            
            .status-indicator {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin-top: 2rem;
                padding: 0.75rem 1.5rem;
                background: rgba(239, 68, 68, 0.1);
                border: 1px solid rgba(239, 68, 68, 0.2);
                border-radius: 50px;
                color: #fca5a5;
                font-size: 0.9rem;
                font-weight: 500;
            }
            
            .status-dot {
                width: 8px;
                height: 8px;
                background: #ef4444;
                border-radius: 50%;
                animation: blink 2s ease-in-out infinite;
            }
            
            @keyframes blink {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.3; }
            }
            
            /* Floating particles */
            .particle {
                position: absolute;
                width: 4px;
                height: 4px;
                background: rgba(0, 20, 137, 0.5);
                border-radius: 50%;
                animation: float 20s infinite;
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 1; }
                100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
            }
            
            .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
            .particle:nth-child(2) { left: 20%; animation-delay: 2s; }
            .particle:nth-child(3) { left: 30%; animation-delay: 4s; }
            .particle:nth-child(4) { left: 40%; animation-delay: 6s; }
            .particle:nth-child(5) { left: 50%; animation-delay: 8s; }
            .particle:nth-child(6) { left: 60%; animation-delay: 10s; }
            .particle:nth-child(7) { left: 70%; animation-delay: 12s; }
            .particle:nth-child(8) { left: 80%; animation-delay: 14s; }
            .particle:nth-child(9) { left: 90%; animation-delay: 16s; }
            
            @media (max-width: 640px) {
                .maintenance-container {
                    padding: 3rem 2rem;
                }
                .maintenance-title {
                    font-size: 2rem;
                }
                .maintenance-icon {
                    width: 100px;
                    height: 100px;
                }
            }
        </style>
    </head>
    <body>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        
        <div class="maintenance-container">
            <div class="maintenance-icon">
                <div class="icon-circle"></div>
            </div>
            
            <h1 class="maintenance-title">Under Maintenance</h1>
            <p class="maintenance-subtitle">System Upgrade in Progress</p>
            <p class="maintenance-message"><?php echo htmlspecialchars($message); ?></p>
            
            <div class="button-group">
                <button class="refresh-btn" onclick="location.reload()">Refresh Page</button>
            </div>
            
            <div class="status-indicator">
                <span class="status-dot"></span>
                <span>Service Temporarily Unavailable</span>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
