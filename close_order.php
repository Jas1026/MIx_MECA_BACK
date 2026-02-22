<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);
$order_id = $input['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["error"=>1,"message"=>"Falta order_id"]);
    exit;
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("UPDATE orders SET status='closed' WHERE id_order=?");
$stmt->execute([$order_id]);

$stmt = $pdo->prepare("
    UPDATE cafe_tables 
    SET estado='Libre'
    WHERE id_table = (
        SELECT table_id FROM orders WHERE id_order=?
    )
");
$stmt->execute([$order_id]);

$pdo->commit();

echo json_encode(["error"=>0]);