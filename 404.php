<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - 404</title>
    <link rel="icon" type="image/png" href="assets/img/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #0f172a, #111827);
            color: #e2e8f0;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            padding: 48px 40px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(15, 23, 42, 0.5);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 12px;
            letter-spacing: -1px;
            color: #3b82f6;
            background: linear-gradient(135deg, #3b82f6, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #cbd5f5;
            margin-bottom: 28px;
        }
        a {
            display: inline-block;
            padding: 12px 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, #3b82f6, #9333ea);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(59, 130, 246, 0.45);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>404</h1>
        <p>The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
        <a href="<?php echo '//' . $_SERVER['HTTP_HOST'] . '/'; ?>">Return to Home</a>
    </div>
</body>
</html>
