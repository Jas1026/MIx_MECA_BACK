<?php

// 🔥 CORS COMPLETO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 🔥 RESPONDER PREFLIGHT (IMPORTANTE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("dbconnect.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_location'] ?? null;
$nombre = $data['nombre'] ?? '';
$tipo = $data['tipo'] ?? '';
$parent_id = $data['parent_id'] ?? null;

if($id){
    // UPDATE
    $stmt = $pdo->prepare("
        UPDATE locations 
        SET nombre=?, tipo=?, parent_id=? 
        WHERE id_location=?
    ");
    $stmt->execute([$nombre, $tipo, $parent_id, $id]);
} else {
    // INSERT
    $stmt = $pdo->prepare("
        INSERT INTO locations (nombre, tipo, parent_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$nombre, $tipo, $parent_id]);
}

echo json_encode(["error"=>0]);