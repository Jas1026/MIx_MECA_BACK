<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
require_once 'dbconnect.php';

$detail_id = $_POST['detail_id'] ?? null;
$from = $_POST['from'] ?? 'kitchen'; // por defecto si no se manda

if ($detail_id) {
    // Si viene de cocina, pone alert_status = 1 (Suena Mesero)
    // Si viene de mesero, pone alert_status = 3 (Suena Cocina)
    $status = ($from === 'kitchen') ? 1 : 3;

    $stmt = $pdo->prepare("UPDATE order_details SET alert_status = ? WHERE detail_id = ?");
    $stmt->execute([$status, $detail_id]);
    echo json_encode(["error" => 0, "message" => "Alerta activada: " . $status]);
} else {
    echo json_encode(["error" => 1, "message" => "ID no proporcionado"]);
}