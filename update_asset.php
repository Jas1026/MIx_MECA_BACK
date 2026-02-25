<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);
$system = $data['system'] ?? 'mecapos';
$pdo->exec("USE `$system` "); 

if (isset($data['id_asset']) && isset($data['stock'])) {
    $sql = "UPDATE assets SET stock = ? WHERE id_asset = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['stock'], $data['id_asset']]);
    echo json_encode(["error" => 0, "message" => "Actualizado"]);
} else {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
}