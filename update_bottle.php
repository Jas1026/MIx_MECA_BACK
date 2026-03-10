<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

include("dbconnect.php");

$id_bottle   = $_POST['id_bottle'] ?? null;
$peso_actual = $_POST['peso_actual'] ?? 0;
$estado      = $_POST['estado'] ?? 'abierta'; // Recibido desde el TS
$system      = $_POST['system'] ?? 'mecapos';

if(!$id_bottle){
    echo json_encode(["error" => 1, "message" => "ID de botella requerido"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");

    $stmt = $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = ?, estado = ? WHERE id_bottle = ?");
    $stmt->execute([$peso_actual, $estado, $id_bottle]);

    echo json_encode(["error" => 0, "message" => "Estado actualizado"]);
} catch(Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}