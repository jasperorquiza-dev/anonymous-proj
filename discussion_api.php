<?php
require_once 'philippines_time.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_time':
        echo json_encode(PhilippinesTime::getTimeForJS());
        break;
        
    case 'get_messages':
        // Return empty messages array - messages will be loaded dynamically
        $messages = [];
        
        echo json_encode([
            'status' => 'success',
            'messages' => $messages,
            'current_time' => PhilippinesTime::getTimeForJS()
        ]);
        break;
        
    case 'add_message':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['author']) && isset($input['content'])) {
                $message = [
                    'id' => time(), // Simple ID generation
                    'author' => htmlspecialchars($input['author']),
                    'content' => htmlspecialchars($input['content']),
                    'timestamp' => PhilippinesTime::getCurrentTime(),
                    'time_ago' => 'Just now'
                ];
                
                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'current_time' => PhilippinesTime::getTimeForJS()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }
        break;
        
    case 'get_server_status':
        echo json_encode([
            'status' => 'online',
            'server_time' => PhilippinesTime::getTimeForJS(),
            'timezone' => 'Asia/Manila',
            'offset' => '+08:00'
        ]);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        break;
}
?>
