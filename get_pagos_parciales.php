<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

// 🔥 PREFLIGHT (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

$order_id = $_GET['order_id'] ?? 0;
$system   = $_GET['system'] ?? 'mixtura';

if (!$order_id) {
    echo json_encode([
        "error" => 1,
        "message" => "Falta order_id"
    ]);
    exit;
}

try {

    $pdo->exec("USE `$system`");

    // 🔥 SOLO PAGOS PARCIALES
    $stmt = $pdo->prepare("
        SELECT 
            id_pago,
            nit_cliente,
            razon_social,
            metodo_pago,
            voucher,
            monto_total,
            tipo_pago
        FROM pagos_realizados 
        WHERE order_id = ? 
        AND tipo_pago = 'parcial'
        ORDER BY id_pago ASC
    ");

    $stmt->execute([$order_id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}