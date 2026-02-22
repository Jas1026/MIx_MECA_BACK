<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["error"=>1,"message"=>"Falta order_id"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT od.*, p.nombre_producto 
    FROM order_details od
    JOIN products p ON od.product_id = p.id_product
    WHERE od.order_id = ?
");
$stmt->execute([$order_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "error"=>0,
    "data"=>$data
]);