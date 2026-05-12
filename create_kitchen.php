<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$id = $_POST['id'] ?? null; // Recibimos el ID si es edición
$name = $_POST['name'] ?? '';
$system = $_POST['system'] ?? 'mecapos';

if (empty($name)) {
    echo json_encode(["error" => 1, "message" => "El nombre es obligatorio"]);
    exit;
}

try {
    $pdo->exec("USE `$system` "); 
    
    if ($id) {
        // MODO EDICIÓN
        $stmt = $pdo->prepare("UPDATE kitchen SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $msg = "Cocina actualizada con éxito";
    } else {
        // MODO CREACIÓN
        $stmt = $pdo->prepare("INSERT INTO kitchen (name, active) VALUES (?, 1)");
        $stmt->execute([$name]);
        $msg = "Piso creado con éxito";
    }

    echo json_encode(["error" => 0, "message" => $msg]);
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}