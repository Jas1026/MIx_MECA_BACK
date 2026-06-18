<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'dbconnect.php';

// Aceptamos tanto GET como POST para mayor comodidad
$order_id = $_REQUEST['order_id'] ?? null;
$system   = $_REQUEST['system'] ?? 'mixtura';

if (!$order_id) {
    echo json_encode([
        "error" => 1,
        "message" => "Falta el ID del pedido"
    ]);
    exit;
}

try {
    $pdo->exec("USE `$system`");

    // Consultamos el detalle del pedido trayendo el nombre del producto desde su tabla original
    $stmt = $pdo->prepare("
        SELECT 
            od.product_id,
            p.nombre_producto,
            od.quantity,
            od.unit_price,
            od.notes,
            od.sides
        FROM order_details od
        INNER JOIN products p ON od.product_id = p.id_product
        WHERE od.order_id = ?
    ");
    
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}