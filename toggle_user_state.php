<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$id = $_POST['id'] ?? '';
$state = $_POST['state'] ?? '';
$system = $_POST['system'] ?? '';

if (empty($id) || $state === '') {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    // Seleccionar la base de datos del cliente
    $pdo->exec("USE `$system` "); 
    
    // Actualizar el estado (1 activo, 0 inactivo)
    $stmt = $pdo->prepare("UPDATE user SET state = ? WHERE id = ?");
    $stmt->execute([$state, $id]);

    echo json_encode(["error" => 0, "message" => "Estado actualizado"]);
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}