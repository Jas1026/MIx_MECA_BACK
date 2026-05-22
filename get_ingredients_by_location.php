<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

include("dbconnect.php");

$location_id = $_POST['location_id'] ?? null;
$system = $_POST['system'] ?? 'mecapos';

try {

    $pdo->exec("USE `$system`");

   $stmt = $pdo->prepare("
    SELECT 
        i.id_ingredients,
        i.nombre,
        i.tipo,
        i.unidad_med,
        i.stock_act,

        COALESCE(
            (
                SELECT SUM(f.cantidad_actual)
                FROM ingredient_fractions f
                WHERE f.ingredient_id = i.id_ingredients
            ),
            0
        ) AS stock_fractional

    FROM ingredients i
    WHERE i.location_id = ?

    ORDER BY i.orden ASC
");

    $stmt->execute([$location_id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {

        if ($row['tipo'] === 'fraccionado') {
            $row['stock_final'] = floatval($row['stock_fractional']);
        } else {
            $row['stock_final'] = floatval($row['stock_act']);
        }
    }

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch(Exception $e){

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>