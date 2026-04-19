<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

// 🔥 LEER POST (no GET)
$target_db = $_POST['system'] ?? 'mixtura';
$id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

try {
    $pdo->exec("USE `$target_db` ");

    $stmt = $pdo->prepare("
        SELECT 
            location_id, 
            stock_disponible, 
            stock_congelado, 
            stock_minimo
        FROM product_location
        WHERE product_id = ?
    ");

    $stmt->execute([$id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>