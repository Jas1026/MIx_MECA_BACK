<?php
header("Access-Control-Allow-Origin: *");
require_once 'dbconnect.php';

$detail_id = $_POST['detail_id'] ?? null;

if ($detail_id) {
    $stmt = $pdo->prepare("UPDATE order_details SET alert_status = 1 WHERE detail_id = ?");
    $stmt->execute([$detail_id]);
    echo json_encode(["error" => 0, "message" => "Alerta activada"]);
} else {
    echo json_encode(["error" => 1, "message" => "ID no proporcionado"]);
}