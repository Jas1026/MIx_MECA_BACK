<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);
$system = $data['system'] ?? 'mecapos';
$pdo->exec("USE `$system` "); 

if (isset($data['id_product']) && isset($data['state'])) {
    $sql = "UPDATE products SET state = ? WHERE id_product = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$data['state'], $data['id_product']]);
    echo json_encode(["success" => $success]);
} else {
    echo json_encode(["success" => false, "error" => "Datos incompletos"]);
}