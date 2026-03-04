<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';
$order_id = $_POST['order_id'] ?? null;
$system = $_POST['system'] ?? 'mecapos';

if (!$order_id) {
    echo json_encode(["error" => 1, "message" => "Falta order_id"]);
    exit;
}

try {
    // Traemos los detalles del pedido y el tiempo de creación del pedido original
   // ... (dentro del try de get_order_products.php)
$stmt = $pdo->prepare("
    SELECT 
        od.detail_id,
        p.nombre_producto AS producto,
        od.quantity,
        od.status,
        od.alert_status,
        o.order_date,
        -- Obtenemos los nombres de las cocinas vinculadas al producto
        (SELECT GROUP_CONCAT(k.name SEPARATOR ' - ') 
         FROM product_kitchen pk 
         JOIN kitchen k ON pk.kitchen_id = k.id 
         WHERE pk.product_id = p.id_product) as nombres_cocinas
    FROM order_details od
    INNER JOIN products p ON od.product_id = p.id_product
    INNER JOIN orders o ON od.order_id = o.order_id
    WHERE od.order_id = ?
");

    $stmt->execute([$order_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $products
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}