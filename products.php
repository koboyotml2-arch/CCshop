<?php
header('Content-Type: application/json');
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$role = $_SESSION['role'] ?? null;

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
    foreach ($products as &$p) {
        $p['images'] = json_decode($p['images'] ?? '[]', true);
    }
    echo json_encode($products);
    exit;
}

if ($method === 'POST') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses la permitidu']);
        exit;
    }
    
    $nama = trim($_POST['nama'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $ukuran = trim($_POST['ukuran'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    
    if (!$nama) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Naran produk tenki isi!']);
        exit;
    }
    
    $uploadedPaths = [];
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = $_FILES['images']['name'][$key];
            $fileSize = $_FILES['images']['size'][$key];
            $fileError = $_FILES['images']['error'][$key];
            
            if ($fileError !== UPLOAD_ERR_OK) continue;
            
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowed)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Format $fileName la support"]);
                exit;
            }
            
            if ($fileSize > 5 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Size $fileName bootu liu (max 5MB)"]);
                exit;
            }
            
            $newName = uniqid('img_', true) . '.' . $ext;
            $destPath = $uploadDir . $newName;
            
            if (move_uploaded_file($tmpName, $destPath)) {
                $uploadedPaths[] = 'uploads/' . $newName;
            }
        }
    }
    
    if (empty($uploadedPaths)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Upload foto minimal 1']);
        exit;
    }
    
    $imagesJson = json_encode($uploadedPaths);
    $stmt = $pdo->prepare("INSERT INTO products (nama, warna, ukuran, harga, images, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $warna, $ukuran, $harga, $imagesJson, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses la permitidu']);
        exit;
    }
    
    parse_str(file_get_contents('php://input'), $data);
    $id = intval($data['id'] ?? 0);
    
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
                if ($product) {
            $images = json_decode($product['images'], true);
            if (is_array($images)) {
                foreach ($images as $path) {
                    $fullPath = dirname(_DIR_) . '/' . $path;
                    if (file_exists($fullPath)) unlink($fullPath);
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method la support']);
?>