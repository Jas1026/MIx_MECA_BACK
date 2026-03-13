<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

// 1. Detección dinámica del sistema (importante si usas varias BD)
$system = $_GET['system'] ?? 'mixtura';
$id_product = isset($_GET["id_product"]) ? intval($_GET["id_product"]) : 0;

if ($id_product <= 0) {
    echo json_encode(["success" => false, "data" => [], "message" => "ID invalido"]);
    exit;
}

try {
    $pdo->exec("USE `$system` "); // Aseguramos que use la BD correcta
    
    // 2. Usamos product_id que es el nombre de tu columna
    $stmt = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ?");
    $stmt->execute([$id_product]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>