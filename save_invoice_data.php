<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once 'dbconnect.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? '';
$client_name = $data['client_name'] ?? '';
$client_nit = $data['client_nit'] ?? '';

if (!$order_id) {
    echo json_encode(["error"=>1,"message"=>"Falta order_id"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE orders 
    SET client_name = ?, client_nit = ?
    WHERE id_order = ?
");

$stmt->execute([$client_name, $client_nit, $order_id]);

echo json_encode(["error"=>0]);