<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

$term = $_POST['term'] ?? '';

$stmt = $pdo->prepare("
SELECT 
  id_product,
  nombre_producto AS name,
  price
FROM products
WHERE nombre_producto LIKE ?
LIMIT 20
");

$stmt->execute(["%$term%"]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  "error" => 0,
  "data" => $data
]);