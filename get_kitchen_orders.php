<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$kitchen_id = $_POST['kitchen_id'] ?? 0;

try {

    $stmt = $pdo->prepare("
      SELECT 
    od.detail_id,
    od.order_id,
    od.product_id,
    od.quantity,
    od.status,
    od.alert_status,
    od.notes,
    od.sides,
    o.table_id,
    o.order_date,
    p.nombre_producto AS name,
    p.alias,
    p.time_prep
FROM order_details od
INNER JOIN orders o ON o.order_id = od.order_id
INNER JOIN products p ON p.id_product = od.product_id
WHERE od.kitchen_id = ?
AND od.status = 'pending'
AND o.status NOT IN ('paid','closed')
ORDER BY o.order_date ASC
    ");

    $stmt->execute([$kitchen_id]);

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