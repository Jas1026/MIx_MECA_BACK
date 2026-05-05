<?php

// 🔥 CORS (OBLIGATORIO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// 🔥 RESPUESTA A PREFLIGHT (MUY IMPORTANTE)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

// 🔥 RECIBIR DATOS (igual que tu otro PHP)
$id_subcategory = $_POST['id_subcategory'] ?? '';
$system = $_POST['system'] ?? '';
$stmt = $pdo->prepare("
  SELECT 
    id_product, 
    nombre_producto AS name, 
    price,
    stock_disponible
  FROM products 
  WHERE id_subcategory = ?
  AND state = 'active'
");

$stmt->execute([$id_subcategory]);

echo json_encode([
    "error" => 0,
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);