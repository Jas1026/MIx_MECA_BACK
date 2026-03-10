<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

// Recibir system si lo necesitas
$system = $_POST['system'] ?? '';

try {

$stmt = $pdo->prepare("
SELECT
i.id_ingredients,
i.nombre,
i.unidad_med,
i.tipo,

CASE
 WHEN i.tipo='normal' THEN i.stock_act

 ELSE (
   SELECT COALESCE(SUM(peso_actual),0)
   FROM ingredient_bottles b
   WHERE b.ingredient_id=i.id_ingredients
 )

END as stock_act

FROM ingredients i
");


    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}