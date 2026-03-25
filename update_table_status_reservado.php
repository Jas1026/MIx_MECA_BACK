<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// 🔥 CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

// 🔥 leer JSON
$input = json_decode(file_get_contents("php://input"), true);

$id_table = $input['id_table'] ?? null;
$nuevo_estado = $input['estado'] ?? null;
$system = $input['system'] ?? 'mixtura'; // 🔥 CLAVE

if (!$id_table || !$nuevo_estado) {
    echo json_encode([
        "error" => 1,
        "message" => "Datos incompletos"
    ]);
    exit;
}

try {

    // 🔥 USAR BASE DE DATOS CORRECTA
    $pdo->exec("USE `$system`");

    // 🔥 obtener estado actual
    $stmt = $pdo->prepare("SELECT estado FROM cafe_tables WHERE id_table = ?");
    $stmt->execute([$id_table]);
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mesa) {
        echo json_encode([
            "error" => 1,
            "message" => "Mesa no encontrada en sistema: $system"
        ]);
        exit;
    }

    $estadoActual = $mesa['estado'];

    // 🔥 VALIDACIÓN
    if ($estadoActual !== 'Libre' && $estadoActual !== 'Reservado') {
        echo json_encode([
            "error" => 1,
            "message" => "No se puede cambiar estado de una mesa en servicio"
        ]);
        exit;
    }

    if (!in_array($nuevo_estado, ['Libre', 'Reservado'])) {
        echo json_encode([
            "error" => 1,
            "message" => "Estado inválido"
        ]);
        exit;
    }

    // 🔥 update
    $stmtUpdate = $pdo->prepare("
        UPDATE cafe_tables 
        SET estado = ? 
        WHERE id_table = ?
    ");
    $stmtUpdate->execute([$nuevo_estado, $id_table]);

    echo json_encode([
        "error" => 0,
        "message" => "Estado actualizado",
        "nuevo_estado" => $nuevo_estado
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}