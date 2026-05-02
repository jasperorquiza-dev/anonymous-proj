<?php
require_once 'philippines_time.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippines Time Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .time-display {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        .server-time {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .client-time {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
        }
        .live-clock {
            background: #e8f5e8;
            border-left: 4px solid #4caf50;
        }
        h2 {
            margin-top: 0;
            color: #333;
        }
        .time-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Philippines Time Test</h1>
    <div class="time-display server-time">
        <h2>Server Time (PHP)</h2>
        <div class="time-value"><?php echo PhilippinesTime::getHumanReadableTime(); ?></div>
        <div>Formatted: <?php echo PhilippinesTime::getCurrentTime(); ?></div>
        <div>ISO: <?php echo PhilippinesTime::now()->format('c'); ?></div>
    </div>
    <div class="time-display client-time">
        <h2>Client Time (JavaScript)</h2>
        <div class="time-value" id="client-time"></div>
        <div id="client-formatted"></div>
        <div id="client-iso"></div>
    </div>
    <div class="time-display live-clock">
        <h2>Live Clock</h2>
        <div id="philippines-clock"></div>
    </div>
    <div class="time-display">
        <h2>Server API Response</h2>
        <pre id="api-response"></pre>
    </div>
    <script src="realtime_clock.js"></script>
    <script>
        const clock = new PhilippinesRealTimeClock('philippines-clock', {
            showSeconds: true,
            showDate: true,
            showTimezone: true,
            format: '12'
        });
        function updateClientTime() {
            const now = new Date();
            document.getElementById('client-time').textContent = now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('client-formatted').textContent = 'Formatted: ' + now.toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            document.getElementById('client-iso').textContent = 'ISO: ' + now.toISOString();
        }
        setInterval(updateClientTime, 1000);
        updateClientTime();
        fetch('philippines_time.php?action=get_time')
            .then(response => response.json())
            .then(data => {
                document.getElementById('api-response').textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('api-response').textContent = 'Error: ' + error.message;
            });
    </script>
</body>
</html>