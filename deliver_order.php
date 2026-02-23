<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

try {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['order_id'])) {
        throw new Exception("Falta order_id");
    }

    $order_id = $input['order_id'];

    // Cambiar estado de la orden
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'ready'
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);

    echo json_encode([
        "error" => 0,
        "message" => "Orden lista para facturación"
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}