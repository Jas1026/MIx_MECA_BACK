<?php

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, system");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
header("Content-Type: application/json; charset=utf-8");

// Responder al preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include("dbconnect.php");

// Obtener parámetros
$id = $_GET['id'] ?? null;
$system = $_GET['system'] ?? null;

// Si no vienen por GET, intentar leer JSON
if (!$id) {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? null;
    $system = $input['system'] ?? null;
}

// Validar
if (!$id) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "msg" => "ID no proporcionado"
    ]);

    exit;
}

try {

    // Eliminación lógica
    $stmt = $pdo->prepare("
        UPDATE locations
        SET estado = 0
        WHERE id_location = ?
    ");

    $stmt->execute([$id]);

    echo json_encode([
        "success" => true,
        "rows" => $stmt->rowCount(),
        "id" => $id
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

exit;