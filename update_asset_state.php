<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);
$system = $data['system'] ?? 'mecapos';
$pdo->exec("USE `$system` "); 

if (isset($data['id_asset']) && isset($data['estado'])) {
    $stmt = $pdo->prepare("UPDATE assets SET estado = ? WHERE id_asset = ?");
    $stmt->execute([$data['estado'], $data['id_asset']]);
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}