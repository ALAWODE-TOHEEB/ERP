<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/connectserver.php';
// require __DIR__ . '/encryption.php';

// Get request details
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Extract endpoint
$base_path = '/ERP_API/login_api.php';
$endpoint = str_replace($base_path, '', $request_uri);
$endpoint = explode('?', $endpoint)[0]; // Remove query parameters

try {
    // Route requests
    switch ($endpoint) {
        // ==================== AUTHENTICATION ==================== //
        case '/login':
            if ($method === 'POST') {
                handleLogin();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/logout':
            if ($method === 'POST') {
                handleLogout();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        // ==================== USER MANAGEMENT ==================== //
        case '/users':
            if ($method === 'GET') {
                authenticateRequest();
                handleGetUsers();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/user/create':
            if ($method === 'POST') {
                authenticateRequest();
                handleCreateUser();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/user/update':
            if ($method === 'PUT') {
                authenticateRequest();
                handleUpdateUser();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        // ==================== INVENTORY ==================== //
        case '/inventory':
            if ($method === 'GET') {
                authenticateRequest();
                handleGetInventory();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/inventory/add':
            if ($method === 'POST') {
                authenticateRequest();
                handleAddInventory();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        // ==================== REPORTS ==================== //
        case '/reports/sales':
            if ($method === 'GET') {
                authenticateRequest();
                handleSalesReport();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/reports/inventory':
            if ($method === 'GET') {
                authenticateRequest();
                handleInventoryReport();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        // ==================== SYSTEM ==================== //
        case '/system/status':
            if ($method === 'GET') {
                handleSystemStatus();
            } else {
                sendMethodNotAllowed();
            }
            break;
            
        case '/test':
            if ($method === 'GET') {
                echo json_encode(['success' => true, 'message' => 'API is working']);
            }
            break;

        default:
            sendNotFound();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}

// ==================== HELPER FUNCTIONS ==================== //

function sendMethodNotAllowed() {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function sendNotFound() {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}

function authenticateRequest() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    try {
        $data = json_decode(base64_decode($token), true);
        if (empty($data['user_id'])) {
            throw new Exception("Invalid token");
        }
        return $data['user_id'];
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

// ==================== HANDLER FUNCTIONS ==================== //

function handleLogin() {
    global $connr;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception("Username and password are required");
    }

    // $encryptedUsername = encdata($username);
    // $encryptedPassword = encdata($password);
    $encryptedUsername = $username;
    $encryptedPassword = $password;
    $hashedPassword = md5($encryptedPassword);
    $user = explode('@', $encryptedUsername)[0];

    $sql = "SELECT staffid, Password, role FROM usermapping WHERE ISNULL(loginid,'') <> '' AND loginid = ?";
    $stmt = sqlsrv_query($connr, $sql, array($user));
    
    if (sqlsrv_has_rows($stmt) && sqlsrv_fetch($stmt)) {
        $dbPassword = sqlsrv_get_field($stmt, 1);
        $staffid = sqlsrv_get_field($stmt, 0);
        $role = sqlsrv_get_field($stmt, 2);
        
        if ($hashedPassword === $dbPassword) {
            // Update login status
            sqlsrv_query($connr, "UPDATE usermapping SET loginstatus = '4' WHERE staffid = ?", array($staffid));
            
            // Generate token
            $tokenData = [
                'user_id' => $staffid,
                'role' => $role,
                'timestamp' => time(),
                'expires' => time() + (8 * 60 * 60) // 8 hours
            ];
            
            echo json_encode([
                'success' => true,
                'message' => "Login successful",
                'user' => [
                    'id' => $staffid,
                    'role' => $role
                ],
                'token' => base64_encode(json_encode($tokenData))
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => "Password incorrect!"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Username incorrect!"]);
    }
}

function handleLogout() {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    
    if ($userId) {
        global $connr;
        sqlsrv_query($connr, "UPDATE usermapping SET loginstatus = '0' WHERE staffid = ?", array($userId));
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}

function handleGetUsers() {
    global $connr;
    
    $sql = "SELECT staffid, loginid, fullname, role, department FROM usermapping";
    $stmt = sqlsrv_query($connr, $sql);
    
    $users = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $users[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
}

function handleCreateUser() {
    global $connr;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['username', 'password', 'fullname', 'role'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Encrypt and process user data
    $encryptedUsername = encdata($input['username']);
    $encryptedPassword = md5(encdata($input['password']));
    
    $sql = "INSERT INTO usermapping (loginid, Password, fullname, role, department) VALUES (?, ?, ?, ?, ?)";
    $params = [
        $encryptedUsername,
        $encryptedPassword,
        $input['fullname'],
        $input['role'],
        $input['department'] ?? null
    ];
    
    $stmt = sqlsrv_query($connr, $sql, $params);
    
    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'User created']);
    } else {
        throw new Exception("Failed to create user");
    }
}

function handleGetInventory() {
    global $connr;
    
    $sql = "SELECT item_id, item_name, quantity, price, category FROM inventory";
    $stmt = sqlsrv_query($connr, $sql);
    
    $inventory = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $inventory[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $inventory
    ]);
}

// Add more handler functions for other endpoints...

function handleSystemStatus() {
    echo json_encode([
        'success' => true,
        'status' => 'operational',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}