<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
require_once 'dbconnect.php';

$detail_id = $_POST['detail_id'] ?? null;

if ($detail_id) {
    // Consultamos el estado actual antes de apagarlo
    $stmtCheck = $pdo->prepare("SELECT alert_status FROM order_details WHERE detail_id = ?");
    $stmtCheck->execute([$detail_id]);
    $current = $stmtCheck->fetchColumn();

    // Si era 1 (alerta a mesero), pasa a 2 (silenciado por mesero)
    // Si era 3 (alerta a cocina), pasa a 0 o 4 (silenciado por cocina)
    $newStatus = ($current == 1) ? 2 : 0;

    $stmt = $pdo->prepare("UPDATE order_details SET alert_status = ? WHERE detail_id = ?");
    $stmt->execute([$newStatus, $detail_id]);
    echo json_encode(["error" => 0, "message" => "Alerta silenciada"]);
} else {
    echo json_encode(["error" => 1, "message" => "ID no proporcionado"]);
}