<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("dbconnect.php");

$location_id = $_POST['location_id'] ?? null;
$system = $_POST['system'] ?? 'mecapos';

try {

    $pdo->exec("USE `$system`");

$stmt = $pdo->prepare("
    SELECT 
        b.ingredient_id,
        i.nombre,
        i.orden,
        COUNT(*) as cantidad

    FROM ingredient_bottles b

    JOIN ingredients i 
        ON i.id_ingredients = b.ingredient_id

    WHERE b.location_id = ?

    GROUP BY b.ingredient_id

    ORDER BY i.orden ASC
");

    $stmt->execute([$location_id]);

    echo json_encode([
        "error" => 0,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch(Exception $e){
    echo json_encode(["error"=>1,"message"=>$e->getMessage()]);
}