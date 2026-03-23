<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'dbconnect.php';

try {
    $detail_id = $_POST['detail_id'] ?? null;
    $system = $_POST['system'] ?? null;

    if (!$detail_id || !$system) {
        throw new Exception("Parámetros insuficientes");
    }

    $pdo->exec("USE `$system` ");

    $query = "SELECT 
                a.ingredient_id, 
                a.adjustment_qty as qty, 
                i.nombre, 
                i.unidad_med as unidad
              FROM order_detail_adjustments a
              INNER JOIN ingredients i ON a.ingredient_id = i.id_ingredients
              WHERE a.detail_id = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$detail_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (Throwable $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}