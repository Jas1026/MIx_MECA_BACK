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

$system = $_POST['system'] ?? '';

try {

    if (!empty($system)) {
        $pdo->exec("USE `$system`");
    }

    $stmt = $pdo->prepare("

        SELECT
            i.id_ingredients,
            i.nombre,
            i.unidad_med,
            i.tipo,
            i.location_id,
            i.proveedor_id,

            p.nombre_empresa AS nombre_proveedor,

            CASE
                WHEN i.tipo='normal' THEN i.stock_act
                ELSE (
                    SELECT COALESCE(SUM(b.peso_actual),0)
                    FROM ingredient_bottles b
                    WHERE b.ingredient_id = i.id_ingredients
                )
            END as stock_act

        FROM ingredients i

        LEFT JOIN proveedor p
            ON p.id_proveedor = i.proveedor_id

        ORDER BY i.nombre ASC

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
?>