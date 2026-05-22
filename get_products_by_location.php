<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include("dbconnect.php");

$location_id = $_POST['location_id'] ?? null;
$system = $_POST['system'] ?? 'mecapos';

try {

    $pdo->exec("USE `$system`");

$stmt = $pdo->prepare("
    SELECT 
        pl.id,
        p.id_product,
        p.nombre_producto,
        pl.stock_disponible,
        pl.stock_congelado,
        pl.stock_minimo

    FROM product_location pl

    INNER JOIN products p 
        ON p.id_product = pl.product_id

    WHERE pl.location_id = ?

    ORDER BY pl.orden ASC
");

    $stmt->execute([$location_id]);

    echo json_encode([
        "error" => 0,
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch(Exception $e){

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);
}
?>