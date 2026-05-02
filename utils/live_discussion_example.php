<?php
require_once 'philippines_time.php';

// Set headers for real-time updates
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Discussion - Philippines Time</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .live-indicator {
            display: inline-block;
            background: #ff4757;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .discussion-area {
            padding: 20px;
        }
        
        .message {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 10px 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .message-author {
            font-weight: bold;
            color: #667eea;
        }
        
        .message-time {
            font-size: 0.9em;
            color: #666;
        }
        
        .message-content {
            line-height: 1.6;
        }
        
        .new-message-form {
            background: #f8f9fa;
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .status-bar {
            background: #e9ecef;
            padding: 10px 20px;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #666;
        }
        
        .online-users {
            float: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Live Community Discussion</h1>
            <div class="live-indicator">🔴 LIVE</div>
            <div id="philippines-clock"></div>
        </div>
        
        <div class="discussion-area" id="discussion-area">
            <!-- Messages will be loaded dynamically via JavaScript -->
            <div class="message">
                <div class="message-header">
                    <span class="message-author">System</span>
                    <span class="message-time" id="welcome-time">Loading...</span>
                </div>
                <div class="message-content">Welcome to our live discussion! All times are displayed in Philippines Standard Time (GMT+8).</div>
            </div>
        </div>
        
        <div class="new-message-form">
            <form id="message-form">
                <div class="form-group">
                    <label for="author">Your Name:</label>
                    <input type="text" id="author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="3" required placeholder="Share your thoughts..."></textarea>
                </div>
                <button type="submit" class="btn">Send Message</button>
            </form>
        </div>
        
        <div class="status-bar">
            <span>Current Philippines Time: <span id="current-time"><?php echo PhilippinesTime::getHumanReadableTime(); ?></span></span>
            <span class="online-users">👥 127 users online</span>
        </div>
    </div>

    <!-- Include the real-time clock script -->
    <script src="realtime_clock.js"></script>
    
    <script>
        // Initialize the Philippines real-time clock
        const clock = new PhilippinesRealTimeClock('philippines-clock', {
            showSeconds: true,
            showDate: true,
            showTimezone: true,
            format: '12'
        });
        
        // Update current time display with real-time
        function updateCurrentTime() {
            const timeInfo = clock.getCurrentTime();
            document.getElementById('current-time').textContent = timeInfo.formatted + ' - ' + timeInfo.date;
        }
        
        // Update welcome message time with real-time
        function updateWelcomeTime() {
            const now = new Date();
            const philippinesTime = now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('welcome-time').textContent = philippinesTime;
        }
        
        // Update time every second
        setInterval(updateCurrentTime, 1000);
        setInterval(updateWelcomeTime, 1000);
        updateWelcomeTime();
        
        // Handle message form submission
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const author = document.getElementById('author').value;
            const message = document.getElementById('message').value;
            
            if (author && message) {
                addMessage(author, message);
                document.getElementById('message').value = '';
            }
        });
        
        // Add new message to discussion
        function addMessage(author, content) {
            const discussionArea = document.getElementById('discussion-area');
            const now = new Date();
            const timestamp = now.getTime();
            
            // Use EXACT same logic as time_test.php
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
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message';
            messageDiv.innerHTML = `
                <div class="message-header">
                    <span class="message-author">${author}</span>
                    <span class="message-time" data-timestamp="${timestamp}">${timeString}</span>
                </div>
                <div class="message-content">${content}</div>
            `;
            
            discussionArea.appendChild(messageDiv);
            discussionArea.scrollTop = discussionArea.scrollHeight;
        }
        
        // Message timestamps are now static - they show the original posting time
        
        // Auto-scroll to bottom when new messages arrive
        const discussionArea = document.getElementById('discussion-area');
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    discussionArea.scrollTop = discussionArea.scrollHeight;
                }
            });
        });
        
        observer.observe(discussionArea, { childList: true });
    </script>
</body>
</html>
