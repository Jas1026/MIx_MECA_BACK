<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

$id_category = $_POST['id_category'] ?? '';

$stmt = $pdo->prepare("
  SELECT 
    id_product, 
    nombre_producto AS name, 
    price 
  FROM products 
  WHERE id_category = ?
");
$stmt->execute([$id_category]);

echo json_encode([
    "error" => 0,
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);