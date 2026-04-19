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
            id_ingredients,
            nombre,
            stock_actual,
            unidad_med
        FROM ingredients
        WHERE location_id = ?
    ");

    $stmt->execute([$location_id]);

    echo json_encode([
        "error" => 0,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch(Exception $e){
    echo json_encode(["error"=>1,"message"=>$e->getMessage()]);
}