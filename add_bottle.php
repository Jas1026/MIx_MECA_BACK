<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

include("dbconnect.php");

$ingredient_id   = $_POST['ingredient_id']   ?? null;
$peso_envase     = $_POST['peso_envase']     ?? 0;
$capacidad_total = $_POST['capacidad_total'] ?? 0;
$peso_actual     = $_POST['peso_actual']     ?? 0;
$cantidad        = intval($_POST['cantidad'] ?? 1); // Forzamos a entero
$system          = $_POST['system']          ?? 'mecapos';

if(!$ingredient_id){
    echo json_encode(["error" => 1, "message" => "Falta ID"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");
    
    $stmt = $pdo->prepare("INSERT INTO ingredient_bottles (ingredient_id, peso_envase, capacidad_total, peso_actual, estado) VALUES (?, ?, ?, ?, 'abierta')");

    $pdo->beginTransaction();
    for ($i = 0; $i < $cantidad; $i++) {
        $stmt->execute([$ingredient_id, $peso_envase, $capacidad_total, $peso_actual]);
    }
    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "message" => "Insertadas $cantidad botellas",
        "debug_cantidad_recibida" => $cantidad // Esto te ayudará a saber qué llegó al PHP
    ]);

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}