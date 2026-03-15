<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once 'dbconnect.php';

$order_id = $_GET['order_id'] ?? null;
$system = $_GET['system'] ?? 'mixtura';

if (!$order_id) {
    echo json_encode(["error" => 1, "message" => "Falta order_id"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");

    $stmt = $pdo->prepare("SELECT * FROM pagos_realizados WHERE order_id = ? ORDER BY fecha_pago ASC");
    $stmt->execute([$order_id]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos cada pago para incluir los nombres de los productos pagados
    foreach ($pagos as &$pago) {
        $detalle_nombres = [];
        $items = json_decode($pago['items_ids'], true);

        if (!empty($items) && is_array($items)) {
            foreach ($items as $detail_id => $cantidad) {
                // Buscamos el nombre del producto asociado a ese detail_id
                $stmtN = $pdo->prepare("
                    SELECT p.nombre_producto 
                    FROM order_details od 
                    JOIN products p ON od.product_id = p.id_product 
                    WHERE od.detail_id = ?
                ");
                $stmtN->execute([$detail_id]);
                $prod = $stmtN->fetch(PDO::FETCH_ASSOC);
                
                if ($prod) {
                    $detalle_nombres[] = $cantidad . "x " . $prod['nombre_producto'];
                }
            }
        }
        // Creamos un campo nuevo con el texto legible
        $pago['productos_pagados'] = !empty($detalle_nombres) ? implode(", ", $detalle_nombres) : "Pago parcial/monto";
    }

    echo json_encode([
        "error" => 0,
        "data" => $pagos
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}