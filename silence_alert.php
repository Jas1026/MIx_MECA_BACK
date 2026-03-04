<?php
header("Access-Control-Allow-Origin: *");
require_once 'dbconnect.php';

$detail_id = $_POST['detail_id'] ?? null;

if ($detail_id) {
    // Ponemos alert_status en 2 (Atendido/Silenciado)
    $stmt = $pdo->prepare("UPDATE order_details SET alert_status = 2 WHERE detail_id = ?");
    $stmt->execute([$detail_id]);
    echo json_encode(["error" => 0, "message" => "Alerta silenciada"]);
} else {
    echo json_encode(["error" => 1, "message" => "ID no proporcionado"]);
}