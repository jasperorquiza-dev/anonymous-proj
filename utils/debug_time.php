<?php
require_once 'philippines_time.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Time Display</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .debug-box { background: white; padding: 20px; margin: 10px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .working { border-left: 4px solid #4CAF50; }
        .broken { border-left: 4px solid #f44336; }
        .test { border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <h1>Time Display Debug</h1>
    <div class="debug-box working">
        <h3>✅ Working Time (from time_test.php logic)</h3>
        <p><strong>PHP Server Time:</strong> <?php echo PhilippinesTime::getHumanReadableTime(); ?></p>
        <p><strong>Formatted:</strong> <?php echo PhilippinesTime::getCurrentTime(); ?></p>
    </div>
    <div class="debug-box test">
        <h3>🧪 Test Message Time Display</h3>
        <div id="test-message">
            <div class="message-item">
                <div class="message-header">
                    <span class="message-username">Test User</span>
                    <span class="message-time" id="test-time">Loading...</span>
                </div>
                <div class="message-content">This is a test message to check time display</div>
            </div>
        </div>
    </div>
    <div class="debug-box test">
        <h3>🧪 Test Current Time Display</h3>
        <p><strong>Current Time (JavaScript):</strong> <span id="current-time">Loading...</span></p>
        <p><strong>Message Time (JavaScript):</strong> <span id="message-time">Loading...</span></p>
    </div>
    <script>
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        function updateMessageTime() {
            const messageTime = new Date(Date.now() - 5 * 60 * 1000);
            const timeString = messageTime.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('message-time').textContent = timeString;
            document.getElementById('test-time').textContent = timeString;
        }
        updateCurrentTime();
        updateMessageTime();
        setInterval(updateCurrentTime, 1000);
        setInterval(updateMessageTime, 1000);
    </script>
</body>
</html>