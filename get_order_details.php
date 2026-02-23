<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

$order_id = $_GET['order_id'] ?? '';

if (!$order_id) {
    echo json_encode(["error"=>1,"message"=>"Falta order_id"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT 
            od.quantity,
            od.unit_price,
            (od.quantity * od.unit_price) AS total_price,
            p.nombre_producto
        FROM order_details od
        INNER JOIN products p ON od.product_id = p.id_product
        WHERE od.order_id = ?
    ");

    $stmt->execute([$order_id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);
}