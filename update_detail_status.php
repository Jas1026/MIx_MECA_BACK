<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['detail_id']) || !isset($input['status'])) {
        throw new Exception("Faltan parámetros");
    }

    $detail_id = $input['detail_id'];
    $status = $input['status'];

    // Actualizar el detalle
    $stmt = $pdo->prepare("UPDATE order_details SET status = ? WHERE detail_id = ?");
    $stmt->execute([$status, $detail_id]);

    // Verificar si todos los detalles del pedido están listos
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) AS pendientes
        FROM order_details
        WHERE order_id = (
            SELECT order_id FROM order_details WHERE detail_id = ?
        )
        AND status != 'ready'
    ");
    $stmtCheck->execute([$detail_id]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // Si no hay pendientes, marcar la orden completa como ready
    if ($result['pendientes'] == 0) {

    // 1️⃣ Marcar orden como ready
    $stmtUpdateOrder = $pdo->prepare("
        UPDATE orders
        SET status = 'ready'
        WHERE order_id = (
            SELECT order_id FROM order_details WHERE detail_id = ?
        )
    ");
    $stmtUpdateOrder->execute([$detail_id]);

    // 2️⃣ Marcar mesa como READY
    $stmtUpdateTable = $pdo->prepare("
        UPDATE cafe_tables
        SET estado = 'Ready'
        WHERE id_table = (
            SELECT table_id 
            FROM orders 
            WHERE order_id = (
                SELECT order_id 
                FROM order_details 
                WHERE detail_id = ?
            )
        )
    ");
    $stmtUpdateTable->execute([$detail_id]);
}

    echo json_encode([
        "error" => 0,
        "message" => "Detalle actualizado y pedido verificado",
        "order_ready" => $result['pendientes'] == 0
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}