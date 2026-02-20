<?php
header('Content-Type: application/json');
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Naran-utilizador tenki isi!']);
    exit;
}
if (empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password tenki isi!']);
    exit;
}
if (strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Naran-utilizador minimal letra 3']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password minimal letra 6']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Naran-utilizador uza tiha ona']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    
    echo json_encode(['success' => true, 'message' => 'Rejista susesu!']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>